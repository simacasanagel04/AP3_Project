<?php
// patient_create.php
// inside the folder public in AP3_PROJECT root

session_start();

require_once '../config/Database.php';
require_once '../classes/Patient.php';


$db = (new Database())->connect();

$patient = new Patient($db);

$message = "";
$status = "";
$new_pat_id = null;

$formData = [
    'pat_first_name'  => '',
    'pat_middle_init' => '',
    'pat_last_name'   => '',
    'pat_dob'         => '',
    'pat_gender'      => '',
    'pat_contact_num' => '',
    'pat_email'       => '',
    'pat_address'     => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validation
    $formData = [
        'pat_first_name'  => trim($_POST['pat_first_name']),
        'pat_middle_init' => trim($_POST['pat_middle_init']),
        'pat_last_name'   => trim($_POST['pat_last_name']),
        'pat_dob'         => $_POST['pat_dob'],
        'pat_gender'      => $_POST['pat_gender'],
        'pat_contact_num' => trim($_POST['pat_contact_num']),
        'pat_email'       => trim($_POST['pat_email']),
        'pat_address'     => trim($_POST['pat_address'])
    ];

    $errors = [];
    
    // Basic validation - just check if fields are filled
    if (empty($formData['pat_first_name']))  $errors[] = "First Name is required.";
    if (empty($formData['pat_last_name']))   $errors[] = "Last Name is required.";
    if (empty($formData['pat_dob']))         $errors[] = "Date of Birth is required.";
    if (empty($formData['pat_gender']))      $errors[] = "Gender is required.";
    if (empty($formData['pat_contact_num'])) $errors[] = "Contact Number is required.";
    if (empty($formData['pat_email']))       $errors[] = "Email is required.";
    if (empty($formData['pat_address']))     $errors[] = "Address is required.";

    if (empty($errors)) {
        error_log("=== PATIENT CREATE DEBUG ===");
        error_log("Form data: " . print_r($formData, true));
        
        $result = $patient->create($formData);

        error_log("Create result: " . var_export($result, true));
        error_log("Result type: " . gettype($result));

        if ($result !== false && is_numeric($result) && $result > 0) {
            $new_pat_id = $result;
            $status = "success";
            $message = "Patient <strong>{$formData['pat_first_name']} {$formData['pat_last_name']}</strong> registered successfully!";

            $_SESSION['pending_pat_id'] = $new_pat_id;
            $_SESSION['pending_email']  = $formData['pat_email'];

            error_log("SUCCESS! Patient ID: $new_pat_id stored in session");

            // Clear form
            $formData = array_fill_keys(array_keys($formData), '');

            // Redirect after 2 seconds
            header("Refresh: 5; url=signup.php");
        } else {
            $status = "error";
            
            // More detailed error message
            if ($result === false) {
                $message = "Database error occurred. Please try again or contact support.";
                error_log("ERROR: create() returned FALSE");
            } else {
                $message = "Unexpected error (result: " . var_export($result, true) . "). Please try again.";
                error_log("ERROR: Unexpected result value");
            }
            
            error_log("Form data was: " . print_r($formData, true));
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
    <title>Patient Registration | AKSyon Medical Center</title>

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
                    <h3 class="mb-0">Patient Registration</h3>
                </div>

                <div class="card-body p-3">
                    <!-- ALERT -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <?php if ($status === 'success'): ?>
                                <br>
                                <strong>Your Patient ID: <?= htmlspecialchars($new_pat_id) ?></strong>
                                <br><small>Redirecting to account creation in 5 seconds...</small>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- FORM -->
                    <form method="post" action="" id="patientForm" novalidate>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_first_name" value="<?= htmlspecialchars($formData['pat_first_name']) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">M.I.</label>
                                <input type="text" class="form-control" name="pat_middle_init" value="<?= htmlspecialchars($formData['pat_middle_init']) ?>" maxlength="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_last_name" value="<?= htmlspecialchars($formData['pat_last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="pat_dob" value="<?= htmlspecialchars($formData['pat_dob']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" name="pat_gender" required>
                                    <option value="">-- Select --</option>
                                    <option value="Male"   <?= $formData['pat_gender'] === 'Male'   ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $formData['pat_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other"  <?= $formData['pat_gender'] === 'Other'  ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_email" value="<?= htmlspecialchars($formData['pat_email']) ?>" placeholder="Any format for testing" required>
                                <small class="text-muted">You can use any format for testing</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pat_contact_num" value="<?= htmlspecialchars($formData['pat_contact_num']) ?>" placeholder="09XX-XXX-XXXX" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pat_address" rows="3" required><?= htmlspecialchars($formData['pat_address']) ?></textarea>
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
        const form = document.getElementById('patientForm');
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