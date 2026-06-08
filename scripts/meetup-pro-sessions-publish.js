const fs = require('fs');

const TOKEN_PATH = 'data/meetup-oauth-token.local.json';
const JOIN_URL = 'https://watchmebuildai.com/api/join-session.php?id=pro-sess';
const SOURCE_GROUP = 'advanced-ai-concepts';
const SOURCE_EVENT_ID = '315079834';
const ONLINE_VENUE_ID = '26906060';
const HOST_ID = 12650379;
const TOPIC_IDS = ['488822', '90163', '16621', '1510550', '1464432'];
const TITLE = 'Pro Session - Watch Me Build AI.com';

function loadEnv(path = '.env') {
  const env = {};
  if (!fs.existsSync(path)) return env;
  const text = fs.readFileSync(path, 'utf8').replace(/^\uFEFF/, '');
  for (const line of text.split(/\r?\n/)) {
    const match = line.match(/^([^=#\s][^=]*)=(.*)$/);
    if (match) env[match[1].trim()] = match[2];
  }
  return env;
}

function visibleProSessions() {
  const sessions = JSON.parse(fs.readFileSync('data/sessions.json', 'utf8')).sessions || [];
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  return sessions
    .filter((session) => session.type === 'pro' && !session.hidden && Date.parse(session.date || '') >= now.getTime())
    .sort((a, b) => Date.parse(a.date) - Date.parse(b.date));
}

function toOffsetDate(dateValue, offsetHours) {
  const date = new Date(dateValue);
  const shifted = new Date(date.getTime() + offsetHours * 60 * 60 * 1000);
  const pad = (value) => String(value).padStart(2, '0');
  const sign = offsetHours < 0 ? '-' : '+';
  const abs = Math.abs(offsetHours);
  return `${shifted.getUTCFullYear()}-${pad(shifted.getUTCMonth() + 1)}-${pad(shifted.getUTCDate())}T${pad(shifted.getUTCHours())}:${pad(shifted.getUTCMinutes())}:00${sign}${pad(abs)}:00`;
}

function sourceGroupStart(session) {
  return toOffsetDate(session.date, -6);
}

function expectedInstant(session) {
  return new Date(session.date).toISOString().slice(0, 16);
}

function description() {
  return [
    'Sign up for your 7 free days of access to our private professional coaching sessions.',
    '',
    'This is our one of our paid pro sessions for subscribers of https://watchmebuildai.com.',
    '',
    'Subscribers can engage directly and watch real websites, tools, products, command centers, and orchestration engines being built in real time.',
    '',
    'No lessons, No PowerPoints. Just building, testing, and learning.',
    '',
    `Join: ${JOIN_URL}`,
    '',
    'You will be sent an AI transcript, but may invite your AI recorder as well.',
  ].join('\n');
}

function loadToken() {
  if (!fs.existsSync(TOKEN_PATH)) throw new Error(`Missing ${TOKEN_PATH}`);
  return JSON.parse(fs.readFileSync(TOKEN_PATH, 'utf8'));
}

function saveToken(token) {
  fs.writeFileSync(TOKEN_PATH, JSON.stringify({ ...token, obtained_at: new Date().toISOString() }, null, 2) + '\n', 'utf8');
}

async function refreshToken(env, token) {
  if (!token.refresh_token) return token;
  const obtained = Date.parse(token.obtained_at || 0);
  const expiresAt = obtained + ((token.expires_in || 3600) - 120) * 1000;
  if (Date.now() < expiresAt) return token;

  const body = new URLSearchParams({
    client_id: env.MEETUP_CLIENT_ID || '',
    client_secret: env.MEETUP_CLIENT_SECRET || '',
    grant_type: 'refresh_token',
    refresh_token: token.refresh_token,
  });
  const response = await fetch('https://secure.meetup.com/oauth2/access', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  });
  const data = await response.json();
  if (!response.ok) throw new Error(`Token refresh failed: ${response.status} ${JSON.stringify(data)}`);
  saveToken(data);
  return data;
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

async function existingGroupEvents(token, group, start, end) {
  const query = `query($urlname:String!){
    groupByUrlname(urlname:$urlname){
      events(first:100,status:ACTIVE,sort:ASC){
        edges{ node{ id title dateTime eventUrl howToFindUs } }
      }
    }
  }`;
  const data = await gql(token, query, { urlname: group });
  const min = Date.parse(start);
  const max = Date.parse(end);
  return ((data.groupByUrlname && data.groupByUrlname.events.edges) || [])
    .map((edge) => edge.node)
    .filter((event) => event.title === TITLE && Date.parse(event.dateTime) >= min && Date.parse(event.dateTime) <= max);
}

async function proNetworkGroups(token) {
  const groups = [];
  let after = null;
  do {
    const data = await gql(token, `query($urlname:String!,$after:String){
      groupByUrlname(urlname:$urlname){
        proNetwork{
          groupsSearch(input:{first:100,after:$after,sort:"NAME",filter:{activeGroups:true}}){
            pageInfo{ hasNextPage endCursor }
            edges{ node{ urlname } }
          }
        }
      }
    }`, { urlname: SOURCE_GROUP, after });
    const search = data.groupByUrlname.proNetwork.groupsSearch;
    groups.push(...search.edges.map((edge) => edge.node.urlname));
    after = search.pageInfo.hasNextPage ? search.pageInfo.endCursor : null;
  } while (after);
  return [...new Set(groups)].sort();
}

async function createGroupEvent(token, group, session) {
  const mutation = `mutation($input:CreateEventInput!){
    createEvent(input:$input){
      event{ id title dateTime eventUrl status eventType howToFindUs }
      errors{ message code field }
    }
  }`;
  const data = await gql(token, mutation, {
    input: {
      groupUrlname: group,
      title: TITLE,
      description: description(),
      startDateTime: sourceGroupStart(session),
      duration: 'PT1H',
      publishStatus: 'PUBLISHED',
      venueId: ONLINE_VENUE_ID,
      howToFindUs: JOIN_URL,
      eventHosts: [HOST_ID],
      topics: TOPIC_IDS,
      isCopy: true,
    },
  });
  const payload = data.createEvent;
  if (payload.errors && payload.errors.length) throw new Error(JSON.stringify(payload.errors));
  return payload.event;
}

async function repairGroupEvent(token, event) {
  const mutation = `mutation($input:EditEventInput!){
    editEvent(input:$input){
      event{ id title dateTime eventUrl status eventType howToFindUs }
      errors{ message code field }
    }
  }`;
  const data = await gql(token, mutation, {
    input: {
      eventId: event.id,
      description: description(),
      howToFindUs: JOIN_URL,
    },
  });
  const payload = data.editEvent;
  if (payload.errors && payload.errors.length) throw new Error(JSON.stringify(payload.errors));
  return payload.event;
}

async function main() {
  const publish = process.argv.includes('--publish');
  const limitArg = process.argv.find((arg) => arg.startsWith('--limit='));
  const limit = limitArg ? Number(limitArg.split('=')[1]) : Infinity;
  const env = loadEnv();
  let token = await refreshToken(env, loadToken());
  const groups = await proNetworkGroups(token);
  const sessions = visibleProSessions();
  const source = await gql(token, `query($urlname:String!){
    groupByUrlname(urlname:$urlname){
      events(first:10,status:ACTIVE,sort:ASC){
        edges{ node{ id title eventType howToFindUs venue{ id name } networkEvent{ id groupCount status } } }
      }
    }
  }`, { urlname: SOURCE_GROUP });
  const sourceEvent = source.groupByUrlname.events.edges.map((edge) => edge.node).find((event) => event.id === SOURCE_EVENT_ID);
  if (!sourceEvent) throw new Error(`Missing source event ${SOURCE_EVENT_ID}`);

  console.log(JSON.stringify({
    mode: publish ? 'publish' : 'dry-run',
    groups: groups.length,
    sessions: sessions.length,
    sourceEvent,
    estimatedChecks: groups.length,
    joinUrl: JOIN_URL,
  }, null, 2));

  let created = 0;
  let skipped = 0;
  let repaired = 0;
  for (const group of groups) {
    token = await refreshToken(env, token);
    const existing = await existingGroupEvents(token, group, sessions[0].date, sessions[sessions.length - 1].date);
    const existingByInstant = new Map(existing.map((event) => [new Date(event.dateTime).toISOString().slice(0, 16), event]));
    for (const session of sessions) {
      if (created >= limit) break;
      const existingEvent = existingByInstant.get(expectedInstant(session));
      if (existingEvent) {
        if (existingEvent.howToFindUs !== JOIN_URL) {
          if (publish) {
            const event = await repairGroupEvent(token, existingEvent);
            repaired += 1;
            console.log(JSON.stringify({ repaired, group, session: session.id, eventId: event.id, eventUrl: event.eventUrl, dateTime: event.dateTime }));
          } else {
            console.log(`would repair ${group} ${session.id} ${existingEvent.eventUrl}`);
            repaired += 1;
          }
        }
        skipped += 1;
        continue;
      }
      if (!publish) {
        console.log(`would create ${group} ${session.id} ${sourceGroupStart(session)}`);
        created += 1;
        continue;
      }
      const event = await createGroupEvent(token, group, session);
      created += 1;
      console.log(JSON.stringify({ created, group, session: session.id, eventId: event.id, eventUrl: event.eventUrl, dateTime: event.dateTime }));
    }
  }

  console.log(JSON.stringify({ publish, created, repaired, skipped }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
