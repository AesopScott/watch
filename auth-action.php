<?php
// Custom Firebase action handler — handles password reset links sent via Brevo.
// Firebase generates the oobCode; we host the form on watchmebuildai.com.
require_once __DIR__ . '/config/config.php';

$mode     = $_GET['mode']    ?? '';
$oob_code = $_GET['oobCode'] ?? '';

if ($mode !== 'resetPassword' || !$oob_code) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
</head>
<body class="login-page">
    <div class="login-box">
        <a href="/" class="login-logo">Watch Me Build AI</a>
        <h1>Set New Password</h1>
        <p class="login-sub">Choose a new password for your account.</p>

        <div id="error-msg" class="alert alert-error" style="display:none"></div>
        <div id="success-msg" class="alert alert-success" style="display:none"></div>

        <form id="reset-form" autocomplete="on">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required minlength="8" autofocus>
            <button type="submit" id="submit-btn" style="margin-top:16px">Set Password</button>
        </form>
    </div>

    <script type="module">
        import { initializeApp }        from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
        import { getAuth,
                 confirmPasswordReset } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js';

        const app = initializeApp({
            apiKey:            '<?= FIREBASE_API_KEY ?>',
            authDomain:        '<?= FIREBASE_AUTH_DOMAIN ?>',
            projectId:         '<?= FIREBASE_PROJECT_ID ?>',
            appId:             '<?= FIREBASE_APP_ID ?>',
            messagingSenderId: '<?= FIREBASE_MESSAGING_SENDER_ID ?>',
        });
        const auth    = getAuth(app);
        const oobCode = '<?= htmlspecialchars($oob_code, ENT_QUOTES) ?>';

        document.getElementById('reset-form').addEventListener('submit', async e => {
            e.preventDefault();
            const btn      = document.getElementById('submit-btn');
            const password = document.getElementById('password').value;
            btn.disabled    = true;
            btn.textContent = 'Saving…';
            document.getElementById('error-msg').style.display = 'none';

            try {
                await confirmPasswordReset(auth, oobCode, password);
                document.getElementById('reset-form').style.display = 'none';
                const el = document.getElementById('success-msg');
                el.textContent = 'Password updated — redirecting to sign in…';
                el.style.display = 'block';
                setTimeout(() => window.location.href = '/login.php', 2000);
            } catch (err) {
                btn.disabled    = false;
                btn.textContent = 'Set Password';
                const el = document.getElementById('error-msg');
                el.textContent =
                    err.code === 'auth/expired-action-code'  ? 'This reset link has expired. Please request a new one.' :
                    err.code === 'auth/invalid-action-code'  ? 'This reset link is invalid or has already been used.' :
                    err.code === 'auth/weak-password'        ? 'Password must be at least 6 characters.' :
                    'Something went wrong. Please try again.';
                el.style.display = 'block';
            }
        });
    </script>
</body>
</html>
