<?php

/**
 * FARUNOVA Payment Reconciliation Engine
 * Matches M-Pesa transactions with system records
 * Detects discrepancies and generates audit reports
 * 
 * @version 1.0
 * @author FARUNOVA Team
 */

class PaymentReconciliation
{
    private $db;
    private $logger;

    /**
     * Constructor
     */
    public function __construct($db, $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Reconcile single payment
     * Verifies M-Pesa receipt code and amount match
     * 
     * @param int $paymentId - Payment ID to reconcile
     * 
     * @return array
     */
    public function reconcilePayment($paymentId)
    {
        try {
            // Get payment details
            $stmt = $this->db->prepare("
                SELECT p.id, p.amount, p.mpesaReceiptCode, p.status, p.completedAt
                FROM payments p
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            if ($payment['status'] !== 'completed') {
                return ['success' => false, 'message' => 'Only completed payments can be reconciled'];
            }

            // Check for existing reconciliation
            $stmt = $this->db->prepare("
                SELECT id, isMatched FROM payment_reconciliation
                WHERE paymentId = ?
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing && $existing['isMatched']) {
                return [
                    'success' => true,
                    'message' => 'Payment already reconciled',
                    'paymentId' => $paymentId,
                    'isMatched' => true
                ];
            }

            // Validate receipt code exists
            if (empty($payment['mpesaReceiptCode'])) {
                $notes = 'Missing M-Pesa receipt code';
                $this->updateReconciliation($paymentId, $payment['amount'], $payment['amount'], 0, false, $notes);

                return [
                    'success' => false,
                    'message' => $notes,
                    'paymentId' => $paymentId
                ];
            }

            // Verify M-Pesa transaction (in production, call M-Pesa API)
            // For now, verify via local M-Pesa receipt code
            $stmt = $this->db->prepare("
                SELECT mpesaReceiptCode, amount FROM payments
                WHERE mpesaReceiptCode = ? AND status = 'completed'
            ");
            $stmt->bind_param("s", $payment['mpesaReceiptCode']);
            $stmt->execute();
            $mpesaRecord = $stmt->get_result()->fetch_assoc();

            if (!$mpesaRecord) {
                $notes = 'M-Pesa receipt code not verified in system';
                $this->updateReconciliation($paymentId, $payment['amount'], $payment['amount'], 0, false, $notes);

                return [
                    'success' => false,
                    'message' => $notes,
                    'paymentId' => $paymentId
                ];
            }

            // Compare amounts
            $mpesaAmount = $mpesaRecord['amount'];
            $systemAmount = $payment['amount'];
            $difference = abs($mpesaAmount - $systemAmount);
            $isMatched = ($difference < 0.01); // Allow 1 cent variance

            // Update reconciliation record
            $this->updateReconciliation(
                $paymentId,
                $mpesaAmount,
                $systemAmount,
                $difference,
                $isMatched,
                $isMatched ? 'Amounts match' : 'Amount discrepancy detected'
            );

            // Log reconciliation
            $this->logger->log('Payment reconciled', [
                'paymentId' => $paymentId,
                'mpesaAmount' => $mpesaAmount,
                'systemAmount' => $systemAmount,
                'isMatched' => $isMatched
            ], 'reconciliation');

            return [
                'success' => $isMatched,
                'message' => $isMatched ? 'Payment reconciled successfully' : 'Discrepancy detected',
                'paymentId' => $paymentId,
                'mpesaAmount' => $mpesaAmount,
                'systemAmount' => $systemAmount,
                'difference' => $difference,
                'isMatched' => $isMatched
            ];
        } catch (Exception $e) {
            $this->logger->error('Error reconciling payment: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Reconciliation error'];
        }
    }

    /**
     * Reconcile payments by date range
     * Bulk reconciliation for reporting period
     * 
     * @param string $startDate - Start date (YYYY-MM-DD)
     * @param string $endDate - End date (YYYY-MM-DD)
     * 
     * @return array
     */
    public function reconcileByDateRange($startDate, $endDate)
    {
        try {
            // Get completed payments in date range
            $stmt = $this->db->prepare("
                SELECT id FROM payments
                WHERE status = 'completed'
                AND completedAt BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                ORDER BY completedAt DESC
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();

            $totalPayments = 0;
            $matchedCount = 0;
            $discrepancyCount = 0;
            $errors = [];

            while ($row = $result->fetch_assoc()) {
                $totalPayments++;
                $reconcileResult = $this->reconcilePayment($row['id']);

                if ($reconcileResult['isMatched']) {
                    $matchedCount++;
                } else if (!$reconcileResult['success']) {
                    $discrepancyCount++;
                    $errors[] = [
                        'paymentId' => $row['id'],
                        'message' => $reconcileResult['message']
                    ];
                }
            }

            $this->logger->log('Bulk reconciliation completed', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalPayments' => $totalPayments,
                'matchedCount' => $matchedCount,
                'discrepancyCount' => $discrepancyCount
            ], 'reconciliation');

            return [
                'success' => true,
                'message' => 'Reconciliation completed',
                'totalPayments' => $totalPayments,
                'matchedCount' => $matchedCount,
                'discrepancyCount' => $discrepancyCount,
                'matchPercentage' => $totalPayments > 0 ? round(($matchedCount / $totalPayments) * 100, 2) : 0,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            $this->logger->error('Error bulk reconciling: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Bulk reconciliation error'];
        }
    }

    /**
     * Detect payment discrepancies
     * Find payments with amount mismatches
     * 
     * @param string $status - Filter by status (all, unmatched, discrepancy)
     * 
     * @return array
     */
    public function detectDiscrepancies($status = 'unmatched')
    {
        try {
            $where = "pr.isMatched = 0";

            if ($status === 'discrepancy') {
                $where = "pr.amountDifference > 0.01";
            }

            $stmt = $this->db->prepare("
                SELECT pr.*, p.mpesaReceiptCode, o.orderId, u.username, u.email
                FROM payment_reconciliation pr
                JOIN payments p ON pr.paymentId = p.id
                LEFT JOIN orders o ON p.orderId = o.id
                LEFT JOIN users u ON p.userId = u.id
                WHERE $where
                ORDER BY pr.createdAt DESC
            ");
            $stmt->execute();
            $result = $stmt->get_result();

            $discrepancies = [];
            while ($row = $result->fetch_assoc()) {
                $discrepancies[] = $row;
            }

            return [
                'success' => true,
                'discrepancies' => $discrepancies,
                'count' => count($discrepancies)
            ];
        } catch (Exception $e) {
            $this->logger->error('Error detecting discrepancies: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Error detecting discrepancies'];
        }
    }

    /**
     * Generate reconciliation report
     * Creates detailed audit report
     * 
     * @param array $filters - Filter options (startDate, endDate, status)
     * 
     * @return array
     */
    public function generateReport($filters = [])
    {
        try {
            $startDate = $filters['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $filters['endDate'] ?? date('Y-m-d');

            // Get reconciliation statistics
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as totalReconciled,
                    SUM(CASE WHEN isMatched = 1 THEN 1 ELSE 0 END) as matchedCount,
                    SUM(CASE WHEN isMatched = 0 THEN 1 ELSE 0 END) as unmatchedCount,
                    SUM(CASE WHEN amountDifference > 0 THEN 1 ELSE 0 END) as discrepancyCount,
                    SUM(amountDifference) as totalDifference,
                    AVG(amountDifference) as avgDifference,
                    MAX(amountDifference) as maxDifference
                FROM payment_reconciliation
                WHERE reconciliedAt BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();

            // Get payment summary
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as totalPayments,
                    SUM(p.amount) as totalAmount,
                    COUNT(CASE WHEN p.status = 'completed' THEN 1 END) as completedCount,
                    SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as completedAmount
                FROM payments p
                WHERE p.completedAt BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $paymentStats = $stmt->get_result()->fetch_assoc();

            // Get top discrepancies
            $stmt = $this->db->prepare("
                SELECT pr.*, p.mpesaReceiptCode, o.orderId, u.username
                FROM payment_reconciliation pr
                JOIN payments p ON pr.paymentId = p.id
                LEFT JOIN orders o ON p.orderId = o.id
                LEFT JOIN users u ON p.userId = u.id
                WHERE pr.amountDifference > 0
                AND pr.createdAt BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
                ORDER BY pr.amountDifference DESC
                LIMIT 10
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $topDiscrepancies = [];
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $topDiscrepancies[] = $row;
            }

            return [
                'success' => true,
                'report' => [
                    'period' => ['startDate' => $startDate, 'endDate' => $endDate],
                    'reconciliation' => $stats,
                    'payments' => $paymentStats,
                    'topDiscrepancies' => $topDiscrepancies,
                    'matchPercentage' => $stats['totalReconciled'] > 0
                        ? round(($stats['matchedCount'] / $stats['totalReconciled']) * 100, 2)
                        : 0
                ]
            ];
        } catch (Exception $e) {
            $this->logger->error('Error generating report: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Error generating report'];
        }
    }

    /**
     * Manual payment matching
     * Admin manually matches a discrepant payment
     * 
     * @param int $paymentId - Payment ID
     * @param float $mpesaAmount - Confirmed M-Pesa amount
     * @param string $notes - Admin notes
     * @param int $matchedBy - Admin user ID
     * 
     * @return array
     */
    public function manualMatch($paymentId, $mpesaAmount, $notes, $matchedBy)
    {
        try {
            // Get payment
            $stmt = $this->db->prepare("SELECT amount FROM payments WHERE id = ?");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            // Update reconciliation with manual match
            $systemAmount = $payment['amount'];
            $difference = abs($mpesaAmount - $systemAmount);
            $isMatched = true;

            $stmt = $this->db->prepare("
                UPDATE payment_reconciliation
                SET mpesaAmount = ?, systemAmount = ?, amountDifference = ?,
                    isMatched = 1, reconciliedAt = NOW(), reconciliedBy = ?,
                    notes = CONCAT(IFNULL(notes, ''), ' [Manual Match: ', ?, ']')
                WHERE paymentId = ?
            ");
            $stmt->bind_param("dddis", $mpesaAmount, $systemAmount, $difference, $matchedBy, $notes, $paymentId);
            $stmt->execute();

            $this->logger->log('Manual payment match', [
                'paymentId' => $paymentId,
                'mpesaAmount' => $mpesaAmount,
                'systemAmount' => $systemAmount,
                'matchedBy' => $matchedBy
            ], 'reconciliation');

            return [
                'success' => true,
                'message' => 'Payment manually matched',
                'paymentId' => $paymentId,
                'mpesaAmount' => $mpesaAmount,
                'systemAmount' => $systemAmount,
                'difference' => $difference
            ];
        } catch (Exception $e) {
            $this->logger->error('Error manual matching: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Manual matching error'];
        }
    }

    /**
     * Get reconciliation status
     * Overview of reconciliation health
     * 
     * @return array
     */
    public function getStatus()
    {
        try {
            // Overall statistics
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as totalPayments,
                    SUM(CASE WHEN isMatched = 1 THEN 1 ELSE 0 END) as matchedCount,
                    SUM(CASE WHEN isMatched = 0 THEN 1 ELSE 0 END) as unmatchedCount,
                    SUM(CASE WHEN amountDifference > 0 THEN 1 ELSE 0 END) as discrepancyCount,
                    SUM(amountDifference) as totalDifference
                FROM payment_reconciliation
            ");
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();

            // Recent reconciliations
            $stmt = $this->db->prepare("
                SELECT reconciliedAt, COUNT(*) as count
                FROM payment_reconciliation
                WHERE reconciliedAt IS NOT NULL
                GROUP BY DATE(reconciliedAt)
                ORDER BY reconciliedAt DESC
                LIMIT 7
            ");
            $stmt->execute();
            $recentActivity = [];
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $recentActivity[] = $row;
            }

            return [
                'success' => true,
                'status' => [
                    'totalPayments' => $stats['totalPayments'] ?? 0,
                    'matchedCount' => $stats['matchedCount'] ?? 0,
                    'unmatchedCount' => $stats['unmatchedCount'] ?? 0,
                    'discrepancyCount' => $stats['discrepancyCount'] ?? 0,
                    'totalDifference' => $stats['totalDifference'] ?? 0,
                    'matchPercentage' => isset($stats['totalPayments']) && $stats['totalPayments'] > 0
                        ? round(($stats['matchedCount'] / $stats['totalPayments']) * 100, 2)
                        : 0,
                    'health' => $this->getHealthStatus($stats)
                ],
                'recentActivity' => $recentActivity
            ];
        } catch (Exception $e) {
            $this->logger->error('Error getting status: ' . $e->getMessage(), 'reconciliation');
            return ['success' => false, 'message' => 'Error getting reconciliation status'];
        }
    }

    /**
     * Determine system health based on stats
     */
    private function getHealthStatus($stats)
    {
        if (!isset($stats['totalPayments']) || $stats['totalPayments'] === 0) {
            return 'unknown';
        }

        $matchPercent = ($stats['matchedCount'] / $stats['totalPayments']) * 100;

        if ($matchPercent >= 99.5) {
            return 'excellent';
        } elseif ($matchPercent >= 95) {
            return 'good';
        } elseif ($matchPercent >= 90) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Update reconciliation record
     */
    private function updateReconciliation($paymentId, $mpesaAmount, $systemAmount, $difference, $isMatched, $notes)
    {
        try {
            // Check if record exists
            $stmt = $this->db->prepare("SELECT id FROM payment_reconciliation WHERE paymentId = ?");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();

            if ($exists) {
                // Update existing
                $stmt = $this->db->prepare("
                    UPDATE payment_reconciliation
                    SET mpesaAmount = ?, systemAmount = ?, amountDifference = ?,
                        isMatched = ?, notes = ?
                    WHERE paymentId = ?
                ");
                $matched = $isMatched ? 1 : 0;
                $stmt->bind_param("dddis", $mpesaAmount, $systemAmount, $difference, $matched, $notes, $paymentId);
            } else {
                // Create new
                $stmt = $this->db->prepare("
                    INSERT INTO payment_reconciliation
                    (paymentId, mpesaAmount, systemAmount, amountDifference, isMatched, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $matched = $isMatched ? 1 : 0;
                $stmt->bind_param("idddis", $paymentId, $mpesaAmount, $systemAmount, $difference, $matched, $notes);
            }

            return $stmt->execute();
        } catch (Exception $e) {
            $this->logger->error('Error updating reconciliation: ' . $e->getMessage(), 'reconciliation');
            return false;
        }
    }
}
