<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VolunteerMatch;
use App\Notifications\MatchRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * The signed-in user's notifications, newest first.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Open a notification: mark it as read and continue to the page it
     * refers to (the new request for volunteers, the requests list for
     * students).
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return redirect($this->targetUrl($request->user(), $notification));
    }

    /**
     * Mark every unread notification as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index');
    }

    private function targetUrl(User $user, DatabaseNotification $notification): string
    {
        // A volunteer clicking "X sent you a support request" lands on that
        // match's detail page, where they can accept or decline.
        if ($notification->type === MatchRequested::class) {
            $match = $user->volunteerProfile?->volunteerMatches()
                ->where('support_request_id', $notification->data['support_request_id'] ?? 0)
                ->latest()
                ->first();

            return $match
                ? route('volunteer-matches.show', $match)
                : route('support-requests.index');
        }

        // Accepted: jump straight to the session — view it if scheduled,
        // otherwise open the scheduling form for that request.
        if (($notification->data['decision'] ?? null) === 'accepted') {
            $match = VolunteerMatch::with('supportSession')
                ->find($notification->data['volunteer_match_id'] ?? 0);

            if ($match?->supportSession) {
                return route('support-sessions.show', $match->supportSession);
            }

            if ($match) {
                return route('support-sessions.create', $match->support_request_id);
            }
        }

        // Rejected / expired — the student lands on their requests list,
        // which shows the resulting status and next action.
        return route('support-requests.index');
    }
}
