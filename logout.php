<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['subscriber_email'], $_SESSION['logged_in_at']);
session_destroy();

header('Location: /');
exit;
