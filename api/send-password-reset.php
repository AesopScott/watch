<?php
// Generates a Firebase password reset OOB code via the Admin API and sends
// the reset link from scott@watchmebuildai.com via Brevo.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/firebase-admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

// Always return 200 — never reveal whether the email exists.
try {
    $token = firebase_admin_token();
    if ($token) {
        $oob_code = firebase_generate_password_reset_oob($token, $email);
        if ($oob_code) {
            $reset_link = SITE_URL . '/auth-action.php?mode=resetPassword&oobCode=' . urlencode($oob_code);
            _send_reset_email($email, $reset_link);
        }
    }
} catch (Throwable $e) {
    // Fail silently — client always gets 200
}

echo json_encode(['ok' => true]);

function _send_reset_email(string $email, string $link): void {
    $payload = [
        'to'     => [['email' => $email]],
        'sender' => ['email' => BREVO_SENDER_EMAIL, 'name' => BREVO_SENDER_NAME],
        'subject' => 'Reset your Watch Me Build AI password',
        'htmlContent' => '
            <p>Click the link below to set a new password. It expires in 1 hour.</p>
            <p><a href="' . htmlspecialchars($link) . '" style="font-size:18px;font-weight:bold;">Reset Password →</a></p>
            <p style="color:#888;font-size:12px;">If you didn\'t request this, you can safely ignore this email.</p>
        ',
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
