@extends('layouts.guest')

@section('title', 'Choose a new password - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-bold tracking-tight">Choose a new password</h2>
        <p class="mt-2 text-sm text-gray-500">Your code was accepted. Enter a new password for your account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-sm font-semibold">New password</label>
                <input id="password" name="password" type="password" required autofocus
                    autocomplete="new-password" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-gray-500">At least 8 characters, with letters and numbers.</p>
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    autocomplete="new-password" class="{{ $inputClass }}">
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Change password
            </button>
        </form>
    </div>
@endsection
