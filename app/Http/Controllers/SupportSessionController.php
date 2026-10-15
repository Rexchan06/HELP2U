<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportSession;
use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use Illuminate\Support\Facades\DB;
use App\Models\SupportRequest;
use App\Enums\SupportMode;
use Illuminate\Validation\Rule;
use App\Enums\SessionStatus;
use Illuminate\Support\Carbon;

class SupportSessionController extends Controller
{
    public function create(SupportRequest $supportRequest)
    {
        $userId = auth()->id();
        $student = $supportRequest->user_id;

        if ($userId != $student) {
            abort(403);
        }

        $match = $supportRequest->volunteerMatches()
            ->where('status', MatchStatus::Accepted)
            ->first();

        if (! $match) {
            return back()->with('failed', 'This request has no accepted match.');
        }

        if ($match->supportSession) {
            return back()->with('failed', 'A session has already been scheduled for this request.');
        }

        return view('support-sessions.create', compact('supportRequest'));
    }

    public function store(SupportRequest $supportRequest, Request $request)
    {
        $userId = auth()->id();

        if ($userId != $supportRequest->user_id) {
            abort(403);
        }

        $match = $supportRequest->volunteerMatches()
            ->where('status', MatchStatus::Accepted)
            ->first();

        if (! $match) {
            return back()->with('failed', 'This request has no accepted match.');
        }

        if ($match->supportSession) {
            return back()->with('failed', 'A session has already been scheduled for this request.');
        }

        $validated = $request->validate([
            'scheduled_start' => 'required|date|after:now',
            'location' => [
                Rule::requiredIf($supportRequest->support_mode === SupportMode::F2F),
                'nullable', 'string', 'max:255',
            ],
        ]);

        $start = Carbon::parse($validated['scheduled_start']);
        $end = $start->copy()->addHour();

        $fitsAvailability = $match->volunteerProfile->availabilities
            ->where('day_of_week', $start->dayOfWeek)
            ->contains(fn ($availability) =>
                $start->gte($start->copy()->setTimeFromTimeString($availability->start_time))
                && $end->lte($start->copy()->setTimeFromTimeString($availability->end_time))
            );

        if (! $fitsAvailability) {
            return back()
                ->withErrors(['scheduled_start' => 'Please choose a time within the volunteer\'s availability.'])
                ->withInput();
        }

        $alreadyBooked = SupportSession::query()
            ->where('scheduled_start', $start)
            ->where('status', SessionStatus::Scheduled)
            ->whereHas('volunteerMatch', fn ($q) => $q->where('volunteer_profile_id',
$match->volunteer_profile_id))
            ->exists();

        if ($alreadyBooked) {
            return back()
                ->withErrors(['scheduled_start' => 'That time slot has just been booked. Please pick another.'])
                ->withInput();
        }

        $validated['volunteer_match_id'] = $match->id;

        $support_session = DB::transaction(function () use ($validated, $supportRequest) {
            $session = SupportSession::create($validated);

            $supportRequest->update(['status' => RequestStatus::Scheduled]);

            return $session;
        });

        return redirect()->route('support-sessions.show', $support_session)->with('success', 'Support session created successfully!');
    }

    public function index()
    {
        $userId = auth()->id();

        $supportSessions = SupportSession::query()
            ->whereHas('volunteerMatch.supportRequest', fn ($q) => $q->where('user_id', $userId))
            ->orWhereHas('volunteerMatch.volunteerProfile', fn ($q) => $q->where('user_id', $userId))
            ->latest('scheduled_start')
            ->get();

        return view('support-sessions.index', compact('supportSessions'));
    }

    public function show(SupportSession $supportSession)
    {
        $userId = auth()->id();
        $student = $supportSession->volunteerMatch->supportRequest->user_id;
        $volunteer = $supportSession->volunteerMatch->volunteerProfile->user_id;

        if ($userId != $student && $userId != $volunteer) {
            abort(403);
        }

        return view('support-sessions.show', compact('supportSession'));
    }
}
