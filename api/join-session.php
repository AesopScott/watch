<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/attendance.php';
require_once __DIR__ . '/../lib/sessions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth ─────────────────────────────────────────────────────────────────────
if (!is_active_subscriber()) {
    header('Location: /login.php');
    exit;
}

$email      = (string) ($_SESSION['subscriber_email'] ?? '');
$session_id = trim($_GET['id'] ?? '');
$go         = isset($_GET['go']);

if ($session_id === '') {
    join_error('No session specified.');
}

// ── Load session ─────────────────────────────────────────────────────────────
$all_sessions = get_all_sessions();
$session      = null;
foreach ($all_sessions as $s) {
    if (($s['id'] ?? '') === $session_id) { $session = $s; break; }
}

if ($session === null) {
    join_error('Session not found. <a href="/portal/">Back to portal</a>');
}

$zoom_url = session_zoom_url($session);
if ($zoom_url === '') {
    join_error('The Zoom link for this session isn\'t available yet — check back closer to the session time. <a href="/portal/">Back to portal</a>');
}

// ── Subscriber plan ───────────────────────────────────────────────────────────
$sub  = get_subscriber_data($email);
$plan = strtolower(trim($sub['plan'] ?? 'pro'));

// ── Time gate ────────────────────────────────────────────────────────────────
$session_ts   = strtotime($session['date'] ?? '');
$now          = time();
$window_open  = $session_ts - 15 * 60;   // 15 min before
$window_close = $session_ts + 30 * 60;   // 30 min after

if ($session_ts && $now < $window_open) {
    $opens_in = $window_open - $now;
    $mins     = (int) ceil($opens_in / 60);
    join_info(
        htmlspecialchars($session['title'] ?? 'Session'),
        'Session hasn\'t started yet',
        'The join link opens 15 minutes before the session. '
        . 'Come back in about <strong>' . $mins . ' minute' . ($mins === 1 ? '' : 's') . '</strong>.',
        null
    );
}

if ($session_ts && $now > $window_close) {
    join_info(
        htmlspecialchars($session['title'] ?? 'Session'),
        'Session has ended',
        'This session is over. Recordings are posted to the <a href="/portal/">member portal</a> shortly after each session.',
        '/portal/'
    );
}

// ── Capacity check ────────────────────────────────────────────────────────────
if (!has_capacity($session_id, $plan)) {
    $tier  = is_pro_lite_plan($plan) ? 'Pro Lite' : 'Pro';
    $limit = is_pro_lite_plan($plan) ? SESSION_CAPACITY_PRO_LITE : SESSION_CAPACITY_PRO;
    join_error(
        'This session has reached the ' . $tier . ' capacity limit of ' . $limit . ' seats. '
        . (is_pro_lite_plan($plan)
            ? 'Upgrade to Pro for more guaranteed access. <a href="/pricing.php">View plans</a>'
            : '')
        . ' <a href="/portal/">Back to portal</a>'
    );
}

// ── Pro Lite weekly limit ─────────────────────────────────────────────────────
if (is_pro_lite_plan($plan)) {
    $week_key = gmdate('o-W');
    $claims   = $sub['weekly_claims'][$week_key] ?? [];
    if (!in_array($session_id, $claims, true) && count($claims) >= 1) {
        $used_title = 'a session';
        foreach ($all_sessions as $s) {
            if (($s['id'] ?? '') === ($claims[0] ?? '')) {
                $used_title = htmlspecialchars($s['title'] ?? 'a session');
                break;
            }
        }
        join_error(
            'Your Pro Lite plan includes one session per week. '
            . 'You already joined <strong>' . $used_title . '</strong> this week. '
            . 'Access resets Monday. '
            . '<a href="/portal/">Back to portal</a> &nbsp;·&nbsp; '
            . '<a href="/pricing.php">Upgrade to Pro</a>'
        );
    }
}

// ── Request 2: validated go — record and redirect ────────────────────────────
if ($go) {
    $briefed_at = $_SESSION['join_briefed'][$session_id] ?? 0;
    if (!$briefed_at || (time() - $briefed_at) > 600) {
        header('Location: /api/join-session.php?id=' . urlencode($session_id));
        exit;
    }

    if (!has_capacity($session_id, $plan)) {
        join_error('Sorry, the last seat was just taken. <a href="/portal/">Back to portal</a>');
    }

    record_join($session_id, $email, $plan);

    if (is_pro_lite_plan($plan)) {
        record_session_claim($email, gmdate('o-W'), $session_id);
    }

    unset($_SESSION['join_briefed'][$session_id]);

    header('Location: ' . $zoom_url);
    exit;
}

// ── Request 1: set briefed flag and show briefing page ───────────────────────
if (!isset($_SESSION['join_briefed'])) $_SESSION['join_briefed'] = [];
$_SESSION['join_briefed'][$session_id] = time();

$title    = htmlspecialchars($session['title'] ?? 'Session');
$desc     = htmlspecialchars($session['description'] ?? '');
$date_str = isset($session['date']) ? format_session_date_brief($session['date']) : '';
$go_url   = '/api/join-session.php?id=' . urlencode($session_id) . '&go=1';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joining: <?= $title ?> — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .brief-wrap { max-width: 520px; margin: 80px auto; text-align: center; padding: 0 24px; }
        .brief-type { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 12px; }
        .brief-title { font-size: 26px; font-weight: 800; line-height: 1.2; margin-bottom: 8px; letter-spacing: -0.5px; }
        .brief-date { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; }
        .brief-desc { font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-bottom: 40px; }
        .brief-countdown { font-size: 48px; font-weight: 800; color: var(--accent); font-family: var(--mono); line-height: 1; margin-bottom: 8px; }
        .brief-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 32px; }
        .brief-actions { display: flex; gap: 12px; justify-content: center; align-items: center; }
    </style>
</head>
<body>
<nav class="nav"><div class="nav-inner">
    <a href="/" class="nav-logo">Watch Me Build AI</a>
    <div class="nav-links">
        <a href="/portal/" class="btn btn-sm btn-outline">← Portal</a>
    </div>
</div></nav>

<div class="brief-wrap">
    <div class="brief-type">Joining session</div>
    <h1 class="brief-title"><?= $title ?></h1>
    <?php if ($date_str): ?><div class="brief-date"><?= htmlspecialchars($date_str) ?></div><?php endif; ?>
    <?php if ($desc): ?><div class="brief-desc"><?= $desc ?></div><?php endif; ?>
    <div class="brief-countdown" id="count">5</div>
    <div class="brief-sub">Redirecting to Zoom automatically…</div>
    <div class="brief-actions">
        <a href="<?= htmlspecialchars($go_url) ?>" class="btn btn-primary" id="join-btn">Join Now →</a>
        <a href="/portal/" style="font-size:13px;color:var(--text-muted)">Cancel</a>
    </div>
</div>

<script>
(function () {
    const go = <?= json_encode($go_url) ?>;
    const el = document.getElementById('count');
    let   n  = 5;
    const t  = setInterval(() => {
        n--;
        el.textContent = n;
        if (n <= 0) { clearInterval(t); window.location.href = go; }
    }, 1000);
    document.getElementById('join-btn').addEventListener('click', () => clearInterval(t));
})();
</script>
</body>
</html>
<?php
exit;

// ── Helpers ───────────────────────────────────────────────────────────────────
function join_error(string $message): void {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Session Access — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    </head><body>
    <nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">Watch Me Build AI</a></div></nav>
    <div class="container" style="max-width:540px;padding-top:80px;text-align:center">
        <h2 style="margin-bottom:16px">Session Access</h2>
        <p style="color:var(--text-muted);line-height:1.7">' . $message . '</p>
    </div></body></html>';
    exit;
}

function join_info(string $title, string $heading, string $body, ?string $cta_url): void {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . $title . ' — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    </head><body>
    <nav class="nav"><div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
        <div class="nav-links"><a href="/portal/" class="btn btn-sm btn-outline">← Portal</a></div>
    </div></nav>
    <div class="container" style="max-width:540px;padding-top:80px;text-align:center">
        <h2 style="margin-bottom:8px">' . htmlspecialchars($heading) . '</h2>
        <p style="font-size:20px;font-weight:700;margin-bottom:16px">' . $title . '</p>
        <p style="color:var(--text-muted);line-height:1.7;margin-bottom:32px">' . $body . '</p>'
        . ($cta_url ? '<a href="' . htmlspecialchars($cta_url) . '" class="btn btn-primary">Back to Portal →</a>' : '')
    . '</div></body></html>';
    exit;
}

function format_session_date_brief(string $date): string {
    $ts = strtotime($date);
    if (!$ts) return '';
    return date('l, F j \a\t g:i a T', $ts);
}
