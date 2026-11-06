<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\Feedback;
use App\Models\SupportSession;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
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