<?php
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Get user details using prepared statement
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Get cart items using prepared statement
$cart_stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.stock FROM cart_items c 
               JOIN products p ON c.productId = p.id 
               WHERE c.userId = ? 
               ORDER BY c.addedAt DESC");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
$cart_stmt->close();

// If cart is empty, redirect
if (count($cart_items) == 0) {
    header("location: cart.php");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$tax = $subtotal * 0.16;
$shipping = 500;
$total = $subtotal + $tax + $shipping;

// Handle order placement
if (isset($_POST['place_order'])) {
    // Generate order ID
    $order_id = 'ORD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);

    // Sanitize shipping details
    $shipping_address = sanitizeInput(trim($_POST['shipping_address'] ?? ''));
    $shipping_city = sanitizeInput(trim($_POST['shipping_city'] ?? ''));
    $shipping_postal = sanitizeInput(trim($_POST['shipping_postal'] ?? ''));
    $shipping_country = sanitizeInput(trim($_POST['shipping_country'] ?? ''));
    $payment_method = sanitizeInput(trim($_POST['payment_method'] ?? ''));

    // Create order using prepared statement
    $order_stmt = $conn->prepare("INSERT INTO orders (orderId, userId, totalAmount, paymentMethod, shippingAddress, shippingCity, shippingPostalCode, shippingCountry, status, paymentStatus)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");

    $order_stmt->bind_param("sdssssss", $order_id, $user_id, $total, $payment_method, $shipping_address, $shipping_city, $shipping_postal, $shipping_country);

    if ($order_stmt->execute()) {
        $order_db_id = $conn->insert_id;

        // Add order items using prepared statement
        $item_stmt = $conn->prepare("INSERT INTO order_items (orderId, productId, quantity, size, color, priceAtTime)
                                VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($cart_items as $item) {
            $product_id = $item['productId'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $size = $item['size'] ?? '';
            $color = $item['color'] ?? '';

            $item_stmt->bind_param("iiiiss", $order_db_id, $product_id, $quantity, $size, $color, $price);
            $item_stmt->execute();

            // Update product stock using prepared statement
            $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stock_stmt->bind_param("ii", $quantity, $product_id);
            $stock_stmt->execute();
            $stock_stmt->close();
        }
        $item_stmt->close();

        // Clear cart using prepared statement
        $clear_stmt = $conn->prepare("DELETE FROM cart_items WHERE userId = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();

        // Send order confirmation email
        include_once 'email_config.php';
        $order_items = [];
        foreach ($cart_items as $item) {
            $order_items[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'priceAtTime' => $item['price'],
                'size' => $item['size'] ?? 'N/A',
                'color' => $item['color'] ?? 'N/A'
            ];
        }

        $order_data = [
            'orderId' => $order_id,
            'date' => date('Y-m-d H:i:s'),
            'total' => $total,
            'baseUrl' => BASE_URL
        ];
        sendOrderConfirmationEmail($order_data, $order_items, $user['email']);

        // Send admin notification
        $admin_vars = [
            'orderId' => $order_id,
            'orderDate' => date('Y-m-d H:i:s'),
            'orderTotal' => $total,
            'customerName' => $user['username'],
            'customerEmail' => $user['email'],
            'customerPhone' => $user['phone'] ?? 'N/A',
            'shippingAddress' => $shipping_address,
            'shippingCity' => $shipping_city,
            'shippingPostal' => $shipping_postal,
            'shippingCountry' => $shipping_country,
            'items' => $order_items,
            'paymentStatus' => 'Pending',
            'adminUrl' => BASE_URL . 'admin_order_detail.php?id=' . $order_db_id
        ];
        sendAdminNotificationEmail('new_order', $admin_vars);

        logSecurityEvent('order_placed', 'Order placed successfully', $user['email']);

        // Redirect to order confirmation
        header("location: order_confirmation.php?order_id=" . urlencode($order_id));
        exit();
    } else {
        $error = "Failed to place order. Please try again.";
    }
    $order_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FARUNOVA</title>
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

        .checkout-container {
            padding: 20px 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #088F8F;
        }

        .checkout-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #088F8F;
            box-shadow: 0 0 0 0.2rem rgba(8, 143, 143, 0.25);
        }

        .order-summary {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            color: #555;
            border-bottom: 1px solid #ddd;
        }

        .summary-item.total {
            font-size: 18px;
            font-weight: 700;
            color: #2B547E;
            border: none;
            padding-top: 15px;
            margin-top: 15px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .place-order-btn {
            width: 100%;
            padding: 15px;
            background-color: #2B547E;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .place-order-btn:hover {
            background-color: #088F8F;
        }

        .place-order-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #088F8F;
            background-color: #f0f8ff;
        }

        .payment-method input[type="radio"] {
            margin-right: 10px;
        }

        .payment-method.selected {
            border-color: #088F8F;
            background-color: #e8f5f5;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .checkout-section {
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 12px;
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

    <!-- Page Header -->
    <div class="page-header">
        <h1>Checkout</h1>
        <p>Complete your order</p>
    </div>

    <!-- Checkout Content -->
    <div class="container checkout-container">
        <?php if (isset($error)): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8 col-md-12">
                <form method="POST" id="checkoutForm">
                    <!-- Shipping Information -->
                    <div class="checkout-section">
                        <h5 class="section-title">Shipping Information</h5>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+254 700 000000" required>
                        </div>

                        <div class="form-group">
                            <label>Shipping Address <span style="color: #ff4757;">*</span></label>
                            <input type="text" class="form-control" name="shipping_address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Street address" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>City <span style="color: #ff4757;">*</span></label>
                                    <input type="text" class="form-control" name="shipping_city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="City" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" class="form-control" name="shipping_postal" value="<?php echo htmlspecialchars($user['postalCode'] ?? ''); ?>" placeholder="Postal code">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Country <span style="color: #ff4757;">*</span></label>
                            <input type="text" class="form-control" name="shipping_country" value="<?php echo htmlspecialchars($user['country'] ?? 'Kenya'); ?>" placeholder="Country" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h5 class="section-title">Payment Method</h5>

                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="card" required onchange="selectPayment(this)">
                            <span style="margin-left: 10px;">
                                <strong><i class="bi bi-credit-card"></i> Credit/Debit Card</strong><br>
                                <small style="color: #999;">Visa, Mastercard, or other card</small>
                            </span>
                        </label>

                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="bank_transfer" required onchange="selectPayment(this)">
                            <span style="margin-left: 10px;">
                                <strong><i class="bi bi-bank"></i> Bank Transfer</strong><br>
                                <small style="color: #999;">Direct bank deposit</small>
                            </span>
                        </label>

                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="mpesa" required onchange="selectPayment(this)">
                            <span style="margin-left: 10px;">
                                <strong><i class="bi bi-phone"></i> M-Pesa</strong><br>
                                <small style="color: #999;">Mobile money payment</small>
                            </span>
                        </label>

                        <div style="margin-top: 20px; padding: 15px; background-color: #f0f8ff; border-radius: 5px; display: none;" id="paymentInfo">
                            <p style="color: #2B547E; font-size: 14px;">After placing your order, you will receive payment instructions for your selected payment method.</p>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="place-order-btn">
                        <i class="bi bi-shield-check"></i> Place Order
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4 col-md-12">
                <div class="checkout-section order-summary">
                    <h5 class="section-title">Order Summary</h5>

                    <div style="margin-bottom: 20px; max-height: 300px; overflow-y: auto;">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div>
                                    <div style="font-weight: 600; color: #2B547E;"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <small style="color: #999;">
                                        <?php echo htmlspecialchars($item['size']); ?> •
                                        <?php echo htmlspecialchars($item['color']); ?> •
                                        Qty: <?php echo $item['quantity']; ?>
                                    </small>
                                </div>
                                <div style="font-weight: 600; color: #088F8F;">
                                    KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 2px solid #ddd; padding-top: 15px;">
                        <div class="summary-item">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="summary-item">
                            <span>Tax (16%):</span>
                            <span>KES <?php echo number_format($tax, 2); ?></span>
                        </div>

                        <div class="summary-item">
                            <span>Shipping:</span>
                            <span>KES <?php echo number_format($shipping, 2); ?></span>
                        </div>

                        <div class="summary-item total">
                            <span>Total Amount:</span>
                            <span>KES <?php echo number_format($total, 2); ?></span>
                        </div>
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
    <script>
        function selectPayment(radio) {
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            radio.closest('.payment-method').classList.add('selected');
            document.getElementById('paymentInfo').style.display = 'block';
        }

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
            }
        });

        // Select first payment method by default
        document.querySelector('input[name="payment_method"]').click();
    </script>
</body>

</html>