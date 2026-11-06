@extends('layouts.app')

@section('title', 'Reschedule Session - HELP2U')

@section('content')
    @php
        $supportSession->loadMissing([
            'volunteerMatch.supportRequest.category',
            'volunteerMatch.volunteerProfile.user',
            'volunteerMatch.volunteerProfile.availabilities',
        ]);

        $volunteerProfile = $supportSession->volunteerMatch->volunteerProfile;
        $current = $supportSession->scheduled_start;
        $daysAhead = 14;
        $slotsByDate = $volunteerProfile->openSlots($daysAhead);
    @endphp

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('support-sessions.show', $supportSession) }}" class="text-sm text-gray-500 transition hover:text-gray-900">&larr; Back to session</a>

        <h1 class="mt-4 text-2xl font-bold tracking-tight">Reschedule session</h1>
        <p class="mt-2 text-sm text-gray-500">Pick a new 1-hour slot. {{ $volunteerProfile->user->name }} will be notified of the change.</p>

        {{-- Current booking --}}
        <dl class="mt-6 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Volunteer</dt>
                <dd class="mt-1 font-medium">{{ $volunteerProfile->user->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Category</dt>
                <dd class="mt-1 font-medium">{{ $supportSession->volunteerMatch->supportRequest->category->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Current time</dt>
                <dd class="mt-1 font-medium">{{ $current->format('D, j M · g:i A') }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('support-sessions.reschedule.update', $supportSession) }}" class="mt-6 space-y-6">
            @csrf
            @method('PATCH')

            @include('support-sessions._slot-picker', [
                'volunteerName' => $volunteerProfile->user->name,
            ])

            <div class="flex justify-end gap-3">
                <a href="{{ route('support-sessions.show', $supportSession) }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-900">
                    Keep current time
                </a>
                <button type="submit" @disabled(empty($slotsByDate))
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40">
                    Confirm New Time
                </button>
            </div>
        </form>
    </div>
@endsection
