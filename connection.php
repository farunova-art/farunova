<?php
// Set session configuration for security BEFORE starting session
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '3600');
ini_set('session.cookie_samesite', 'Strict');

// Database Configuration
$server   = "localhost";      // Database server (use localhost for local development)
$username = "appuser";           // Database user
$password = "FarunovaPass@2025";               // Database password
$db       = "farunova_ecommerce";         // Database name

// Debug: Log connection attempt
error_log("[" . date('Y-m-d H:i:s') . "] Attempting database connection to $server, user: $username, db: $db");

// Create connection
$conn = new mysqli($server, $username, $password, $db);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // Log error with full details
    $error_msg = "Database connection failed: " . $conn->connect_error;
    error_log("[" . date('Y-m-d H:i:s') . "] " . $error_msg);

    // Send error to browser console for debugging
    echo "<script>console.error('DB Error: " . addslashes($conn->connect_error) . "');</script>";
    echo "<script>console.error('Server: $server');</script>";
    echo "<script>console.error('User: $username');</script>";
    echo "<script>console.error('Database: $db');</script>";

    // Show user-friendly error
    die("Database connection failed. Please contact administrator. Error has been logged.");
}

// Log successful connection
error_log("[" . date('Y-m-d H:i:s') . "] Database connection successful!");
echo "<script>console.log('✓ Database connection established successfully');</script>";

// Define base URL for the application
define('BASE_URL', 'http://www.farunova.com/');

echo "<script>console.log('BASE_URL: " . BASE_URL . "');</script>";

// Enforce HTTPS in production
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    if (strpos(BASE_URL, 'https://') === 0) {
        $secure_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $secure_url);
        exit();
    }
}

// Include security helpers
error_log("[" . date('Y-m-d H:i:s') . "] Including security.php from: " . __DIR__ . '/security.php');

if (!file_exists(__DIR__ . '/security.php')) {
    echo "<script>console.error('ERROR: security.php not found at " . __DIR__ . "/security.php');</script>";
    error_log("ERROR: security.php not found!");
    die("Security module missing!");
}

require_once(__DIR__ . '/security.php');
echo "<script>console.log('✓ security.php loaded successfully');</script>";

// Include Phase 3 libraries (Code Quality & Performance)
$libs = array(
    'Logger.php' => 'Logger',
    'Cache.php' => 'CacheManager',
    'ErrorHandler.php' => 'ErrorHandler',
    'Database.php' => 'Database',
    'Validator.php' => 'Validator',
    'Helpers.php' => 'Helpers'
);

foreach ($libs as $file => $class) {
    $path = __DIR__ . '/lib/' . $file;
    if (file_exists($path)) {
        require_once($path);
        error_log("[" . date('Y-m-d H:i:s') . "] ✓ Loaded $file");
    } else {
        error_log("[" . date('Y-m-d H:i:s') . "] ⚠ Warning: $file not found");
        echo "<script>console.warn('⚠ Optional library missing: $file');</script>";
    }
}

echo "<script>console.log('✓ All available libraries loaded');</script>";

// Initialize logger
try {
    $logger = new Logger();
    error_log("[" . date('Y-m-d H:i:s') . "] Logger initialized");
    echo "<script>console.log('✓ Logger initialized');</script>";
} catch (Exception $e) {
    error_log("Logger initialization error: " . $e->getMessage());
    echo "<script>console.warn('Logger not available: " . addslashes($e->getMessage()) . "');</script>";
}

// Initialize error handler
try {
    ErrorHandler::init($logger ?? null);
    error_log("[" . date('Y-m-d H:i:s') . "] ErrorHandler initialized");
    echo "<script>console.log('✓ ErrorHandler initialized');</script>";
} catch (Exception $e) {
    error_log("ErrorHandler initialization error: " . $e->getMessage());
}

// Initialize cache
try {
    $cache = new CacheManager();
    error_log("[" . date('Y-m-d H:i:s') . "] Cache initialized");
    echo "<script>console.log('✓ Cache initialized');</script>";
} catch (Exception $e) {
    error_log("Cache initialization error: " . $e->getMessage());
    echo "<script>console.warn('Cache not available: " . addslashes($e->getMessage()) . "');</script>";
}

// Add security headers to all responses
addSecurityHeaders();

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) { // 10 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
