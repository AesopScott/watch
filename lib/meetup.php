<?php
// Fetches upcoming Meetup events from the Advanced AI Concepts pro network
// via the mojoaistudio RSVP endpoint, deduplicates across city groups,
// and returns a clean list for the public schedule.

define('MEETUP_SOURCE_URL',  'https://mojoaistudio.com/api/meetup-rsvp-count.php?debug=true');
define('MEETUP_CACHE_FILE',  __DIR__ . '/../data/meetup_cache.json');
define('MEETUP_CACHE_TTL',   3600); // 1 hour

function get_meetup_events(int $limit = 20): array {
    // Serve from cache when fresh
    if (file_exists(MEETUP_CACHE_FILE)) {
        $cached = json_decode(file_get_contents(MEETUP_CACHE_FILE), true);
        if ($cached && ($cached['fetched_at'] ?? 0) > time() - MEETUP_CACHE_TTL) {
            return array_slice($cached['events'] ?? [], 0, $limit);
        }
    }

    $ch = curl_init(MEETUP_SOURCE_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$body) return _meetup_stale_cache($limit);

    $data = json_decode($body, true);
    if (!($data['ok'] ?? false) || empty($data['breakdown'])) return _meetup_stale_cache($limit);

    $now    = time();
    $unique = [];

    foreach ($data['breakdown'] as $event) {
        $ts = strtotime($event['date'] ?? '');
        if (!$ts || $ts < $now) continue;

        // Normalize title — strip "Global - " prefix so duplicates collapse
        $title = preg_replace('/^Global\s*[-–]\s*/i', '', trim($event['title'] ?? ''));
        if (!$title) continue;

        // Deduplicate key: same title + same ISO week + same half-day (AM vs PM UTC).
        // Events cross-posted across timezones land on different UTC calendar days
        // (e.g. Friday 6 PM MT = UTC Saturday), so we bucket by week+half-day
        // to collapse all instances of the same session into one entry.
        $week     = gmdate('oW', $ts);           // ISO year + week number
        $half_day = (int) gmdate('G', $ts) >= 12 ? 'pm' : 'am';
        $key      = strtolower($title) . '|' . $week . '|' . $half_day;

        if (!isset($unique[$key])) {
            $unique[$key] = [
                'title'       => $title,
                'date'        => $event['date'],
                'ts'          => $ts,
                'rsvps'       => (int) ($event['rsvps'] ?? 0),
                'group'       => $event['group'] ?? '',
                'id'          => $event['id'] ?? '',
                'description' => $event['description'] ?? '',
                'eventUrl'    => $event['eventUrl'] ?? '',
            ];
        } else {
            // Prefer root group; otherwise prefer highest RSVP count
            $existing_group = $unique[$key]['group'];
            $new_group      = $event['group'] ?? '';
            $prefer_new     = ($new_group === 'advanced-ai-concepts')
                || ($existing_group !== 'advanced-ai-concepts' && (int)($event['rsvps'] ?? 0) > $unique[$key]['rsvps']);
            if ($prefer_new) {
                $unique[$key]['group']       = $new_group;
                $unique[$key]['id']          = $event['id'] ?? '';
                $unique[$key]['rsvps']       = (int) ($event['rsvps'] ?? 0);
                $unique[$key]['description'] = $event['description'] ?? $unique[$key]['description'];
                $unique[$key]['eventUrl']    = $event['eventUrl'] ?? $unique[$key]['eventUrl'];
            }
        }
    }

    // Sort ascending by timestamp
    usort($unique, fn($a, $b) => $a['ts'] <=> $b['ts']);

    // Add Meetup URL
    foreach ($unique as &$e) {
        $e['url'] = 'https://www.meetup.com/' . $e['group'] . '/events/' . $e['id'] . '/';
    }
    unset($e);

    // Cache result
    @file_put_contents(MEETUP_CACHE_FILE, json_encode([
        'fetched_at' => $now,
        'events'     => $unique,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    return array_slice($unique, 0, $limit);
}

function _meetup_stale_cache(int $limit): array {
    if (!file_exists(MEETUP_CACHE_FILE)) return [];
    $cached = json_decode(file_get_contents(MEETUP_CACHE_FILE), true);
    return array_slice($cached['events'] ?? [], 0, $limit);
}

function format_meetup_date(string $date_str): string {
    $ts = strtotime($date_str);
    if (!$ts) return $date_str;
    return date('D, M j, Y \a\t g:i A T', $ts);
}
