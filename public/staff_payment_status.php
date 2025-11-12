<?php
// public/staff_payment_status.php

session_start();
require_once '../config/Database.php';
include '../includes/staff_header.php';

// Check if user is logged in as staff
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['staff_id'])) {
//     header("Location: login.php");
//     exit();
// }

$db = (new Database())->connect();
$message = "";
$status = "";

// Handle Add Payment Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $statusName = trim($_POST['status_name']);
    
    if (!empty($statusName)) {
        try {
            $sql = "INSERT INTO payment_status (PYMT_STAT_NAME, PYMT_STAT_CREATED_AT, PYMT_STAT_UPDATED_AT) 
                    VALUES (:name, NOW(), NOW())";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':name' => $statusName]);
            
            if ($success) {
                $status = "success";
                $message = "Payment status added successfully!";
            } else {
                $status = "error";
                $message = "Failed to add payment status.";
            }
        } catch (PDOException $e) {
            $status = "error";
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $status = "error";
        $message = "Payment status name is required.";
    }
}

// Handle Update Payment Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $statusId = trim($_POST['status_id']);
    $statusName = trim($_POST['status_name']);
    
    if (!empty($statusId) && !empty($statusName)) {
        try {
            $sql = "UPDATE payment_status SET PYMT_STAT_NAME = :name, PYMT_STAT_UPDATED_AT = NOW() 
                    WHERE PYMT_STAT_ID = :id";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':name' => $statusName, ':id' => $statusId]);
            
            if ($success) {
                $status = "success";
                $message = "Payment status updated successfully!";
            } else {
                $status = "error";
                $message = "Failed to update payment status.";
            }
        } catch (PDOException $e) {
            $status = "error";
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $status = "error";
        $message = "All fields are required.";
    }
}

// Handle search/filter
$searchTerm = $_GET['search'] ?? '';
$filterById = $_GET['filter_id'] ?? '';

// Fetch payment statuses with search/filter
try {
    $sql = "SELECT PYMT_STAT_ID, PYMT_STAT_NAME,
            DATE_FORMAT(PYMT_STAT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
            DATE_FORMAT(PYMT_STAT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
            FROM payment_status
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND LOWER(PYMT_STAT_NAME) LIKE LOWER(:search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($filterById)) {
        $sql .= " AND PYMT_STAT_ID = :id";
        $params[':id'] = $filterById;
    }
    
    $sql .= " ORDER BY PYMT_STAT_ID";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $paymentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentStatuses = [];
    error_log("Error fetching payment statuses: " . $e->getMessage());
}

// Get total count
try {
    $countSql = "SELECT COUNT(*) as total FROM payment_status";
    $countStmt = $db->query($countSql);
    $totalStatuses = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $totalStatuses = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status Management - AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/staff_payments_style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h3 class="page-title mb-4">Staff Payment Management</h3>

            <!-- Navigation Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-0">PAYMENT STATUSES</h5>
                        <div class="btn-group" role="group">
                            <a href="staff_payment.php" class="btn btn-outline-primary">ALL PAYMENTS</a>
                            <a href="staff_payment_method.php" class="btn btn-outline-primary">PAYMENT METHODS</a>
                            <a href="staff_payment_status.php" class="btn btn-primary active">PAYMENT STATUS</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $status === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search/Filter Card -->
            <div class="card shadow-sm mb-4 search-filter-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-funnel-fill text-primary fs-4 me-2"></i>
                        <h6 class="mb-0 fw-bold">Search & Filter</h6>
                    </div>
                    <form method="GET" action="" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="search" name="search" 
                                        value="<?= htmlspecialchars($searchTerm) ?>" 
                                        placeholder="Search by status name...">
                                </div>
                                <div class="form-text ms-2">
                                    <i class="bi bi-info-circle"></i> Case-insensitive search
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-hash"></i>
                                    </span>
                                    <input type="number" class="form-control" id="filter_id" name="filter_id" 
                                        value="<?= htmlspecialchars($filterById) ?>" 
                                        placeholder="Filter by ID">
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-start gap-2">
                                <button type="submit" class="btn btn-primary btn-lg flex-fill">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="staff_payment_status.php" class="btn btn-outline-secondary btn-lg flex-fill">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </a>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-bar-chart-fill"></i> 
                                    Showing <strong class="text-primary"><?= count($paymentStatuses) ?></strong> of 
                                    <strong class="text-primary"><?= $totalStatuses ?></strong> payment statuses
                                </small>
                                <?php if (!empty($searchTerm) || !empty($filterById)): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-funnel"></i> Filters Active
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Status Button Card -->
            <div class="card shadow-sm mb-4 add-status-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="bi bi-kanban text-primary"></i> Payment Status Management
                            </h5>
                            <p class="text-muted mb-0 small">Manage and organize payment statuses</p>
                        </div>
                        <button type="button" class="btn btn-success btn-lg add-status-btn" id="openAddModalBtn">
                            <i class="bi bi-plus-circle-fill"></i> Add New Status
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Statuses Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-table"></i> Payment Statuses List
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="bi bi-hash"></i> ID</th>
                                    <th><i class="bi bi-tag-fill"></i> Status Name</th>
                                    <th><i class="bi bi-calendar-plus"></i> Created At</th>
                                    <th><i class="bi bi-calendar-check"></i> Updated At</th>
                                    <th><i class="bi bi-gear-fill"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentStatuses)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            <?php if (!empty($searchTerm) || !empty($filterById)): ?>
                                                <strong>No payment statuses match your search criteria</strong>
                                                <br><small>Try adjusting your filters</small>
                                            <?php else: ?>
                                                <strong>No payment statuses found</strong>
                                                <br><small>Click "Add New Status" to get started</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentStatuses as $paymentStatus): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($paymentStatus['PYMT_STAT_ID']) ?></strong></td>
                                            <td>
                                                <span class="badge badge-status bg-<?= $paymentStatus['PYMT_STAT_NAME'] == 'Paid' ? 'success' : ($paymentStatus['PYMT_STAT_NAME'] == 'Pending' ? 'warning' : 'secondary') ?>">
                                                    <i class="bi bi-circle-fill"></i> <?= htmlspecialchars($paymentStatus['PYMT_STAT_NAME']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><i class="bi bi-clock"></i> <?= htmlspecialchars($paymentStatus['formatted_created_at']) ?></small>
                                            </td>
                                            <td>
                                                <small><i class="bi bi-clock-history"></i> <?= htmlspecialchars($paymentStatus['formatted_updated_at']) ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                    data-id="<?= htmlspecialchars($paymentStatus['PYMT_STAT_ID']) ?>"
                                                    data-name="<?= htmlspecialchars($paymentStatus['PYMT_STAT_NAME']) ?>">
                                                    <i class="bi bi-pencil-square"></i> Update
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addModalLabel">
                    <i class="bi bi-plus-circle-fill"></i> Add New Payment Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_status_name" class="form-label">
                            <i class="bi bi-tag-fill"></i> Status Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="add_status_name" name="status_name" required placeholder="e.g., Paid, Pending, Refunded">
                    </div>
                    <div class="mb-3">
                        <label for="add_created_at" class="form-label">
                            <i class="bi bi-calendar-plus"></i> Created At
                        </label>
                        <input type="text" class="form-control" id="add_created_at" readonly disabled>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Automatically set when you submit
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle-fill"></i> Add Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bi bi-pencil-square"></i> Update Payment Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_status_id" name="status_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_status_name" class="form-label">
                            <i class="bi bi-tag-fill"></i> Status Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="edit_status_name" name="status_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save-fill"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Open Add Modal
    document.getElementById('openAddModalBtn')?.addEventListener('click', function() {
        // Set current date/time in the Created At field
        const now = new Date();
        const formatted = now.toLocaleString('en-US', { 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric', 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        document.getElementById('add_created_at').value = formatted;
        
        const modal = new bootstrap.Modal(document.getElementById('addModal'));
        modal.show();
    });
    
    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('edit_status_id').value = id;
            document.getElementById('edit_status_name').value = name;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add animation to add button
    const addBtn = document.getElementById('openAddModalBtn');
    if (addBtn) {
        addBtn.addEventListener('mouseenter', function() {
            this.classList.add('btn-pulse');
        });
        addBtn.addEventListener('mouseleave', function() {
            this.classList.remove('btn-pulse');
        });
    }
});
</script>
</body>
</html>