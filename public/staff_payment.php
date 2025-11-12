<?php
// public/staff_payment.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Payment.php';
include '../includes/staff_header.php';

// Check if user is logged in as staff
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['staff_id'])) {
//     header("Location: login.php");
//     exit();
// }

$db = (new Database())->connect();
$payment = new Payment($db);

$message = "";
$status = "";

// Handle filter
$filters = [
    'appt_id' => $_GET['filter_appt_id'] ?? '',
    'paymt_id' => $_GET['filter_paymt_id'] ?? '',
    'pymt_stat_id' => $_GET['filter_pymt_stat'] ?? '',
    'patient_name' => $_GET['filter_patient_name'] ?? '',
    'pymt_meth_id' => $_GET['filter_pymt_meth'] ?? '',
    'date_from' => $_GET['filter_date_from'] ?? '',
    'date_to' => $_GET['filter_date_to'] ?? ''
];

// Fetch filtered payments
$payments = $payment->allWithFilters($filters);

// Fetch payment statuses for filter dropdown
try {
    $sql = "SELECT PYMT_STAT_ID, PYMT_STAT_NAME FROM payment_status ORDER BY PYMT_STAT_NAME";
    $stmt = $db->query($sql);
    $paymentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentStatuses = [];
}

// Fetch payment methods for filter dropdown
try {
    $sql = "SELECT PYMT_METH_ID, PYMT_METH_NAME FROM payment_method ORDER BY PYMT_METH_NAME";
    $stmt = $db->query($sql);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $paymentMethods = [];
}

// Calculate totals
$totalPayments = $payment->count();
$filteredTotal = count($payments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Payment Management - AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
                        <h5 class="card-title mb-0">ALL PAYMENTS</h5>
                        <div class="btn-group" role="group">
                            <a href="staff_payment.php" class="btn btn-primary active">ALL PAYMENTS</a>
                            <a href="staff_payment_method.php" class="btn btn-outline-primary">PAYMENT METHODS</a>
                            <a href="staff_payment_status.php" class="btn btn-outline-primary">PAYMENT STATUS</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Options Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Filter Options</h6>
                    <form method="GET" action="" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="filter_appt_id" class="form-label">Appointment ID</label>
                                <input type="text" class="form-control" id="filter_appt_id" name="filter_appt_id" 
                                    value="<?= htmlspecialchars($filters['appt_id']) ?>" placeholder="Enter Appointment ID">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_paymt_id" class="form-label">Payment ID</label>
                                <input type="text" class="form-control" id="filter_paymt_id" name="filter_paymt_id" 
                                    value="<?= htmlspecialchars($filters['paymt_id']) ?>" placeholder="Enter Payment ID">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_patient_name" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="filter_patient_name" name="filter_patient_name" 
                                    value="<?= htmlspecialchars($filters['patient_name']) ?>" placeholder="First or Last Name">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_pymt_meth" class="form-label">Payment Method</label>
                                <select class="form-select" id="filter_pymt_meth" name="filter_pymt_meth">
                                    <option value="">All Methods</option>
                                    <?php foreach ($paymentMethods as $pm): ?>
                                        <option value="<?= htmlspecialchars($pm['PYMT_METH_ID']) ?>" 
                                            <?= ($filters['pymt_meth_id'] == $pm['PYMT_METH_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pm['PYMT_METH_NAME']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_pymt_stat" class="form-label">Payment Status</label>
                                <select class="form-select" id="filter_pymt_stat" name="filter_pymt_stat">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($paymentStatuses as $ps): ?>
                                        <option value="<?= htmlspecialchars($ps['PYMT_STAT_ID']) ?>" 
                                            <?= ($filters['pymt_stat_id'] == $ps['PYMT_STAT_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ps['PYMT_STAT_NAME']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_date_from" class="form-label">Paid Date From</label>
                                <input type="date" class="form-control" id="filter_date_from" name="filter_date_from" 
                                    value="<?= htmlspecialchars($filters['date_from']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_date_to" class="form-label">Paid Date To</label>
                                <input type="date" class="form-control" id="filter_date_to" name="filter_date_to" 
                                    value="<?= htmlspecialchars($filters['date_to']) ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="staff_payment.php" class="btn btn-outline-secondary flex-fill">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Total Filters Applied: <strong><?= count(array_filter($filters)) ?></strong></small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card summary-card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2">Total Payments</h6>
                                    <h3 class="card-title mb-0"><?= $totalPayments ?></h3>
                                </div>
                                <i class="bi bi-receipt fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card bg-info text-white shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2">Filtered Results</h6>
                                    <h3 class="card-title mb-0"><?= $filteredTotal ?></h3>
                                </div>
                                <i class="bi bi-funnel fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card clickable-card bg-success text-white shadow-sm" id="addPaymentCard">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-subtitle mb-2">Add Payment</h6>
                                    <h3 class="card-title mb-0"><i class="bi bi-plus-circle"></i> Record</h3>
                                </div>
                                <i class="bi bi-cash-coin fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Payment Form Card (Hidden by default) -->
            <div class="card shadow-sm mb-4 d-none" id="addPaymentFormCard">
                <div class="card-body">
                    <h5 class="card-title mb-4">Add New Payment Record</h5>
                    <form id="addPaymentForm">
                        <div class="row g-3">
                            <!-- Appointment Selection -->
                            <div class="col-md-12">
                                <label for="add_appt_id" class="form-label">Select Appointment <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select" id="add_appt_id" name="add_appt_id" required>
                                        <option value="">-- Select or Search Appointment --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="clearApptBtn">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                                <div class="form-text">Search by Appointment ID or Patient Name</div>
                            </div>

                            <!-- Appointment Details (Hidden until appointment selected) -->
                            <div id="appointmentDetails" class="d-none">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Patient Name</label>
                                        <input type="text" class="form-control" id="add_patient_name" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Service</label>
                                        <input type="text" class="form-control" id="add_service_name" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Appointment Date</label>
                                        <input type="text" class="form-control" id="add_appt_date" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Service Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="text" class="form-control" id="add_service_price" readonly>
                                        </div>
                                    </div>

                                    <!-- Previous Payments Alert -->
                                    <div class="col-md-12" id="previousPaymentsAlert" style="display:none;">
                                        <div class="alert alert-warning">
                                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Previous Payments Exist</h6>
                                            <p class="mb-2">This appointment already has payment records. You are adding an additional payment (e.g., installment).</p>
                                            <div id="previousPaymentsList"></div>
                                        </div>
                                    </div>

                                    <!-- Payment Form -->
                                    <div class="col-md-6">
                                        <label for="add_amount" class="form-label">New Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" class="form-control" id="add_amount" name="add_amount" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="add_payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select" id="add_payment_method" name="add_payment_method" required>
                                            <option value="">-- Select Method --</option>
                                            <?php foreach ($paymentMethods as $pm): ?>
                                                <option value="<?= htmlspecialchars($pm['PYMT_METH_ID']) ?>">
                                                    <?= htmlspecialchars($pm['PYMT_METH_NAME']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="add_payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="add_payment_status" name="add_payment_status" required>
                                            <option value="">-- Select Status --</option>
                                            <?php foreach ($paymentStatuses as $ps): ?>
                                                <option value="<?= htmlspecialchars($ps['PYMT_STAT_ID']) ?>">
                                                    <?= htmlspecialchars($ps['PYMT_STAT_NAME']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Select "Paid" to enable date/time field</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="add_payment_date" class="form-label">Paid Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="add_payment_date" name="add_payment_date" disabled>
                                        <div class="form-text">Only required when status is "Paid"</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success" id="submitPaymentBtn" disabled>
                                <i class="bi bi-check-circle"></i> Add Payment
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelAddBtn">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Payment Records</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Appointment ID</th>
                                    <th>Patient Name</th>
                                    <th>Service</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                    <th>Updated Date & Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No payment records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['PAYMT_ID']) ?></td>
                                            <td><?= htmlspecialchars($p['APPT_ID']) ?></td>
                                            <td><?= htmlspecialchars($p['patient_name']) ?></td>
                                            <td><?= htmlspecialchars($p['SERV_NAME'] ?? 'N/A') ?></td>
                                            <td>₱<?= number_format($p['PAYMT_AMOUNT_PAID'], 2) ?></td>
                                            <td><?= htmlspecialchars($p['PYMT_METH_NAME']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $p['PYMT_STAT_NAME'] == 'Paid' ? 'success' : ($p['PYMT_STAT_NAME'] == 'Pending' ? 'warning' : 'secondary') ?>">
                                                    <?= htmlspecialchars($p['PYMT_STAT_NAME']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($p['formatted_paymt_date']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary update-payment-btn" 
                                                    data-id="<?= htmlspecialchars($p['PAYMT_ID']) ?>"
                                                    data-appt="<?= htmlspecialchars($p['APPT_ID']) ?>">
                                                    <i class="bi bi-pencil"></i> Update
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

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" aria-labelledby="updatePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updatePaymentModalLabel">Update Payment Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updatePaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="update_paymt_id" name="update_paymt_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="update_paymt_id_display" class="form-label">Payment ID</label>
                            <input type="text" class="form-control" id="update_paymt_id_display" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="update_appt_id" class="form-label">Appointment ID</label>
                            <input type="text" class="form-control" id="update_appt_id" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="update_amount" class="form-label">Amount Paid <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" id="update_amount" name="update_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="update_payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="update_payment_method" name="update_payment_method" required>
                                <option value="">-- Select Method --</option>
                                <?php foreach ($paymentMethods as $pm): ?>
                                    <option value="<?= htmlspecialchars($pm['PYMT_METH_ID']) ?>">
                                        <?= htmlspecialchars($pm['PYMT_METH_NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="update_payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="update_payment_status" name="update_payment_status" required>
                                <option value="">-- Select Status --</option>
                                <?php foreach ($paymentStatuses as $ps): ?>
                                    <option value="<?= htmlspecialchars($ps['PYMT_STAT_ID']) ?>">
                                        <?= htmlspecialchars($ps['PYMT_STAT_NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="update_payment_date" class="form-label">Updated Date & Time</label>
                            <input type="datetime-local" class="form-control" id="update_payment_date" name="update_payment_date">
                            <div class="form-text">Only required when status is "Paid"</div>
                        </div>
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

<!-- Alert Container -->
<div class="alert-container" id="alertContainer"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/staff_payments_script.js"></script>
</body>
</html>