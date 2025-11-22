<?php
// Enable error reporting for debugging
ini_set('display_errors', 0); // Don't display to user
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Database Configuration
// Try to auto-detect if local or remote
$is_local = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);

if ($is_local) {
    // LOCAL DEVELOPMENT
    $server   = "localhost";      // Usually 'localhost' on shared hosting
    $username = "appuser";        // Your database username
    $password = "FarunovaPass@2025";    // Your database password
    $db       = "farunova_ecommerce";
} else {
    // REMOTE SERVER - Update these with your actual credentials
    $server   = "localhost";      // Usually 'localhost' on shared hosting
    $username = "appuser";        // Your database username
    $password = "FarunovaPass@2025";    // Your database password
    $db       = "farunova_ecommerce";   // Your database name
}

// Create connection
$conn = new mysqli($server, $username, $password, $db);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // Log error to file
    error_log("FARUNOVA Database connection failed: " . $conn->connect_error);

    // For debugging, create a temporary error file
    $error_msg = "Database Connection Error: " . $conn->connect_error . "\n";
    $error_msg .= "Server: $server\n";
    $error_msg .= "Username: $username\n";
    $error_msg .= "Database: $db\n";
    $error_msg .= "Is Local: " . ($is_local ? 'Yes' : 'No') . "\n";
    $error_msg .= "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n";

    // Write to temporary debug file
    file_put_contents(__DIR__ . '/db_error.txt', $error_msg);

    // Show error page instead of die
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #fee; }
            .error-box { background: #fdd; border: 2px solid #f00; padding: 20px; border-radius: 5px; }
            h1 { color: #c00; }
            code { background: #f0f0f0; padding: 2px 5px; }
            p { line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h1>⚠️ Database Connection Error</h1>
            <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
            <p><strong>Please contact the administrator.</strong></p>
            <hr>
            <details>
                <summary>Debug Information</summary>
                <pre>" . htmlspecialchars($error_msg) . "</pre>
                <p><small>A debug file has been created. Contact your hosting provider with this information.</small></p>
            </details>
        </div>
    </body>
    </html>
    ");
}

// Define base URL for the application
// Auto-detect protocol and host for flexibility
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// For production, uncomment one of these:
// define('BASE_URL', 'https://farunova.com/');
// define('BASE_URL', 'https://www.farunova.com/');
// define('BASE_URL', 'http://40.127.11.133/');

// Auto-detect (will use whatever protocol/host the current request is using)
define('BASE_URL', $protocol . $host . '/');

// Enforce HTTPS in production (uncomment when SSL is set up)
// if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
//     if (strpos($_SERVER['HTTP_HOST'], 'farunova.com') !== false) {
//         $secure_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//         header('Location: ' . $secure_url);
//         exit();
//     }
// }

// Include library files with error handling
$library_files = [
    'security.php',
    'lib/Logger.php',
    'lib/Cache.php',
    'lib/ErrorHandler.php',
    'lib/Database.php',
    'lib/Validator.php',
    'lib/Helpers.php'
];

$missing_files = [];
$failed_files = [];

foreach ($library_files as $lib_file) {
    $file_path = __DIR__ . '/' . $lib_file;

    if (!file_exists($file_path)) {
        $missing_files[] = $lib_file;
        error_log("FARUNOVA: Missing library file: " . $lib_file);
    } else {
        // Try to include the file
        try {
            require_once($file_path);
        } catch (Exception $e) {
            $failed_files[] = ['file' => $lib_file, 'error' => $e->getMessage()];
            error_log("FARUNOVA: Failed to include $lib_file - " . $e->getMessage());
        }
    }
}

// Store errors in session for debugging
if (!empty($missing_files) || !empty($failed_files)) {
    $_SESSION['library_errors'] = [
        'missing' => $missing_files,
        'failed' => $failed_files
    ];
}

// Initialize components only if they were loaded
if (class_exists('Logger')) {
    $logger = new Logger();
} else {
    $logger = null;
}

if (class_exists('ErrorHandler')) {
    ErrorHandler::init($logger);
}

if (class_exists('CacheManager')) {
    $cache = new CacheManager();
} else {
    $cache = null;
}

// Add security headers - use conditional check
if (function_exists('addSecurityHeaders')) {
    addSecurityHeaders();
}

// Set session configuration for security
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '3600');

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) { // 10 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
