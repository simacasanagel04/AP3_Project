<?php
// HEADER.PHP

session_start();
require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/classes/Patient.php';

$db = (new Database())->connect();
$patient = new Patient($db);

$userName = null;
$pat_id = $_SESSION['pat_id'] ?? null;

if ($pat_id) {
    $patientData = $patient->findById($pat_id);
    if ($patientData) {
        $userName = trim($patientData['PAT_FIRST_NAME'] . ' ' . $patientData['PAT_LAST_NAME']);
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

                <a class="navbar-brand" href="../index.php">
                    <img src="assets/logo/logo-no-margn.png" alt="AKSyon Medical Center Logo" height="60">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                            <?php if ($userName): ?>
                                <!-- Logged In: Show Name + Dropdown -->
                                <div class="dropdown">
                                    <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?= htmlspecialchars($userName) ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="patient_dashb.php">
                                                <i class="bi bi-speedometer2 me-2"></i>View Dashboard
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="logout.php">
                                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <!-- Not Logged In: Show Buttons -->
                                <button class="btn btn-outline-primary btn-sm me-2" onclick="location.href='public/login.php'">LOG IN</button>
                                <button class="btn btn-primary btn-sm" onclick="location.href='public/patient_create.php'">REGISTER</button>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
