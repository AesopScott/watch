# Meetup Pro Network Events Process

This document records the end-to-end process for Watch Me Build AI Pro sessions, the current verified state, and the guardrails that prevent accidental direct group pushes.

## Current Site State

- The public schedule and member portal are updated from `data/sessions.json`.
- All visible Pro sessions use `join_session_id: "pro-sess"`.
- The shared Zoom mapping for Pro sessions lives in `data/sessions.json` as the hidden `pro-sess` entry.
- `https://watchmebuildai.com/api/join-session.php?id=pro-sess` is the canonical Meetup join link.
- The portal displays both EDT and UTC times for every event.
- The portal month calendar exposes all sessions and future months.
- Calendar exports exist for each session and for all sessions, with a 15-minute reminder.
- The 7-day free pass flow does not require a credit card.

## Verification Commands

Run the local test suite before shipping schedule or portal changes:

```powershell
npm test
```

The suite currently verifies:

- Critical direct-join sessions resolve.
- Portal entries show both EDT and UTC times.
- Per-session and all-session calendar exports are valid.
- Free-pass CTAs point to the no-card pass flow.
- The portal month calendar includes all sessions and future-month navigation.

Run the live Meetup network audit separately:

```powershell
npm run test:meetup-network-sessions
```

This command calls Meetup and compares the source group events against scheduled Pro sessions. It fails if any scheduled Pro slot is missing a true network event or if a slot has duplicate true network events.

## Meetup Publishing Rules

Meetup Pro session events must be true Meetup Pro network events. Do not create one event per group.

The direct group publisher is now guarded. This command refuses to run by default:

```powershell
node scripts\meetup-pro-sessions-publish.js --publish
```

It can only create direct group events if someone intentionally passes:

```powershell
--allow-direct-group-push
```

That flag is for emergency repair only. It should not be used for normal Pro session publishing.

## Required Network Event Workflow

Meetup's public OAuth GraphQL endpoint does not create true Pro network events, even when given a browser-generated network group filter. It creates direct group events instead.

The supported workflow is the Meetup Pro UI:

1. Open the Meetup Pro Network Events dashboard.
2. Use the existing real Pro network event as the source event.
3. Choose `Duplicate event`.
4. Confirm the copy form says: `This event will be published to all 49 included groups.`
5. Confirm the form has the canonical link:

   ```text
   https://watchmebuildai.com/api/join-session.php?id=pro-sess
   ```

6. Set the target date and time in US/Mountain.
7. Confirm the included-groups preview shows the correct local source group time for `Advanced AI Concepts`.
8. Publish.
9. Verify through:

   ```powershell
   npm run test:meetup-network-sessions
   ```

10. Only after the true network event exists, delete the matching direct group-pushed copy for that slot.

## Recommended Meetup Policy

Publishing every Pro session for three months into all 49 groups creates too much Meetup noise and can bury the Friday and Sunday public events.

Use Meetup for a short rolling window only, such as the next 7 or 14 days of Pro sessions. Keep the full three-month Pro schedule in the Watch Me Build AI portal and downloadable calendars.

## Current Meetup State

As of the last audit:

- True 49-group network Pro events exist through June 23, 2026.
- 60 scheduled Pro slots after that still need true network events if we choose to publish them.
- Duplicate true network events were removed.
- Direct group-pushed copies still exist and should only be removed after matching true network events exist, or if we decide to reduce Meetup noise and remove future Pro sessions from Meetup entirely.

