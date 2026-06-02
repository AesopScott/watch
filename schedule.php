<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/sessions.php';

$logged_in      = is_active_subscriber();
$upcoming       = get_upcoming_sessions(20);
$polar_checkout = 'https://buy.polar.sh/polar_cl_' . '3bbf8000-9928-486f-890b-edb630b7733d';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule — Watch Me Build AI</title>
    <meta name="description" content="Upcoming live sessions. Zoom links are available to subscribers in the member portal.">
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
        <div class="nav-links">
            <a href="/schedule.php">Schedule</a>
            <a href="/pricing.php">Pricing</a>
            <a href="/faq.php">FAQ</a>
            <?php if ($logged_in): ?>
                <a href="/portal/" class="btn btn-sm">Member Portal →</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-sm btn-outline">Log In</a>
                <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-sm">Start Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Upcoming Sessions</h1>
    </div>

    <p style="color:var(--text-muted);font-size:14px;margin-bottom:32px">
        Sessions run as long as the build runs — no fixed length.
        <?php if (!$logged_in): ?>
            Zoom links are available to subscribers. <a href="<?= htmlspecialchars($polar_checkout) ?>">Start your free trial →</a>
        <?php endif; ?>
    </p>

    <?php if ($upcoming): ?>
    <div class="session-list" style="margin-bottom:80px">
        <?php foreach ($upcoming as $session): ?>
        <div class="session-card">
            <div class="session-date"><?= htmlspecialchars(format_session_date($session['date'])) ?></div>
            <div class="session-info">
                <div class="session-title"><?= htmlspecialchars($session['title']) ?></div>
                <?php if (!empty($session['description'])): ?>
                <div class="session-desc"><?= htmlspecialchars($session['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="session-lock">
                <?php if ($logged_in): ?>
                    <a href="/portal/#session-<?= htmlspecialchars($session['id']) ?>" class="btn btn-sm">Join →</a>
                <?php else: ?>
                    <span class="lock-badge">🔒 Subscribers only</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="session-empty" style="margin-bottom:80px">
        <p>No sessions scheduled yet — check back soon.</p>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-brand">Watch Me Build AI</span>
            <span class="footer-links">
                <a href="/faq.php">FAQ</a>
                <a href="/login.php">Member Login</a>
            </span>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
