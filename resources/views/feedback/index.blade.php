@extends('layouts.app')

@section('title', 'Feedback - HELP2U')

@section('content')
    @php $linkTo = fn (string $name) => Route::has($name) ? route($name) : '#'; @endphp

    <h1 class="text-2xl font-bold tracking-tight">History</h1>

    <nav class="mt-6 flex gap-6 border-b border-gray-200 text-sm font-medium" aria-label="History">
        <a href="{{ $linkTo('support-requests.index') }}" class="-mb-px border-b-2 border-transparent pb-3 text-gray-500 transition hover:text-gray-900">Requests</a>
        <a href="{{ route('support-sessions.index') }}" class="-mb-px border-b-2 border-transparent pb-3 text-gray-500 transition hover:text-gray-900">Sessions</a>
        <span class="-mb-px border-b-2 border-gray-900 pb-3 text-gray-900" aria-current="page">Feedback</span>
    </nav>

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-3">Session</th>
                    <th scope="col" class="px-4 py-3">With</th>
                    <th scope="col" class="px-4 py-3">Category</th>
                    <th scope="col" class="px-4 py-3">Rating</th>
                    <th scope="col" class="px-4 py-3">Comment</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($feedback as $item)
                    @php
                        $supportSession = $item->supportSession;
                        $supportRequest = $supportSession->volunteerMatch->supportRequest;
                        $isStudent = $supportRequest->user_id == auth()->id();
                        $otherUser = $isStudent
                            ? $supportSession->volunteerMatch->volunteerProfile->user
                            : $supportRequest->user;
                    @endphp
                    <tr class="transition hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-4">
                            {{ $supportSession->scheduled_start->format('d M Y') }}
                            <span class="block text-xs text-gray-500">{{ $isStudent ? 'You gave' : 'You received' }}</span>
                        </td>
                        <td class="px-4 py-4">
                            {{ $otherUser->name }}
                            <span class="block text-xs text-gray-500">{{ $isStudent ? 'Volunteer' : 'Student' }}</span>
                        </td>
                        <td class="px-4 py-4">{{ $supportRequest->category->name }}</td>
                        <td class="whitespace-nowrap px-4 py-4 text-amber-500" aria-label="Rated {{ $item->rating }} out of 5">
                            {{ str_repeat('★', $item->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $item->rating) }}</span>
                        </td>
                        <td class="max-w-xs px-4 py-4 text-gray-700">{{ $item->comment ?? '—' }}</td>
                        <td class="px-4 py-4 text-right">
                            <a href="{{ route('support-sessions.show', $supportSession) }}"
                               class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium transition hover:border-gray-900">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <p class="font-medium">No feedback yet</p>
                            <p class="mt-1 text-sm text-gray-500">Feedback appears here once a completed session has been rated.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection