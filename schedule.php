<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/sessions.php';
require_once __DIR__ . '/lib/meetup.php';

$logged_in      = is_active_subscriber();
$upcoming       = get_upcoming_sessions(60);
$meetup_events  = get_meetup_events(60);
$polar_checkout = 'https://buy.polar.sh/polar_cl_' . '3bbf8000-9928-486f-890b-edb630b7733d';

// ── Build event map keyed by YYYY-MM-DD ──────────────────────────────────────
$event_map = [];

$mountain_tz = new DateTimeZone('America/Denver');

foreach ($upcoming as $s) {
    $ts  = strtotime($s['date']);
    if (!$ts) continue;
    $dt  = (new DateTime('@' . $ts))->setTimezone($mountain_tz);
    $day = $dt->format('Y-m-d');
    $mt  = $dt->format('g:i a T');
    $utc = gmdate('g:i a', $ts) . ' UTC';
    $event_map[$day][] = [
        'type'   => 'pro',
        'title'  => $s['title'],
        'time'   => $mt . ' / ' . $utc,
        'desc'   => $s['description'] ?? '',
        'url'    => $logged_in ? '/portal/#session-' . htmlspecialchars($s['id']) : null,
        'locked' => !$logged_in,
    ];
}

foreach ($meetup_events as $e) {
    $ts  = strtotime($e['date'] ?? '');
    if (!$ts) continue;
    $dt  = (new DateTime('@' . $ts))->setTimezone($mountain_tz);
    $day = $dt->format('Y-m-d');  // place on the Mountain Time calendar day
    $rsvp_line = $e['rsvps'] > 0 ? $e['rsvps'] . ' RSVPs across the network' : '';
    $desc_full = trim(($e['description'] ?? '') . ($rsvp_line ? "\n\n" . $rsvp_line : ''));
    $mt  = $dt->format('g:i a T');
    $utc = gmdate('g:i a', $ts) . ' UTC';
    $event_map[$day][] = [
        'type'   => 'meetup',
        'title'  => $e['title'],
        'time'   => $mt . ' / ' . $utc,
        'desc'   => $desc_full ?: $rsvp_line,
        'url'    => !empty($e['eventUrl']) ? $e['eventUrl'] : $e['url'],
        'locked' => false,
    ];
}

// ── Recurring Pro Sessions: Tue/Thu 8am and 3pm MT ───────────────────────────
$now    = time();
$cursor = new DateTime('today', $mountain_tz);
$end    = new DateTime('first day of +3 months', $mountain_tz);
$end->modify('last day of this month')->setTime(23, 59, 59);

while ($cursor <= $end) {
    $dow = (int) $cursor->format('N'); // 2=Tue, 4=Thu
    if ($dow === 2 || $dow === 4) {
        foreach ([8, 15] as $hour) {
            $dt  = clone $cursor;
            $dt->setTime($hour, 0, 0);
            $ts  = $dt->getTimestamp();
            if ($ts < $now) { continue; }
            $day = $dt->format('Y-m-d');
            $mt  = $dt->format('g:i a T');
            $utc = gmdate('g:i a', $ts) . ' UTC';
            $event_map[$day][] = [
                'type'   => 'pro',
                'title'  => 'Pro Session',
                'time'   => $mt . ' / ' . $utc,
                'desc'   => 'Pro Members Only',
                'url'    => $logged_in ? '/portal/' : null,
                'locked' => !$logged_in,
            ];
        }
    }
    $cursor->modify('+1 day');
}

// ── Calendar month builder ────────────────────────────────────────────────────
function cal_months(int $count = 3): array {
    $months = [];
    $base   = strtotime(date('Y-m-01'));
    for ($i = 0; $i < $count; $i++) {
        $ts = strtotime("+$i month", $base);
        $months[] = [
            'label'     => date('F Y', $ts),
            'year'      => (int) date('Y', $ts),
            'month'     => (int) date('n', $ts),
            'first_dow' => (int) date('w', $ts),   // 0 = Sunday
            'days'      => (int) date('t', $ts),
        ];
    }
    return $months;
}

$months  = cal_months(3);
$today   = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule — Watch Me Build AI</title>
    <meta name="description" content="Upcoming live sessions and public Meetup events.">
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
        <h1>Schedule</h1>
    </div>

    <!-- Legend -->
    <div class="cal-legend">
        <span class="cal-dot cal-dot-pro"></span> Pro Sessions
        <span class="cal-dot cal-dot-cohort"  style="margin-left:20px"></span> Cohort 10
        <span class="cal-dot cal-dot-private" style="margin-left:20px"></span> Private Cohort
        <span class="cal-dot cal-dot-meetup"  style="margin-left:20px"></span> Meetup (free, public)
    </div>

    <!-- Calendars -->
    <?php foreach ($months as $m): ?>
    <div class="cal-month">
        <div class="cal-month-label"><?= $m['label'] ?></div>
        <div class="cal-grid">
            <div class="cal-weekday">Sun</div>
            <div class="cal-weekday">Mon</div>
            <div class="cal-weekday">Tue</div>
            <div class="cal-weekday">Wed</div>
            <div class="cal-weekday">Thu</div>
            <div class="cal-weekday">Fri</div>
            <div class="cal-weekday">Sat</div>

            <?php
            // Empty cells before month starts
            for ($blank = 0; $blank < $m['first_dow']; $blank++): ?>
            <div class="cal-day cal-day-empty"></div>
            <?php endfor; ?>

            <?php for ($d = 1; $d <= $m['days']; $d++):
                $date_key = sprintf('%04d-%02d-%02d', $m['year'], $m['month'], $d);
                $is_today = $date_key === $today;
                $events   = $event_map[$date_key] ?? [];
            ?>
            <div class="cal-day<?= $is_today ? ' cal-day-today' : '' ?>">
                <div class="cal-day-num"><?= $d ?></div>
                <?php foreach ($events as $ev):
                    $tip = htmlspecialchars(json_encode([
                        'title'  => $ev['title'],
                        'time'   => $ev['time'],
                        'desc'   => $ev['desc'],
                        'url'    => $ev['url'],
                        'locked' => $ev['locked'],
                        'type'   => $ev['type'],
                    ]), ENT_QUOTES);
                ?>
                <div class="cal-event cal-event-<?= $ev['type'] ?>" data-tip="<?= $tip ?>">
                    <span class="cal-event-title"><?= htmlspecialchars($ev['title']) ?></span>
                    <?php if ($ev['time']): ?>
                    <span class="cal-event-time"><?= htmlspecialchars($ev['time']) ?></span>
                    <?php endif; ?>
                    <?php if ($ev['type'] === 'meetup'): ?>
                    <span class="cal-event-badge">Meetup · Free</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Tooltip (hidden, moved by JS) -->
    <div id="cal-tooltip" class="cal-tooltip" style="display:none">
        <div class="cal-tip-type"></div>
        <div class="cal-tip-title"></div>
        <div class="cal-tip-time"></div>
        <div class="cal-tip-desc"></div>
        <a class="cal-tip-cta btn btn-sm btn-primary" href="#" target="_blank" rel="noopener" style="margin-top:10px;display:none"></a>
        <span class="cal-tip-locked" style="display:none">🔒 Subscribers only — <a href="<?= htmlspecialchars($polar_checkout) ?>">start free trial</a></span>
    </div>
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
<script>
(function () {
    const tip   = document.getElementById('cal-tooltip');
    const events = document.querySelectorAll('.cal-event');

    function show(el, data) {
        tip.querySelector('.cal-tip-type').textContent  = data.type === 'pro' ? 'Pro Session' : 'Meetup Event';
        tip.querySelector('.cal-tip-title').textContent = data.title;
        tip.querySelector('.cal-tip-time').textContent  = data.time;
        tip.querySelector('.cal-tip-desc').textContent  = data.desc || '';

        const cta    = tip.querySelector('.cal-tip-cta');
        const locked = tip.querySelector('.cal-tip-locked');

        if (data.locked) {
            cta.style.display    = 'none';
            locked.style.display = 'block';
        } else if (data.url) {
            cta.href         = data.url;
            cta.textContent  = data.type === 'pro' ? 'Join Session →' : 'RSVP on Meetup →';
            cta.style.display    = 'inline-flex';
            locked.style.display = 'none';
        } else {
            cta.style.display    = 'none';
            locked.style.display = 'none';
        }

        tip.style.display = 'block';
        position(el);
    }

    function position(el) {
        const rect = el.getBoundingClientRect();
        const sw   = window.innerWidth;
        let   left = rect.left + window.scrollX + rect.width / 2 - tip.offsetWidth / 2;
        let   top  = rect.top  + window.scrollY - tip.offsetHeight - 10;

        // Keep within viewport
        if (left < 8) left = 8;
        if (left + tip.offsetWidth > sw - 8) left = sw - tip.offsetWidth - 8;
        if (top < window.scrollY + 8) top = rect.bottom + window.scrollY + 10;

        tip.style.left = left + 'px';
        tip.style.top  = top  + 'px';
    }

    events.forEach(el => {
        el.addEventListener('mouseenter', () => {
            try { show(el, JSON.parse(el.dataset.tip)); } catch(e) {}
        });
        el.addEventListener('mouseleave', () => { tip.style.display = 'none'; });
    });

    document.addEventListener('scroll', () => { tip.style.display = 'none'; }, { passive: true });
})();
</script>
</body>
</html>
