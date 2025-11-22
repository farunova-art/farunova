<?php
include("connection.php");

if (!isset($_SESSION['username'])) {
    header("location:login.php");
    exit();
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Get user profile info
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$user_stmt) {
    error_log("Error preparing user statement: " . $conn->error);
    die("Database error. Please contact administrator.");
}
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Get order statistics
$order_stats_stmt = $conn->prepare("SELECT 
    COUNT(*) as total_orders,
    SUM(totalAmount) as total_spent,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'pending' OR status = 'confirmed' THEN 1 END) as pending_orders
FROM orders WHERE userId = ?");
if (!$order_stats_stmt) {
    error_log("Error preparing stats statement: " . $conn->error);
    die("Database error. Please contact administrator.");
}
$order_stats_stmt->bind_param("i", $user_id);
$order_stats_stmt->execute();
$stats_result = $order_stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$order_stats_stmt->close();

// Get recent orders
$recent_orders_stmt = $conn->prepare("SELECT * FROM orders WHERE userId = ? ORDER BY createdAt DESC LIMIT 5");
if (!$recent_orders_stmt) {
    error_log("Error preparing recent orders statement: " . $conn->error);
    $recent_orders = [];
} else {
    $recent_orders_stmt->bind_param("i", $user_id);
    $recent_orders_stmt->execute();
    $recent_orders = $recent_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recent_orders_stmt->close();
}

// Get wishlist count
$wishlist_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE userId = ?");
if (!$wishlist_stmt) {
    error_log("Error preparing wishlist statement: " . $conn->error);
    $wishlist_count = 0;
} else {
    $wishlist_stmt->bind_param("i", $user_id);
    $wishlist_stmt->execute();
    $wishlist_result = $wishlist_stmt->get_result()->fetch_assoc();
    $wishlist_count = $wishlist_result['count'] ?? 0;
    $wishlist_stmt->close();
}

// Get favorite categories
$favorite_cat_stmt = $conn->prepare("SELECT p.category, COUNT(*) as count
FROM order_items oi
JOIN orders o ON oi.orderId = o.id
JOIN products p ON oi.productId = p.id
WHERE o.userId = ?
GROUP BY p.category
ORDER BY count DESC
LIMIT 3");
if (!$favorite_cat_stmt) {
    error_log("Error preparing favorite categories statement: " . $conn->error);
    $favorite_cats = [];
} else {
    $favorite_cat_stmt->bind_param("i", $user_id);
    $favorite_cat_stmt->execute();
    $favorite_cats = $favorite_cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $favorite_cat_stmt->close();
}

// Get status badge color
function getStatusBadgeColor($status)
{
    switch ($status) {
        case 'delivered':
            return 'success';
        case 'shipped':
            return 'info';
        case 'confirmed':
            return 'warning';
        case 'pending':
            return 'secondary';
        case 'cancelled':
            return 'danger';
        default:
            return 'primary';
    }
}

// formatCurrency() is defined in lib/Helpers.php - removed duplicate here
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FARUNOVA Clothing Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 40px;
        }

        .dashboard-greeting {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .dashboard-subtext {
            font-size: 14px;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #088F8F;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 32px;
            color: #2B547E;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #088F8F;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.3s;
        }

        .order-item:hover {
            background-color: #f8f9fa;
            padding-left: 10px;
            padding-right: 10px;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info {
            flex: 1;
        }

        .order-number {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 5px;
        }

        .order-date {
            font-size: 13px;
            color: #999;
        }

        .order-amount {
            font-weight: 600;
            color: #088F8F;
            font-size: 16px;
            margin-right: 15px;
        }

        .quick-action {
            display: inline-block;
            padding: 8px 16px;
            background-color: #2B547E;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .quick-action:hover {
            background-color: #088F8F;
            text-decoration: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .category-badge {
            display: inline-block;
            background-color: #e8f4f8;
            color: #088F8F;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .profile-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .profile-info:last-child {
            border-bottom: none;
            margin-bottom: 20px;
            padding-bottom: 0;
        }

        .profile-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .profile-value {
            font-size: 16px;
            color: #2B547E;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-primary-custom {
            flex: 1;
            padding: 10px;
            background-color: #2B547E;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-primary-custom:hover {
            background-color: #088F8F;
            text-decoration: none;
            color: white;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 20px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-amount {
                margin-right: 0;
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
                        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="dashboard-greeting">
                <i class="bi bi-hand-thumbs-up"></i> Welcome back, <?php echo htmlspecialchars($username); ?>!
            </div>
            <div class="dashboard-subtext">
                Here's your shopping activity and account information
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics Row -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                <div class="stat-value"><?php echo (int)($stats['total_orders'] ?? 0); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-value"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value"><?php echo (int)($stats['completed_orders'] ?? 0); ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-heart"></i></div>
                <div class="stat-value"><?php echo $wishlist_count; ?></div>
                <div class="stat-label">Wishlist Items</div>
            </div>
        </div>

        <div class="row mt-5">
            <!-- Profile Section -->
            <div class="col-lg-4 col-md-6">
                <div class="profile-section">
                    <h4 style="font-weight: 600; color: #2B547E; margin-bottom: 20px;">
                        <i class="bi bi-person-circle"></i> Account Information
                    </h4>

                    <div class="profile-info">
                        <div class="profile-label">Username</div>
                        <div class="profile-value"><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></div>
                    </div>

                    <div class="profile-info">
                        <div class="profile-label">Email</div>
                        <div class="profile-value"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></div>
                    </div>

                    <div class="profile-info">
                        <div class="profile-label">Member Since</div>
                        <div class="profile-value">
                            <?php
                            $date = new DateTime($user_data['createdAt'] ?? 'now');
                            echo $date->format('M d, Y');
                            ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="edit.php?id=<?php echo $user_id; ?>" class="btn-primary-custom">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </a>
                        <a href="logout.php" class="btn-primary-custom" style="background-color: #dc3545;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Favorite Categories -->
                <?php if (count($favorite_cats) > 0): ?>
                    <div class="profile-section">
                        <h4 style="font-weight: 600; color: #2B547E; margin-bottom: 15px;">
                            <i class="bi bi-star"></i> Favorite Categories
                        </h4>
                        <div>
                            <?php foreach ($favorite_cats as $cat): ?>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['count']; ?>)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Links -->
                <div class="profile-section">
                    <h4 style="font-weight: 600; color: #2B547E; margin-bottom: 15px;">
                        <i class="bi bi-lightning"></i> Quick Links
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="wishlist.php" class="quick-action" style="display: block; text-align: center;">
                            <i class="bi bi-heart"></i> My Wishlist
                        </a>
                        <a href="order_tracking.php" class="quick-action" style="display: block; text-align: center;">
                            <i class="bi bi-truck"></i> Track Orders
                        </a>
                        <a href="products.php" class="quick-action" style="display: block; text-align: center;">
                            <i class="bi bi-shop"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="col-lg-8 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 style="margin: 0; font-weight: 600; color: #2B547E;">
                            <i class="bi bi-clock-history"></i> Recent Orders
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_orders) > 0): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <div class="order-number">Order #<?php echo htmlspecialchars($order['id']); ?></div>
                                        <div class="order-date">
                                            <i class="bi bi-calendar"></i>
                                            <?php
                                            $order_date = new DateTime($order['createdAt']);
                                            echo $order_date->format('M d, Y \a\t h:i A');
                                            ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge-status bg-<?php echo getStatusBadgeColor($order['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="order-amount"><?php echo formatCurrency($order['totalAmount']); ?></div>
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="quick-action">
                                        View Details
                                    </a>
                                </div>
                            <?php endforeach; ?>

                            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                                <a href="order_tracking.php" class="btn-primary-custom" style="max-width: 200px; margin: 0 auto;">
                                    <i class="bi bi-list"></i> View All Orders
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h5>No Orders Yet</h5>
                                <p>You haven't placed any orders yet. Start shopping now!</p>
                                <a href="products.php" class="quick-action" style="display: inline-block; margin-top: 10px;">
                                    <i class="bi bi-shop"></i> Browse Products
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="row mt-5 mb-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 style="margin: 0; font-weight: 600; color: #2B547E;">
                            <i class="bi bi-info-circle"></i> Shopping Tips
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div style="text-align: center;">
                                    <i class="bi bi-percent" style="font-size: 32px; color: #088F8F; margin-bottom: 10px;"></i>
                                    <h6>Save with Discounts</h6>
                                    <p style="font-size: 14px; color: #666;">Browse our collection of discounted items and save on your favorite clothes.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div style="text-align: center;">
                                    <i class="bi bi-bookmark" style="font-size: 32px; color: #088F8F; margin-bottom: 10px;"></i>
                                    <h6>Create a Wishlist</h6>
                                    <p style="font-size: 14px; color: #666;">Save items to your wishlist and get notified when they go on sale.</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div style="text-align: center;">
                                    <i class="bi bi-star" style="font-size: 32px; color: #088F8F; margin-bottom: 10px;"></i>
                                    <h6>Leave Reviews</h6>
                                    <p style="font-size: 14px; color: #666;">Share your experience and help other customers by rating products.</p>
                                </div>
                            </div>
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
</body>

</html>