<?php

namespace App\Notifications;

use App\Enums\MatchStatus;
use App\Models\VolunteerMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification sent to a student when the volunteer they chose
 * accepts or rejects their support request.
 */
class MatchResponded extends Notification
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
        $accepted = $this->match->status === MatchStatus::Accepted;
        $volunteer = $this->match->volunteerProfile->user->name;

        return [
            'volunteer_match_id' => $this->match->id,
            'support_request_id' => $this->match->support_request_id,
            'decision' => $accepted ? 'accepted' : 'rejected',
            'category' => $this->match->supportRequest->category->name,
            'message' => $accepted
                ? $volunteer.' accepted your request. You can now schedule a session.'
                : $volunteer.' could not take your request. Please choose another volunteer.',
        ];
    }
}
