<?php
// Copy this file to config.php and fill in real values.
// config.php is gitignored and excluded from FTP deploy — upload it manually to the server once.

// Firebase (web config — safe to be public)
define('FIREBASE_API_KEY',             'AIzaSyB8Y-2L0BZ3f5WvbPstjDB1Gi1dSU1Ij4M');
define('FIREBASE_PROJECT_ID',          'watch-38b66');
define('FIREBASE_AUTH_DOMAIN',         'watch-38b66.firebaseapp.com');
define('FIREBASE_APP_ID',              '1:497659403030:web:c3ba82f57a316a88e2ce37');
define('FIREBASE_MESSAGING_SENDER_ID', '497659403030');

// Polar.sh
define('POLAR_HMAC_SECRET',   'your-polar-webhook-signing-secret');
define('POLAR_PRODUCT_IDS',   [
    '1995f61c-e56f-4487-8962-78608bd0b56c',  // Weekly
    '3bbf8000-9928-486f-890b-edb630b7733d',  // Monthly — 7-day trial
    'a9b65de6-64c2-4921-86d2-443db4eb0a05',  // Cohort 10
    '5fa68e31-e670-4492-90da-6495fa4170ea',  // Cohort Private
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
