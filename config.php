<?php

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'loginsystem');
define('DB_USER', getenv('DB_USER') ?: 'jamlick');
define('DB_PASS', getenv('DB_PASS') ?: 'login123!');

// SMTP configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'jamlickmarsh400@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'bagr dtmy zxds yqpq');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Secure Login System');

// Session security
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false, 
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}