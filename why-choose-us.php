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
    <title>Why Choose Us — Watch Me Build AI</title>
    <meta name="description" content="Don't trust credentials. Show up for free, see if you learn, see if you like the style. Then decide to become a subscriber.">
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
                <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-sm">Start Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── Hero ── -->
<section class="hero">
    <div class="container">
        <h1 class="hero-headline">
            Don't trust anybody.<br>
            <span class="highlight">Show up and decide for yourself.</span>
        </h1>
        <p class="hero-sub">
            You shouldn't base anything on degrees, certifications, experience, or what someone's built.
            You should show up, see if they actually teach you anything, see if you like their style.
            Then decide if it's worth your money.
        </p>
    </div>
</section>

<hr class="section-divider">

<!-- ── How It Works ── -->
<section class="section">
    <div class="container">
        <h2 class="section-title">This is how it actually works here</h2>
        <div class="value-grid-4">
            <div class="value-card">
                <div class="value-icon">🎬</div>
                <h3>You show up for free</h3>
                <p>Watch real AI systems get built in real time. No paywall. No hidden catch. See exactly what you're getting before you commit a dollar.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">📚</div>
                <h3>You determine if you learn</h3>
                <p>Do the explanations make sense? Does the code actually click? Are the decisions being made transparent? You'll know immediately whether this style works for you.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">👤</div>
                <h3>You decide if you like the style</h3>
                <p>Teaching style matters. Some people resonate with certain voices and approaches. See if mine is one of them. No guessing. No hype. Just watch.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">💳</div>
                <h3>Then you decide if it's worth paying for</h3>
                <p>You've got real data now. You've seen what you're getting. If it works for you, become a subscriber. If it doesn't, move on. That's it.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Not Teaching ── -->
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title">What this is not</h2>
        <p class="section-sub">We're not here to teach you concepts or theory.</p>
        <div class="value-grid-3">
            <div class="value-card">
                <div class="value-icon">❌</div>
                <h3>No slides</h3>
                <p>You won't see PowerPoint decks or animated explanations of concepts. This isn't an educational lecture format.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">❌</div>
                <h3>No tutorials</h3>
                <p>We're not walking you through step-by-step instructions. You're watching real work happen, with all the decisions and dead ends that come with it.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">❌</div>
                <h3>No theory</h3>
                <p>You won't hear abstract explanations about how things work. You're seeing how things actually get built in production, right now.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── What You're Actually Getting ── -->
<section class="section">
    <div class="container">
        <h2 class="section-title">What you're actually getting</h2>
        <div class="value-grid-4">
            <div class="value-card">
                <div class="value-icon">🏗</div>
                <h3>Real production systems</h3>
                <p>You're watching real AI companies and real production systems get built. Not examples. Not demos. The actual work.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🤔</div>
                <h3>Real decision-making</h3>
                <p>Why choose this approach over that one? Why pivot here? What's the dead end we just hit and how do we get around it? You see the actual judgment calls that matter.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h3>Hands-on building</h3>
                <p>Everything is live. Everything is code. Everything is being built right in front of you. You're not reading about building — you're watching it happen.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🎯</div>
                <h3>Shortened learning curve</h3>
                <p>You get to skip the trial-and-error phase. Watch someone who's already figured it out, and you know what works before you spend months finding out the hard way.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── Free Sessions ── -->
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title">Upcoming free sessions</h2>
        <p class="section-sub">Come watch. No signup required. No strings attached.</p>
        <div style="text-align: center; padding: 60px 20px;">
            <p style="font-size: 18px; color: #666;">We're scheduling our first free sessions soon. Check back here for the schedule.</p>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="section">
    <div class="container" style="text-align: center;">
        <h2 class="section-title">Ready to see what you're getting?</h2>
        <p class="section-sub">Show up for free. Make your own decision.</p>
        <div style="margin-top: 40px;">
            <a href="<?= htmlspecialchars($polar_checkout) ?>" class="btn btn-primary btn-lg">Start 7-Day Free Trial</a>
            <p style="margin-top: 20px; color: #666; font-size: 14px;">$100/month after trial &nbsp;·&nbsp; Cancel any time</p>
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
