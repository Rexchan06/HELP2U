<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit profile - HELP2U</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header class="topbar">
    <a href="{{ route('dashboard') }}" class="brand">HELP2U</a>
    <div class="topbar-user">
        <span>{{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="link-btn">Log out</button>
        </form>
    </div>
</header>

<main class="container">
    <div class="profile-card">
        <h1 class="profile-title">Edit Profile</h1>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       maxlength="255" required autocomplete="name">
            </div>

            <div class="field">
                <label for="email">University Email (Read-Only)</label>
                <input id="email" type="email" value="{{ $user->email }}" readonly>
            </div>

            <div class="field">
                <label for="student_id">Student ID (Read-Only)</label>
                <input id="student_id" type="text" value="{{ $user->student_id }}" readonly>
            </div>

            <div class="field">
                <label for="bio">Bio (Describe how you can support peers or what you need support in)</label>
                <textarea id="bio" name="bio" rows="5" maxlength="500">{{ old('bio', $user->bio) }}</textarea>
                <p class="hint">Up to 500 characters.</p>
            </div>

            <div class="profile-actions">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>