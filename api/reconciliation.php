<?php

/**
 * FARUNOVA Reconciliation API Endpoints
 * Admin-only API for payment reconciliation and auditing
 * 
 * @version 1.0
 */

require_once dirname(__DIR__) . '/connection.php';
require_once dirname(__DIR__) . '/lib/PaymentReconciliation.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || !($_SESSION['isAdmin'] ?? false)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? 'status';
$adminId = $_SESSION['userId'];

// Initialize PaymentReconciliation
$reconciliation = new PaymentReconciliation($conn, $logger);

try {
    switch ($action) {
        case 'status':
            handleReconciliationStatus();
            break;

        case 'reconcile':
            handleReconcile();
            break;

        case 'discrepancies':
            handleDiscrepancies();
            break;

        case 'report':
            handleReport();
            break;

        case 'manual-match':
            handleManualMatch();
            break;

        case 'reconcile-range':
            handleReconcileRange();
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    $logger->error('Reconciliation API error: ' . $e->getMessage(), 'reconciliation');
}

/**
 * Handle reconciliation status request
 * GET /api/reconciliation.php?action=status
 * 
 * Returns:
 * - Overall reconciliation health
 * - Match statistics
 * - Recent activity
 */
function handleReconciliationStatus()
{
    global $conn, $logger, $reconciliation;

    $result = $reconciliation->getStatus();

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(500);
    }

    echo json_encode($result);
}

/**
 * Handle single payment reconciliation
 * POST /api/reconciliation.php?action=reconcile
 * 
 * Required:
 * - paymentId: int - Payment ID to reconcile
 */
function handleReconcile()
{
    global $conn, $logger, $reconciliation, $adminId;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $paymentId = (int)($data['paymentId'] ?? 0);

    if ($paymentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        return;
    }

    // Check if payment exists
    $stmt = $conn->prepare("SELECT id FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }

    // Reconcile payment
    $result = $reconciliation->reconcilePayment($paymentId);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle bulk reconciliation by date range
 * POST /api/reconciliation.php?action=reconcile-range
 * 
 * Required:
 * - startDate: string (YYYY-MM-DD)
 * - endDate: string (YYYY-MM-DD)
 */
function handleReconcileRange()
{
    global $conn, $logger, $reconciliation, $adminId;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $startDate = trim($data['startDate'] ?? '');
    $endDate = trim($data['endDate'] ?? '');

    if (empty($startDate) || empty($endDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Start and end dates required']);
        return;
    }

    // Validate date format
    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)
    ) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format (use YYYY-MM-DD)']);
        return;
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Start date must be before end date']);
        return;
    }

    // Reconcile range
    $result = $reconciliation->reconcileByDateRange($startDate, $endDate);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}

/**
 * Handle discrepancy detection
 * GET /api/reconciliation.php?action=discrepancies&status=all|unmatched|discrepancy
 * 
 * Optional:
 * - status: string - Filter type (all, unmatched, discrepancy)
 * - limit: int - Results limit (default 50)
 * - offset: int - Results offset (default 0)
 */
function handleDiscrepancies()
{
    global $conn, $logger, $reconciliation;

    $status = $_GET['status'] ?? 'unmatched';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    if (!in_array($status, ['all', 'unmatched', 'discrepancy'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status filter']);
        return;
    }

    $result = $reconciliation->detectDiscrepancies($status);

    // Apply limit/offset to results
    if ($result['success']) {
        $all = $result['discrepancies'];
        $result['discrepancies'] = array_slice($all, $offset, $limit);
        $result['total'] = count($all);
        $result['limit'] = $limit;
        $result['offset'] = $offset;
        http_response_code(200);
    } else {
        http_response_code(500);
    }

    echo json_encode($result);
}

/**
 * Handle reconciliation report generation
 * GET /api/reconciliation.php?action=report&startDate=X&endDate=Y
 * 
 * Optional:
 * - startDate: string - Report start date
 * - endDate: string - Report end date
 * - format: string - Report format (json, csv)
 */
function handleReport()
{
    global $conn, $logger, $reconciliation;

    $startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['endDate'] ?? date('Y-m-d');
    $format = $_GET['format'] ?? 'json';

    // Validate dates
    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)
    ) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Start date must be before end date']);
        return;
    }

    // Generate report
    $result = $reconciliation->generateReport([
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);

    if (!$result['success']) {
        http_response_code(500);
        echo json_encode($result);
        return;
    }

    if ($format === 'csv') {
        // Output as CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reconciliation-report-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reconciliation Report', $startDate . ' to ' . $endDate]);
        fputcsv($output, []);
        fputcsv($output, ['Statistics']);
        fputcsv($output, ['Total Reconciled', 'Matched', 'Unmatched', 'Discrepancies', 'Total Difference']);

        $stats = $result['report']['reconciliation'];
        fputcsv($output, [
            $stats['totalReconciled'],
            $stats['matchedCount'],
            $stats['unmatchedCount'],
            $stats['discrepancyCount'],
            $stats['totalDifference']
        ]);

        fclose($output);
    } else {
        // JSON response
        http_response_code(200);
        echo json_encode($result);
    }
}

/**
 * Handle manual payment matching
 * POST /api/reconciliation.php?action=manual-match
 * 
 * Required:
 * - paymentId: int - Payment ID to match
 * - mpesaAmount: float - Confirmed M-Pesa amount
 * - notes: string - Admin notes/reason
 */
function handleManualMatch()
{
    global $conn, $logger, $reconciliation, $adminId;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $paymentId = (int)($data['paymentId'] ?? 0);
    $mpesaAmount = (float)($data['mpesaAmount'] ?? 0);
    $notes = trim($data['notes'] ?? 'Manual admin match');

    // Validate input
    if ($paymentId <= 0 || $mpesaAmount <= 0 || empty($notes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
        return;
    }

    // Check if payment exists
    $stmt = $conn->prepare("SELECT id, status FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }

    if ($payment['status'] !== 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only completed payments can be matched']);
        return;
    }

    // Perform manual match
    $result = $reconciliation->manualMatch($paymentId, $mpesaAmount, $notes, $adminId);

    if ($result['success']) {
        http_response_code(200);
        $logger->info('Manual payment match performed', [
            'paymentId' => $paymentId,
            'mpesaAmount' => $mpesaAmount,
            'adminId' => $adminId
        ], 'reconciliation');
    } else {
        http_response_code(400);
    }

    echo json_encode($result);
}
