<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportSession;
use App\Enums\MatchStatus;
use App\Models\SupportRequest;

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
            'location' => 'nullable|string|max:255',
        ]);

        $validated['volunteer_match_id'] = $match->id;

        $support_session = SupportSession::create($validated);

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
