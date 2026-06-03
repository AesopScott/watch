<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in = is_active_subscriber();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ — Watch Me Build AI</title>
    <meta name="description" content="Frequently asked questions about Watch Me Build AI — sessions, access, pricing, and more.">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <meta property="og:type" content="website">
    <meta property="og:title" content="FAQ — Watch Me Build AI">
    <meta property="og:description" content="Frequently asked questions about Watch Me Build AI — sessions, access, pricing, and more.">
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
                <a href="https://buy.polar.sh/polar_cl_3bbf8000-9928-486f-890b-edb630b7733d" class="btn btn-sm">Start Free Trial</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Frequently Asked Questions</h1>
    </div>

    <!-- Who it's for -->
    <section style="margin-bottom:60px">
        <div class="two-col">
            <div>
                <h2>Who this is for</h2>
                <ul class="check-list">
                    <li>Developers who've taken AI courses but want to see real-world application</li>
                    <li>Engineers who want to understand Claude Code SDK, agent orchestration, and multi-step pipelines</li>
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
    </section>

    <!-- FAQ accordion -->
    <section style="margin-bottom:80px">
        <div class="faq-list">

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">What actually happens during a session? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Each session is a live, unedited Zoom call where Scott builds something real — an agent pipeline, an orchestration layer, a production AI system. You watch the whole process: the planning, the mistakes, the debugging, the decisions. No slides. No script. Just building.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">How do I get the Zoom link? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Once you're a subscriber, the Zoom link for every upcoming session appears directly in your member portal. You'll also get an email reminder before each session with the link included.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">What if I miss a session? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Every session is recorded and added to the library in your member portal. You can watch at any time, as many times as you want, for as long as your subscription is active.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">What tools or experience do I need? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>You need a Zoom account to join live. Beyond that: basic programming experience and familiarity with AI concepts. This is not a beginner course — it's for developers who already understand the basics and want to see what serious AI engineering actually looks like.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">How much time does this take per week? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Sessions run as long as the build runs — there's no fixed length. You can attend live, catch the recording, or both. There's no homework, no assignments, no required time outside of watching. It fits around your schedule.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">Can I cancel any time? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Yes. Cancel any time and you keep access through the end of your current billing period. No questions asked, no hoops to jump through.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">What's the refund policy? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>You have a 7-day free trial — no charge until it ends. If you decide it's not for you during that window, just cancel and you won't be billed. After the trial, subscriptions are non-refundable but you can cancel to stop future charges.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-q" onclick="toggleFaq(this)">What exactly is being shown in these sessions? <span class="faq-chevron">›</span></button>
                <div class="faq-a">
                    <p>Real production AI systems — agent pipelines, orchestration layers, multi-step workflows, AI-powered tools. Scott is simultaneously building ten companies with AI, and these sessions show exactly how that gets done: the architecture decisions, the tooling choices, the failures and what came after them.</p>
                </div>
            </div>

        </div>
    </section>
</div>

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
