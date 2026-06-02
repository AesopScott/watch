<?php
/**
 * Session join gateway.
 *
 * Verifies subscriber auth, enforces the Pro Lite one-session-per-week limit,
 * records the claim, then redirects to the Zoom URL.
 *
 * Fail-open policy: any unexpected read/write error lets the subscriber
 * through rather than locking them out.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/sessions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth check ────────────────────────────────────────────────────────────────
if (!is_active_subscriber()) {
    header('Location: /login.php');
    exit;
}

$email      = (string) ($_SESSION['subscriber_email'] ?? '');
$session_id = trim($_GET['id'] ?? '');

if ($session_id === '') {
    join_error('No session specified.');
}

// ── Load the session ──────────────────────────────────────────────────────────
$all_sessions = get_all_sessions();
$session      = null;
foreach ($all_sessions as $s) {
    if (($s['id'] ?? '') === $session_id) {
        $session = $s;
        break;
    }
}

if ($session === null) {
    join_error('Session not found. <a href="/portal/">Back to portal</a>');
}

$zoom_url = trim($session['zoom_url'] ?? '');
if ($zoom_url === '') {
    join_error('The Zoom link for this session isn\'t available yet. Check back closer to the session time. <a href="/portal/">Back to portal</a>');
}

// ── Plan-based access control ─────────────────────────────────────────────────
$sub  = get_subscriber_data($email);  // returns [] on any read error → fail open
$plan = strtolower(trim($sub['plan'] ?? ''));

$pro_lite_plans = ['pro_lite', 'weekly', 'lite'];

if (in_array($plan, $pro_lite_plans, true)) {
    $week_key = gmdate('o-W');  // ISO year-week in UTC, e.g. "2026-W23"
    $claims   = $sub['weekly_claims'][$week_key] ?? [];

    if (!in_array($session_id, $claims, true) && count($claims) >= 1) {
        // Already used their one session this week
        $used_id    = $claims[0];
        $used_title = 'a session';
        foreach ($all_sessions as $s) {
            if (($s['id'] ?? '') === $used_id) {
                $used_title = htmlspecialchars($s['title'] ?? 'a session');
                break;
            }
        }
        join_error(
            'Your Pro Lite plan includes one session per week. '
            . 'You already joined <strong>' . $used_title . '</strong> this week. '
            . 'Your access resets next Monday. '
            . '<a href="/portal/">Back to portal</a> &nbsp;·&nbsp; '
            . '<a href="/pricing.php">Upgrade to Pro</a>'
        );
    }

    // Record the claim — fail open if write fails
    record_session_claim($email, $week_key, $session_id);
}

// ── Redirect to Zoom ──────────────────────────────────────────────────────────
header('Location: ' . $zoom_url);
exit;

// ── Helpers ───────────────────────────────────────────────────────────────────
function join_error(string $message): never {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Session Access — Watch Me Build AI</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    </head><body>
    <nav class="nav"><div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
    </div></nav>
    <div class="container" style="max-width:560px;padding-top:80px;text-align:center">
        <h2 style="margin-bottom:16px">Session Access</h2>
        <p style="color:var(--text-muted);line-height:1.7">' . $message . '</p>
    </div></body></html>';
    exit;
}
