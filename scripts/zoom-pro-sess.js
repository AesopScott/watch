const fs = require('fs');

function loadEnv(path = '.env') {
  const env = {};
  if (!fs.existsSync(path)) return env;
  const text = fs.readFileSync(path, 'utf8').replace(/^\uFEFF/, '');
  for (const line of text.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const index = trimmed.indexOf('=');
    if (index === -1) continue;
    env[trimmed.slice(0, index)] = trimmed.slice(index + 1);
  }
  return env;
}

async function zoomAccessToken(env) {
  const required = ['ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_ACCOUNT_ID'];
  for (const key of required) {
    if (!env[key]) throw new Error(`Missing ${key}`);
  }

  const credentials = Buffer.from(`${env.ZOOM_CLIENT_ID}:${env.ZOOM_CLIENT_SECRET}`).toString('base64');
  const url = `https://zoom.us/oauth/token?grant_type=account_credentials&account_id=${encodeURIComponent(env.ZOOM_ACCOUNT_ID)}`;
  const response = await fetch(url, {
    method: 'POST',
    headers: { Authorization: `Basic ${credentials}` },
  });
  const data = await response.json();
  if (!response.ok) throw new Error(`Zoom token failed: ${response.status} ${JSON.stringify(data)}`);
  return data.access_token;
}

async function createRecurringMeeting(token) {
  const response = await fetch('https://api.zoom.us/v2/users/me/meetings', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      topic: 'Watch Me Build AI - Pro Session',
      type: 3,
      agenda: 'Shared Zoom room for Watch Me Build AI Pro sessions.',
      duration: 60,
      timezone: 'America/Denver',
      settings: {
        join_before_host: false,
        waiting_room: true,
        approval_type: 2,
        audio: 'both',
        auto_recording: 'none',
      },
    }),
  });
  const data = await response.json();
  if (!response.ok) throw new Error(`Zoom meeting create failed: ${response.status} ${JSON.stringify(data)}`);
  return data;
}

function updateProSess(joinUrl) {
  const path = 'data/sessions.json';
  const data = JSON.parse(fs.readFileSync(path, 'utf8'));
  const session = data.sessions.find((entry) => entry.id === 'pro-sess');
  if (!session) throw new Error('Missing pro-sess in data/sessions.json');
  session.zoom_url = joinUrl;
  fs.writeFileSync(path, JSON.stringify(data, null, 2) + '\n', 'utf8');
}

async function main() {
  const dryRun = process.argv.includes('--dry-run');
  const env = loadEnv();

  if (dryRun) {
    console.log('Zoom dry run: would create one recurring no-fixed-time meeting for pro-sess.');
    console.log('Required env present:', ['ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_ACCOUNT_ID'].every((key) => Boolean(env[key])));
    return;
  }

  const token = await zoomAccessToken(env);
  const meeting = await createRecurringMeeting(token);
  if (!meeting.join_url) throw new Error('Zoom response did not include join_url');
  updateProSess(meeting.join_url);
  console.log(JSON.stringify({ id: meeting.id, join_url: meeting.join_url }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
