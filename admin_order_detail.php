<?php
session_start();
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

if (!isset($_GET['id'])) {
    header("location: admin_orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.username, u.email, u.phone FROM orders o
                        JOIN users u ON o.userId = u.id
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    header("location: admin_orders.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi
                        JOIN products p ON oi.productId = p.id
                        WHERE oi.orderId = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - FARUNOVA Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .detail-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .detail-section:last-child {
            border-bottom: none;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #2B547E;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-completed {
            background-color: #28a745;
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
            <li><a href="admin_orders.php" class="active"><i class="bi bi-receipt"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h5 style="margin: 0; color: #2B547E;">Order Details</h5>
            <a href="admin_orders.php" class="btn btn-sm btn-outline-secondary">Back to Orders</a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Information -->
                <div class="card">
                    <div class="card-header">Order Information</div>
                    <div class="detail-section">
                        <div class="detail-row">
                            <span class="detail-label">Order ID:</span>
                            <span><?php echo htmlspecialchars($order['orderId']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span><?php echo date('F d, Y g:i A', strtotime($order['createdAt'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span><span class="badge badge-pending"><?php echo ucfirst($order['status']); ?></span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span><span class="badge badge-<?php echo $order['paymentStatus'] === 'completed' ? 'completed' : 'pending'; ?>"><?php echo ucfirst($order['paymentStatus']); ?></span></span>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card">
                    <div class="card-header">Customer Information</div>
                    <div class="detail-section">
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span><?php echo htmlspecialchars($order['username']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone:</span>
                            <span><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="card">
                    <div class="card-header">Shipping Information</div>
                    <div class="detail-section">
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span><?php echo htmlspecialchars($order['shippingAddress']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">City:</span>
                            <span><?php echo htmlspecialchars($order['shippingCity']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Postal Code:</span>
                            <span><?php echo htmlspecialchars($order['shippingPostalCode'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Country:</span>
                            <span><?php echo htmlspecialchars($order['shippingCountry']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <div class="card-header">Order Items</div>
                    <div class="detail-section">
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                    <small style="color: #999;">
                                        Size: <?php echo htmlspecialchars($item['size']); ?> |
                                        Color: <?php echo htmlspecialchars($item['color']); ?> |
                                        Qty: <?php echo $item['quantity']; ?>
                                    </small>
                                </div>
                                <div style="font-weight: 600;">
                                    KES <?php echo number_format($item['priceAtTime'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Order Summary</div>
                    <div style="padding: 20px;">
                        <div class="detail-row">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($order['totalAmount'] - 500 - (($order['totalAmount'] - 500) * 0.16), 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Tax:</span>
                            <span>KES <?php echo number_format((($order['totalAmount'] - 500) * 0.16), 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Shipping:</span>
                            <span>KES 500.00</span>
                        </div>
                        <div class="detail-row" style="font-size: 18px; font-weight: 700; color: #088F8F; border-top: 2px solid #ddd; padding-top: 10px; margin-top: 10px;">
                            <span>Total:</span>
                            <span>KES <?php echo number_format($order['totalAmount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>