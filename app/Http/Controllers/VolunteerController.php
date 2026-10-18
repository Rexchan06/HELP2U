<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Http\Requests\SearchVolunteersRequest;
use App\Models\SupportRequest;
use App\Models\VolunteerProfile;
use App\Services\VolunteerSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VolunteerController extends Controller
{
    /**
     * Display the filterable volunteer directory.
     */
    public function index(SearchVolunteersRequest $request, VolunteerSearchService $search): View
    {
        $supportRequest = $request->supportRequestContext();
        $filters = $request->filters();
        $filters['exclude_user_id'] = $request->user()->id;

        if ($supportRequest) {
            // Backend skill filter: only volunteers covering the request's category.
            $filters['category_id'] = $supportRequest->category_id;

            // Relevance boost: volunteers free on the student's preferred day rank higher.
            $filters['preferred_day'] = $supportRequest->preferred_datetime?->dayOfWeek;

            // A fresh redirect pre-checks the mode the student asked for;
            // once they touch the filters themselves we respect their choice.
            if (! $request->hasFilterInput()) {
                $filters['modes'] = [$supportRequest->support_mode->value];
            }
        }

        return view('volunteers.index', [
            'volunteers' => $search->search($filters),
            'filters' => $filters,
            'supportRequest' => $supportRequest,
        ]);
    }

    /**
     * Display a single volunteer's profile (bio, skills, weekly availability).
     * Carries the support_request context so the "Send Request" button can
     * link the chosen volunteer back to that request.
     */
    public function show(Request $request, VolunteerProfile $volunteer, VolunteerSearchService $search): View
    {
        abort_unless($volunteer->is_active, 404);

        $volunteer = $search->findForDisplay($volunteer->id);

        $supportRequest = ($id = (int) $request->query('support_request')) > 0
            ? SupportRequest::with('category')->find($id)
            : null;

        $alreadySent = $supportRequest !== null && $volunteer->volunteerMatches()
            ->where('support_request_id', $supportRequest->id)
            ->where(fn ($q) => $q
                ->where('status', MatchStatus::Accepted->value)
                ->orWhere(fn ($q2) => $q2
                    ->where('status', MatchStatus::Pending->value)
                    ->where('expires_at', '>', now())))
            ->exists();

        return view('volunteers.show', compact('volunteer', 'supportRequest', 'alreadySent'));
    }
}
