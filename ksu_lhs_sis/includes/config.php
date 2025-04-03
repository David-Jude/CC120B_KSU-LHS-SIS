<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ksu_lhs_sis');

// Site configuration
define('SITE_NAME', 'KSU LHS SIS');
define('SITE_URL', 'http://localhost/ksu-lhs-sis');

// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ksu-lhs-sis/assets/uploads/');
define('PROFILE_PIC_PATH', 'assets/uploads/profile_pics/');

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>