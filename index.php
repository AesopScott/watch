<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/sessions.php';

$logged_in      = is_active_subscriber();
$upcoming       = get_upcoming_sessions(6);   // public view — no Zoom links
$polar_checkout = 'https://buy.polar.sh/polar_cl_' . '3bbf8000-9928-486f-890b-edb630b7733d';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Me Build AI — Learn by Watching AI Masters Build in Real Time</title>
    <meta name="description" content="Stop learning about AI. Start watching it get built. Live coding sessions, 3x per week, with a senior AI engineer building real production systems.">
    <link rel="stylesheet" href="/assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>

<!-- ── Nav ── -->
<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-logo">Watch Me Build AI</a>
        <div class="nav-links">
            <a href="#schedule">Schedule</a>
            <a href="#pricing">Pricing</a>
            <?php if ($logged_in): ?>
                <a href="/portal/" class="btn btn-sm">Member Portal →</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-sm btn-outline">Log In</a>
                <a href="#subscribe" class="btn btn-sm">Start Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<section class="hero">
    <div class="container">
        <div class="stage-track">
            <div class="stage-item">Stage 0 — Aesop AI Academy</div>
            <div class="stage-sep">›</div>
            <div class="stage-item">Stage 1 — Weekly Meetup Events</div>
            <div class="stage-sep">›</div>
            <div class="stage-item stage-active">Stage 2 — Interactive Builder Sessions</div>
            <div class="stage-sep">›</div>
            <div class="stage-item">Stage 3 — Group Cohorts</div>
            <div class="stage-sep">›</div>
            <div class="stage-item">Stage 4 — Private Cohorts</div>
        </div>
        <h1 class="hero-headline">
            You want to master AI.<br>
            <span class="highlight">Watch AI masters build it.</span>
        </h1>
        <p class="hero-sub">
            Courses teach you concepts. YouTube shows you tutorials.
            Neither shows you what it actually looks like to build ten AI companies simultaneously —
            the decisions, the dead ends, the agent pipelines that actually ship.
        </p>
        <div class="hero-cta">
            <a href="#subscribe" class="btn btn-primary btn-lg">Start 7-Day Free Trial</a>
            <span class="hero-cta-note">$100/month after trial &nbsp;·&nbsp; Cancel any time</span>
        </div>
        <div class="hero-proof">
            <span>3 live sessions per week</span>
            <span>·</span>
            <span>Real production systems</span>
            <span>·</span>
            <span>No slides. No theory.</span>
        </div>
    </div>
</section>

<!-- ── Value ── -->
<section class="section section-value">
    <div class="container">
        <h2 class="section-title">There's a better way to learn than just listening.</h2>
        <div class="value-grid">
            <div class="value-card">
                <div class="value-icon">👁</div>
                <h3>Watch the whole process</h3>
                <p>Not just the finished product — the thinking, the tooling, the mistakes. Every session is a live, unedited build.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h3>Go further, faster</h3>
                <p>In six months you'll be building what most developers spend years working toward — agent pipelines, orchestration, your own AI command center.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🔬</div>
                <h3>Beyond basic AI education</h3>
                <p>Skip the "what is a prompt" videos. This is for developers who already understand AI basics and want to build serious systems.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🏗</div>
                <h3>Real systems, real decisions</h3>
                <p>Watch ten companies being built simultaneously with AI. See how multi-session agent workflows actually get designed and shipped.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Who it's for ── -->
<section class="section section-alt">
    <div class="container">
        <div class="two-col">
            <div>
                <h2>Who this is for</h2>
                <ul class="check-list">
                    <li>Developers who've taken AI courses but want to see real-world application</li>
                    <li>Engineers who want to build with Claude Code SDK, agent orchestration, and multi-step pipelines</li>
                    <li>People who learn by watching, not just reading docs</li>
                    <li>Anyone who wants to build their own AI-powered tools and doesn't want to start from scratch</li>
                </ul>
            </div>
            <div>
                <h2>What you won't find here</h2>
                <ul class="x-list">
                    <li>Basic "what is AI" explanations</li>
                    <li>Pre-recorded, edited, polished content</li>
                    <li>Things you could learn by just asking ChatGPT</li>
                    <li>Boring</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ── Schedule ── -->
<section class="section" id="schedule">
    <div class="container">
        <h2 class="section-title">Upcoming Sessions</h2>
        <p class="section-sub">3 sessions per week. No fixed length — sessions run as long as the build runs.</p>
        <?php if ($upcoming): ?>
        <div class="session-list">
            <?php foreach ($upcoming as $session): ?>
            <div class="session-card">
                <div class="session-date"><?= htmlspecialchars(format_session_date($session['date'])) ?></div>
                <div class="session-info">
                    <div class="session-title"><?= htmlspecialchars($session['title']) ?></div>
                    <?php if (!empty($session['description'])): ?>
                    <div class="session-desc"><?= htmlspecialchars($session['description']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="session-lock">
                    <?php if ($logged_in): ?>
                        <a href="/portal/#session-<?= htmlspecialchars($session['id']) ?>" class="btn btn-sm">Join →</a>
                    <?php else: ?>
                        <span class="lock-badge">🔒 Subscribers only</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="session-empty">
            <p>Sessions are scheduled weekly. <a href="#subscribe">Subscribe to get notified</a> when the next one is posted.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Pricing ── -->
<section class="section section-pricing" id="pricing">
    <div class="container">
        <h2 class="section-title">Simple pricing</h2>
        <div class="pricing-card" id="subscribe">
            <div class="pricing-badge">Most popular</div>
            <div class="pricing-amount">$100<span>/month</span></div>
            <div class="pricing-trial">Start with a 7-day free trial</div>
            <ul class="pricing-features">
                <li>All live sessions — 3+ per week</li>
                <li>Zoom links delivered before each session</li>
                <li>Full recorded session library</li>
                <li>Email reminders before every session</li>
                <li>Cancel any time — access through end of billing month</li>
            </ul>
            <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-primary btn-lg btn-full">
                Start Free Trial →
            </a>
            <p class="pricing-note">You become a subscriber for the next 7 days. No charge until your trial ends.</p>
        </div>
    </div>
</section>

<!-- ── Footer ── -->
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-brand">Watch Me Build AI</span>
            <span class="footer-links">
                <a href="/login.php">Member Login</a>
            </span>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
