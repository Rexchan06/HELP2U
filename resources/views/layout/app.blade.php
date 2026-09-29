<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HELP2U</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    <nav class="navbar">

        <div class="logo">
            HELP2U
        </div>

        <div class="nav-links">
            <a href="/dashboard">Dashboard</a>
            <a href="/requests/create">New Request</a>
            <a href="/history">History</a>
        </div>

        <div class="profile-menu">
            <a href="/profile/edit">Profile</a>
        </div>

    </nav>

    <main>
        @yield('content')
    </main>

</body>
</html>