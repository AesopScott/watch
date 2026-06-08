const fs = require('fs');

const portal = fs.readFileSync('portal/index.php', 'utf8');
const failures = [];

if (portal.includes('get_upcoming_sessions_with_zoom(10)')) {
  failures.push('portal/index.php must not cap upcoming sessions at 10');
}

if (!portal.includes('get_upcoming_sessions_with_zoom(1000)')) {
  failures.push('portal/index.php must load all visible upcoming sessions for calendar navigation');
}

if (!portal.includes('portal_months(3)')) {
  failures.push('portal/index.php must expose current and future month panels');
}

for (const marker of ['portal-month-tab', 'portal-month-panel', 'portal-cal-grid', 'data-month-panel']) {
  if (!portal.includes(marker)) failures.push(`portal/index.php is missing ${marker}`);
}

if (!portal.includes('/api/calendar.php?id=') || !portal.includes('/api/calendar.php?all=1')) {
  failures.push('portal calendar must keep individual and all-session calendar downloads');
}

if (!portal.includes('/api/join-session.php?id=')) {
  failures.push('portal calendar must keep gated join links');
}

if (failures.length) {
  console.error('Portal month calendar invariant failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('Portal month calendar exposes all sessions and future months');
