<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\SupportSession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $upcomingSessions = collect();

        if ($user->volunteerProfile) {
            $upcomingSessions = SupportSession::query()
                ->where('status', SessionStatus::Scheduled)
                ->where('scheduled_start', '>=', now())
                ->whereHas('volunteerMatch', fn ($q) => $q->where('volunteer_profile_id', $user->volunteerProfile->id))
                ->with('volunteerMatch.supportRequest.category')
                ->orderBy('scheduled_start')
                ->get();
        }

        return view('dashboard', compact('user', 'upcomingSessions'));
    }
}
