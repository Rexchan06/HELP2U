@extends('layouts.guest')

@section('title', 'Log in - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-bold tracking-tight">Log in</h2>
        {{-- <p class="lead">Enter your email and password. We will then email you a 6-digit code.</p> --}}
        <p class="mt-2 text-sm text-gray-500">Enter your email and password.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold">University email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="{{ $inputClass }}">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold">Password</label>
                <input id="password" name="password" type="password" required class="{{ $inputClass }}">
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Log in
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            New to HELP2U?
            <a href="{{ route('register') }}" class="font-medium text-gray-900 underline underline-offset-2">Create an account</a>
        </p>
    </div>
@endsection
