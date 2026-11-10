<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\Feedback;
use App\Models\SupportSession;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // Feedback the user gave as a student, or received as a volunteer
        $feedback = Feedback::query()
            ->whereHas('supportSession.volunteerMatch.supportRequest', fn ($q) => $q->where('user_id', $userId))
            ->orWhereHas('supportSession.volunteerMatch.volunteerProfile', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'supportSession.volunteerMatch.supportRequest.user',
                'supportSession.volunteerMatch.supportRequest.category',
                'supportSession.volunteerMatch.volunteerProfile.user',
            ])
            ->latest()
            ->get();

        return view('feedback.index', compact('feedback'));
    }

    public function store(SupportSession $supportSession, Request $request)
    {
        if (auth()->id() != $supportSession->volunteerMatch->supportRequest->user_id) {
            abort(403);
        }

        if ($supportSession->status !== SessionStatus::Completed) {
            return back()->with('failed', 'Feedback is only allowed after the session is completed.');
        }

        if ($supportSession->feedback) {
            return back()->with('failed', 'You have already given feedback for this session.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:200',
        ]);

        Feedback::create([
            'support_session_id' => $supportSession->id,
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()
            ->route('support-sessions.show', $supportSession)
            ->with('success', 'Thank you! Your feedback has been submitted.');
    }
}