<?php
// Exchanges a Firebase ID token for a PHP session.
// Called client-side after Firebase sign-in; verifies the token,
// checks subscriber status, and sets $_SESSION['subscriber_email'].
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/firebase.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$id_token = trim($body['idToken'] ?? '');

if (!$id_token) {
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

$email = strtolower(trim($claims['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid email in token']);
    exit;
}

// Read subscription status from Firebase custom claims (single source of truth)
$plan       = $claims['plan']       ?? '';
$status     = $claims['status']     ?? '';
$expires_at = $claims['expires_at'] ?? '';

if ($status !== 'active') {
    http_response_code(403);
    echo json_encode(['error' => 'not_subscriber', 'email' => $email]);
    exit;
}

// Block expired manual grants at login time too — defense in depth alongside is_active_subscriber().
if ($expires_at !== '' && strtotime($expires_at) < time()) {
    http_response_code(403);
    echo json_encode(['error' => 'not_subscriber', 'email' => $email]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['subscriber_email']      = $email;
$_SESSION['subscriber_plan']       = $plan;
$_SESSION['subscriber_status']     = $status;
$_SESSION['subscriber_expires_at'] = $expires_at;
$_SESSION['logged_in_at']          = time();

echo json_encode(['ok' => true, 'redirect' => '/portal/']);
