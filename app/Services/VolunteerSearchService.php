<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\SessionStatus;
use App\Enums\SupportMode;
use App\Models\Feedback;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Runs the volunteer directory search.
 *
 * The result is a paginated list of active volunteer profiles with three
 * computed columns:
 *  - rating        average feedback score (null when the volunteer has none)
 *  - reviews_count number of feedback entries received
 *  - is_free       whether the volunteer can take a new request right now
 *
 * Results are ordered so free volunteers appear first, then highest rated.
 */
class VolunteerSearchService
{
    /**
     * @param  array{days?: array<int, int>, modes?: array<int, string>, category_id?: int}  $filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        return $this->statsQuery()
            ->with(['user', 'skills', 'availabilities'])
            ->where('is_active', true)
            // Backend skill matching: only volunteers covering the request's category.
            ->when($filters['category_id'] ?? null, fn(Builder $query, int $categoryId) => $query->whereHas(
                'categories',
                fn(Builder $q) => $q->where('categories.id', $categoryId),
            ))
            ->when($filters['days'] ?? [], fn(Builder $query, array $days) => $query->whereHas(
                'availabilities',
                fn(Builder $q) => $q->whereIn('day_of_week', $days),
            ))
            ->when($filters['modes'] ?? [], fn(Builder $query, array $modes) => $this->applyModes($query, $modes))
            ->orderByDesc('is_free')
            ->orderByDesc('rating')
            ->orderBy('volunteer_profiles.id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * One active volunteer with full detail and computed stats, for the
     * profile page. 404s for missing or inactive profiles.
     */
    public function findForDisplay(int $id): VolunteerProfile
    {
        return $this->statsQuery()
            ->with(['user', 'skills', 'availabilities', 'categories'])
            ->where('is_active', true)
            ->findOrFail($id);
    }

    /**
     * Base query with the computed rating / reviews_count / is_free columns.
     */
    private function statsQuery(): Builder
    {
        return VolunteerProfile::query()
            ->select('volunteer_profiles.*')
            ->addSelect([
                'is_free' => $this->freeFlagSubquery(),
                'rating' => $this->feedbackAggregateSubquery('ROUND(AVG(feedback.rating), 1)'),
                'reviews_count' => $this->feedbackAggregateSubquery('COUNT(*)'),
            ]);
    }

    /**
     * Match volunteers supporting ANY of the selected modes.
     *
     * @param  array<int, string>  $modes
     */
    private function applyModes(Builder $query, array $modes): void
    {
        $query->where(function (Builder $query) use ($modes) {
            if (in_array(SupportMode::F2F->value, $modes, true)) {
                $query->orWhere('supports_f2f', true);
            }

            if (in_array(SupportMode::Virtual->value, $modes, true)) {
                $query->orWhere('supports_virtual', true);
            }
        });
    }

    /**
     * 1 when the volunteer has no pending match awaiting their response and
     * no accepted match with a session still on the schedule; 0 otherwise.
     */
    private function freeFlagSubquery(): Builder
    {
        return VolunteerMatch::query()
            ->selectRaw('CASE WHEN COUNT(*) = 0 THEN 1 ELSE 0 END')
            ->whereColumn('volunteer_matches.volunteer_profile_id', 'volunteer_profiles.id')
            ->where(function (Builder $query) {
                $query->where(fn(Builder $q) => $q
                    ->where('status', MatchStatus::Pending->value)
                    ->where('expires_at', '>', now()))
                    ->orWhere(fn(Builder $q) => $q
                        ->where('status', MatchStatus::Accepted->value)
                        ->whereHas('supportSession', fn(Builder $s) => $s
                            ->where('status', SessionStatus::Scheduled->value)));
            });
    }

    /**
     * Aggregate feedback reached through the match -> session chain.
     */
    private function feedbackAggregateSubquery(string $aggregate): Builder
    {
        return Feedback::query()
            ->selectRaw($aggregate)
            ->join('support_sessions', 'support_sessions.id', '=', 'feedback.support_session_id')
            ->join('volunteer_matches', 'volunteer_matches.id', '=', 'support_sessions.volunteer_match_id')
            ->whereColumn('volunteer_matches.volunteer_profile_id', 'volunteer_profiles.id');
    }
}
