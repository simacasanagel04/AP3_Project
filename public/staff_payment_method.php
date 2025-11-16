<?php
// public/staff_payment_method.php

session_start();
require_once '../config/Database.php';
include '../includes/staff_header.php';

// Redirect if not logged in (BEFORE including header)
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$db = (new Database())->connect();
$message = "";
$status = "";

// Handle Add Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $methodName = trim($_POST['method_name']);
    
    if (!empty($methodName)) {
        try {
            $sql = "INSERT INTO payment_method (PYMT_METH_NAME, PYMT_METH_CREATED_AT, PYMT_METH_UPDATED_AT) 
                    VALUES (:name, NOW(), NOW())";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':name' => $methodName]);
            
            if ($success) {
                $status = "success";
                $message = "Payment method added successfully!";
            } else {
                $status = "error";
                $message = "Failed to add payment method.";
            }
        } catch (PDOException $e) {
            $status = "error";
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $status = "error";
        $message = "Payment method name is required.";
    }
}

// Handle Update Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $methodId = trim($_POST['method_id']);
    $methodName = trim($_POST['method_name']);
    
    if (!empty($methodId) && !empty($methodName)) {
        try {
            $sql = "UPDATE payment_method SET PYMT_METH_NAME = :name, PYMT_METH_UPDATED_AT = NOW() 
                    WHERE PYMT_METH_ID = :id";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':name' => $methodName, ':id' => $methodId]);
            
            if ($success) {
                $status = "success";
                $message = "Payment method updated successfully!";
            } else {
                $status = "error";
                $message = "Failed to update payment method.";
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

// Fetch payment methods with search/filter
try {
    $sql = "SELECT PYMT_METH_ID, PYMT_METH_NAME,
            DATE_FORMAT(PYMT_METH_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
            DATE_FORMAT(PYMT_METH_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
            FROM payment_method
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND LOWER(PYMT_METH_NAME) LIKE LOWER(:search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($filterById)) {
        $sql .= " AND PYMT_METH_ID = :id";
        $params[':id'] = $filterById;
    }
    
    $sql .= " ORDER BY PYMT_METH_ID";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentMethods = [];
    error_log("Error fetching payment methods: " . $e->getMessage());
}

// Get total count
try {
    $countSql = "SELECT COUNT(*) as total FROM payment_method";
    $countStmt = $db->query($countSql);
    $totalMethods = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $totalMethods = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods Management - AKSyon Medical Center</title>
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
                        <h5 class="card-title mb-0">PAYMENT METHODS</h5>
                        <div class="btn-group" role="group">
                            <a href="staff_payment.php" class="btn btn-outline-primary">ALL PAYMENTS</a>
                            <a href="staff_payment_method.php" class="btn btn-primary active">PAYMENT METHODS</a>
                            <a href="staff_payment_status.php" class="btn btn-outline-primary">PAYMENT STATUS</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search/Filter Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Search & Filter</h6>
                    <form method="GET" action="" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="search" class="form-label">Search by Name</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                    value="<?= htmlspecialchars($searchTerm) ?>" 
                                    placeholder="Enter payment method name">
                                <div class="form-text">Search is case-insensitive and matches any part of the name</div>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_id" class="form-label">Filter by ID</label>
                                <input type="number" class="form-control" id="filter_id" name="filter_id" 
                                    value="<?= htmlspecialchars($filterById) ?>" 
                                    placeholder="Enter ID">
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <a href="staff_payment_method.php" class="btn btn-outline-secondary flex-fill">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                Showing <strong><?= count($paymentMethods) ?></strong> of <strong><?= $totalMethods ?></strong> payment methods
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Button Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Payment Methods Management</h5>
                        <button type="button" class="btn btn-success" id="openAddModalBtn">
                            <i class="bi bi-plus-circle"></i> Add New Payment Method
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Payment Methods List</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Payment Method Name</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentMethods)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <?php if (!empty($searchTerm) || !empty($filterById)): ?>
                                                No payment methods match your search criteria
                                            <?php else: ?>
                                                No payment methods found
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($method['PYMT_METH_ID']) ?></td>
                                            <td><?= htmlspecialchars($method['PYMT_METH_NAME']) ?></td>
                                            <td><?= htmlspecialchars($method['formatted_created_at']) ?></td>
                                            <td><?= htmlspecialchars($method['formatted_updated_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                    data-id="<?= htmlspecialchars($method['PYMT_METH_ID']) ?>"
                                                    data-name="<?= htmlspecialchars($method['PYMT_METH_NAME']) ?>">
                                                    <i class="bi bi-pencil"></i> Edit
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Add New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_method_name" class="form-label">Payment Method Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_method_name" name="method_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_created_at" class="form-label">Created At</label>
                        <input type="text" class="form-control" id="add_created_at" readonly>
                        <div class="form-text">This will be automatically set when you submit</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Add Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_method_id" name="method_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_method_name" class="form-label">Payment Method Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_method_name" name="method_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DEDICATED SCRIPT FOR THIS PAGE -->
<script>
console.log('Script loaded'); // Debug line

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded'); // Debug line
    
    // Find the button
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    console.log('Button found:', openAddModalBtn); // Debug line
    
    if (openAddModalBtn) {
        // Add click event
        openAddModalBtn.addEventListener('click', function(e) {
            console.log('Button clicked!'); // Debug line
            e.preventDefault();
            
            // Set current date/time
            const now = new Date();
            const formatted = now.toLocaleString('en-US', { 
                month: 'long', 
                day: 'numeric', 
                year: 'numeric', 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            const createdAtField = document.getElementById('add_created_at');
            if (createdAtField) {
                createdAtField.value = formatted;
                console.log('Date set:', formatted); // Debug line
            }
            
            // Open modal
            const addModalEl = document.getElementById('addModal');
            const addModal = new bootstrap.Modal(addModalEl);
            addModal.show();
            console.log('Modal opened'); // Debug line
        });
    } else {
        console.error('Button NOT found!'); // Debug line
    }
    
    // Edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('edit_method_id').value = id;
            document.getElementById('edit_method_name').value = name;
            
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<footer>
    <div class="container">
        <div class="row align-items-center small">
            <div class="col-md-8 text-center text-md-start">
                <p class="mb-0 text-black">© 2025 AKSyon Medical Center. All rights reserved.</p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <a href="https://www.facebook.com/" class="text-black mx-2"><i class="bi bi-facebook fs-5"></i></a>
                <a href="https://www.instagram.com/" class="text-black mx-2"><i class="bi bi-instagram fs-5"></i></a>
                <a href="https://www.linkedin.com/" class="text-black mx-2"><i class="bi bi-linkedin fs-5"></i></a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>