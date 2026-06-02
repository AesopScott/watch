<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in      = is_active_subscriber();
$polar_checkout = 'https://buy.polar.sh/polar_cl_' . '3bbf8000-9928-486f-890b-edb630b7733d';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Me Build AI — Learn by Watching AI Masters Build in Real Time</title>
    <meta name="description" content="Stop learning about AI. Start watching it get built. Live coding sessions with a senior AI engineer building real production systems in real time.">
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
            <?php if ($logged_in): ?>
                <a href="/portal/" class="btn btn-sm">Member Portal →</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-sm btn-outline">Log In</a>
                <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-sm">Start Free Trial</a>
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
            <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-primary btn-lg">Start 7-Day Free Trial</a>
            <span class="hero-cta-note">$100/month after trial &nbsp;·&nbsp; Cancel any time</span>
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
        <h2 class="section-title">What watching does for you</h2>
        <p class="section-sub">Watching someone build the real thing is the fastest way to learn how to build it yourself.</p>
        <div class="value-grid" style="margin-top:0">
            <div class="value-card">
                <div class="value-icon">🧠</div>
                <h3>You start thinking in systems</h3>
                <p>You'll stop asking "what prompt do I use?" and start asking "how do I architect this pipeline?" — the shift that separates AI tinkerers from AI builders.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">⚙️</div>
                <h3>You see how real pipelines actually get built</h3>
                <p>Watch multi-step agent workflows go from idea to working system in real time. Not a demo. Not a cleaned-up tutorial. The actual build.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🗺</div>
                <h3>You internalize the decisions, not just the code</h3>
                <p>Every session shows the dead ends, the pivots, the "why not that" moments. That judgment is what you're here to absorb — it can't come from reading docs.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🚀</div>
                <h3>You skip six months of trial and error</h3>
                <p>Watching someone who's already figured it out means you know what works before you try it yourself. That's the entire value of learning by watching.</p>
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
