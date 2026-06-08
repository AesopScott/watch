const { spawnSync } = require('child_process');

const php = String.raw`
require 'lib/sessions.php';

$entries = [];
foreach (get_upcoming_sessions_with_zoom(1000) as $session) {
    $entries[] = ['kind' => 'session', 'id' => $session['id'] ?? '', 'date' => $session['date'] ?? ''];
}
foreach (get_recordings(1000) as $recording) {
    $entries[] = ['kind' => 'recording', 'id' => $recording['id'] ?? '', 'date' => $recording['date'] ?? ''];
}

echo json_encode(array_map(function ($entry) {
    $entry['formatted'] = format_session_date($entry['date']);
    return $entry;
}, $entries), JSON_PRETTY_PRINT);
`;

const result = spawnSync('php', ['-r', php], { encoding: 'utf8' });

if (result.status !== 0) {
  process.stderr.write(result.stderr || result.stdout);
  process.exit(result.status || 1);
}

const entries = JSON.parse(result.stdout);
const failures = entries.filter((entry) => {
  if (!String(entry.date).includes(':')) return false;
  return !/\b(?:EDT|EST)\b/.test(entry.formatted) || !/\bUTC\b/.test(entry.formatted);
});

if (failures.length) {
  console.error('Portal entries must show both Eastern time and UTC:');
  for (const entry of failures) {
    console.error(`- ${entry.kind} ${entry.id || '(no id)'}: ${entry.formatted}`);
  }
  process.exit(1);
}

console.log(`Portal time display OK for ${entries.length} entries`);
