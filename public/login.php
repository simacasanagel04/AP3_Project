<?php
// public/login.php

session_start();
require_once '../config/Database.php';
require_once '../classes/User.php';
require_once '../classes/Patient.php';
require_once '../classes/Doctor.php';
require_once '../classes/Staff.php';

$db = (new Database())->connect();
$user = new User($db);
$patient = new Patient($db);
$doctor = new Doctor($db);
$staff = new Staff($db);

$errors = [];
$success_login = false;
$user_data_json = '';
$redirect_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        $userData = $user->findByUsername($email);

        if ($userData) {
            $passwordMatch = false;

            if (substr($userData['PASSWORD'], 0, 4) === '$2y$') {
                $passwordMatch = password_verify($password, $userData['PASSWORD']);
            } else {
                $passwordMatch = ($password === $userData['PASSWORD']);
            }

            if ($passwordMatch) {
                $_SESSION['user_id'] = $userData['USER_ID'];
                $_SESSION['username'] = $userData['USER_NAME'];

                $user->updateLastLogin($userData['USER_ID']);

                $localStorageData = [
                    'email' => $email,
                    'user_id' => $userData['USER_ID'],
                    'user_type' => '',
                    'logged_in' => true,
                    'is_logged_in' => 'true'
                ];

            /*
            ============================================
            SUPERADMIN FIXED REDIRECT
            ============================================
            */
            if (!empty($userData['USER_IS_SUPERADMIN']) && $userData['USER_IS_SUPERADMIN'] == 1) {

                $_SESSION['user_type'] = 'superadmin';
                $_SESSION['is_superadmin'] = true;

                $userName = 'Super Administrator';

                // IMPORTANT: Superadmins should NOT have STAFF_ID according to your database constraint
                // The constraint is: (USER_IS_SUPERADMIN = 1 AND STAFF_ID IS NULL AND DOC_ID IS NULL AND PAT_ID IS NULL)
                // So we don't fetch staff data for superadmins

                $localStorageData['user_type'] = 'superadmin';
                $localStorageData['is_superadmin'] = true;
                $localStorageData['user_name'] = $userName;

                // FIXED PATH:
                $localStorageData['dashboard_link'] = 'superadmin/superadmin_dashboard.php';

                $success_login = true;
                $user_data_json = json_encode($localStorageData);

                // FIXED REDIRECT:
                $redirect_url = 'superadmin/superadmin_dashboard.php';

                /*
                ======================================================
                PATIENT
                ======================================================
                */
                } elseif (!empty($userData['PAT_ID'])) {

                    $_SESSION['user_type'] = 'patient';
                    $_SESSION['pat_id'] = $userData['PAT_ID'];

                    $patientData = $patient->findById($userData['PAT_ID']);

                    if ($patientData) {
                        $userName = trim($patientData['pat_first_name'] . ' ' . $patientData['pat_last_name']);

                        $localStorageData['user_type'] = 'patient';
                        $localStorageData['pat_id'] = $userData['PAT_ID'];
                        $localStorageData['user_name'] = $userName;
                        $localStorageData['dashboard_link'] = 'patient_dashb.php';

                        $success_login = true;
                        $user_data_json = json_encode($localStorageData);
                        $redirect_url = 'patient_dashb.php';
                    } else {
                        $errors[] = "Patient data not found.";
                    }

                /*
                ======================================================
                DOCTOR
                ======================================================
                */
                } elseif (!empty($userData['DOC_ID'])) {

                    $_SESSION['user_type'] = 'doctor';
                    $_SESSION['doc_id'] = $userData['DOC_ID'];

                    $doctorData = $doctor->findById($userData['DOC_ID']);

                    if ($doctorData) {
                        $userName = "Dr. " . trim($doctorData['doc_first_name'] . ' ' . $doctorData['doc_last_name']);

                        $localStorageData['user_type'] = 'doctor';
                        $localStorageData['doc_id'] = $userData['DOC_ID'];
                        $localStorageData['user_name'] = $userName;
                        $localStorageData['dashboard_link'] = 'doctor_dashb.php';

                        $success_login = true;
                        $user_data_json = json_encode($localStorageData);
                        $redirect_url = 'doctor_dashb.php';
                    } else {
                        $errors[] = "Doctor data not found.";
                    }

                /*
                ======================================================
                STAFF
                ======================================================
                */
                } elseif (!empty($userData['STAFF_ID'])) {

                    $_SESSION['user_type'] = 'staff';
                    $_SESSION['staff_id'] = $userData['STAFF_ID'];

                    $staffData = $staff->getStaffById($userData['STAFF_ID']);

                    if ($staffData) {
                        $userName = trim($staffData['STAFF_FIRST_NAME'] . ' ' . $staffData['STAFF_LAST_NAME']);

                        $localStorageData['user_type'] = 'staff';
                        $localStorageData['staff_id'] = $userData['STAFF_ID'];
                        $localStorageData['user_name'] = $userName;
                        $localStorageData['dashboard_link'] = 'staff_dashboard.php';

                        $success_login = true;
                        $user_data_json = json_encode($localStorageData);
                        $redirect_url = 'staff_dashboard.php';
                    } else {
                        $errors[] = "Staff data not found.";
                    }

                } else {
                    $errors[] = "User role could not be determined.";
                }

            } else {
                $errors[] = "Invalid email or password.";
            }
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AKSyon Medical Center</title>

    <!-- FAVICON -->
    <link rel="icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
    <link rel="shortcut icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <a href="../index.php"><img src="../assets/logo/logo_white_bg.png" height="80"></a>
                        <h5 class="mt-3">Welcome Back</h5>
                        <p class="text-muted small">Login to your account.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 text-start">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3 text-start">
                            <label class="form-label"><strong>Email:</strong></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label"><strong>Password:</strong></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            LOGIN
                        </button>
                    </form>

                    <div class="mt-4">
                        <small class="text-muted">
                            Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" class="text-primary text-decoration-none">Register</a>
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- REGISTER MODAL (Same as index.php) -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="registerModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Register As</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-center text-muted mb-4">Choose your account type to get started</p>
                <div class="row g-3">
                    <!-- PATIENT CARD -->
                    <div class="col-12">
                        <a href="patient_create.php" class="text-decoration-none">
                            <div class="register-card p-4 border rounded-3 text-center h-100 shadow-sm">
                                <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 mb-2 fw-bold">PATIENT</h5>
                                <p class="text-muted small mb-0">Register to book appointments and access medical services</p>
                            </div>
                        </a>
                    </div>

                    <!-- DOCTOR CARD -->
                    <div class="col-12">
                        <a href="doctor_create.php" class="text-decoration-none">
                            <div class="register-card p-4 border rounded-3 text-center h-100 shadow-sm">
                                <i class="bi bi-clipboard2-pulse-fill text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 mb-2 fw-bold">DOCTOR</h5>
                                <p class="text-muted small mb-0">Register to manage patients and appointments</p>
                            </div>
                        </a>
                    </div>

                    <!-- STAFF CARD -->
                    <div class="col-12">
                        <a href="staff_create.php" class="text-decoration-none">
                            <div class="register-card p-4 border rounded-3 text-center h-100 shadow-sm">
                                <i class="bi bi-briefcase-fill text-warning" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 mb-2 fw-bold">STAFF</h5>
                                <p class="text-muted small mb-0">Register to assist with clinic operations</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
<?php if ($success_login): ?>
    localStorage.clear();

    const userData = <?= $user_data_json ?>;
    localStorage.setItem('aksyon_user_data', JSON.stringify(userData));
    localStorage.setItem('user_type', userData.user_type);
    localStorage.setItem('user_name', userData.user_name);

    setTimeout(() => {
        window.location.href = '<?= $redirect_url ?>';
    }, 400);
<?php endif; ?>
</script>

</body>
</html>