<?php

namespace App\Notifications;

use App\Models\SupportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Sent to the volunteer when the student reschedules a session.
 */
class SessionRescheduled extends Notification
{
    use Queueable;

    public function __construct(
        private SupportSession $supportSession,
        private Carbon $oldStart,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $student = $this->supportSession->volunteerMatch->supportRequest->user->name;

        return [
            'support_session_id' => $this->supportSession->id,
            'message' => $student.' rescheduled your session from '
                .$this->oldStart->format('d M Y, g:i A').' to '
                .$this->supportSession->scheduled_start->format('d M Y, g:i A').'.',
        ];
    }
}