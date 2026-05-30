# CONTEXT — WATCH

Canonical vocabulary for this project. All docs, contracts, and code use these terms consistently.

---

## Agentic AI
Autonomous AI systems that perceive inputs, make decisions, and take actions — as opposed to single-turn prompt/response models. In the context of WATCH, this refers to systems built with Claude Code SDK, tool-use loops, and multi-step reasoning pipelines.

## Agent Orchestration
Coordinating multiple AI agents to solve complex tasks: routing work between agents, managing shared state, handling failures and retries, and composing outputs. The primary skill WATCH teaches through observation.

## Session
A live or recorded coding stream where Scott builds production AI systems in real time. The core content unit of the platform.

## Subscriber
A paying member ($100/month) with full access to all live sessions and all recorded sessions. Granted the "Subscriber" Discord role via Polar.sh webhook on payment confirmation. On cancellation, subscription access continues through the end of the current billing month.

## Polar.sh
Third-party subscription billing platform (course subscription model). Handles $100/month recurring payments. Fires webhooks to /webhooks/polar.php, which calls the Discord API to grant/revoke the Subscriber role. https://polar.sh

## Live Session
The primary content unit. Scott codes live; subscribers attend in real time. This is the product. Minimum cadence: 3 sessions/week, scheduled on a public calendar. No fixed duration — sessions run as long as the build runs.

## Restream
Third-party simulcast service. OBS sends one stream to Restream, which fans it out to Discord Stage and YouTube Live simultaneously. Free tier supports both destinations. https://restream.io

## Replay
A recorded session. Deferred — not part of MVP. Will need to be built when retention requires it.
