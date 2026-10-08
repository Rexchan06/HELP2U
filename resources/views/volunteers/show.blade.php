@extends('layouts.app')

@section('title', $volunteer->user->name . ' - HELP2U')

@section('content')
    @php
        $chip = 'display: inline-block; border: 1px solid #d1d5db; border-radius: 9999px; padding: 3px 10px; font-size: 11px; color: #4b5563; line-height: 1.4;';
        $card = 'background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px 32px;';
        $heading = 'font-size: 14px; font-weight: 600; margin: 0; color: #111827;';
        $divider = 'margin: 20px 0; border: 0; border-top: 1px solid #e5e7eb;';
    @endphp

    <div style="max-width: 768px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">

        {{-- Header card --}}
        <div style="{{ $card }} display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div
                    style="width: 80px; height: 80px; min-width: 80px; border-radius: 9999px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; color: #9ca3af;">
                    {{ \Illuminate\Support\Str::of($volunteer->user->name)->explode(' ')->map(fn($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->join('') }}
                </div>

                <div>
                    <h1 style="font-size: 18px; font-weight: 700; margin: 0;">{{ $volunteer->user->name }}</h1>

                    <div
                        style="margin-top: 4px; display: flex; align-items: center; gap: 4px; font-size: 13px; color: #6b7280;">
                        <svg style="width: 14px; height: 14px; color: #9ca3af;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 1.5l2.6 5.3 5.9.9-4.2 4.1 1 5.8L10 14.9l-5.3 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5z" />
                        </svg>
                        @if ($volunteer->rating !== null)
                            <strong style="color: #1f2937;">{{ number_format($volunteer->rating, 1) }}</strong>
                            <span>({{ $volunteer->reviews_count }}
                                {{ \Illuminate\Support\Str::plural('review', $volunteer->reviews_count) }})</span>
                        @else
                            <span>New volunteer</span>
                        @endif
                    </div>

                    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px;">
                        @if ($volunteer->supports_virtual)
                            <span style="{{ $chip }}">{{ \App\Enums\SupportMode::Virtual->label() }}</span>
                        @endif
                        @if ($volunteer->supports_f2f)
                            <span style="{{ $chip }}">{{ \App\Enums\SupportMode::F2F->label() }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <a href="#"
                style="flex-shrink: 0; background: #1c3d5a; color: #fff; border-radius: 6px; padding: 10px 20px; font-size: 14px; font-weight: 500; text-decoration: none;">
                Send Request
            </a>
        </div>

        {{-- Details card --}}
        <div style="{{ $card }}">
            <h2 style="{{ $heading }}">About {{ \Illuminate\Support\Str::before($volunteer->user->name, ' ') }}</h2>
            <p style="margin: 8px 0 0; font-size: 14px; line-height: 1.6; color: #4b5563;">
                {{ $volunteer->bio ?: 'This volunteer has not written a bio yet.' }}
            </p>

            <hr style="{{ $divider }}">

            <h2 style="{{ $heading }}">Skills</h2>
            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px;">
                @forelse ($volunteer->skills as $skill)
                    <span style="{{ $chip }}">{{ \Illuminate\Support\Str::title($skill->name) }}</span>
                @empty
                    <p style="margin: 0; font-size: 14px; color: #9ca3af;">No skills listed yet.</p>
                @endforelse
            </div>

            <hr style="{{ $divider }}">

            <h2 style="{{ $heading }}">Categories</h2>
            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px;">
                @forelse ($volunteer->categories as $category)
                    <span style="{{ $chip }}">{{ $category->name }}</span>
                @empty
                    <p style="margin: 0; font-size: 14px; color: #9ca3af;">No categories listed yet.</p>
                @endforelse
            </div>

            <hr style="{{ $divider }}">

            <h2 style="{{ $heading }}">Weekly Availability</h2>
            <div style="margin-top: 4px; font-size: 14px;">
                @forelse ($volunteer->availabilitySlots() as $slot)
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; padding: 12px 8px; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
                        <span style="font-weight: 500; color: #1f2937;">{{ $slot['day'] }}</span>
                        <span style="color: #4b5563;">{{ $slot['time'] }}</span>
                    </div>
                @empty
                    <p style="margin: 0; padding: 12px 0; color: #9ca3af;">No availability set.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection