<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter your code - UniHELP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
            <div class="alert alert-success" role="status">We sent a 6-digit code to aisha.rahman@student.example.edu.</div>

            <h2>Enter your login code</h2>
            <p class="lead">Type the 6-digit code we emailed you. It expires in 10 minutes.</p>

            <form action="{{ route('dashboard') }}" method="get">
                <div class="field">
                    <label for="code">6-digit code</label>
                    <input id="code" type="text" required class="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autofocus autocomplete="one-time-code">
                </div>

                <button type="submit" class="btn btn-primary">Log in</button>
            </form>

            <p class="inline-form"><a href="two-factor.html" class="link-btn">Send a new code</a></p>
            <p class="switch"><a href="{{ route('login') }}">Back to log in</a></p>
        </div>
    </main>
</div>
</body>
</html>
