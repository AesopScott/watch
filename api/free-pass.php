<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/firebase.php';
require_once __DIR__ . '/../lib/firebase-admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$id_token = trim((string) ($body['idToken'] ?? ''));

if ($id_token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing idToken']);
    exit;
}

$claims = verify_firebase_id_token($id_token);
if (!$claims) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

$email = strtolower(trim((string) ($claims['email'] ?? '')));
$uid   = trim((string) ($claims['sub'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $uid === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No valid account in token']);
    exit;
}

$store = read_json_file(subscribers_file());
if (!isset($store['subscribers'])) $store['subscribers'] = [];
$existing = $store['subscribers'][$email] ?? [];
$existing_expires = (string) ($existing['ends_at'] ?? '');
$existing_active  = ($existing['status'] ?? '') === 'active'
    && ($existing_expires === '' || strtotime($existing_expires) >= time());

if ($existing_active) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['subscriber_email']      = $email;
    $_SESSION['subscriber_plan']       = (string) ($existing['plan'] ?? 'pro');
    $_SESSION['subscriber_status']     = 'active';
    $_SESSION['subscriber_expires_at'] = $existing_expires;
    $_SESSION['logged_in_at']          = time();

    echo json_encode(['ok' => true, 'redirect' => '/portal/', 'expires_at' => $existing_expires]);
    exit;
}

if (!empty($existing['free_pass_claimed_at'])) {
    http_response_code(409);
    echo json_encode(['error' => 'already_claimed']);
    exit;
}

$expires_ts  = time() + 7 * 24 * 60 * 60;
$expires_iso = gmdate('c', $expires_ts);
$started_iso = gmdate('c');
$plan        = 'pro';

$token = firebase_admin_token();
if ($token === '' || !firebase_set_claims($token, $uid, [
    'plan'       => $plan,
    'status'     => 'active',
    'trial'      => true,
    'expires_at' => $expires_iso,
])) {
    http_response_code(500);
    echo json_encode(['error' => 'grant_failed']);
    exit;
}

$store['subscribers'][$email] = array_merge($existing, [
    'status'               => 'active',
    'plan'                 => $plan,
    'trial'                => true,
    'started_at'           => $existing['started_at'] ?? $started_iso,
    'ends_at'              => $expires_iso,
    'manual'               => false,
    'source'               => 'free_pass',
    'free_pass_claimed_at' => $started_iso,
]);
write_json_file(subscribers_file(), $store);

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['subscriber_email']      = $email;
$_SESSION['subscriber_plan']       = $plan;
$_SESSION['subscriber_status']     = 'active';
$_SESSION['subscriber_expires_at'] = $expires_iso;
$_SESSION['logged_in_at']          = time();

echo json_encode(['ok' => true, 'redirect' => '/portal/', 'expires_at' => $expires_iso]);
