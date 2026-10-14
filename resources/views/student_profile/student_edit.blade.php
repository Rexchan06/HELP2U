@extends('layouts.app')

@section('title', 'Edit profile - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
        $readonlyClass = 'mt-2 block w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-500 focus:outline-none';
    @endphp

    <div class="mx-auto max-w-xl rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-bold tracking-tight">Edit Profile</h1>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold">Full Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       maxlength="255" required autocomplete="name" class="{{ $inputClass }}">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold">University Email (Read-Only)</label>
                <input id="email" type="email" value="{{ $user->email }}" readonly class="{{ $readonlyClass }}">
            </div>

            <div>
                <label for="student_id" class="block text-sm font-semibold">Student ID (Read-Only)</label>
                <input id="student_id" type="text" value="{{ $user->student_id }}" readonly class="{{ $readonlyClass }}">
            </div>

            <div>
                <label for="bio" class="block text-sm font-semibold">Bio (Describe how you can support peers or what you need support in)</label>
                <textarea id="bio" name="bio" rows="5" maxlength="200" class="{{ $inputClass }}">{{ old('bio', $user->bio) }}</textarea>
                <p class="mt-2 text-xs text-gray-500">Up to 200 characters.</p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-900">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection
