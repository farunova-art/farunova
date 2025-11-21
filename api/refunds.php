<?php

/**
 * FARUNOVA Refund API Endpoints
 * Handles refund operations: initiate, query, approve, list
 * 
 * @version 1.0
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/lib/RefundManager.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'initiate';
$userId = $_SESSION['userId'];
$isAdmin = $_SESSION['isAdmin'] ?? false;

// Initialize RefundManager
$refundManager = new RefundManager($conn, $logger, $mpesaPayment, $mpesaAuth);

try {
    switch ($action) {
        case 'initiate':
            handleRefundInitiate();
            break;

        case 'query':
            handleRefundQuery();
            break;

        case 'approve':
            handleRefundApprove();
            break;

        case 'deny':
            handleRefundDeny();
            break;

        case 'list':
            handleRefundList();
            break;

        case 'history':
            handleRefundHistory();
            break;

        case 'callback':
            handleRefundCallback();
            break;

        case 'statistics':
            handleRefundStatistics();
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    $logger->error('Refund API error: ' . $e->getMessage(), 'payments');
}

/**
 * Handle refund initiation
 * POST /api/refunds.php?action=initiate
 * 
 * Required:
 * - paymentId: int - Payment ID to refund
 * - amount: float (optional) - Refund amount, defaults to full refund
 * - reason: string - Reason for refund
 */
function handleRefundInitiate()
{
    global $conn, $logger, $refundManager, $userId;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    $paymentId = (int)($data['paymentId'] ?? 0);
    $amount = isset($data['amount']) ? (float)$data['amount'] : null;
    $reason = trim($data['reason'] ?? 'Customer Request');

    // Validate input
    if ($paymentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        return;
    }

    if (empty($reason)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Refund reason is required']);
        return;
    }

    // Verify payment belongs to user
    $stmt = $conn->prepare("SELECT userId FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment || $payment['userId'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }

    // Initiate refund
    $result = $refundManager->initiateRefund($paymentId, $amount, $reason, $userId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle refund status query
 * POST/GET /api/refunds.php?action=query
 * 
 * Required:
 * - refundId: int - Refund ID to query
 */
function handleRefundQuery()
{
    global $conn, $logger, $refundManager, $userId;

    $refundId = (int)($_REQUEST['refundId'] ?? 0);

    if ($refundId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid refund ID']);
        return;
    }

    // Verify refund belongs to user
    $stmt = $conn->prepare("
        SELECT pr.*, p.userId 
        FROM payment_refunds pr
        JOIN payments p ON pr.paymentId = p.id
        WHERE pr.id = ?
    ");
    $stmt->bind_param("i", $refundId);
    $stmt->execute();
    $refund = $stmt->get_result()->fetch_assoc();

    if (!$refund || $refund['userId'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Refund not found']);
        return;
    }

    // Get refund status
    $result = $refundManager->queryRefundStatus($refundId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(404);
    }

    echo json_encode($result);
}

/**
 * Handle refund approval (Admin only)
 * POST /api/refunds.php?action=approve
 * 
 * Required:
 * - refundId: int - Refund ID to approve
 */
function handleRefundApprove()
{
    global $conn, $logger, $refundManager, $userId, $isAdmin;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    // Admin only
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $refundId = (int)($data['refundId'] ?? 0);

    if ($refundId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid refund ID']);
        return;
    }

    // Approve and process refund
    $result = $refundManager->approveRefund($refundId, $userId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle refund denial (Admin only)
 * POST /api/refunds.php?action=deny
 * 
 * Required:
 * - refundId: int - Refund ID to deny
 * - reason: string - Reason for denial
 */
function handleRefundDeny()
{
    global $conn, $logger, $refundManager, $userId, $isAdmin;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    // Admin only
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $refundId = (int)($data['refundId'] ?? 0);
    $reason = trim($data['reason'] ?? 'Admin decision');

    if ($refundId <= 0 || empty($reason)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid refund ID or reason']);
        return;
    }

    // Deny refund
    $result = $refundManager->denyRefund($refundId, $reason, $userId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle refund listing
 * GET /api/refunds.php?action=list&paymentId=X
 * 
 * Optional:
 * - paymentId: int - Filter by payment ID
 */
function handleRefundList()
{
    global $conn, $logger, $refundManager, $userId;

    $paymentId = (int)($_GET['paymentId'] ?? 0);

    if ($paymentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment ID required']);
        return;
    }

    // Verify payment belongs to user
    $stmt = $conn->prepare("SELECT userId FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment || $payment['userId'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }

    // Get refund history
    $result = $refundManager->getRefundHistory($paymentId);

    http_response_code(200);
    echo json_encode($result);
}

/**
 * Handle refund history (Admin only)
 * GET /api/refunds.php?action=history&startDate=X&endDate=Y
 * 
 * Optional:
 * - startDate: string - Filter start date
 * - endDate: string - Filter end date
 * - status: string - Filter by status
 */
function handleRefundHistory()
{
    global $conn, $logger, $isAdmin;

    // Admin only
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }

    $status = $_GET['status'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    $where = "1=1";
    $params = [];

    if (!empty($status)) {
        $where .= " AND status = ?";
        $params[] = $status;
    }

    $query = "
        SELECT pr.*, u.username, o.id as orderId, p.amount as paymentAmount
        FROM payment_refunds pr
        JOIN users u ON pr.requestedBy = u.id
        LEFT JOIN orders o ON pr.orderId = o.id
        LEFT JOIN payments p ON pr.paymentId = p.id
        WHERE $where
        ORDER BY pr.requestedAt DESC
        LIMIT ? OFFSET ?
    ";

    $types = str_repeat('s', count($params)) . 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $refunds = [];
    while ($row = $result->fetch_assoc()) {
        $refunds[] = $row;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'refunds' => $refunds,
        'count' => count($refunds)
    ]);
}

/**
 * Handle refund callback from M-Pesa
 * POST /api/refunds.php?action=callback
 */
function handleRefundCallback()
{
    global $conn, $logger, $refundManager;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    // Get raw JSON body
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    // Log callback
    $logger->info('Refund callback received', ['data' => $data], 'payments');

    // Process callback
    $result = $refundManager->processCallback($data);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle refund statistics (Admin only)
 * GET /api/refunds.php?action=statistics&startDate=X&endDate=Y
 */
function handleRefundStatistics()
{
    global $conn, $logger, $refundManager, $isAdmin;

    // Admin only
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }

    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;

    $result = $refundManager->getRefundStatistics($startDate, $endDate);

    http_response_code(200);
    echo json_encode($result);
}
