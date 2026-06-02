<?php
// Magic link handler — validates token, sets session, redirects to portal
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));

if (!$token || !$email) {
    header('Location: /login.php');
    exit;
}

$result = verify_magic_link($email, $token);

if ($result === true) {
    session_start();
    $_SESSION['subscriber_email'] = $email;
    $_SESSION['logged_in_at']     = time();
    header('Location: /portal/');
    exit;
}

if ($result === 'expired') {
    $msg = 'Your login link has expired. Please request a new one.';
} elseif ($result === 'not_subscriber') {
    $msg = 'Your subscription is no longer active.';
} else {
    $msg = 'Invalid login link. Please request a new one.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Failed — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="login-page">
    <div class="login-box">
        <a href="/" class="login-logo">Watch Me Build AI</a>
        <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
        <a href="/login.php" class="btn">Request a new login link</a>
    </div>
</body>
</html>
