<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/sessions.php';

if (!is_active_subscriber()) {
    header('Location: /login.php');
    exit;
}

$email    = $_SESSION['subscriber_email'];
$upcoming = get_upcoming_sessions_with_zoom(1000);
$past     = get_recordings(50);

$portal_tz = new DateTimeZone('America/New_York');
$today     = (new DateTimeImmutable('today', $portal_tz))->format('Y-m-d');
$event_map = [];

foreach ($upcoming as $s) {
    $ts = strtotime($s['date'] ?? '');
    if (!$ts) continue;
    $day = (new DateTimeImmutable('@' . $ts))->setTimezone($portal_tz)->format('Y-m-d');
    $event_map[$day][] = $s;
}

foreach ($event_map as &$events) {
    usort($events, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));
}
unset($events);

function portal_months(int $count = 3): array {
    $months = [];
    $tz     = new DateTimeZone('America/New_York');
    $base   = new DateTimeImmutable('first day of this month', $tz);

    for ($i = 0; $i < $count; $i++) {
        $dt = $base->modify("+$i month");
        $months[] = [
            'id'        => $dt->format('Y-m'),
            'label'     => $dt->format('F Y'),
            'year'      => (int) $dt->format('Y'),
            'month'     => (int) $dt->format('n'),
            'first_dow' => (int) $dt->format('w'),
            'days'      => (int) $dt->format('t'),
        ];
    }

    return $months;
}

$months = portal_months(3);
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
        <div class="portal-section-head">
            <h2 class="section-title" style="text-align:left;margin-bottom:8px">Upcoming Sessions</h2>
            <?php if ($upcoming): ?>
            <a href="/api/calendar.php?all=1" class="btn btn-sm btn-outline">Download All Sessions</a>
            <?php endif; ?>
        </div>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px">Zoom links are live 15 minutes before each session.</p>

        <?php if ($upcoming): ?>
        <div class="portal-month-tabs" role="tablist" aria-label="Session months">
            <?php foreach ($months as $i => $m): ?>
            <button type="button" class="portal-month-tab<?= $i === 0 ? ' active' : '' ?>" data-month="<?= htmlspecialchars($m['id']) ?>">
                <?= htmlspecialchars($m['label']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($months as $i => $m): ?>
        <div class="portal-month-panel<?= $i === 0 ? ' active' : '' ?>" data-month-panel="<?= htmlspecialchars($m['id']) ?>">
            <div class="cal-month-label"><?= htmlspecialchars($m['label']) ?></div>
            <div class="portal-cal-grid">
                <div class="cal-weekday">Sun</div>
                <div class="cal-weekday">Mon</div>
                <div class="cal-weekday">Tue</div>
                <div class="cal-weekday">Wed</div>
                <div class="cal-weekday">Thu</div>
                <div class="cal-weekday">Fri</div>
                <div class="cal-weekday">Sat</div>

                <?php for ($blank = 0; $blank < $m['first_dow']; $blank++): ?>
                <div class="portal-cal-day portal-cal-empty"></div>
                <?php endfor; ?>

                <?php for ($d = 1; $d <= $m['days']; $d++):
                    $date_key = sprintf('%04d-%02d-%02d', $m['year'], $m['month'], $d);
                    $events = $event_map[$date_key] ?? [];
                ?>
                <div class="portal-cal-day<?= $date_key === $today ? ' portal-cal-today' : '' ?>">
                    <div class="portal-cal-day-num"><?= $d ?></div>
                    <?php foreach ($events as $s): ?>
                    <div class="portal-cal-event" id="session-<?= htmlspecialchars($s['id']) ?>">
                        <div class="portal-cal-time"><?= htmlspecialchars(format_session_date($s['date'])) ?></div>
                        <div class="portal-cal-title"><?= htmlspecialchars($s['title']) ?></div>
                        <?php if (!empty($s['description'])): ?>
                        <div class="portal-cal-desc"><?= htmlspecialchars($s['description']) ?></div>
                        <?php endif; ?>
                        <div class="portal-cal-actions">
                            <a href="/api/calendar.php?id=<?= urlencode($s['id']) ?>" class="btn btn-sm btn-outline">Calendar</a>
                            <?php if (session_zoom_url($s) !== ''): ?>
                                <a href="<?= htmlspecialchars(session_join_path($s)) ?>" class="btn btn-sm btn-primary">Join Zoom →</a>
                            <?php else: ?>
                                <span class="lock-badge">Link coming soon</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endforeach; ?>
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

<script>
(function () {
    const tabs = document.querySelectorAll('.portal-month-tab');
    const panels = document.querySelectorAll('.portal-month-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const month = tab.dataset.month;
            tabs.forEach(t => t.classList.toggle('active', t === tab));
            panels.forEach(panel => {
                panel.classList.toggle('active', panel.dataset.monthPanel === month);
            });
        });
    });
})();
</script>
</body>
</html>
