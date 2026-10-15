@extends('layouts.app')

@section('title', 'Dashboard - HELP2U')

@section('content')
    <h1 class="text-2xl font-bold tracking-tight">Welcome, {{ $user->name }}</h1>
    <p class="mt-2 text-sm text-gray-500">Your account is active. Set up your profile to start asking for or offering support.</p>

    <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-sm font-semibold">Your details</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Student ID</dt>
                <dd class="mt-1 font-medium">{{ $user->student_id }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">University email</dt>
                <dd class="mt-1 break-all font-medium">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Account status</dt>
                <dd class="mt-1 font-medium">
                    {{ $user->email_verified_at ? 'Verified on ' . $user->email_verified_at->format('j M Y') : 'Active' }}
                </dd>
            </div>
        </dl>
    </section>

    @if ($upcomingSessions->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-sm font-semibold">Upcoming sessions</h2>
            <ul class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
                @foreach ($upcomingSessions as $session)
                    <li class="flex flex-wrap items-center justify-between gap-4 p-5 text-sm">
                        <div>
                            <strong class="block font-semibold">
                                {{ $session->volunteerMatch->supportRequest->category->name }}
                            </strong>
                            <span class="text-gray-500">{{ $session->scheduled_start->format('D, j M Y, g:i A') }}</span>
                        </div>
                        <a href="{{ route('support-sessions.show', $session) }}"
                           class="rounded-lg border border-gray-300 px-3 py-1.5 font-medium transition hover:border-gray-900">
                            View
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-8">
        <h2 class="text-sm font-semibold">What you can do</h2>
        <ul class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
            <li class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div class="text-sm">
                    <strong class="block font-semibold">Volunteer Profile</strong>
                    <span class="text-gray-500">Become a volunteer and manage your volunteer profile here.</span>
                </div>
                <a href="{{ route('volunteer.profile') }}"
                   class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium transition hover:border-gray-900">
                    Manage Profile
                </a>
            </li>
        </ul>
    </section>
@endsection
