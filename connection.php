<?php
// Database Configuration
$server   = "localhost";      // Database server (use localhost for local development)
$username = "root";           // Database user
$password = "";               // Database password
$db       = "GROUP1";         // Database name

// Create connection
$conn = new mysqli($server, $username, $password, $db);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // Log error securely without exposing details to user
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to database. Please try again later.");
}

// Define base URL for the application
define('BASE_URL', 'http://localhost/farunova/');

// Enforce HTTPS in production
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    if (strpos(BASE_URL, 'https://') === 0) {
        $secure_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $secure_url);
        exit();
    }
}

// Include security helpers
require_once(__DIR__ . '/security.php');

// Include Phase 3 libraries (Code Quality & Performance)
require_once(__DIR__ . '/lib/Logger.php');
require_once(__DIR__ . '/lib/Cache.php');
require_once(__DIR__ . '/lib/ErrorHandler.php');
require_once(__DIR__ . '/lib/Database.php');
require_once(__DIR__ . '/lib/Validator.php');
require_once(__DIR__ . '/lib/Helpers.php');

// Initialize logger
$logger = new Logger();

// Initialize error handler
ErrorHandler::init($logger);

// Initialize cache
$cache = new CacheManager();

// Add security headers to all responses
addSecurityHeaders();

// Set session configuration for security
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '3600');

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) { // 10 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
