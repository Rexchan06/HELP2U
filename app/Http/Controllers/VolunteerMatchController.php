<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Models\SupportRequest;
use App\Models\VolunteerProfile;
use App\Notifications\MatchRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
}
