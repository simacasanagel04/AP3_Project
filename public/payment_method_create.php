<?php
require_once '../config/Database.php';
require_once '../classes/Payment_Method.php';
include '../includes/header.php';

$db = (new Database())->connect();
$payment_Method = new Payment_Method($db);

$message = "";
$status = "";
$formData = [
    'pymt_meth_name' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'pymt_meth_name' => $_POST['pymt_meth_name']
    ];

    $newPymtMethId = $payment_Method->create($formData);
    if ($newPymtMethId) {
        $status = "success";
        $message = "Payment method '" . htmlspecialchars($formData['pymt_meth_name']) . "' created successfully! Method ID: " . $newPymtMethId;
        // Clear form after success
        $formData = ['pymt_meth_name' => ''];
    } else {
        $status = "error";
        $message = "Failed to create payment method. Please check the data.";
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
                        <h2 class="mb-0">Payment Method Registration</h2>
                    </div>

                    <!-- Display success/error message -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="pymt_meth_name" class="form-label">Method Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pymt_meth_name" id="pymt_meth_name"
                                value="<?= htmlspecialchars($formData['pymt_meth_name']) ?>" required maxlength="50">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create Payment Method
                            </button>
                            <a href="payment_method_list.php" class="btn btn-secondary">
                                <i class="bi bi-list"></i> View All Payment Methods
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