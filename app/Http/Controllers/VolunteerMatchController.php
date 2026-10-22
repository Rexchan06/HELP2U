<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Models\SupportRequest;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use App\Notifications\MatchRequested;
use App\Notifications\MatchResponded;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VolunteerMatchController extends Controller
{
    /** Hours a volunteer has to respond before the match expires. */
    private const RESPONSE_WINDOW_HOURS = 48;

    /**
     * Send the student's support request to the chosen volunteer:
     * create a PENDING match (48h response window), flag the request as
     * pending, and notify the volunteer in-app.
     */
    public function store(Request $request, VolunteerProfile $volunteer): RedirectResponse
    {
        abort_unless($volunteer->is_active, 404);

        $data = $request->validate([
            'support_request' => ['required', 'integer', Rule::exists('support_requests', 'id')],
        ]);

        $supportRequest = SupportRequest::findOrFail($data['support_request']);

        abort_unless($supportRequest->user_id === $request->user()->id, 403);

        if ($volunteer->user_id === $request->user()->id) {
            return $this->backWithError($volunteer, $supportRequest, 'You cannot send a request to yourself.');
        }

        // One active volunteer per request — the student picks another only
        // after the current match is rejected or expires.
        $hasActiveMatch = $supportRequest->volunteerMatches()
            ->where(fn ($q) => $q
                ->where('status', MatchStatus::Accepted->value)
                ->orWhere(fn ($q2) => $q2
                    ->where('status', MatchStatus::Pending->value)
                    ->where('expires_at', '>', now())))
            ->exists();

        if ($hasActiveMatch) {
            return $this->backWithError($volunteer, $supportRequest, 'This request is already waiting for a volunteer to respond.');
        }

        DB::transaction(function () use ($supportRequest, $volunteer): void {
            // Re-sending to the same volunteer after a rejection revives the
            // pair row instead of violating its unique constraint.
            $supportRequest->volunteerMatches()->updateOrCreate(
                ['volunteer_profile_id' => $volunteer->id],
                [
                    'status' => MatchStatus::Pending,
                    'expires_at' => now()->addHours(self::RESPONSE_WINDOW_HOURS),
                    'responded_at' => null,
                ],
            );

            $supportRequest->update(['status' => RequestStatus::Pending]);
        });

        $volunteer->user->notify(new MatchRequested($supportRequest));

        return redirect()
            ->route('support-requests.index')
            ->with('success', 'Your request was sent to '.$volunteer->user->name.'. They have '.self::RESPONSE_WINDOW_HOURS.' hours to respond.');
    }

    private function backWithError(VolunteerProfile $volunteer, SupportRequest $supportRequest, string $message): RedirectResponse
    {
        return redirect()
            ->route('volunteers.show', ['volunteer' => $volunteer, 'support_request' => $supportRequest->id])
            ->with('failed', $message);
    }

    /**
     * The volunteer's inbox: every request sent to them, newest first.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->volunteerProfile;

        if (! $profile) {
            return redirect()
                ->route('volunteer.profile')
                ->with('failed', 'Set up your volunteer profile to receive support requests.');
        }

        $matches = $profile->volunteerMatches()
            ->with(['supportRequest.category', 'supportRequest.user'])
            ->latest()
            ->paginate(10);

        return view('volunteer-matches.index', compact('matches'));
    }

    /**
     * Full request detail for the volunteer. Opening it marks the related
     * notification as read.
     */
    public function show(Request $request, VolunteerMatch $match): View
    {
        abort_unless($match->volunteerProfile->user_id === $request->user()->id, 403);

        $match->load(['supportRequest.category', 'supportRequest.user']);

        $request->user()->unreadNotifications()
            ->where('data->support_request_id', $match->support_request_id)
            ->update(['read_at' => now()]);

        return view('volunteer-matches.show', compact('match'));
    }

    /**
     * Accept or reject a pending request: flag the match with the decision,
     * move the support request to its next state, and inform the student.
     */
    public function respond(Request $request, VolunteerMatch $match): RedirectResponse
    {
        abort_unless($match->volunteerProfile->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        $answerable = $match->status === MatchStatus::Pending && $match->expires_at->isFuture();

        if (! $answerable) {
            return back()->with('failed', 'This request can no longer be answered.');
        }

        $accept = $data['decision'] === 'accept';

        DB::transaction(function () use ($match, $accept): void {
            $match->update([
                'status' => $accept ? MatchStatus::Accepted : MatchStatus::Rejected,
                'responded_at' => now(),
            ]);

            // Accepting starts the scheduling phase; rejecting re-opens the
            // request so the student can pick another volunteer.
            $match->supportRequest->update([
                'status' => $accept ? RequestStatus::Matched : RequestStatus::Open,
            ]);
        });

        $match->supportRequest->user->notify(new MatchResponded($match->refresh()));

        return redirect()
            ->route('support-requests.index')
            ->with('success', $accept
                ? 'Request accepted. The student can now schedule a session with you.'
                : 'Request rejected. It is open for another volunteer again.');
    }
}
