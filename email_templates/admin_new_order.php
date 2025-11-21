<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Notification</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
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

        .alert {
            background-color: #FFF5E1;
            border-left: 4px solid #FF9800;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #E65100;
            font-weight: 600;
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

        .info-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #999;
            font-size: 13px;
            font-weight: 600;
        }

        .info-value {
            color: #2B547E;
            font-weight: 600;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .item-table th {
            background-color: #2B547E;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        .item-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .item-table tr:last-child td {
            border-bottom: none;
        }

        .item-table .item-name {
            font-weight: 600;
            color: #2B547E;
        }

        .total-row {
            background-color: #f9f9f9;
            font-weight: 700;
            color: #2B547E;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            flex: 1;
            display: inline-block;
            padding: 12px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #2B547E;
            color: white;
        }

        .btn-primary:hover {
            background-color: #088F8F;
        }

        .btn-secondary {
            background-color: #088F8F;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #2B547E;
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

        .status-badge {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛒 New Order Received</h1>
            <p>Order #<?php echo htmlspecialchars($orderId); ?></p>
        </div>

        <div class="alert">
            ⏱️ Action Required: Please review and process this order within 24 hours
        </div>

        <div class="section">
            <div class="section-title">Order Summary</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">#<?php echo htmlspecialchars($orderId); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value"><?php echo htmlspecialchars($orderDate); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">KES <?php echo number_format($orderTotal, 2); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Status:</span>
                    <span class="info-value"><span class="status-badge"><?php echo htmlspecialchars($paymentStatus ?? 'Completed'); ?></span></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Customer Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($customerName); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($customerEmail); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($customerPhone ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Shipping Address</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingAddress ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">City:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingCity ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Postal Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingPostal ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Country:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingCountry ?? 'Kenya'); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Order Items</div>
            <table class="item-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="item-name"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>KES <?php echo number_format($item['priceAtTime'], 2); ?></td>
                            <td>KES <?php echo number_format($item['priceAtTime'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td>KES <?php echo number_format($orderTotal, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Next Steps</div>
            <ol style="margin: 0; padding-left: 20px;">
                <li>Review the order details above</li>
                <li>Verify payment has been received</li>
                <li>Pick and pack the items</li>
                <li>Update the order status in the admin dashboard</li>
                <li>Generate shipping label and ship the order</li>
                <li>Update customer with tracking information</li>
            </ol>

            <div class="action-buttons">
                <a href="<?php echo htmlspecialchars($adminUrl ?? 'admin_orders.php'); ?>" class="btn btn-primary">View Order in Dashboard</a>
                <a href="<?php echo htmlspecialchars($adminUrl ?? 'admin_order_detail.php'); ?>?id=<?php echo htmlspecialchars($orderId); ?>" class="btn btn-secondary">Order Details</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>