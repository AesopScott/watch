# ADR 0004 — Streaming Platform: Zoom; No Discord

**Status:** Accepted  
**Date:** 2026-06-02  
**Supersedes:** ADR 0002, ADR 0003 (Discord role-gating aspect only; Polar.sh billing unchanged)

## Context
ADR 0002 and ADR 0003 used Discord as the live streaming and community platform, with subscriber access gated by a Discord role granted via Polar.sh webhook. This required maintaining a Discord server, a Discord bot with Manage Roles permission, and Discord OAuth for the member portal login.

## Decision
Remove Discord from the stack entirely. Use Zoom for live sessions. Gate subscriber access via server-side PHP session (email magic-link login checked against a Polar.sh-maintained subscriber record).

- OBS → Zoom (meeting or webinar)
- Zoom join link stored in sessions.json, visible on the session calendar to logged-in subscribers only
- Brevo sends Zoom link via email before each session
- No Discord server, no Discord bot, no Discord OAuth

## Rationale
- Zoom is familiar to the target audience and requires no additional account setup from subscribers
- Removes Discord server administration, bot permission management, and OAuth complexity
- PHP session + email magic link is simpler to build and maintain than Discord OAuth role check
- Brevo email already in the stack — session reminder emails are zero additional infrastructure
- Discord community features (forums, chat) not needed at 15–20 subscribers; not worth the overhead

## Consequences
- No Discord community; subscribers interact via email and live Zoom sessions only
- Zoom links must be kept out of public calendar view — server-side PHP session check required
- If Zoom meeting ID changes between sessions, sessions.json must be updated and Brevo reminder re-sent
- Community features (forums, Q&A threads) remain deferred — can revisit at scale
