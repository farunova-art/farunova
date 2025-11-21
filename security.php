<?php

/**
 * FARUNOVA Security Helpers
 * Provides security functions for CSRF protection, input validation, and rate limiting
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token
 * Creates a unique token for session-based CSRF protection
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * Validates the CSRF token from form submission
 *
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token = null)
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Token for HTML forms
 * Returns HTML input field with CSRF token
 *
 * @return string HTML input field
 */
function csrfTokenField()
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate Email Format
 *
 * @param string $email Email to validate
 * @return bool True if valid email format
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Password Strength
 * Password must be at least 8 characters with mix of uppercase, lowercase, and numbers
 *
 * @param string $password Password to validate
 * @return array Array with 'valid' bool and 'errors' array
 */
function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    return [
        'valid' => count($errors) === 0,
        'errors' => $errors
    ];
}

/**
 * Validate Username Format
 * Username must be 3-20 characters, alphanumeric with underscores allowed
 *
 * @param string $username Username to validate
 * @return bool True if valid format
 */
function isValidUsername($username)
{
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

/**
 * Rate Limiting Check
 * Prevents brute force attacks by limiting attempts per IP/identifier
 *
 * @param string $identifier Unique identifier (email, IP, etc)
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return array Array with 'allowed' bool and 'remaining' attempts count
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900)
{
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();
    $key = 'limit_' . hash('sha256', $identifier);

    // Clean up old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function ($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }

    $attempts = count($_SESSION['rate_limit'][$key]);
    $allowed = $attempts < $maxAttempts;

    if ($allowed) {
        $_SESSION['rate_limit'][$key][] = $now;
    }

    return [
        'allowed' => $allowed,
        'remaining' => max(0, $maxAttempts - $attempts),
        'attempts' => $attempts
    ];
}

/**
 * Increment Failed Login Attempts
 *
 * @param string $email Email of failed attempt
 * @return array Rate limit check result
 */
function recordFailedLogin($email)
{
    return checkRateLimit('login_' . $email, 5, 900); // 5 attempts per 15 minutes
}

/**
 * Clear Rate Limit for Identifier
 *
 * @param string $identifier Unique identifier
 */
function clearRateLimit($identifier)
{
    if (isset($_SESSION['rate_limit'])) {
        $key = 'limit_' . hash('sha256', $identifier);
        unset($_SESSION['rate_limit'][$key]);
    }
}

/**
 * Sanitize String Input
 * Removes potentially dangerous characters but preserves legitimate input
 *
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Input Length
 *
 * @param string $input Input to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool True if within range
 */
function isValidLength($input, $min = 1, $max = 255)
{
    $length = strlen($input);
    return $length >= $min && $length <= $max;
}

/**
 * Log Security Event
 * Logs security-related events for monitoring
 *
 * @param string $event Event type (login_failure, sql_error, etc)
 * @param string $details Event details
 * @param string $userEmail User email (optional)
 */
function logSecurityEvent($event, $details, $userEmail = null)
{
    $logDir = __DIR__ . '/logs';

    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'details' => $details,
        'user_email' => $userEmail,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];

    $logMessage = json_encode($logEntry) . PHP_EOL;

    if (is_writable($logDir)) {
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

/**
 * Add Security Headers to Response
 * Should be called early in page load
 */
function addSecurityHeaders()
{
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');

    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; font-src fonts.gstatic.com; img-src 'self' data:;");

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Safely Redirect
 * Prevents open redirect vulnerabilities
 *
 * @param string $url URL to redirect to
 * @param bool $external Allow external URLs
 */
function safeRedirect($url, $external = false)
{
    // Only allow relative URLs or same-origin URLs by default
    if (!$external) {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if ($url === false || parse_url($url, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
            $url = 'index.php';
        }
    }

    header('Location: ' . $url);
    exit();
}

/**
 * Get Client IP Address
 *
 * @return string Client IP address
 */
function getClientIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    // Validate IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'invalid';
    }

    return $ip;
}

/**
 * Create Secure Password Hash
 *
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify Password Hash
 *
 * @param string $password Plain password
 * @param string $hash Password hash to verify against
 * @return bool True if password matches hash
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}
