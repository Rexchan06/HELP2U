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
        $duration = 60;
        $daysAhead = 14;

        // Start times the volunteer is already booked for, so they aren't offered twice.
        $booked = \App\Models\SupportSession::query()
            ->where('status', \App\Enums\SessionStatus::Scheduled)
            ->whereHas('volunteerMatch', fn ($q) => $q->where('volunteer_profile_id', $volunteerProfile->id))
            ->pluck('scheduled_start')
            ->map(fn ($start) => \Illuminate\Support\Carbon::parse($start)->format('Y-m-d H:i'))
            ->all();

        // Split each weekly availability window into 1-hour slots over the next 14 days.
        // day_of_week follows Carbon: 0 = Sunday ... 6 = Saturday.
        $slotsByDate = [];

        for ($i = 0; $i < $daysAhead; $i++) {
            $date = now()->startOfDay()->addDays($i);

            foreach ($volunteerProfile->availabilities->where('day_of_week', $date->dayOfWeek) as $availability) {
                $slot = $date->copy()->setTimeFromTimeString($availability->start_time);
                $windowEnd = $date->copy()->setTimeFromTimeString($availability->end_time);

                while ($slot->copy()->addMinutes($duration)->lte($windowEnd)) {
                    if ($slot->isFuture() && ! in_array($slot->format('Y-m-d H:i'), $booked, true)) {
                        $slotsByDate[$date->toDateString()][] = $slot->copy();
                    }
                    $slot->addMinutes($duration);
                }
            }
        }

        foreach ($slotsByDate as $day => $slots) {
            $slotsByDate[$day] = collect($slots)->sort()->values()->all();
        }
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

            <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
                @forelse ($slotsByDate as $day => $slots)
                    <fieldset>
                        <legend class="text-sm font-semibold">
                            {{ \Illuminate\Support\Carbon::parse($day)->format('l, j F') }}
                        </legend>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($slots as $slot)
                                @php $value = $slot->format('Y-m-d H:i:s'); @endphp
                                <label class="cursor-pointer">
                                    <input type="radio" name="scheduled_start" value="{{ $value }}" class="peer sr-only"
                                           @checked(old('scheduled_start') === $value) required>
                                    <span class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-900 peer-checked:border-gray-900 peer-checked:bg-gray-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-gray-900 peer-focus-visible:ring-offset-2">
                                        {{ $slot->format('g:i A') }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <div class="py-8 text-center">
                        <p class="font-medium">No open time slots</p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $volunteerProfile->user->name }} has no available slots in the next {{ $daysAhead }} days.
                        </p>
                    </div>
                @endforelse

                @error('scheduled_start')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if ($isF2F)
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <label for="location" class="block text-sm font-semibold">Meeting location</label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}" maxlength="255" required
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