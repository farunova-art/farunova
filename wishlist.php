<?php
session_start();
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Get all wishlist items for the user using prepared statement
$wishlist_stmt = $conn->prepare("SELECT w.id as wishlistId, p.* FROM wishlist w
                                JOIN products p ON w.productId = p.id
                                WHERE w.userId = ?
                                ORDER BY w.addedAt DESC");
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();
$wishlist_items = $wishlist_result->fetch_all(MYSQLI_ASSOC);
$wishlist_stmt->close();

// Handle remove from wishlist
$removed_message = null;
if (isset($_POST['remove_from_wishlist'])) {
    $wishlist_id = sanitizeInput($_POST['wishlist_id']);

    $remove_stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND userId = ?");
    $remove_stmt->bind_param("ii", $wishlist_id, $user_id);

    if ($remove_stmt->execute()) {
        logSecurityEvent('wishlist_item_removed', 'Item removed from wishlist', $_SESSION['username']);
        $removed_message = "Item removed from wishlist!";
        // Refresh the page to show updated wishlist
        header("Refresh:0");
    }
    $remove_stmt->close();
}

// Handle add to cart from wishlist
if (isset($_POST['add_to_cart_from_wishlist'])) {
    $product_id = sanitizeInput($_POST['product_id']);
    $size = sanitizeInput($_POST['size'] ?? 'Default');
    $color = sanitizeInput($_POST['color'] ?? 'Default');
    $quantity = 1;

    // Check if already in cart
    $check_stmt = $conn->prepare("SELECT id FROM cart_items 
                                 WHERE userId = ? AND productId = ? AND size = ? AND color = ?");
    $check_stmt->bind_param("iiss", $user_id, $product_id, $size, $color);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update quantity if exists
        $cart_item = $check_result->fetch_assoc();
        $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 
                                      WHERE id = ? AND userId = ?");
        $update_stmt->bind_param("ii", $cart_item['id'], $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Get product price
        $price_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $price_stmt->bind_param("i", $product_id);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $product = $price_result->fetch_assoc();
        $price_stmt->close();

        // Add to cart
        $insert_stmt = $conn->prepare("INSERT INTO cart_items (userId, productId, quantity, size, color, price) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("iiissd", $user_id, $product_id, $quantity, $size, $color, $product['price']);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();

    logSecurityEvent('wishlist_to_cart', 'Item added to cart from wishlist', $_SESSION['username']);
    $_SESSION['cart_message'] = "Item added to cart!";
    header("location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - FARUNOVA</title>
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

        .wishlist-container {
            padding: 20px 0;
        }

        .wishlist-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            transition: all 0.3s;
        }

        .wishlist-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #088F8F;
        }

        .wishlist-item-image {
            width: 150px;
            height: 150px;
            background-color: #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .wishlist-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wishlist-item-details {
            flex: 1;
        }

        .wishlist-item-title {
            font-size: 18px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 8px;
        }

        .wishlist-item-category {
            display: inline-block;
            background-color: #f0f0f0;
            color: #666;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .wishlist-item-price {
            font-size: 20px;
            font-weight: 700;
            color: #088F8F;
            margin: 10px 0;
        }

        .wishlist-item-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .wishlist-item-stock {
            font-size: 13px;
            margin-bottom: 15px;
            padding: 8px 12px;
            border-radius: 5px;
            display: inline-block;
        }

        .stock-available {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .stock-limited {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .stock-out {
            background-color: #FFEBEE;
            color: #C62828;
        }

        .wishlist-item-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-add-cart {
            flex: 1;
            padding: 10px 15px;
            background-color: #2B547E;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-add-cart:hover {
            background-color: #088F8F;
        }

        .btn-add-cart:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-remove {
            padding: 10px 15px;
            background-color: #f0f0f0;
            color: #f44336;
            border: 2px solid #f44336;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background-color: #f44336;
            color: white;
        }

        .btn-view-details {
            flex: 1;
            padding: 10px 15px;
            background-color: #f0f0f0;
            color: #2B547E;
            border: 2px solid #2B547E;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-view-details:hover {
            background-color: #2B547E;
            color: white;
            text-decoration: none;
        }

        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #ddd;
        }

        .empty-wishlist i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .empty-wishlist h3 {
            color: #999;
            margin-bottom: 10px;
        }

        .empty-wishlist p {
            color: #999;
            margin-bottom: 25px;
        }

        .btn-continue-shopping {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2B547E;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-continue-shopping:hover {
            background-color: #088F8F;
            text-decoration: none;
        }

        .success-message {
            background-color: #E8F5E9;
            border: 1px solid #4CAF50;
            color: #2E7D32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .wishlist-stats {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            flex: 1;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #2B547E;
        }

        .stat-label {
            font-size: 13px;
            color: #999;
            margin-top: 5px;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .relative-container {
            position: relative;
        }

        @media (max-width: 768px) {
            .wishlist-item {
                flex-direction: column;
            }

            .wishlist-item-image {
                width: 100%;
                height: 200px;
            }

            .wishlist-item-actions {
                flex-wrap: wrap;
            }

            .wishlist-stats {
                flex-direction: column;
                gap: 15px;
            }

            .stat-item {
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }

            .stat-item:last-child {
                border-bottom: none;
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
                        <li class="nav-item"><a class="nav-link active" href="wishlist.php"><i class="bi bi-heart"></i> Wishlist</a></li>
                        <li class="nav-item"><a class="nav-link" href="order_tracking.php"><i class="bi bi-truck"></i> Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="home.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="bi bi-heart-fill"></i> My Wishlist</h1>
        <p>Save your favorite items for later</p>
    </div>

    <!-- Main Content -->
    <div class="container wishlist-container">
        <?php if ($removed_message): ?>
            <div class="success-message">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($removed_message); ?>
            </div>
        <?php endif; ?>

        <?php if (count($wishlist_items) > 0): ?>
            <!-- Wishlist Stats -->
            <div class="wishlist-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($wishlist_items); ?></div>
                    <div class="stat-label">Items Saved</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        KES <?php
                            $total_value = 0;
                            foreach ($wishlist_items as $item) {
                                $total_value += $item['price'];
                            }
                            echo number_format($total_value, 2);
                            ?>
                    </div>
                    <div class="stat-label">Total Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?php
                        $in_stock_count = 0;
                        foreach ($wishlist_items as $item) {
                            if ($item['stock'] > 0) {
                                $in_stock_count++;
                            }
                        }
                        echo $in_stock_count;
                        ?>
                    </div>
                    <div class="stat-label">In Stock</div>
                </div>
            </div>

            <!-- Wishlist Items -->
            <div class="wishlist-items">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item">
                        <div class="relative-container wishlist-item-image">
                            <?php if ($item['image']): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999;">
                                    No Image
                                </div>
                            <?php endif; ?>
                            <?php if ($item['discountPercentage'] > 0): ?>
                                <span class="discount-badge">-<?php echo $item['discountPercentage']; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="wishlist-item-details">
                            <div class="wishlist-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                            <span class="wishlist-item-category"><?php echo htmlspecialchars($item['category']); ?></span>

                            <div class="wishlist-item-price">
                                <?php if ($item['discountPercentage'] > 0): ?>
                                    <span style="text-decoration: line-through; color: #999; font-size: 16px; margin-right: 10px;">
                                        KES <?php echo number_format($item['price'], 2); ?>
                                    </span>
                                    KES <?php echo number_format($item['price'] * (1 - $item['discountPercentage'] / 100), 2); ?>
                                <?php else: ?>
                                    KES <?php echo number_format($item['price'], 2); ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($item['stock'] > 0): ?>
                                <div class="wishlist-item-stock stock-available">
                                    <i class="bi bi-check-circle"></i> In Stock (<?php echo $item['stock']; ?> available)
                                </div>
                            <?php elseif ($item['stock'] > 0 && $item['stock'] < 5): ?>
                                <div class="wishlist-item-stock stock-limited">
                                    <i class="bi bi-exclamation-circle"></i> Limited Stock (<?php echo $item['stock']; ?> available)
                                </div>
                            <?php else: ?>
                                <div class="wishlist-item-stock stock-out">
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </div>
                            <?php endif; ?>

                            <p class="wishlist-item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>

                            <div class="wishlist-item-actions">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlistId']; ?>">
                                    <button type="submit" name="add_to_cart_from_wishlist" class="btn-add-cart" <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                        <i class="bi bi-cart-plus"></i> <?php echo $item['stock'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                                    </button>
                                </form>
                                <a href="product_detail.php?id=<?php echo $item['id']; ?>" class="btn-view-details">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <form method="POST" style="flex: 0;">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlistId']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="btn-remove" title="Remove from wishlist">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Continue Shopping Button -->
            <div style="text-align: center; margin-top: 40px;">
                <a href="products.php" class="btn-continue-shopping">
                    <i class="bi bi-shop"></i> Continue Shopping
                </a>
            </div>

        <?php else: ?>
            <!-- Empty Wishlist -->
            <div class="empty-wishlist">
                <i class="bi bi-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Start adding items to your wishlist to save them for later!</p>
                <a href="products.php" class="btn-continue-shopping">
                    <i class="bi bi-shop"></i> Browse Products
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
    <script src="js/wishlist.js"></script>
</body>

</html>