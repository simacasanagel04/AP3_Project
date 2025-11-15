<?php
// staff_dashboard.php
session_start();

// Adjust path to your Database.php if it's located elsewhere
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/staff_header.php';

// Optional: if you use a Staff class for user info, require it
// require_once __DIR__ . '/../classes/Staff.php';

// Optional: auth check (uncomment if you want to enforce login)
// if (!isset($_SESSION['STAFF_ID'])) {
//     header('Location: ../public/login.php');
//     exit();
// }

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

// === Fetch counts and recent patients ===
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM doctor");
    $stmt->execute();
    $total_doctors = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM staff");
    $stmt->execute();
    $total_staff = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM patient");
    $stmt->execute();
    $total_patients = (int)$stmt->fetchColumn();

    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) FROM appointment WHERE APPT_DATE = ?");
    $stmt->execute([$today]);
    $appointments_today = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT PAT_FIRST_NAME, PAT_LAST_NAME, PAT_CREATED_AT FROM patient ORDER BY PAT_CREATED_AT DESC LIMIT 5");
    $stmt->execute();
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo '<div style="padding:20px; font-family:Arial, sans-serif;">';
    echo '<h3>Database query failed</h3>';
    echo '<pre style="color:#900;">' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</div>';
    exit();
}

$quickActions = [
    ['label' => 'Staff',              'icon' => 'person-lines-fill',    'link' => '../public/staff_manage.php',     'desc' => 'Manage staff accounts'],
    ['label' => 'Specialization',     'icon' => 'star-fill',            'link' => '../public/staff_specialization_manage.php','desc' => 'Doctor specializations'],
    ['label' => 'Status',             'icon' => 'check2-square',        'link' => '../public/staff_status.php',        'desc' => 'Appointment statuses'],
    ['label' => 'Service',            'icon' => 'gear-fill',            'link' => '../public/staff_service.php',      'desc' => 'Manage services'],
    ['label' => 'Medical Records',    'icon' => 'folder2-open',         'link' => '../public/staff_medical_records.php','desc' => 'Patient records'],
    ['label' => 'Payment',            'icon' => 'credit-card-2-back-fill','link' => '../public/staff_payment.php',      'desc' => 'Payment records'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Staff Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --accent: #003366;
      --muted: #6c757d;
      --card-bg: #ffffff;
      --page-bg: #f4f7fb;
    }
    body { background: var(--page-bg); font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #111827; }
    .dashboard-title { font-size: 1.9rem; font-weight: 800; color: var(--accent); }
    .card-stat { border-radius: 14px; padding: 20px; background: var(--card-bg); border: none; box-shadow: 0 6px 18px rgba(2,6,23,0.06); transition: .18s; }
    .card-stat:hover { transform: translateY(-6px); box-shadow: 0 14px 30px rgba(2,6,23,0.08); }
    .quick-card {
      border-radius: 12px;
      background: var(--card-bg);
      padding: 18px;
      display: flex;
      gap: 12px;
      align-items: center;
      box-shadow: 0 6px 18px rgba(2,6,23,0.04);
      transition: transform .15s, box-shadow .15s;
      text-decoration: none;
      color: inherit;
    }
    .quick-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(2,6,23,0.08); text-decoration: none; }
    .quick-icon {
      width: 56px;
      height: 56px;
      border-radius: 10px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: linear-gradient(180deg, rgba(0,51,102,0.08), rgba(0,51,102,0.02));
      color: var(--accent);
      font-size: 1.6rem;
    }
    .quick-text .title { font-weight:700; margin:0; }
    .quick-text .desc  { font-size: .875rem; color: var(--muted); margin:0; }
    .recent-list li { border-left: 5px solid var(--accent); margin-bottom: 8px; background: #fff; padding: 12px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
    footer { color: var(--muted); }
    @media (max-width: 767px) {
      .quick-icon { width: 48px; height:48px; font-size:1.25rem; }
    }
  </style>
</head>
<body>
  <div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
      <h4 class="dashboard-title mb-0">Staff Dashboard</h4>
      <div class="mt-2 mt-md-0">
        <span class="me-3 fw-semibold"><?= date('l, F j, Y') ?></span>
        <span id="live-clock" class="fw-bold text-primary"></span>
      </div>
    </div>

    <!-- Top Stats -->
    <div class="row g-4 mb-4">
      <div class="col-6 col-md-3">
        <div class="card-stat text-center">
          <h6 class="text-muted">Total Doctors</h6>
          <h2 class="fw-bold text-primary"><?= htmlspecialchars($total_doctors) ?></h2>
          <small class="text-muted">Active Doctors</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat text-center">
          <h6 class="text-muted">Total Staff</h6>
          <h2 class="fw-bold text-success"><?= htmlspecialchars($total_staff) ?></h2>
          <small class="text-muted">Including Admins</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat text-center">
          <h6 class="text-muted">Total Patients</h6>
          <h2 class="fw-bold text-info"><?= htmlspecialchars($total_patients) ?></h2>
          <small class="text-muted">Registered Patients</small>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card-stat text-center">
          <h6 class="text-muted">Appointments Today</h6>
          <h2 class="fw-bold text-danger"><?= htmlspecialchars($appointments_today) ?></h2>
          <small class="text-muted">Pending + Completed</small>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Quick Actions (Icon Cards) - 3 per row -->
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-lightning-charge me-2"></i> Quick Actions
          </div>
          <div class="card-body">
            <div class="row g-3">
              <?php foreach ($quickActions as $i => $act): ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <a class="quick-card" href="<?= htmlspecialchars($act['link']) ?>">
                    <div class="quick-icon">
                      <i class="bi bi-<?= htmlspecialchars($act['icon']) ?>"></i>
                    </div>
                    <div class="quick-text">
                      <p class="title mb-0"><?= htmlspecialchars($act['label']) ?></p>
                      <p class="desc mb-0"><?= htmlspecialchars($act['desc']) ?></p>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Patients -->
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-secondary text-white fw-bold">
            <i class="bi bi-clock-history me-2"></i> Recent Patients
          </div>
          <div class="card-body">
            <?php if (!empty($recent_patients)): ?>
              <ul class="list-group list-group-flush recent-list small">
                <?php foreach ($recent_patients as $row): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($row['PAT_FIRST_NAME'] . ' ' . $row['PAT_LAST_NAME']) ?>
                    <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($row['PAT_CREATED_AT'] ?? 'now'))) ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted mb-0">No recent patient records found.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>

    <footer class="text-center mt-4">
      <p class="mt-3 mb-0">Â© <?= date('Y') ?> AKSyon Medical Center. All Rights Reserved.</p>
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

  <!-- Bootstrap JS (optional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>