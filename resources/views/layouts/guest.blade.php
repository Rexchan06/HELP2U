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
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="hidden flex-col justify-between gap-12 bg-gray-900 p-12 text-white lg:flex">
            <a href="{{ route('login') }}" class="text-xl font-bold tracking-tight">HELP2U</a>

            <div>
                <h1 class="text-3xl font-bold tracking-tight">Students helping students.</h1>
                <p class="mt-3 text-gray-300">Ask for support, or offer your own skills to other students at your university.</p>

                <ul class="mt-8 space-y-4 text-sm">
                    @foreach ([
                        'Academic support' => 'Mathematics, programming, study techniques',
                        'Technology support' => 'Software installation, basic computer help',
                        'New student support' => 'Campus orientation and student life',
                        'General student support' => 'Non-academic help and peer guidance',
                    ] as $topic => $description)
                        <li class="border-l-2 border-gray-600 pl-4">
                            <strong class="block font-semibold">{{ $topic }}</strong>
                            <span class="text-gray-400">{{ $description }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <span></span>
        </aside>

        <main class="flex items-center justify-center px-4 py-12 sm:px-6">
            <div class="w-full max-w-md">
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
            </div>
        </main>
    </div>
</body>
</html>
