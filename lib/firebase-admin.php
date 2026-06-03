<?php
// Firebase Admin REST API helpers — server-to-server, requires service account key.
// FIREBASE_ADMIN_KEY_PATH and FIREBASE_PROJECT_ID must be defined in config.php.

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

// Generates a password reset link via the Admin API without sending the email.
// Returns the oobCode on success, or '' on failure (e.g. email not found).
function firebase_generate_password_reset_oob(string $token, string $email): string {
    $ch = curl_init('https://identitytoolkit.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/accounts:sendOobCode');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'requestType'   => 'PASSWORD_RESET',
            'email'         => $email,
            'returnOobLink' => true,
        ]),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $oob_link = $resp['oobLink'] ?? '';
    if (!$oob_link) return '';

    parse_str(parse_url($oob_link, PHP_URL_QUERY), $params);
    return $params['oobCode'] ?? '';
}
