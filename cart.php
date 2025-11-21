<?php
session_start();
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Handle remove item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ? AND userId = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("location: cart.php");
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND userId = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("location: cart.php");
    exit();
}

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.stock, p.image FROM cart_items c 
                        JOIN products p ON c.productId = p.id 
                        WHERE c.userId = ? 
                        ORDER BY c.addedAt DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
}

$tax = $subtotal * 0.16; // 16% VAT
$shipping = $subtotal > 0 ? 500 : 0; // KES 500 shipping
$total = $subtotal + $tax + $shipping;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - FARUNOVA</title>
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

        .cart-container {
            padding: 20px 0;
        }

        .cart-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            background-color: #f0f0f0;
            border-radius: 8px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-size: 18px;
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 5px;
        }

        .cart-item-specs {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }

        .cart-item-specs span {
            margin-right: 20px;
        }

        .cart-item-price {
            font-size: 16px;
            font-weight: 600;
            color: #088F8F;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
        }

        .remove-btn {
            background-color: #ff4757;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .remove-btn:hover {
            background-color: #e84245;
        }

        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            position: sticky;
            top: 20px;
        }

        .cart-summary-title {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
            color: #555;
        }

        .summary-row.total {
            border-top: 2px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
        }

        .checkout-btn {
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

        .checkout-btn:hover {
            background-color: #088F8F;
        }

        .continue-shopping-btn {
            width: 100%;
            padding: 12px;
            background-color: white;
            color: #2B547E;
            border: 2px solid #2B547E;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .continue-shopping-btn:hover {
            background-color: #2B547E;
            color: white;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-cart-icon {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
            }

            .cart-item-image {
                width: 100%;
            }

            .cart-summary {
                margin-top: 30px;
                position: relative;
                top: auto;
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
                        <li class="nav-item"><a class="nav-link active" href="cart.php"><i class="bi bi-cart"></i> Cart</a></li>
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
        <h1>Shopping Cart</h1>
        <p><?php echo $total_items; ?> Item(s)</p>
    </div>

    <!-- Cart Content -->
    <div class="container cart-container">
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="success-message">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['cart_message']; ?>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <h4 style="margin-bottom: 20px;">Cart Items</h4>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div style="color: #999;">No Image</div>
                                <?php endif; ?>
                            </div>

                            <div class="cart-item-details">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="cart-item-specs">
                                    <span><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></span>
                                    <span><strong>Color:</strong> <?php echo htmlspecialchars($item['color']); ?></span>
                                </div>
                                <div class="cart-item-price">KES <?php echo number_format($item['price'], 2); ?> each</div>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" onclick="decreaseQuantity(this, <?php echo $item['stock']; ?>)">-</button>
                                        <input type="number" class="quantity-input" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                                        <button type="button" class="quantity-btn" onclick="increaseQuantity(this, <?php echo $item['stock']; ?>)">+</button>
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary" style="margin-left: 10px;">Update</button>
                                    </div>
                                </form>

                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="remove-btn">
                                    <i class="bi bi-trash"></i> Remove
                                </a>
                            </div>

                            <div style="text-align: right; font-size: 18px; font-weight: 700; color: #088F8F; min-width: 100px;">
                                KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4 col-md-12">
                    <div class="cart-summary">
                        <div class="cart-summary-title">Order Summary</div>

                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($subtotal, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Tax (16%):</span>
                            <span>KES <?php echo number_format($tax, 2); ?></span>
                        </div>

                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>KES <?php echo number_format($shipping, 2); ?></span>
                        </div>

                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>KES <?php echo number_format($total, 2); ?></span>
                        </div>

                        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                            <i class="bi bi-credit-card"></i> Proceed to Checkout
                        </button>

                        <button class="continue-shopping-btn" onclick="window.location.href='products.php'">
                            <i class="bi bi-arrow-left"></i> Continue Shopping
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="bi bi-cart-x"></i>
                </div>
                <h4>Your cart is empty</h4>
                <p>Start shopping to add items to your cart</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="bi bi-shop"></i> Shop Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer style="background-color: #2B547E; color: white; margin-top: 50px; padding: 30px 0; text-align: center;">
        <p>&copy; 2025 FARUNOVA - Authentic Clothing Store. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/cart.js"></script>
    <script>
        function increaseQuantity(button, maxStock) {
            const input = button.parentElement.querySelector('input[name="quantity"]');
            if (parseInt(input.value) < maxStock) {
                input.value = parseInt(input.value) + 1;
            }
        }

        function decreaseQuantity(button, maxStock) {
            const input = button.parentElement.querySelector('input[name="quantity"]');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
    </script>
</body>

</html>