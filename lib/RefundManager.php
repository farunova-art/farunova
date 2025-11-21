<?php

/**
 * FARUNOVA Refund Manager
 * Handles M-Pesa refunds with status tracking and reconciliation
 * 
 * @version 1.0
 * @author FARUNOVA Team
 */

class RefundManager
{
    private $db;
    private $logger;
    private $mpesaPayment;
    private $mpesaAuth;

    /**
     * Constructor
     */
    public function __construct($db, $logger, $mpesaPayment, $mpesaAuth)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->mpesaPayment = $mpesaPayment;
        $this->mpesaAuth = $mpesaAuth;
    }

    /**
     * Initiate refund for a payment
     * 
     * @param int $paymentId - Payment ID to refund
     * @param float $amount - Refund amount (leave null for full refund)
     * @param string $reason - Reason for refund
     * @param int $requestedBy - User ID requesting refund
     * 
     * @return array
     */
    public function initiateRefund($paymentId, $amount = null, $reason = 'Customer Request', $requestedBy = null)
    {
        try {
            // Get payment details
            $stmt = $this->db->prepare("
                SELECT p.*, o.id as orderId 
                FROM payments p 
                LEFT JOIN orders o ON p.orderId = o.id 
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found'
                ];
            }

            // Check if payment is completed
            if ($payment['status'] !== 'completed') {
                return [
                    'success' => false,
                    'message' => 'Only completed payments can be refunded'
                ];
            }

            // Set refund amount to full if not specified
            if ($amount === null) {
                $amount = $payment['amount'];
            }

            // Validate refund amount
            if ($amount <= 0 || $amount > $payment['amount']) {
                return [
                    'success' => false,
                    'message' => 'Invalid refund amount. Must be between 0 and ' . $payment['amount']
                ];
            }

            // Check existing refunds
            $stmt = $this->db->prepare("
                SELECT SUM(refundAmount) as totalRefunded 
                FROM payment_refunds 
                WHERE paymentId = ? AND status IN ('completed', 'processing')
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $refundResult = $stmt->get_result()->fetch_assoc();
            $totalRefunded = $refundResult['totalRefunded'] ?? 0;

            if (($totalRefunded + $amount) > $payment['amount']) {
                return [
                    'success' => false,
                    'message' => 'Refund amount exceeds available balance. Available: ' . ($payment['amount'] - $totalRefunded)
                ];
            }

            // Create refund record
            $refundId = $this->createRefundRecord(
                $paymentId,
                $payment['orderId'],
                $amount,
                $reason,
                $requestedBy
            );

            if (!$refundId) {
                return [
                    'success' => false,
                    'message' => 'Failed to create refund record'
                ];
            }

            // Log refund initiation
            $this->logger->log('Refund initiated', [
                'refundId' => $refundId,
                'paymentId' => $paymentId,
                'amount' => $amount,
                'reason' => $reason,
                'requestedBy' => $requestedBy
            ], 'payments');

            return [
                'success' => true,
                'message' => 'Refund initiated successfully',
                'refundId' => $refundId,
                'amount' => $amount,
                'paymentId' => $paymentId
            ];
        } catch (Exception $e) {
            $this->logger->error('Error initiating refund: ' . $e->getMessage(), 'payments');
            return [
                'success' => false,
                'message' => 'Error initiating refund: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create refund database record
     */
    private function createRefundRecord($paymentId, $orderId, $amount, $reason, $requestedBy)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payment_refunds (paymentId, orderId, refundAmount, reason, status, requestedBy)
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->bind_param("iidsi", $paymentId, $orderId, $amount, $reason, $requestedBy);

            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
            return false;
        } catch (Exception $e) {
            $this->logger->error('Error creating refund record: ' . $e->getMessage(), 'payments');
            return false;
        }
    }

    /**
     * Process refund with M-Pesa
     * 
     * @param int $refundId - Refund ID to process
     * 
     * @return array
     */
    public function processRefund($refundId)
    {
        try {
            // Get refund details
            $stmt = $this->db->prepare("
                SELECT pr.*, p.mpesaReceiptCode, p.phoneNumber 
                FROM payment_refunds pr 
                JOIN payments p ON pr.paymentId = p.id 
                WHERE pr.id = ?
            ");
            $stmt->bind_param("i", $refundId);
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if (!$refund) {
                return ['success' => false, 'message' => 'Refund not found'];
            }

            if ($refund['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Refund is already being processed'];
            }

            // Update refund status to processing
            $this->updateRefundStatus($refundId, 'processing');

            // Call M-Pesa refund API
            $refundResult = $this->callMpesaRefund(
                $refund['mpesaReceiptCode'],
                $refund['refundAmount'],
                $refund['paymentId']
            );

            if (!$refundResult['success']) {
                $this->updateRefundStatus($refundId, 'failed', $refundResult['message']);
                return $refundResult;
            }

            // Log refund API call
            $this->logRefundTransaction($refundId, $refund['paymentId'], 'api_call', 'initiated', $refundResult);

            return [
                'success' => true,
                'message' => 'Refund processing initiated',
                'refundId' => $refundId,
                'mpesaRequestId' => $refundResult['mpesaRequestId'] ?? null
            ];
        } catch (Exception $e) {
            $this->logger->error('Error processing refund: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error processing refund'];
        }
    }

    /**
     * Call M-Pesa refund API
     */
    private function callMpesaRefund($receiptCode, $amount, $paymentId)
    {
        try {
            // Get M-Pesa token
            $token = $this->mpesaAuth->getAccessToken();
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
            }

            // Prepare refund payload
            $payload = [
                'Initiator' => 'FARUNOVA',
                'SecurityCredential' => 'MPESA_CREDENTIAL',
                'CommandID' => 'Refund',
                'OriginatingTransactionID' => $receiptCode,
                'Amount' => (int)$amount,
                'QueueTimeOutURL' => 'https://farunova.localhost/api/refunds.php?action=callback',
                'ResultURL' => 'https://farunova.localhost/api/refunds.php?action=callback',
                'Remarks' => 'Refund for payment ID: ' . $paymentId
            ];

            // Call M-Pesa API
            $refundUrl = 'https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request';

            $ch = curl_init($refundUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 200 && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
                return [
                    'success' => true,
                    'mpesaRequestId' => $result['ConversationID'] ?? null,
                    'response' => $result
                ];
            }

            return [
                'success' => false,
                'message' => $result['ResponseDescription'] ?? 'M-Pesa refund failed',
                'responseCode' => $result['ResponseCode'] ?? null,
                'response' => $result
            ];
        } catch (Exception $e) {
            $this->logger->error('M-Pesa refund API error: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'M-Pesa API error'];
        }
    }

    /**
     * Query refund status
     * 
     * @param int $refundId - Refund ID
     * 
     * @return array
     */
    public function queryRefundStatus($refundId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM payment_refunds WHERE id = ?
            ");
            $stmt->bind_param("i", $refundId);
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if (!$refund) {
                return ['success' => false, 'message' => 'Refund not found'];
            }

            return [
                'success' => true,
                'refund' => $refund,
                'status' => $refund['status'],
                'amount' => $refund['refundAmount']
            ];
        } catch (Exception $e) {
            $this->logger->error('Error querying refund: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error querying refund'];
        }
    }

    /**
     * Process refund callback from M-Pesa
     * 
     * @param array $callbackData - Callback data from M-Pesa
     * 
     * @return array
     */
    public function processCallback($callbackData)
    {
        try {
            // Parse callback (refund-specific structure)
            $resultCode = $callbackData['Result']['ResultCode'] ?? null;
            $resultDesc = $callbackData['Result']['ResultDesc'] ?? '';
            $conversationId = $callbackData['Result']['ConversationID'] ?? null;
            $transactionId = $callbackData['Result']['TransactionID'] ?? null;

            // Find refund by conversation ID (stored in payment_refunds notes)
            $stmt = $this->db->prepare("
                SELECT * FROM payment_refunds 
                WHERE status IN ('processing', 'pending') 
                ORDER BY requestedAt DESC LIMIT 1
            ");
            $stmt->execute();
            $refund = $stmt->get_result()->fetch_assoc();

            if (!$refund) {
                return ['success' => false, 'message' => 'Refund not found'];
            }

            // Update refund status based on result code
            $newStatus = ($resultCode === 0) ? 'completed' : 'failed';
            $this->updateRefundStatus($refund['id'], $newStatus, $resultDesc);

            // Update receipt code if successful
            if ($resultCode === 0 && $transactionId) {
                $stmt = $this->db->prepare("
                    UPDATE payment_refunds 
                    SET mpesaReceiptCode = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $transactionId, $refund['id']);
                $stmt->execute();
            }

            // Log callback
            $this->logRefundTransaction(
                $refund['id'],
                $refund['paymentId'],
                'callback',
                $newStatus,
                $callbackData
            );

            $this->logger->log('Refund callback processed', [
                'refundId' => $refund['id'],
                'status' => $newStatus,
                'resultCode' => $resultCode
            ], 'payments');

            return [
                'success' => true,
                'refundId' => $refund['id'],
                'status' => $newStatus
            ];
        } catch (Exception $e) {
            $this->logger->error('Error processing refund callback: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error processing callback'];
        }
    }

    /**
     * Update refund status
     */
    private function updateRefundStatus($refundId, $status, $notes = null)
    {
        try {
            if ($notes) {
                $stmt = $this->db->prepare("
                    UPDATE payment_refunds 
                    SET status = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("ssi", $status, $notes, $refundId);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE payment_refunds 
                    SET status = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $status, $refundId);
            }
            return $stmt->execute();
        } catch (Exception $e) {
            $this->logger->error('Error updating refund status: ' . $e->getMessage(), 'payments');
            return false;
        }
    }

    /**
     * Log refund transaction
     */
    private function logRefundTransaction($refundId, $paymentId, $type, $status, $data)
    {
        try {
            $dataJson = json_encode($data);
            $stmt = $this->db->prepare("
                INSERT INTO payment_transactions 
                (paymentId, transactionType, status, apiResponse)
                VALUES (?, ?, ?, ?)
            ");
            $transactionType = 'refund_' . $type;
            $stmt->bind_param("isss", $paymentId, $transactionType, $status, $dataJson);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->logger->error('Error logging refund transaction: ' . $e->getMessage(), 'payments');
            return false;
        }
    }

    /**
     * Get refund history for payment
     */
    public function getRefundHistory($paymentId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM payment_refunds 
                WHERE paymentId = ? 
                ORDER BY requestedAt DESC
            ");
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();

            $refunds = [];
            while ($row = $result->fetch_assoc()) {
                $refunds[] = $row;
            }

            return [
                'success' => true,
                'refunds' => $refunds,
                'count' => count($refunds),
                'totalRefunded' => array_sum(array_column($refunds, 'refundAmount'))
            ];
        } catch (Exception $e) {
            $this->logger->error('Error getting refund history: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error retrieving refund history'];
        }
    }

    /**
     * Get refund statistics
     */
    public function getRefundStatistics($startDate = null, $endDate = null)
    {
        try {
            $where = "1=1";
            $params = [];

            if ($startDate && $endDate) {
                $where = "requestedAt BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }

            $query = "
                SELECT 
                    COUNT(*) as totalRefunds,
                    SUM(refundAmount) as totalAmount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completedCount,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pendingCount,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processingCount,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failedCount,
                    AVG(refundAmount) as avgAmount
                FROM payment_refunds 
                WHERE $where
            ";

            $stmt = $this->db->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param("ss", ...$params);
            }
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'statistics' => $stats
            ];
        } catch (Exception $e) {
            $this->logger->error('Error getting refund statistics: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error retrieving statistics'];
        }
    }

    /**
     * Get pending refunds for admin approval
     */
    public function getPendingRefunds($limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.*, u.username, o.id as orderId, p.amount as paymentAmount
                FROM payment_refunds pr
                JOIN users u ON pr.requestedBy = u.id
                LEFT JOIN orders o ON pr.orderId = o.id
                LEFT JOIN payments p ON pr.paymentId = p.id
                WHERE pr.status = 'pending'
                ORDER BY pr.requestedAt DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $refunds = [];
            while ($row = $result->fetch_assoc()) {
                $refunds[] = $row;
            }

            return [
                'success' => true,
                'refunds' => $refunds,
                'count' => count($refunds)
            ];
        } catch (Exception $e) {
            $this->logger->error('Error getting pending refunds: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error retrieving pending refunds'];
        }
    }

    /**
     * Approve refund and process with M-Pesa
     */
    public function approveRefund($refundId, $approvedBy)
    {
        try {
            // Update refund with approval info
            $stmt = $this->db->prepare("
                UPDATE payment_refunds 
                SET processedBy = ?, processedAt = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->bind_param("ii", $approvedBy, $refundId);
            $stmt->execute();

            // Process refund with M-Pesa
            return $this->processRefund($refundId);
        } catch (Exception $e) {
            $this->logger->error('Error approving refund: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error approving refund'];
        }
    }

    /**
     * Deny refund request
     */
    public function denyRefund($refundId, $reason, $deniedBy)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE payment_refunds 
                SET status = 'failed', notes = ?, processedBy = ?, processedAt = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("sii", $reason, $deniedBy, $refundId);
            $stmt->execute();

            $this->logger->log('Refund denied', [
                'refundId' => $refundId,
                'reason' => $reason,
                'deniedBy' => $deniedBy
            ], 'payments');

            return [
                'success' => true,
                'message' => 'Refund request denied'
            ];
        } catch (Exception $e) {
            $this->logger->error('Error denying refund: ' . $e->getMessage(), 'payments');
            return ['success' => false, 'message' => 'Error denying refund'];
        }
    }
}
