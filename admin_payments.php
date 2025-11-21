<?php
session_start();
include("connection.php");

// Check if user is admin
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header("location: login.php");
    exit();
}

// Get filters
$statusFilter = sanitizeInput($_GET['status'] ?? 'all');
$dateFrom = sanitizeInput($_GET['from'] ?? date('Y-m-01'));
$dateTo = sanitizeInput($_GET['to'] ?? date('Y-m-d'));

// Build query
$where = "WHERE p.initiatedAt BETWEEN '$dateFrom' AND '$dateTo'";
if ($statusFilter !== 'all') {
    $where .= " AND p.status = '$statusFilter'";
}

// Get payment statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue
    FROM payments
    WHERE initiatedAt BETWEEN ? AND ?
");
$stats_stmt->bind_param("ss", $dateFrom, $dateTo);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Get payment records
$payments_query = "
    SELECT 
        p.id, p.orderId, p.amount, p.status, p.phoneNumber,
        p.mpesaReceiptCode, p.initiatedAt, p.completedAt,
        u.username, o.totalPrice
    FROM payments p
    JOIN users u ON p.userId = u.id
    JOIN orders o ON p.orderId = o.id
    $where
    ORDER BY p.initiatedAt DESC
    LIMIT 50
";

$payments_result = $conn->query($payments_query);
$payments = $payments_result->fetch_all(MYSQLI_ASSOC);

// Get daily revenue
$daily_stmt = $conn->prepare("
    SELECT 
        DATE(initiatedAt) as date,
        COUNT(*) as transactions,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue
    FROM payments
    WHERE initiatedAt BETWEEN ? AND ?
    GROUP BY DATE(initiatedAt)
    ORDER BY date DESC
");
$daily_stmt->bind_param("ss", $dateFrom, $dateTo);
$daily_stmt->execute();
$daily_result = $daily_stmt->get_result();
$daily_data = $daily_result->fetch_all(MYSQLI_ASSOC);
$daily_stmt->close();

$logger->info('Admin payments viewed', ['user' => $_SESSION['username'], 'filter' => $statusFilter]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .stat-card.completed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card.failed {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .payment-row {
            background: white;
            border-left: 4px solid #088F8F;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .payment-row.completed {
            border-left-color: #28a745;
        }

        .payment-row.failed {
            border-left-color: #dc3545;
        }

        .payment-row.pending {
            border-left-color: #ffc107;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
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

    <!-- Admin Layout -->
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
                            <a class="nav-link active" href="admin_payments.php">
                                <i class="bi bi-credit-card"></i> Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_orders.php">
                                <i class="bi bi-bag"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_analytics.php">
                                <i class="bi bi-graph-up"></i> Analytics
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4 py-4">
                <h1 class="mb-4">Payment Management</h1>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5>Total Payments</h5>
                            <div class="value"><?php echo $stats['total_payments'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card completed">
                            <h5>Completed</h5>
                            <div class="value"><?php echo $stats['completed'] ?? 0; ?></div>
                            <small>KES <?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pending">
                            <h5>Pending</h5>
                            <div class="value"><?php echo $stats['pending'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card failed">
                            <h5>Failed</h5>
                            <div class="value"><?php echo $stats['failed'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="from" class="form-label">From Date</label>
                                <input type="date" name="from" id="from" class="form-control" value="<?php echo $dateFrom; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="to" class="form-label">To Date</label>
                                <input type="date" name="to" id="to" class="form-control" value="<?php echo $dateTo; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="admin_payments.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Transactions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Transactions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($payments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead style="background-color: #2B547E; color: white;">
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Phone</th>
                                            <th>M-Pesa Code</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted"><?php echo substr($payment['id'], 0, 8); ?></small>
                                                </td>
                                                <td>
                                                    <a href="order_detail.php?id=<?php echo $payment['orderId']; ?>" class="text-decoration-none">
                                                        #<?php echo $payment['orderId']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                                <td><strong>KES <?php echo number_format($payment['amount'], 0); ?></strong></td>
                                                <td><?php echo htmlspecialchars($payment['phoneNumber']); ?></td>
                                                <td>
                                                    <?php if ($payment['mpesaReceiptCode']): ?>
                                                        <code><?php echo htmlspecialchars($payment['mpesaReceiptCode']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo strtolower($payment['status']); ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y H:i', strtotime($payment['initiatedAt'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No payments found for the selected criteria.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Daily Revenue -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daily Revenue Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead style="background-color: #f8f9fa;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Transactions</th>
                                        <th>Successful</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daily_data as $day): ?>
                                        <tr>
                                            <td><strong><?php echo date('M d, Y', strtotime($day['date'])); ?></strong></td>
                                            <td><?php echo $day['transactions']; ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $day['successful']; ?></span>
                                            </td>
                                            <td><strong>KES <?php echo number_format($day['revenue'] ?? 0, 0); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>