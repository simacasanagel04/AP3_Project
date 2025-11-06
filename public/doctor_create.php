<?php
// public/doctor_create.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Doctor.php';

$db = (new Database())->connect();
$doctor = new Doctor($db);

// Fetch specializations for dropdown
try {
    $sql = "SELECT SPEC_ID, SPEC_NAME FROM specialization ORDER BY SPEC_NAME";
    $stmt = $db->query($sql);
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching specializations: " . $e->getMessage());
    $specializations = [];
}

$message = "";
$status = "";
$new_doc_id = null;

$formData = [
    'doc_first_name'   => '',
    'doc_middle_init'  => '',
    'doc_last_name'    => '',
    'doc_contact_num'  => '',
    'doc_email'        => '',
    'spec_id'          => ''
];

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'doc_first_name'   => trim($_POST['doc_first_name']),
        'doc_middle_init'  => trim($_POST['doc_middle_init']),
        'doc_last_name'    => trim($_POST['doc_last_name']),
        'doc_contact_num'  => trim($_POST['doc_contact_num']),
        'doc_email'        => trim($_POST['doc_email']),
        'spec_id'          => $_POST['spec_id']
    ];

    $errors = [];
    
    // Basic validation
    if (empty($formData['doc_first_name']))  $errors[] = "First Name is required.";
    if (empty($formData['doc_last_name']))   $errors[] = "Last Name is required.";
    if (empty($formData['doc_contact_num'])) $errors[] = "Contact Number is required.";
    if (empty($formData['doc_email']))       $errors[] = "Email is required.";
    if (empty($formData['spec_id']))         $errors[] = "Specialization is required.";

    if (empty($errors)) {
        try {
            // The DOC_ID is auto-incremented by the database
            // Just insert without providing DOC_ID, it will be generated automatically
            
            error_log("=== DOCTOR CREATE DEBUG ===");
            error_log("Form data: " . print_r($formData, true));

            // Insert without doc_id (let database auto-increment)
            $sql = "INSERT INTO doctor 
                    (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM, 
                     DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
                    VALUES (:doc_first_name, :doc_middle_init, :doc_last_name, :doc_contact_num,
                            :doc_email, :spec_id, NOW(), NOW())";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':doc_first_name'   => $formData['doc_first_name'],
                ':doc_middle_init'  => $formData['doc_middle_init'],
                ':doc_last_name'    => $formData['doc_last_name'],
                ':doc_contact_num'  => $formData['doc_contact_num'],
                ':doc_email'        => $formData['doc_email'],
                ':spec_id'          => $formData['spec_id']
            ]);

            if ($result) {
                // Get the auto-generated DOC_ID
                $new_doc_id = $db->lastInsertId();
                
                $status = "success";
                $message = "Doctor <strong>{$formData['doc_first_name']} {$formData['doc_last_name']}</strong> registered successfully!";

                // Store in session for signup page
                $_SESSION['pending_doc_id'] = $new_doc_id;
                $_SESSION['pending_email']  = $formData['doc_email'];
                $_SESSION['pending_user_type'] = 'doctor';

                error_log("SUCCESS! Doctor ID: $new_doc_id stored in session");

                // Clear form
                $formData = array_fill_keys(array_keys($formData), '');

                // Redirect after 5 seconds
                header("Refresh: 5; url=signup.php");
            } else {
                $status = "error";
                $message = "Failed to register doctor. Please check if email or contact number already exists.";
                error_log("ERROR: Insert failed");
            }
        } catch (PDOException $e) {
            $status = "error";
            
            // Check for duplicate entry
            if ($e->getCode() == 23000) {
                $message = "Email or contact number already exists. Please use different credentials.";
            } else {
                $message = "Database error occurred. Please try again.";
            }
            
            error_log("Exception: " . $e->getMessage());
        }
    } else {
        $status = "error";
        $message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration | AKSyon Medical Center</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body class="bg-light">

<!-- MAIN CONTENT -->
<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="card shadow-sm rounded-2 my-5">

                <!-- HEADER WITH LOGO -->
                <div class="text-center my-2">
                    <a href="../index.php"><img src="../assets/logo/logo_white_bg.png" alt="AKSyon Medical Center" height="100" class="mb-3"></a>
                </div>

                <div class="card-header text-white text-center" style="background-color: #336d96;">
                    <h3 class="mb-0">Doctor Registration</h3>
                </div>

                <div class="card-body p-3">
                    <!-- ALERT -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <?php if ($status === 'success'): ?>
                                <br>
                                <strong>Your Doctor ID: <?= htmlspecialchars($new_doc_id) ?></strong>
                                <br><small>Redirecting to account creation in 5 seconds...</small>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- FORM -->
                    <form method="post" action="" id="doctorForm" novalidate>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="doc_first_name" value="<?= htmlspecialchars($formData['doc_first_name']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">M.I.</label>
                                <input type="text" class="form-control" name="doc_middle_init" value="<?= htmlspecialchars($formData['doc_middle_init']) ?>" maxlength="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="doc_last_name" value="<?= htmlspecialchars($formData['doc_last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="doc_email" value="<?= htmlspecialchars($formData['doc_email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="doc_contact_num" value="<?= htmlspecialchars($formData['doc_contact_num']) ?>" placeholder="09XX-XXX-XXXX" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Specialization <span class="text-danger">*</span></label>
                            <select class="form-select" name="spec_id" required>
                                <option value="">-- Select Specialization --</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?= $spec['SPEC_ID'] ?>" 
                                        <?= ($formData['spec_id'] == $spec['SPEC_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($spec['SPEC_NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4" id="nextBtn">
                                <i class="bi bi-arrow-right-circle"></i> Next: Create Account
                            </button>
                        </div>

                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Already have an account? <a href="login.php" class="text-primary text-decoration-none">LOGIN</a>
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- FORM VALIDATION -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('doctorForm');
        const nextBtn = document.getElementById('nextBtn');
        const required = form.querySelectorAll('[required]');

        function validate() {
            let valid = true;
            required.forEach(field => {
                const value = field.value.trim();
                if (!value) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Enable button if all required fields are filled
            nextBtn.disabled = !valid;
            
            // Change button appearance based on validity
            if (valid) {
                nextBtn.classList.remove('btn-secondary');
                nextBtn.classList.add('btn-primary');
            } else {
                nextBtn.classList.remove('btn-primary');
                nextBtn.classList.add('btn-secondary');
            }
        }

        required.forEach(field => {
            field.addEventListener('input', validate);
            field.addEventListener('change', validate);
        });

        // Run validation on page load
        validate();
    });
</script>

</body>
</html>