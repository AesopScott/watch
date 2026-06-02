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

$status = check_subscriber_status($email);
if ($status !== 'active') {
    http_response_code(403);
    echo json_encode(['error' => 'not_subscriber', 'email' => $email]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['subscriber_email'] = $email;
$_SESSION['logged_in_at']     = time();

echo json_encode(['ok' => true, 'redirect' => '/portal/']);
