const { spawnSync } = require('child_process');

const php = String.raw`
require 'lib/sessions.php';
require 'lib/calendar.php';

$sessions = get_upcoming_sessions_with_zoom(1000);
echo json_encode([
    'count' => count($sessions),
    'all' => calendar_build_ics($sessions),
    'single' => array_map(function ($session) {
        return [
            'id' => $session['id'] ?? '',
            'ics' => calendar_build_ics([$session]),
        ];
    }, $sessions),
], JSON_PRETTY_PRINT);
`;

const result = spawnSync('php', ['-r', php], { encoding: 'utf8' });

if (result.status !== 0) {
  process.stderr.write(result.stderr || result.stdout);
  process.exit(result.status || 1);
}

const payload = JSON.parse(result.stdout);

function fail(message) {
  console.error(message);
  process.exit(1);
}

function assertCalendar(ics, label, expectedEvents) {
  const eventCount = (ics.match(/BEGIN:VEVENT/g) || []).length;
  if (!ics.includes('BEGIN:VCALENDAR')) fail(`${label} is missing VCALENDAR`);
  if (!ics.includes('END:VCALENDAR')) fail(`${label} is missing VCALENDAR end`);
  if (eventCount !== expectedEvents) fail(`${label} has ${eventCount} events, expected ${expectedEvents}`);
  if (!ics.includes('BEGIN:VALARM')) fail(`${label} is missing a reminder alarm`);
  if (!ics.includes('TRIGGER:-PT15M')) fail(`${label} is missing the 15-minute reminder`);
  if (!ics.includes('/api/join-session.php?id=')) fail(`${label} is missing the gated join URL`);
}

if (payload.count < 1) {
  fail('Expected at least one visible upcoming session for calendar export');
}

assertCalendar(payload.all, 'All-sessions calendar', payload.count);

for (const entry of payload.single) {
  assertCalendar(entry.ics, `Session calendar ${entry.id}`, 1);
}

console.log(`Calendar exports OK for ${payload.count} sessions`);
