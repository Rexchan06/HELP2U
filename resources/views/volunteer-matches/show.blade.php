@extends('layouts.app')

@section('title', 'Request Details - HELP2U')

@section('content')
    @php
        $supportRequest = $match->supportRequest;
        $student = $supportRequest->user;
        $answerable = $match->status === \App\Enums\MatchStatus::Pending && $match->expires_at?->isFuture();
        $statusText = ($match->status === \App\Enums\MatchStatus::Pending && $match->expires_at?->isPast())
            ? 'Expired'
            : \Illuminate\Support\Str::title(strtolower($match->status->value));

        $initials = \Illuminate\Support\Str::of($student->name)->explode(' ')
            ->map(fn($p) => \Illuminate\Support\Str::substr($p, 0, 1))->take(2)->join('');

        $expiresIn = null;
        if ($answerable && $match->expires_at) {
            $totalMinutes = now()->diffInMinutes($match->expires_at);
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $expiresIn = ($hours > 0 ? $hours.' hour'.($hours !== 1 ? 's' : '') : '')
                .($hours > 0 && $minutes > 0 ? ' ' : '')
                .($minutes > 0 ? $minutes.' minute'.($minutes !== 1 ? 's' : '') : '');
        }

        $row = 'display: grid; grid-template-columns: 180px 1fr; gap: 8px; padding: 10px 0; font-size: 14px;';
    @endphp

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('support-requests.index') }}" class="text-sm text-gray-500 transition hover:text-gray-900">&larr; Back to requests</a>

        <div style="margin-top: 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px 40px;">

            {{-- Student header --}}
            <div style="display: flex; align-items: center; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid #e5e7eb;">
                <div style="width: 56px; height: 56px; min-width: 56px; border-radius: 9999px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: #9ca3af;">
                    {{ $initials }}
                </div>
                <div>
                    <h1 style="font-size: 18px; font-weight: 700; margin: 0; color: #111827;">{{ $student->name }}</h1>
                    <p style="margin: 2px 0 0; font-size: 13px; color: #9ca3af;">
                        HELP University Student
                        @if ($statusText !== 'Pending')
                            &bull; <span style="font-weight: 600; color: {{ $statusText === 'Accepted' ? '#15803d' : '#b91c1c' }};">{{ $statusText }}</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- Request fields --}}
            <div style="padding: 14px 0; border-bottom: 1px solid #e5e7eb;">
                <div style="{{ $row }}">
                    <span style="color: #6b7280; font-weight: 500;">Category</span>
                    <span style="color: #111827;">{{ $supportRequest->category->name }}</span>
                </div>
                <div style="{{ $row }}">
                    <span style="color: #6b7280; font-weight: 500;">Preferred Mode</span>
                    <span style="color: #111827;">{{ $supportRequest->support_mode->label() }}</span>
                </div>
                <div style="{{ $row }}">
                    <span style="color: #6b7280; font-weight: 500;">Preferred Date &amp; Time</span>
                    <span style="color: #111827;">{{ $supportRequest->preferred_datetime?->format('M d, Y, g:i A') ?? 'Flexible' }}</span>
                </div>
                <div style="{{ $row }}">
                    <span style="color: #6b7280; font-weight: 500;">Requested On</span>
                    <span style="color: #111827;">{{ $match->created_at->format('M d, Y, g:i A') }}</span>
                </div>
                @if ($expiresIn)
                    <div style="{{ $row }}">
                        <span style="color: #6b7280; font-weight: 500;">Expires In</span>
                        <span style="color: #111827;">{{ $expiresIn }}</span>
                    </div>
                @endif
            </div>

            {{-- Description --}}
            <div style="padding: 20px 0 24px; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 14px; font-weight: 600; margin: 0 0 8px; color: #111827;">Description</h2>
                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #374151; white-space: pre-line;">{{ $supportRequest->description }}</p>
            </div>

            {{-- Actions --}}
            @if ($answerable)
                <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px;">
                    <form method="POST" action="{{ route('volunteer-matches.respond', $match) }}">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <button type="submit"
                                style="border: 1px solid #d1d5db; background: #fff; color: #111827; border-radius: 8px; padding: 10px 22px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            Decline
                        </button>
                    </form>
                    <form method="POST" action="{{ route('volunteer-matches.respond', $match) }}">
                        @csrf
                        <input type="hidden" name="decision" value="accept">
                        <button type="submit"
                                style="background: #17324D; color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-size: 14px; font-weight: 600; cursor: pointer;">
                            Accept Request
                        </button>
                    </form>
                </div>
            @elseif ($match->responded_at)
                <p style="padding-top: 20px; margin: 0; font-size: 13px; color: #6b7280;">
                    You {{ $match->status === \App\Enums\MatchStatus::Accepted ? 'accepted' : 'rejected' }} this request {{ $match->responded_at->diffForHumans() }}.
                </p>
            @endif
        </div>
    </div>
@endsection
