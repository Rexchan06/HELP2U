<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Models\VolunteerMatch;
use App\Notifications\MatchExpired;
use Illuminate\Support\Facades\DB;

class MatchExpiryService
{
    public function expireStale(): int
    {
        $stale = VolunteerMatch::with(['supportRequest.user', 'volunteerProfile.user'])
            ->where('status', MatchStatus::Pending->value)
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($stale as $match) {
            DB::transaction(function () use ($match): void {
                $match->update(['status' => MatchStatus::Expired]);

                if ($match->supportRequest->status === RequestStatus::Pending) {
                    $match->supportRequest->update(['status' => RequestStatus::Open]);
                }
            });

            $match->supportRequest->user->notify(new MatchExpired($match));
        }

        return $stale->count();
    }
}
