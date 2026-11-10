<?php

namespace App\Notifications;

use App\Models\SupportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the student when the volunteer marks a session as completed.
 */
class SessionCompleted extends Notification
{
    use Queueable;

    public function __construct(private SupportSession $supportSession) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $volunteer = $this->supportSession->volunteerMatch->volunteerProfile->user->name;

        return [
            'support_session_id' => $this->supportSession->id,
            'message' => $volunteer.' marked your session as completed. Please leave your feedback.',
        ];
    }
}