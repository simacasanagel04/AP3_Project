<?php
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

// Handle Delete Payment Method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $methodId = trim($_POST['method_id']);
    
    if (!empty($methodId)) {
        try {
            $sql = "DELETE FROM payment_method WHERE PYMT_METH_ID = :id";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([':id' => $methodId]);
            
            if ($success) {
                $status = "success";
                $message = "Payment method deleted successfully!";
            } else {
                $status = "error";
                $message = "Failed to delete payment method.";
            }
        } catch (PDOException $e) {
            $status = "error";
            $message = "Cannot delete: Payment method is in use.";
        }
    }
}

// Fetch all payment methods
try {
    $sql = "SELECT * FROM payment_method ORDER BY PYMT_METH_ID";
    $stmt = $db->query($sql);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentMethods = [];
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

            <!-- Add Payment Method Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Add New Payment Method</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="method_name" class="form-label">Payment Method Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="method_name" name="method_name" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle"></i> Add Method
                                </button>
                            </div>
                        </div>
                    </form>
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
                                        <td colspan="5" class="text-center text-muted py-4">No payment methods found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($method['PYMT_METH_ID']) ?></td>
                                            <td><?= htmlspecialchars($method['PYMT_METH_NAME']) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($method['PYMT_METH_CREATED_AT'])) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($method['PYMT_METH_UPDATED_AT'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                    data-id="<?= htmlspecialchars($method['PYMT_METH_ID']) ?>"
                                                    data-name="<?= htmlspecialchars($method['PYMT_METH_NAME']) ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                    data-id="<?= htmlspecialchars($method['PYMT_METH_ID']) ?>"
                                                    data-name="<?= htmlspecialchars($method['PYMT_METH_NAME']) ?>">
                                                    <i class="bi bi-trash"></i> Delete
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_method_id" name="method_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete "<strong id="delete_method_name"></strong>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('edit_method_id').value = id;
            document.getElementById('edit_method_name').value = name;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        });
    });
    
    // Delete button
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('delete_method_id').value = id;
            document.getElementById('delete_method_name').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
});
</script>
</body>
</html>