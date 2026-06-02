<?php
// Copy this file to config.php and fill in real values.
// config.php is gitignored and excluded from FTP deploy — upload it manually to the server once.

// Polar.sh
define('POLAR_HMAC_SECRET',   'your-polar-webhook-signing-secret');
define('POLAR_PRODUCT_IDS',   [
    '3bbf8000-9928-486f-890b-edb630b7733d',  // $100/month subscription — 7-day trial
    // Add additional product IDs here as new plans are created
]);

// Brevo
define('BREVO_API_KEY',       'your-brevo-api-key');
define('BREVO_LIST_ID',       0);      // Brevo subscriber list ID (integer)
define('BREVO_SENDER_EMAIL',  'scott@watchmebuildai.com');
define('BREVO_SENDER_NAME',   'Scott — Watch Me Build AI');

// Admin
define('ADMIN_PASSWORD_HASH', '');     // Generate with: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"

// Site
define('SITE_URL',            'https://watchmebuildai.com');
