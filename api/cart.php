<?php
include("../connection.php");

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit;
}

$user_id = $_SESSION['id'];
$action = sanitizeInput($_GET['action'] ?? '');

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'add':
            // Add item to cart
            $product_id = (int)($_POST['productId'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $size = sanitizeInput($_POST['size'] ?? '');
            $color = sanitizeInput($_POST['color'] ?? '');

            if (!$product_id || $quantity < 1) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid product or quantity'];
                break;
            }

            // Check if product exists
            $product_stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();

            if ($product_result->num_rows === 0) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Product not found'];
                $product_stmt->close();
                break;
            }

            $product = $product_result->fetch_assoc();
            $product_stmt->close();

            // Check stock
            if ($product['stock'] < $quantity) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Insufficient stock available'];
                break;
            }

            // Check if already in cart
            $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE userId = ? AND productId = ? AND size = ? AND color = ?");
            $check_stmt->bind_param("iiss", $user_id, $product_id, $size, $color);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                // Update quantity
                $existing = $check_result->fetch_assoc();
                $new_quantity = $existing['quantity'] + $quantity;

                if ($product['stock'] < $new_quantity) {
                    http_response_code(400);
                    $response = ['success' => false, 'message' => 'Insufficient stock for requested quantity'];
                    $check_stmt->close();
                    break;
                }

                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $new_quantity, $existing['id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new cart item
                $insert_stmt = $conn->prepare("INSERT INTO cart (userId, productId, quantity, size, color) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("iiiiss", $user_id, $product_id, $quantity, $size, $color);
                $insert_stmt->execute();
                $insert_stmt->close();
            }

            $check_stmt->close();

            logSecurityEvent('cart_api_add', 'Item added to cart via API', $_SESSION['username']);
            http_response_code(200);
            $response = [
                'success' => true,
                'message' => 'Item added to cart successfully'
            ];
            break;

        case 'remove':
            // Remove item from cart
            $cart_id = (int)($_POST['cartId'] ?? 0);

            if (!$cart_id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Cart item ID is required'];
                break;
            }

            // Verify ownership
            $verify_stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND userId = ?");
            $verify_stmt->bind_param("ii", $cart_id, $user_id);
            $verify_stmt->execute();

            if ($verify_stmt->get_result()->num_rows === 0) {
                http_response_code(403);
                $response = ['success' => false, 'message' => 'Unauthorized'];
                $verify_stmt->close();
                break;
            }
            $verify_stmt->close();

            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
            $delete_stmt->bind_param("i", $cart_id);

            if ($delete_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Item removed from cart'];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to remove item'];
            }
            $delete_stmt->close();
            break;

        case 'update':
            // Update cart item quantity
            $cart_id = (int)($_POST['cartId'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if (!$cart_id || $quantity < 1) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Invalid cart ID or quantity'];
                break;
            }

            // Get product ID from cart
            $get_stmt = $conn->prepare("SELECT productId FROM cart WHERE id = ? AND userId = ?");
            $get_stmt->bind_param("ii", $cart_id, $user_id);
            $get_stmt->execute();
            $get_result = $get_stmt->get_result();

            if ($get_result->num_rows === 0) {
                http_response_code(403);
                $response = ['success' => false, 'message' => 'Unauthorized'];
                $get_stmt->close();
                break;
            }

            $cart_item = $get_result->fetch_assoc();
            $get_stmt->close();

            // Check stock
            $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stock_stmt->bind_param("i", $cart_item['productId']);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result()->fetch_assoc();
            $stock_stmt->close();

            if ($stock_result['stock'] < $quantity) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Insufficient stock'];
                break;
            }

            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $quantity, $cart_id);

            if ($update_stmt->execute()) {
                $response = ['success' => true, 'message' => 'Quantity updated'];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to update quantity'];
            }
            $update_stmt->close();
            break;

        case 'get':
            // Get cart items
            $stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.discountPercentage, p.discountPrice 
                                   FROM cart c
                                   JOIN products p ON c.productId = p.id
                                   WHERE c.userId = ?
                                   ORDER BY c.addedAt DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cart_items = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Calculate totals
            $subtotal = 0;
            foreach ($cart_items as &$item) {
                $price = $item['discountPrice'] ?? $item['price'];
                $item['itemTotal'] = $price * $item['quantity'];
                $subtotal += $item['itemTotal'];
            }

            $response = [
                'success' => true,
                'items' => $cart_items,
                'subtotal' => $subtotal,
                'itemCount' => count($cart_items)
            ];
            break;

        case 'count':
            // Get cart item count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE userId = ?");
            $count_stmt->bind_param("i", $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            $count_stmt->close();

            $response = [
                'success' => true,
                'count' => (int)$count_result['count']
            ];
            break;

        case 'clear':
            // Clear entire cart
            $delete_stmt = $conn->prepare("DELETE FROM cart WHERE userId = ?");
            $delete_stmt->bind_param("i", $user_id);

            if ($delete_stmt->execute()) {
                logSecurityEvent('cart_api_clear', 'Cart cleared via API', $_SESSION['username']);
                $response = ['success' => true, 'message' => 'Cart cleared successfully'];
            } else {
                http_response_code(500);
                $response = ['success' => false, 'message' => 'Failed to clear cart'];
            }
            $delete_stmt->close();
            break;

        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    http_response_code(500);
    logSecurityEvent('api_error', 'Cart API error: ' . $e->getMessage(), $_SESSION['username'] ?? 'guest');
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);
