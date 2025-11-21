<?php
session_start();
include("connection.php");

if (!isset($_GET['id'])) {
    header("location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND active = TRUE");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("location: products.php");
    exit();
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['username'])) {
        header("location: login.php");
        exit();
    }

    $user_id = $_SESSION['id'];
    $quantity = (int)$_POST['quantity'];
    $size = $conn->real_escape_string($_POST['size']);
    $color = $conn->real_escape_string($_POST['color']);
    $price = $product['discountPrice'] ?? $product['price'];

    // Check if item already in cart using prepared statement
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE userId = ? AND productId = ? AND size = ? AND color = ?");
    $stmt->bind_param("iiss", $user_id, $product_id, $size, $color);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update quantity
        $cart_item = $check_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new cart item using prepared statement
        $insert_stmt = $conn->prepare("INSERT INTO cart_items (userId, productId, quantity, size, color, price) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("iiissd", $user_id, $product_id, $quantity, $size, $color, $price);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $stmt->close();

    $_SESSION['cart_message'] = 'Product added to cart successfully!';
    header("location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-detail-container {
            padding: 40px 0;
        }

        .product-image-section {
            position: relative;
        }

        .product-main-image {
            width: 100%;
            height: 400px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            overflow: hidden;
        }

        .product-main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #ff4757;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
        }

        .product-info {
            padding: 20px 0;
        }

        .product-title {
            font-size: 32px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 15px;
        }

        .product-category {
            display: inline-block;
            background-color: #088F8F;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .product-price-section {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }

        .product-price {
            font-size: 36px;
            font-weight: bold;
            color: #088F8F;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 24px;
            margin-left: 10px;
        }

        .discount-badge {
            background-color: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }

        .stock-status {
            font-size: 16px;
            margin: 15px 0;
            font-weight: 600;
        }

        .stock-available {
            color: #28a745;
        }

        .stock-low {
            color: #ffc107;
        }

        .stock-unavailable {
            color: #ff4757;
        }

        .product-description {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin: 30px 0;
        }

        .form-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .form-group-custom {
            margin-bottom: 20px;
        }

        .form-group-custom label {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 10px;
            display: block;
        }

        .size-options,
        .color-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .size-btn,
        .color-btn {
            padding: 10px 20px;
            border: 2px solid #ddd;
            background-color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .size-btn:hover,
        .color-btn:hover {
            border-color: #088F8F;
            color: #088F8F;
        }

        .size-btn.selected,
        .color-btn.selected {
            background-color: #088F8F;
            color: white;
            border-color: #088F8F;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 15px;
            background-color: #2B547E;
            color: white;
            border: none;
            font-size: 18px;
            font-weight: 700;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .add-to-cart-btn:hover {
            background-color: #088F8F;
        }

        .add-to-cart-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .breadcrumb-custom {
            margin-bottom: 30px;
        }

        .breadcrumb-custom a {
            color: #088F8F;
            text-decoration: none;
        }

        .breadcrumb-custom a:hover {
            text-decoration: underline;
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

    <!-- Breadcrumb -->
    <div class="container product-detail-container">
        <div class="breadcrumb-custom">
            <a href="products.php">Shop</a> /
            <a href="products.php?category=<?php echo urlencode($product['category']); ?>"><?php echo htmlspecialchars($product['category']); ?></a> /
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="row">
            <!-- Product Image -->
            <div class="col-lg-6 col-md-12 product-image-section">
                <div class="product-main-image">
                    <?php if ($product['image']): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div style="color: #999; font-size: 18px;">No Image Available</div>
                    <?php endif; ?>
                </div>
                <?php if ($product['discountPercentage'] > 0): ?>
                    <span class="product-badge">Sale -<?php echo $product['discountPercentage']; ?>%</span>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6 col-md-12 product-info">
                <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="product-price-section">
                    <div class="product-price">
                        KES <?php echo number_format($product['discountPrice'] ?? $product['price'], 2); ?>
                        <?php if ($product['discountPrice']): ?>
                            <span class="original-price">KES <?php echo number_format($product['price'], 2); ?></span>
                            <span class="discount-badge">Save <?php echo $product['discountPercentage']; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stock Status -->
                <div class="stock-status">
                    <?php if ($product['stock'] > 10): ?>
                        <span class="stock-available"><i class="bi bi-check-circle"></i> In Stock</span>
                    <?php elseif ($product['stock'] > 0): ?>
                        <span class="stock-low"><i class="bi bi-exclamation-circle"></i> Low Stock (<?php echo $product['stock']; ?> remaining)</span>
                    <?php else: ?>
                        <span class="stock-unavailable"><i class="bi bi-x-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Product Description -->
                <div class="product-description">
                    <h5 style="color: #2B547E; font-weight: 600;">Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <!-- Add to Cart Form -->
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" id="addToCart">
                        <div class="form-section">
                            <!-- Size Selection -->
                            <div class="form-group-custom">
                                <label>Select Size <span style="color: #ff4757;">*</span></label>
                                <div class="size-options" id="sizeOptions">
                                    <?php
                                    $sizes = explode(',', $product['sizes']);
                                    foreach ($sizes as $index => $size):
                                        $size = trim($size);
                                    ?>
                                        <button type="button" class="size-btn" onclick="selectSize(this, '<?php echo htmlspecialchars($size); ?>')">
                                            <?php echo htmlspecialchars($size); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="size" id="selectedSize" required>
                            </div>

                            <!-- Color Selection -->
                            <div class="form-group-custom">
                                <label>Select Color <span style="color: #ff4757;">*</span></label>
                                <div class="color-options" id="colorOptions">
                                    <?php
                                    $colors = explode(',', $product['colors']);
                                    foreach ($colors as $color):
                                        $color = trim($color);
                                    ?>
                                        <button type="button" class="color-btn" onclick="selectColor(this, '<?php echo htmlspecialchars($color); ?>')">
                                            <?php echo htmlspecialchars($color); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="color" id="selectedColor" required>
                            </div>

                            <!-- Quantity Selection -->
                            <div class="form-group-custom">
                                <label>Quantity</label>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-btn" onclick="decreaseQuantity()">-</button>
                                    <input type="number" class="quantity-input" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly>
                                    <button type="button" class="quantity-btn" onclick="increaseQuantity()">+</button>
                                    <span style="color: #999; font-size: 14px;">Max: <?php echo $product['stock']; ?></span>
                                </div>
                            </div>

                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                <i class="bi bi-cart"></i> Add to Cart
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-section">
                        <div class="alert alert-danger">This product is currently out of stock</div>
                    </div>
                <?php endif; ?>

                <!-- Product Info -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                    <p><strong>Status:</strong> <?php echo $product['active'] ? 'Available' : 'Unavailable'; ?></p>
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
        function selectSize(button, size) {
            document.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('selected'));
            button.classList.add('selected');
            document.getElementById('selectedSize').value = size;
        }

        function selectColor(button, color) {
            document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('selected'));
            button.classList.add('selected');
            document.getElementById('selectedColor').value = color;
        }

        function increaseQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        document.getElementById('addToCart').addEventListener('submit', function(e) {
            const size = document.getElementById('selectedSize').value;
            const color = document.getElementById('selectedColor').value;

            if (!size || !color) {
                e.preventDefault();
                alert('Please select both size and color');
            }
        });
    </script>
</body>

</html>