<?php

/**
 * Error Handler
 * Central error handling for the application
 * 
 * @package FARUNOVA
 * @version 1.0
 */

class ErrorHandler
{
    private static $logger = null;

    /**
     * Initialize error handler
     * 
     * @param Logger|null $logger Logger instance
     * @return void
     */
    public static function init($logger = null)
    {
        self::$logger = $logger;

        // Set error handler
        set_error_handler([self::class, 'handleError']);

        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Set shutdown handler
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error string
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        $errorType = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];

        $type = $errorType[$errno] ?? 'Unknown';

        $context = [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ];

        if (self::$logger) {
            self::$logger->error("[$type] $errstr", $context);
        }

        // Return false to continue with PHP's internal error handling
        return false;
    }

    /**
     * Handle exceptions
     * 
     * @param Throwable $exception Exception instance
     * @return void
     */
    public static function handleException($exception)
    {
        $context = [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        if (self::$logger) {
            self::$logger->error('Exception: ' . $exception->getMessage(), $context);
        }

        // Display error page or JSON based on request type
        if (self::isAjax()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        } else {
            http_response_code(500);
            include 'errors/500.php';
        }

        exit;
    }

    /**
     * Handle fatal errors on shutdown
     * 
     * @return void
     */
    public static function handleShutdown()
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );

            // Display error page
            if (!self::isAjax()) {
                http_response_code(500);
                include 'errors/500.php';
            }
        }
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    private static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
