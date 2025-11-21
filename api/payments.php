<?php

/**
 * FARUNOVA Payment API
 * Handles M-Pesa payment processing, status queries, and callbacks
 * 
 * @version 1.0
 */

session_start();
include("../connection.php");

// Set JSON response header
header('Content-Type: application/json');

// Require M-Pesa libraries
require_once(__DIR__ . '/../lib/MpesaConfig.php');
require_once(__DIR__ . '/../lib/MpesaAuth.php');
require_once(__DIR__ . '/../lib/MpesaPayment.php');

// Get action parameter
$action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');

// Initialize M-Pesa
$mpesa = new MpesaPayment(new MpesaAuth(), $logger, $db ?? null);

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {

        // ============================================
        // INITIATE PAYMENT - STK Push
        // ============================================
        case 'initiate':
            $response = handleInitiatePayment();
            break;

        // ============================================
        // QUERY PAYMENT STATUS
        // ============================================
        case 'query':
            $response = handleQueryPayment();
            break;

        // ============================================
        // GENERATE QR CODE
        // ============================================
        case 'qr':
            $response = handleGenerateQr();
            break;

        // ============================================
        // M-PESA CALLBACK - Payment confirmation
        // ============================================
        case 'callback':
            $response = handleCallback();
            break;

        // ============================================
        // GET PAYMENT STATUS - For frontend polling
        // ============================================
        case 'status':
            $response = handleGetStatus();
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];

    if ($logger) {
        $logger->error('Payment API error', ['action' => $action, 'error' => $e->getMessage()]);
    }
}

echo json_encode($response);
exit;

// ============================================
// HANDLER FUNCTIONS
// ============================================

/**
 * Handle payment initiation
 */
function handleInitiatePayment()
{
    global $mpesa, $logger;

    // Validate user is logged in
    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        return ['success' => false, 'message' => 'You must be logged in to make payments'];
    }

    // Get request data
    $orderId = (int)($_POST['orderId'] ?? 0);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    // Validate inputs
    if (!$orderId || !$phone || $amount <= 0) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Missing required fields: orderId, phone, amount'];
    }

    // Verify order exists and belongs to user
    $stmt = $GLOBALS['conn']->prepare("SELECT id, totalPrice FROM orders WHERE id = ? AND userId = ?");
    $stmt->bind_param("ii", $orderId, $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        return ['success' => false, 'message' => 'Order not found'];
    }

    // Verify amount matches order total
    if (abs($amount - $order['totalPrice']) > 0.01) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Amount does not match order total'];
    }

    // Initiate M-Pesa payment
    $result = $mpesa->initiatePayment($phone, $amount, 'ORDER-' . $orderId, 'FARUNOVA Order #' . $orderId);

    if (!$result['success']) {
        http_response_code(400);
        return $result;
    }

    // Save payment record to database
    $checkoutRequestID = $result['checkoutRequestID'];
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO payments (orderId, userId, paymentMethod, amount, checkoutRequestID, phoneNumber, status)
        VALUES (?, ?, 'mpesa', ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iidss", $orderId, $_SESSION['id'], $amount, $checkoutRequestID, $phone);
    $stmt->execute();
    $paymentId = $stmt->insert_id;
    $stmt->close();

    // Log the transaction
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO payment_transactions (paymentId, transactionType, status, resultCode, apiResponse)
        VALUES (?, 'initiate', 'pending', ?, ?)
    ");
    $resultCode = $result['responseCode'] ?? null;
    $apiResponse = json_encode($result);
    $stmt->bind_param("iss", $paymentId, $resultCode, $apiResponse);
    $stmt->execute();
    $stmt->close();

    if ($logger) {
        $logger->info('Payment initiated', [
            'orderId' => $orderId,
            'amount' => $amount,
            'checkoutRequestID' => $checkoutRequestID
        ]);
    }

    return [
        'success' => true,
        'message' => 'Payment initiated. Please complete the M-Pesa transaction.',
        'checkoutRequestID' => $checkoutRequestID,
        'paymentId' => $paymentId,
        'amount' => $amount,
        'phone' => $phone
    ];
}

/**
 * Handle payment status query
 */
function handleQueryPayment()
{
    global $mpesa, $logger;

    // Get checkout request ID
    $checkoutRequestID = sanitizeInput($_POST['checkoutRequestID'] ?? $_GET['checkoutRequestID'] ?? '');

    if (!$checkoutRequestID) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Checkout request ID is required'];
    }

    // Query payment status from M-Pesa
    $result = $mpesa->queryPaymentStatus($checkoutRequestID);

    // Update payment record if query successful
    if ($result['success']) {
        $stmt = $GLOBALS['conn']->prepare("
            UPDATE payments 
            SET status = 'completed', completedAt = NOW()
            WHERE checkoutRequestID = ?
        ");
        $stmt->bind_param("s", $checkoutRequestID);
        $stmt->execute();
        $stmt->close();
    }

    // Log the query
    $stmt = $GLOBALS['conn']->prepare("
        INSERT INTO payment_transactions (paymentId, transactionType, status, resultCode)
        SELECT id, 'query', ?, ? FROM payments WHERE checkoutRequestID = ?
    ");
    $status = $result['success'] ? 'success' : 'failed';
    $resultCode = $result['resultCode'] ?? null;
    $stmt->bind_param("sis", $status, $resultCode, $checkoutRequestID);
    $stmt->execute();
    $stmt->close();

    return [
        'success' => $result['success'],
        'message' => $result['userMessage'] ?? 'Payment query completed',
        'resultCode' => $result['resultCode'],
        'checkoutRequestID' => $checkoutRequestID,
        'response' => $result['response'] ?? []
    ];
}

/**
 * Handle QR code generation
 */
function handleGenerateQr()
{
    global $mpesa;

    // Get request data
    $refNo = sanitizeInput($_POST['refNo'] ?? 'QR-' . time());
    $amount = (float)($_POST['amount'] ?? 0);
    $size = sanitizeInput($_POST['size'] ?? '300');

    // Generate QR code
    $result = $mpesa->generateQrCode([
        'MerchantName' => 'FARUNOVA',
        'RefNo' => $refNo,
        'Amount' => $amount,
        'TrxCode' => 'BG',
        'Size' => $size
    ]);

    return $result;
}

/**
 * Handle M-Pesa callback (STK Push notification)
 */
function handleCallback()
{
    global $mpesa, $logger;

    // Get callback body
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        return ['success' => false, 'message' => 'No callback data received'];
    }

    // Process callback
    $result = $mpesa->processCallback($input);

    if (!$result['success']) {
        http_response_code(400);
        return $result;
    }

    // Extract callback data
    $checkoutRequestID = $result['checkoutRequestID'];
    $resultCode = $result['resultCode'];
    $mpesaCode = $result['mpesaCode'];

    try {
        // Get payment record
        $stmt = $GLOBALS['conn']->prepare("SELECT id, orderId, userId FROM payments WHERE checkoutRequestID = ?");
        $stmt->bind_param("s", $checkoutRequestID);
        $stmt->execute();
        $paymentResult = $stmt->get_result();
        $payment = $paymentResult->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            if ($logger) {
                $logger->warning('Callback received for unknown payment', ['checkoutRequestID' => $checkoutRequestID]);
            }
            return ['success' => false, 'message' => 'Payment not found'];
        }

        $paymentId = $payment['id'];
        $orderId = $payment['orderId'];

        // Update payment record
        $paymentStatus = ($resultCode === 0) ? 'completed' : 'failed';

        $stmt = $GLOBALS['conn']->prepare("
            UPDATE payments 
            SET status = ?, resultCode = ?, resultDescription = ?, 
                mpesaReceiptCode = ?, mpesaTransactionDate = NOW(), completedAt = NOW()
            WHERE id = ?
        ");
        $resultDesc = $result['resultDescription'] ?? '';
        $stmt->bind_param("sissi", $paymentStatus, $resultCode, $resultDesc, $mpesaCode, $paymentId);
        $stmt->execute();
        $stmt->close();

        // Update order status if payment successful
        if ($resultCode === 0) {
            $stmt = $GLOBALS['conn']->prepare("
                UPDATE orders 
                SET paymentStatus = 'completed', transactionId = ?, status = 'confirmed'
                WHERE id = ?
            ");
            $stmt->bind_param("si", $mpesaCode, $orderId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $GLOBALS['conn']->prepare("
                UPDATE orders 
                SET paymentStatus = 'failed'
                WHERE id = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();
        }

        // Log transaction
        $stmt = $GLOBALS['conn']->prepare("
            INSERT INTO payment_transactions (paymentId, transactionType, status, resultCode, apiResponse)
            VALUES (?, 'callback', ?, ?, ?)
        ");
        $txnStatus = $paymentStatus;
        $apiResponse = json_encode($input);
        $stmt->bind_param("isls", $paymentId, $txnStatus, $resultCode, $apiResponse);
        $stmt->execute();
        $stmt->close();

        // Log success
        if ($logger) {
            $logger->userAction('payment_completed', [
                'orderId' => $orderId,
                'paymentId' => $paymentId,
                'mpesaCode' => $mpesaCode,
                'status' => $paymentStatus
            ]);
        }

        return [
            'success' => true,
            'message' => 'Callback processed successfully',
            'paymentId' => $paymentId,
            'orderId' => $orderId,
            'paymentStatus' => $paymentStatus
        ];
    } catch (Exception $e) {
        if ($logger) {
            $logger->error('Callback processing error', ['error' => $e->getMessage()]);
        }

        http_response_code(500);
        return ['success' => false, 'message' => 'Error processing callback: ' . $e->getMessage()];
    }
}

/**
 * Get payment status for frontend
 */
function handleGetStatus()
{
    // Get payment ID or checkout request ID
    $paymentId = (int)($_GET['paymentId'] ?? 0);
    $checkoutRequestID = sanitizeInput($_GET['checkoutRequestID'] ?? '');

    if (!$paymentId && !$checkoutRequestID) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Payment ID or Checkout Request ID required'];
    }

    // Query payment status
    if ($paymentId) {
        $stmt = $GLOBALS['conn']->prepare("
            SELECT id, orderId, amount, status, mpesaReceiptCode, completedAt 
            FROM payments 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $paymentId);
    } else {
        $stmt = $GLOBALS['conn']->prepare("
            SELECT id, orderId, amount, status, mpesaReceiptCode, completedAt 
            FROM payments 
            WHERE checkoutRequestID = ?
        ");
        $stmt->bind_param("s", $checkoutRequestID);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        http_response_code(404);
        return ['success' => false, 'message' => 'Payment not found'];
    }

    return [
        'success' => true,
        'payment' => [
            'id' => $payment['id'],
            'orderId' => $payment['orderId'],
            'amount' => $payment['amount'],
            'status' => $payment['status'],
            'mpesaCode' => $payment['mpesaReceiptCode'],
            'completedAt' => $payment['completedAt']
        ]
    ];
}
