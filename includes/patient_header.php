<?php
// includes/patient_header.php

session_start();

// // Check if user is logged in as patient
// if (!isset($_SESSION['pat_id'])) {
//     header("Location: ../public/login.php");
//     exit();
// }

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/classes/Patient.php';

$db = (new Database())->connect();
$patient = new Patient($db);
$pat_id = $_SESSION['pat_id'];

// Fetch patient data
$patientData = $patient->findById($pat_id);
if ($patientData) {
    session_destroy();
    header("Location: ../public/patient_dashb.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | AKSyon Medical Center</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Patient Style -->
    <link rel="stylesheet" href="../public/css/patient_style.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-lg-3 col-md-4 sidebar p-4">
            <div class="text-center mb-4">
                <img src="../assets/logo/logo.png" alt="AKSyon Medical Center" height="60">
                <h5 class="mt-2">
                    <span class="logo-font-aksyon">AKSyon</span><br>
                    <span class="logo-font-medical">Medical Center</span>
                </h5>
            </div>

            <!-- USER INFO -->
            <div class="d-flex align-items-center mb-4 p-3 bg-white rounded shadow-sm">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;">
                    <i class="bi bi-person-fill fs-4"></i>
                </div>
                <div>
                    <strong class="d-block text-truncate" style="max-width: 150px;">
                        <?= htmlspecialchars($patientData['pat_first_name'] . ' ' . $patientData['pat_last_name']) ?>
                    </strong>
                    <small class="text-muted text-truncate d-block" style="max-width: 150px;">
                        <?= htmlspecialchars($patientData['pat_email']) ?>
                    </small>
                </div>
            </div>

            <hr class="border-secondary">

            <!-- NAVIGATION -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="patient_profile.php" class="nav-link">
                        <i class="bi bi-person me-2"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_book_appt.php" class="nav-link">
                        <i class="bi bi-calendar-plus me-2"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_appointment_history.php" class="nav-link">
                        <i class="bi bi-clock-history me-2"></i> History
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>

        <!-- MAIN CONTENT AREA (will be filled by page) -->
        <div class="col-lg-9 col-md-8 p-4">