@extends('layouts.app')

@section('title', 'History - HELP2U')

@section('content')
    @php
        $supportSessions->loadMissing([
            'volunteerMatch.supportRequest.user',
            'volunteerMatch.supportRequest.category',
            'volunteerMatch.volunteerProfile.user',
            'feedback',
        ]);

        $statusStyles = [
            'SCHEDULED' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'COMPLETED' => 'bg-green-50 text-green-700 ring-green-200',
            'CANCELLED' => 'bg-gray-100 text-gray-600 ring-gray-200',
        ];

        $linkTo = fn (string $name) => Route::has($name) ? route($name) : '#';
    @endphp

    <h1 class="text-2xl font-bold tracking-tight">History</h1>

    {{-- Tabs: Requests (UC2) and Feedback (later iteration) link out once their routes exist --}}
    <nav class="mt-6 flex gap-6 border-b border-gray-200 text-sm font-medium" aria-label="History">
        <a href="{{ $linkTo('support-requests.index') }}" class="-mb-px border-b-2 border-transparent pb-3 text-gray-500 transition hover:text-gray-900">Requests</a>
        <span class="-mb-px border-b-2 border-gray-900 pb-3 text-gray-900" aria-current="page">Sessions</span>
        <a href="{{ $linkTo('feedback.index') }}" class="-mb-px border-b-2 border-transparent pb-3 text-gray-500 transition hover:text-gray-900">Feedback</a>
    </nav>

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th scope="col" class="px-4 py-3">Date</th>
                    <th scope="col" class="px-4 py-3">With</th>
                    <th scope="col" class="px-4 py-3">Category</th>
                    <th scope="col" class="px-4 py-3">Mode</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($supportSessions as $supportSession)
                    @php
                        $supportRequest = $supportSession->volunteerMatch->supportRequest;
                        $isStudent = $supportRequest->user_id == auth()->id();
                        $otherUser = $isStudent
                            ? $supportSession->volunteerMatch->volunteerProfile->user
                            : $supportRequest->user;
                        $status = $supportSession->status->value;
                    @endphp
                    <tr class="transition hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-4">
                            {{ $supportSession->scheduled_start->format('d M Y') }}
                            <span class="block text-xs text-gray-500">{{ $supportSession->scheduled_start->format('g:i A') }}</span>
                        </td>
                        <td class="px-4 py-4">
                            {{ $otherUser->name }}
                            <span class="block text-xs text-gray-500">{{ $isStudent ? 'Volunteer' : 'Student' }}</span>
                        </td>
                        <td class="px-4 py-4">{{ $supportRequest->category->name }}</td>
                        <td class="whitespace-nowrap px-4 py-4">
                            {{ $supportRequest->support_mode === \App\Enums\SupportMode::F2F ? 'Face-to-face' : 'Virtual' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyles[$status] ?? $statusStyles['CANCELLED'] }}">
                                {{ ucfirst(strtolower($status)) }}
                            </span>
                            @if ($supportSession->feedback)
                                <span class="ml-2 text-amber-500" aria-label="Rated {{ $supportSession->feedback->rating }} out of 5">
                                    {{ str_repeat('★', $supportSession->feedback->rating) }}
                                </span>
                            @endif
                        </td>
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
                            <p class="font-medium">No sessions yet</p>
                            <p class="mt-1 text-sm text-gray-500">Once a volunteer accepts your request, you can schedule a session with them.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection