<?php
require_once __DIR__ . '/../config/config.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function respond(int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['message' => $message]);
    exit;
}

function read_json(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function write_json(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function subscribers_path(): string {
    return __DIR__ . '/../data/subscribers.json';
}

// ── Signature verification ────────────────────────────────────────────────────

$raw_body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '';

$expected = 'sha256=' . hash_hmac('sha256', $raw_body, POLAR_HMAC_SECRET);
if (!hash_equals($expected, $signature)) {
    respond(401, 'Invalid signature');
}

$event = json_decode($raw_body, true);
if (!$event || !isset($event['type'], $event['data'])) {
    respond(400, 'Malformed payload');
}

$type = $event['type'];
$data = $event['data'];

// ── Route events ──────────────────────────────────────────────────────────────

$email      = strtolower(trim($data['customer']['email'] ?? ''));
$product_id = $data['product_id'] ?? ($data['product']['id'] ?? '');
$sub_id     = $data['id'] ?? '';
$ends_at    = $data['current_period_end'] ?? null;
$trial      = ($data['started_with_trial'] ?? false) === true;

if (!$email) {
    respond(400, 'No customer email in payload');
}

// Only process events for known product IDs
if ($product_id && !in_array($product_id, POLAR_PRODUCT_IDS, true)) {
    respond(200, 'Product not managed by this webhook');
}

$store    = read_json(subscribers_path());
$subs     = $store['subscribers'] ?? [];

switch ($type) {

    case 'subscription.created':
        $subs[$email] = [
            'status'         => 'active',
            'polar_sub_id'   => $sub_id,
            'polar_product'  => $product_id,
            'trial'          => $trial,
            'started_at'     => date('c'),
            'ends_at'        => $ends_at,
            'cancelled_at'   => null,
        ];
        write_json(subscribers_path(), ['subscribers' => $subs]);
        brevo_upsert_contact($email, 'active', $trial);
        brevo_send_onboarding($email);
        respond(200, 'Subscriber activated');

    case 'subscription.updated':
        if (isset($subs[$email])) {
            $subs[$email]['polar_sub_id']  = $sub_id;
            $subs[$email]['polar_product'] = $product_id;
            $subs[$email]['ends_at']       = $ends_at;
            // Reactivate if they were cancelled but renewed
            if ($subs[$email]['status'] === 'cancelled' || $subs[$email]['status'] === 'inactive') {
                $subs[$email]['status']       = 'active';
                $subs[$email]['cancelled_at'] = null;
            }
        } else {
            // Subscriber not found locally — create record
            $subs[$email] = [
                'status'        => 'active',
                'polar_sub_id'  => $sub_id,
                'polar_product' => $product_id,
                'trial'         => $trial,
                'started_at'    => date('c'),
                'ends_at'       => $ends_at,
                'cancelled_at'  => null,
            ];
        }
        write_json(subscribers_path(), ['subscribers' => $subs]);
        brevo_upsert_contact($email, 'active', false);
        respond(200, 'Subscriber updated');

    case 'subscription.canceled':
        // Access continues to end of billing period — mark cancelled but keep status active
        if (isset($subs[$email])) {
            $subs[$email]['status']       = 'cancelled';
            $subs[$email]['cancelled_at'] = date('c');
            $subs[$email]['ends_at']      = $ends_at;
        }
        write_json(subscribers_path(), ['subscribers' => $subs]);
        brevo_upsert_contact($email, 'cancelled', false);
        respond(200, 'Subscription cancellation recorded');

    case 'subscription.revoked':
        // Immediate access removal
        if (isset($subs[$email])) {
            $subs[$email]['status']       = 'inactive';
            $subs[$email]['cancelled_at'] = date('c');
        }
        write_json(subscribers_path(), ['subscribers' => $subs]);
        brevo_upsert_contact($email, 'inactive', false);
        respond(200, 'Subscription revoked');

    default:
        respond(200, 'Event type not handled');
}

// ── Brevo integration ─────────────────────────────────────────────────────────

function brevo_upsert_contact(string $email, string $status, bool $trial): void {
    $payload = [
        'email'      => $email,
        'listIds'    => [BREVO_LIST_ID],
        'attributes' => [
            'SUBSCRIPTION_STATUS' => $status,
            'IS_TRIAL'            => $trial ? 'yes' : 'no',
        ],
        'updateEnabled' => true,
    ];
    brevo_post('https://api.brevo.com/v3/contacts', $payload);
}

function brevo_send_onboarding(string $email): void {
    // Sends the onboarding transactional email template
    // Set BREVO_ONBOARDING_TEMPLATE_ID in config.php once the template is created in Brevo
    if (!defined('BREVO_ONBOARDING_TEMPLATE_ID')) return;
    $payload = [
        'to'         => [['email' => $email]],
        'templateId' => BREVO_ONBOARDING_TEMPLATE_ID,
        'params'     => ['SITE_URL' => SITE_URL],
    ];
    brevo_post('https://api.brevo.com/v3/smtp/email', $payload);
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
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
