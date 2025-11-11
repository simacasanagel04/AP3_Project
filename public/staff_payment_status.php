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

// Fetch all payment statuses
try {
    $sql = "SELECT * FROM payment_status ORDER BY PYMT_STAT_ID";
    $stmt = $db->query($sql);
    $paymentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentStatuses = [];
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

            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                Payment statuses are predefined in the system and cannot be modified directly.
            </div>

            <!-- Payment Statuses Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Payment Statuses List</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Status Name</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($paymentStatuses)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No payment statuses found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($paymentStatuses as $paymentStatus): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($paymentStatus['PYMT_STAT_ID']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $paymentStatus['PYMT_STAT_NAME'] == 'Paid' ? 'success' : ($paymentStatus['PYMT_STAT_NAME'] == 'Pending' ? 'warning' : 'secondary') ?>">
                                                    <?= htmlspecialchars($paymentStatus['PYMT_STAT_NAME']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y h:i A', strtotime($paymentStatus['PYMT_STAT_CREATED_AT'])) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($paymentStatus['PYMT_STAT_UPDATED_AT'])) ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>