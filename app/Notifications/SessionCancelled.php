<?php

namespace App\Notifications;

use App\Models\SupportSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification sent to the other participant when a session is cancelled.
 */
class SessionCancelled extends Notification
{
    use Queueable;

    public function __construct(
        private SupportSession $supportSession,
        private User $cancelledBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'support_session_id' => $this->supportSession->id,
            'message' => $this->cancelledBy->name.' cancelled the session on '
                .$this->supportSession->scheduled_start->format('d M Y, g:i A').'.',
        ];
    }
}