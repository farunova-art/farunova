<?php
session_start();
include("connection.php");

if (!isset($_GET['order_id'])) {
    header("location: index.php");
    exit();
}

$order_id = $conn->real_escape_string($_GET['order_id']);

// Get order details
$order_query = "SELECT * FROM orders WHERE orderId = '$order_id'";
$order_result = mysqli_query($conn, $order_query);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("location: index.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name FROM order_items oi
                JOIN products p ON oi.productId = p.id
                WHERE oi.orderId = " . $order['id'];
$items_result = mysqli_query($conn, $items_query);
$order_items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmation-container {
            padding: 60px 0;
        }

        .success-banner {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-banner h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .success-banner p {
            font-size: 18px;
            margin: 0;
        }

        .order-details {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            font-size: 16px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #2B547E;
        }

        .detail-value {
            color: #555;
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

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2B547E;
            font-size: 16px;
        }

        .item-specs {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
            color: #088F8F;
        }

        .summary-table {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
            border: none;
            padding-top: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary-custom {
            flex: 1;
            padding: 15px;
            background-color: #2B547E;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary-custom:hover {
            background-color: #088F8F;
            text-decoration: none;
            color: white;
        }

        .btn-secondary-custom {
            flex: 1;
            padding: 15px;
            background-color: white;
            color: #2B547E;
            border: 2px solid #2B547E;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background-color: #2B547E;
            color: white;
            text-decoration: none;
        }

        .payment-notice {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ddd;
            z-index: -1;
        }

        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .timeline-dot {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            z-index: 1;
        }

        .timeline-step.active .timeline-dot {
            background-color: #28a745;
        }

        .timeline-step.pending .timeline-dot {
            background-color: #ffc107;
        }

        .timeline-label {
            font-size: 14px;
            font-weight: 600;
            color: #2B547E;
        }

        .timeline-status {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }

            .detail-row {
                flex-direction: column;
            }

            .order-item {
                flex-direction: column;
            }

            .item-price {
                text-align: left;
                margin-top: 10px;
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
                        <?php if (isset($_SESSION['username'])): ?>
                            <li class="nav-item"><a class="nav-link" href="home.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                            <li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Confirmation Content -->
    <div class="container confirmation-container">
        <!-- Success Banner -->
        <div class="success-banner">
            <div class="success-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. We'll send updates to your email soon.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Summary -->
                <div class="order-details">
                    <h4 style="color: #2B547E; margin-bottom: 20px;">Order Information</h4>

                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value" style="font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($order['orderId']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('F d, Y \a\t g:i A', strtotime($order['createdAt'])); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php
                            $status_colors = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $color = $status_colors[$order['status']] ?? 'secondary';
                            ?>
                            <span style="background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value">
                            <span style="background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;">
                                <?php echo ucfirst($order['paymentStatus']); ?>
                            </span>
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['paymentMethod'])); ?></span>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="order-details">
                    <h4 style="color: #2B547E; margin-bottom: 20px;">Shipping Address</h4>

                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shippingAddress']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">City:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shippingCity']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Postal Code:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shippingPostalCode']); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Country:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['shippingCountry']); ?></span>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="order-details">
                    <h4 style="color: #2B547E; margin-bottom: 20px;">Order Items</h4>

                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-specs">
                                    Size: <?php echo htmlspecialchars($item['size']); ?> |
                                    Color: <?php echo htmlspecialchars($item['color']); ?> |
                                    Quantity: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div class="item-price">
                                KES <?php echo number_format($item['priceAtTime'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Timeline -->
                <div class="timeline">
                    <div class="timeline-step <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'active' : 'pending'; ?>">
                        <div class="timeline-dot"><i class="bi bi-check"></i></div>
                        <div class="timeline-label">Order Placed</div>
                        <div class="timeline-status"><?php echo date('M d', strtotime($order['createdAt'])); ?></div>
                    </div>

                    <div class="timeline-step <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'active' : 'pending'; ?>">
                        <div class="timeline-dot">✓</div>
                        <div class="timeline-label">Processing</div>
                        <div class="timeline-status">In Progress</div>
                    </div>

                    <div class="timeline-step <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'active' : 'pending'; ?>">
                        <div class="timeline-dot">📦</div>
                        <div class="timeline-label">Shipped</div>
                        <div class="timeline-status">Pending</div>
                    </div>

                    <div class="timeline-step <?php echo $order['status'] === 'delivered' ? 'active' : 'pending'; ?>">
                        <div class="timeline-dot">✓</div>
                        <div class="timeline-label">Delivered</div>
                        <div class="timeline-status">Pending</div>
                    </div>
                </div>
            </div>

            <!-- Order Total -->
            <div class="col-lg-4">
                <div class="order-details">
                    <h4 style="color: #2B547E; margin-bottom: 20px;">Order Summary</h4>

                    <div class="summary-table">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($order['totalAmount'] - ($order['totalAmount'] * 0.16) - 500, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Tax (16%):</span>
                            <span>KES <?php echo number_format(($order['totalAmount'] - 500) * 0.16, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>KES 500.00</span>
                        </div>

                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>KES <?php echo number_format($order['totalAmount'], 2); ?></span>
                        </div>
                    </div>

                    <div class="payment-notice">
                        <i class="bi bi-info-circle"></i>
                        <strong>Payment Instructions:</strong><br>
                        <small>
                            <?php
                            if ($order['paymentMethod'] === 'mpesa') {
                                echo 'Please complete your M-Pesa payment. You will receive a prompt on your phone.';
                            } elseif ($order['paymentMethod'] === 'card') {
                                echo 'Please proceed with your credit/debit card payment. A secure link will be sent to your email.';
                            } elseif ($order['paymentMethod'] === 'bank_transfer') {
                                echo 'Bank transfer details will be sent to your email. Please complete the transfer within 24 hours.';
                            }
                            ?>
                        </small>
                    </div>

                    <div class="action-buttons">
                        <a href="products.php" class="btn-secondary-custom">
                            <i class="bi bi-shop"></i> Continue Shopping
                        </a>
                        <a href="home.php" class="btn-primary-custom">
                            <i class="bi bi-person"></i> My Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: #2B547E; color: white; margin-top: 50px; padding: 30px 0; text-align: center;">
        <p>&copy; 2025 FARUNOVA - Authentic Clothing Store. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>