<?php
include("../connection.php");

// Set JSON response header
header('Content-Type: application/json');

$action = sanitizeInput($_GET['action'] ?? '');
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'search':
            // Search products with filters
            $q = sanitizeInput($_GET['q'] ?? '');
            $category = sanitizeInput($_GET['category'] ?? '');
            $sort = sanitizeInput($_GET['sort'] ?? 'featured');
            $limit = (int)($_GET['limit'] ?? 12);
            $offset = (int)($_GET['offset'] ?? 0);

            if (strlen($q) < 2) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Search term must be at least 2 characters'];
                break;
            }

            // Build query
            $query = "SELECT * FROM products WHERE active = TRUE AND (MATCH(name, description) AGAINST(? IN BOOLEAN MODE) OR name LIKE ? OR description LIKE ?)";
            $search_param = '%' . $q . '%';

            if ($category && $category !== 'all') {
                $query .= " AND category = ?";
            }

            // Apply sorting
            switch ($sort) {
                case 'price_low':
                    $query .= " ORDER BY price ASC";
                    break;
                case 'price_high':
                    $query .= " ORDER BY price DESC";
                    break;
                case 'newest':
                    $query .= " ORDER BY createdAt DESC";
                    break;
                case 'popular':
                    $query .= " ORDER BY (SELECT COUNT(*) FROM order_items oi WHERE oi.productId = products.id) DESC";
                    break;
                default:
                    $query .= " ORDER BY MATCH(name, description) AGAINST(?) DESC, createdAt DESC";
            }

            $query .= " LIMIT ? OFFSET ?";

            // Get count
            $count_query = "SELECT COUNT(*) as total FROM products WHERE active = TRUE AND (MATCH(name, description) AGAINST(? IN BOOLEAN MODE) OR name LIKE ? OR description LIKE ?)";
            if ($category && $category !== 'all') {
                $count_query .= " AND category = ?";
            }

            if ($category && $category !== 'all') {
                $count_stmt = $conn->prepare($count_query);
                if ($sort === 'relevance') {
                    $count_stmt->bind_param("ssss", $q, $search_param, $search_param, $category);
                } else {
                    $count_stmt->bind_param("ssss", $q, $search_param, $search_param, $category);
                }
            } else {
                $count_stmt = $conn->prepare($count_query);
                $count_stmt->bind_param("sss", $q, $search_param, $search_param);
            }
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            $count_stmt->close();

            // Get products
            if ($category && $category !== 'all') {
                $stmt = $conn->prepare($query);
                if ($sort === 'relevance') {
                    $stmt->bind_param("sssssii", $q, $search_param, $search_param, $category, $q, $limit, $offset);
                } else {
                    $stmt->bind_param("ssssii", $q, $search_param, $search_param, $category, $limit, $offset);
                }
            } else {
                $stmt = $conn->prepare($query);
                if ($sort === 'relevance') {
                    $stmt->bind_param("sssii", $q, $search_param, $search_param, $q, $limit, $offset);
                } else {
                    $stmt->bind_param("sssii", $q, $search_param, $search_param, $limit, $offset);
                }
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            logSecurityEvent('products_api_search', 'Product search via API', $q);
            $response = [
                'success' => true,
                'products' => $products,
                'total' => (int)($count_result['total'] ?? 0),
                'count' => count($products)
            ];
            break;

        case 'get':
            // Get product details
            $product_id = (int)($_GET['productId'] ?? 0);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND active = TRUE");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Product not found'];
                $stmt->close();
                break;
            }

            $product = $result->fetch_assoc();
            $stmt->close();

            // Get reviews stats
            $review_stmt = $conn->prepare("SELECT AVG(rating) as avgRating, COUNT(*) as totalReviews FROM reviews WHERE productId = ? AND status = 'approved'");
            $review_stmt->bind_param("i", $product_id);
            $review_stmt->execute();
            $review_data = $review_stmt->get_result()->fetch_assoc();
            $review_stmt->close();

            $product['averageRating'] = round($review_data['avgRating'] ?? 0, 1);
            $product['totalReviews'] = (int)($review_data['totalReviews'] ?? 0);

            $response = [
                'success' => true,
                'product' => $product
            ];
            break;

        case 'related':
            // Get related products
            $product_id = (int)($_GET['productId'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 4);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            // Get current product category
            $cat_stmt = $conn->prepare("SELECT category FROM products WHERE id = ?");
            $cat_stmt->bind_param("i", $product_id);
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();

            if ($cat_result->num_rows === 0) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Product not found'];
                $cat_stmt->close();
                break;
            }

            $cat_data = $cat_result->fetch_assoc();
            $cat_stmt->close();

            // Get related products in same category
            $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND active = TRUE LIMIT ?");
            $stmt->bind_param("sii", $cat_data['category'], $product_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $response = [
                'success' => true,
                'products' => $products,
                'count' => count($products)
            ];
            break;

        case 'featured':
            // Get featured products
            $limit = (int)($_GET['limit'] ?? 8);

            $stmt = $conn->prepare("SELECT * FROM products WHERE active = TRUE AND featured = TRUE ORDER BY createdAt DESC LIMIT ?");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $response = [
                'success' => true,
                'products' => $products,
                'count' => count($products)
            ];
            break;

        case 'categories':
            // Get all categories with product counts
            $stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM products WHERE active = TRUE GROUP BY category ORDER BY category ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $response = [
                'success' => true,
                'categories' => $categories
            ];
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    http_response_code(500);
    logSecurityEvent('api_error', 'Products API error: ' . $e->getMessage(), $_SESSION['username'] ?? 'guest');
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);
