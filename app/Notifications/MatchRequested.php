<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification sent to a volunteer when a student sends them a
 * support request to review.
 */
class MatchRequested extends Notification
{
    use Queueable;

    public function __construct(private SupportRequest $supportRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'support_request_id' => $this->supportRequest->id,
            'category' => $this->supportRequest->category->name,
            'message' => $this->supportRequest->user->name.' sent you a support request.',
        ];
    }
}
