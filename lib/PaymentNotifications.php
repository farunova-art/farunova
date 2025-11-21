<?php

/**
 * FARUNOVA Payment Notifications System
 * Sends email notifications for payment events
 * 
 * @version 1.0
 * @author FARUNOVA Team
 */

class PaymentNotifications
{
    private $db;
    private $logger;
    private $emailFrom = 'support@farunova.com';
    private $emailFromName = 'FARUNOVA Support';

    /**
     * Constructor
     */
    public function __construct($db, $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Send payment confirmation email
     * 
     * @param int $paymentId - Payment ID
     * 
     * @return array
     */
    public function sendPaymentConfirmation($paymentId)
    {
        try {
            // Get payment and order details
            $paymentData = $this->getPaymentData($paymentId);

            if (!$paymentData) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            $email = $paymentData['email'];
            $username = $paymentData['username'];
            $amount = $paymentData['amount'];
            $orderId = $paymentData['orderId'];
            $mpesaCode = $paymentData['mpesaReceiptCode'];

            $subject = 'Payment Confirmation - Order #' . $orderId;
            $body = $this->getPaymentConfirmationTemplate(
                $username,
                $amount,
                $orderId,
                $mpesaCode
            );

            return $this->sendEmail($email, $subject, $body, $paymentId, 'payment_confirmation');
        } catch (Exception $e) {
            $this->logger->error('Error sending payment confirmation: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }

    /**
     * Send payment failed notification
     * 
     * @param int $paymentId - Payment ID
     * @param string $reason - Failure reason
     * 
     * @return array
     */
    public function sendPaymentFailed($paymentId, $reason = 'Unknown error')
    {
        try {
            $paymentData = $this->getPaymentData($paymentId);

            if (!$paymentData) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            $email = $paymentData['email'];
            $username = $paymentData['username'];
            $orderId = $paymentData['orderId'];

            $subject = 'Payment Failed - Order #' . $orderId;
            $body = $this->getPaymentFailedTemplate($username, $orderId, $reason);

            return $this->sendEmail($email, $subject, $body, $paymentId, 'payment_failed');
        } catch (Exception $e) {
            $this->logger->error('Error sending payment failed notification: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }

    /**
     * Send refund notification
     * 
     * @param int $refundId - Refund ID
     * 
     * @return array
     */
    public function sendRefundNotification($refundId)
    {
        try {
            // Get refund details
            $stmt = $this->db->prepare("
                SELECT pr.*, p.orderId, p.userId, u.email, u.username
                FROM payment_refunds pr
                JOIN payments p ON pr.paymentId = p.id
                JOIN users u ON p.userId = u.id
                WHERE pr.id = ?
            ");
            $stmt->bind_param("i", $refundId);
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if (!$refund) {
                return ['success' => false, 'message' => 'Refund not found'];
            }

            $email = $refund['email'];
            $username = $refund['username'];
            $amount = $refund['refundAmount'];
            $orderId = $refund['orderId'];
            $status = $refund['status'];

            $subject = 'Refund Notification - Order #' . $orderId;
            $body = $this->getRefundTemplate($username, $amount, $orderId, $status);

            return $this->sendEmail($email, $subject, $body, $refund['paymentId'], 'refund_notification');
        } catch (Exception $e) {
            $this->logger->error('Error sending refund notification: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }

    /**
     * Send invoice email
     * 
     * @param int $orderId - Order ID
     * @param string $email - Customer email
     * 
     * @return array
     */
    public function sendInvoice($orderId, $email = null)
    {
        try {
            // Get order details
            $stmt = $this->db->prepare("
                SELECT o.*, u.username, u.email as userEmail
                FROM orders o
                JOIN users u ON o.userId = u.id
                WHERE o.id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $recipientEmail = $email ?? $order['userEmail'];
            $username = $order['username'];

            $subject = 'Your Invoice - Order #' . $order['orderId'];
            $body = $this->getInvoiceTemplate($username, $orderId);

            return $this->sendEmail($recipientEmail, $subject, $body, null, 'invoice_delivery');
        } catch (Exception $e) {
            $this->logger->error('Error sending invoice: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }

    /**
     * Send payment receipt email
     * 
     * @param int $paymentId - Payment ID
     * 
     * @return array
     */
    public function sendPaymentReceipt($paymentId)
    {
        try {
            $paymentData = $this->getPaymentData($paymentId);

            if (!$paymentData) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            $email = $paymentData['email'];
            $username = $paymentData['username'];
            $amount = $paymentData['amount'];
            $mpesaCode = $paymentData['mpesaReceiptCode'];
            $completedAt = $paymentData['completedAt'];

            $subject = 'Payment Receipt - FARUNOVA';
            $body = $this->getReceiptTemplate($username, $amount, $mpesaCode, $completedAt);

            return $this->sendEmail($email, $subject, $body, $paymentId, 'payment_receipt');
        } catch (Exception $e) {
            $this->logger->error('Error sending payment receipt: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Error sending email'];
        }
    }

    /**
     * Get payment data
     */
    private function getPaymentData($paymentId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.email, u.username, o.orderId
                FROM payments p
                JOIN users u ON p.userId = u.id
                LEFT JOIN orders o ON p.orderId = o.id
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            $this->logger->error('Error getting payment data: ' . $e->getMessage(), 'notifications');
            return null;
        }
    }

    /**
     * Send email
     */
    private function sendEmail($to, $subject, $body, $paymentId, $type)
    {
        try {
            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }

            // Prepare headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . $this->emailFromName . " <" . $this->emailFrom . ">\r\n";
            $headers .= "Reply-To: " . $this->emailFrom . "\r\n";

            // Send email
            $sent = mail($to, $subject, $body, $headers);

            if ($sent) {
                // Log notification
                $this->logNotification($paymentId, $type, $to, 'sent');

                $this->logger->log('Notification sent', [
                    'type' => $type,
                    'email' => $to,
                    'paymentId' => $paymentId
                ], 'notifications');

                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'email' => $to
                ];
            } else {
                $this->logNotification($paymentId, $type, $to, 'failed');
                return [
                    'success' => false,
                    'message' => 'Failed to send email'
                ];
            }
        } catch (Exception $e) {
            $this->logger->error('Error sending email: ' . $e->getMessage(), 'notifications');
            return ['success' => false, 'message' => 'Email error'];
        }
    }

    /**
     * Log notification
     */
    private function logNotification($paymentId, $type, $email, $status)
    {
        try {
            // Create log entry
            $stmt = $this->db->prepare("
                INSERT INTO payment_notifications (paymentId, notificationType, recipientEmail, status)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $paymentId, $type, $email, $status);
            $stmt->execute();
        } catch (Exception $e) {
            $this->logger->error('Error logging notification: ' . $e->getMessage(), 'notifications');
        }
    }

    /**
     * Email templates
     */
    private function getPaymentConfirmationTemplate($username, $amount, $orderId, $mpesaCode)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
        .amount { font-size: 1.5em; font-weight: bold; color: #28a745; }
        .info-box { background-color: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Payment Confirmed</h1>
        </div>
        <div class="content">
            <p>Hi <strong>{$username}</strong>,</p>
            
            <p>Thank you! Your payment has been successfully received.</p>
            
            <div class="info-box">
                <p><strong>Order #:</strong> {$orderId}</p>
                <p><strong>Amount:</strong> <span class="amount">KES {$amount}</span></p>
                <p><strong>M-Pesa Code:</strong> {$mpesaCode}</p>
            </div>
            
            <p>Your order is now being processed and will be shipped soon. You can track your order status on your account dashboard.</p>
            
            <p>If you have any questions, please don't hesitate to contact our support team.</p>
            
            <p>Thank you for shopping with FARUNOVA!</p>
        </div>
        <div class="footer">
            <p>FARUNOVA - Premium Clothing Store</p>
            <p>Email: support@farunova.com | Phone: +254 700 000 000</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getPaymentFailedTemplate($username, $orderId, $reason)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
        .error-box { background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✗ Payment Failed</h1>
        </div>
        <div class="content">
            <p>Hi <strong>{$username}</strong>,</p>
            
            <p>Unfortunately, your payment for Order #{$orderId} could not be processed.</p>
            
            <div class="error-box">
                <p><strong>Reason:</strong> {$reason}</p>
            </div>
            
            <p>Please try again or contact our support team for assistance:</p>
            <ul>
                <li>Email: support@farunova.com</li>
                <li>Phone: +254 700 000 000</li>
            </ul>
            
            <p>We're here to help!</p>
        </div>
        <div class="footer">
            <p>FARUNOVA - Premium Clothing Store</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getRefundTemplate($username, $amount, $orderId, $status)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #17a2b8; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
        .info-box { background-color: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Refund Notification</h1>
        </div>
        <div class="content">
            <p>Hi <strong>{$username}</strong>,</p>
            
            <p>Your refund request for Order #{$orderId} has been processed.</p>
            
            <div class="info-box">
                <p><strong>Refund Amount:</strong> KES {$amount}</p>
                <p><strong>Status:</strong> {$status}</p>
            </div>
            
            <p>The refund has been initiated and should appear in your M-Pesa account within 1-3 business days.</p>
            
            <p>Thank you for your understanding. If you have any questions, please contact us at support@farunova.com</p>
        </div>
        <div class="footer">
            <p>FARUNOVA - Premium Clothing Store</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getInvoiceTemplate($username, $orderId)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
        .action-button { display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Invoice</h1>
        </div>
        <div class="content">
            <p>Hi <strong>{$username}</strong>,</p>
            
            <p>Your invoice for Order #{$orderId} is ready!</p>
            
            <p>You can download or view your invoice from your account dashboard or by clicking the button below:</p>
            
            <a href="https://farunova.com/order_confirmation.php?orderId={$orderId}" class="action-button">View Invoice</a>
            
            <p>If you need any assistance, please don't hesitate to reach out to our support team.</p>
            
            <p>Thank you for your purchase!</p>
        </div>
        <div class="footer">
            <p>FARUNOVA - Premium Clothing Store</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getReceiptTemplate($username, $amount, $mpesaCode, $completedAt)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .header h1 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #666; }
        .receipt-box { background-color: white; padding: 20px; border: 2px solid #28a745; margin: 20px 0; }
        .receipt-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Receipt</h1>
        </div>
        <div class="content">
            <p>Hi <strong>{$username}</strong>,</p>
            
            <p>Thank you for your payment. Here is your receipt:</p>
            
            <div class="receipt-box">
                <div class="receipt-item">
                    <span>Amount Paid:</span>
                    <strong>KES {$amount}</strong>
                </div>
                <div class="receipt-item">
                    <span>M-Pesa Code:</span>
                    <strong>{$mpesaCode}</strong>
                </div>
                <div class="receipt-item">
                    <span>Date & Time:</span>
                    <strong>{$completedAt}</strong>
                </div>
            </div>
            
            <p>Please keep this receipt for your records. If you need to file a refund or have any issues, you can reference the M-Pesa code above.</p>
            
            <p>Best regards,<br>FARUNOVA Support Team</p>
        </div>
        <div class="footer">
            <p>FARUNOVA - Premium Clothing Store</p>
            <p>Email: support@farunova.com | Phone: +254 700 000 000</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
