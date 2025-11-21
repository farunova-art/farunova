<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
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
            background: linear-gradient(135deg, #d32f2f 0%, #ff6f00 100%);
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

        .warning-icon {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .alert-section {
            background-color: #FFF3E0;
            border-left: 4px solid #FF6F00;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .alert-title {
            color: #E65100;
            font-weight: 700;
            font-size: 16px;
            margin: 0 0 10px 0;
        }

        .alert-text {
            color: #D84315;
            font-size: 14px;
            margin: 0;
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

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table thead {
            background-color: #f5f5f5;
        }

        .product-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2B547E;
            border-bottom: 2px solid #ddd;
            font-size: 13px;
        }

        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .product-table tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            font-weight: 600;
            color: #2B547E;
        }

        .stock-low {
            background-color: #FFEBEE;
            color: #c62828;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
        }

        .stock-critical {
            background-color: #ffcdd2;
            color: #b71c1c;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 700;
            text-align: center;
        }

        .action-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action-items li {
            padding: 10px 0 10px 30px;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }

        .action-items li:last-child {
            border-bottom: none;
        }

        .action-items li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #FF6F00;
            font-weight: bold;
            font-size: 18px;
        }

        .btn {
            display: inline-block;
            background-color: #2B547E;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #FF6F00;
        }

        .btn-primary:hover {
            background-color: #E65100;
        }

        .btn-secondary {
            background-color: #2B547E;
        }

        .btn-secondary:hover {
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
            <div class="warning-icon">⚠️</div>
            <h1>Low Stock Alert</h1>
            <p>Products need inventory restocking</p>
        </div>

        <div class="alert-section">
            <p class="alert-title">Action Required</p>
            <p class="alert-text">The following products are running low on inventory and should be restocked as soon as possible to avoid stock-outs.</p>
        </div>

        <div class="section">
            <div class="section-title">Low Stock Products</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="product-name"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['currentStock']; ?> units</td>
                            <td><?php echo $product['reorderLevel']; ?> units</td>
                            <td>
                                <?php if ($product['currentStock'] < $product['reorderLevel'] / 2): ?>
                                    <span class="stock-critical">CRITICAL</span>
                                <?php else: ?>
                                    <span class="stock-low">LOW</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Recommended Actions</div>
            <ul class="action-items">
                <li><strong>Update Product Stock</strong> - Log in to the admin dashboard and update inventory levels</li>
                <li><strong>Place Supplier Orders</strong> - Contact your suppliers to place restock orders immediately</li>
                <li><strong>Check Demand</strong> - Review recent sales to determine appropriate order quantities</li>
                <li><strong>Monitor Stock Movement</strong> - Track when items are most likely to sell out</li>
                <li><strong>Adjust Pricing</strong> - Consider promotional pricing to move slow-moving inventory</li>
            </ul>
        </div>

        <div class="section">
            <div class="section-title">Quick Links</div>
            <p>Manage inventory from your admin dashboard:</p>
            <div style="margin-top: 15px;">
                <a href="<?php echo htmlspecialchars($adminUrl ?? 'admin_products.php'); ?>" class="btn btn-primary">Go to Products</a>
                <a href="<?php echo htmlspecialchars($adminUrl ?? 'admin_analytics.php'); ?>" class="btn btn-secondary">View Analytics</a>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Stock Management Tips</div>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Maintain stock levels based on historical sales data</li>
                <li>Set appropriate reorder points for each product</li>
                <li>Review stock levels weekly</li>
                <li>Consider seasonal demand patterns</li>
                <li>Build buffer stock for popular items</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>This is an automated alert. Please take action to maintain adequate inventory levels.</p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>