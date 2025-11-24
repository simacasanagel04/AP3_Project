<?php
// public/staff_create.php

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Staff.php';

// Initialize DB and class
$db = (new Database())->connect();
$staff = new Staff($db);

$message = "";
$status = "";
$new_staff_id = null;

// Initialize form data (use uppercase keys to match SQL)
$formData = [
    'STAFF_FIRST_NAME'  => '',
    'STAFF_MIDDLE_INIT' => '',
    'STAFF_LAST_NAME'   => '',
    'STAFF_EMAIL'       => '',
    'STAFF_CONTACT_NUM' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'STAFF_FIRST_NAME'  => trim($_POST['STAFF_FIRST_NAME']),
        'STAFF_MIDDLE_INIT' => trim($_POST['STAFF_MIDDLE_INIT']),
        'STAFF_LAST_NAME'   => trim($_POST['STAFF_LAST_NAME']),
        'STAFF_EMAIL'       => trim($_POST['STAFF_EMAIL']),
        'STAFF_CONTACT_NUM' => trim($_POST['STAFF_CONTACT_NUM'])
    ];

    $errors = [];

    // Validation
    if (empty($formData['STAFF_FIRST_NAME']))   $errors[] = "First Name is required.";
    if (empty($formData['STAFF_LAST_NAME']))    $errors[] = "Last Name is required.";
    if (empty($formData['STAFF_EMAIL']))        $errors[] = "Email is required.";
    if (empty($formData['STAFF_CONTACT_NUM']))  $errors[] = "Contact Number is required.";
    
    // Check if email already exists in STAFF table (prevents creating duplicate staff records)
    if (empty($errors) && $staff->findByEmail($formData['STAFF_EMAIL'])) {
        $errors[] = "This email is already registered as a staff member.";
    }

    if (empty($errors)) {
        error_log("=== STAFF CREATE DEBUG ===");
        error_log("Form data: " . print_r($formData, true));

        // Prepare data in the format Staff::create() expects
        $staffData = [
            'first_name'  => $formData['STAFF_FIRST_NAME'],
            'middle_init' => $formData['STAFF_MIDDLE_INIT'],
            'last_name'   => $formData['STAFF_LAST_NAME'],
            'email'       => $formData['STAFF_EMAIL'],
            'phone'       => $formData['STAFF_CONTACT_NUM']
        ];

        // Call create WITHOUT user_type check (since this is public registration)
        try {
            // Direct SQL insert since we're bypassing the user_type restriction
            $sql = "INSERT INTO staff 
                    (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_MIDDLE_INIT, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT) 
                    VALUES (:first_name, :last_name, :middle_init, :phone, :email, NOW(), NOW())";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':first_name'  => $staffData['first_name'],
                ':last_name'   => $staffData['last_name'],
                ':middle_init' => $staffData['middle_init'] ?: null,
                ':phone'       => $staffData['phone'],
                ':email'       => $staffData['email']
            ]);

            if ($result) {
                $new_staff_id = $db->lastInsertId();
                error_log("SUCCESS! Staff ID: $new_staff_id");

                // Save staff info to session for sign up
                $_SESSION['pending_staff_id'] = $new_staff_id;
                $_SESSION['pending_email']    = $formData['STAFF_EMAIL'];
                $_SESSION['pending_name']     = $formData['STAFF_FIRST_NAME'] . ' ' . $formData['STAFF_LAST_NAME'];
                $_SESSION['pending_user_type'] = 'staff';

                $status = "success";
                $message = "Staff <strong>{$formData['STAFF_FIRST_NAME']} {$formData['STAFF_LAST_NAME']}</strong> registered successfully!";

                // Clear form
                $formData = array_fill_keys(array_keys($formData), '');

                // Redirect after 2 seconds
                header("Refresh: 2; url=signup.php");
            } else {
                $status = "error";
                $message = "Database error occurred during staff creation. Please try again.";
                error_log("ERROR: Staff creation failed");
            }
        } catch (PDOException $e) {
            $status = "error";
            $message = "Database error: " . $e->getMessage();
            error_log("PDO EXCEPTION in staff create: " . $e->getMessage());
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
  <title>Staff Registration</title>

  <!-- FAVICON -->
  <link rel="icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
  <link rel="shortcut icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
  <link rel="apple-touch-icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container">
  <div class="text-center my-4">
    <!-- Placeholder for logo image -->
   <a class="navbar-brand" href="../index.php">
    <img src="../assets/logo/logo-no-margn.png" alt="AKSyon Medical Center Logo" height="60">
    </a>
  </div>
</div>

<div class="container mt-3">
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header text-white text-center" style="background-color: #336d96;">
          <h2 class="mb-0">Staff Registration</h2>
        </div>
        <div class="card-body p-4">

          <!-- ALERT -->
          <?php if ($message): ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
              <?= $message ?>
              <?php if ($status === 'success'): ?>
                <br>
                <strong>Your Staff ID: <?= htmlspecialchars($new_staff_id) ?></strong>
                <br><small>Redirecting to account creation in 2 seconds...</small>
              <?php endif; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <!-- FORM -->
          <form method="post" action="" id="staffForm" novalidate>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="STAFF_FIRST_NAME" value="<?= htmlspecialchars($formData['STAFF_FIRST_NAME']) ?>" required>
              </div>
              <div class="col-md-2">
                <label class="form-label">M.I.</label>
                <input type="text" class="form-control" name="STAFF_MIDDLE_INIT" value="<?= htmlspecialchars($formData['STAFF_MIDDLE_INIT']) ?>" maxlength="5">
              </div>
              <div class="col-md-6">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="STAFF_LAST_NAME" value="<?= htmlspecialchars($formData['STAFF_LAST_NAME']) ?>" required>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="STAFF_EMAIL" value="<?= htmlspecialchars($formData['STAFF_EMAIL']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="STAFF_CONTACT_NUM" value="<?= htmlspecialchars($formData['STAFF_CONTACT_NUM']) ?>" required>
              </div>
            </div>

            <div class="mt-4 text-end">
              <button type="submit" class="btn btn-primary px-4" id="nextBtn" style="background-color: #336d96; border-color: #336d96;">
                <i class="bi bi-person-plus"></i> Register Staff & Continue to Sign Up
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- FORM VALIDATION -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('staffForm');
  const nextBtn = document.getElementById('nextBtn');
  const required = form.querySelectorAll('[required]');

  function validate() {
    let valid = true;
    required.forEach(field => {
      if (!field.value.trim()) {
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
  });

  validate();
});
</script>

</body>
</html>