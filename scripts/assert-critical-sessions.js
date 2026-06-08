const fs = require('fs');

const sessions = JSON.parse(fs.readFileSync('data/sessions.json', 'utf8')).sessions || [];
const critical = sessions.find((session) => session.id === 'pro-jun9-3pm');
const expectedZoom = 'https://us06web.zoom.us/j/83979200637?pwd=sHLaomkoOAb3yfsFCqBiecwwN3J83C.1';

function fail(message) {
  console.error(message);
  process.exit(1);
}

if (!critical) {
  fail('Missing critical direct-join session: pro-jun9-3pm');
}

if (critical.zoom_url !== expectedZoom) {
  fail('pro-jun9-3pm does not map to the expected Zoom URL');
}

if (critical.hidden !== true) {
  fail('pro-jun9-3pm must stay hidden from schedule and portal listings');
}

console.log('Critical direct-join sessions OK');
