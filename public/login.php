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

                    $_SESSION['user_type'] = 'super_admin';
                    $_SESSION['is_superadmin'] = true;

                    $userName = 'Super Administrator';

                    if (!empty($userData['STAFF_ID'])) {
                        $_SESSION['staff_id'] = $userData['STAFF_ID'];
                        $staffData = $staff->getStaffById($userData['STAFF_ID']);
                        if ($staffData) {
                            $userName = trim($staffData['STAFF_FIRST_NAME'] . ' ' . $staffData['STAFF_LAST_NAME']) . ' (Admin)';
                            $localStorageData['staff_id'] = $userData['STAFF_ID'];
                        }
                    }

                    $localStorageData['user_type'] = 'super_admin';
                    $localStorageData['is_superadmin'] = true;
                    $localStorageData['user_name'] = $userName;

                    // FIXED PATH BELOW:
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <a href="../index.php"><img src="../assets/logo/logo_white_bg.png" alt="AKSyon Medical Center" height="80" class="mb-3"></a>
                        <h5 class="mt-3" style="font-family: 'Times New Roman', serif;">Welcome Back</h5>
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

                    <form method="POST" action="" id="loginForm">
                        <div class="mb-3 text-start">
                            <label class="form-label"><strong>Email:</strong></label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Enter email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label"><strong>Password:</strong></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="background-color: #336d96; border: none;">
                            LOGIN
                        </button>
                    </form>

                    <div class="mt-4">
                        <small class="text-muted">
                            Don't have an account? <a href="patient_create.php" class="text-primary text-decoration-none">Register</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if ($success_login): ?>
        // Clear any old data first
        localStorage.clear();
        sessionStorage.clear();
        
        // Store new user data in localStorage
        const userData = <?= $user_data_json ?>;
        localStorage.setItem('aksyon_user_data', JSON.stringify(userData));
        localStorage.setItem('user_name', userData.user_name || '');
        localStorage.setItem('is_logged_in', 'true');
        localStorage.setItem('user_type', userData.user_type || '');
        
        // Store superadmin flag if applicable
        if (userData.is_superadmin) {
            localStorage.setItem('is_superadmin', 'true');
        }
        
        console.log('User data stored in localStorage:', userData);
        console.log('Redirecting to:', '<?= $redirect_url ?>');
        
        // Redirect after storing
        setTimeout(function() {
            window.location.href = '<?= $redirect_url ?>';
        }, 500);
    <?php endif; ?>
</script>
</body>
</html>