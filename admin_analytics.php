<?php
include("connection.php");

if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

if ($user['role'] !== 'admin') {
    header("location: home.php");
    exit();
}

// Get analytics data
$stmt = $conn->prepare("SELECT SUM(totalAmount) as total, DATE_FORMAT(createdAt, '%Y-%m') as month FROM orders WHERE paymentStatus = 'completed' GROUP BY DATE_FORMAT(createdAt, '%Y-%m') ORDER BY month DESC LIMIT 12");
$stmt->execute();
$monthly_sales_result = $stmt->get_result();
$monthly_sales = $monthly_sales_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Category sales
$stmt = $conn->prepare("SELECT p.category, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.priceAtTime) as total_amount FROM order_items oi
                         JOIN products p ON oi.productId = p.id
                         JOIN orders o ON oi.orderId = o.id
                         WHERE o.paymentStatus = 'completed'
                         GROUP BY p.category");
$stmt->execute();
$category_sales_result = $stmt->get_result();
$category_sales = $category_sales_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - FARUNOVA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .sidebar {
            background: linear-gradient(135deg, #2B547E 0%, #1a3a52 100%);
            min-height: 100vh;
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #088F8F;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #2B547E;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="bi bi-gear"></i> Admin
        </div>
        <ul class="sidebar-menu" style="list-style: none; padding: 0; margin: 0;">
            <li><a href="admin_dashboard.php"><i class="bi bi-graph-up"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="bi bi-box"></i> Products</a></li>
            <li><a href="admin_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="admin_analytics.php" class="active"><i class="bi bi-bar-chart"></i> Analytics</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h5 style="margin: 0; color: #2B547E;">Analytics Dashboard</h5>
        </div>

        <!-- Sales by Category -->
        <div class="card">
            <div class="card-header">Sales by Category</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category_sales as $category): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['category']); ?></strong></td>
                                <td><?php echo $category['total_qty']; ?> units</td>
                                <td><strong>KES <?php echo number_format($category['total_amount'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="card">
            <div class="card-header">Monthly Sales</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sales Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_sales as $sale): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($sale['month'] . '-01')); ?></td>
                                <td><strong>KES <?php echo number_format($sale['total'], 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>