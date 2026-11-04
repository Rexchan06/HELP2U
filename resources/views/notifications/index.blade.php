@extends('layouts.app')

@section('title', 'Notifications - HELP2U')

@section('content')
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold">Notifications</h1>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-[#17324D] hover:underline">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No notifications yet.</p>
            </div>
        @else
            <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white shadow-sm">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $decision = $data['decision'] ?? null;
                        $style = match (true) {
                            $notification->type === \App\Notifications\MatchRequested::class => ['bg-blue-100 text-blue-700', 'New request'],
                            $decision === 'accepted' => ['bg-green-100 text-green-700', 'Accepted'],
                            $decision === 'rejected' => ['bg-amber-100 text-amber-700', 'Rejected'],
                            $decision === 'expired' => ['bg-red-100 text-red-700', 'Expired'],
                            default => ['bg-gray-100 text-gray-700', 'Update'],
                        };
                        [$badge, $label] = $style;
                    @endphp

                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-start gap-4 px-5 py-4 text-left transition hover:bg-gray-50 {{ $notification->unread() ? 'bg-blue-50/40' : '' }}">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $badge }}">
                                @if ($decision === 'accepted')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @elseif ($decision === 'rejected' || $decision === 'expired')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                @endif
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm {{ $notification->unread() ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                                    {{ $data['message'] ?? '' }}
                                </span>
                                @if (! empty($data['category']))
                                    <span class="mt-1 block text-xs text-gray-400">{{ $label }} &bull; {{ $data['category'] }}</span>
                                @endif
                            </span>

                            <span class="flex shrink-0 items-center gap-2">
                                <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans(short: true) }}</span>
                                @if ($notification->unread())
                                    <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                @endif
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
