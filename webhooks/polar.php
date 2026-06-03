<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/firebase-admin.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function respond(int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['message' => $message]);
    exit;
}

function set_subscriber_claims(string $email, array $claims): void {
    try {
        $token = firebase_admin_token();
        if (!$token) return;
        $uid = firebase_uid_for_email($token, $email);
        if ($uid) firebase_set_claims($token, $uid, $claims);
    } catch (Throwable $e) {
        // Fail open — don't block webhook response on Firebase errors
    }
}

function plan_for_product(string $product_id): string {
    return POLAR_PRODUCT_PLANS[$product_id] ?? 'pro';
}

// ── Signature verification ────────────────────────────────────────────────────

$raw_body  = file_get_contents('php://input');
$signature = $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $raw_body, POLAR_HMAC_SECRET);
if (!hash_equals($expected, $signature)) {
    respond(401, 'Invalid signature');
}

$event = json_decode($raw_body, true);
if (!$event || !isset($event['type'], $event['data'])) {
    respond(400, 'Malformed payload');
}

$type       = $event['type'];
$data       = $event['data'];
$email      = strtolower(trim($data['customer']['email'] ?? ''));
$product_id = $data['product_id'] ?? ($data['product']['id'] ?? '');
$trial      = ($data['started_with_trial'] ?? false) === true;

if (!$email) respond(400, 'No customer email in payload');

if ($product_id && !in_array($product_id, POLAR_PRODUCT_IDS, true)) {
    respond(200, 'Product not managed by this webhook');
}

$plan = plan_for_product($product_id);

// ── Route events ──────────────────────────────────────────────────────────────

switch ($type) {

    case 'subscription.created':
        set_subscriber_claims($email, ['plan' => $plan, 'status' => 'active', 'trial' => $trial]);
        brevo_upsert_contact($email, 'active', $trial);
        brevo_send_onboarding($email);
        notify_admin_new_signup($email, $plan, $trial);
        respond(200, 'Subscriber activated');

    case 'subscription.updated':
        set_subscriber_claims($email, ['plan' => $plan, 'status' => 'active', 'trial' => false]);
        brevo_upsert_contact($email, 'active', false);
        respond(200, 'Subscriber updated');

    case 'subscription.canceled':
        // Access continues to end of billing period — mark cancelled (still let them in until period ends)
        set_subscriber_claims($email, ['plan' => $plan, 'status' => 'cancelled', 'trial' => false]);
        brevo_upsert_contact($email, 'cancelled', false);
        respond(200, 'Subscription cancellation recorded');

    case 'subscription.revoked':
        set_subscriber_claims($email, ['plan' => '', 'status' => 'inactive', 'trial' => false]);
        brevo_upsert_contact($email, 'inactive', false);
        respond(200, 'Subscription revoked');

    default:
        respond(200, 'Event type not handled');
}

// ── Brevo integration ─────────────────────────────────────────────────────────

function notify_admin_new_signup(string $email, string $plan, bool $trial): void {
    $plan_label  = ucfirst($plan) . ($trial ? ' (trial)' : '');
    $subject     = 'New signup: ' . $email . ' — ' . $plan_label;
    $html        = '<p><strong>New subscriber:</strong> ' . htmlspecialchars($email) . '</p>'
                 . '<p><strong>Plan:</strong> ' . htmlspecialchars($plan_label) . '</p>'
                 . '<p><strong>Time:</strong> ' . gmdate('Y-m-d H:i:s') . ' UTC</p>';

    brevo_post('https://api.brevo.com/v3/smtp/email', [
        'sender'      => ['email' => BREVO_SENDER_EMAIL, 'name' => BREVO_SENDER_NAME],
        'to'          => [['email' => BREVO_SENDER_EMAIL]],
        'subject'     => $subject,
        'htmlContent' => $html,
    ]);
}

function brevo_upsert_contact(string $email, string $status, bool $trial): void {
    brevo_post('https://api.brevo.com/v3/contacts', [
        'email'      => $email,
        'listIds'    => [BREVO_LIST_ID],
        'attributes' => ['SUBSCRIPTION_STATUS' => $status, 'IS_TRIAL' => $trial ? 'yes' : 'no'],
        'updateEnabled' => true,
    ]);
}

function brevo_send_onboarding(string $email): void {
    if (!defined('BREVO_ONBOARDING_TEMPLATE_ID')) return;
    brevo_post('https://api.brevo.com/v3/smtp/email', [
        'to'         => [['email' => $email]],
        'templateId' => BREVO_ONBOARDING_TEMPLATE_ID,
        'params'     => ['SITE_URL' => SITE_URL],
    ]);
}

function brevo_post(string $url, array $payload): void {
    $ch = curl_init($url);
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
