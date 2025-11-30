<?php
// public/superadmin/superadmin_dashboard.php

session_start();
require_once '../../config/Database.php';

$database = new Database();
$db = $database->connect();
if (!$db) {
    die("Critical Error: Database connection failed in dashboard.");
}

// Access Control
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}

// === ALL MODULES (for sidebar) ===
$all_modules = [
    'staff'          => 'Staff',
    'doctor'         => 'Doctors',
    'patient'        => 'Patients',
    'appointment'    => 'Appointments',
    'medical-record' => 'Medical Records',
    'schedule'       => 'Schedules',
    'service'        => 'Services',
    'specialization' => 'Specializations',
    'status'         => 'Status Settings',
    'payments'       => 'Payment Details',
    'payment-method' => 'Payment Method Configurations',
    'payment-status' => 'Payment Status Configurations',
    'user'           => 'Users'
];

// === MODULES TO SHOW IN SUMMARY CARDS ONLY ===
$summary_modules = [
    ['module' => 'staff', 'table' => 'staff', 'name' => 'Staff'],
    ['module' => 'doctor', 'table' => 'doctor', 'name' => 'Doctors'],
    ['module' => 'patient', 'table' => 'patient', 'name' => 'Patients'],
    ['module' => 'appointment', 'table' => 'appointment', 'name' => 'Appointments'],
    ['module' => 'medical-record', 'table' => 'medical_record', 'name' => 'Medical Records'],
    ['module' => 'service', 'table' => 'service', 'name' => 'Services'],
    ['module' => 'specialization', 'table' => 'specialization', 'name' => 'Specializations'],
    ['module' => 'user', 'table' => 'users', 'name' => 'Users']
];

$module = $_GET['module'] ?? null;
$action = $_GET['action'] ?? 'view_all';

function getSummaryCounts($db, $modules) {
    $summary = [];
    foreach ($modules as $item) {
        try {
            $table = $item['table'];
            $stmt = $db->prepare("SELECT COUNT(*) AS count FROM `$table`");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary[] = [
                'module' => $item['module'],
                'table'  => $table,
                'name'   => $item['name'],
                'count'  => $row['count'] ?? 0
            ];
        } catch (Exception $e) {
            $summary[] = [
                'module' => $item['module'],
                'table'  => $item['table'],
                'name'   => $item['name'],
                'count'  => 'N/A'
            ];
        }
    }
    return $summary;
}

function getDailyStats($db) {
    $today = date('Y-m-d');
    $stats = [];
    
    // New Patients Today
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM patient WHERE DATE(pat_created_at) = :today");
        $stmt->execute([':today' => $today]);
        $stats['new_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $stats['new_patients'] = 0;
    }
    
    // Completed Appointments Today
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointment 
                              WHERE DATE(APPT_DATE) = :today AND STAT_ID = (SELECT STAT_ID FROM status WHERE STAT_NAME = 'Completed')");
        $stmt->execute([':today' => $today]);
        $stats['completed_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $stats['completed_appointments'] = 0;
    }
    
    return $stats;
}

function getChartData($db, $type = 'appointments') {
    $data = ['labels' => [], 'values' => []];
    
    // Get last 30 days data
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $data['labels'][] = date('M d', strtotime($date));
        
        try {
            if ($type === 'appointments') {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointment WHERE DATE(APPT_DATE) = :date");
            } else { // patients
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM patient WHERE DATE(pat_created_at) = :date");
            }
            $stmt->execute([':date' => $date]);
            $data['values'][] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            $data['values'][] = 0;
        }
    }
    
    return $data;
}

function getPendingTasks($db) {
    $tasks = [];
    
    // Pending Appointments (Today's appointments not yet completed)
    try {
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM appointment 
                              WHERE DATE(appt_date) = :today 
                              AND stat_id != (SELECT stat_id FROM status WHERE stat_name = 'Completed' LIMIT 1)
                              AND stat_id != (SELECT stat_id FROM status WHERE stat_name = 'Cancelled' LIMIT 1)");
        $stmt->execute([':today' => $today]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count > 0) {
            $tasks[] = ['text' => "$count pending appointment(s) today", 'link' => '?module=appointment'];
        }
    } catch (Exception $e) {}
    
    // Doctor applications/new doctors without schedules
    try {
        $stmt = $db->query("SELECT COUNT(DISTINCT d.DOC_ID) as count 
                           FROM doctor d 
                           LEFT JOIN schedule s ON d.DOC_ID = s.DOC_ID 
                           WHERE s.SCHED_ID IS NULL");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count > 0) {
            $tasks[] = ['text' => "$count doctor(s) without schedules", 'link' => '?module=schedule'];
        }
    } catch (Exception $e) {}
    
    // Unprocessed payment records
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM payment 
                           WHERE pymt_stat_id = (SELECT pymt_stat_id FROM payment_status WHERE pymt_stat_name = 'Pending' LIMIT 1)");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($count > 0) {
            $tasks[] = ['text' => "$count pending payment(s)", 'link' => '?module=payments'];
        }
    } catch (Exception $e) {}
    
    return $tasks;
}

function getUpcomingMaintenance() {
    // Static example - you can make this dynamic with a database table
    return [
        'date' => 'Dec, 19',
        'description' => 'Upcoming 3 System Maintenance'
    ];
}

$summary = getSummaryCounts($db, $summary_modules);
$dailyStats = getDailyStats($db);
$appointmentsChart = getChartData($db, 'appointments');
$patientsChart = getChartData($db, 'patients');
$pendingTasks = getPendingTasks($db);
$maintenance = getUpcomingMaintenance();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>

<!-- FAVICON -->
<link rel="icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
<link rel="shortcut icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
<link rel="apple-touch-icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="../css/admin-style.css">

<!-- RESPONSIVE FIXES ONLY (added) -->
<style>
    /* Ensure header is flexible and user icon is always visible */
    .admin-app-header {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        min-height: 60px;
        padding: 0.5rem 1rem;
    }

    /* Make sure user dropdown works properly */
    .header-user-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
    }

    .header-user-info .btn {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        padding: 0 !important;
    }

    /* Always show user icon and make it tappable */
    #userDropdownToggle {
        background: transparent;
        border: none;
        font-size: 1.75rem;
        color: #0d6efd;
    }

    /* Dropdown menu positioning fix for mobile */
    @media (max-width: 576px) {
        .header-user-info .dropdown-menu {
            right: 0;
            left: auto !important;
            transform: translateX(0) !important;
            min-width: 160px;
        }
        .d-none.d-sm-inline {
            display: none !important;
        }
    }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div>
        <div class="brand text-center">
            <a href="../../index.php"><img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763156755/logo_jbpnwf.png" alt="AKSyon Logo" class="brand-logo mb-2" style="height: 80px;"></a><br>
        </div>

        <div class="p-3">
            <nav class="nav flex-column">
                <a class="nav-link <?= !$module ? 'active' : '' ?>" href="?">Dashboard</a>
                <?php foreach ($all_modules as $key => $label): ?>
                    <a class="nav-link <?= $module === $key ? 'active' : '' ?>" href="?module=<?= $key ?>&action=view_all">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</div>

<div class="main-content">
<header class="admin-app-header">
    <div class="d-flex align-items-center">
        <button class="menu-toggle btn btn-lg text-primary" id="menuToggle" aria-label="Toggle Navigation">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="fw-bold mb-0 ms-3 d-none d-md-block">Super Admin Dashboard</h5>
    </div>

    <div class="header-user-info position-relative">
        <span class="d-none d-sm-inline me-2 text-muted">Welcome, Superadmin!</span>
        <button class="btn p-0 border-0" id="userDropdownToggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenu">
            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
        </ul>
    </div>
</header>

<div class="container-fluid py-4">
<?php if (!$module): ?>
    <h1 class="fw-bold mb-4">Summary Report</h1>

    <div class="row g-4">
        <!-- Left Column: Summary Cards and Charts -->
        <div class="col-lg-9">
            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <?php foreach ($summary as $item): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card text-center shadow-sm card-hover h-100"
                         onclick="window.location='?module=<?= htmlspecialchars($item['module']) ?>&action=view_all'">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-2"><?= htmlspecialchars($item['name']) ?></h6>
                            <p class="display-6 <?= $item['count'] === 'N/A' ? 'text-danger' : 'text-primary' ?> mb-0">
                                <?= htmlspecialchars($item['count']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Charts Row -->
            <div class="row g-3 mb-4">
                <!-- Appointments Chart -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                Appointments
                            </h5>
                            <canvas id="appointmentsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Patients Chart -->
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                Patients
                            </h5>
                            <canvas id="patientsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Tasks -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        PENDING TASKS
                    </h5>
                    <?php if (empty($pendingTasks)): ?>
                        <p class="text-muted mb-0">No pending tasks. Great job!</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pendingTasks as $task): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($task['text']) ?></span>
                                <a href="<?= htmlspecialchars($task['link']) ?>" class="btn btn-sm btn-primary">Review</a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Stats and Actions -->
        <div class="col-lg-3">
            <!-- Daily Stats -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        DAILY STATS
                    </h5>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">
                                New Patients Today
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.location='?module=patient'">
                                View
                            </button>
                        </div>
                        <h4 class="text-primary mb-0"><?= $dailyStats['new_patients'] ?></h4>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">
                                Completed Appointments
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.location='?module=appointment'">
                                View
                            </button>
                        </div>
                        <h4 class="text-success mb-0"><?= $dailyStats['completed_appointments'] ?></h4>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        QUICK ACTIONS
                    </h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="window.location='?module=user&action=add'">
                            Add New User
                        </button>
                        <button class="btn btn-outline-primary" onclick="window.location='?module=appointment'">
                            Create Appointment
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.location='?module=medical-record'">
                            Add New Medical Record
                        </button>
                    </div>
                </div>
            </div>

            <!-- Support -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        REMINDERS
                    </h5>
                    <div class="alert alert-info small mb-0">
                        <strong><?= $maintenance['date'] ?>:</strong> <?= $maintenance['description'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php
    $file = __DIR__ . '/modules/' . $module . '-module.php';
    if (file_exists($file)) {
        require $file;
    } else {
        $label = $all_modules[$module] ?? 'Settings';
        echo "<h2 class='fw-bold mb-4'>" . htmlspecialchars($label) . " Management</h2>";
        echo "<div class='alert alert-warning'>
                <h4>Module File Missing:</h4>
                <p>Please create <code>modules/{$module}-module.php</code></p>
                <p class='mb-0'>Checked path: <code>{$file}</code></p>
            </div>";
    }
    ?>  
<?php endif; ?>
</div>
</div>

<footer class="admin-app-footer">
    <p class="mb-0 small text-muted text-center">© 2025 AKSyon Medical Center. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>

<?php if (!$module): ?>
<!-- Pass data to JavaScript -->
<script>
    const dashboardChartData = {
        appointmentsData: <?= json_encode($appointmentsChart) ?>,
        patientsData: <?= json_encode($patientsChart) ?>
    };
</script>
<script src="../js/dashboard_chart.js"></script>
<?php endif; ?>

</body>
</html>