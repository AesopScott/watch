const fs = require('fs');
const https = require('https');

function fetchJson(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (response) => {
      let body = '';
      response.on('data', (chunk) => { body += chunk; });
      response.on('end', () => {
        try {
          resolve(JSON.parse(body));
        } catch (error) {
          reject(error);
        }
      });
    }).on('error', reject);
  });
}

function visibleProSessions() {
  const sessions = JSON.parse(fs.readFileSync('data/sessions.json', 'utf8')).sessions || [];
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return sessions.filter((session) => {
    return session.type === 'pro'
      && !session.hidden
      && Date.parse(session.date || '') >= today.getTime();
  });
}

async function main() {
  const feed = await fetchJson('https://mojoaistudio.com/api/meetup-rsvp-count.php?debug=true');
  const groups = [...new Set((feed.breakdown || []).map((event) => event.group).filter(Boolean))].sort();
  const source = (feed.breakdown || []).find((event) => {
    const ts = Date.parse(event.date || '');
    return event.title && ts && new Date(ts).toISOString().startsWith('2026-06-09T21:00:00');
  }) || null;
  const sessions = visibleProSessions();
  const joinUrl = 'https://watchmebuildai.com/api/join-session.php?id=pro-sess';

  console.log(JSON.stringify({
    sourceEvent: source ? {
      id: source.id,
      title: source.title,
      group: source.group,
      eventUrl: source.eventUrl,
    } : null,
    groups: groups.length,
    proSessions: sessions.length,
    eventsToCreate: groups.length * sessions.length,
    joinUrl,
    firstFiveGroups: groups.slice(0, 5),
    firstFiveSessions: sessions.slice(0, 5).map((session) => ({
      id: session.id,
      date: session.date,
      join_session_id: session.join_session_id,
    })),
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
