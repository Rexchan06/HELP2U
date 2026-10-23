@extends('layouts.guest')

@section('title', 'Forgot password - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <h2 class="text-2xl font-bold tracking-tight">Forgot your password?</h2>
        <p class="mt-2 text-sm text-gray-500">Enter your university email and we will email you a 6-digit code.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold">University email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    placeholder="name@helplive.edu.my" autocomplete="email" class="{{ $inputClass }}">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Send code
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-medium text-gray-900 underline underline-offset-2">Back to log in</a>
        </p>
    </div>
@endsection
