<?php
// includes/doctor_header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for authentication and user type
if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    // If not logged in as a doctor, redirect to login
    header('Location: login.php');
    exit;
}

// Fetch doctor details from database
require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/classes/Doctor.php';

$database = new Database();
$db = $database->connect();
$doctorObj = new Doctor($db);

$doc_id = $_SESSION['doc_id'];

// Fetch doctor data with specialization
try {
    $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_MIDDLE_INIT, d.DOC_LAST_NAME, 
                   d.DOC_EMAIL, d.DOC_CONTACT_NUM, s.SPEC_NAME
            FROM doctor d
            LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE d.DOC_ID = :doc_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doctorData) {
        // Doctor not found, destroy session and redirect
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Build doctor name with title
    $doctorName = "Dr. " . trim($doctorData['DOC_FIRST_NAME'] . ' ' . $doctorData['DOC_MIDDLE_INIT'] . '. ' . $doctorData['DOC_LAST_NAME']);
    $doctorEmail = $doctorData['DOC_EMAIL'];
    $doctorSpecialization = $doctorData['SPEC_NAME'] ?? 'General';
    
} catch (PDOException $e) {
    error_log("Error fetching doctor data: " . $e->getMessage());
    session_destroy();
    header('Location: login.php');
    exit;
}

// Determine current page for active nav link
$currentPage = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/doctor_style.css">
</head>
<body>

<!-- TOGGLE BUTTON -->
<button class="sidebar-toggle" title="Toggle Menu">
    <i class="bi bi-list"></i>
</button>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo-area">
        <a href="../index.php"><img src="../assets/logo/logo.png" alt="Logo" class="logo-img"></a>
    </div>

    <div class="user-info">
        <div class="user-avatar">
            <i class="bi bi-person-fill"></i>
        </div>
        <div class="flex-grow-1">
            <strong class="d-block text-truncate"><?= htmlspecialchars($doctorName) ?></strong>
            <small class="text-muted"><?= htmlspecialchars($doctorEmail) ?></small>
        </div>
    </div>

    <div class="clock-card">
        <strong>Current Time</strong><br>
        <span id="current-time"><?= date('M d, Y h:i A') ?></span>
    </div>

    <hr class="border-secondary mx-3">

    <ul class="nav flex-column px-2">
        <li>
            <a href="doctor_dashb.php" class="nav-link <?= $currentPage == 'doctor_dashb.php' ? 'active' : '' ?>">
                <i class="bi bi-house"></i> <span>Home</span>
            </a>
        </li>
        <li>
            <a href="doctor_schedule.php" class="nav-link <?= $currentPage == 'doctor_schedule.php' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> <span>Schedule</span>
            </a>
        </li>
        <li>
            <a href="doctor_med_rec.php" class="nav-link <?= $currentPage == 'doctor_med_rec.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-medical"></i> <span>Medical Records</span>
            </a>
        </li>
        <li>
            <a href="doctor_profile.php" class="nav-link <?= $currentPage == 'doctor_profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person"></i> <span>Profile</span>
            </a>
        </li>
        <li class="mt-4">
            <a href="logout.php" class="nav-link text-danger" id="doctorLogoutLink">
                <i class="bi bi-box-arrow-right"></i> <span>Log Out</span>
            </a>
        </li>
    </ul>
</div>
<!-- END SIDEBAR -->

<!-- Main Content Wrapper -->
<div class="main-content">