@extends('layouts.app')

@section('title', 'My Support Requests - HELP2U')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold">My Support Requests</h1>
            <a
                href="{{ route('support-requests.create') }}"
                class="rounded-md bg-[#4a8ac4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3d7ab3]"
            >
                New Request
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
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
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($supportRequests as $supportRequest)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $supportRequest->category->name }}</td>
                                <td class="max-w-xs truncate px-6 py-4 text-gray-600">{{ $supportRequest->description }}</td>
                                <td class="px-6 py-4">{{ $supportRequest->support_mode->label() }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                        {{ $supportRequest->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $supportRequest->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $supportRequests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
