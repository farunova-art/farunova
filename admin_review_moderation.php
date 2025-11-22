<?php
include("connection.php");

// Check if user is admin
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header("location: login.php");
    exit();
}

// Handle review approval/rejection
if (isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    $review_id = (int)$_POST['review_id'];

    if ($action === 'approve') {
        $approve_stmt = $conn->prepare("UPDATE reviews SET isApproved = 1 WHERE id = ?");
        $approve_stmt->bind_param("i", $review_id);
        $approve_stmt->execute();
        $approve_stmt->close();
        $logger->userAction('review_approved', "Admin approved review #$review_id");
        header("Refresh:0");
        exit();
    } elseif ($action === 'reject') {
        $reject_stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $reject_stmt->bind_param("i", $review_id);
        $reject_stmt->execute();
        $reject_stmt->close();
        $logger->userAction('review_rejected', "Admin rejected review #$review_id");
        header("Refresh:0");
        exit();
    }
}

// Get pending reviews
$pending_stmt = $conn->prepare("
    SELECT r.*, p.name as product_name, u.username
    FROM reviews r
    JOIN products p ON r.productId = p.id
    JOIN users u ON r.userId = u.id
    WHERE r.isApproved = 0
    ORDER BY r.createdAt DESC
");
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_reviews = $pending_result->fetch_all(MYSQLI_ASSOC);
$pending_stmt->close();

// Get approved reviews
$approved_stmt = $conn->prepare("
    SELECT r.*, p.name as product_name, u.username, COUNT(r.id) as review_count
    FROM reviews r
    JOIN products p ON r.productId = p.id
    JOIN users u ON r.userId = u.id
    WHERE r.isApproved = 1
    GROUP BY r.productId
    ORDER BY r.createdAt DESC
    LIMIT 20
");
$approved_stmt->execute();
$approved_result = $approved_stmt->get_result();
$approved_reviews = $approved_result->fetch_all(MYSQLI_ASSOC);
$approved_stmt->close();

// Get review statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        SUM(CASE WHEN isApproved = 0 THEN 1 ELSE 0 END) as pending_reviews,
        SUM(CASE WHEN isApproved = 1 THEN 1 ELSE 0 END) as approved_reviews,
        AVG(rating) as avg_rating
    FROM reviews
");
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$logger->info('Admin review moderation accessed', ['user' => $_SESSION['username']]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Moderation - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }

        .review-card {
            background: white;
            border-left: 4px solid #088F8F;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .review-card.pending {
            border-left-color: #ffc107;
            background: #fffbf0;
        }

        .review-card.approved {
            border-left-color: #28a745;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .review-author {
            font-weight: 600;
            color: #2B547E;
        }

        .review-rating {
            color: #FFB800;
            font-size: 14px;
        }

        .review-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stat-card h5 {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.approved {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark" style="background-color: #2B547E;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> FARUNOVA
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Admin Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-light sidebar" style="min-height: calc(100vh - 56px);">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_analytics.php">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_orders.php">
                                <i class="bi bi-bag"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_products.php">
                                <i class="bi bi-box"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_customers.php">
                                <i class="bi bi-people"></i> Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_review_moderation.php">
                                <i class="bi bi-chat-left-text"></i> Reviews
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4">
                <h1 class="mb-4">Review Moderation</h1>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5>Total Reviews</h5>
                            <div class="value"><?php echo $stats['total_reviews'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pending">
                            <h5>Pending Reviews</h5>
                            <div class="value"><?php echo $stats['pending_reviews'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card approved">
                            <h5>Approved Reviews</h5>
                            <div class="value"><?php echo $stats['approved_reviews'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5>Avg Rating</h5>
                            <div class="value">
                                <?php echo round($stats['avg_rating'] ?? 0, 1); ?>/5
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Reviews Tab -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                            <i class="bi bi-clock-history"></i> Pending Review (<?php echo count($pending_reviews); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                            <i class="bi bi-check-circle"></i> Approved Reviews
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Pending Reviews -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <?php if (!empty($pending_reviews)): ?>
                            <?php foreach ($pending_reviews as $review): ?>
                                <div class="review-card pending">
                                    <div class="review-header">
                                        <div>
                                            <h5 class="mb-2"><?php echo htmlspecialchars($review['title']); ?></h5>
                                            <p class="mb-2">
                                                <span class="review-author"><?php echo htmlspecialchars($review['username']); ?></span>
                                                <span class="ms-3 text-muted small">
                                                    For: <a href="product_detail.php?id=<?php echo $review['productId']; ?>">
                                                        <?php echo htmlspecialchars($review['product_name']); ?>
                                                    </a>
                                                </span>
                                            </p>
                                            <div class="review-rating">
                                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php endfor; ?>
                                                <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2"><?php echo $review['rating']; ?>/5</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($review['createdAt'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-3"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <div class="review-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this review?');">
                                                <i class="bi bi-x"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No pending reviews for moderation.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Approved Reviews -->
                    <div class="tab-pane fade" id="approved" role="tabpanel">
                        <?php if (!empty($approved_reviews)): ?>
                            <?php foreach ($approved_reviews as $review): ?>
                                <div class="review-card approved">
                                    <div class="review-header">
                                        <div>
                                            <h5 class="mb-2"><?php echo htmlspecialchars($review['title']); ?></h5>
                                            <p class="mb-2">
                                                <span class="review-author"><?php echo htmlspecialchars($review['username']); ?></span>
                                                <span class="ms-3 text-muted small">
                                                    For: <a href="product_detail.php?id=<?php echo $review['productId']; ?>">
                                                        <?php echo htmlspecialchars($review['product_name']); ?>
                                                    </a>
                                                </span>
                                            </p>
                                            <div class="review-rating">
                                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php endfor; ?>
                                                <?php for ($i = $review['rating']; $i < 5; $i++): ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2"><?php echo $review['rating']; ?>/5</span>
                                            </div>
                                        </div>
                                        <small class="text-success">
                                            <i class="bi bi-check-circle-fill"></i> Approved
                                        </small>
                                    </div>
                                    <p class="mb-3"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <small class="text-muted">
                                        Posted: <?php echo date('M d, Y', strtotime($review['createdAt'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No approved reviews yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>