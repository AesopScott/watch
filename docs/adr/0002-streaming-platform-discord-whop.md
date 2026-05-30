# ADR 0002 — Streaming Platform: Discord + Whop

**Status:** Superseded by ADR 0003  
**Date:** 2026-05-22

## Context
WATCH needs a live streaming platform with a paywall at $100/month. Options considered: Discord + paywall bot, Twitch + separate paywall, custom RTMP server.

## Decision
Use Discord as the live streaming and community platform, with Whop handling subscription billing and Discord role gating.

- OBS → Discord Stage Channel (private, subscribers-only)
- Whop handles $100/month billing, grants "Subscriber" Discord role on payment
- YouTube as simultaneous public stream for marketing clips and backup

## Rationale
- Fastest path to first paying subscriber — no custom infrastructure to build
- Discord handles community naturally (channels, threads = built-in forum)
- Whop is the lowest-fee subscription manager at this price point (~4.9% + $0.30/transaction)
- YouTube simulcast via OBS is zero extra effort and provides marketing reach
- Twitch can be added later as a free teaser/discovery channel without changing the core setup

## Consequences
- Subscribers are anchored to Discord — migrating them to a new platform later has real friction
- Discord Stage quality depends on internet connection; no CDN fallback
- Whop takes a cut; at scale, a custom billing system becomes worth building
- Community features are Discord-native — cannot easily export to a custom forum later
