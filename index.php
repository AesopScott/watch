<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in      = is_active_subscriber();
$free_pass_url  = '/free-pass.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Me Build AI — Learn by Watching AI Masters Build in Real Time</title>
    <meta name="description" content="Stop learning about AI. Start watching it get built. Live coding sessions with a senior AI engineer building real production systems in real time.">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Watch Me Build AI">
    <meta property="og:description" content="Stop learning about AI. Start watching it get built. Live coding sessions building real AI products in real time.">
    <meta property="og:image" content="https://watchmebuildai.com/assets/images/og.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://watchmebuildai.com/assets/images/og.png">
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
            <a href="/schedule.php">Schedule</a>
            <a href="/pricing.php">Pricing</a>
            <a href="/faq.php">FAQ</a>
            <a href="/why-choose-us.php">Why Choose Us</a>
            <?php if ($logged_in): ?>
                <a href="/portal/" class="btn btn-sm">Member Portal →</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-sm btn-outline">Log In</a>
                <a href="<?= htmlspecialchars($free_pass_url) ?>" class="btn btn-sm">Start Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<section class="hero">
    <div class="container">
        <div class="stage-track">
            <a href="https://aesopacademy.org" target="_blank" rel="noopener" class="stage-item">Stage 0 — Aesop AI Academy</a>
            <div class="stage-sep">›</div>
            <a href="https://mojoaistudio.com/learn" target="_blank" rel="noopener" class="stage-item">Stage 1 — Weekly Meetup Events</a>
            <div class="stage-sep">›</div>
            <a href="/pricing.php" class="stage-item stage-active">Stage 2 — Interactive Builder Sessions</a>
            <div class="stage-sep">›</div>
            <a href="/pricing.php" class="stage-item">Stage 3 — Group Cohorts</a>
            <div class="stage-sep">›</div>
            <a href="/pricing.php" class="stage-item">Stage 4 — Private Cohorts</a>
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
            <a href="<?= htmlspecialchars($free_pass_url) ?>" class="btn btn-primary btn-lg">Start 7-Day Pass</a>
            <span class="hero-cta-note">No credit card required &nbsp;·&nbsp; $100/month when you choose to subscribe</span>
        </div>
        <div class="hero-proof">
            <span>Real production systems</span>
            <span>·</span>
            <span>No slides. No theory.</span>
        </div>
    </div>
</section>

<!-- ── Outcomes ── -->
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title">What co-building does for you</h2>
        <p class="section-sub">Fork the repo. Build alongside us in real time. Ship the same working system the moment we do — not six months later from a tutorial.</p>
        <div class="value-grid" style="margin-top:0">
            <div class="value-card">
                <div class="value-icon">🧠</div>
                <h3>You start thinking in systems</h3>
                <p>Building the pipeline yourself — not just watching a video of it — is what forces the systems-thinking shift. You'll stop asking "what prompt do I use?" and start asking "how do I architect this pipeline?"</p>
            </div>
            <div class="value-card">
                <div class="value-icon">⚙️</div>
                <h3>You build the pipeline as we build it</h3>
                <p>Fork the repo, follow each session live, and your code lands the same time ours does. Multi-step agent workflows, end-to-end — built once by us, built once by you, in parallel.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🗺</div>
                <h3>You inherit the decisions, not just the code</h3>
                <p>Every session shows the dead ends, the pivots, the "why not that" moments — as they happen, with your editor open. When you hit the same fork in the road on your own projects, you already know which way to go.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🚀</div>
                <h3>You skip six months of trial and error</h3>
                <p>Co-building with someone who's already figured it out means you ship the working system today, not after six months of false starts. That's the entire value of learning by building together.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🔬</div>
                <h3>This isn't basic AI education</h3>
                <p>Skip the "what is a prompt" videos. This is for developers who already understand AI basics and want to see what serious AI engineering actually looks like in practice.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Footer ── -->
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <span class="footer-brand">Watch Me Build AI</span>
            <span class="footer-links">
                <a href="/faq.php">FAQ</a>
                <a href="/login.php">Member Login</a>
            </span>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js?v=<?= filemtime(__DIR__ . '/assets/js/main.js') ?>"></script>
</body>
</html>
