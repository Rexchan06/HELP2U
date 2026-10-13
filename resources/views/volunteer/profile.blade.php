@extends('layouts.app')

@section('title', 'Volunteer Profile - HELP2U')

@section('content')
    @php
        // Time options every 30 minutes from 06:00 AM to 10:00 PM
        $times = [];
        for ($m = 6 * 60; $m <= 22 * 60; $m += 30) {
            $times[sprintf('%02d:%02d', intdiv($m, 60), $m % 60)] = date('h:i A', mktime(intdiv($m, 60), $m % 60));
        }

        $chosenCategories = array_map('intval', old('categories', $selectedCategories ?? []));
        $chosenModes      = old('support_modes', $selectedModes ?? []);
        $rows             = old('availability') ? array_values(old('availability')) : ($availabilityRows ?? []);

        $inputClass  = 'mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
        $selectClass = 'rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900';
        $checkClass  = 'h-4 w-4 rounded border-gray-300 accent-gray-900';
    @endphp

    <form method="POST" action="{{ route('volunteer.profile.store') }}" class="mx-auto max-w-2xl">
        @csrf

        <h1 class="text-2xl font-bold tracking-tight">Volunteer Profile</h1>
        <p class="mt-2 text-sm text-gray-500">Configure your peer counseling settings, areas of capability, and schedule slots.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6 space-y-6">
            {{-- Support categories (loaded from the categories table) --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 id="categories-heading" class="text-sm font-semibold">Support Categories (Choose 1 or more)</h2>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2" role="group" aria-labelledby="categories-heading">
                    @forelse ($categories as $category)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" class="{{ $checkClass }}"
                                   @checked(in_array($category->id, $chosenCategories))>
                            {{ $category->name }}
                        </label>
                    @empty
                        <p class="text-xs text-gray-500">No support categories exist yet. Run the category seeder first.</p>
                    @endforelse
                </div>
            </section>

            {{-- Skills (typed as plain text, separated by commas) --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="text-sm font-semibold">Skills</h2>
                <label for="skills" class="sr-only">Skills</label>
                <input id="skills" name="skills" type="text" value="{{ old('skills', $skills ?? '') }}"
                       placeholder="Type your skills (e.g. Python, Interview Prep, Exam Prep)" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-gray-500">Separate your skills with commas.</p>
            </section>

            {{-- Support mode --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 id="mode-heading" class="text-sm font-semibold">Support Mode (Choose 1 or more)</h2>
                <div class="mt-4 space-y-3 text-sm" role="group" aria-labelledby="mode-heading">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="support_modes[]" value="{{ \App\Enums\SupportMode::F2F->value }}" class="{{ $checkClass }}"
                               @checked(in_array(\App\Enums\SupportMode::F2F->value, $chosenModes))>
                        Face-to-face
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="support_modes[]" id="mode_virtual" value="{{ \App\Enums\SupportMode::Virtual->value }}" class="{{ $checkClass }}"
                               @checked(in_array(\App\Enums\SupportMode::Virtual->value, $chosenModes))>
                        Virtual
                    </label>
                </div>

                <div class="mt-5" id="virtual-link-box" hidden>
                    <label for="meeting_link" class="block text-sm font-semibold">Virtual Meeting Link</label>
                    <input id="meeting_link" name="meeting_link" type="url"
                           value="{{ old('meeting_link', $meetingLink ?? '') }}"
                           placeholder="https://zoom.us/j/123456789" class="{{ $inputClass }}">
                    <p class="mt-2 text-xs text-gray-500">This link will be shared with students once a virtual peer support request is accepted.</p>
                </div>
            </section>

            {{-- Weekly availability --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="text-sm font-semibold">Weekly Availability</h2>
                <div id="availability-list" class="mt-4 space-y-3"></div>
                <button type="button" id="add-availability"
                        class="mt-4 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium transition hover:border-gray-900">
                    + Add Availability
                </button>
            </section>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('dashboard') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-900">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                Save Volunteer Profile
            </button>
        </div>
    </form>

    {{-- One availability row. The script copies this and fills in the row number. --}}
    <template id="availability-template">
        <div class="avail-row flex flex-wrap items-center gap-2 text-sm">
            <select name="availability[__INDEX__][day]" aria-label="Day" required class="{{ $selectClass }}">
                @foreach ($days as $number => $label)
                    <option value="{{ $number }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="availability[__INDEX__][start]" aria-label="Start time" required class="{{ $selectClass }}">
                @foreach ($times as $value => $label)
                    <option value="{{ $value }}" @selected($value === '09:00')>{{ $label }}</option>
                @endforeach
            </select>
            <span class="text-gray-500">to</span>
            <select name="availability[__INDEX__][end]" aria-label="End time" required class="{{ $selectClass }}">
                @foreach ($times as $value => $label)
                    <option value="{{ $value }}" @selected($value === '11:00')>{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" aria-label="Remove this time slot"
                    class="avail-remove rounded-full px-2 py-1 text-lg leading-none text-gray-400 transition hover:bg-gray-100 hover:text-red-600">
                &times;
            </button>
        </div>
    </template>

    <script>
        (function () {
            // ----- Availability rows -----
            const list = document.getElementById('availability-list');
            const template = document.getElementById('availability-template');
            const savedRows = @json($rows);
            let index = 0;

            function addRow(data) {
                list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index++));

                if (data) {
                    const row = list.lastElementChild;
                    row.querySelector('[name$="[day]"]').value = data.day;
                    row.querySelector('[name$="[start]"]').value = data.start;
                    row.querySelector('[name$="[end]"]').value = data.end;
                }
            }

            document.getElementById('add-availability').addEventListener('click', function () {
                addRow();
            });

            list.addEventListener('click', function (event) {
                const button = event.target.closest('.avail-remove');
                if (button) {
                    button.closest('.avail-row').remove();
                }
            });

            if (savedRows.length) {
                savedRows.forEach(addRow);
            } else {
                addRow(); // start with one empty row
            }

            // ----- Meeting link only shows when "Virtual" is ticked -----
            const virtual = document.getElementById('mode_virtual');
            const linkBox = document.getElementById('virtual-link-box');

            function toggleLink() {
                linkBox.hidden = !virtual.checked;
            }

            virtual.addEventListener('change', toggleLink);
            toggleLink();
        })();
    </script>
@endsection
