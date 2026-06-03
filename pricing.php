<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in = is_active_subscriber();

$plans = [
    [
        'id'          => 'weekly',
        'badge'       => 'Pro Lite',
        'badge_color' => '#0891b2',
        'name'        => 'Pro Lite',
        'tagline'     => '1 Pro Session per week (1 hour)',
        'amount'      => '$25',
        'period'      => '/month',
        'limit'       => '10 seats per live session — first in',
        'callout'     => 'Real client work. Every Pro Session is built around an actual project commissioned by one of our customers — so you learn to ship real AI products, exactly like working at your own AI agency.',
        'callout_color' => '#0891b2',
        'features'    => [
            '1 live Pro Session per week (1 hour)',
            'Session link in your paywalled calendar',
            'Access to session recording library',
            'Email reminder before every session',
            'Cancel any time',
        ],
        'cta'      => 'Get Weekly Access',
        'checkout' => 'https://buy.polar.sh/polar_cl_1995f61c-e56f-4487-8962-78608bd0b56c',
    ],
    [
        'id'          => 'monthly',
        'badge'       => 'Most Popular',
        'badge_color' => '#7c3aed',
        'name'        => 'Pro',
        'tagline'     => '8 Pro Sessions per week (1 hour each)',
        'amount'      => '$100',
        'period'      => '/month',
        'limit'       => '30 seats per live session — first in',
        'trial'       => 'Start with a 7-day free trial',
        'callout'     => 'Real client work. Every Pro Session is built around an actual project commissioned by one of our customers — so you learn to ship real AI products, exactly like working at your own AI agency.',
        'callout_color' => '#7c3aed',
        'features'    => [
            '8 live Pro Sessions per week (1 hour each)',
            'All session links in your paywalled calendar',
            'Full recorded session library',
            'Email reminder before every session',
            'Cancel any time — access through end of billing month',
        ],
        'cta'      => 'Start Free Trial',
        'checkout' => 'https://buy.polar.sh/polar_cl_3bbf8000-9928-486f-890b-edb630b7733d',
    ],
    [
        'id'          => 'cohort10',
        'badge'       => 'Cohort',
        'badge_color' => '#0d9488',
        'name'        => 'Cohort 10',
        'tagline'     => '4 Cohort Sessions per week + all Pro Sessions',
        'amount'      => '$995',
        'period'      => '/month',
        'callout'     => 'We build YOUR project. Every cohort session is dedicated to what you\'re working on — not a demo, not our idea. You bring the project; we build it together.',
        'callout_color' => '#0d9488',
        'features'    => [
            '4 Cohort group sessions per week (1 hour each)',
            'All Pro Sessions included',
            'Schedule your Cohort session via form on the site',
            'All session links in your paywalled calendar',
            'Full recorded session library',
            'Email reminder before every session',
            'Cancel any time',
        ],
        'cta'      => 'Join Cohort 10',
        'checkout' => 'https://buy.polar.sh/polar_cl_a9b65de6-64c2-4921-86d2-443db4eb0a05',
    ],
    [
        'id'          => 'private',
        'badge'       => 'Private',
        'badge_color' => '#b45309',
        'name'        => 'Cohort Private',
        'tagline'     => '4 private sessions per week + all Pro sessions',
        'amount'      => '$4,995',
        'period'      => '/month',
        'callout'     => 'We build YOUR product. Every session is 1-on-1, focused entirely on your vision. You set the agenda; we execute together.',
        'callout_color' => '#b45309',
        'features'    => [
            '4 private 1-on-1 sessions per week (1 hour each)',
            'All Pro Sessions included',
            'All session links in your paywalled calendar',
            'Full recorded session library',
            'Direct access to Scott',
        ],
        'cta'      => 'Join Private Cohort',
        'checkout' => 'https://buy.polar.sh/polar_cl_5fa68e31-e670-4492-90da-6495fa4170ea',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — Watch Me Build AI</title>
    <meta name="description" content="Choose your level of access — from weekly sessions to private cohort coaching.">
    <link rel="icon" href="/assets/images/favicon.svg" type="image/svg+xml">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pricing — Watch Me Build AI">
    <meta property="og:description" content="Choose your level of access — from weekly sessions to private cohort coaching.">
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
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="section">
    <div class="container">
        <h1 class="section-title">Choose your level of access</h1>
        <p class="section-sub">Every tier includes the full session library and Zoom links. Upgrade or cancel any time.</p>

        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
            <div class="pricing-card-v2">
                <div class="pricing-badge" style="background:<?= $plan['badge_color'] ?>"><?= htmlspecialchars($plan['badge']) ?></div>
                <div class="pc-name"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="pc-tagline"><?= htmlspecialchars($plan['tagline']) ?></div>
                <div class="pc-amount"><?= htmlspecialchars($plan['amount']) ?><span><?= htmlspecialchars($plan['period']) ?></span></div>
                <?php if (!empty($plan['limit'])): ?>
                <div class="pc-limit">⚡ <?= htmlspecialchars($plan['limit']) ?></div>
                <?php endif; ?>
                <?php if (!empty($plan['trial'])): ?>
                <div class="pricing-trial"><?= htmlspecialchars($plan['trial']) ?></div>
                <?php endif; ?>
                <?php if (!empty($plan['callout'])): ?>
                <div class="pc-callout" style="border-left-color:<?= htmlspecialchars($plan['callout_color']) ?>">
                    <?= htmlspecialchars($plan['callout']) ?>
                </div>
                <?php endif; ?>
                <ul class="pricing-features">
                    <?php foreach ($plan['features'] as $f): ?>
                    <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= htmlspecialchars($plan['checkout']) ?>" class="btn btn-primary btn-full">
                    <?= htmlspecialchars($plan['cta']) ?> →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
