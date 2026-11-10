{{-- Time slot picker shared by create and reschedule --}}
<div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
    @forelse ($slotsByDate as $day => $slots)
        <fieldset>
            <legend class="text-sm font-semibold">
                {{ \Illuminate\Support\Carbon::parse($day)->format('l, j F') }}
            </legend>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($slots as $slot)
                    @php $value = $slot->format('Y-m-d H:i:s'); @endphp
                    <label class="cursor-pointer">
                        <input type="radio" name="scheduled_start" value="{{ $value }}" class="peer sr-only"
                               @checked(old('scheduled_start') === $value) required>
                        <span class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-900 peer-checked:border-gray-900 peer-checked:bg-gray-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-gray-900 peer-focus-visible:ring-offset-2">
                            {{ $slot->format('g:i A') }}
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @empty
        <div class="py-8 text-center">
            <p class="font-medium">No open time slots</p>
            <p class="mt-1 text-sm text-gray-500">
                {{ $volunteerName }} has no available slots in the next {{ $daysAhead }} days.
            </p>
        </div>
    @endforelse

    @error('scheduled_start')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
