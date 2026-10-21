<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter your login code - HELP2U</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <a href="{{ route('login') }}" class="brand">HELP2U</a>
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
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <h2>Enter your login code</h2>
            <p class="lead">Type the 6-digit code we emailed you. It expires in 10 minutes.</p>

            <form method="POST" action="{{ route('login.2fa.verify') }}">
                @csrf

                <div class="field">
                    <label for="code">6-digit code</label>
                    <input id="code" name="code" type="text" class="otp" inputmode="numeric"
                           pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
                    @error('code')<p class="error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Log in</button>
            </form>

            <form method="POST" action="{{ route('login.2fa.resend') }}" class="inline-form">
                @csrf
                <button type="submit" class="link-btn">Send a new code</button>
            </form>

            <p class="switch"><a href="{{ route('login') }}">Back to log in</a></p>
        </div>
    </main>
</div>
</body>
</html>