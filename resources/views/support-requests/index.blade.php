@extends('layouts.app')

@section('title', 'My Support Requests - HELP2U')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold">My Support Requests</h1>
            <a
                href="{{ route('support-requests.create') }}"
                class="rounded-md bg-[#4a8ac4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3d7ab3]"
            >
                New Request
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($supportRequests->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-gray-500">
                    You have not made any support requests yet.
                </p>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Description</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Mode</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Volunteer</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Created</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($supportRequests as $supportRequest)
                            <tr>
                                @php
                                    $acceptedMatch = $supportRequest->volunteerMatches
                                        ->firstWhere('status', \App\Enums\MatchStatus::Accepted);
                                    $pendingMatch = $supportRequest->volunteerMatches
                                        ->firstWhere('status', \App\Enums\MatchStatus::Pending);
                                    $activeVolunteer = ($pendingMatch ?? $acceptedMatch)?->volunteerProfile?->user?->name;
                                @endphp
                                <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $supportRequest->category->name }}</td>
                                <td class="max-w-[12rem] truncate px-6 py-4 text-gray-600">{{ $supportRequest->description }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $supportRequest->support_mode->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                        {{ $supportRequest->status->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-600">
                                    {{ $activeVolunteer ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-500">{{ $supportRequest->created_at->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($acceptedMatch?->supportSession)
                                        <a href="{{ route('support-sessions.show', $acceptedMatch->supportSession) }}"
                                           class="font-medium text-gray-700 hover:underline">View Session</a>
                                    @elseif ($acceptedMatch)
                                        <a href="{{ route('support-sessions.create', $supportRequest) }}"
                                       class="font-medium text-blue-600 hover:underline">Schedule Session</a>
                                    @elseif ($pendingMatch)
                                        <span class="text-xs text-gray-500">Waiting Response</span>
                                    @elseif ($supportRequest->status === \App\Enums\RequestStatus::Open)
                                        <a href="{{ route('volunteers.index', ['support_request' => $supportRequest->id]) }}"
                                           class="font-medium text-[#17324D] hover:underline">Find Volunteer</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $supportRequests->links() }}
                </div>
            @endif
        </div>

        @if ($incomingMatches !== null)
            <div class="mt-24 mb-4">
                <h2 class="text-lg font-bold">Incoming Support Requests</h2>
                <p class="mt-1 text-sm text-gray-500">Requests students have sent to you as a volunteer. New requests expire after 48 hours.</p>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                @if ($incomingMatches->isEmpty())
                    <p class="px-6 py-10 text-center text-sm text-gray-500">
                        No students have sent you a request yet.
                    </p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Description</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Mode</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Student</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Preferred</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($incomingMatches as $match)
                                @php
                                    $expired = $match->status === \App\Enums\MatchStatus::Pending && $match->expires_at?->isPast();
                                    $statusText = $expired ? 'Expired' : \Illuminate\Support\Str::title(strtolower($match->status->value));
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $match->supportRequest->category->name }}</td>
                                    <td class="max-w-[12rem] truncate px-6 py-4 text-gray-600">{{ $match->supportRequest->description }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $match->supportRequest->support_mode->label() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span @class([
                                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-yellow-100 text-yellow-800' => $statusText === 'Pending',
                                            'bg-green-100 text-green-800' => $statusText === 'Accepted',
                                            'bg-red-100 text-red-800' => $statusText === 'Rejected' || $statusText === 'Expired',
                                        ])>
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $match->supportRequest->user->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-500">
                                        {{ $match->supportRequest->preferred_datetime?->format('d M Y, g:i A') ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <a href="{{ route('volunteer-matches.show', $match) }}"
                                           class="font-medium text-[#17324D] hover:underline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $incomingMatches->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
