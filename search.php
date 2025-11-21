<?php
session_start();
include("connection.php");

// Get search parameters
$search = isset($_GET['q']) ? sanitizeInput(trim($_GET['q'])) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'relevance';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Pagination settings
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Initialize products array
$products = [];
$search_query = '';
$error = null;
$totalProducts = 0;
$totalPages = 1;

// Perform search if query is provided
if (!empty($search)) {
    // Validate search input length
    if (strlen($search) < 2) {
        $error = "Search term must be at least 2 characters long.";
    } else {
        // First, get total count
        $count_query = "SELECT COUNT(*) as total FROM products 
                      WHERE (MATCH(name, description) AGAINST(? IN BOOLEAN MODE) 
                             OR name LIKE ?
                             OR description LIKE ?)
                      AND active = TRUE";

        if ($category && $category != 'all') {
            $count_query .= " AND category = ?";
        }

        $search_param = '%' . $search . '%';

        if ($category && $category != 'all') {
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param("ssss", $search, $search_param, $search_param, $category);
        } else {
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param("sss", $search, $search_param, $search_param);
        }

        if ($count_stmt->execute()) {
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            $totalProducts = $count_data['total'];
            $totalPages = ceil($totalProducts / $itemsPerPage);
        }
        $count_stmt->close();

        // Use FULLTEXT search with prepared statement
        $search_param = '%' . $search . '%';

        // Build the query with category filter
        $query = "SELECT * FROM products 
                  WHERE (MATCH(name, description) AGAINST(? IN BOOLEAN MODE) 
                         OR name LIKE ?
                         OR description LIKE ?)
                  AND active = TRUE";

        if ($category && $category != 'all') {
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

        // Add pagination
        $query .= " LIMIT ? OFFSET ?";

        // Prepare and execute statement
        if ($category && $category != 'all') {
            $stmt = $conn->prepare($query);
            if ($sort === 'relevance') {
                $stmt->bind_param("sssssii", $search, $search_param, $search_param, $category, $search, $itemsPerPage, $offset);
            } else {
                $stmt->bind_param("ssssii", $search, $search_param, $search_param, $category, $itemsPerPage, $offset);
            }
        } else {
            $stmt = $conn->prepare($query);
            if ($sort === 'relevance') {
                $stmt->bind_param("sssii", $search, $search_param, $search_param, $search, $itemsPerPage, $offset);
            } else {
                $stmt->bind_param("sssii", $search, $search_param, $search_param, $itemsPerPage, $offset);
            }
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "An error occurred during search. Please try again.";
        }
        $stmt->close();

        $search_query = htmlspecialchars($search);
    }
}

// Log search for analytics
if (!empty($search) && empty($error)) {
    logSecurityEvent('product_search', 'Product search performed', $search);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - FARUNOVA Clothing Store</title>
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

        .search-bar {
            background: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .search-form {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            gap: 10px;
        }

        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-form input:focus {
            border-color: #088F8F;
            outline: none;
        }

        .search-form button {
            padding: 12px 30px;
            background-color: #2B547E;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-form button:hover {
            background-color: #088F8F;
        }

        .search-info {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f8ff;
            border-left: 4px solid #088F8F;
            border-radius: 5px;
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
            font-size: 16px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group h6 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2B547E;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            color: #333;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            margin-right: 8px;
            cursor: pointer;
        }

        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
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

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-results i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .results-count {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .view-detail-link {
            color: #088F8F;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .view-detail-link:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-form button {
                width: 100%;
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
        <h1>Search FARUNOVA Products</h1>
        <p>Find exactly what you're looking for</p>
    </div>

    <!-- Search Bar -->
    <div class="container search-bar">
        <form class="search-form" method="GET">
            <input type="text" name="q" placeholder="Search for clothing, sizes, colors..." value="<?php echo $search_query; ?>" required>
            <button type="submit" class="btn-search"><i class="bi bi-search"></i> Search</button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="error-message">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filters -->
            <div class="col-lg-3 col-md-4">
                <div class="filters">
                    <h5 class="filter-title">Refine Search</h5>

                    <form method="GET">
                        <input type="hidden" name="q" value="<?php echo $search_query; ?>">

                        <div class="filter-group">
                            <h6>Category</h6>
                            <label><input type="radio" name="category" value="all" <?php echo (!$category || $category == 'all') ? 'checked' : ''; ?> onchange="this.form.submit()"> All Products</label>
                            <label><input type="radio" name="category" value="Shirts" <?php echo $category == 'Shirts' ? 'checked' : ''; ?> onchange="this.form.submit()"> Shirts</label>
                            <label><input type="radio" name="category" value="Trousers" <?php echo $category == 'Trousers' ? 'checked' : ''; ?> onchange="this.form.submit()"> Trousers</label>
                            <label><input type="radio" name="category" value="Hoodies" <?php echo $category == 'Hoodies' ? 'checked' : ''; ?> onchange="this.form.submit()"> Hoodies</label>
                        </div>

                        <div class="filter-group">
                            <h6>Sort By</h6>
                            <select class="form-select form-select-sm" name="sort" onchange="this.form.submit()">
                                <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>Most Relevant</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9 col-md-8">
                <?php if (!empty($search)): ?>
                    <div class="search-info">
                        <i class="bi bi-info-circle"></i>
                        <?php if (count($products) > 0): ?>
                            Showing <strong><?php echo (($page - 1) * $itemsPerPage) + 1; ?></strong> to <strong><?php echo min($page * $itemsPerPage, $totalProducts); ?></strong> of <strong><?php echo $totalProducts; ?></strong> result<?php echo $totalProducts !== 1 ? 's' : ''; ?> for "<strong><?php echo $search_query; ?></strong>"
                        <?php else: ?>
                            No results found for "<strong><?php echo $search_query; ?></strong>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (count($products) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card product-card">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none;">
                                        <div class="product-image">
                                            <?php if ($product['image']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <?php else: ?>
                                                <div>No Image</div>
                                            <?php endif; ?>
                                            <?php if ($product['discountPercentage'] > 0): ?>
                                                <span class="product-badge">-<?php echo $product['discountPercentage']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <div class="product-body">
                                        <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <div class="product-price">
                                            <?php if ($product['discountPercentage'] > 0): ?>
                                                <span class="original-price">KES <?php echo number_format($product['price'], 2); ?></span>
                                                KES <?php echo number_format($product['price'] * (1 - $product['discountPercentage'] / 100), 2); ?>
                                            <?php else: ?>
                                                KES <?php echo number_format($product['price'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                        <button class="add-to-cart-btn" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                                            <i class="bi bi-info-circle"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" style="margin-top: 40px;">
                            <ul class="pagination justify-content-center">
                                <!-- Previous button -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=1">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>">
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
                                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>">
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
                                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>">
                                            Next
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $totalPages; ?>">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($search) && empty($error)): ?>
                        <div class="no-results">
                            <i class="bi bi-search"></i>
                            <h4>No Products Found</h4>
                            <p>We couldn't find any products matching your search criteria.</p>
                            <p style="color: #999; font-size: 14px;">Try different keywords or browse our <a href="products.php" style="color: #088F8F;">full product collection</a></p>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="bi bi-search"></i>
                            <h4>Start Searching</h4>
                            <p>Enter a search term above to find products</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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