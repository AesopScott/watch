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
A paying member ($100/month) with full access to all live sessions and all recorded sessions. Activated via Polar.sh webhook on payment confirmation; access record stored server-side by the PHP webhook. On cancellation, access continues through the end of the current billing month.

## Polar.sh
Third-party subscription billing platform (course subscription model). Handles $100/month recurring payments. Fires webhooks to /webhooks/polar.php, which updates the server-side subscriber access record and syncs to Brevo. https://polar.sh

## Live Session
The primary content unit. Scott codes live via Zoom; subscribers attend in real time. This is the product. Minimum cadence: 3 sessions/week. No fixed duration — sessions run as long as the build runs. The Zoom link for each session is visible on the session calendar to logged-in subscribers only.

## Zoom
The live session platform. Scott hosts a Zoom meeting or webinar; the join link is shown on the member calendar to active subscribers. Brevo sends the link via email before each session.

## Session Calendar
A page on watchmebuildai.com showing upcoming sessions. Public view: dates and titles only. Subscriber view: full details including the Zoom join link.

## Member Portal
The subscriber-gated area of watchmebuildai.com. Shows the session calendar with Zoom links and recorded session library. Access controlled by server-side PHP session tied to Polar.sh subscription status.

## Brevo
Email platform. Sends onboarding emails on subscription activation, session reminder emails with Zoom links before each live session, and win-back emails on cancellation. https://brevo.com

## Replay
A recorded session. Deferred — not part of MVP. Will need to be built when retention requires it.
