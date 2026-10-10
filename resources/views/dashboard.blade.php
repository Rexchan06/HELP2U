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
@include('partials.navbar')

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
                <div><strong>Volunteer Profile</strong><span class="desc">Become a volunteer and manage your volunteer profile here.</span></div>
                <a href="{{ route('volunteer.profile') }}" class="link-btn">Manage Profile</a>
            </li>

        </ul>
    </section>
</main>
</body>
</html>