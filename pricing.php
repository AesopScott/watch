<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in      = is_active_subscriber();
$polar_checkout = 'https://buy.polar.sh/polar_cl_' . '3bbf8000-9928-486f-890b-edb630b7733d';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — Watch Me Build AI</title>
    <meta name="description" content="$100/month with a 7-day free trial. Access all live sessions, Zoom links, and the full recorded session library.">
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

<section class="section section-pricing">
    <div class="container">
        <h2 class="section-title">Simple pricing</h2>
        <p class="section-sub">One plan. Everything included. Cancel any time.</p>
        <div class="pricing-card">
            <div class="pricing-badge">Most popular</div>
            <div class="pricing-amount">$100<span>/month</span></div>
            <div class="pricing-trial">Start with a 7-day free trial</div>
            <ul class="pricing-features">
                <li>All live sessions</li>
                <li>Zoom links delivered before each session</li>
                <li>Full recorded session library</li>
                <li>Email reminders before every session</li>
                <li>Cancel any time — access through end of billing month</li>
            </ul>
            <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-primary btn-lg btn-full">
                Start Free Trial →
            </a>
            <p class="pricing-note">You become a subscriber for the next 7 days. No charge until your trial ends.</p>
        </div>
    </div>
</section>

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
