<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - UniHELP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ route('login') }}" class="brand">UniHELP</a>
        <div>
            <h1>Students helping students.</h1>
            <p>Ask for support, or offer your own skills to other students at your university.</p>
            <ul class="topics">
                <li><strong>Academic support</strong><span>Mathematics, programming, study techniques</span></li>
                <li><strong>Technology support</strong><span>Software installation, basic computer help</span></li>
                <li><strong>New student support</strong><span>Campus orientation and student life</span></li>
                <li><strong>General student support</strong><span>Non-academic help and peer guidance</span></li>
            </ul>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <h2>Log in</h2>
            <p class="lead">Enter your email and password. We will then email you a 6-digit code.</p>

            <form action="{{ route('two-factor') }}" method="get">
                <div class="field">
                    <label for="email">University email address</label>
                    <input id="email" type="email" required autofocus autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary">Log in</button>
            </form>

            <p class="switch">New to UniHELP? <a href="{{ route('register') }}">Create an account</a></p>
        </div>
    </main>
</div>
</body>
</html>

