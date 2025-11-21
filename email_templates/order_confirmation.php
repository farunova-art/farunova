<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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

        .order-id {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 10px;
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

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 1;
            color: #333;
        }

        .item-details {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
            color: #088F8F;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #2B547E;
            border-top: 2px solid #2B547E;
            padding-top: 15px;
            margin-top: 15px;
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
            <h1>✓ Order Confirmed!</h1>
            <div class="order-id">Order #<?php echo htmlspecialchars($orderId); ?></div>
        </div>

        <p>Thank you for your order! We're excited to get your items to you.</p>

        <div class="section">
            <div class="section-title">Order Details</div>
            <div class="summary-row">
                <span>Order Number:</span>
                <span><?php echo htmlspecialchars($orderId); ?></span>
            </div>
            <div class="summary-row">
                <span>Order Date:</span>
                <span><?php echo htmlspecialchars($date); ?></span>
            </div>
            <div class="summary-row">
                <span>Order Status:</span>
                <span>Confirmed</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Items Ordered</div>
            <?php foreach ($items as $item): ?>
                <div class="item-row">
                    <div>
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></div>
                        <div class="item-details">Size: <?php echo htmlspecialchars($item['size']); ?> | Color: <?php echo htmlspecialchars($item['color']); ?></div>
                    </div>
                    <div class="item-price">KES <?php echo number_format($item['priceAtTime'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <div class="section-title">Order Summary</div>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>KES <?php echo number_format($total - 500 - (($total - 500) * 0.16), 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Tax (16%):</span>
                <span>KES <?php echo number_format(($total - 500) * 0.16, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span>KES 500.00</span>
            </div>
            <div class="summary-row total">
                <span>Total Amount:</span>
                <span>KES <?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">What's Next?</div>
            <p>We'll send you an email when your order ships. You can track your order status anytime by visiting your account dashboard.</p>
            <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>order_tracking.php" class="btn">Track Your Order</a>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>For questions about your order, please <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>contact.php">contact us</a>.</p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>