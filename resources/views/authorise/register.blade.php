<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your account - UniHELP</title>
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
            <h2>Create your account</h2>
            <p class="lead">Register with your student details. We will email you a code to verify your account.</p>

            <form action="{{ route('verify') }}" method="get">
                <div class="field">
                    <label for="student_id">Student ID</label>
                    <input id="student_id" type="text" required maxlength="20" autofocus autocomplete="off">
                </div>
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" type="text" required autocomplete="name">
                </div>
                <div class="field">
                    <label for="email">University email address</label>
                    <input id="email" type="email" required autocomplete="email">
                <p class="hint">You will use this email to log in.</p>
                </div>

                <button type="submit" class="btn btn-primary">Create account</button>
            </form>

            <p class="switch">Already registered? <a href="{{ route('login') }}">Log in</a></p>
        </div>
    </main>
</div>
</body>
</html>
