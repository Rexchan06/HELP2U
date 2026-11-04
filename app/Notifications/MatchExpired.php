<?php

namespace App\Notifications;

use App\Models\VolunteerMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification sent to a student when the volunteer they chose did
 * not respond before the 48-hour window closed.
 */
class MatchExpired extends Notification
{
    use Queueable;

    public function __construct(private VolunteerMatch $match) {}

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
            'volunteer_match_id' => $this->match->id,
            'support_request_id' => $this->match->support_request_id,
            'decision' => 'expired',
            'category' => $this->match->supportRequest->category->name,
            'message' => $this->match->volunteerProfile->user->name
                .' did not respond in time. Your request is open again — please choose another volunteer.',
        ];
    }
}
