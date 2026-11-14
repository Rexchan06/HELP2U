@extends('layouts.app')

@section('title', 'Session Details - HELP2U')

@section('content')
    @php
        $supportSession->loadMissing([
            'volunteerMatch.supportRequest.user',
            'volunteerMatch.supportRequest.category',
            'volunteerMatch.volunteerProfile.user',
            'cancelledBy',
            'feedback'
        ]);

        $supportRequest = $supportSession->volunteerMatch->supportRequest;
        $volunteerProfile = $supportSession->volunteerMatch->volunteerProfile;
        $isStudent = $supportRequest->user_id == auth()->id();
        $otherUser = $isStudent ? $volunteerProfile->user : $supportRequest->user;
        $isF2F = $supportRequest->support_mode === \App\Enums\SupportMode::F2F;

        $start = $supportSession->scheduled_start;
        $end = $start->copy()->addMinutes($supportSession->duration_minutes ?? 60);

        $status = $supportSession->status->value;
        $isScheduled = $supportSession->status === \App\Enums\SessionStatus::Scheduled;

        $statusStyles = [
            'SCHEDULED' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'COMPLETED' => 'bg-green-50 text-green-700 ring-green-200',
            'CANCELLED' => 'bg-gray-100 text-gray-600 ring-gray-200',
        ];

        $details = [
            'With' => $otherUser->name . ' (' . ($isStudent ? 'Volunteer' : 'Student') . ')',
            'Category' => $supportRequest->category->name,
            'Date' => $start->format('l, j F Y'),
            'Time' => $start->format('g:i A') . ' – ' . $end->format('g:i A'),
            'Mode' => $isF2F ? 'Face-to-face' : 'Virtual',
        ];
    @endphp

    <div class="mx-auto max-w-xl">
        <a href="{{ route('support-sessions.index') }}" class="text-sm text-gray-500 transition hover:text-gray-900">&larr; Back to sessions</a>

        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-bold tracking-tight">Session Details</h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyles[$status] ?? $statusStyles['CANCELLED'] }}">
                    {{ ucfirst(strtolower($status)) }}
                </span>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                @foreach ($details as $label => $value)
                    <div class="grid grid-cols-[6rem_1fr] gap-4">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="font-medium">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 text-sm">
                <p class="text-gray-500">Request description</p>
                <p class="mt-1 whitespace-pre-line">{{ $supportRequest->description }}</p>
            </div>

            {{-- Where to meet --}}
            <div class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-sm">
                @if ($isF2F)
                    <span class="text-gray-500">Meeting location:</span>
                    <span class="font-medium break-words">{{ $supportSession->location ?? $volunteerProfile->location ?? 'To be confirmed' }}</span>
                @elseif ($volunteerProfile->meeting_link)
                    <span class="text-gray-500">Meeting link:</span>
                    <a href="{{ $volunteerProfile->meeting_link }}" target="_blank" rel="noopener noreferrer"
                       class="break-all font-medium underline underline-offset-2">{{ $volunteerProfile->meeting_link }}</a>
                @else
                    <span class="text-gray-500">The volunteer hasn't added a meeting link yet.</span>
                @endif
            </div>

            @if ($supportSession->notes)
                <div class="mt-4 text-sm">
                    <p class="text-gray-500">Notes</p>
                    <p class="mt-1 whitespace-pre-line">{{ $supportSession->notes }}</p>
                </div>
            @endif

            @if ($supportSession->completed_at)
                <p class="mt-4 text-sm text-gray-500">Completed on {{ $supportSession->completed_at->format('j M Y, g:i A') }}.</p>
            @endif

            @if ($supportSession->cancelled_at)
                <p class="mt-4 text-sm text-gray-500">
                    Cancelled on {{ $supportSession->cancelled_at->format('j M Y, g:i A') }}
                    @if ($supportSession->cancelledBy) by {{ $supportSession->cancelledBy->name }}@endif.
                </p>
            @endif

            {{-- Feedback --}}
            @php $feedback = $supportSession->feedback; @endphp

            @if ($feedback)
                <div class="mt-6 rounded-lg border border-gray-200 px-4 py-3 text-sm">
                    <p class="text-gray-500">{{ $isStudent ? 'Your feedback' : 'Feedback from ' . $supportRequest->user->name }}</p>
                    <p class="mt-1 text-lg text-amber-500" aria-label="Rated {{ $feedback->rating }} out of 5">
                        {{ str_repeat('★', $feedback->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $feedback->rating) }}</span>
                    </p>
                    @if ($feedback->comment)
                        <p class="mt-1 whitespace-pre-line">{{ $feedback->comment }}</p>
                    @endif
                </div>
            @elseif ($isStudent && $supportSession->status === \App\Enums\SessionStatus::Completed)
                <form method="POST" action="{{ route('feedback.store', $supportSession) }}" class="mt-6 space-y-4 rounded-lg border border-gray-200 p-4">
                    @csrf
                    <fieldset>
                        <legend class="text-sm font-semibold">How was your session?</legend>
                        <div class="mt-2 flex gap-2">
                            @foreach (range(1, 5) as $value)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $value }}" class="peer sr-only" @checked(old('rating') == $value) required>
                                    <span class="inline-flex h-10 w-12 items-center justify-center rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 transition hover:border-gray-900 peer-checked:border-amber-500 peer-checked:bg-amber-500 peer-checked:text-white">
                                        {{ $value }}★
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-500">1 = poor, 5 = excellent</p>
                        @error('rating')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </fieldset>

                    <div>
                        <label for="comment" class="text-sm font-semibold">Comment <span class="font-normal text-gray-500">(optional)</span></label>
                        <textarea id="comment" name="comment" rows="3" maxlength="200"
                                  class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">{{ old('comment') }}</textarea>
                        @error('comment')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                        Submit Feedback
                    </button>
                </form>
            @elseif ($isStudent && $isScheduled)
                <p class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-500">
                    Feedback is only allowed after the session is completed.
                </p>
            @elseif (! $isStudent && $supportSession->status === \App\Enums\SessionStatus::Completed)
                <p class="mt-6 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-500">
                    Waiting for {{ $supportRequest->user->name }}'s feedback.
                </p>
            @endif

            @if ($isScheduled)
                <div class="mt-8 space-y-2">
                    @if (! $isStudent)
                        @if ($start->isPast())
                            <form method="POST" action="{{ route('support-sessions.complete', $supportSession) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                                    Mark as Completed
                                </button>
                            </form>
                        @else
                            <button type="button" disabled title="Available once the session has started"
                                    class="w-full cursor-not-allowed rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white opacity-40">
                                Mark as Completed
                            </button>
                        @endif
                    @endif

                    @if ($isStudent && $start->isFuture())
                        <a href="{{ route('support-sessions.reschedule', $supportSession) }}"
                           class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 transition hover:border-gray-900 hover:text-gray-900">
                            Reschedule
                        </a>
                    @endif

                    @if ($start->isFuture())
                        <form method="POST" action="{{ route('support-sessions.cancel', $supportSession) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-red-400 hover:text-red-600">
                                Cancel Session
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection