@extends('layouts.guest')

@section('title', 'Create your password - HELP2U')

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

        <h2 class="text-2xl font-bold tracking-tight">Create your password</h2>
        <p class="mt-2 text-sm text-gray-500">
            Choose a password for <strong class="text-gray-900">{{ $email }}</strong>.
            Your account is created when you finish this step.
        </p>

        <form method="POST" action="{{ route('verification.password.store', $token) }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-sm font-semibold">Password</label>
                <input id="password" name="password" type="password" required autofocus
                    autocomplete="new-password" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-gray-500">At least 8 characters, with letters and numbers.</p>
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    autocomplete="new-password" class="{{ $inputClass }}">
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Create account
            </button>
        </form>
    </div>
@endsection
