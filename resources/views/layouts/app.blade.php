<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'HELP2U')</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
        <nav class="bg-[#1c3d5a]">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-6">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="HELP2U logo" class="h-8 w-8 rounded-sm">
                    <span class="text-lg font-extrabold italic tracking-wide text-white">HELP2U</span>
                </a>

                <div class="flex items-center gap-7 text-sm text-gray-200">
                    <a href="{{ url('/') }}" class="transition hover:text-white">Home</a>
                    <a href="#" class="transition hover:text-white">Find support</a>
                    <a href="#" class="transition hover:text-white">Session</a>
                    <a href="{{ route('support-requests.index') }}" class="transition hover:text-white">History</a>

                    <a
                        href="{{ route('support-requests.create') }}"
                        class="rounded-md border border-gray-300 px-3 py-1.5 font-medium text-white transition hover:bg-white/10"
                    >
                        New Request
                    </a>

                    <a href="#" class="relative transition hover:text-white" aria-label="Notifications">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute -top-0.5 -right-0.5 block h-2 w-2 rounded-full bg-red-500"></span>
                    </a>

                    <a href="#" class="block rounded bg-gray-300 p-1.5" aria-label="Profile">
                        <svg class="h-5 w-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5Z" />
                        </svg>
                    </a>
                </div>
            </div>
        </nav>

        <main class="mx-auto max-w-5xl px-6 py-10">
            @if (session('success'))
                <div class="mx-auto mb-6 max-w-xl rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </body>
</html>
