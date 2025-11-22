<?php
include("../connection.php");

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in for POST requests
$action = sanitizeInput($_GET['action'] ?? '');

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get':
            // Get reviews for a product
            $product_id = (int)($_GET['productId'] ?? 0);
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            $stmt = $conn->prepare("SELECT r.*, u.username, p.name 
                                   FROM reviews r
                                   JOIN users u ON r.userId = u.id
                                   JOIN products p ON r.productId = p.id
                                   WHERE r.productId = ? AND r.status = 'approved'
                                   ORDER BY r.createdAt DESC
                                   LIMIT ? OFFSET ?");
            $stmt->bind_param("iii", $product_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $reviews = $result->fetch_all(MYSQLI_ASSOC);

            // Get average rating
            $avg_stmt = $conn->prepare("SELECT AVG(rating) as avgRating, COUNT(*) as totalReviews
                                       FROM reviews WHERE productId = ? AND status = 'approved'");
            $avg_stmt->bind_param("i", $product_id);
            $avg_stmt->execute();
            $avg_result = $avg_stmt->get_result();
            $rating_data = $avg_result->fetch_assoc();

            $response = [
                'success' => true,
                'reviews' => $reviews,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $rating_data['totalReviews'] ?? 0
                ],
                'rating' => [
                    'average' => round($rating_data['avgRating'] ?? 0, 1),
                    'total' => (int)($rating_data['totalReviews'] ?? 0)
                ]
            ];

            $stmt->close();
            $avg_stmt->close();
            break;

        case 'submit':
            // Submit a review
            if (!isset($_SESSION['id'])) {
                http_response_code(401);
                $response = ['success' => false, 'message' => 'Unauthorized. Please login first.'];
                break;
            }

            $user_id = $_SESSION['id'];
            $product_id = (int)($_POST['productId'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $title = sanitizeInput(trim($_POST['title'] ?? ''));
            $content = sanitizeInput(trim($_POST['content'] ?? ''));

            // Validation
            if (!$product_id || $rating < 1 || $rating > 5 || strlen($title) < 5 || strlen($content) < 20) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid input. Please check all fields.'];
                break;
            }

            // Check if already reviewed
            $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE userId = ? AND productId = ?");
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows > 0) {
                http_response_code(409);
                $response = ['success' => false, 'message' => 'You have already reviewed this product.'];
                $check_stmt->close();
                break;
            }
            $check_stmt->close();

            // Check if user purchased the product
            $purchase_stmt = $conn->prepare("SELECT oi.id FROM order_items oi
                                           JOIN orders o ON oi.orderId = o.id
                                           WHERE o.userId = ? AND oi.productId = ? AND o.status IN ('shipped', 'delivered')");
            $purchase_stmt->bind_param("ii", $user_id, $product_id);
            $purchase_stmt->execute();

            if ($purchase_stmt->get_result()->num_rows === 0) {
                http_response_code(403);
                $response = ['success' => false, 'message' => 'You can only review products you have purchased.'];
                $purchase_stmt->close();
                break;
            }
            $purchase_stmt->close();

            // Insert review
            $insert_stmt = $conn->prepare("INSERT INTO reviews (userId, productId, rating, title, content, status) 
                                          VALUES (?, ?, ?, ?, ?, 'pending')");
            $insert_stmt->bind_param("iiiis", $user_id, $product_id, $rating, $title, $content);

            if ($insert_stmt->execute()) {
                logSecurityEvent('review_api_submit', 'Review submitted via API', $_SESSION['username']);
                http_response_code(201);
                $response = [
                    'success' => true,
                    'message' => 'Review submitted successfully! It will appear after admin approval.',
                    'reviewId' => $conn->insert_id
                ];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to submit review.'];
            }
            $insert_stmt->close();
            break;

        case 'helpful':
            // Mark review as helpful
            if (!isset($_SESSION['id'])) {
                http_response_code(401);
                $response = ['success' => false, 'message' => 'Unauthorized.'];
                break;
            }

            $review_id = (int)($_POST['reviewId'] ?? 0);
            $helpful = isset($_POST['helpful']) ? (bool)$_POST['helpful'] : true;

            if (!$review_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Review ID is required.'];
                break;
            }

            $field = $helpful ? 'helpful' : 'notHelpful';
            $update_stmt = $conn->prepare("UPDATE reviews SET $field = $field + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $review_id);

            if ($update_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Thank you for your feedback.'];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to update.'];
            }
            $update_stmt->close();
            break;

        case 'rating':
            // Get average rating for a product
            $product_id = (int)($_GET['productId'] ?? 0);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            $stmt = $conn->prepare("SELECT AVG(rating) as average, COUNT(*) as total,
                                   SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                                   SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                                   SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                                   SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                                   SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                                   FROM reviews WHERE productId = ? AND status = 'approved'");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $rating_data = $result->fetch_assoc();

            $response = [
                'success' => true,
                'average' => round($rating_data['average'] ?? 0, 1),
                'total' => (int)($rating_data['total'] ?? 0),
                'distribution' => [
                    5 => (int)($rating_data['five_star'] ?? 0),
                    4 => (int)($rating_data['four_star'] ?? 0),
                    3 => (int)($rating_data['three_star'] ?? 0),
                    2 => (int)($rating_data['two_star'] ?? 0),
                    1 => (int)($rating_data['one_star'] ?? 0)
                ]
            ];

            $stmt->close();
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    http_response_code(500);
    logSecurityEvent('api_error', 'Reviews API error: ' . $e->getMessage(), $_SESSION['username'] ?? 'guest');
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);
