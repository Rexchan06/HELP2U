<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your account - HELP2U</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
            <div class="alert alert-success" role="status">Account created. We sent a verification link and a 6-digit code to aisha.rahman@student.example.edu.</div>

            <h2>Verify your account</h2>
            <p class="lead">Enter the 6-digit code we sent to <strong>aisha.rahman@student.example.edu</strong>, then choose a password.</p>

            @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('verify.store') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="field">
                    <label for="code">6-digit code</label>
                    <input id="code" name="code" type="text" class="otp" inputmode="numeric" maxlength="6" required>
                    @error('code')<p class="error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')<p class="error">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>

                <button type="submit" class="btn btn-primary">Activate account</button>
            </form>

            <form method="POST" action="{{ route('verify.resend') }}" class="inline-form">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="link-btn">Send a new code</button>
            </form>

            <p class="inline-form"><a href="{{ route('verify') }}" class="link-btn">Send a new code</a></p>
        </div>
    </main>
</div>
</body>
</html>
