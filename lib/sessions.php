<?php
// Session and recording data helpers

function sessions_file(): string {
    return __DIR__ . '/../data/sessions.json';
}

function recordings_file(): string {
    return __DIR__ . '/../data/recordings.json';
}

function get_all_sessions(): array {
    $data = json_decode(file_get_contents(sessions_file()), true);
    return $data['sessions'] ?? [];
}

function find_session_by_id(string $session_id): ?array {
    foreach (get_all_sessions() as $session) {
        if (($session['id'] ?? '') === $session_id) return $session;
    }
    return null;
}

function session_join_id(array $session): string {
    return (string) ($session['join_session_id'] ?? $session['id'] ?? '');
}

function session_zoom_url(array $session): string {
    $join_id = session_join_id($session);
    if ($join_id !== '' && $join_id !== ($session['id'] ?? '')) {
        $target = find_session_by_id($join_id);
        return trim((string) ($target['zoom_url'] ?? ''));
    }

    return trim((string) ($session['zoom_url'] ?? ''));
}

function session_join_path(array $session): string {
    return '/api/join-session.php?id=' . urlencode((string) ($session['id'] ?? ''));
}

function get_upcoming_sessions(int $limit = 10): array {
    $sessions = get_all_sessions();
    $now      = time();
    $upcoming = array_filter($sessions, fn($s) => empty($s['hidden']) && strtotime($s['date']) >= strtotime('today'));
    usort($upcoming, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));
    return array_slice(array_values($upcoming), 0, $limit);
}

// Returns sessions with Zoom links — for subscriber portal only
function get_upcoming_sessions_with_zoom(int $limit = 10): array {
    return get_upcoming_sessions($limit);   // sessions.json stores zoom_url; public view strips it
}

function get_recordings(int $limit = 50): array {
    $data = json_decode(file_get_contents(recordings_file()), true);
    $recs = $data['recordings'] ?? [];
    usort($recs, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
    return array_slice($recs, 0, $limit);
}

function format_session_date(string $date): string {
    $ts = strtotime($date);
    if (!$ts) return $date;
    $eastern_tz = new DateTimeZone('America/New_York');
    $utc_tz     = new DateTimeZone('UTC');
    $eastern    = (new DateTimeImmutable('@' . $ts))->setTimezone($eastern_tz);
    $utc        = (new DateTimeImmutable('@' . $ts))->setTimezone($utc_tz);
    $today      = new DateTimeImmutable('today', $eastern_tz);
    $prefix     = $eastern->format('D, M j');

    if ($eastern >= $today && $eastern < $today->modify('+1 day')) {
        $prefix = 'Today';
    } elseif ($eastern >= $today->modify('+1 day') && $eastern < $today->modify('+2 days')) {
        $prefix = 'Tomorrow';
    }

    if (strpos($date, ':') === false) return $prefix;
    return $prefix . ' — ' . $eastern->format('g:ia T') . ' / ' . $utc->format('g:ia T');
}
