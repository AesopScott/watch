const fs = require('fs');

const sessions = JSON.parse(fs.readFileSync('data/sessions.json', 'utf8')).sessions || [];
const critical = sessions.find((session) => session.id === 'pro-sess');

function fail(message) {
  console.error(message);
  process.exit(1);
}

if (!critical) {
  fail('Missing critical direct-join session: pro-sess');
}

if (!/^https:\/\/[^/]*zoom\.us\/j\//.test(critical.zoom_url || '')) {
  fail('pro-sess must map to a Zoom meeting URL');
}

if (critical.hidden !== true) {
  fail('pro-sess must stay hidden from schedule and portal listings');
}

const visiblePro = sessions.filter((session) => session.type === 'pro' && !session.hidden);
const missingSharedJoin = visiblePro.filter((session) => session.join_session_id !== 'pro-sess');

if (missingSharedJoin.length) {
  fail(`Visible Pro sessions must point at pro-sess: ${missingSharedJoin.map((session) => session.id).join(', ')}`);
}

console.log('Critical direct-join sessions OK');
