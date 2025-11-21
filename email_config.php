<?php

/**
 * FARUNOVA Email Configuration
 * Handles all email-related settings and SMTP configuration
 */

// Email Configuration
define('EMAIL_FROM_ADDRESS', 'noreply@farunova.com');
define('EMAIL_FROM_NAME', 'FARUNOVA - Authentic Clothing Store');

// SMTP Configuration (Update these with your mail server details)
// For development/testing, you can use a free service like Mailtrap
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_ENABLED', getenv('SMTP_ENABLED') ?: false);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/email_templates/');

/**
 * Send Email Helper Function
 * Uses PHPMailer or native mail() function based on configuration
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML email body
 * @param string $textBody Plain text email body (optional)
 * @param array $attachments Array of file paths to attach (optional)
 * @return bool True if email sent successfully
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '', $attachments = [])
{
    try {
        // Log email attempt
        logSecurityEvent('email_send_attempt', "Email to: $to, Subject: $subject", $to);

        // Try using PHPMailer if available
        if (function_exists('mail')) {
            return sendEmailWithPHP($to, $subject, $htmlBody, $textBody);
        }

        return false;
    } catch (Exception $e) {
        logSecurityEvent('email_send_failure', $e->getMessage(), $to);
        return false;
    }
}

/**
 * Send Email using PHP mail() function
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML content
 * @param string $textBody Plain text content
 * @return bool Success status
 */
function sendEmailWithPHP($to, $subject, $htmlBody, $textBody = '')
{
    $headers = [
        'From' => EMAIL_FROM_ADDRESS,
        'Reply-To' => EMAIL_FROM_ADDRESS,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'FARUNOVA',
    ];

    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= $key . ': ' . $value . "\r\n";
    }

    return mail($to, $subject, $htmlBody, $headerString);
}

/**
 * Load Email Template
 * Loads and returns email template content with variable substitution
 *
 * @param string $templateName Template file name (without .php)
 * @param array $variables Variables to pass to template
 * @return string Rendered template HTML
 */
function loadEmailTemplate($templateName, $variables = [])
{
    $templateFile = EMAIL_TEMPLATES_DIR . $templateName . '.php';

    if (!file_exists($templateFile)) {
        logSecurityEvent('email_template_missing', "Template: $templateName", null);
        return '';
    }

    // Extract variables for use in template
    extract($variables, EXTR_SKIP);

    // Start output buffering
    ob_start();
    include $templateFile;
    $content = ob_get_clean();

    return $content;
}

/**
 * Send Order Confirmation Email
 *
 * @param array $order Order data
 * @param array $orderItems Order items data
 * @param string $email Customer email
 * @return bool Success status
 */
function sendOrderConfirmationEmail($order, $orderItems, $email)
{
    $variables = [
        'order' => $order,
        'items' => $orderItems,
        'total' => $order['totalAmount'],
        'orderId' => $order['orderId'],
        'date' => date('F d, Y', strtotime($order['createdAt'])),
    ];

    $htmlBody = loadEmailTemplate('order_confirmation', $variables);
    $subject = 'Order Confirmation - ' . $order['orderId'];

    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send Order Status Update Email
 *
 * @param array $order Order data
 * @param string $email Customer email
 * @return bool Success status
 */
function sendOrderStatusUpdateEmail($order, $email)
{
    $statusMessages = [
        'pending' => 'Your order is awaiting processing',
        'processing' => 'Your order is being prepared for shipment',
        'shipped' => 'Your order has been shipped',
        'delivered' => 'Your order has been delivered',
        'cancelled' => 'Your order has been cancelled',
    ];

    $variables = [
        'order' => $order,
        'status' => $order['status'],
        'statusMessage' => $statusMessages[$order['status']] ?? 'Status updated',
        'orderId' => $order['orderId'],
        'trackingNumber' => $order['trackingNumber'] ?? 'N/A',
    ];

    $htmlBody = loadEmailTemplate('order_status_update', $variables);
    $subject = 'Order Status Update - ' . $order['orderId'];

    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send Welcome Email
 *
 * @param string $username User's username
 * @param string $email User's email
 * @return bool Success status
 */
function sendWelcomeEmail($username, $email)
{
    $variables = [
        'username' => htmlspecialchars($username),
        'baseUrl' => BASE_URL,
    ];

    $htmlBody = loadEmailTemplate('welcome', $variables);
    $subject = 'Welcome to FARUNOVA - ' . EMAIL_FROM_NAME;

    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send Contact Form Response Email
 *
 * @param string $name Contact name
 * @param string $email Contact email
 * @param string $message Original message
 * @return bool Success status
 */
function sendContactReplyEmail($name, $email, $message)
{
    $variables = [
        'name' => htmlspecialchars($name),
        'message' => htmlspecialchars($message),
        'baseUrl' => BASE_URL,
    ];

    $htmlBody = loadEmailTemplate('contact_reply', $variables);
    $subject = 'We Received Your Message - ' . EMAIL_FROM_NAME;

    return sendEmail($email, $subject, $htmlBody);
}

/**
 * Send Admin Notification Email
 * Notifies admin of new orders or system events
 *
 * @param string $type Notification type (new_order, low_stock, etc)
 * @param array $data Notification data
 * @param string $adminEmail Admin email address
 * @return bool Success status
 */
function sendAdminNotificationEmail($type, $data, $adminEmail = 'admin@farunova.com')
{
    $templates = [
        'new_order' => 'admin_new_order',
        'low_stock' => 'admin_low_stock',
        'contact' => 'admin_contact',
    ];

    $templateName = $templates[$type] ?? null;
    if (!$templateName) {
        return false;
    }

    $htmlBody = loadEmailTemplate($templateName, $data);
    $subject = '[FARUNOVA ADMIN] ' . ucwords(str_replace('_', ' ', $type));

    return sendEmail($adminEmail, $subject, $htmlBody);
}
