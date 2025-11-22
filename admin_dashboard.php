<?php
include("connection.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Get user role
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

// Get dashboard statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$total_orders_result = $stmt->get_result();
$total_orders = $total_orders_result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT SUM(totalAmount) as total FROM orders WHERE paymentStatus = 'completed'");
$stmt->execute();
$total_revenue_result = $stmt->get_result();
$total_revenue = $total_revenue_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$total_products_result = $stmt->get_result();
$total_products = $total_products_result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0");
$stmt->execute();
$low_stock_result = $stmt->get_result();
$low_stock = $low_stock_result->fetch_assoc()['total'];
$stmt->close();

// Get recent orders
$stmt = $conn->prepare("SELECT o.*, u.email, u.username FROM orders o
                        JOIN users u ON o.userId = u.id
                        ORDER BY o.createdAt DESC
                        LIMIT 5");
$stmt->execute();
$recent_orders_result = $stmt->get_result();
$recent_orders = $recent_orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FARUNOVA</title>
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

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            padding: 0;
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

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .top-bar-left {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #088F8F;
        }

        .stat-icon {
            font-size: 32px;
            color: #088F8F;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2B547E;
        }

        .stat-label {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
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
            color: #2B547E;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-completed {
            background-color: #28a745;
        }

        .badge-shipped {
            background-color: #17a2b8;
        }

        .action-btn {
            padding: 5px 10px;
            font-size: 12px;
            margin: 0 2px;
        }

        .btn-add {
            background-color: #2B547E;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-add:hover {
            background-color: #088F8F;
            text-decoration: none;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }

            .stat-card {
                margin-bottom: 15px;
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
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active"><i class="bi bi-graph-up"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="bi bi-box"></i> Products</a></li>
            <li><a href="admin_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="admin_analytics.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
            <li><a href="edit.php"><i class="bi bi-person"></i> Profile</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">Dashboard</div>
            <div class="top-bar-right">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <img src="https://via.placeholder.com/40" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%;">
            </div>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                    <div class="stat-number">KES <?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-box"></i></div>
                    <div class="stat-number"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="stat-number"><?php echo $low_stock; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Orders</span>
                <a href="admin_orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['orderId']); ?></strong></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><strong>KES <?php echo number_format($order['totalAmount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-pending"><?php echo ucfirst($order['status']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $order['paymentStatus'] === 'completed' ? 'completed' : 'pending'; ?>">
                                        <?php echo ucfirst($order['paymentStatus']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['createdAt'])); ?></td>
                                <td>
                                    <a href="admin_order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary action-btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body">
                        <a href="admin_products.php?action=add" class="btn btn-add w-100" style="margin-bottom: 10px;">
                            <i class="bi bi-plus-circle"></i> Add New Product
                        </a>
                        <a href="admin_orders.php" class="btn btn-add w-100">
                            <i class="bi bi-receipt"></i> Manage Orders
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">System Info</div>
                    <div class="card-body">
                        <p><strong>Database:</strong> <?php echo htmlspecialchars($db); ?></p>
                        <p><strong>Server:</strong> <?php echo htmlspecialchars($server); ?></p>
                        <p><strong>Admin User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>