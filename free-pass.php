<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

if (is_active_subscriber()) {
    header('Location: /portal/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>7-Day Pass — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head>
<body class="login-page">
    <div class="login-box">
        <a href="/" class="login-logo">Watch Me Build AI</a>
        <h1>Start your 7-day pass</h1>
        <p class="login-sub">No credit card required. Create an account and get instant Pro access for 7 days.</p>

        <div id="error-msg" class="alert alert-error" style="display:none"></div>
        <div id="success-msg" class="alert alert-success" style="display:none"></div>

        <form id="pass-form" autocomplete="on">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
            <label for="password" style="margin-top:12px">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
            <button type="submit" id="submit-btn" style="margin-top:16px">Start 7-Day Pass</button>
        </form>

        <div class="login-divider">or</div>

        <button id="google-btn" class="btn btn-google btn-full">
            <svg width="18" height="18" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M44.5 20H24v8.5h11.8C34.7 33.9 29.9 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 5.1 29.6 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21c10.5 0 20-7.6 20-21 0-1.3-.2-2.7-.5-4z" fill="#FFC107"/>
                <path d="M6.3 14.7l7 5.1C15 16.1 19.1 13 24 13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 5.1 29.6 3 24 3 16.3 3 9.7 7.9 6.3 14.7z" fill="#FF3D00"/>
                <path d="M24 45c5.8 0 10.8-1.9 14.7-5.2l-6.8-5.7C29.8 35.9 27 37 24 37c-5.8 0-10.7-3.9-11.8-9.3l-7 5.4C8.5 40.5 15.7 45 24 45z" fill="#4CAF50"/>
                <path d="M44.5 20H24v8.5h11.8c-.6 2.9-2.3 5.4-4.7 7l6.8 5.7C42.1 37.4 45 31.2 45 24c0-1.3-.2-2.7-.5-4z" fill="#1976D2"/>
            </svg>
            Continue with Google
        </button>

        <p class="login-footer">Already have access? <a href="/login.php">Log in →</a></p>
    </div>

    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import { getAuth, createUserWithEmailAndPassword,
                 signInWithEmailAndPassword, GoogleAuthProvider,
                 signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

        const app = initializeApp({
            apiKey:            '<?= FIREBASE_API_KEY ?>',
            authDomain:        '<?= FIREBASE_AUTH_DOMAIN ?>',
            projectId:         '<?= FIREBASE_PROJECT_ID ?>',
            appId:             '<?= FIREBASE_APP_ID ?>',
            messagingSenderId: '<?= FIREBASE_MESSAGING_SENDER_ID ?>',
        });
        const auth = getAuth(app);

        function showError(msg) {
            const el = document.getElementById('error-msg');
            el.innerHTML = msg;
            el.style.display = 'block';
            document.getElementById('success-msg').style.display = 'none';
        }

        function showSuccess(msg) {
            const el = document.getElementById('success-msg');
            el.textContent = msg;
            el.style.display = 'block';
            document.getElementById('error-msg').style.display = 'none';
        }

        function setLoading(loading) {
            const btn = document.getElementById('submit-btn');
            const gBtn = document.getElementById('google-btn');
            btn.disabled = loading;
            gBtn.disabled = loading;
            btn.textContent = loading ? 'Starting pass…' : 'Start 7-Day Pass';
        }

        async function grantPass(user) {
            const idToken = await user.getIdToken();
            const resp = await fetch('/api/free-pass.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idToken }),
            });
            const data = await resp.json();
            if (data.ok) {
                showSuccess('Pass active — redirecting…');
                window.location.href = data.redirect;
                return;
            }
            if (data.error === 'already_claimed') {
                showError('This email has already used its 7-day pass. <a href="/pricing.php">Choose a plan →</a>');
            } else {
                showError('Could not start your pass. Please try again.');
            }
            setLoading(false);
        }

        document.getElementById('pass-form').addEventListener('submit', async e => {
            e.preventDefault();
            setLoading(true);
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            try {
                const cred = await createUserWithEmailAndPassword(auth, email, password);
                await grantPass(cred.user);
            } catch (err) {
                try {
                    if (err.code === 'auth/email-already-in-use') {
                        const cred = await signInWithEmailAndPassword(auth, email, password);
                        await grantPass(cred.user);
                        return;
                    }
                } catch (signInErr) {
                    showError('That email already has an account. Use the existing password or continue with Google.');
                    setLoading(false);
                    return;
                }
                const msg = err.code === 'auth/weak-password'
                    ? 'Password must be at least 6 characters.'
                    : 'Could not create your account. Please try again.';
                showError(msg);
                setLoading(false);
            }
        });

        document.getElementById('google-btn').addEventListener('click', async () => {
            setLoading(true);
            try {
                const provider = new GoogleAuthProvider();
                const cred = await signInWithPopup(auth, provider);
                await grantPass(cred.user);
            } catch (err) {
                setLoading(false);
                if (err.code !== 'auth/popup-closed-by-user' && err.code !== 'auth/cancelled-popup-request') {
                    showError('Google sign-in failed. Please try again.');
                }
            }
        });
    </script>
</body>
</html>
