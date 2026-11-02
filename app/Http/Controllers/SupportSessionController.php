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
use App\Notifications\SessionCancelled;
use App\Models\VolunteerMatch;
use App\Notifications\SessionRescheduled;

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

        if ($error = $this->slotError($match, $start)) {
            return back()->withErrors(['scheduled_start' => $error])->withInput();
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

    public function cancel(SupportSession $supportSession)
    {
        $userId = auth()->id();
        $student = $supportSession->volunteerMatch->supportRequest->user;
        $volunteer = $supportSession->volunteerMatch->volunteerProfile->user;

        if ($userId != $student->id && $userId != $volunteer->id) {
            abort(403);
        }

        if ($supportSession->status !== SessionStatus::Scheduled) {
            return back()->with('failed', 'Only scheduled sessions can be cancelled.');
        }

        if ($supportSession->scheduled_start->isPast()) {
            return back()->with('failed', 'This session has already started and can no longer be cancelled.');
        }

        $supportSession->update([
            'status' => SessionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
        ]);

        // Notify the other participant
        $otherParty = $userId == $student->id ? $volunteer : $student;
        $otherParty->notify(new SessionCancelled($supportSession, auth()->user()));

        return redirect()
            ->route('support-sessions.show', $supportSession)
            ->with('success', 'Session cancelled. The other participant has been notified.');
    }

    // Shared checks for editSchedule() and reschedule()
    private function rescheduleError(SupportSession $supportSession): ?string
    {
        if (auth()->id() != $supportSession->volunteerMatch->supportRequest->user_id) {
            abort(403);
        }

        if ($supportSession->status !== SessionStatus::Scheduled) {
            return 'Only scheduled sessions can be rescheduled.';
        }

        if ($supportSession->scheduled_start->isPast()) {
            return 'This session has already started and can no longer be rescheduled.';
        }

        return null;
    }

    public function editSchedule(SupportSession $supportSession)
    {
        if ($error = $this->rescheduleError($supportSession)) {
            return back()->with('failed', $error);
        }

        return view('support-sessions.reschedule', compact('supportSession'));
    }

    public function reschedule(SupportSession $supportSession, Request $request)
    {
        if ($error = $this->rescheduleError($supportSession)) {
            return back()->with('failed', $error);
        }

        $validated = $request->validate([
            'scheduled_start' => 'required|date|after:now',
        ]);

        $start = Carbon::parse($validated['scheduled_start']);

        if ($start->eq($supportSession->scheduled_start)) {
            return back()
                ->withErrors(['scheduled_start' => 'Please choose a different time from the current one.'])
                ->withInput();
        }

        $match = $supportSession->volunteerMatch;

        if ($error = $this->slotError($match, $start, $supportSession->id)) {
            return back()->withErrors(['scheduled_start' => $error])->withInput();
        }

        $oldStart = $supportSession->scheduled_start->copy();
        $supportSession->update(['scheduled_start' => $start]);

        $match->volunteerProfile->user->notify(new SessionRescheduled($supportSession, $oldStart));

        return redirect()
            ->route('support-sessions.show', $supportSession)
            ->with('success', 'Session rescheduled. The volunteer has been notified.');
    }

    private function slotError(VolunteerMatch $match, Carbon $start, ?int $ignoreSessionId = null): ?string
    {
        $end = $start->copy()->addHour();

        $fitsAvailability = $match->volunteerProfile->availabilities
            ->where('day_of_week', $start->dayOfWeek)
            ->contains(fn ($availability) =>
                $start->gte($start->copy()->setTimeFromTimeString($availability->start_time))
                && $end->lte($start->copy()->setTimeFromTimeString($availability->end_time))
            );

        if (! $fitsAvailability) {
            return 'Please choose a time within the volunteer\'s availability.';
        }

        $alreadyBooked = SupportSession::query()
            ->where('scheduled_start', $start)
            ->where('status', SessionStatus::Scheduled)
            ->when($ignoreSessionId, fn ($q) => $q->whereKeyNot($ignoreSessionId))
            ->whereHas('volunteerMatch', fn ($q) => $q->where('volunteer_profile_id', $match->volunteer_profile_id))
            ->exists();

        return $alreadyBooked ? 'That time slot has just been booked. Please pick another.' : null;
    }
}
