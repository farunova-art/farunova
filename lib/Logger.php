<?php

/**
 * Logger Class
 * Comprehensive logging system for FARUNOVA
 * 
 * @package FARUNOVA
 * @version 1.0
 */

class Logger
{
    const LOG_ERROR = 'ERROR';
    const LOG_WARNING = 'WARNING';
    const LOG_INFO = 'INFO';
    const LOG_DEBUG = 'DEBUG';

    private $logDir = 'logs/';
    private $maxFileSize = 10485760; // 10 MB

    public function __construct($logDir = 'logs/')
    {
        $this->logDir = $logDir;

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log an error
     * 
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    public function error($message, $context = [])
    {
        $this->log(self::LOG_ERROR, $message, $context);
    }

    /**
     * Log a warning
     * 
     * @param string $message Warning message
     * @param array $context Additional context
     * @return void
     */
    public function warning($message, $context = [])
    {
        $this->log(self::LOG_WARNING, $message, $context);
    }

    /**
     * Log info
     * 
     * @param string $message Info message
     * @param array $context Additional context
     * @return void
     */
    public function info($message, $context = [])
    {
        $this->log(self::LOG_INFO, $message, $context);
    }

    /**
     * Log debug
     * 
     * @param string $message Debug message
     * @param array $context Additional context
     * @return void
     */
    public function debug($message, $context = [])
    {
        $this->log(self::LOG_DEBUG, $message, $context);
    }

    /**
     * Log database query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param float $executionTime Execution time in seconds
     * @return void
     */
    public function query($query, $params = [], $executionTime = 0)
    {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime
        ];

        $this->log('QUERY', 'Database query executed', $context);
    }

    /**
     * Log API request
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param int $statusCode HTTP status code
     * @param float $responseTime Response time in seconds
     * @return void
     */
    public function api($method, $endpoint, $statusCode, $responseTime)
    {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->getClientIP()
        ];

        $this->log('API', $method . ' ' . $endpoint, $context);
    }

    /**
     * Log user action
     * 
     * @param string $action Action name
     * @param string $description Action description
     * @param int|null $userId User ID
     * @return void
     */
    public function userAction($action, $description, $userId = null)
    {
        $context = [
            'action' => $action,
            'user_id' => $userId ?? ($_SESSION['id'] ?? null),
            'username' => $_SESSION['username'] ?? 'guest',
            'ip_address' => $this->getClientIP(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->log('USER_ACTION', $description, $context);
    }

    /**
     * Log security event
     * 
     * @param string $event Event name
     * @param string $description Event description
     * @param array $details Additional details
     * @return void
     */
    public function security($event, $description, $details = [])
    {
        $context = array_merge([
            'event' => $event,
            'user' => $_SESSION['username'] ?? 'guest',
            'user_id' => $_SESSION['id'] ?? null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ], $details);

        $this->log('SECURITY', $description, $context);
    }

    /**
     * Main logging method
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    private function log($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = $this->logDir . strtolower($level) . '.log';

        // Rotate log file if needed
        if (file_exists($logFile) && filesize($logFile) > $this->maxFileSize) {
            $this->rotateLog($logFile);
        }

        // Format log entry
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];

        // Write to log file
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

    /**
     * Rotate log file when it reaches max size
     * 
     * @param string $logFile Path to log file
     * @return void
     */
    private function rotateLog($logFile)
    {
        $timestamp = date('Ymd_His');
        $rotatedFile = str_replace('.log', '_' . $timestamp . '.log', $logFile);
        rename($logFile, $rotatedFile);

        // Delete old rotated logs (keep last 10)
        $pattern = str_replace('.log', '_*.log', $logFile);
        $files = glob($pattern);

        if (count($files) > 10) {
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $filesToDelete = array_slice($files, 0, count($files) - 10);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    /**
     * Get logs for a specific level
     * 
     * @param string $level Log level
     * @param int $limit Number of lines to retrieve
     * @return array Array of log entries
     */
    public function getLogs($level, $limit = 100)
    {
        $logFile = $this->logDir . strtolower($level) . '.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $file = new SplFileObject($logFile, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        // Get last $limit lines
        $lines = [];
        foreach ($file as $line) {
            $lines[] = trim($line);
        }

        $lines = array_slice($lines, -$limit);

        foreach ($lines as $line) {
            if (!empty($line)) {
                $logs[] = json_decode($line, true);
            }
        }

        return $logs;
    }

    /**
     * Search logs
     * 
     * @param string $keyword Keyword to search
     * @param string $level Optional log level filter
     * @param int $limit Number of results
     * @return array Array of matching log entries
     */
    public function search($keyword, $level = null, $limit = 50)
    {
        $results = [];

        $levels = $level ? [$level] : [self::LOG_ERROR, self::LOG_WARNING, self::LOG_INFO, self::LOG_DEBUG];

        foreach ($levels as $lvl) {
            $logs = $this->getLogs($lvl, 1000);

            foreach ($logs as $log) {
                if (
                    stripos($log['message'], $keyword) !== false ||
                    stripos(json_encode($log['context']), $keyword) !== false
                ) {
                    $results[] = $log;
                }
            }
        }

        // Sort by timestamp descending
        usort($results, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($results, 0, $limit);
    }

    /**
     * Clear logs older than specified days
     * 
     * @param int $days Number of days to keep
     * @return int Number of files deleted
     */
    public function clearOldLogs($days = 30)
    {
        $deleted = 0;
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        $files = glob($this->logDir . '*.log');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
