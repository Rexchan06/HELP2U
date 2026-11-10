@extends('layouts.app')

@section('title', 'Incoming Requests - HELP2U')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <h1 class="text-xl font-bold">Incoming Support Requests</h1>
            <p class="mt-1 text-sm text-gray-500">Review requests students have sent to you. New requests expire after 48
                hours.</p>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($matches->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-gray-500">
                    No students have sent you a request yet.
                </p>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Student</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Description</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Mode</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Preferred</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($matches as $match)
                            @php
                                $expired = $match->status === \App\Enums\MatchStatus::Pending && $match->expires_at?->isPast();
                                $statusText = $expired ? 'Expired' : \Illuminate\Support\Str::title(strtolower($match->status->value));
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $match->supportRequest->user->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $match->supportRequest->category->name }}</td>
                                <td class="max-w-[12rem] truncate px-6 py-4 text-gray-600">{{ $match->supportRequest->description }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">{{ $match->supportRequest->support_mode->label() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-500">
                                    {{ $match->supportRequest->preferred_datetime?->format('d M Y, g:i A') ?? '—' }}
                                </td>
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
                                <td class="whitespace-nowrap px-6 py-4">
                                    <a href="{{ route('volunteer-matches.show', $match) }}"
                                        class="font-medium text-[#17324D] hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $matches->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection