const fs = require('fs');

const TOKEN_PATH = 'data/meetup-oauth-token.local.json';
const SOURCE_GROUP = 'advanced-ai-concepts';
const TITLE = 'Pro Session - Watch Me Build AI.com';

function loadToken() {
  if (!fs.existsSync(TOKEN_PATH)) throw new Error(`Missing ${TOKEN_PATH}`);
  return JSON.parse(fs.readFileSync(TOKEN_PATH, 'utf8'));
}

function proSessionSlots() {
  const sessions = JSON.parse(fs.readFileSync('data/sessions.json', 'utf8')).sessions || [];
  return sessions
    .filter((session) => session.type === 'pro' && !session.hidden && Date.parse(session.date) > Date.now())
    .map((session) => {
      const local = new Date(Date.parse(session.date) - 6 * 60 * 60 * 1000);
      const pad = (value) => String(value).padStart(2, '0');
      return `${local.getUTCFullYear()}-${pad(local.getUTCMonth() + 1)}-${pad(local.getUTCDate())}T${pad(local.getUTCHours())}:${pad(local.getUTCMinutes())}`;
    })
    .sort();
}

async function gql(token, query, variables = {}) {
  const response = await fetch('https://api.meetup.com/gql-ext', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token.access_token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ query, variables }),
  });
  const data = await response.json();
  if (data.errors) throw new Error(JSON.stringify(data.errors));
  return data.data;
}

async function sourceGroupEvents(token) {
  const data = await gql(token, `query($urlname:String!){
    groupByUrlname(urlname:$urlname){
      events(first:100,status:ACTIVE,sort:ASC){
        edges{
          node{
            id
            title
            dateTime
            eventUrl
            networkEvent{ id groupCount status eventTime }
          }
        }
      }
    }
  }`, { urlname: SOURCE_GROUP });

  return ((data.groupByUrlname && data.groupByUrlname.events.edges) || [])
    .map((edge) => edge.node)
    .filter((event) => event.title === TITLE);
}

function slotOf(event) {
  return event.dateTime.slice(0, 16);
}

async function main() {
  const token = loadToken();
  const expected = proSessionSlots();
  const events = await sourceGroupEvents(token);
  const networkBySlot = new Map();
  const directBySlot = new Map();

  for (const event of events) {
    const slot = slotOf(event);
    const bucket = event.networkEvent ? networkBySlot : directBySlot;
    if (!bucket.has(slot)) bucket.set(slot, []);
    bucket.get(slot).push(event);
  }

  const missingNetwork = expected.filter((slot) => !networkBySlot.has(slot));
  const duplicateNetwork = [...networkBySlot.entries()]
    .filter(([, items]) => items.length > 1)
    .map(([slot, items]) => ({ slot, count: items.length, eventIds: items.map((event) => event.id) }));
  const directCopies = expected
    .filter((slot) => directBySlot.has(slot))
    .map((slot) => ({ slot, count: directBySlot.get(slot).length, eventIds: directBySlot.get(slot).map((event) => event.id) }));

  console.log(JSON.stringify({
    expected: expected.length,
    networkSlots: networkBySlot.size,
    missingNetwork,
    duplicateNetwork,
    directCopies,
  }, null, 2));

  if (missingNetwork.length || duplicateNetwork.length) process.exit(1);
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
