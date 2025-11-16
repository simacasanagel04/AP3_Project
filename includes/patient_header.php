<?php
// includes/patient_header.php

session_start();

// Include database and classes
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Patient.php';

// Initialize database
$database = new Database();
$db = $database->connect();

// Initialize Patient class
$patient = new Patient($db);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['pat_id'])) {
    header('Location: ../public/login.php');
    exit();
}

$pat_id = $_SESSION['pat_id'];
$patientData = $patient->findById($pat_id);

if (!$patientData) {
    // Patient not found, logout
    session_destroy();
    header('Location: ../public/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../public/css/patient_style.css">
</head>
<body>

<!-- TOGGLE BUTTON -->
<button class="sidebar-toggle" title="Toggle Menu">
    <i class="bi bi-list"></i>
</button>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo-area">
        <div class="d-flex align-items-center mb-4">
            <a href="../index.php"><img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763156755/logo_jbpnwf.png" alt="Logo" class="logo-img" style="width: 200px; height: auto;"></a>
        </div>
    </div>

    <hr class="border-secondary">

    <div class="user-info">
        <div class="user-avatar">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="user-details">
            <strong class="d-block text-truncate">
                <?= htmlspecialchars($patientData['pat_first_name'] . ' ' . $patientData['pat_last_name']) ?>
            </strong>
            <small class="text-muted text-truncate d-block">
                <?= htmlspecialchars($patientData['pat_email']) ?>
            </small>
        </div>
    </div>

    <div class="clock-card">
        <strong>Current Time</strong><br>
        <span id="current-time"><?= date('M d, Y h:i A') ?></span>
    </div>

    <hr class="border-secondary">

    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="patient_dashb.php" class="nav-link">
                <i class="bi bi-person"></i> <span>My Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="patient_book_appt.php" class="nav-link">
                <i class="bi bi-calendar-plus"></i> <span>Appointments</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="patient_view_patients.php" class="nav-link">
                <i class="bi bi-people"></i><span>View Patients</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="patient_settings.php" class="nav-link">
                <i class="bi bi-gear"></i> <span>Settings</span>
            </a>
        </li>
        <li class="nav-item mt-4">
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> <span>Log Out</span>
            </a>
        </li>
    </ul>
</div>

<script>
// ===============================
// ENHANCED SIDEBAR TOGGLE - MATCHES DOCTOR FUNCTIONALITY
// ===============================
(function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    if (!sidebar || !toggleBtn) return;

    // ===============================
    // SIDEBAR TOGGLE BUTTON CLICK
    // ===============================
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('active');
    });

    // ===============================
    // RESPONSIVE BEHAVIOR - KEY FIX
    // ===============================
    function checkScreen() {
        if (window.innerWidth <= 992) {
            // Mobile/Tablet: Hide sidebar by default, show toggle button
            sidebar.classList.add('hidden');
            sidebar.classList.remove('active'); // Remove active on resize
            if (toggleBtn) toggleBtn.style.display = 'flex';
        } else {
            // Desktop: Show sidebar, hide toggle button
            sidebar.classList.remove('hidden');
            sidebar.classList.remove('active');
            if (toggleBtn) toggleBtn.style.display = 'none';
        }
    }
    
    // Initial check
    checkScreen();
    
    // Re-check on window resize
    window.addEventListener('resize', checkScreen);

    // ===============================
    // CLOSE SIDEBAR WHEN CLICKING OUTSIDE (Mobile)
    // ===============================
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
            const isClickInsideSidebar = sidebar.contains(e.target);
            const isClickOnToggle = toggleBtn.contains(e.target);
            
            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                sidebar.classList.add('hidden');
            }
        }
    });

    // ===============================
    // PREVENT SIDEBAR CLOSE WHEN CLICKING INSIDE
    // ===============================

    sidebar.addEventListener('click', function(e) {
        e.stopPropagation();
    });
})();

// ===============================
// LIVE CLOCK UPDATE
// ===============================

function updateClock() {
    const now = new Date();
    const str = now.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
    document.querySelectorAll('#current-time').forEach(el => el.textContent = str);
}
setInterval(updateClock, 1000);
updateClock();

// ===============================
// ACTIVE NAV LINK
// ===============================

(function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });
})();
</script>

<!-- MAIN CONTENT -->
<div class="main-content">
