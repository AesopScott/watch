<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

// If already logged in, go straight to portal
if (is_active_subscriber()) {
    header('Location: /portal/');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = send_magic_link($email);
        if ($result === true) {
            $success = 'Check your email — we sent you a login link.';
        } elseif ($result === 'not_subscriber') {
            $error = 'That email isn\'t associated with an active subscription. <a href="' . SITE_URL . '/#subscribe">Start your free trial</a> to get access.';
        } else {
            $error = 'Something went wrong sending your login link. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="login-page">
    <div class="login-box">
        <a href="/" class="login-logo">Watch Me Build AI</a>
        <h1>Member Login</h1>
        <p class="login-sub">Enter your subscriber email and we'll send you a secure login link.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="/login.php">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="you@example.com" required autofocus>
            <button type="submit">Send Login Link</button>
        </form>
        <?php endif; ?>

        <p class="login-footer">Not a subscriber yet? <a href="/#subscribe">Start your free trial →</a></p>
    </div>
</body>
</html>
