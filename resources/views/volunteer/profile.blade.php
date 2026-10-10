@php
    // Time options every 30 minutes from 06:00 AM to 10:00 PM
    $times = [];
    for ($m = 6 * 60; $m <= 22 * 60; $m += 30) {
        $times[sprintf('%02d:%02d', intdiv($m, 60), $m % 60)] = date('h:i A', mktime(intdiv($m, 60), $m % 60));
    }

    $chosenCategories = array_map('intval', old('categories', $selectedCategories ?? []));
    $chosenModes      = old('support_modes', $selectedModes ?? []);
    $rows             = old('availability') ? array_values(old('availability')) : ($availabilityRows ?? []);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Volunteer Profile - HELP2U</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
@include('partials.navbar')

<main class="container">
    <form method="POST" action="{{ route('volunteer.profile.store') }}" class="volunteer-form">
        @csrf

        <h1>Volunteer Profile</h1>
        <p class="page-lead">Configure your peer counseling settings, areas of capability, and schedule slots.</p>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Support categories (loaded from the categories table) --}}
        <section class="form-section">
            <h2 id="categories-heading">Support Categories (Choose 1 or more)</h2>
            <div class="checks" role="group" aria-labelledby="categories-heading">
                @forelse ($categories as $category)
                    <label class="check">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                               @checked(in_array($category->id, $chosenCategories))>
                        {{ $category->name }}
                    </label>
                @empty
                    <p class="hint">No support categories exist yet. Run the category seeder first.</p>
                @endforelse
            </div>
        </section>

        {{-- Skills (typed as plain text, separated by commas) --}}
        <section class="form-section">
            <h2>Skills</h2>
            <div class="field">
                <label for="skills" class="visually-hidden">Skills</label>
                <input id="skills" name="skills" type="text" value="{{ old('skills', $skills ?? '') }}"
                       placeholder="Type your skills (e.g. Python, Interview Prep, Exam Prep)">
                <p class="hint">Separate your skills with commas.</p>
            </div>
        </section>

        {{-- Support mode --}}
        <section class="form-section">
            <h2 id="mode-heading">Support Mode (Choose 1 or more)</h2>
            <div class="checks checks-stacked" role="group" aria-labelledby="mode-heading">
                <label class="check">
                    <input type="checkbox" name="support_modes[]" value="{{ \App\Enums\SupportMode::F2F->value }}"
                           @checked(in_array(\App\Enums\SupportMode::F2F->value, $chosenModes))>
                    Face-to-face
                </label>
                <label class="check">
                    <input type="checkbox" name="support_modes[]" id="mode_virtual" value="{{ \App\Enums\SupportMode::Virtual->value }}"
                           @checked(in_array(\App\Enums\SupportMode::Virtual->value, $chosenModes))>
                    Virtual
                </label>
            </div>

            <div class="field virtual-link" id="virtual-link-box" hidden>
                <label for="meeting_link">Virtual Meeting Link</label>
                <input id="meeting_link" name="meeting_link" type="url"
                       value="{{ old('meeting_link', $meetingLink ?? '') }}"
                       placeholder="https://zoom.us/j/123456789">
                <p class="hint">This link will be shared with students once a virtual peer support request is accepted.</p>
            </div>
        </section>

        {{-- Weekly availability --}}
        <section class="form-section">
            <h2>Weekly Availability</h2>
            <div id="availability-list"></div>
            <button type="button" id="add-availability" class="btn btn-outline btn-auto">+ Add Availability</button>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Volunteer Profile</button>
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</main>

{{-- One availability row. The script copies this and fills in the row number. --}}
<template id="availability-template">
    <div class="avail-row">
        <select name="availability[__INDEX__][day]" aria-label="Day" required>
            @foreach ($days as $number => $label)
                <option value="{{ $number }}">{{ $label }}</option>
            @endforeach
        </select>
        <select name="availability[__INDEX__][start]" aria-label="Start time" required>
            @foreach ($times as $value => $label)
                <option value="{{ $value }}" @selected($value === '09:00')>{{ $label }}</option>
            @endforeach
        </select>
        <span>to</span>
        <select name="availability[__INDEX__][end]" aria-label="End time" required>
            @foreach ($times as $value => $label)
                <option value="{{ $value }}" @selected($value === '11:00')>{{ $label }}</option>
            @endforeach
        </select>
        <button type="button" class="avail-remove" aria-label="Remove this time slot">&times;</button>
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
</body>
</html>