<?php
    require_once '../config/Database.php';
    require_once '../classes/Patient.php';
    include '../includes/header.php';

    $db = (new Database())->connect();
    $patient = new Patient($db);

    $message = "";
    $status = "";
    $formData = [
        'pat_id'          => '',
        'pat_first_name'  => '',
        'pat_middle_init' => '',
        'pat_last_name'   => '',
        'pat_dob'         => '',
        'pat_gender'      => '',
        'pat_contact_num' => '',
        'pat_email'       => '',
        'pat_address'     => ''
    ];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formData = [
            'pat_id'          => $_POST['pat_id'],
            'pat_first_name'  => $_POST['pat_first_name'],
            'pat_middle_init' => $_POST['pat_middle_init'],
            'pat_last_name'   => $_POST['pat_last_name'],
            'pat_dob'         => $_POST['pat_dob'],
            'pat_gender'      => $_POST['pat_gender'],
            'pat_contact_num' => $_POST['pat_contact_num'],
            'pat_email'       => $_POST['pat_email'],
            'pat_address'     => $_POST['pat_address']
        ];

        if ($patient->create($formData)) {
            $status = "success";
            $message = "Patient {$formData['pat_first_name']} {$formData['pat_last_name']} registered successfully!";
            // Clear form after success
            $formData = [
                'pat_id'          => '',
                'pat_first_name'  => '',
                'pat_middle_init' => '',
                'pat_last_name'   => '',
                'pat_dob'         => '',
                'pat_gender'      => '',
                'pat_contact_num' => '',
                'pat_email'       => '',
                'pat_address'     => ''
            ];
        } else {
            $status = "error";
            $message = "Failed to register patient. Please check if Patient ID already exists.";
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
                        <h2 class="mb-0">Patient Registration</h2>
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
                                <label for="pat_id" class="form-label">Patient ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_id" id="pat_id"
                                    value="<?= htmlspecialchars($formData['pat_id']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pat_gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" name="pat_gender" id="pat_gender" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?= ($formData['pat_gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($formData['pat_gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($formData['pat_gender'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="pat_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_first_name" id="pat_first_name"
                                    value="<?= htmlspecialchars($formData['pat_first_name']) ?>" required>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="pat_middle_init" class="form-label">M.I.</label>
                                <input type="text" class="form-control" name="pat_middle_init" id="pat_middle_init"
                                    value="<?= htmlspecialchars($formData['pat_middle_init']) ?>" maxlength="5">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pat_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_last_name" id="pat_last_name"
                                    value="<?= htmlspecialchars($formData['pat_last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pat_dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="pat_dob" id="pat_dob"
                                    value="<?= htmlspecialchars($formData['pat_dob']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="pat_contact_num" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="pat_contact_num" id="pat_contact_num"
                                    value="<?= htmlspecialchars($formData['pat_contact_num']) ?>" 
                                    placeholder="09XX-XXX-XXXX" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pat_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="pat_email" id="pat_email"
                                value="<?= htmlspecialchars($formData['pat_email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="pat_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pat_address" id="pat_address" 
                                rows="3" required><?= htmlspecialchars($formData['pat_address']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Register Patient
                            </button>
                            <a href="patient_list.php" class="btn btn-secondary">
                                <i class="bi bi-list"></i> View All Patients
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