# ADR 0003 — Subscription Platform: Polar.sh

**Status:** Accepted  
**Date:** 2026-05-29  
**Supersedes:** ADR 0002

## Context
ADR 0002 selected Whop as the subscription and Discord role-gating platform. Scott has an existing contract with Polar.sh, which natively supports course subscriptions. Polar.sh replaces Whop for billing.

## Decision
Use Polar.sh as the subscription billing platform for $100/month recurring payments.

- Polar.sh issues the subscription and sends lifecycle webhooks (created, updated, canceled)
- A PHP webhook at `/webhooks/polar.php` receives these events and calls the Discord API to grant or revoke the "Subscriber" role
- Discord remains the live streaming and community platform (unchanged from ADR 0002)
- YouTube remains the simultaneous public simulcast (unchanged from ADR 0002)

## Subscription Access Rules
- Active subscribers have full access to all live sessions and all recorded sessions
- Cancellation is end-of-period: access continues through the last day of the current billing month
- Trial users are standard subscribers with a 7-day expiry on account creation (no separate tier visible to the user)

## Rationale
- Existing contract with Polar.sh removes procurement friction
- Polar.sh supports course-style subscriptions natively
- Webhook-driven Discord role management is straightforward PHP and reuses the same pattern as the Brevo webhook

## Consequences
- Discord role gating requires a running PHP webhook — if the webhook is down, new subscribers won't get Discord access until it recovers (mitigated by Polar.sh webhook retry logic)
- Polar.sh does not have native Discord integration; the webhook is custom code
- At scale, the PHP webhook is a single point of failure that may warrant a queue or retry table
