<?php
// -----------------------------------------------------
// public/staff_medical_records.php
// Staff: VIEW ONLY Medical Records (NO edit/delete/create)
// FIXED: Date display issue & Made fully responsive
// -----------------------------------------------------

session_start();

// Set proper charset for cp850 collation
header('Content-Type: text/html; charset=cp850');

require_once '../config/Database.php';
require_once '../classes/Medical_Record.php';

// Check if logged in BEFORE including header
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$database = new Database();
$db = $database->connect();

// Handle filter parameters
$filters = [
    'med_rec_id' => $_GET['filter_med_rec_id'] ?? '',
    'appt_id' => $_GET['filter_appt_id'] ?? '',
    'diagnosis' => $_GET['filter_diagnosis'] ?? '',
    'visit_date_from' => $_GET['filter_visit_date_from'] ?? '',
    'visit_date_to' => $_GET['filter_visit_date_to'] ?? ''
];

// BULLETPROOF SQL – ONLY CHANGE: Force cp850 collation on the JOIN keys
$sql = "SELECT 
            MR.MED_REC_ID,
            MR.MED_REC_VISIT_DATE,
            MR.MED_REC_DIAGNOSIS,
            MR.MED_REC_PRESCRIPTION,
            MR.MED_REC_CREATED_AT,
            MR.APPT_ID,
            CONCAT(P.PAT_FIRST_NAME, ' ', P.PAT_LAST_NAME) AS PATIENT_NAME,
            CONCAT('Dr. ', D.DOC_FIRST_NAME, ' ', D.DOC_LAST_NAME) AS DOCTOR_NAME
        FROM MEDICAL_RECORD MR
        LEFT JOIN APPOINTMENT A ON MR.APPT_ID COLLATE cp850_general_ci = A.APPT_ID COLLATE cp850_general_ci
        LEFT JOIN PATIENT P ON A.PAT_ID = P.PAT_ID
        LEFT JOIN DOCTOR D ON A.DOC_ID = D.DOC_ID
        WHERE 1=1";

$params = [];

if (!empty($filters['med_rec_id'])) {
    $sql .= " AND MR.MED_REC_ID = :med_rec_id";
    $params[':med_rec_id'] = $filters['med_rec_id'];
}

if (!empty($filters['appt_id'])) {
    $sql .= " AND MR.APPT_ID COLLATE cp850_general_ci = :appt_id";
    $params[':appt_id'] = $filters['appt_id'];
}

if (!empty($filters['diagnosis'])) {
    $sql .= " AND MR.MED_REC_DIAGNOSIS LIKE :diagnosis";
    $params[':diagnosis'] = '%' . $filters['diagnosis'] . '%';
}

if (!empty($filters['visit_date_from'])) {
    $sql .= " AND MR.MED_REC_VISIT_DATE >= :visit_date_from";
    $params[':visit_date_from'] = $filters['visit_date_from'];
}

if (!empty($filters['visit_date_to'])) {
    $sql .= " AND MR.MED_REC_VISIT_DATE <= :visit_date_to";
    $params[':visit_date_to'] = $filters['visit_date_to'];
}

$sql .= " ORDER BY MR.MED_REC_VISIT_DATE DESC, MR.MED_REC_ID DESC";

// Execute query
$stmt = null;
$totalRecords = 0;
$recordsData = [];

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $recordsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalRecords = count($recordsData);
} catch (PDOException $e) {
    error_log("Error fetching medical records: " . $e->getMessage());
}

// Get total count (unfiltered)
$totalCount = 0;
try {
    $countStmt = $db->query("SELECT COUNT(*) as total FROM MEDICAL_RECORD");
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    error_log("Error counting records: " . $e->getMessage());
}

// NOW include header after session check
require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        .card {
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        /* Responsive table styling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Mobile: Stack table for better readability */
        @media (max-width: 768px) {
            .table-responsive {
                border: 0;
            }
            
            .mobile-card-view {
                display: block;
            }
            
            .mobile-card-view .card {
                margin-bottom: 1rem;
                border-left: 4px solid #0d6efd;
            }
            
            .desktop-table-view {
                display: none;
            }
            
            /* Adjust filter form for mobile */
            .filter-buttons-mobile {
                width: 100%;
            }
            
            .filter-buttons-mobile .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-card-view {
                display: none;
            }
            
            .desktop-table-view {
                display: table;
            }
        }
        
        /* Summary cards responsive */
        @media (max-width: 576px) {
            .summary-card-container .col-md-6 {
                margin-bottom: 1rem;
            }
        }
        
        footer {
            background: #e5e2e2;
            color: #333;
            text-align: center;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            margin-top: auto;
        }
        
        /* Improve badge readability */
        .badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        
        /* Mobile: Better spacing */
        @media (max-width: 576px) {
            main.container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <main class="container mt-4 mt-md-5 mb-5">
        <h2 class="text-center text979 text-primary fw-bold mb-4">
            <i class="bi bi-file-medical"></i> Medical Records Management
        </h2>

        <!-- Summary Cards -->
        <div class="row mb-4 summary-card-container">
            <div class="col-md-6">
                <div class="card border-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Records</h6>
                                <h3 class="text-primary mb-0 fw-bold"><?= $totalCount ?></h3>
                            </div>
                            <i class="bi bi-file-earmark-medical text-primary" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Filtered Results</h6>
                                <h3 class="text-info mb-0 fw-bold"><?= $totalRecords ?></h3>
                            </div>
                            <i class="bi bi-funnel text-info" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-funnel-fill"></i> Filter Options
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3">
                        <!-- Medical Record ID Filter -->
                        <div class="col-md-4 col-sm-6">
                            <label for="filter_med_rec_id" class="form-label fw-semibold">
                                <i class="bi bi-hash text-primary"></i> Medical Record ID
                            </label>
                            <input type="number" class="form-control" id="filter_med_rec_id" 
                                name="filter_med_rec_id" 
                                value="<?= htmlspecialchars($filters['med_rec_id']) ?>" 
                                placeholder="Enter Record ID">
                        </div>

                        <!-- Appointment ID Filter -->
                        <div class="col-md-4 col-sm-6">
                            <label for="filter_appt_id" class="form-label fw-semibold">
                                <i class="bi bi-calendar-check text-success"></i> Appointment ID
                            </label>
                            <input type="number" class="form-control" id="filter_appt_id" 
                                name="filter_appt_id" 
                                value="<?= htmlspecialchars($filters['appt_id']) ?>" 
                                placeholder="Enter Appointment ID">
                        </div>

                        <!-- Diagnosis Filter -->
                        <div class="col-md-4 col-sm-12">
                            <label for="filter_diagnosis" class="form-label fw-semibold">
                                <i class="bi bi-clipboard2-pulse text-danger"></i> Diagnosis
                            </label>
                            <input type="text" class="form-control" id="filter_diagnosis" 
                                name="filter_diagnosis" 
                                value="<?= htmlspecialchars($filters['diagnosis']) ?>" 
                                placeholder="Search diagnosis">
                        </div>

                        <!-- Visit Date From Filter -->
                        <div class="col-md-4 col-sm-6">
                            <label for="filter_visit_date_from" class="form-label fw-semibold">
                                <i class="bi bi-calendar-range text-info"></i> Visit Date From
                            </label>
                            <input type="date" class="form-control" id="filter_visit_date_from" 
                                name="filter_visit_date_from" 
                                value="<?= htmlspecialchars($filters['visit_date_from']) ?>">
                        </div>

                        <!-- Visit Date To Filter -->
                        <div class="col-md-4 col-sm-6">
                            <label for="filter_visit_date_to" class="form-label fw-semibold">
                                <i class="bi bi-calendar-range text-info"></i> Visit Date To
                            </label>
                            <input type="date" class="form-control" id="filter_visit_date_to" 
                                name="filter_visit_date_to" 
                                value="<?= htmlspecialchars($filters['visit_date_to']) ?>">
                        </div>

                        <!-- Filter Buttons -->
                        <div class="col-md-4 col-sm-12 d-flex align-items-end gap-2 filter-buttons-mobile">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i> Apply Filters
                            </button>
                            <a href="staff_medical_records.php" class="btn btn-outline-secondary flex-fill">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>

                    <!-- Active Filters Badge -->
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Active Filters: 
                            <span class="badge bg-secondary"><?= count(array_filter($filters)) ?></span>
                            <?php if (count(array_filter($filters)) > 0): ?>
                                <span class="text-primary">
                                    | Showing <?= $totalRecords ?> of <?= $totalCount ?> records
                                </span>
                            <?php endif; ?>
                        </small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Medical Records Table Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="bi bi-table"></i> Patient Medical Record List
                </h5>
            </div>
            <div class="card-body p-0 p-md-3">
                
                <!-- DESKTOP TABLE VIEW -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 desktop-table-view">
                        <thead class="table-light text-uppercase">
                            <tr>
                                <th><i class="bi bi-hash"></i> ID</th>
                                <th><i class="bi bi-person"></i> Patient</th>
                                <th><i class="bi bi-calendar-event"></i> Visit Date</th>
                                <th><i class="bi bi-clipboard2-pulse"></i> Diagnosis</th>
                                <th><i class="bi bi-capsule"></i> Prescription</th>
                                <th><i class="bi bi-calendar-check"></i> Appt ID</th>
                                <th><i class="bi bi-person-badge"></i> Doctor</th>
                                <th><i class="bi bi-clock-history"></i> Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recordsData)): ?>
                                <?php foreach ($recordsData as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($row['MED_REC_ID']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['PATIENT_NAME'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td>
                                            <i class="bi bi-calendar3 text-primary"></i>
                                            <?= date('M d, Y', strtotime($row['MED_REC_VISIT_DATE'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['MED_REC_DIAGNOSIS']) ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($row['MED_REC_PRESCRIPTION']) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['APPT_ID'])): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($row['APPT_ID']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($row['DOCTOR_NAME'] ?? 'N/A') ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($row['MED_REC_CREATED_AT'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">
                                                <?php if (count(array_filter($filters)) > 0): ?>
                                                    No medical records match your filter criteria.
                                                <?php else: ?>
                                                    No medical records found.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- MOBILE CARD VIEW -->
                <div class="mobile-card-view p-3">
                    <?php if (!empty($recordsData)): ?>
                        <?php foreach ($recordsData as $row): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary">ID: <?= htmlspecialchars($row['MED_REC_ID']) ?></span>
                                        <span class="badge bg-success">Appt: <?= htmlspecialchars($row['APPT_ID'] ?? 'N/A') ?></span>
                                    </div>
                                    
                                    <h6 class="card-title text-primary mb-2">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($row['PATIENT_NAME'] ?? 'N/A') ?>
                                    </h6>
                                    
                                    <p class="card-text mb-2">
                                        <i class="bi bi-calendar3 text-info"></i>
                                        <strong>Visit:</strong> <?= date('M d, Y', strtotime($row['MED_REC_VISIT_DATE'])) ?>
                                    </p>
                                    
                                    <p class="card-text mb-2">
                                        <i class="bi bi-clipboard2-pulse text-danger"></i>
                                        <strong>Diagnosis:</strong><br>
                                        <small><?= htmlspecialchars($row['MED_REC_DIAGNOSIS']) ?></small>
                                    </p>
                                    
                                    <p class="card-text mb-2">
                                        <i class="bi bi-capsule text-warning"></i>
                                        <strong>Prescription:</strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['MED_REC_PRESCRIPTION']) ?></small>
                                    </p>
                                    
                                    <p class="card-text mb-2">
                                        <i class="bi bi-person-badge text-success"></i>
                                        <strong>Doctor:</strong> <small><?= htmlspecialchars($row['DOCTOR_NAME'] ?? 'N/A') ?></small>
                                    </p>
                                    
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Created: <?= date('M d, Y', strtotime($row['MED_REC_CREATED_AT'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">
                                <?php if (count(array_filter($filters)) > 0): ?>
                                    No medical records match your filter criteria.
                                <?php else: ?>
                                    No medical records found.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
            <?php if (!empty($recordsData)): ?>
                <div class="card-footer bg-light">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Displaying <?= $totalRecords ?> record(s)
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> 
                            Last updated: <?= date('M d, Y h:i A') ?>
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row align-items-center small">
                <div class="col-md-8 text-center text-md-start mb-2 mb-md-0">
                    <p class="mb-0 text-black">© 2025 AKSyon Medical Center. All rights reserved.</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="https://www.facebook.com/" class="text-black mx-2" target="_blank"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="https://www.instagram.com/" class="text-black mx-2" target="_blank"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="https://www.linkedin.com/" class="text-black mx-2" target="_blank"><i class="bi bi-linkedin fs-5"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>