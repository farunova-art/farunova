<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .status-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 10px;
            font-weight: 600;
        }

        .status-badge.processing {
            background-color: #FFA500;
        }

        .status-badge.shipped {
            background-color: #4CAF50;
        }

        .status-badge.delivered {
            background-color: #2196F3;
        }

        .section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 18px;
            color: #2B547E;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .timeline {
            margin: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 20px;
        }

        .timeline-marker {
            width: 40px;
            height: 40px;
            background-color: #088F8F;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .timeline-marker.completed {
            background-color: #4CAF50;
        }

        .timeline-content h4 {
            margin: 0 0 5px 0;
            color: #2B547E;
            font-size: 16px;
        }

        .timeline-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .timeline-date {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .tracking-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #088F8F;
            border-radius: 5px;
            margin: 15px 0;
        }

        .tracking-info label {
            color: #999;
            font-size: 12px;
            display: block;
            margin-bottom: 5px;
        }

        .tracking-info value {
            color: #2B547E;
            font-weight: 600;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            background-color: #2B547E;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #088F8F;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }

        .footer a {
            color: #2B547E;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Order Status Updated</h1>
            <div class="status-badge <?php echo strtolower(str_replace(' ', '_', $status)); ?>">
                <?php echo htmlspecialchars($status); ?>
            </div>
        </div>

        <p>Hi <?php echo htmlspecialchars($customerName); ?>,</p>
        <p>Great news! Your order #<?php echo htmlspecialchars($orderId); ?> has been <strong><?php echo strtolower($status); ?></strong>.</p>

        <div class="section">
            <div class="section-title">Order Timeline</div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker completed">✓</div>
                    <div class="timeline-content">
                        <h4>Order Confirmed</h4>
                        <p>Your order has been confirmed and payment received.</p>
                        <div class="timeline-date"><?php echo htmlspecialchars($dateConfirmed); ?></div>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker <?php echo in_array($status, ['Processing', 'Shipped', 'Delivered']) ? 'completed' : ''; ?>">
                        <?php echo in_array($status, ['Processing', 'Shipped', 'Delivered']) ? '✓' : '2'; ?>
                    </div>
                    <div class="timeline-content">
                        <h4>Processing</h4>
                        <p>We're preparing your order for shipment.</p>
                        <?php if (in_array($status, ['Processing', 'Shipped', 'Delivered'])): ?>
                            <div class="timeline-date"><?php echo htmlspecialchars($dateProcessing ?? 'Completed'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker <?php echo in_array($status, ['Shipped', 'Delivered']) ? 'completed' : ''; ?>">
                        <?php echo in_array($status, ['Shipped', 'Delivered']) ? '✓' : '3'; ?>
                    </div>
                    <div class="timeline-content">
                        <h4>Shipped</h4>
                        <p>Your package is on its way to you.</p>
                        <?php if (in_array($status, ['Shipped', 'Delivered'])): ?>
                            <div class="timeline-date"><?php echo htmlspecialchars($dateShipped ?? 'Pending'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker <?php echo $status === 'Delivered' ? 'completed' : ''; ?>">
                        <?php echo $status === 'Delivered' ? '✓' : '4'; ?>
                    </div>
                    <div class="timeline-content">
                        <h4>Delivered</h4>
                        <p>Your order has been delivered successfully.</p>
                        <?php if ($status === 'Delivered'): ?>
                            <div class="timeline-date"><?php echo htmlspecialchars($dateDelivered ?? 'In transit'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($trackingNumber)): ?>
            <div class="section">
                <div class="section-title">Tracking Information</div>
                <div class="tracking-info">
                    <label>Tracking Number:</label>
                    <value><?php echo htmlspecialchars($trackingNumber); ?></value>
                </div>
                <p>You can track your shipment using the tracking number above on the carrier's website.</p>
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-title">Order Details</div>
            <div class="summary-row">
                <strong>Order Number:</strong> <?php echo htmlspecialchars($orderId); ?>
            </div>
            <div class="summary-row">
                <strong>Order Total:</strong> KES <?php echo number_format($orderTotal, 2); ?>
            </div>
        </div>

        <div class="section">
            <p>If you have any questions about your order, please don't hesitate to <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>contact.php">contact us</a>.</p>
            <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>order_tracking.php" class="btn">View Full Order Details</a>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>