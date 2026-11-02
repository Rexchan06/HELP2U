@extends('layouts.guest')

@section('title', 'Enter your login code - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-3 text-center text-xl tracking-[0.5em] focus:outline-none focus:ring-2 focus:ring-gray-900';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <h2 class="text-2xl font-bold tracking-tight">Enter your login code</h2>
        <p class="mt-2 text-sm text-gray-500">
            Type the 6-digit code we emailed you. It expires in 10 minutes.
        </p>

        <form method="POST" action="{{ route('login.2fa.verify') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="code" class="block text-sm font-semibold">6-digit code</label>
                <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                    placeholder="000000" autocomplete="one-time-code" required autofocus
                    class="{{ $inputClass }}">
                @error('code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Log in
            </button>
        </form>

        <form method="POST" action="{{ route('login.2fa.resend') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-900 underline underline-offset-2">
                Send a new code
            </button>
        </form>

        <p class="mt-3 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-gray-900 underline underline-offset-2">Back to log in</a>
        </p>
    </div>
@endsection