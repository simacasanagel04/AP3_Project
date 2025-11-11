<?php
require_once '../config/Database.php';
require_once '../classes/Payment.php';
include '../includes/header.php';

$db = (new Database())->connect();
$payment = new Payment($db);

// Fetch payment methods for dropdown
try {
    $sql = "SELECT pymt_meth_id, pymt_meth_name FROM payment_method WHERE pymt_meth_is_available = 1 ORDER BY pymt_meth_name";
    $stmt = $db->query($sql);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    $paymentMethods = [];
}

// Fetch payment statuses for dropdown
try {
    $sql = "SELECT pymt_stat_id, pymt_stat_name FROM payment_status ORDER BY pymt_stat_name";
    $stmt = $db->query($sql);
    $paymentStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payment statuses: " . $e->getMessage());
    $paymentStatuses = [];
}

// Fetch appointments for dropdown with more details
try {
    $sql = "SELECT a.appt_id, 
                   CONCAT('Appt #', a.appt_id, ' - ', 
                          CONCAT(p.pat_first_name, ' ', p.pat_last_name), 
                          ' (', DATE_FORMAT(a.appt_date, '%M %d, %Y'), ')') as appt_info
            FROM appointment a
            LEFT JOIN patient p ON a.pat_id = p.pat_id
            WHERE a.stat_id != (SELECT stat_id FROM status WHERE stat_name = 'Cancelled' LIMIT 1)
            ORDER BY a.appt_id DESC";
    $stmt = $db->query($sql);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

$message = "";
$status = "";
$formData = [
    'paymt_amount_paid' => '',
    'paymt_date'        => date('Y-m-d\TH:i'),
    'pymt_meth_id'      => '',
    'pymt_stat_id'      => '',
    'appt_id'           => ''
];

// form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'paymt_amount_paid' => $_POST['paymt_amount_paid'] ?? '',
        'paymt_date'        => $_POST['paymt_date'] ?? '',
        'pymt_meth_id'      => $_POST['pymt_meth_id'] ?? '',
        'pymt_stat_id'      => $_POST['pymt_stat_id'] ?? '',
        'appt_id'           => $_POST['appt_id'] ?? ''
    ];

    // Validate required fields
    if (empty($formData['paymt_amount_paid']) || empty($formData['paymt_date']) || 
        empty($formData['pymt_meth_id']) || empty($formData['pymt_stat_id']) || 
        empty($formData['appt_id'])) {
        $status = "error";
        $message = "All fields are required. Please fill in all the information.";
    } else {
        $newPaymtId = $payment->create($formData);
        if ($newPaymtId) {
            $status = "success";
            $message = "Payment of ₱" . number_format($formData['paymt_amount_paid'], 2) . " recorded successfully! Payment ID: " . $newPaymtId;
            // Clear form after success
            $formData = [
                'paymt_amount_paid' => '',
                'paymt_date'        => date('Y-m-d\TH:i'),
                'pymt_meth_id'      => '',
                'pymt_stat_id'      => '',
                'appt_id'           => ''
            ];
        } else {
            $status = "error";
            $message = "Failed to record payment. Please check the data or foreign key references.";
        }
    }
}
?>

<!-- Content -->
<div class="public_background flex-grow-1 d-flex justify-content-center align-items-center position-relative p-4">
    <div class="container mt-2">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="card shadow-sm border-0 rounded-2 p-4">
                    <div class="card_header card-header text-white mb-4">
                        <h2 class="mb-0">Payment Registration</h2>
                    </div>

                    <!-- Display success/error message -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="paymt_amount_paid" class="form-label">Amount Paid <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="paymt_amount_paid" id="paymt_amount_paid"
                                        value="<?= htmlspecialchars($formData['paymt_amount_paid']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="paymt_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="paymt_date" id="paymt_date"
                                    value="<?= htmlspecialchars($formData['paymt_date']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pymt_meth_id" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" name="pymt_meth_id" id="pymt_meth_id" required>
                                    <option value="">-- Select Payment Method --</option>
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <option value="<?= htmlspecialchars($method['pymt_meth_id']) ?>" 
                                            <?= ($formData['pymt_meth_id'] == $method['pymt_meth_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($method['pymt_meth_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pymt_stat_id" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="pymt_stat_id" id="pymt_stat_id" required>
                                    <option value="">-- Select Payment Status --</option>
                                    <?php foreach ($paymentStatuses as $paymentStatus): ?>
                                        <option value="<?= htmlspecialchars($paymentStatus['pymt_stat_id']) ?>" 
                                            <?= ($formData['pymt_stat_id'] == $paymentStatus['pymt_stat_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($paymentStatus['pymt_stat_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="appt_id" class="form-label">Appointment <span class="text-danger">*</span></label>
                            <select class="form-select" name="appt_id" id="appt_id" required>
                                <option value="">-- Select Appointment --</option>
                                <?php foreach ($appointments as $appt): ?>
                                    <option value="<?= htmlspecialchars($appt['appt_id']) ?>" 
                                        <?= ($formData['appt_id'] == $appt['appt_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($appt['appt_info']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Record Payment
                            </button>
                            <a href="payment_list.php" class="btn btn-secondary">
                                <i class="bi bi-list"></i> View All Payments
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    var status = "<?php echo $status; ?>";
    var message = "<?php echo $message; ?>";
</script>
<script src="js/modal.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>