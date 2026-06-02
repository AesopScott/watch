<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/auth.php';

$logged_in = is_active_subscriber();

$plans = [
    [
        'id'       => 'weekly',
        'badge'    => 'Limited Access',
        'badge_color' => '#0891b2',
        'name'     => 'Weekly',
        'tagline'  => '1 Pro Session per week',
        'amount'   => '$25',
        'period'   => '/month',
        'limit'    => 'First 10 members',
        'features' => [
            '1 live Pro Session per week',
            'Zoom link delivered before each session',
            'Access to session recording library',
            'Email reminders before every session',
            'Cancel any time',
        ],
        'cta'      => 'Get Weekly Access',
        'checkout' => 'https://buy.polar.sh/polar_cl_1995f61c-e56f-4487-8962-78608bd0b56c',
    ],
    [
        'id'       => 'monthly',
        'badge'    => 'Most Popular',
        'badge_color' => '#7c3aed',
        'name'     => 'Monthly',
        'tagline'  => 'Unlimited Pro Sessions',
        'amount'   => '$100',
        'period'   => '/month',
        'limit'    => 'First 30 members',
        'trial'    => 'Start with a 7-day free trial',
        'features' => [
            'All live Pro Sessions — unlimited',
            'Zoom links delivered before each session',
            'Full recorded session library',
            'Email reminders before every session',
            'Cancel any time — access through end of billing month',
        ],
        'cta'      => 'Start Free Trial',
        'checkout' => 'https://buy.polar.sh/polar_cl_3bbf8000-9928-486f-890b-edb630b7733d',
    ],
    [
        'id'       => 'cohort10',
        'badge'    => 'Cohort',
        'badge_color' => '#0d9488',
        'name'     => 'Cohort 10',
        'tagline'  => 'Unlimited Pro + Cohort Sessions',
        'amount'   => '$995',
        'period'   => '/month',
        'limit'    => 'First 10 members',
        'features' => [
            'All live Pro Sessions — unlimited',
            'Cohort group sessions included',
            'Zoom links delivered before each session',
            'Full recorded session library',
            'Email reminders before every session',
            'Cancel any time',
        ],
        'cta'      => 'Join Cohort 10',
        'checkout' => 'https://buy.polar.sh/polar_cl_a9b65de6-64c2-4921-86d2-443db4eb0a05',
    ],
    [
        'id'       => 'private',
        'badge'    => 'Private',
        'badge_color' => '#b45309',
        'name'     => 'Cohort Private',
        'tagline'  => '2–4 private meetings per week',
        'amount'   => '$4,995',
        'period'   => '/month',
        'limit'    => 'By application',
        'features' => [
            '2–4 private sessions per week',
            'Sessions scheduled by you',
            'All live Pro Sessions — unlimited',
            'Full recorded session library',
            'Direct access to Scott',
        ],
        'cta'      => 'Apply for Private Cohort',
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
                <div class="pc-limit">⚡ <?= htmlspecialchars($plan['limit']) ?></div>
                <?php if (!empty($plan['trial'])): ?>
                <div class="pricing-trial"><?= htmlspecialchars($plan['trial']) ?></div>
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
