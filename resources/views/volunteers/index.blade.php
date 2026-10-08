@extends('layouts.app')

@section('title', 'Find Volunteers - HELP2U')

@section('content')
    <div class="flex items-start gap-6">
        <form method="GET" action="{{ route('volunteers.index') }}"
            style="width: 208px; flex-shrink: 0; background: #e8f1f9; border-radius: 8px; padding: 20px;">
            <input type="hidden" name="applied" value="1">
            @if ($supportRequest)
                <input type="hidden" name="support_request" value="{{ $supportRequest->id }}">
            @endif

            <h2
                style="font-size: 11px; font-weight: 700; letter-spacing: 0.05em; color: #374151; text-transform: uppercase; margin: 0;">
                Support Mode
            </h2>
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                @foreach (\App\Enums\SupportMode::cases() as $mode)
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="modes[]" value="{{ $mode->value }}" @checked(in_array($mode->value, $filters['modes'], true)) onchange="this.form.submit()"
                            style="width: 14px; height: 14px; accent-color: #111827;">
                        {{ $mode->label() }}
                    </label>
                @endforeach
            </div>

            <hr style="margin: 16px 0; border: 0; border-top: 1px solid #d1d5db;">

            <h2
                style="font-size: 11px; font-weight: 700; letter-spacing: 0.05em; color: #374151; text-transform: uppercase; margin: 0;">
                Available On
            </h2>
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                @foreach (\App\Models\Availability::DAY_ORDER as $day)
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, $filters['days'], true))
                            onchange="this.form.submit()" style="width: 14px; height: 14px; accent-color: #111827;">
                        {{ \App\Models\Availability::DAYS[$day] }}
                    </label>
                @endforeach
            </div>

            <a href="{{ route('volunteers.index', $supportRequest ? ['support_request' => $supportRequest->id] : []) }}"
                style="display: inline-block; margin-top: 20px; font-size: 12px; color: #6b7280; text-decoration: none;">
                Clear filters
            </a>
        </form>

        <div class="min-w-0 flex-1">
            @if ($supportRequest)
                <p class="mb-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-800">
                    Finding volunteers for your
                    <strong>{{ $supportRequest->category->name }}</strong>
                    request ({{ $supportRequest->support_mode->label() }}).
                </p>
            @endif

            <h1 class="text-xl font-bold">{{ $volunteers->total() }}
                {{ \Illuminate\Support\Str::plural('volunteer', $volunteers->total()) }} found
            </h1>

            <div class="mt-5 space-y-5">
                @forelse ($volunteers as $volunteer)
                    <div class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white px-5"
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
                                @if (!$volunteer->is_free)
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

                        <a href="{{ route('volunteers.show', $volunteer) }}"
                            class="shrink-0 rounded-md bg-[#1c3d5a] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#16304a]">
                            View Profile
                        </a>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                        <p class="text-sm text-gray-500">No volunteers match your filters.</p>
                        <a href="{{ route('volunteers.index', $supportRequest ? ['support_request' => $supportRequest->id] : []) }}"
                            class="mt-2 inline-block text-sm font-medium text-[#4a8ac4] hover:underline">
                            Clear all filters
                        </a>
                    </div>
                @endforelse
            </div>

            @if ($volunteers->hasPages())
                <div class="mt-6">{{ $volunteers->links() }}</div>
            @endif
        </div>
    </div>
@endsection