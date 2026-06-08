<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sessions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_authenticated'])) {
    header('Location: /admin/');
    exit;
}

$session = find_session_by_id('pro-sess');
if (!$session) {
    start_error('The shared Pro session is missing.');
}

$meeting_id = zoom_meeting_id((string) ($session['zoom_url'] ?? ''));
if ($meeting_id === '') {
    start_error('The shared Pro session does not have a Zoom meeting URL.');
}

$token = zoom_access_token();
$meeting = zoom_api_get('/v2/meetings/' . rawurlencode($meeting_id), $token);
$start_url = trim((string) ($meeting['start_url'] ?? ''));

if ($start_url === '') {
    start_error('Zoom did not return an instructor start URL.');
}

header('Location: ' . $start_url);
exit;

function zoom_meeting_id(string $zoom_url): string {
    if (preg_match('~/j/([0-9]+)~', $zoom_url, $match)) {
        return $match[1];
    }
    return '';
}

function zoom_config(string $key): string {
    if (defined($key)) {
        return trim((string) constant($key));
    }

    $value = getenv($key);
    if ($value !== false && trim($value) !== '') {
        return trim($value);
    }

    static $env = null;
    if ($env === null) {
        $env = [];
        $paths = [
            __DIR__ . '/../config/.env',
            __DIR__ . '/../.env',
        ];

        foreach ($paths as $path) {
            if (!is_readable($path)) continue;
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$name, $raw] = explode('=', $line, 2);
                $env[trim($name)] = trim($raw);
            }
        }
    }

    return trim((string) ($env[$key] ?? ''));
}

function zoom_access_token(): string {
    $client_id = zoom_config('ZOOM_CLIENT_ID');
    $client_secret = zoom_config('ZOOM_CLIENT_SECRET');
    $account_id = zoom_config('ZOOM_ACCOUNT_ID');

    if ($client_id === '' || $client_secret === '' || $account_id === '') {
        start_error('Zoom API credentials are not configured on the server.');
    }

    $url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . rawurlencode($account_id);
    $response = zoom_curl($url, [
        'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
    ], 'POST');

    $token = trim((string) ($response['access_token'] ?? ''));
    if ($token === '') {
        start_error('Zoom token request failed.');
    }

    return $token;
}

function zoom_api_get(string $path, string $token): array {
    return zoom_curl('https://api.zoom.us' . $path, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ], 'GET');
}

function zoom_curl(string $url, array $headers, string $method): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300) {
        start_error('Zoom API request failed' . ($error ? ': ' . $error : '.'));
    }

    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        start_error('Zoom returned an unreadable response.');
    }

    return $data;
}

function start_error(string $message): void {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Start Pro Session - Watch Me Build AI</title>';
    echo '<link rel="stylesheet" href="/assets/css/main.css"></head><body>';
    echo '<nav class="nav"><div class="nav-inner"><a href="/admin/" class="nav-logo">Watch Me Build AI</a></div></nav>';
    echo '<div class="container" style="max-width:540px;padding-top:80px;text-align:center">';
    echo '<h2 style="margin-bottom:16px">Could not start Pro session</h2>';
    echo '<p style="color:var(--text-muted);line-height:1.7">' . htmlspecialchars($message) . '</p>';
    echo '<p style="margin-top:28px"><a class="btn btn-primary" href="/admin/">Back to Admin</a></p>';
    echo '</div></body></html>';
    exit;
}
