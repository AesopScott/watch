<?php
declare(strict_types=1);

function calendar_base_url(): string {
    return 'https://watchmebuildai.com';
}

function calendar_session_url(string $session_id): string {
    return calendar_base_url() . '/api/join-session.php?id=' . rawurlencode($session_id);
}

function calendar_session_join_url(array $session): string {
    return calendar_base_url() . session_join_path($session);
}

function calendar_escape_text(string $value): string {
    $value = str_replace("\\", "\\\\", $value);
    $value = str_replace(["\r\n", "\r", "\n"], "\\n", $value);
    return str_replace([",", ";"], ["\\,", "\\;"], $value);
}

function calendar_fold_line(string $line): array {
    $folded = [];
    while (strlen($line) > 74) {
        $folded[] = substr($line, 0, 74);
        $line = ' ' . substr($line, 74);
    }
    $folded[] = $line;
    return $folded;
}

function calendar_add_line(array &$lines, string $name, string $value): void {
    foreach (calendar_fold_line($name . ':' . $value) as $line) {
        $lines[] = $line;
    }
}

function calendar_dt_utc(int $ts): string {
    return gmdate('Ymd\THis\Z', $ts);
}

function calendar_event_lines(array $session): array {
    $ts = strtotime($session['date'] ?? '');
    if (!$ts) return [];

    $id = (string) ($session['id'] ?? ('session-' . $ts));
    $duration = max(1, (int) ($session['duration'] ?? 60));
    $join_url = calendar_session_join_url($session);
    $summary = trim((string) ($session['title'] ?? 'Watch Me Build AI Session'));
    $description = trim((string) ($session['description'] ?? ''));
    $description = trim($description . "\n\nJoin session: " . $join_url);

    $lines = ['BEGIN:VEVENT'];
    calendar_add_line($lines, 'UID', calendar_escape_text($id . '@watchmebuildai.com'));
    calendar_add_line($lines, 'DTSTAMP', calendar_dt_utc(time()));
    calendar_add_line($lines, 'DTSTART', calendar_dt_utc($ts));
    calendar_add_line($lines, 'DTEND', calendar_dt_utc($ts + ($duration * 60)));
    calendar_add_line($lines, 'SUMMARY', calendar_escape_text($summary));
    calendar_add_line($lines, 'DESCRIPTION', calendar_escape_text($description));
    calendar_add_line($lines, 'LOCATION', calendar_escape_text('Online'));
    calendar_add_line($lines, 'URL', $join_url);
    $lines[] = 'BEGIN:VALARM';
    calendar_add_line($lines, 'TRIGGER', '-PT15M');
    calendar_add_line($lines, 'ACTION', 'DISPLAY');
    calendar_add_line($lines, 'DESCRIPTION', calendar_escape_text('Watch Me Build AI starts in 15 minutes'));
    $lines[] = 'END:VALARM';
    $lines[] = 'END:VEVENT';

    return $lines;
}

function calendar_build_ics(array $sessions): string {
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Watch Me Build AI//Sessions//EN',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
    ];

    foreach ($sessions as $session) {
        array_push($lines, ...calendar_event_lines($session));
    }

    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines) . "\r\n";
}

function calendar_filename(string $label): string {
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $label));
    return trim($slug, '-') . '.ics';
}
