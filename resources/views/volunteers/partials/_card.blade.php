{{-- Single volunteer card. $volunteer: VolunteerProfile, $bestMatch: bool (optional) --}}
@php($bestMatch = $bestMatch ?? false)

<div class="flex items-center gap-4 rounded-lg border bg-white px-5 {{ $bestMatch ? 'border-amber-300 ring-1 ring-amber-200' : 'border-gray-200' }}"
    style="padding-top: 28px; padding-bottom: 28px;">
    <div class="flex shrink-0 items-center justify-center rounded-lg bg-gray-200 text-lg font-semibold text-gray-400"
        style="width: 80px; height: 80px; min-width: 80px;">
        {{ \Illuminate\Support\Str::of($volunteer->user->name)->explode(' ')->map(fn($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->join('') }}
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <h2 class="text-lg font-bold leading-tight">{{ $volunteer->user->name }}</h2>
            <span class="inline-flex items-center gap-1 text-[13px] text-gray-500">
                <svg class="h-3.5 w-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 1.5l2.6 5.3 5.9.9-4.2 4.1 1 5.8L10 14.9l-5.3 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z" />
                </svg>
                @if ($volunteer->rating !== null)
                    <span class="font-semibold text-gray-800">{{ number_format($volunteer->rating, 1) }}</span>
                    <span>({{ $volunteer->reviews_count }}
                        {{ \Illuminate\Support\Str::plural('review', $volunteer->reviews_count) }})</span>
                @else
                    New volunteer
                @endif
            </span>
            @if ($bestMatch)
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Best Match</span>
            @endif
            @if (! $volunteer->is_free)
                <span
                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">Busy</span>
            @endif
        </div>

        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
            @foreach ($volunteer->skills->take(3) as $skill)
                <span class="rounded-full border border-gray-300 px-2 py-0.5 text-xs text-gray-600">
                    {{ \Illuminate\Support\Str::title($skill->name) }}
                </span>
            @endforeach
            @if ($volunteer->skills->count() > 3)
                <span class="px-1 py-0.5 text-[11px] text-gray-400">+{{ $volunteer->skills->count() - 3 }}
                    more</span>
            @endif
        </div>

        <p class="mt-1.5 text-[13px] text-gray-500">
            <span class="font-semibold text-gray-700">{{ $volunteer->supportModeLabel() }}</span>
            @if ($volunteer->availabilitySummary() !== '')
                <span class="mx-1.5 text-gray-300">•</span>
                {{ $volunteer->availabilitySummary() }}
            @endif
        </p>
    </div>

    <a href="{{ route('volunteers.show', array_filter(['volunteer' => $volunteer->id, 'support_request' => $supportRequest->id ?? null])) }}"
        class="shrink-0 rounded-md bg-[#1c3d5a] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#16304a]">
        View Profile
    </a>
</div>
