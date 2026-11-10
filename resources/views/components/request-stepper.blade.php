@props(['current' => 1])

@php
    $steps = [
        1 => ['label' => 'Create Support Request'],
        2 => ['label' => 'Find Volunteers'],
        3 => ['label' => 'Volunteer Details'],
    ];
@endphp

<ol style="display: flex; width: 100%; margin: 0 0 28px; padding: 0; list-style: none;">
    @foreach ($steps as $n => $step)
        @php
            $done = $n < $current;
            $active = $n === $current;
        @endphp
        <li style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;">
            @if (! $loop->first)
                <span style="position: absolute; top: 16px; right: 50%; width: 100%; height: 2px; z-index: 0; background: {{ $done || $active ? '#17324D' : '#e5e7eb' }};"></span>
            @endif

            <span style="
                position: relative; z-index: 1;
                width: 32px; height: 32px; border-radius: 9999px;
                display: flex; align-items: center; justify-content: center;
                font-size: 13px; font-weight: 600;
                @if ($done)
                    background: #17324D; color: #fff; border: 2px solid #17324D;
                @elseif ($active)
                    background: #fff; color: #17324D; border: 2px solid #17324D;
                    box-shadow: 0 0 0 4px rgba(23, 50, 77, 0.15);
                @else
                    background: #fff; color: #9ca3af; border: 1px solid #d1d5db;
                @endif
            ">
                @if ($done)
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4l2.3 2.3 6.3-6.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
                    </svg>
                @else
                    {{ $n }}
                @endif
            </span>

            <span style="margin-top: 8px; font-size: 12px; font-weight: 600; color: {{ $done || $active ? '#17324D' : '#9ca3af' }};">
                {{ $step['label'] }}
            </span>
            <span style="margin-top: 2px; font-size: 11px; color: {{ $done ? '#17324D' : ($active ? '#17324D' : '#d1d5db') }};">
                {{ $done ? 'Completed' : ($active ? 'In Progress' : 'Pending') }}
            </span>
        </li>
    @endforeach
</ol>
