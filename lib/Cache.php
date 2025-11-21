<?php

/**
 * Cache Manager Class
 * Simple caching system for query results and static data
 * 
 * @package FARUNOVA
 * @version 1.0
 */

class CacheManager
{
    private $cacheDir = 'cache/';
    private $defaultTTL = 3600; // 1 hour

    public function __construct($cacheDir = 'cache/')
    {
        $this->cacheDir = $cacheDir;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get value from cache
     * 
     * @param string $key Cache key
     * @return mixed Cached value or null
     */
    public function get($key)
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $data = json_decode(file_get_contents($filename), true);

        // Check if expired
        if ($data['expires'] < time()) {
            unlink($filename);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null)
    {
        $filename = $this->getFilename($key);

        $data = [
            'key' => $key,
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->defaultTTL),
            'created' => date('Y-m-d H:i:s')
        ];

        return file_put_contents($filename, json_encode($data)) !== false;
    }

    /**
     * Delete value from cache
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key)
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool True if exists, false otherwise
     */
    public function exists($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear all cache
     * 
     * @return int Number of files deleted
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear expired cache entries
     * 
     * @return int Number of files deleted
     */
    public function clearExpired()
    {
        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if ($data['expires'] < time()) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get cache stats
     * 
     * @return array Cache statistics
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $expiredCount = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);

            $data = json_decode(file_get_contents($file), true);
            if ($data['expires'] < time()) {
                $expiredCount++;
            }
        }

        return [
            'total_entries' => count($files),
            'expired_entries' => $expiredCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1048576, 2)
        ];
    }

    /**
     * Cache a query result
     * 
     * @param string $key Cache key
     * @param callable $callback Function to execute if not cached
     * @param int $ttl Time to live
     * @return mixed Cached or fresh result
     */
    public function remember($key, $callback, $ttl = null)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Cache multiple values
     * 
     * @param array $items Array of key => value pairs
     * @param int $ttl Time to live
     * @return bool Success status
     */
    public function setMultiple($items, $ttl = null)
    {
        $success = true;

        foreach ($items as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get multiple values
     * 
     * @param array $keys Array of cache keys
     * @return array Array of values (null for missing keys)
     */
    public function getMultiple($keys)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Generate cache filename from key
     * 
     * @param string $key Cache key
     * @return string Filename path
     */
    private function getFilename($key)
    {
        $hash = hash('sha256', $key);
        return $this->cacheDir . $hash . '.cache';
    }

    /**
     * Set default TTL
     * 
     * @param int $ttl Time to live in seconds
     * @return void
     */
    public function setDefaultTTL($ttl)
    {
        $this->defaultTTL = $ttl;
    }
}
