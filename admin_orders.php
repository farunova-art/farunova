<?php
include("connection.php");

// Check if user is admin
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

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();
}

// Get all orders
$stmt = $conn->prepare("SELECT o.*, u.username, u.email FROM orders o
                        JOIN users u ON o.userId = u.id
                        ORDER BY o.createdAt DESC");
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$pending_orders = 0;
$processing_orders = 0;
$shipped_orders = 0;
$total_sales = 0;

foreach ($orders as $order) {
    if ($order['status'] === 'pending') $pending_orders++;
    if ($order['status'] === 'processing') $processing_orders++;
    if ($order['status'] === 'shipped') $shipped_orders++;
    if ($order['paymentStatus'] === 'completed') $total_sales += $order['totalAmount'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - FARUNOVA Admin</title>
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

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #088F8F;
            text-align: center;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #2B547E;
        }

        .stat-label {
            font-size: 12px;
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

        .badge-processing {
            background-color: #17a2b8;
            color: white;
        }

        .badge-shipped {
            background-color: #2B547E;
            color: white;
        }

        .badge-delivered {
            background-color: #28a745;
        }

        .badge-completed {
            background-color: #28a745;
        }

        .badge-failed {
            background-color: #ff4757;
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
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="bi bi-graph-up"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="bi bi-box"></i> Products</a></li>
            <li><a href="admin_orders.php" class="active"><i class="bi bi-receipt"></i> Orders</a></li>
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
            <h5 style="margin: 0; color: #2B547E;">Orders Management</h5>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_orders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $processing_orders; ?></div>
                    <div class="stat-label">Processing</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-number">KES <?php echo number_format($total_sales, 0); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">All Orders</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Order Status</th>
                                <th>Payment</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars(substr($order['orderId'], 0, 15)); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['username']); ?><br>
                                        <small style="color: #999;"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><strong>KES <?php echo number_format($order['totalAmount'], 2); ?></strong></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $order['paymentStatus'] === 'completed' ? 'completed' : ($order['paymentStatus'] === 'failed' ? 'failed' : 'pending'); ?>">
                                            <?php echo ucfirst($order['paymentStatus']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $order['paymentMethod'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['createdAt'])); ?></td>
                                    <td>
                                        <a href="admin_order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>