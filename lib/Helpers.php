<?php

/**
 * Helper Functions Library
 * Common utility functions for FARUNOVA application
 * 
 * @package FARUNOVA
 * @version 1.0
 */

/**
 * Format currency for display
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code (default: KES)
 * @return string Formatted currency string
 */
function formatCurrency($amount, $currency = 'KES')
{
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date for display
 * 
 * @param string $date Date string from database
 * @param string $format Format string (default: 'M d, Y')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y')
{
    if (empty($date)) {
        return '';
    }

    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Get time ago string (e.g., "2 hours ago")
 * 
 * @param string $date Date string from database
 * @return string Relative time string
 */
function getTimeAgo($date)
{
    if (empty($date)) {
        return '';
    }

    $dateObj = new DateTime($date);
    $now = new DateTime();
    $interval = $now->diff($dateObj);

    if ($interval->y > 0) {
        return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    }
    if ($interval->m > 0) {
        return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    }
    if ($interval->d > 0) {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    }
    if ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    }
    if ($interval->i > 0) {
        return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    }
    return 'Just now';
}

/**
 * Truncate text to specified length with ellipsis
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix (default: '...')
 * @return string Truncated text
 */
function truncate($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn()
{
    return isset($_SESSION['id']) && isset($_SESSION['username']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect user to login if not authenticated
 * 
 * @return void
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect user if not admin
 * 
 * @return void
 */
function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Get order status badge HTML
 * 
 * @param string $status Order status
 * @return string HTML badge
 */
function getStatusBadge($status)
{
    $badges = [
        'pending' => '<span class="badge bg-secondary">Pending</span>',
        'confirmed' => '<span class="badge bg-warning">Confirmed</span>',
        'shipped' => '<span class="badge bg-info">Shipped</span>',
        'delivered' => '<span class="badge bg-success">Delivered</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
    ];

    return $badges[$status] ?? '<span class="badge bg-primary">' . ucfirst($status) . '</span>';
}

/**
 * Get rating stars HTML
 * 
 * @param float $rating Rating value (1-5)
 * @param int $totalReviews Total number of reviews
 * @return string HTML with stars
 */
function getRatingStars($rating, $totalReviews = 0)
{
    $html = '<div class="rating-stars">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($i <= $rating) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }

    $html .= ' <span class="rating-value">' . number_format($rating, 1) . '</span>';

    if ($totalReviews > 0) {
        $html .= ' <span class="rating-count">(' . $totalReviews . ' review' . ($totalReviews > 1 ? 's' : '') . ')</span>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Get status color class
 * 
 * @param string $status Status value
 * @return string Bootstrap color class
 */
function getStatusColor($status)
{
    $colors = [
        'pending' => 'secondary',
        'confirmed' => 'warning',
        'shipped' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    return $colors[$status] ?? 'primary';
}

/**
 * Build pagination links
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination
 * @return string HTML pagination controls
 */
function buildPagination($currentPage, $totalPages, $baseUrl)
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">First</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Previous</a></li>';
    }

    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ($endPage < $totalPages) {
        $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Next</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">Last</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Check if product is in stock
 * 
 * @param int $stock Stock quantity
 * @return bool True if in stock, false otherwise
 */
function isInStock($stock)
{
    return $stock > 0;
}

/**
 * Get stock status badge
 * 
 * @param int $stock Stock quantity
 * @return string Status badge HTML
 */
function getStockBadge($stock)
{
    if ($stock > 10) {
        return '<span class="badge bg-success">In Stock</span>';
    } elseif ($stock > 0) {
        return '<span class="badge bg-warning">Low Stock</span>';
    } else {
        return '<span class="badge bg-danger">Out of Stock</span>';
    }
}

/**
 * Calculate discount savings
 * 
 * @param float $originalPrice Original price
 * @param float $discountedPrice Discounted price
 * @return float Savings amount
 */
function calculateSavings($originalPrice, $discountedPrice)
{
    return $originalPrice - $discountedPrice;
}

/**
 * Calculate discount percentage
 * 
 * @param float $originalPrice Original price
 * @param float $discountedPrice Discounted price
 * @return int Discount percentage
 */
function calculateDiscountPercent($originalPrice, $discountedPrice)
{
    if ($originalPrice == 0) {
        return 0;
    }

    return round((($originalPrice - $discountedPrice) / $originalPrice) * 100);
}

/**
 * Highlight search terms in text
 * 
 * @param string $text Text to highlight in
 * @param string $searchTerm Term to highlight
 * @return string Text with highlighted terms
 */
function highlightSearchTerm($text, $searchTerm)
{
    if (empty($searchTerm)) {
        return $text;
    }

    $pattern = '/' . preg_quote($searchTerm, '/') . '/i';
    return preg_replace($pattern, '<mark>$0</mark>', $text);
}

/**
 * Get user avatar initials
 * 
 * @param string $username Username
 * @return string Initials
 */
function getInitials($username)
{
    $parts = explode(' ', trim($username));
    $initials = '';

    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return substr($initials, 0, 2) ?: 'U';
}

/**
 * Generate a random color
 * 
 * @return string Hex color code
 */
function getRandomColor()
{
    $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    return $colors[array_rand($colors)];
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function isValidPhone($phone)
{
    return preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $phone);
}

/**
 * Convert camelCase to Title Case
 * 
 * @param string $str String to convert
 * @return string Title case string
 */
function camelToTitle($str)
{
    return preg_replace('/([a-z])([A-Z])/', '$1 $2', ucfirst($str));
}

/**
 * Get percentage value
 * 
 * @param float $value Value
 * @param float $total Total
 * @return float Percentage (0-100)
 */
function getPercentage($value, $total)
{
    if ($total == 0) {
        return 0;
    }

    return round(($value / $total) * 100, 2);
}

/**
 * Check if file upload is valid
 * 
 * @param array $file File from $_FILES
 * @param array $allowedTypes Allowed mime types
 * @param int $maxSize Maximum file size in bytes
 * @return bool|string True if valid, error message if invalid
 */
function isValidFileUpload($file, $allowedTypes = [], $maxSize = 5242880)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload error occurred';
    }

    if ($file['size'] > $maxSize) {
        return 'File size exceeds maximum allowed';
    }

    if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
        return 'File type not allowed';
    }

    return true;
}
