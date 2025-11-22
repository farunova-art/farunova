<?php
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$tracked_order = null;
$order_items = [];
$error = null;

// Check if a specific order is being tracked
if (isset($_GET['order_id'])) {
    $order_id = sanitizeInput($_GET['order_id']);

    // Get order details using prepared statement
    $order_stmt = $conn->prepare("SELECT o.*, u.email FROM orders o
                                  JOIN users u ON o.userId = u.id
                                  WHERE o.orderId = ? AND o.userId = ?");
    $order_stmt->bind_param("si", $order_id, $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $tracked_order = $order_result->fetch_assoc();
    $order_stmt->close();

    if (!$tracked_order) {
        $error = "Order not found.";
    } else {
        // Get order items using prepared statement
        $items_stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi
                                      JOIN products p ON oi.productId = p.id
                                      WHERE oi.orderId = ?");
        $items_stmt->bind_param("i", $tracked_order['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();
    }
}

// Get all orders for the user using prepared statement
$orders_stmt = $conn->prepare("SELECT id, orderId, totalAmount, status, paymentStatus, createdAt
                              FROM orders
                              WHERE userId = ?
                              ORDER BY createdAt DESC
                              LIMIT 10");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$user_orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

// Status timeline helper
$statusTimeline = [
    'pending' => ['label' => 'Pending', 'icon' => 'hourglass-split', 'color' => '#FFA500'],
    'processing' => ['label' => 'Processing', 'icon' => 'gear', 'color' => '#FF9800'],
    'shipped' => ['label' => 'Shipped', 'icon' => 'truck', 'color' => '#4CAF50'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'check-circle', 'color' => '#2196F3'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'x-circle', 'color' => '#f44336']
];

$statusOrder = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            text-align: center;
        }

        .tracking-container {
            padding: 20px 0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #088F8F;
        }

        .tracking-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
            color: #2B547E;
        }

        .order-date {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-processing {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-shipped {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .status-delivered {
            background-color: #E3F2FD;
            color: #1565C0;
        }

        .status-cancelled {
            background-color: #FFEBEE;
            color: #C62828;
        }

        .timeline {
            position: relative;
            padding: 30px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 60px;
            width: 2px;
            height: 30px;
            background: #ddd;
            z-index: 0;
        }

        .timeline-marker {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border: 3px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 20px;
            color: #999;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .timeline-marker.active {
            background: #088F8F;
            border-color: #088F8F;
            color: white;
        }

        .timeline-marker.completed {
            background: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .timeline-content h5 {
            margin: 0 0 5px 0;
            color: #2B547E;
            font-weight: 600;
        }

        .timeline-content p {
            margin: 0;
            color: #999;
            font-size: 13px;
        }

        .timeline-date {
            color: #999;
            font-size: 12px;
            margin-top: 8px;
        }

        .order-items {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
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

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 5px;
        }

        .item-details {
            font-size: 12px;
            color: #999;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
            color: #088F8F;
        }

        .order-summary {
            background-color: #f0f8ff;
            border-left: 4px solid #088F8F;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .summary-row.total {
            font-weight: 700;
            color: #2B547E;
            border-top: 2px solid #088F8F;
            padding-top: 12px;
            margin-top: 12px;
        }

        .order-list {
            margin: 20px 0;
        }

        .order-list-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .order-list-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: #088F8F;
        }

        .order-list-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .order-list-item-row:last-child {
            margin-bottom: 0;
        }

        .order-list-id {
            font-weight: 600;
            color: #2B547E;
            font-size: 16px;
        }

        .order-list-date {
            font-size: 12px;
            color: #999;
        }

        .order-list-amount {
            font-weight: 600;
            color: #088F8F;
        }

        .no-orders {
            background: white;
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .no-orders i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            color: #ddd;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-custom {
            flex: 1;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background-color: #2B547E;
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: #088F8F;
            color: white;
        }

        .btn-secondary-custom {
            background-color: #f0f0f0;
            color: #2B547E;
            border: 2px solid #2B547E;
        }

        .btn-secondary-custom:hover {
            background-color: #2B547E;
            color: white;
        }

        .shipping-info {
            background: #f0f8ff;
            border-left: 4px solid #088F8F;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }

        .shipping-info label {
            display: block;
            color: #999;
            font-size: 12px;
            margin-bottom: 3px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .shipping-info value {
            display: block;
            color: #2B547E;
            margin-bottom: 12px;
        }

        @media (max-width: 768px) {
            .tracking-card {
                padding: 20px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .timeline-item {
                margin-bottom: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <header class="navbar-section">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php"><i class="bi bi-bag"></i> FARUNOVA</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="products.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a></li>
                        <li class="nav-item"><a class="nav-link active" href="order_tracking.php"><i class="bi bi-truck"></i> Track Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="home.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <h1>Track Your Orders</h1>
        <p>Monitor your order status and shipping information</p>
    </div>

    <!-- Main Content -->
    <div class="container tracking-container">
        <?php if ($tracked_order): ?>
            <!-- Single Order Tracking -->
            <div class="tracking-card">
                <div class="order-header">
                    <div>
                        <div class="order-id"><?php echo htmlspecialchars($tracked_order['orderId']); ?></div>
                        <div class="order-date">
                            <i class="bi bi-calendar"></i>
                            <?php echo date('F d, Y', strtotime($tracked_order['createdAt'])); ?>
                        </div>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo strtolower($tracked_order['status']); ?>">
                            <?php echo ucfirst($tracked_order['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Status Timeline -->
                <h5 class="section-title" style="border-bottom: 2px solid #ddd; margin-bottom: 20px;">Order Timeline</h5>
                <div class="timeline">
                    <?php
                    $currentStatusIndex = array_search($tracked_order['status'], $statusOrder);
                    foreach ($statusOrder as $index => $statusKey) {
                        if ($statusKey === 'cancelled' && $tracked_order['status'] !== 'cancelled') {
                            continue;
                        }

                        $statusInfo = $statusTimeline[$statusKey];
                        $isCompleted = $index < $currentStatusIndex || $tracked_order['status'] === $statusKey;
                        $isActive = $tracked_order['status'] === $statusKey;
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $isActive ? 'active' : ($isCompleted ? 'completed' : ''); ?>">
                                <i class="bi bi-<?php echo $statusInfo['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h5><?php echo $statusInfo['label']; ?></h5>
                                <p><?php
                                    switch ($statusKey) {
                                        case 'pending':
                                            echo 'Your order is being prepared for shipment';
                                            break;
                                        case 'processing':
                                            echo 'We are picking and packing your items';
                                            break;
                                        case 'shipped':
                                            echo 'Your package is on its way';
                                            break;
                                        case 'delivered':
                                            echo 'Your order has been delivered';
                                            break;
                                        case 'cancelled':
                                            echo 'Your order has been cancelled';
                                            break;
                                    }
                                    ?></p>
                                <?php if ($isCompleted && $isActive): ?>
                                    <div class="timeline-date"><?php echo date('F d, Y', strtotime($tracked_order['createdAt'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <!-- Order Items -->
                <h5 class="section-title" style="border-bottom: 2px solid #ddd; margin: 30px 0 20px;">Order Items</h5>
                <div class="order-items">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-details">
                                    Quantity: <?php echo $item['quantity']; ?> |
                                    Size: <?php echo htmlspecialchars($item['size']); ?> |
                                    Color: <?php echo htmlspecialchars($item['color']); ?>
                                </div>
                            </div>
                            <div class="item-price">
                                KES <?php echo number_format($item['priceAtTime'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Order Total:</span>
                        <span>KES <?php echo number_format($tracked_order['totalAmount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Payment Status:</span>
                        <span><?php echo ucfirst($tracked_order['paymentStatus']); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Status:</span>
                        <span><?php echo ucfirst($tracked_order['status']); ?></span>
                    </div>
                </div>

                <!-- Shipping Information -->
                <h5 class="section-title" style="border-bottom: 2px solid #ddd; margin: 30px 0 20px;">Shipping Address</h5>
                <div class="shipping-info">
                    <label>Address</label>
                    <value><?php echo htmlspecialchars($tracked_order['shippingAddress']); ?></value>

                    <label>City</label>
                    <value><?php echo htmlspecialchars($tracked_order['shippingCity']); ?></value>

                    <label>Postal Code</label>
                    <value><?php echo htmlspecialchars($tracked_order['shippingPostalCode']); ?></value>

                    <label>Country</label>
                    <value><?php echo htmlspecialchars($tracked_order['shippingCountry']); ?></value>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="order_tracking.php" class="btn-custom btn-secondary-custom">
                        <i class="bi bi-arrow-left"></i> Back to All Orders
                    </a>
                    <a href="contact.php" class="btn-custom btn-primary-custom">
                        <i class="bi bi-chat"></i> Contact Support
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- All Orders List -->
            <h2 class="section-title">Your Orders</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (count($user_orders) > 0): ?>
                <div class="order-list">
                    <?php foreach ($user_orders as $order): ?>
                        <div class="order-list-item" onclick="window.location.href='order_tracking.php?order_id=<?php echo urlencode($order['orderId']); ?>'">
                            <div class="order-list-item-row">
                                <div class="order-list-id"><?php echo htmlspecialchars($order['orderId']); ?></div>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="order-list-item-row">
                                <div class="order-list-date">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo date('F d, Y', strtotime($order['createdAt'])); ?>
                                </div>
                                <div class="order-list-amount">
                                    KES <?php echo number_format($order['totalAmount'], 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="bi bi-box"></i>
                    <h5>No Orders Yet</h5>
                    <p>You haven't placed any orders yet. Start shopping to track your order here!</p>
                    <a href="products.php" class="btn-custom btn-primary-custom" style="display: inline-block; margin-top: 15px;">
                        <i class="bi bi-shop"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer style="background-color: #2B547E; color: white; margin-top: 50px; padding: 30px 0; text-align: center;">
        <p>&copy; 2025 FARUNOVA - Authentic Clothing Store. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>