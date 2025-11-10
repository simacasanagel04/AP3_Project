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
            <a href="../index.php"><img src="../assets/logo/logo.png" alt="Logo" class="logo-img" style="width: 200px; height: auto;"></a>
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

<!-- MAIN CONTENT -->
<div class="main-content">