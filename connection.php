<?php
// Database Configuration - SIMPLE VERSION
session_start();

// Determine server based on hostname
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Azure VM or Production
if (strpos($host, '40.127.11.133') !== false || strpos($host, 'farunova.com') !== false) {
    $server   = "localhost";
    $username = "appuser";
    $password = "FarunovaPass@2025";
    $db       = "farunova_ecommerce";
} else {
    // Local development
    $server   = "localhost";
    $username = "root";
    $password = "";
    $db       = "GROUP1";
}

// Connect to database
$conn = @new mysqli($server, $username, $password, $db);

// Set charset
if ($conn && !$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}

// Define BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
define('BASE_URL', $protocol . $host . '/');
