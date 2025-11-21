<?php
session_start();
include("connection.php");

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Pagination settings
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Build query for total count
$countQuery = "SELECT COUNT(*) as total FROM products WHERE active = TRUE";

if ($category && $category != 'all') {
    $category_safe = $conn->real_escape_string($category);
    $countQuery .= " AND category = '$category_safe'";
}

$countResult = mysqli_query($conn, $countQuery);
$countData = mysqli_fetch_assoc($countResult);
$totalProducts = $countData['total'];
$totalPages = ceil($totalProducts / $itemsPerPage);

// Build main query
$query = "SELECT * FROM products WHERE active = TRUE";

if ($category && $category != 'all') {
    $category_safe = $conn->real_escape_string($category);
    $query .= " AND category = '$category_safe'";
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
    default:
        $query .= " ORDER BY featured DESC, createdAt DESC";
}

// Add pagination
$query .= " LIMIT $itemsPerPage OFFSET $offset";

$result = mysqli_query($conn, $query);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - FARUNOVA Clothing Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 250px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #999;
            position: relative;
            overflow: hidden;
        }

        .product-badge {
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

        .product-body {
            padding: 15px;
        }

        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2B547E;
        }

        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #088F8F;
            margin-bottom: 10px;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
            margin-right: 10px;
        }

        .add-to-cart-btn {
            background-color: #2B547E;
            color: white;
            border: none;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .add-to-cart-btn:hover {
            background-color: #088F8F;
        }

        .filters {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .filter-title {
            font-weight: 600;
            color: #2B547E;
            margin-bottom: 15px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            color: #333;
            font-size: 14px;
        }

        .filter-group input {
            margin-right: 8px;
            cursor: pointer;
        }

        .page-header {
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            text-align: center;
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #999;
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
                        <li class="nav-item"><a class="nav-link active" href="products.php">Shop</a></li>
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
        <h1>Shop FARUNOVA Clothing</h1>
        <p>Authentic Shirts, Trousers & Hoodies</p>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Filters -->
            <div class="col-lg-3 col-md-4">
                <div class="filters">
                    <h5 class="filter-title">Filter Products</h5>

                    <div class="filter-group">
                        <h6 style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Category</h6>
                        <label><input type="radio" name="category" value="all" <?php echo (!$category || $category == 'all') ? 'checked' : ''; ?> onchange="filterProducts()"> All Products</label>
                        <label><input type="radio" name="category" value="Shirts" <?php echo $category == 'Shirts' ? 'checked' : ''; ?> onchange="filterProducts()"> Shirts</label>
                        <label><input type="radio" name="category" value="Trousers" <?php echo $category == 'Trousers' ? 'checked' : ''; ?> onchange="filterProducts()"> Trousers</label>
                        <label><input type="radio" name="category" value="Hoodies" <?php echo $category == 'Hoodies' ? 'checked' : ''; ?> onchange="filterProducts()"> Hoodies</label>
                    </div>

                    <div class="filter-group">
                        <h6 style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Sort By</h6>
                        <select class="form-select form-select-sm" onchange="sortProducts()">
                            <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9 col-md-8">
                <?php if (count($products) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card product-card">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none;">
                                        <div class="product-image">
                                            <?php if ($product['image']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div>No Image Available</div>
                                            <?php endif; ?>
                                            <?php if ($product['discountPercentage'] > 0): ?>
                                                <span class="product-badge">-<?php echo $product['discountPercentage']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <div class="product-body">
                                        <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <div class="product-price">
                                            KES <?php echo number_format($product['discountPrice'] ?? $product['price'], 2); ?>
                                            <?php if ($product['discountPrice']): ?>
                                                <span class="original-price">KES <?php echo number_format($product['price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                            <i class="bi bi-cart"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation" style="margin-top: 40px;">
                        <ul class="pagination justify-content-center">
                            <!-- Previous button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=1">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif;

                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($endPage < $totalPages): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>

                            <!-- Next button -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>">
                                        Next
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $totalPages; ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- Pagination info -->
                    <div style="text-align: center; color: #666; margin-top: 15px; font-size: 14px;">
                        Showing <?php echo (($page - 1) * $itemsPerPage) + 1; ?> to <?php echo min($page * $itemsPerPage, $totalProducts); ?> of <?php echo $totalProducts; ?> products
                    </div>
                <?php else: ?>
                    <div class="no-products">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 20px; display: block;"></i>
                        <h4>No products found</h4>
                        <p>Try adjusting your filters</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: #2B547E; color: white; margin-top: 50px; padding: 30px 0; text-align: center;">
        <p>&copy; 2025 FARUNOVA - Authentic Clothing Store. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterProducts() {
            const category = document.querySelector('input[name="category"]:checked').value;
            const sort = document.querySelector('select').value;
            window.location.href = `products.php?category=${category}&sort=${sort}`;
        }

        function sortProducts() {
            const sort = event.target.value;
            const category = document.querySelector('input[name="category"]:checked').value;
            window.location.href = `products.php?category=${category}&sort=${sort}`;
        }

        function addToCart(productId, productName) {
            <?php if (!isset($_SESSION['username'])): ?>
                alert('Please login to add items to cart');
                window.location.href = 'login.php';
            <?php else: ?>
                // Redirect to product detail for size/color selection
                window.location.href = `product_detail.php?id=${productId}#addToCart`;
            <?php endif; ?>
        }
    </script>
</body>

</html>