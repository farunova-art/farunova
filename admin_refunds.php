<?php

/**
 * FARUNOVA Admin - Refund Management Dashboard
 * Manage customer refunds, approvals, and refund status
 * 
 * @version 1.0
 */

require_once 'connection.php';
require_once 'lib/RefundManager.php';

// Check admin access
if (!isset($_SESSION['userId']) || !($_SESSION['isAdmin'] ?? false)) {
    header('Location: login.php');
    exit;
}

$adminId = $_SESSION['userId'];
$logger->info('Admin accessed refund dashboard', ['adminId' => $adminId], 'admin');

// Initialize RefundManager
$refundManager = new RefundManager($conn, $logger, $mpesaPayment, $mpesaAuth);

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');
$page = (int)($_GET['page'] ?? 1);
$limit = 25;
$offset = ($page - 1) * $limit;

// Build query
$where = "pr.requestedAt BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
$params = [$startDate, $endDate];
$types = "ss";

if ($status !== 'all') {
    $where .= " AND pr.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Get refunds
$query = "
    SELECT pr.*, u.username, u.email, o.orderId, p.amount as paymentAmount, 
           COUNT(*) OVER () as total_count
    FROM payment_refunds pr
    JOIN users u ON pr.requestedBy = u.id
    LEFT JOIN orders o ON pr.orderId = o.id
    LEFT JOIN payments p ON pr.paymentId = p.id
    WHERE $where
    ORDER BY pr.requestedAt DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$refunds = [];
$totalCount = 0;
while ($row = $result->fetch_assoc()) {
    if (!$totalCount) $totalCount = $row['total_count'];
    unset($row['total_count']);
    $refunds[] = $row;
}

// Get statistics
$statsResult = $refundManager->getRefundStatistics($startDate, $endDate);
$stats = $statsResult['success'] ? $statsResult['statistics'] : [];

// Get pending refunds count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_refunds WHERE status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];

$totalPages = ceil($totalCount / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Management - FARUNOVA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid #007bff;
        }

        .stat-card.pending {
            border-left-color: #ffc107;
        }

        .stat-card.completed {
            border-left-color: #28a745;
        }

        .stat-card.failed {
            border-left-color: #dc3545;
        }

        .refund-table {
            font-size: 0.9rem;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-processing {
            background-color: #17a2b8;
            color: #fff;
        }

        .badge-completed {
            background-color: #28a745;
            color: #fff;
        }

        .badge-failed {
            background-color: #dc3545;
            color: #fff;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">FARUNOVA Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_payments.php">Payments</a></li>
                    <li class="nav-item"><a class="nav-link active" href="admin_refunds.php">Refunds</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3 mb-0">Refund Management</h1>
                <p class="text-muted small">Manage customer refunds and refund requests</p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $pendingCount; ?> Pending Approvals
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-1">Total Refunds</h6>
                        <h3 class="mb-0"><?php echo $stats['totalRefunds'] ?? 0; ?></h3>
                        <small class="text-muted">
                            Amount: KES <?php echo number_format($stats['totalAmount'] ?? 0, 2); ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card completed">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-1">Completed</h6>
                        <h3 class="mb-0"><?php echo $stats['completedCount'] ?? 0; ?></h3>
                        <small class="text-muted">Successful refunds</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card pending">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-1">Pending</h6>
                        <h3 class="mb-0"><?php echo $stats['pendingCount'] ?? 0; ?></h3>
                        <small class="text-muted">Awaiting approval</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card failed">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-1">Failed</h6>
                        <h3 class="mb-0"><?php echo $stats['failedCount'] ?? 0; ?></h3>
                        <small class="text-muted">Failed refunds</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="startDate" class="form-control form-control-sm" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="endDate" class="form-control form-control-sm" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Apply Filters</button>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <a href="admin_refunds.php" class="btn btn-sm btn-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Refunds Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm refund-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($refunds)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox"></i> No refunds found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($refunds as $refund): ?>
                                <tr>
                                    <td><code><?php echo substr($refund['id'], 0, 6); ?></code></td>
                                    <td>
                                        <?php if ($refund['orderId']): ?>
                                            <a href="admin_order_detail.php?orderId=<?php echo $refund['orderId']; ?>" class="text-decoration-none">
                                                #<?php echo $refund['orderId']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($refund['username']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($refund['email']); ?></small>
                                    </td>
                                    <td class="fw-bold">KES <?php echo number_format($refund['refundAmount'], 2); ?></td>
                                    <td><small><?php echo htmlspecialchars(substr($refund['reason'], 0, 30)); ?></small></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($refund['status']); ?>">
                                            <?php echo ucfirst($refund['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($refund['requestedAt'])); ?></small>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($refund['status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm" onclick="approveRefund(<?php echo $refund['id']; ?>)">
                                                Approve
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="denyRefund(<?php echo $refund['id']; ?>)">
                                                Deny
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-info btn-sm" onclick="viewRefundDetails(<?php echo $refund['id']; ?>)">
                                                Details
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status; ?>&startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&page=1">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status; ?>&startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status; ?>&startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status; ?>&startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status; ?>&startDate=<?php echo $startDate; ?>&endDate=<?php echo $endDate; ?>&page=<?php echo $totalPages; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this refund?</p>
                    <p class="text-muted small">The refund will be processed immediately with M-Pesa.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="approveBtn">Approve Refund</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Denial Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deny Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Denial</label>
                        <textarea class="form-control" id="denyReason" rows="3" placeholder="Explain why the refund is being denied"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="denyBtn">Deny Refund</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRefundId = null;
        const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
        const denyModal = new bootstrap.Modal(document.getElementById('denyModal'));

        function approveRefund(refundId) {
            currentRefundId = refundId;
            document.getElementById('approveBtn').onclick = function() {
                fetch('/api/refunds.php?action=approve', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            refundId: currentRefundId
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            };
            approveModal.show();
        }

        function denyRefund(refundId) {
            currentRefundId = refundId;
            document.getElementById('denyBtn').onclick = function() {
                const reason = document.getElementById('denyReason').value;
                if (!reason.trim()) {
                    alert('Please provide a reason for denial');
                    return;
                }
                fetch('/api/refunds.php?action=deny', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            refundId: currentRefundId,
                            reason: reason
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            };
            denyModal.show();
        }

        function viewRefundDetails(refundId) {
            alert('Refund details coming soon');
        }
    </script>
</body>

</html>