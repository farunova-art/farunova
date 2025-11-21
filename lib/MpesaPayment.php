<?php

/**
 * FARUNOVA M-Pesa Payment Handler
 * Complete payment processing with STK Push, Query, and Callback handling
 * 
 * @version 1.0
 */

class MpesaPayment
{

    private $auth;
    private $logger;
    private $db;

    // Result code messages
    private $resultMessages = [
        0 => '✅ Payment successful',
        1 => '⏱️ Timeout - request timed out',
        1032 => '❌ Cancelled by user',
        2001 => '💸 Insufficient balance',
        1001 => '🔐 Invalid credentials',
        17 => '❌ Request aborted by user',
        402 => '❌ Invalid account number'
    ];

    public function __construct($auth = null, $logger = null, $db = null)
    {
        $this->auth = $auth ?: new MpesaAuth();
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Initiate STK Push payment request
     * 
     * @param string $phone - Customer phone number (254...)
     * @param decimal $amount - Amount to charge
     * @param string $accountRef - Order reference
     * @param string $description - Transaction description
     * 
     * @return array - Contains CheckoutRequestID
     */
    public function initiatePayment($phone, $amount, $accountRef, $description = 'FARUNOVA Order')
    {
        try {
            // Validate phone number
            $phone = $this->validatePhone($phone);

            // Get access token
            $token = $this->auth->getAccessToken();

            // Generate timestamp and password
            $timestamp = MpesaConfig::getTimestamp();
            $password = MpesaConfig::generatePassword($timestamp);

            // Prepare request body
            $payload = [
                'BusinessShortCode' => MpesaConfig::getShortCode(),
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$amount,
                'PartyA' => $phone,
                'PartyB' => MpesaConfig::getShortCode(),
                'PhoneNumber' => $phone,
                'CallBackURL' => MpesaConfig::getCallbackUrl(),
                'AccountReference' => sanitizeInput($accountRef),
                'TransactionDesc' => sanitizeInput($description)
            ];

            // Send request to M-Pesa
            $response = $this->curlRequest(MpesaConfig::getStkUrl(), $payload, $token);

            // Log the transaction
            if ($this->logger) {
                $this->logger->info('STK Push initiated', [
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $accountRef,
                    'checkoutRequestID' => $response['CheckoutRequestID'] ?? null
                ]);
            }

            return [
                'success' => true,
                'checkoutRequestID' => $response['CheckoutRequestID'] ?? null,
                'responseCode' => $response['ResponseCode'] ?? null,
                'responseDescription' => $response['ResponseDescription'] ?? null
            ];
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('STK Push failed', ['error' => $e->getMessage()]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Query payment status
     * 
     * @param string $checkoutRequestID - Checkout request ID from STK push
     * 
     * @return array - Payment status
     */
    public function queryPaymentStatus($checkoutRequestID)
    {
        try {
            // Get access token
            $token = $this->auth->getAccessToken();

            // Generate timestamp and password
            $timestamp = MpesaConfig::getTimestamp();
            $password = MpesaConfig::generatePassword($timestamp);

            // Prepare request body
            $payload = [
                'BusinessShortCode' => MpesaConfig::getShortCode(),
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestID
            ];

            // Send request to M-Pesa
            $response = $this->curlRequest(MpesaConfig::getQueryUrl(), $payload, $token);

            // Get user-friendly message
            $resultCode = (int)($response['ResultCode'] ?? 1);
            $userMessage = $this->resultMessages[$resultCode] ?? 'Unknown result code: ' . $resultCode;

            // Log the query
            if ($this->logger) {
                $this->logger->info('Payment status queried', [
                    'checkoutRequestID' => $checkoutRequestID,
                    'resultCode' => $resultCode,
                    'message' => $userMessage
                ]);
            }

            return [
                'success' => $resultCode === 0,
                'resultCode' => $resultCode,
                'userMessage' => $userMessage,
                'response' => $response
            ];
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Payment query failed', ['error' => $e->getMessage()]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate QR code for payment
     * 
     * @param array $data - QR code data
     *  - MerchantName: Store name
     *  - RefNo: Reference number
     *  - Amount: Amount
     *  - TrxCode: Transaction code
     *  - Size: QR code size
     * 
     * @return array - QR code data
     */
    public function generateQrCode($data)
    {
        try {
            $token = $this->auth->getAccessToken();

            // Prepare request body with defaults
            $payload = [
                'MerchantName' => $data['MerchantName'] ?? 'FARUNOVA',
                'RefNo' => sanitizeInput($data['RefNo'] ?? ''),
                'Amount' => (int)($data['Amount'] ?? 0),
                'TrxCode' => $data['TrxCode'] ?? 'BG',
                'CPI' => $data['CPI'] ?? null,
                'Size' => $data['Size'] ?? '300'
            ];

            // Remove null values
            $payload = array_filter($payload, fn($v) => $v !== null);

            // Send request to M-Pesa
            $response = $this->curlRequest(MpesaConfig::getQrUrl(), $payload, $token);

            if ($this->logger) {
                $this->logger->info('QR code generated', ['reference' => $payload['RefNo']]);
            }

            return [
                'success' => true,
                'qrCode' => $response['QRCode'] ?? null,
                'response' => $response
            ];
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('QR generation failed', ['error' => $e->getMessage()]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process callback from M-Pesa
     * 
     * @param array $body - Callback body from M-Pesa
     * 
     * @return array - Processing result
     */
    public function processCallback($body)
    {
        try {
            if (!isset($body['stkCallback'])) {
                throw new Exception('Invalid callback format');
            }

            $result = $body['stkCallback'];
            $checkoutRequestID = $result['CheckoutRequestID'] ?? null;
            $resultCode = (int)($result['ResultCode'] ?? 1);
            $resultDesc = $result['ResultDesc'] ?? '';

            // Extract callback metadata if payment successful
            $mpesaCode = null;
            $mpesaAmount = null;

            if ($resultCode === 0 && isset($result['CallbackMetadata']['Item'])) {
                $items = $result['CallbackMetadata']['Item'];

                // Extract M-Pesa receipt number and amount
                foreach ($items as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaCode = $item['Value'];
                    }
                    if ($item['Name'] === 'Amount') {
                        $mpesaAmount = $item['Value'];
                    }
                }
            }

            // Log the callback
            if ($this->logger) {
                $this->logger->userAction('payment_callback', [
                    'checkoutRequestID' => $checkoutRequestID,
                    'resultCode' => $resultCode,
                    'mpesaCode' => $mpesaCode
                ]);
            }

            return [
                'success' => true,
                'resultCode' => $resultCode,
                'resultDescription' => $resultDesc,
                'checkoutRequestID' => $checkoutRequestID,
                'mpesaCode' => $mpesaCode,
                'amount' => $mpesaAmount
            ];
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Callback processing failed', ['error' => $e->getMessage()]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make CURL request to M-Pesa API
     */
    private function curlRequest($url, $payload, $token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
                'User-Agent: Mozilla/5.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new Exception('CURL Error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP Error ' . $httpCode . ': ' . $response);
        }

        $result = json_decode($response, true);

        if (!$result) {
            throw new Exception('Invalid JSON response from M-Pesa');
        }

        return $result;
    }

    /**
     * Validate and format phone number
     */
    private function validatePhone($phone)
    {
        // Remove common formatting
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 0 prefix to 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // Ensure it starts with 254
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        // Validate length (should be 254 + 9 digits)
        if (strlen($phone) !== 12 || !ctype_digit($phone)) {
            throw new Exception('Invalid phone number format');
        }

        return $phone;
    }

    /**
     * Get result message by code
     */
    public function getResultMessage($resultCode)
    {
        return $this->resultMessages[(int)$resultCode] ?? 'Unknown result code';
    }
}
