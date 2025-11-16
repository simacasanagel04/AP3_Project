<?php
// includes/header.php
// for index.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/classes/Patient.php';
require_once dirname(__DIR__) . '/classes/Doctor.php';
require_once dirname(__DIR__) . '/classes/Staff.php';

$db = (new Database())->connect();
$patient = new Patient($db);
$doctor = new Doctor($db);
$staff = new Staff($db);

$userName = null;
$user_type = $_SESSION['user_type'] ?? null;
$dashboardLink = '#';
$isLoggedIn = false;

if ($user_type) {
    switch ($user_type) {
        case 'super_admin':
            $staff_id = $_SESSION['staff_id'] ?? null;
            if ($staff_id) {
                $staffData = $staff->getStaffById($staff_id);
                if ($staffData) {
                    $userName = trim($staffData['STAFF_FIRST_NAME'] . ' ' . $staffData['STAFF_LAST_NAME']) . ' (Admin)';
                } else {
                    $userName = 'Super Administrator';
                }
            } else {
                $userName = 'Super Administrator';
            }
            $dashboardLink = 'public/superadmin/superadmin_dashboard.php';
            $isLoggedIn = true;
            break;
            
        case 'patient':
            $pat_id = $_SESSION['pat_id'] ?? null;
            if ($pat_id) {
                $patientData = $patient->findById($pat_id);
                if ($patientData) {
                    $userName = trim($patientData['pat_first_name'] . ' ' . $patientData['pat_last_name']);
                    $dashboardLink = 'public/patient_dashb.php';
                    $isLoggedIn = true;
                }
            }
            break;
            
        case 'doctor':
            $doc_id = $_SESSION['doc_id'] ?? null;
            if ($doc_id) {
                $doctorData = $doctor->findById($doc_id);
                if ($doctorData) {
                    $userName = "Dr. " . trim($doctorData['doc_first_name'] . ' ' . $doctorData['doc_last_name']);
                    $dashboardLink = 'public/doctor_dashb.php';
                    $isLoggedIn = true;
                }
            }
            break;
            
        case 'staff':
            $staff_id = $_SESSION['staff_id'] ?? null;
            if ($staff_id) {
                $staffData = $staff->getStaffById($staff_id);
                if ($staffData) {
                    $userName = trim($staffData['STAFF_FIRST_NAME'] . ' ' . $staffData['STAFF_LAST_NAME']);
                    $dashboardLink = 'public/staff_dashboard.php';
                    $isLoggedIn = true;
                }
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKSyon Medical Center - Because Health Needs More Than Words, It Needs Action</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/css/style.css">
</head>

<body>
    <header class="main-header">
        <nav class="navbar navbar-expand-lg shadow-sm" style="background-color: #e5e2e2;">
            <div class="container">

                <a class="navbar-brand" href="index.php">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763156755/logo-no-margn_ovy6na.png" alt="AKSyon Medical Center Logo" height="60">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">HOME</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#about">ABOUT US</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#services">SERVICES</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#contact">CONTACT</a>
                        </li>

                        <li class="nav-item ms-lg-3" id="authSection">
                            <?php if ($isLoggedIn && $userName): ?>
                                <!-- Logged In: Show Name + Dropdown -->
                                <div class="dropdown">
                                    <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= htmlspecialchars($userName) ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="<?= $dashboardLink ?>">
                                                <i class="bi bi-speedometer2 me-2"></i>View Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="public/logout.php">
                                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <!-- Not Logged In: Show Buttons -->
                                <button class="btn btn-outline-primary btn-sm me-2"
                                    onclick="location.href='public/login.php'">LOG IN</button>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">REGISTER</button>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- REGISTER MODAL -->
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
                            <a href="public/patient_create.php" class="text-decoration-none">
                                <div class="register-card p-4 border rounded-3 text-center h-100 shadow-sm">
                                    <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-2 fw-bold">PATIENT</h5>
                                    <p class="text-muted small mb-0">Register to book appointments and access medical services</p>
                                </div>
                            </a>
                        </div>

                        <!-- DOCTOR CARD -->
                        <div class="col-12">
                            <a href="public/doctor_create.php" class="text-decoration-none">
                                <div class="register-card p-4 border rounded-3 text-center h-100 shadow-sm">
                                    <i class="bi bi-clipboard2-pulse-fill text-success" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-2 fw-bold">DOCTOR</h5>
                                    <p class="text-muted small mb-0">Register to manage patients and appointments</p>
                                </div>
                            </a>
                        </div>

                        <!-- STAFF CARD -->
                        <div class="col-12">
                            <a href="public/staff_create.php" class="text-decoration-none">
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

    <!-- Store user data in localStorage for JavaScript access -->
    <?php if ($isLoggedIn && $userName): ?>
        <script>
        // Store user data in localStorage when logged in
        const userData = {
            user_name: <?= json_encode($userName) ?>,
            is_logged_in: 'true',
            user_type: <?= json_encode($user_type) ?>,
            dashboard_link: <?= json_encode($dashboardLink) ?>,
            is_superadmin: <?= $user_type === 'super_admin' ? 'true' : 'false' ?>
        };
        localStorage.setItem('aksyon_user_data', JSON.stringify(userData));
        localStorage.setItem('user_name', userData.user_name);
        localStorage.setItem('is_logged_in', 'true');
        localStorage.setItem('user_type', userData.user_type);
        <?php if ($user_type === 'super_admin'): ?>
        localStorage.setItem('is_superadmin', 'true');
        <?php endif; ?>
        console.log('User session active:', userData);
    </script>
    <?php else: ?>
    <script>
        // Clear localStorage if not logged in
        localStorage.removeItem('aksyon_user_data');
        localStorage.removeItem('user_name');
        localStorage.removeItem('is_logged_in');
        localStorage.removeItem('user_type');
        localStorage.removeItem('is_superadmin');
        console.log('No active user session');
    </script>
    <?php endif; ?>