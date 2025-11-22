<?php
include("connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$error = null;
$success = null;

// Verify product exists
$product_stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();
$product_stmt->close();

if (!$product) {
    $error = "Product not found.";
} else {
    // Handle review submission
    if (isset($_POST['submit_review'])) {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = "Security token validation failed. Please try again.";
        } else {
            $rating = (int)($_POST['rating'] ?? 0);
            $title = sanitizeInput(trim($_POST['title'] ?? ''));
            $content = sanitizeInput(trim($_POST['content'] ?? ''));

            // Validate inputs
            if ($rating < 1 || $rating > 5) {
                $error = "Rating must be between 1 and 5 stars.";
            } elseif (strlen($title) < 5) {
                $error = "Review title must be at least 5 characters.";
            } elseif (strlen($content) < 20) {
                $error = "Review content must be at least 20 characters.";
            } else {
                // Check if user has already reviewed this product
                $existing_review = $conn->prepare("SELECT id FROM reviews WHERE userId = ? AND productId = ?");
                $existing_review->bind_param("ii", $user_id, $product_id);
                $existing_review->execute();

                if ($existing_review->get_result()->num_rows > 0) {
                    $error = "You have already reviewed this product.";
                } else {
                    // Check if user has purchased this product
                    $purchase_check = $conn->prepare("SELECT oi.id FROM order_items oi
                                                     JOIN orders o ON oi.orderId = o.id
                                                     WHERE o.userId = ? AND oi.productId = ? AND o.status IN ('shipped', 'delivered')");
                    $purchase_check->bind_param("ii", $user_id, $product_id);
                    $purchase_check->execute();

                    if ($purchase_check->get_result()->num_rows === 0) {
                        $error = "You can only review products you have purchased.";
                    } else {
                        // Insert review
                        $insert_review = $conn->prepare("INSERT INTO reviews (userId, productId, rating, title, content, status) 
                                                        VALUES (?, ?, ?, ?, ?, 'pending')");
                        $insert_review->bind_param("iiiis", $user_id, $product_id, $rating, $title, $content);

                        if ($insert_review->execute()) {
                            logSecurityEvent('review_submitted', 'Product review submitted', $_SESSION['username']);
                            $success = "Review submitted successfully! It will appear after admin approval.";
                        } else {
                            $error = "Failed to submit review. Please try again.";
                        }
                        $insert_review->close();
                    }
                    $purchase_check->close();
                }
                $existing_review->close();
            }
        }
    }

    // Get approved reviews for the product
    $reviews_stmt = $conn->prepare("SELECT r.*, u.username, 
                                   (SELECT COUNT(*) FROM reviews WHERE productId = ? AND status = 'approved') as totalReviews,
                                   (SELECT AVG(rating) FROM reviews WHERE productId = ? AND status = 'approved') as avgRating
                                   FROM reviews r
                                   JOIN users u ON r.userId = u.id
                                   WHERE r.productId = ? AND r.status = 'approved'
                                   ORDER BY r.createdAt DESC
                                   LIMIT 10");
    $reviews_stmt->bind_param("iii", $product_id, $product_id, $product_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
    $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
    $reviews_stmt->close();

    // Calculate average rating and count
    $avg_rating = $reviews[0]['avgRating'] ?? 0;
    $total_reviews = $reviews[0]['totalReviews'] ?? 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?php echo htmlspecialchars($product['name'] ?? 'FARUNOVA'); ?></title>
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

        .review-container {
            padding: 20px 0;
        }

        .rating-display {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .big-stars {
            font-size: 48px;
            color: #FFB800;
            margin: 20px 0;
        }

        .rating-text {
            font-size: 24px;
            font-weight: 700;
            color: #2B547E;
        }

        .review-count {
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }

        .rating-breakdown {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .star-label {
            width: 40px;
            font-size: 13px;
            text-align: right;
        }

        .rating-bar {
            flex: 1;
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background-color: #FFB800;
        }

        .rating-percent {
            width: 40px;
            text-align: right;
            font-size: 13px;
            color: #999;
        }

        .review-form {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 8px;
            display: block;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #088F8F;
            outline: none;
        }

        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 32px;
        }

        .star {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }

        .star:hover,
        .star.active {
            color: #FFB800;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group button {
            padding: 12px 30px;
            background-color: #2B547E;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-group button:hover {
            background-color: #088F8F;
        }

        .reviews-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .reviews-title {
            font-size: 20px;
            font-weight: 700;
            color: #2B547E;
            padding: 25px;
            border-bottom: 1px solid #ddd;
        }

        .review-item {
            padding: 25px;
            border-bottom: 1px solid #eee;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .review-stars {
            font-size: 14px;
            color: #FFB800;
        }

        .review-author {
            font-weight: 600;
            color: #2B547E;
        }

        .review-date {
            color: #999;
            font-size: 13px;
        }

        .review-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .review-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .review-helpful {
            display: flex;
            gap: 15px;
            font-size: 13px;
        }

        .helpful-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            transition: color 0.2s;
            padding: 0;
        }

        .helpful-btn:hover {
            color: #2B547E;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #f0f0f0;
            color: #2B547E;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-button:hover {
            background-color: #2B547E;
            color: white;
        }

        @media (max-width: 768px) {
            .review-header {
                flex-direction: column;
            }

            .rating-display {
                padding: 20px;
            }

            .review-form {
                padding: 20px;
            }

            .review-item {
                padding: 15px;
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
        <h1><?php echo htmlspecialchars($product['name'] ?? 'Reviews'); ?></h1>
        <p>Customer Reviews & Ratings</p>
    </div>

    <!-- Main Content -->
    <div class="container review-container">
        <a href="product_detail.php?id=<?php echo $product_id; ?>" class="back-button">
            <i class="bi bi-arrow-left"></i> Back to Product
        </a>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!$error || isset($_POST['submit_review'])): ?>
            <!-- Rating Display -->
            <div class="rating-display">
                <div class="rating-text"><?php echo number_format($avg_rating, 1); ?> / 5.0</div>
                <div class="big-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?php echo $i <= round($avg_rating) ? '-fill' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="review-count">Based on <?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?></div>

                <?php if ($total_reviews > 0): ?>
                    <div class="rating-breakdown">
                        <?php
                        // Calculate rating distribution
                        $rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                        foreach ($reviews as $review) {
                            if (isset($rating_counts[$review['rating']])) {
                                $rating_counts[$review['rating']]++;
                            }
                        }

                        for ($stars = 5; $stars >= 1; $stars--):
                            $count = $rating_counts[$stars] ?? 0;
                            $percent = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                        ?>
                            <div class="rating-row">
                                <div class="star-label"><?php echo $stars; ?> <i class="bi bi-star-fill"></i></div>
                                <div class="rating-bar">
                                    <div class="rating-fill" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <div class="rating-percent"><?php echo round($percent); ?>%</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Review Form -->
            <div class="review-form">
                <div class="form-title">Write a Review</div>
                <form method="POST">
                    <?php echo csrfTokenField(); ?>

                    <div class="form-group">
                        <label>Rating <span style="color: #ff4757;">*</span></label>
                        <div class="star-rating" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star" data-value="<?php echo $i; ?>"><i class="bi bi-star-fill"></i></span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0" required>
                    </div>

                    <div class="form-group">
                        <label>Review Title <span style="color: #ff4757;">*</span></label>
                        <input type="text" name="title" placeholder="Brief summary of your review" required minlength="5">
                    </div>

                    <div class="form-group">
                        <label>Your Review <span style="color: #ff4757;">*</span></label>
                        <textarea name="content" placeholder="Share your experience with this product..." required minlength="20"></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="submit_review"><i class="bi bi-send"></i> Submit Review</button>
                    </div>
                </form>
            </div>

            <!-- Reviews List -->
            <?php if (count($reviews) > 0): ?>
                <div class="reviews-list">
                    <div class="reviews-title">Customer Reviews (<?php echo count($reviews); ?>)</div>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <div class="review-author"><?php echo htmlspecialchars($review['username']); ?></div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['createdAt'])); ?></div>
                                </div>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <span style="margin-left: 5px; color: #333;"><?php echo $review['rating']; ?>.0</span>
                                </div>
                            </div>
                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                            <div class="review-content"><?php echo htmlspecialchars($review['content']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <div class="no-reviews">
                        <i class="bi bi-chat-left" style="font-size: 48px; color: #ddd;"></i>
                        <p style="margin-top: 15px;">No reviews yet. Be the first to review this product!</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer style="background-color: #2B547E; color: white; margin-top: 50px; padding: 30px 0; text-align: center;">
        <p>&copy; 2025 FARUNOVA - Authentic Clothing Store. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/common.js"></script>
    <script src="js/reviews.js"></script>
    <script>
        document.getElementById('starRating').addEventListener('click', function(e) {
            if (e.target.classList.contains('bi')) {
                const star = e.target.parentElement;
                const value = star.getAttribute('data-value');
                document.getElementById('ratingInput').value = value;

                document.querySelectorAll('#starRating .star').forEach(s => {
                    s.classList.remove('active');
                    if (s.getAttribute('data-value') <= value) {
                        s.classList.add('active');
                    }
                });
            }
        });

        // Visual hover effect
        document.getElementById('starRating').addEventListener('mouseover', function(e) {
            if (e.target.classList.contains('bi')) {
                const star = e.target.parentElement;
                const value = star.getAttribute('data-value');
                document.querySelectorAll('#starRating .star').forEach(s => {
                    s.style.color = s.getAttribute('data-value') <= value ? '#FFB800' : '#ddd';
                });
            }
        });

        document.getElementById('starRating').addEventListener('mouseout', function() {
            document.querySelectorAll('#starRating .star').forEach(s => {
                s.style.color = s.classList.contains('active') ? '#FFB800' : '#ddd';
            });
        });
    </script>
</body>

</html>