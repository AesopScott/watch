<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/sessions.php';
require_once __DIR__ . '/lib/meetup.php';

$logged_in      = is_active_subscriber();
$upcoming       = get_upcoming_sessions(20);
$meetup_events  = get_meetup_events(30);
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

    <!-- ── Stage 2: Pro Sessions (paywalled) ── -->
    <section style="margin-bottom:60px">
        <div class="schedule-section-label">Stage 2 — Pro Sessions</div>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">
            Live build sessions — Zoom links for subscribers only.
            <?php if (!$logged_in): ?>
                <a href="<?= htmlspecialchars($polar_checkout) ?>">Start your free trial →</a>
            <?php endif; ?>
        </p>

        <?php if ($upcoming): ?>
        <div class="session-list">
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
        <div class="session-empty">
            <p>No Pro Sessions scheduled yet — check back soon.</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Stage 1: Meetup Events (public) ── -->
    <section style="margin-bottom:80px">
        <div class="schedule-section-label">Stage 1 — Weekly Meetup Events</div>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">
            Free public events hosted across the Advanced AI Concepts network. No subscription required.
        </p>

        <?php if ($meetup_events): ?>
        <div class="session-list">
            <?php foreach ($meetup_events as $event): ?>
            <div class="session-card">
                <div class="session-date"><?= htmlspecialchars(format_meetup_date($event['date'])) ?></div>
                <div class="session-info">
                    <div class="session-title"><?= htmlspecialchars($event['title']) ?></div>
                    <?php if ($event['rsvps'] > 0): ?>
                    <div class="session-desc"><?= $event['rsvps'] ?> RSVPs across the network</div>
                    <?php endif; ?>
                </div>
                <div class="session-lock">
                    <a href="<?= htmlspecialchars($event['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">RSVP on Meetup →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="session-empty">
            <p>No upcoming Meetup events found — check <a href="https://www.meetup.com/advanced-ai-concepts/" target="_blank" rel="noopener">Meetup</a> directly.</p>
        </div>
        <?php endif; ?>
    </section>
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
