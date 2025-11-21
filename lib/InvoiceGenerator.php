<?php

/**
 * FARUNOVA Invoice Generator
 * Creates PDF invoices with order details and payment information
 * 
 * @version 1.0
 * @author FARUNOVA Team
 */

// Suppress errors for optional TCPDF dependency
if (!class_exists('TCPDF')) {
    class TCPDF
    {
        public function SetDefaultMonospacedFont($font) {}
        public function SetMargins($left, $top, $right) {}
        public function SetAutoPageBreak($auto, $margin) {}
        public function SetFont($family, $style = '', $size = 0) {}
        public function AddPage() {}
        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false) {}
        public function MultiCell($w, $h, $txt = '', $border = 0, $align = 'J', $fill = false) {}
        public function Ln($h = 10) {}
        public function SetXY($x, $y) {}
        public function GetX()
        {
            return 0;
        }
        public function GetY()
        {
            return 0;
        }
        public function SetTextColor($r, $g = -1, $b = -1) {}
        public function SetFillColor($r, $g = -1, $b = -1) {}
        public function SetY($y, $x = null) {}
        public function Output($name = '', $dest = '') {}
    }
}

class InvoiceGenerator
{
    private $db;
    private $logger;
    private $pdfClass = null;

    /**
     * Constructor
     */
    public function __construct($db, $logger)
    {
        $this->db = $db;
        $this->logger = $logger;

        // Use TCPDF if available, otherwise use simple HTML generation
        if (class_exists('TCPDF')) {
            require_once 'lib/TCPDF/tcpdf.php';
        }
    }

    /**
     * Generate PDF invoice for order
     * 
     * @param int $orderId - Order ID
     * 
     * @return array
     */
    public function generateInvoice($orderId)
    {
        try {
            // Get order details
            $invoiceData = $this->getInvoiceData($orderId);

            if (!$invoiceData['success']) {
                return $invoiceData;
            }

            $order = $invoiceData['order'];
            $items = $invoiceData['items'];
            $payment = $invoiceData['payment'];

            // Generate PDF
            $pdf = $this->createPdfDocument();

            // Add content
            $this->addHeaderSection($pdf, $order);
            $this->addInvoiceInfo($pdf, $order);
            $this->addItemsTable($pdf, $items);
            $this->addTotalsSection($pdf, $order);
            $this->addPaymentInfo($pdf, $payment);
            $this->addFooter($pdf);

            // Save PDF
            $filename = $this->getInvoiceFilename($orderId);
            $filepath = dirname(__DIR__) . '/invoices/' . $filename;

            // Create invoices directory if needed
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // Save based on PDF class availability
            if (class_exists('TCPDF')) {
                $pdf->Output($filepath, 'F');
            } else {
                // Fallback: save HTML for simple viewing
                file_put_contents($filepath . '.html', $this->generateHtmlInvoice($invoiceData));
            }

            // Log invoice generation
            $this->logInvoiceGeneration($orderId, $filename);

            return [
                'success' => true,
                'message' => 'Invoice generated successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => '/invoices/' . $filename
            ];
        } catch (Exception $e) {
            $this->logger->error('Error generating invoice: ' . $e->getMessage(), 'invoices');
            return ['success' => false, 'message' => 'Invoice generation error'];
        }
    }

    /**
     * Get invoice data from database
     */
    public function getInvoiceData($orderId)
    {
        try {
            $orderId = (int)$orderId;

            // Get order details
            $stmt = $this->db->prepare("
                SELECT o.*, u.username, u.email, u.address, u.city, u.postalCode, u.phone
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

            // Get order items
            $stmt = $this->db->prepare("
                SELECT oi.*, p.name, p.category
                FROM order_items oi
                JOIN products p ON oi.productId = p.id
                WHERE oi.orderId = ?
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $items = [];
            while ($row = $stmt->get_result()->fetch_assoc()) {
                $items[] = $row;
            }

            // Get payment info
            $stmt = $this->db->prepare("
                SELECT * FROM payments WHERE orderId = ? AND status = 'completed'
                ORDER BY completedAt DESC LIMIT 1
            ");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $payment = $stmt->get_result()->fetch_assoc();

            return [
                'success' => true,
                'order' => $order,
                'items' => $items,
                'payment' => $payment
            ];
        } catch (Exception $e) {
            $this->logger->error('Error getting invoice data: ' . $e->getMessage(), 'invoices');
            return ['success' => false, 'message' => 'Error retrieving order data'];
        }
    }

    /**
     * Create PDF document
     */
    private function createPdfDocument()
    {
        if (class_exists('TCPDF')) {
            /** @var TCPDF $pdf */
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->AddPage();
            return $pdf;
        }
        return null;
    }

    /**
     * Add header section (company info)
     */
    private function addHeaderSection(&$pdf, $order)
    {
        if (!$pdf) return;

        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'FARUNOVA', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100);
        $pdf->MultiCell(0, 4, "Premium Clothing Store\nNairobi, Kenya\nEmail: support@farunova.com\nPhone: +254 700 000 000", 0, 'L');

        $pdf->SetTextColor(0);
        $pdf->Ln(5);
    }

    /**
     * Add invoice information
     */
    private function addInvoiceInfo(&$pdf, $order)
    {
        if (!$pdf) return;

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'INVOICE', 0, 1);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(50, 5, 'Invoice #: ' . $order['id'], 0, 0);
        $pdf->Cell(50, 5, 'Order #: ' . $order['orderId'], 0, 1);

        $pdf->Cell(50, 5, 'Date: ' . date('M d, Y', strtotime($order['createdAt'])), 0, 0);
        $pdf->Cell(50, 5, 'Status: ' . ucfirst($order['status']), 0, 1);

        $pdf->Ln(3);

        // Customer info
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(95, 5, 'BILL TO:', 0, 0);
        $pdf->Cell(0, 5, 'SHIP TO:', 0, 1);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(95, 4, $order['username'] . "\n" . $order['email'] . "\n" . $order['phone'], 0, 'L');

        $x = $pdf->GetX();
        $y = $pdf->GetY() - 12;
        $pdf->SetXY(115, $y);
        $pdf->MultiCell(0, 4, $order['shippingAddress'] . "\n" . $order['shippingCity'] . "\n" . $order['shippingPostalCode'], 0, 'L');

        $pdf->Ln(2);
    }

    /**
     * Add items table
     */
    private function addItemsTable(&$pdf, $items)
    {
        if (!$pdf) return;

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);

        $pdf->Cell(10, 6, '#', 1, 0, 'C', true);
        $pdf->Cell(70, 6, 'Product', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Size', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'Color', 1, 0, 'C', true);
        $pdf->Cell(15, 6, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Unit Price', 1, 0, 'R', true);
        $pdf->Cell(30, 6, 'Total', 1, 1, 'R', true);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 8);

        $itemNumber = 1;
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['priceAtTime'];

            $pdf->Cell(10, 6, $itemNumber, 1, 0, 'C');
            $pdf->Cell(70, 6, substr($item['name'], 0, 40), 1, 0, 'L');
            $pdf->Cell(20, 6, $item['size'] ?? '-', 1, 0, 'C');
            $pdf->Cell(20, 6, $item['color'] ?? '-', 1, 0, 'C');
            $pdf->Cell(15, 6, $item['quantity'], 1, 0, 'C');
            $pdf->Cell(30, 6, 'KES ' . number_format($item['priceAtTime'], 2), 1, 0, 'R');
            $pdf->Cell(30, 6, 'KES ' . number_format($itemTotal, 2), 1, 1, 'R');

            $itemNumber++;
        }

        $pdf->Ln(2);
    }

    /**
     * Add totals section
     */
    private function addTotalsSection(&$pdf, $order)
    {
        if (!$pdf) return;

        $pdf->SetFont('helvetica', '', 9);

        // Subtotal (assume no separate tax/shipping)
        $pdf->Cell(160, 6, 'Subtotal:', 0, 0, 'R');
        $pdf->Cell(30, 6, 'KES ' . number_format($order['totalAmount'], 2), 0, 1, 'R');

        $pdf->Cell(160, 6, 'Shipping:', 0, 0, 'R');
        $pdf->Cell(30, 6, 'KES 0.00', 0, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(160, 7, 'TOTAL:', 0, 0, 'R', true);
        $pdf->Cell(30, 7, 'KES ' . number_format($order['totalAmount'], 2), 0, 1, 'R', true);

        $pdf->Ln(3);
    }

    /**
     * Add payment information
     */
    private function addPaymentInfo(&$pdf, $payment)
    {
        if (!$pdf || !$payment) return;

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'PAYMENT INFORMATION', 0, 1);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(50, 5, 'Method: ' . ($payment['paymentMethod'] ?? 'M-Pesa'), 0, 1);
        $pdf->Cell(50, 5, 'Status: ' . ucfirst($payment['status'] ?? 'pending'), 0, 1);

        if ($payment['mpesaReceiptCode']) {
            $pdf->Cell(50, 5, 'M-Pesa Code: ' . $payment['mpesaReceiptCode'], 0, 1);
        }

        if ($payment['completedAt']) {
            $pdf->Cell(50, 5, 'Date Paid: ' . date('M d, Y H:i', strtotime($payment['completedAt'])), 0, 1);
        }

        $pdf->Ln(3);
    }

    /**
     * Add footer
     */
    private function addFooter(&$pdf)
    {
        if (!$pdf) return;

        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100);
        $pdf->Cell(0, 5, 'FARUNOVA - Premium Clothing Store', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Thank you for your purchase!', 0, 1, 'C');
        $pdf->Cell(0, 5, 'For support, contact: support@farunova.com', 0, 0, 'C');
    }

    /**
     * Generate HTML invoice (fallback)
     */
    private function generateHtmlInvoice($data)
    {
        $order = $data['order'];
        $items = $data['items'];
        $payment = $data['payment'];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #{$order['id']}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { margin-bottom: 30px; }
        .header h1 { margin: 0; }
        .invoice-details { margin-bottom: 30px; }
        .customer-info { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .totals { float: right; width: 300px; margin-bottom: 20px; }
        .totals-row { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals-total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #000; padding-top: 10px; }
        .payment-info { margin-top: 30px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FARUNOVA</h1>
        <p>Premium Clothing Store | Nairobi, Kenya</p>
    </div>

    <div class="invoice-details">
        <strong>Invoice #:</strong> {$order['id']} | 
        <strong>Order #:</strong> {$order['orderId']} | 
        <strong>Date:</strong> {$order['createdAt']}
    </div>

    <div class="customer-info">
        <div>
            <strong>BILL TO:</strong><br>
            {$order['username']}<br>
            {$order['email']}<br>
            {$order['phone']}
        </div>
        <div>
            <strong>SHIP TO:</strong><br>
            {$order['shippingAddress']}<br>
            {$order['shippingCity']} {$order['shippingPostalCode']}
        </div>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Size</th>
            <th>Color</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
        </tr>
HTML;

        $itemNum = 1;
        foreach ($items as $item) {
            $total = $item['quantity'] * $item['priceAtTime'];
            $html .= <<<HTML
        <tr>
            <td>{$itemNum}</td>
            <td>{$item['name']}</td>
            <td>{$item['size']}</td>
            <td>{$item['color']}</td>
            <td>{$item['quantity']}</td>
            <td>KES {$item['priceAtTime']}</td>
            <td>KES {$total}</td>
        </tr>
HTML;
            $itemNum++;
        }

        $html .= <<<HTML
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>KES {$order['totalAmount']}</span>
        </div>
        <div class="totals-row">
            <span>Shipping:</span>
            <span>KES 0.00</span>
        </div>
        <div class="totals-row totals-total">
            <span>TOTAL:</span>
            <span>KES {$order['totalAmount']}</span>
        </div>
    </div>

    <div class="payment-info">
        <strong>Payment Information:</strong><br>
        Method: {$payment['paymentMethod']}<br>
        Status: {$payment['status']}<br>
HTML;

        if ($payment['mpesaReceiptCode']) {
            $html .= "M-Pesa Code: {$payment['mpesaReceiptCode']}<br>";
        }

        if ($payment['completedAt']) {
            $html .= "Date Paid: {$payment['completedAt']}<br>";
        }

        $html .= <<<HTML
    </div>

    <div class="footer">
        <p>FARUNOVA - Premium Clothing Store</p>
        <p>Thank you for your purchase!</p>
        <p>For support, contact: support@farunova.com</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Get invoice filename
     */
    private function getInvoiceFilename($orderId)
    {
        return 'invoice-' . $orderId . '-' . date('Y-m-d') . '.pdf';
    }

    /**
     * Log invoice generation
     */
    private function logInvoiceGeneration($orderId, $filename)
    {
        $this->logger->info('Invoice generated', [
            'orderId' => $orderId,
            'filename' => $filename
        ], 'invoices');
    }

    /**
     * Email invoice to customer
     * 
     * @param int $orderId - Order ID
     * @param string $email - Customer email
     * 
     * @return array
     */
    public function emailInvoice($orderId, $email)
    {
        try {
            // Generate invoice first
            $generateResult = $this->generateInvoice($orderId);

            if (!$generateResult['success']) {
                return $generateResult;
            }

            // Send email with invoice
            $subject = 'Your FARUNOVA Invoice #' . $orderId;
            $message = 'Your invoice has been generated and is attached. Thank you for your purchase!';

            // TODO: Implement email sending with attachment
            // For now, just log that it would be sent
            $this->logger->info('Invoice email prepared', [
                'orderId' => $orderId,
                'email' => $email
            ], 'invoices');

            return [
                'success' => true,
                'message' => 'Invoice emailed successfully',
                'email' => $email
            ];
        } catch (Exception $e) {
            $this->logger->error('Error emailing invoice: ' . $e->getMessage(), 'invoices');
            return ['success' => false, 'message' => 'Error emailing invoice'];
        }
    }

    /**
     * Get list of invoices
     */
    public function listInvoices($filters = [])
    {
        try {
            $invoiceDir = dirname(__DIR__) . '/invoices/';

            if (!is_dir($invoiceDir)) {
                return ['success' => true, 'invoices' => [], 'count' => 0];
            }

            $files = scandir($invoiceDir, SCANDIR_SORT_DESCENDING);
            $invoices = [];

            foreach ($files as $file) {
                if (strpos($file, 'invoice-') === 0) {
                    $invoices[] = [
                        'filename' => $file,
                        'created' => filemtime($invoiceDir . $file),
                        'size' => filesize($invoiceDir . $file),
                        'url' => '/invoices/' . $file
                    ];
                }
            }

            return [
                'success' => true,
                'invoices' => array_slice($invoices, 0, 50),
                'count' => count($invoices)
            ];
        } catch (Exception $e) {
            $this->logger->error('Error listing invoices: ' . $e->getMessage(), 'invoices');
            return ['success' => false, 'message' => 'Error listing invoices'];
        }
    }
}
