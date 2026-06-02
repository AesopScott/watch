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
    <title>Log In — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
</head>
<body class="login-page">
    <div class="login-box">
        <a href="/" class="login-logo">Watch Me Build AI</a>
        <h1>Member Login</h1>
        <p class="login-sub">Sign in with your subscriber account.</p>

        <div id="error-msg" class="alert alert-error" style="display:none"></div>
        <div id="success-msg" class="alert alert-success" style="display:none"></div>

        <form id="login-form" autocomplete="on">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
            <label for="password" style="margin-top:12px">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="submit" id="submit-btn" style="margin-top:16px">Sign In</button>
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

        <p class="login-footer">Not a subscriber yet? <a href="/pricing.php">View plans →</a></p>
        <p class="login-footer" style="margin-top:8px"><a href="#" id="forgot-link">Forgot password?</a></p>
    </div>

    <script type="module">
        import { initializeApp }                                    from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import { getAuth, signInWithEmailAndPassword,
                 GoogleAuthProvider, signInWithPopup,
                 sendPasswordResetEmail }                           from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

        const app  = initializeApp({
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
            btn.disabled  = loading;
            gBtn.disabled = loading;
            btn.textContent = loading ? 'Signing in…' : 'Sign In';
        }

        async function createSession(user) {
            const idToken = await user.getIdToken();
            const resp    = await fetch('/api/firebase-session.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ idToken }),
            });
            const data = await resp.json();
            if (data.ok) {
                showSuccess('Signed in — redirecting…');
                window.location.href = data.redirect;
            } else if (data.error === 'not_subscriber') {
                showError('That account doesn\'t have an active subscription. <a href="/pricing.php">View plans →</a>');
            } else {
                showError(data.error || 'Something went wrong. Please try again.');
            }
        }

        document.getElementById('login-form').addEventListener('submit', async e => {
            e.preventDefault();
            setLoading(true);
            document.getElementById('error-msg').style.display = 'none';
            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            try {
                const cred = await signInWithEmailAndPassword(auth, email, password);
                await createSession(cred.user);
            } catch (err) {
                setLoading(false);
                const msg = err.code === 'auth/invalid-credential' || err.code === 'auth/wrong-password' || err.code === 'auth/user-not-found'
                    ? 'Incorrect email or password.'
                    : err.code === 'auth/too-many-requests'
                    ? 'Too many attempts. Please wait a moment and try again.'
                    : 'Sign-in failed. Please try again.';
                showError(msg);
            }
        });

        document.getElementById('google-btn').addEventListener('click', async () => {
            setLoading(true);
            document.getElementById('error-msg').style.display = 'none';
            try {
                const provider = new GoogleAuthProvider();
                const cred     = await signInWithPopup(auth, provider);
                await createSession(cred.user);
            } catch (err) {
                setLoading(false);
                if (err.code !== 'auth/popup-closed-by-user' && err.code !== 'auth/cancelled-popup-request') {
                    showError('Google sign-in failed. Please try again.');
                }
            }
        });

        document.getElementById('forgot-link').addEventListener('click', async e => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            if (!email) { showError('Enter your email address above, then click Forgot password.'); return; }
            try {
                await sendPasswordResetEmail(auth, email);
                showSuccess('Password reset email sent — check your inbox.');
            } catch (err) {
                showError('Could not send reset email. Make sure the address is correct.');
            }
        });
    </script>
</body>
</html>
