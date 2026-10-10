<header class="navbar">
    <a href="{{ route('dashboard') }}" class="navbar-brand">
        {{-- Simple placeholder logo mark. Swap for your real logo image if you have one. --}}
        <svg viewBox="0 0 32 32" aria-hidden="true">
            <path d="M5 28 L12 4 H28 L21 28 Z" fill="#e4262c"/>
            <path d="M13 22 L16.5 10 M17 22 L20.5 10 M21 22 L24.5 10" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <span>HELP2U</span>
    </a>

    <nav class="navbar-menu" aria-label="Main">
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
           @if (request()->routeIs('dashboard')) aria-current="page" @endif>Home</a>

        {{-- Buttons only for now. Turn them into links when the pages exist. --}}
        <button type="button" class="nav-link">Find support</button>
        <button type="button" class="nav-link">Session</button>
        <button type="button" class="nav-link">History</button>

        <button type="button" class="nav-btn">New Request</button>

        <button type="button" class="nav-icon" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"/>
            </svg>
            {{-- Remove this line to hide the unread dot --}}
            <span class="nav-dot"></span>
        </button>

        <a href="{{ route('profile.edit') }}" class="nav-icon nav-user" aria-label="My profile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>
            </svg>
        </a>
    </nav>
</header>