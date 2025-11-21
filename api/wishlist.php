<?php
session_start();
include("../connection.php");

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit();
}

$user_id = $_SESSION['id'];
$action = sanitizeInput($_GET['action'] ?? '');
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'add':
            // Add item to wishlist
            $product_id = (int)($_POST['productId'] ?? 0);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            // Check if product exists
            $product_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
            $product_check->bind_param("i", $product_id);
            $product_check->execute();

            if ($product_check->get_result()->num_rows === 0) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Product not found'];
                $product_check->close();
                break;
            }
            $product_check->close();

            // Check if already in wishlist
            $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE userId = ? AND productId = ?");
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows > 0) {
                $check_stmt->close();
                http_response_code(409);
                $response = ['success' => false, 'message' => 'Item already in wishlist'];
                break;
            }
            $check_stmt->close();

            // Add to wishlist
            $insert_stmt = $conn->prepare("INSERT INTO wishlist (userId, productId) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $user_id, $product_id);

            if ($insert_stmt->execute()) {
                logSecurityEvent('wishlist_add', 'Product added to wishlist', $_SESSION['username']);
                http_response_code(201);
                $response = ['success' => true, 'message' => 'Added to wishlist'];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to add to wishlist'];
            }
            $insert_stmt->close();
            break;

        case 'remove':
            // Remove item from wishlist
            $product_id = (int)($_POST['productId'] ?? 0);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE userId = ? AND productId = ?");
            $delete_stmt->bind_param("ii", $user_id, $product_id);

            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    logSecurityEvent('wishlist_remove', 'Product removed from wishlist', $_SESSION['username']);
                    $response = ['success' => true, 'message' => 'Removed from wishlist'];
                } else {
                    http_response_code(404);
                    $response = ['success' => false, 'message' => 'Item not in wishlist'];
                }
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to remove from wishlist'];
            }
            $delete_stmt->close();
            break;

        case 'check':
            // Check if product is in wishlist
            $product_id = (int)($_GET['productId'] ?? 0);

            if (!$product_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Product ID is required'];
                break;
            }

            $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE userId = ? AND productId = ?");
            $check_stmt->bind_param("ii", $user_id, $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            $response = [
                'success' => true,
                'inWishlist' => $result->num_rows > 0
            ];
            $check_stmt->close();
            break;

        case 'count':
            // Get wishlist item count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE userId = ?");
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $result = $count_stmt->get_result();
            $row = $result->fetch_assoc();

            $response = [
                'success' => true,
                'count' => (int)$row['count']
            ];
            $count_stmt->close();
            break;

        case 'list':
            // Get wishlist items
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = (int)($_GET['offset'] ?? 0);

            $list_stmt = $conn->prepare("SELECT p.*, w.addedAt 
                                        FROM wishlist w
                                        JOIN products p ON w.productId = p.id
                                        WHERE w.userId = ?
                                        ORDER BY w.addedAt DESC
                                        LIMIT ? OFFSET ?");
            $list_stmt->bind_param("iii", $user_id, $limit, $offset);
            $list_stmt->execute();
            $result = $list_stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);

            $response = [
                'success' => true,
                'items' => $items,
                'count' => count($items)
            ];
            $list_stmt->close();
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    http_response_code(500);
    logSecurityEvent('api_error', 'Wishlist API error: ' . $e->getMessage(), $_SESSION['username']);
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);
