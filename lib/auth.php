<?php
// Shared auth helpers — session management, magic link send/verify, subscriber check

define('TOKEN_TTL_SECONDS', 900);  // 15-minute magic link window

function subscribers_file(): string {
    return __DIR__ . '/../data/subscribers.json';
}

function tokens_file(): string {
    return __DIR__ . '/../data/tokens.json';
}

function read_json_file(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function write_json_file(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Returns true if the current PHP session belongs to an active subscriber.
function is_active_subscriber(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $email = $_SESSION['subscriber_email'] ?? '';
    if (!$email) return false;
    return check_subscriber_status($email) === 'active';
}

// Returns 'active', 'cancelled' (within period), 'inactive', or 'not_found'.
function check_subscriber_status(string $email): string {
    $store = read_json_file(subscribers_file());
    $sub   = $store['subscribers'][$email] ?? null;
    if (!$sub) return 'not_found';

    $status = $sub['status'] ?? 'inactive';

    // 'cancelled' means end-of-period — check if period has passed
    if ($status === 'cancelled') {
        $ends_at = $sub['ends_at'] ?? null;
        if ($ends_at && strtotime($ends_at) > time()) {
            return 'active';  // Still within paid period
        }
        return 'inactive';
    }

    return $status === 'active' ? 'active' : 'inactive';
}

// Returns the raw subscriber record for $email, or [] on missing/error.
function get_subscriber_data(string $email): array {
    $store = read_json_file(subscribers_file());
    return $store['subscribers'][$email] ?? [];
}

// Records a session claim for the given ISO week key (e.g. "2026-W23").
// Fail-open: returns false without throwing on any error.
function record_session_claim(string $email, string $week_key, string $session_id): bool {
    try {
        $store = read_json_file(subscribers_file());
        if (!isset($store['subscribers'][$email])) return false;
        $claims = $store['subscribers'][$email]['weekly_claims'][$week_key] ?? [];
        if (!in_array($session_id, $claims, true)) {
            $claims[] = $session_id;
        }
        $store['subscribers'][$email]['weekly_claims'][$week_key] = $claims;
        write_json_file(subscribers_file(), $store);
        return true;
    } catch (Throwable) {
        return false;
    }
}

// Sends a magic link email via Brevo. Returns true, 'not_subscriber', or 'error'.
function send_magic_link(string $email) {
    $status = check_subscriber_status($email);
    if ($status !== 'active') return 'not_subscriber';

    $token     = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires   = time() + TOKEN_TTL_SECONDS;

    // Store hashed token
    $store = read_json_file(tokens_file());
    if (!isset($store['tokens'])) $store['tokens'] = [];
    $store['tokens'][$email] = [
        'hash'    => $token_hash,
        'expires' => $expires,
    ];
    write_json_file(tokens_file(), $store);

    $link = SITE_URL . '/auth.php?email=' . urlencode($email) . '&token=' . urlencode($token);

    return brevo_send_magic_link_email($email, $link);
}

// Verifies a magic link token. Returns true, 'expired', 'invalid', or 'not_subscriber'.
function verify_magic_link(string $email, string $token) {
    $store  = read_json_file(tokens_file());
    $record = $store['tokens'][$email] ?? null;

    if (!$record) return 'invalid';

    if (time() > $record['expires']) {
        // Clean up expired token
        unset($store['tokens'][$email]);
        write_json_file(tokens_file(), $store);
        return 'expired';
    }

    if (!hash_equals($record['hash'], hash('sha256', $token))) {
        return 'invalid';
    }

    // Consume token — one-time use
    unset($store['tokens'][$email]);
    write_json_file(tokens_file(), $store);

    // Confirm still an active subscriber at login time
    if (check_subscriber_status($email) !== 'active') return 'not_subscriber';

    return true;
}

// Sends magic link email via Brevo transactional API.
function brevo_send_magic_link_email(string $email, string $link) {
    $payload = [
        'to'      => [['email' => $email]],
        'sender'  => ['email' => BREVO_SENDER_EMAIL, 'name' => BREVO_SENDER_NAME],
        'subject' => 'Your Watch Me Build AI login link',
        'htmlContent' => '
            <p>Click the link below to log in to Watch Me Build AI. It expires in 15 minutes.</p>
            <p><a href="' . htmlspecialchars($link) . '" style="font-size:18px;font-weight:bold;">Log In Now →</a></p>
            <p style="color:#888;font-size:12px;">If you didn\'t request this, you can ignore this email.</p>
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
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($status >= 200 && $status < 300) ? true : 'error';
}
