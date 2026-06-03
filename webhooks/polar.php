<?php
require_once __DIR__ . '/../config/config.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function respond(int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['message' => $message]);
    exit;
}

// ── Firebase Admin helpers ────────────────────────────────────────────────────

function firebase_admin_token(): string {
    $key = json_decode(file_get_contents(FIREBASE_ADMIN_KEY_PATH), true);
    $now = time();
    $header  = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
    $payload = rtrim(strtr(base64_encode(json_encode([
        'iss'   => $key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/identitytoolkit https://www.googleapis.com/auth/firebase',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ])), '+/', '-_'), '=');
    openssl_sign($header . '.' . $payload, $sig, $key['private_key'], 'sha256WithRSAEncryption');
    $sig = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $header . '.' . $payload . '.' . $sig,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $resp['access_token'] ?? '';
}

function firebase_uid_for_email(string $token, string $email): string {
    $ch = curl_init('https://identitytoolkit.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/accounts:lookup');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['email' => [$email]]),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $resp['users'][0]['localId'] ?? '';
}

function firebase_set_claims(string $token, string $uid, array $claims): bool {
    $ch = curl_init('https://identitytoolkit.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/accounts:update');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['localId' => $uid, 'customAttributes' => json_encode($claims)]),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return isset($resp['localId']);
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
