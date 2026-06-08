<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/sessions.php';
require_once __DIR__ . '/../lib/calendar.php';

$sessions = [];
$filename = 'watch-me-build-ai-sessions.ics';

if (isset($_GET['all'])) {
    $sessions = get_upcoming_sessions_with_zoom(1000);
} else {
    $session_id = trim((string) ($_GET['id'] ?? ''));
    foreach (get_all_sessions() as $session) {
        if (($session['id'] ?? '') === $session_id && empty($session['hidden'])) {
            $sessions[] = $session;
            $filename = calendar_filename((string) ($session['id'] ?? 'session'));
            break;
        }
    }
}

if (!$sessions) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No calendar sessions found.';
    exit;
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo calendar_build_ics($sessions);
