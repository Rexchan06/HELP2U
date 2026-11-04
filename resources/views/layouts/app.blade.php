<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HELP2U')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    @php
        // Links to pages owned by other use cases fall back to "#" until those routes exist.
        $linkTo = fn (string $name) => Route::has($name) ? route($name) : '#';

        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
            ['label' => 'Find Support', 'route' => 'volunteers.index', 'active' => 'volunteers.*'],
            ['label' => 'Requests', 'route' => 'support-requests.index', 'active' => 'support-requests.*'],
            ['label' => 'Sessions', 'route' => 'support-sessions.index', 'active' => 'support-sessions.*'],
        ];



        $initials = collect(explode(' ', auth()->user()->name ?? ''))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <header class="sticky top-0 z-10 border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
            <a href="{{ $linkTo('dashboard') }}" class="flex items-center gap-2">
                <span class="text-xl font-bold tracking-tight">HELP2U</span>
                <span class="rounded border border-gray-300 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Portal</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium md:flex" aria-label="Main">
                @foreach ($navItems as $item)
                    <a href="{{ $linkTo($item['route']) }}"
                       @class([
                           'transition hover:text-gray-900',
                           'text-gray-900' => request()->routeIs($item['active']),
                           'text-gray-500' => ! request()->routeIs($item['active']),
                       ])
                       @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-4">
                <a href="{{ $linkTo('support-requests.create') }}"
                   class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700">
                    New Request
                </a>

                @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                <a href="{{ route('notifications.index') }}"
                   class="relative rounded-full p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                   aria-label="Notifications">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    @if ($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </a>

                @if (auth()->user()?->volunteerProfile)
                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">Volunteer</span>
                @endif

                <a href="{{ $linkTo('profile.edit') }}"
                   class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-700"
                   title="{{ auth()->user()->name ?? 'Profile' }}">
                    {{ $initials ?: '?' }}
                </a>

                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-500 transition hover:text-gray-900">
                            Log out
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    @if (session('show_unread_popup') && $unreadCount > 0)
        @php $latest = auth()->user()->notifications()->latest()->limit(4)->get(); @endphp
        <a href="{{ route('notifications.index') }}" id="notification-popup"
           class="fixed right-4 top-20 z-50 block w-80 rounded-xl border border-gray-200 bg-white shadow-xl transition duration-300">
            <div class="px-5 pb-3 pt-4 text-base font-bold">Notifications</div>

            @foreach ($latest as $notification)
                @php
                    $decision = $notification->data['decision'] ?? null;
                    $badge = match (true) {
                        $decision === 'accepted' => 'bg-green-100 text-green-700',
                        $decision === 'rejected' => 'bg-amber-100 text-amber-700',
                        $decision === 'expired'  => 'bg-red-100 text-red-700',
                        default                  => 'bg-blue-100 text-blue-700',
                    };
                @endphp
                <div class="flex items-start gap-3 border-t border-gray-100 px-5 py-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $badge }}">
                        @if ($decision === 'accepted')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @elseif ($decision === 'rejected' || $decision === 'expired')
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm {{ $notification->unread() ? 'font-semibold text-gray-900' : 'text-gray-500' }}">
                            {{ $notification->data['message'] ?? '' }}
                        </span>
                        <span class="mt-0.5 block text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </div>
            @endforeach
        </a>

        <script>
            setTimeout(function () {
                var popup = document.getElementById('notification-popup');
                if (! popup) return;
                popup.classList.add('opacity-0');
                setTimeout(function () { popup.remove(); }, 300);
            }, 10000);
        </script>
    @endif

    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('failed'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('failed') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>