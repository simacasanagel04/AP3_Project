<?php
require_once '../config/Database.php';
require_once '../classes/Doctor.php';
include '../includes/header.php';

$db = (new Database())->connect();
$doctor = new Doctor($db);

// Fetch specializations for dropdown
try {
    $sql = "SELECT spec_id, spec_name FROM specialization ORDER BY spec_name";
    $stmt = $db->query($sql);
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching specializations: " . $e->getMessage());
    $specializations = [];
}

$message = "";
$status = "";
$formData = [
    'doc_first_name'   => '',
    'doc_middle_init'  => '',
    'doc_last_name'    => '',
    'doc_contact_num'  => '',
    'doc_email'        => '',
    'spec_id'          => ''
];

// submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'doc_first_name'   => $_POST['doc_first_name'],
        'doc_middle_init'  => $_POST['doc_middle_init'],
        'doc_last_name'    => $_POST['doc_last_name'],
        'doc_contact_num'  => $_POST['doc_contact_num'],
        'doc_email'        => $_POST['doc_email'],
        'spec_id'          => $_POST['spec_id']
    ];

    if ($doctor->create($formData)) {
        $status = "success";
        $message = "Doctor {$formData['doc_first_name']} {$formData['doc_last_name']} registered successfully!";
        // Clear form after success
        $formData = [
            'doc_first_name'   => '',
            'doc_middle_init'  => '',
            'doc_last_name'    => '',
            'doc_contact_num'  => '',
            'doc_email'        => '',
            'spec_id'          => ''
        ];
    } else {
        $status = "error";
        $message = "Failed to register doctor. Please check if email or contact number already exists.";
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
                        <h2 class="mb-0">Doctor Registration</h2>
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
                            <div class="col-md-4 mb-3">
                                <label for="doc_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="doc_first_name" id="doc_first_name"
                                    value="<?= htmlspecialchars($formData['doc_first_name']) ?>" required>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="doc_middle_init" class="form-label">M.I.</label>
                                <input type="text" class="form-control" name="doc_middle_init" id="doc_middle_init"
                                    value="<?= htmlspecialchars($formData['doc_middle_init']) ?>" maxlength="5">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="doc_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="doc_last_name" id="doc_last_name"
                                    value="<?= htmlspecialchars($formData['doc_last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="doc_contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="doc_contact_num" id="doc_contact_num"
                                    value="<?= htmlspecialchars($formData['doc_contact_num']) ?>" 
                                    placeholder="09XX-XXX-XXXX" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="doc_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="doc_email" id="doc_email"
                                    value="<?= htmlspecialchars($formData['doc_email']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="spec_id" class="form-label">Specialization <span class="text-danger">*</span></label>
                            <select class="form-select" name="spec_id" id="spec_id" required>
                                <option value="">-- Select Specialization --</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?= $spec['spec_id'] ?>" 
                                        <?= ($formData['spec_id'] == $spec['spec_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($spec['spec_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Register Doctor
                            </button>
                            <a href="doctor_list.php" class="btn btn-secondary">
                                <i class="bi bi-list"></i> View All Doctors
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