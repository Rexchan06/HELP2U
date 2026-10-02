@extends('layouts.app')

@section('title', 'Create Support Request - HELP2U')

@section('content')
    <div class="mx-auto max-w-xl rounded-lg border border-gray-200 bg-white px-8 py-7 shadow-sm">
        <h1 class="text-xl font-bold">Create Support Request</h1>
        <p class="mt-1 text-sm text-gray-500">
            Fill out the details below to matching with peer student volunteers.
        </p>

        <form method="POST" action="{{ route('support-requests.store') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="category_id" class="mb-1.5 block text-sm font-medium">Category</label>
                <select
                    id="category_id"
                    name="category_id"
                    required
                    class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >
                    <option value="" disabled @selected(! old('category_id'))>Select a subject category...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-1.5 block text-sm font-medium">What do you need help with?</label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    required
                    placeholder="Describe your problem, assignment details, or questions..."
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="mb-1.5 block text-sm font-medium">Preferred Support Mode (Choose only 1)</span>
                <div class="flex items-center gap-6 text-sm">
                    @foreach (\App\Enums\SupportMode::cases() as $mode)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                name="support_mode"
                                value="{{ $mode->value }}"
                                @checked(old('support_mode', \App\Enums\SupportMode::FaceToFace->value) === $mode->value)
                                class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            {{ $mode->label() }}
                        </label>
                    @endforeach
                </div>
                @error('support_mode')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-[#4a8ac4] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3d7ab3] focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
            >
                Find Volunteers
            </button>
        </form>
    </div>
@endsection
