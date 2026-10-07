<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - HELP2U</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
    @if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <h1>Welcome, {{ $user->name }}</h1>
    <p class="lead">Your account is active. Set up your profile to start asking for or offering support.</p>

    <section class="panel">
        <h2>Your details</h2>
        <dl class="details">
            <div><dt>Student ID</dt><dd>{{ $user->student_id }}</dd></div>
            <div><dt>University email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Account status</dt><dd>Verified on 29 Sep 2026</dd></div>
        </dl>
    </section>

    <section>
        <h2>What you can do</h2>
        <ul class="actions">
            <li>
                <div><strong>Manage your profile</strong><span class="desc">Update your name and contact details.</span></div>
                <a href="{{ route('profile.edit') }}" class="link-btn">Edit profile</a>
            </li>
            <li>
                <div><strong>Become a volunteer</strong><span class="desc">Choose the categories you can support: Academic, Technology, New Student or General.</span></div>
                <span class="tag">Coming soon</span>
            </li>
            <li>
                <div><strong>Skills and interests</strong><span class="desc">Tell other students what you can help with.</span></div>
                <span class="tag">Coming soon</span>
            </li>
            <li>
                <div><strong>Availability</strong><span class="desc">Set when you are free and whether you prefer virtual or face-to-face support.</span></div>
                <span class="tag">Coming soon</span>
            </li>
        </ul>
    </section>
</main>
</body>
</html>