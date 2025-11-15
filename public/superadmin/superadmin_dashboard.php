<?php
session_start();
require_once '../../config/Database.php';

$database = new Database();
$db = $database->connect();

// Access Control
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
    header("Location: ../../public/login.php");
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
    'staff'          => 'Staff',
    'doctor'         => 'Doctors',
    'patient'        => 'Patients',
    'appointment'    => 'Appointments',
    'medical_record' => 'Medical Records',
    'schedule'       => 'Schedules',
    'service'        => 'Services',
    'specialization' => 'Specializations',
    'payment'       => 'Payment Details',
    'users'           => 'Users'
];

$module = $_GET['module'] ?? null;
$action = $_GET['action'] ?? 'view_all';

function getSummaryCounts($db, $modules) {
    $summary = [];
    foreach ($modules as $table => $label) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) AS count FROM `$table`");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary[] = [
                'table' => $table,
                'name'  => $label,
                'count' => $row['count'] ?? 0
            ];
        } catch (Exception $e) {
            $summary[] = [
                'table' => $table,
                'name'  => $label,
                'count' => 'N/A'
            ];
        }
    }
    return $summary;
}

$summary = getSummaryCounts($db, $summary_modules);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div>
        <div class="brand text-center">
            <img src="../images/logo.png" alt="AKSyon Logo" class="brand-logo mb-2"><br>
            <span>AKSyon Medical Center</span>
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
    <button class="menu-toggle btn btn-lg text-primary" id="menuToggle" aria-label="Toggle Navigation">
        <i class="bi bi-list"></i>
    </button>
    <h5 class="fw-bold mb-0 ms-3 d-none d-md-block">Super Admin Dashboard</h5>
    <div class="header-user-info ms-auto position-relative">
        <span class="d-none d-sm-inline me-2">Welcome, Superadmin!</span>
        <button class="btn p-0 border-0" id="userDropdownToggle">
            <i class="bi bi-person-circle fs-4"></i>
        </button>
        <div class="user-dropdown-menu dropdown-menu" id="userDropdownMenu">
            <a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a>
        </div>
    </div>
</header>

<div class="container-fluid py-4">
<?php if (!$module): ?>
    <h1 class="fw-bold mb-4">Summary Report</h1>

    <div class="row g-3">
        <?php foreach ($summary as $item): ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
            <div class="card text-center shadow-sm card-hover"
                 onclick="window.location='?module=<?= $item['table'] ?>&action=view_all'">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                    <p class="display-6 <?= $item['count'] === 'N/A' ? 'text-danger' : 'text-primary' ?> mb-0">
                        <?= htmlspecialchars($item['count']) ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php
    $file = 'modules/' . $module . '-module.php';
    if (file_exists($file)) {
        include $file;
    } else {
        $label = $all_modules[$module] ?? 'Settings';
        echo "<h2 class='fw-bold mb-4'>" . htmlspecialchars($label) . " Management</h2>";
        echo "<div class='alert alert-warning'>
                <h4>Module File Missing:</h4>
                <p>Please create <code>modules/{$module}-module.php</code></p>
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
</body>
</html>