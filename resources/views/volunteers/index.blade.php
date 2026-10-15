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

            <h1 class="text-xl font-bold">{{ $volunteers->total() }} {{ 
        \Illuminate\Support\Str::plural('volunteer', $volunteers->total()) }} found</h1>

            @php($showBestMatch = $supportRequest && $volunteers->currentPage() === 1 && $volunteers->isNotEmpty())

            @if ($showBestMatch)
                <h2 class="mt-5 text-sm font-bold uppercase tracking-wide text-amber-700">Best Match</h2>
                <div class="mt-3">
                    @include('volunteers.partials._card', ['volunteer' => $volunteers->first(), 'bestMatch' => true, 'supportRequest' => $supportRequest])
                </div>

                @if ($volunteers->count() > 1)
                    <h2 class="mt-7 text-sm font-bold uppercase tracking-wide text-gray-500">Other volunteers</h2>
                    <div class="mt-3 space-y-5">
                        @foreach ($volunteers->skip(1) as $volunteer)
                            @include('volunteers.partials._card', ['volunteer' => $volunteer, 'supportRequest' => $supportRequest])
                        @endforeach
                    </div>
                @endif
            @elseif ($volunteers->isNotEmpty())
                <div class="mt-5 space-y-5">
                    @foreach ($volunteers as $volunteer)
                        @include('volunteers.partials._card', ['volunteer' => $volunteer, 'supportRequest' => $supportRequest])
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <p class="text-sm text-gray-500">No volunteers match your filters.</p>
                    <a href="{{ route('volunteers.index', $supportRequest ? ['support_request' => $supportRequest->id] : []) }}"
                        class="mt-2 inline-block text-sm font-medium text-[#4a8ac4] hover:underline">
                        Clear all filters
                    </a>
                </div>
            @endif

            @if ($volunteers->hasPages())
                <div class="mt-6">{{ $volunteers->links() }}</div>
            @endif
        </div>
    </div>
@endsection