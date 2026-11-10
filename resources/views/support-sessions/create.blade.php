@extends('layouts.app')

@section('title', 'Choose a Session Time - HELP2U')

@section('content')
    @php
        $match = $supportRequest->volunteerMatches()
            ->where('status', \App\Enums\MatchStatus::Accepted)
            ->with(['volunteerProfile.user', 'volunteerProfile.availabilities'])
            ->firstOrFail();

        $volunteerProfile = $match->volunteerProfile;
        $isF2F = $supportRequest->support_mode === \App\Enums\SupportMode::F2F;
        $daysAhead = 14;
        $slotsByDate = $volunteerProfile->openSlots($daysAhead);
    @endphp

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('support-sessions.index') }}" class="text-sm text-gray-500 transition hover:text-gray-900">&larr; Back to sessions</a>

        <h1 class="mt-4 text-2xl font-bold tracking-tight">Choose a session time</h1>
        <p class="mt-2 text-sm text-gray-500">Sessions are 1 hour.</p>

        {{-- Request summary --}}
        <dl class="mt-6 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Volunteer</dt>
                <dd class="mt-1 font-medium">{{ $volunteerProfile->user->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Category</dt>
                <dd class="mt-1 font-medium">{{ $supportRequest->category->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Mode</dt>
                <dd class="mt-1 font-medium">{{ $isF2F ? 'Face-to-face' : 'Virtual' }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('support-sessions.store', $supportRequest) }}" class="mt-6 space-y-6">
            @csrf

            @include('support-sessions._slot-picker', [
                'volunteerName' => $volunteerProfile->user->name,
            ])

            @if ($isF2F)
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <label for="location" class="block text-sm font-semibold">Meeting location</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $volunteerProfile->location) }}" maxlength="255" required
                           placeholder="e.g. Student Lounge, Table 4"
                           @class([
                               'mt-2 block w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900',
                               'border-red-400' => $errors->has('location'),
                               'border-gray-300' => ! $errors->has('location'),
                           ])>
                    @error('location')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    This is a virtual session.
                    @if ($volunteerProfile->meeting_link)
                        You'll meet on <span class="font-medium text-gray-900">{{ $volunteerProfile->meeting_link }}</span>.
                    @else
                        Your volunteer will share the meeting link.
                    @endif
                </div>
            @endif

            <div class="flex justify-end">
                <button type="submit" @disabled(empty($slotsByDate))
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40">
                    Confirm Session
                </button>
            </div>
        </form>
    </div>
@endsection