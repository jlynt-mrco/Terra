<?php
/**
 * TERRA — Sistem Manajemen Pendakian Gunung
 * Global Configuration
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base paths
define('BASE_PATH', __DIR__);
define('DATA_PATH', BASE_PATH . '/data');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Base URL (auto-detect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . '/Terra');

// Data files
define('USERS_FILE', DATA_PATH . '/users.json');
define('MOUNTAINS_FILE', DATA_PATH . '/mountains.json');
define('BOOKINGS_FILE', DATA_PATH . '/bookings.json');

// App config
define('APP_NAME', 'TERRA');
define('APP_TAGLINE', 'Sistem Pendakian Gunung Indonesia');
define('APP_VERSION', '1.0.0');

// Quota defaults
define('DEFAULT_QUOTA', 300);

// Include helpers
require_once BASE_PATH . '/helpers.php';
