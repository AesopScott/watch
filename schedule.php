<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/sessions.php';
require_once __DIR__ . '/lib/meetup.php';

$logged_in      = is_active_subscriber();
$force_refresh  = isset($_GET['refresh']) && $_GET['refresh'] !== 'false';
$upcoming       = get_upcoming_sessions(1000);
$meetup_events  = get_meetup_events(60, $force_refresh);
$free_pass_url  = '/free-pass.php';

// ── Build event map keyed by YYYY-MM-DD ──────────────────────────────────────
$event_map   = [];
$mountain_tz = new DateTimeZone('America/Denver');

foreach ($upcoming as $s) {
    $ts = strtotime($s['date']);
    if (!$ts) continue;
    $dt  = (new DateTime('@' . $ts))->setTimezone($mountain_tz);
    $day = $dt->format('Y-m-d');
    $mt  = $dt->format('g:i a T');
    $utc = gmdate('g:i a', $ts) . ' UTC';
    $event_map[$day][] = [
        'type'     => 'pro',
        'title'    => $s['title'],
        'time'     => $mt . ' / ' . $utc,
        'sort_ts'  => $ts,
        'duration' => '1 hour',
        'desc'     => $s['description'] ?? '',
        'url'      => $logged_in ? '/portal/#session-' . htmlspecialchars($s['id']) : null,
        'locked'   => !$logged_in,
    ];
}

foreach ($meetup_events as $e) {
    $ts = strtotime($e['date'] ?? '');
    if (!$ts) continue;
    $dt        = (new DateTime('@' . $ts))->setTimezone($mountain_tz);
    $day       = $dt->format('Y-m-d');
    if ($day === '2026-06-09' && $dt->format('H:i') === '15:00') {
        continue;
    }
    $mt        = $dt->format('g:i a T');
    $utc       = gmdate('g:i a', $ts) . ' UTC';
    $rsvp_line = $e['rsvps'] > 0 ? $e['rsvps'] . ' RSVPs across the network' : '';
    $desc_full = trim(($e['description'] ?? '') . ($rsvp_line ? "\n\n" . $rsvp_line : ''));
    // Convert urlname to readable label, e.g. "advanced-ai-concepts" → "Advanced AI Concepts"
    $group_label = ucwords(str_replace('-', ' ', $e['group'] ?? ''));
    $event_map[$day][] = [
        'type'     => 'meetup',
        'title'    => $e['title'],
        'group'    => $group_label,
        'time'     => $mt . ' / ' . $utc,
        'sort_ts'  => $ts,
        'duration' => '2 hours',
        'desc'     => $desc_full ?: $rsvp_line,
        'url'      => !empty($e['eventUrl']) ? $e['eventUrl'] : $e['url'],
        'locked'   => false,
    ];
}

// ── Recurring Cohort 10 Sessions: Tue/Thu 1pm and 4pm MT ─────────────────────
$now    = time();
$cursor = new DateTime('today', $mountain_tz);
$end    = new DateTime('first day of +3 months', $mountain_tz);
$end->modify('last day of this month')->setTime(23, 59, 59);

while ($cursor <= $end) {
    $dow = (int) $cursor->format('N');
    if ($dow === 2 || $dow === 4) {
        foreach ([13, 16] as $hour) {
            $dt = clone $cursor;
            $dt->setTime($hour, 0, 0);
            $ts = $dt->getTimestamp();
            if ($ts < $now) continue;
            $day = $dt->format('Y-m-d');
            $mt  = $dt->format('g:i a T');
            $utc = gmdate('g:i a', $ts) . ' UTC';
            $event_map[$day][] = [
                'type'     => 'cohort',
                'title'    => 'Cohort 10 Session',
                'time'     => $mt . ' / ' . $utc,
                'sort_ts'  => $ts,
                'duration' => '1 hour',
                'desc'     => 'Cohort 10 Members Only',
                'url'      => $logged_in ? '/portal/' : null,
                'locked'   => !$logged_in,
            ];
        }
    }
    $cursor->modify('+1 day');
}

// Sort each day's events by timestamp so they appear in chronological order
foreach ($event_map as &$day_events) {
    usort($day_events, fn($a, $b) => ($a['sort_ts'] ?? 0) <=> ($b['sort_ts'] ?? 0));
    foreach ($day_events as &$event) {
        unset($event['sort_ts']);
    }
    unset($event);
}
unset($day_events);

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
            'first_dow' => (int) date('w', $ts),
            'days'      => (int) date('t', $ts),
        ];
    }
    return $months;
}

$months = cal_months(3);
$today  = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule — Watch Me Build AI</title>
    <meta name="description" content="Upcoming live sessions and public Meetup events.">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Schedule — Watch Me Build AI">
    <meta property="og:description" content="Upcoming live AI development sessions. Join a Pro session or catch the next free Meetup.">
    <meta property="og:image" content="https://watchmebuildai.com/assets/images/og.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://watchmebuildai.com/assets/images/og.png">
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
            <a href="/why-choose-us.php">Why Choose Us</a>
            <?php if ($logged_in): ?>
                <a href="/portal/" class="btn btn-sm">Member Portal →</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-sm btn-outline">Log In</a>
                <a href="<?= htmlspecialchars($free_pass_url) ?>" class="btn btn-sm">Start Free Trial</a>
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

            <?php for ($blank = 0; $blank < $m['first_dow']; $blank++): ?>
            <div class="cal-day cal-day-empty"></div>
            <?php endfor; ?>

            <?php for ($d = 1; $d <= $m['days']; $d++):
                $date_key = sprintf('%04d-%02d-%02d', $m['year'], $m['month'], $d);
                $is_today = $date_key === $today;
                $events   = $event_map[$date_key] ?? [];
                $has_events = !empty($events);
            ?>
            <div class="cal-day<?= $is_today ? ' cal-day-today' : '' ?><?= $has_events ? ' cal-day-active' : '' ?>">
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
                    <?php if (!empty($ev['group'])): ?>
                    <span class="cal-event-group"><?= htmlspecialchars($ev['group']) ?></span>
                    <?php endif; ?>
                    <span class="cal-event-time"><?= htmlspecialchars($ev['time']) ?><?= !empty($ev['duration']) ? ' · ' . htmlspecialchars($ev['duration']) : '' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Tooltip -->
    <div id="cal-tooltip" class="cal-tooltip" style="display:none">
        <div class="cal-tip-type"></div>
        <div class="cal-tip-title"></div>
        <div class="cal-tip-time"></div>
        <div class="cal-tip-desc"></div>
        <a class="cal-tip-cta btn btn-sm btn-primary" href="#" target="_blank" rel="noopener" style="margin-top:10px;display:none"></a>
        <span class="cal-tip-locked" style="display:none">
            <span style="font-size:12px;color:var(--text-muted)">🔒 Subscribers only</span>
            <div style="display:flex;gap:8px;margin-top:10px">
                <a href="/login.php" class="btn btn-sm btn-outline" style="flex:1;text-align:center;font-size:12px">Log In →</a>
                <a href="/pricing.php" class="btn btn-sm btn-primary" style="flex:1;text-align:center;font-size:12px">Subscribe</a>
            </div>
        </span>
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
    const tip    = document.getElementById('cal-tooltip');
    const events = document.querySelectorAll('.cal-event');

    const typeLabels = {
        pro:     'Pro Session',
        cohort:  'Cohort 10 Session',
        private: 'Private Cohort Session',
        meetup:  'Meetup Event (Free)',
    };

    function show(el, data) {
        tip.querySelector('.cal-tip-type').textContent  = typeLabels[data.type] || data.type;
        tip.querySelector('.cal-tip-title').textContent = data.title;
        tip.querySelector('.cal-tip-time').textContent  = data.time;
        tip.querySelector('.cal-tip-desc').textContent  = data.desc || '';

        const cta    = tip.querySelector('.cal-tip-cta');
        const locked = tip.querySelector('.cal-tip-locked');

        if (data.locked) {
            cta.style.display    = 'none';
            locked.style.display = 'block';
        } else if (data.url) {
            cta.href             = data.url;
            cta.textContent      = data.type === 'meetup' ? 'RSVP on Meetup →' : 'Join Session →';
            cta.style.display    = 'inline-flex';
            locked.style.display = 'none';
        } else {
            cta.style.display    = 'none';
            locked.style.display = 'none';
        }

        tip.className = 'cal-tooltip cal-tip-' + data.type;
        tip.style.display = 'block';
        position(el);
    }

    function position(el) {
        const rect = el.getBoundingClientRect();
        const sw   = window.innerWidth;
        let   left = rect.left + window.scrollX + rect.width / 2 - tip.offsetWidth / 2;
        let   top  = rect.top  + window.scrollY - tip.offsetHeight - 10;

        if (left < 8) left = 8;
        if (left + tip.offsetWidth > sw - 8) left = sw - tip.offsetWidth - 8;
        if (top < window.scrollY + 8) top = rect.bottom + window.scrollY + 10;

        tip.style.left = left + 'px';
        tip.style.top  = top  + 'px';
    }

    let hideTimer = null;

    function scheduleHide() {
        hideTimer = setTimeout(() => { tip.style.display = 'none'; }, 200);
    }
    function cancelHide() {
        clearTimeout(hideTimer);
    }

    events.forEach(el => {
        el.addEventListener('mouseenter', () => {
            cancelHide();
            try { show(el, JSON.parse(el.dataset.tip)); } catch(e) {}
        });
        el.addEventListener('mouseleave', scheduleHide);
    });

    tip.addEventListener('mouseenter', cancelHide);
    tip.addEventListener('mouseleave', scheduleHide);

    document.addEventListener('scroll', () => { cancelHide(); tip.style.display = 'none'; }, { passive: true });
})();
</script>
</body>
</html>
