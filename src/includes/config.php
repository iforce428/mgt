<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'armaya_catering');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'Armaya Enterprise');
define('SITE_DESCRIPTION', 'Professional Catering Services');
define('ADMIN_EMAIL', 'admin@armaya.com');

// Time zone setting
date_default_timezone_set('Asia/Kuala_Lumpur');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
} 