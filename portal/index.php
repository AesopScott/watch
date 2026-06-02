<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/sessions.php';

if (!is_active_subscriber()) {
    header('Location: /login.php');
    exit;
}

$email    = $_SESSION['subscriber_email'];
$upcoming = get_upcoming_sessions_with_zoom(10);
$past     = get_recordings(50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
        <div class="nav-links">
            <span style="font-size:13px;color:var(--text-muted)"><?= htmlspecialchars($email) ?></span>
            <a href="/logout.php" class="btn btn-sm btn-outline">Log Out</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Member Portal</h1>
    </div>

    <!-- Upcoming sessions with Zoom links -->
    <section style="margin-bottom:60px">
        <h2 class="section-title" style="text-align:left;margin-bottom:8px">Upcoming Sessions</h2>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">Zoom links are live 15 minutes before each session.</p>

        <?php if ($upcoming): ?>
        <div class="session-list">
            <?php foreach ($upcoming as $s): ?>
            <div class="session-card" id="session-<?= htmlspecialchars($s['id']) ?>">
                <div class="session-date"><?= htmlspecialchars(format_session_date($s['date'])) ?></div>
                <div class="session-info">
                    <div class="session-title"><?= htmlspecialchars($s['title']) ?></div>
                    <?php if (!empty($s['description'])): ?>
                    <div class="session-desc"><?= htmlspecialchars($s['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="session-lock">
                    <?php if (!empty($s['zoom_url'])): ?>
                        <a href="/api/join-session.php?id=<?= urlencode($s['id']) ?>" class="btn btn-sm btn-primary">Join Zoom →</a>
                    <?php else: ?>
                        <span class="lock-badge">Link coming soon</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="session-empty">
            <p>No sessions scheduled yet — check back soon.</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- Recordings library -->
    <section style="margin-bottom:80px">
        <h2 class="section-title" style="text-align:left;margin-bottom:8px">Session Recordings</h2>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">All past sessions are available to watch at any time.</p>

        <?php if ($past): ?>
        <div class="session-list">
            <?php foreach ($past as $r): ?>
            <div class="session-card">
                <div class="session-date" style="color:var(--text-muted)"><?= htmlspecialchars(format_session_date($r['date'])) ?></div>
                <div class="session-info">
                    <div class="session-title"><?= htmlspecialchars($r['title']) ?></div>
                    <?php if (!empty($r['description'])): ?>
                    <div class="session-desc"><?= htmlspecialchars($r['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="session-lock">
                    <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm">Watch →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="session-empty">
            <p>Recordings will appear here after each live session.</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-brand">Watch Me Build AI</span>
            <span class="footer-links">
                <a href="mailto:scott@watchmebuildai.com">Support</a>
            </span>
        </div>
    </div>
</footer>

</body>
</html>
