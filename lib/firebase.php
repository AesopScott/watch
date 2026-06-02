<?php
// Firebase ID token verification — pure PHP, no Composer required.
// Fetches Firebase's public certs from Google and validates RS256 JWT signatures.

define('FIREBASE_CERTS_URL',   'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
define('FIREBASE_CERTS_CACHE', __DIR__ . '/../data/firebase_certs.json');

function _fb_b64url_decode(string $data): string {
    $rem = strlen($data) % 4;
    if ($rem) $data .= str_repeat('=', 4 - $rem);
    return base64_decode(strtr($data, '-_', '+/'));
}

function _fb_get_public_keys(): array {
    if (file_exists(FIREBASE_CERTS_CACHE)) {
        $cached = json_decode(file_get_contents(FIREBASE_CERTS_CACHE), true);
        if ($cached && ($cached['expires'] ?? 0) > time()) {
            return $cached['keys'] ?? [];
        }
    }

    $ch = curl_init(FIREBASE_CERTS_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response    = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if (!$response) return [];

    $raw_headers = substr($response, 0, $header_size);
    $body        = substr($response, $header_size);
    $keys        = json_decode($body, true);
    if (!is_array($keys)) return [];

    $max_age = 3600;
    if (preg_match('/max-age=(\d+)/i', $raw_headers, $m)) {
        $max_age = (int) $m[1];
    }

    @file_put_contents(FIREBASE_CERTS_CACHE, json_encode([
        'expires' => time() + $max_age,
        'keys'    => $keys,
    ]), LOCK_EX);

    return $keys;
}

// Verifies a Firebase ID token. Returns decoded payload array on success, false on failure.
function verify_firebase_id_token(string $id_token) {
    $parts = explode('.', $id_token);
    if (count($parts) !== 3) return false;

    [$header_b64, $payload_b64, $sig_b64] = $parts;

    $header    = json_decode(_fb_b64url_decode($header_b64), true);
    $payload   = json_decode(_fb_b64url_decode($payload_b64), true);
    $signature = _fb_b64url_decode($sig_b64);

    if (!$header || !$payload || !$signature) return false;
    if (($header['alg'] ?? '') !== 'RS256')   return false;

    $kid  = $header['kid'] ?? '';
    $keys = _fb_get_public_keys();
    if (!isset($keys[$kid])) return false;

    $pub = openssl_pkey_get_public($keys[$kid]);
    if (!$pub) return false;

    $verified = openssl_verify(
        $header_b64 . '.' . $payload_b64,
        $signature,
        $pub,
        OPENSSL_ALGO_SHA256
    );
    if ($verified !== 1) return false;

    $now = time();
    $pid = FIREBASE_PROJECT_ID;

    if (($payload['iss'] ?? '') !== "https://securetoken.google.com/$pid") return false;
    if (($payload['aud'] ?? '') !== $pid)  return false;
    if (($payload['exp'] ?? 0)  <= $now)   return false;
    if (($payload['iat'] ?? 0)  > $now + 300) return false;
    if (empty($payload['sub']))             return false;

    return $payload;
}
