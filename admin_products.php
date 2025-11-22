<?php
include("connection.php");

// Check if user is admin
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_query = "SELECT role FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if ($user['role'] !== 'admin') {
    header("location: home.php");
    exit();
}

$message = '';
$error = '';

// Handle add product
if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = (float)$_POST['price'];
    $discountPrice = isset($_POST['discountPrice']) && $_POST['discountPrice'] ? (float)$_POST['discountPrice'] : null;
    $stock = (int)$_POST['stock'];
    $sku = $conn->real_escape_string($_POST['sku']);
    $sizes = $conn->real_escape_string($_POST['sizes']);
    $colors = $conn->real_escape_string($_POST['colors']);

    $discountPercentage = 0;
    if ($discountPrice) {
        $discountPercentage = round((($price - $discountPrice) / $price) * 100);
    }

    $insert_query = "INSERT INTO products (name, description, category, price, discountPrice, discountPercentage, stock, sku, sizes, colors, featured, active)
                    VALUES ('$name', '$description', '$category', $price, " . ($discountPrice ? $discountPrice : "NULL") . ", $discountPercentage, $stock, '$sku', '$sizes', '$colors', 0, 1)";

    if (mysqli_query($conn, $insert_query)) {
        $message = "Product added successfully!";
    } else {
        $error = "Error adding product: " . mysqli_error($conn);
    }
}

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM products WHERE id = $product_id";
    if (mysqli_query($conn, $delete_query)) {
        $message = "Product deleted successfully!";
    } else {
        $error = "Error deleting product";
    }
}

// Get all products
$products_query = "SELECT * FROM products ORDER BY createdAt DESC";
$products_result = mysqli_query($conn, $products_query);
$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - FARUNOVA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .sidebar {
            background: linear-gradient(135deg, #2B547E 0%, #1a3a52 100%);
            min-height: 100vh;
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #088F8F;
        }

        .top-bar {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #2B547E;
        }

        .btn-add {
            background-color: #2B547E;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .btn-add:hover {
            background-color: #088F8F;
            text-decoration: none;
            color: white;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
            color: #2B547E;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .form-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="bi bi-gear"></i> Admin
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><i class="bi bi-graph-up"></i> Dashboard</a></li>
            <li><a href="admin_products.php" class="active"><i class="bi bi-box"></i> Products</a></li>
            <li><a href="admin_orders.php"><i class="bi bi-receipt"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="bi bi-people"></i> Customers</a></li>
            <li><a href="admin_analytics.php"><i class="bi bi-bar-chart"></i> Analytics</a></li>
            <li><a href="edit.php"><i class="bi bi-person"></i> Profile</a></li>
            <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h5 style="margin: 0; color: #2B547E;">Products Management</h5>
            </div>
            <button class="btn btn-add" onclick="openAddProductForm()">
                <i class="bi bi-plus-circle"></i> Add New Product
            </button>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header">All Products (<?php echo count($products); ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>SKU</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small style="color: #999;"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>
                                        <strong>KES <?php echo number_format($product['price'], 2); ?></strong><br>
                                        <?php if ($product['discountPrice']): ?>
                                            <small style="color: #ff4757;">KES <?php echo number_format($product['discountPrice'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] > 10): ?>
                                            <span style="color: #28a745; font-weight: 600;"><?php echo $product['stock']; ?></span>
                                        <?php elseif ($product['stock'] > 0): ?>
                                            <span style="color: #ffc107; font-weight: 600;"><?php echo $product['stock']; ?></span>
                                        <?php else: ?>
                                            <span style="color: #ff4757; font-weight: 600;">Out</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($product['sku']); ?></code></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $product['active'] ? '#28a745' : '#999'; ?>;">
                                            <?php echo $product['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="admin_products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="form-modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddProductForm()">&times;</span>
            <h4 style="color: #2B547E; margin-bottom: 20px;">Add New Product</h4>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Product Name *</label>
                    <input type="text" class="form-control" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description *</label>
                    <textarea class="form-control" name="description" rows="4" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Shirts">Shirts</option>
                                <option value="Trousers">Trousers</option>
                                <option value="Hoodies">Hoodies</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">SKU *</label>
                            <input type="text" class="form-control" name="sku" placeholder="FAR-TS-001" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Price (KES) *</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Discount Price (KES)</label>
                            <input type="number" class="form-control" name="discountPrice" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Stock *</label>
                            <input type="number" class="form-control" name="stock" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Sizes *</label>
                            <input type="text" class="form-control" name="sizes" value="S,M,L,XL,XXL" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Colors *</label>
                    <input type="text" class="form-control" name="colors" value="Black,White,Blue,Red,Gray" required>
                </div>

                <div class="mb-3">
                    <button type="submit" name="add_product" class="btn btn-add w-100">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openAddProductForm() {
            document.getElementById('addProductModal').style.display = 'block';
        }

        function closeAddProductForm() {
            document.getElementById('addProductModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addProductModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>