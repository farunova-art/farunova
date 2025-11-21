<?php

/**
 * FARUNOVA Invoice API Endpoints
 * Handles invoice generation, download, and email
 * 
 * @version 1.0
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/lib/InvoiceGenerator.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'generate';
$userId = $_SESSION['userId'];
$isAdmin = $_SESSION['isAdmin'] ?? false;

// Initialize InvoiceGenerator
$invoiceGenerator = new InvoiceGenerator($conn, $logger);

try {
    switch ($action) {
        case 'generate':
            handleGenerateInvoice();
            break;

        case 'download':
            handleDownloadInvoice();
            break;

        case 'email':
            handleEmailInvoice();
            break;

        case 'list':
            handleListInvoices();
            break;

        case 'get':
            handleGetInvoice();
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    $logger->error('Invoice API error: ' . $e->getMessage(), 'invoices');
}

/**
 * Handle invoice generation
 * POST /api/invoices.php?action=generate
 * 
 * Required:
 * - orderId: int - Order ID to generate invoice for
 */
function handleGenerateInvoice()
{
    global $conn, $logger, $invoiceGenerator, $userId, $isAdmin;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($data['orderId'] ?? 0);

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        return;
    }

    // Verify order access (customer owns order or is admin)
    $stmt = $conn->prepare("SELECT userId FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    if ($order['userId'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    // Generate invoice
    $result = $invoiceGenerator->generateInvoice($orderId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle invoice download
 * GET /api/invoices.php?action=download&orderId=X
 * 
 * Streams PDF file to browser
 */
function handleDownloadInvoice()
{
    global $conn, $logger, $invoiceGenerator, $userId, $isAdmin;

    $orderId = (int)($_GET['orderId'] ?? 0);

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        return;
    }

    // Verify order access
    $stmt = $conn->prepare("SELECT userId FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    if ($order['userId'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    // Find invoice file
    $invoiceDir = dirname(__DIR__) . '/invoices/';
    $files = glob($invoiceDir . 'invoice-' . $orderId . '-*.pdf');

    if (empty($files)) {
        // Generate if not found
        $genResult = $invoiceGenerator->generateInvoice($orderId);
        if (!$genResult['success']) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invoice not found and generation failed']);
            return;
        }
        $filepath = $invoiceDir . $genResult['filename'];
    } else {
        $filepath = $files[0];
    }

    // Stream file
    if (file_exists($filepath)) {
        $logger->info('Invoice downloaded', ['orderId' => $orderId, 'userId' => $userId], 'invoices');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice file not found']);
    }
}

/**
 * Handle invoice email
 * POST /api/invoices.php?action=email
 * 
 * Required:
 * - orderId: int - Order ID
 * - email: string - Email address
 */
function handleEmailInvoice()
{
    global $conn, $logger, $invoiceGenerator, $userId, $isAdmin;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($data['orderId'] ?? 0);
    $email = trim($data['email'] ?? '');

    if ($orderId <= 0 || empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or email']);
        return;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }

    // Verify order access
    $stmt = $conn->prepare("SELECT userId FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    if ($order['userId'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    // Email invoice
    $result = $invoiceGenerator->emailInvoice($orderId, $email);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle invoice listing (Admin only)
 * GET /api/invoices.php?action=list
 */
function handleListInvoices()
{
    global $conn, $logger, $invoiceGenerator, $isAdmin;

    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }

    $result = $invoiceGenerator->listInvoices();

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(500);
    }

    echo json_encode($result);
}

/**
 * Handle get invoice data
 * GET /api/invoices.php?action=get&orderId=X
 * 
 * Returns invoice data for preview
 */
function handleGetInvoice()
{
    global $conn, $logger, $invoiceGenerator, $userId, $isAdmin;

    $orderId = (int)($_GET['orderId'] ?? 0);

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        return;
    }

    // Verify order access
    $stmt = $conn->prepare("SELECT userId FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }

    if ($order['userId'] != $userId && !$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    // Get invoice data
    $result = $invoiceGenerator->getInvoiceData($orderId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}
