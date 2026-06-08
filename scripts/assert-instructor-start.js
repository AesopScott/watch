const fs = require('fs');

const endpoint = fs.readFileSync('api/start-pro-session.php', 'utf8');
const admin = fs.readFileSync('admin/index.php', 'utf8');
const failures = [];

if (!endpoint.includes("empty($_SESSION['admin_authenticated'])")) {
  failures.push('instructor start endpoint must require admin authentication');
}

for (const marker of ['ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_ACCOUNT_ID', 'start_url', "find_session_by_id('pro-sess')"]) {
  if (!endpoint.includes(marker)) failures.push(`instructor start endpoint is missing ${marker}`);
}

if (!admin.includes('/api/start-pro-session.php') || !admin.includes('Start Pro Session as Instructor')) {
  failures.push('admin page must expose the instructor start button');
}

if (failures.length) {
  console.error('Instructor start invariant failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('Instructor start endpoint is wired');
