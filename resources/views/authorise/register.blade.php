@extends('layouts.guest')

@section('title', 'Create your account - HELP2U')

@section('content')
    @php
        $inputClass = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-bold tracking-tight">Create your account</h2>
        <p class="mt-2 text-sm text-gray-500">Register with your student details.</p>

        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="student_id" class="block text-sm font-semibold">Student ID</label>
                <input id="student_id" name="student_id" type="text" value="{{ old('student_id') }}"
                    maxlength="8" pattern="B[0-9]{7}" title="Enter valid Student ID"
                    placeholder="Example: B2612345" required autofocus autocomplete="off"
                    class="{{ $inputClass }}">
                @error('student_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-semibold">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="{{ $inputClass }}">
                @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold">University email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="{{ $inputClass }}" 
                placeholder="name@helplive.edu.my" pattern=".+@helplive\.edu\.my" title="Use your @helplive.edu.my email address">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold">Password</label>
                <input id="password" name="password" type="password" required class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-gray-500">At least 8 characters, with letters and numbers.</p>
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="{{ $inputClass }}">
            </div>

            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Already registered?
            <a href="{{ route('login') }}" class="font-medium text-gray-900 underline underline-offset-2">Log in</a>
        </p>
    </div>
@endsection
