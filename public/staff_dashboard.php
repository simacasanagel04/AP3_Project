<?php
// staff_dashboard.php
session_start();

// Adjust path to your Database.php if it's located elsewhere
require_once __DIR__ . '/../config/Database.php';

// Optional: if you use a Staff class for user info, require it
require_once __DIR__ . '/../classes/Staff.php';

// Redirect if not logged in (BEFORE including header)
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// === Connect to DB safely ===
try {
    $database = new Database();         
    $db = $database->connect();          
    if (!$db instanceof PDO) {
        throw new Exception('Database connect() did not return a PDO instance.');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    // Friendly error (for debugging - remove message in production)
    echo '<div style="padding:20px; font-family:Arial, sans-serif;">';
    echo '<h3>Unable to connect to the database</h3>';
    echo '<p>Check your DB configuration.</p>';
    echo '<pre style="color:#900;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</div>';
    exit();
}

// === Fetch counts ===
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM doctor");
    $stmt->execute();
    $total_doctors = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM staff");
    $stmt->execute();
    $total_staff = (int)$stmt->fetchColumn();

} catch (Throwable $e) {
    echo '<div style="padding:20px; font-family:Arial, sans-serif;">';
    echo '<h3>Database query failed</h3>';
    echo '<pre style="color:#900;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</div>';
    exit();
}

$quickActions = [
    ['label' => 'Staff',              'icon' => 'person-lines-fill',    'link' => 'staff_manage.php',     'desc' => 'Manage staff accounts'],
    ['label' => 'Specialization',     'icon' => 'star-fill',            'link' => 'staff_specialization_manage.php','desc' => 'Doctor specializations'],
    ['label' => 'Status',             'icon' => 'check2-square',        'link' => 'staff_status.php',        'desc' => 'Appointment statuses'],
    ['label' => 'Service',            'icon' => 'gear-fill',            'link' => 'staff_service.php',      'desc' => 'Manage services'],
    ['label' => 'Medical Records',    'icon' => 'folder2-open',         'link' => 'staff_medical_records.php','desc' => 'Patient records'],
    ['label' => 'Payment',            'icon' => 'credit-card-2-back-fill','link' => 'staff_payment.php',      'desc' => 'Payment records'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Staff Dashboard</title>

  <!-- FAVICON -->
  <link rel="icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
  <link rel="shortcut icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
  <link rel="apple-touch-icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f4f7fb; }
    .dashboard-title { color: #003366; }
    .quick-icon { width: 56px; height: 56px; background: linear-gradient(180deg, rgba(0,51,102,0.08), rgba(0,51,102,0.02)); color: #003366; font-size: 1.6rem; }
    @media (max-width: 767px) { .quick-icon { width: 48px; height: 48px; font-size: 1.25rem; } }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/../includes/staff_header.php'; ?>

  <div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
      <h4 class="dashboard-title mb-0 fw-bold fs-3">Staff Dashboard</h4>
      <div class="mt-2 mt-md-0">
        <span class="me-3 fw-semibold"><?= date('l, F j, Y') ?></span>
        <span id="live-clock" class="fw-bold text-primary"></span>
      </div>
    </div>

    <!-- Top Stats -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6">
        <div class="card shadow text-center p-4 border-0 rounded-4 transition-transform" onmouseover="this.style.transform='translateY(-6px)'" onmouseout="this.style.transform='translateY(0)'">
          <h6 class="text-muted">Total Doctors</h6>
          <h2 class="fw-bold text-primary"><?= htmlspecialchars($total_doctors) ?></h2>
          <small class="text-muted">Active Doctors</small>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card shadow text-center p-4 border-0 rounded-4 transition-transform" onmouseover="this.style.transform='translateY(-6px)'" onmouseout="this.style.transform='translateY(0)'">
          <h6 class="text-muted">Total Staff</h6>
          <h2 class="fw-bold text-success"><?= htmlspecialchars($total_staff) ?></h2>
          <small class="text-muted">Including Admins</small>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Quick Actions (Icon Cards) -->
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-lightning-charge me-2"></i> Quick Actions
          </div>
          <div class="card-body">
            <div class="row g-3">
              <?php foreach ($quickActions as $i => $act): ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <a class="card d-flex flex-row gap-3 align-items-center p-3 shadow-sm text-decoration-none text-dark rounded-3 transition-transform" href="<?= htmlspecialchars($act['link']) ?>" onmouseover="this.style.transform='translateY(-6px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div class="quick-icon d-flex align-items-center justify-content-center rounded-3">
                      <i class="bi bi-<?= htmlspecialchars($act['icon']) ?>"></i>
                    </div>
                    <div>
                      <p class="fw-bold mb-0"><?= htmlspecialchars($act['label']) ?></p>
                      <p class="text-muted small mb-0"><?= htmlspecialchars($act['desc']) ?></p>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <footer class="text-center text-muted mt-4">
      <p class="mt-3 mb-0">© <?= date('Y') ?> AKSyon Medical Center. All Rights Reserved.</p>
    </footer>

  </div>

  <!-- Live Clock Script -->
  <script>
    const clock = document.getElementById('live-clock');
    function updateClock() {
      const now = new Date();
      clock.textContent = now.toLocaleTimeString('en-US', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
      });
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>