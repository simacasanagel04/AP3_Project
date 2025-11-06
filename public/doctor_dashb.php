<?php
// public/doctor_dashb.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Doctor.php';

// TEMP FAKE LOGIN
if (!isset($_SESSION['doc_id'])) {
    $_SESSION['doc_id'] = 3;
    $_SESSION['user_type'] = 'doctor';
}

$doc_id = $_SESSION['doc_id'];
$database = new Database();
$db = $database->connect();
$doctorObj = new Doctor($db);

$doctorData = $doctorObj->findById($doc_id) ?: [
    'doc_first_name' => 'Maria',
    'doc_middle_init' => 'L',
    'doc_last_name' => 'Santos',
    'spec_name' => 'Pediatrics'
];

$fullName = trim("{$doctorData['doc_first_name']} {$doctorData['doc_middle_init']}. {$doctorData['doc_last_name']}");
$specialization = $doctorData['spec_name'] ?? 'General';

$appointments = $doctorObj->getDoctorAppointments($doc_id) ?: [];
if (empty($appointments)) {
    $appointments = [
        ['APPT_ID' => '2025-10-000002', 'patient_name' => 'Ana Cruz', 'APPT_REASON' => 'General check-up', 'APPT_DATE' => '2025-10-31', 'APPT_TIME' => '9:00 AM - 9:30 AM', 'STAT_ID' => 1, 'PAT_ID' => 1001],
        ['APPT_ID' => '2025-11-000003', 'patient_name' => 'Juan Dela Peña', 'APPT_REASON' => 'Vaccination', 'APPT_DATE' => '2025-11-02', 'APPT_TIME' => '10:30 AM - 10:40 AM', 'STAT_ID' => 1, 'PAT_ID' => 1002],
        ['APPT_ID' => '2025-09-000004', 'patient_name' => 'Maria Lopez', 'APPT_REASON' => 'Check-up', 'APPT_DATE' => '2025-09-15', 'APPT_TIME' => '1:00 PM - 1:30 PM', 'STAT_ID' => 2, 'PAT_ID' => 1003]
    ];
}

$today = date('Y-m-d');
$todayAppts = array_filter($appointments, fn($a) => ($a['APPT_DATE'] ?? '') === $today);
$upcomingAppts = array_filter($appointments, fn($a) => ($a['APPT_DATE'] ?? '') > $today);
$historyAppts = array_filter($appointments, fn($a) => ($a['APPT_DATE'] ?? '') < $today);

// Include header with sidebar
require_once '../includes/doctor_header.php';
?>

<!-- Dashboard Content -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 text-white">DOCTOR DASHBOARD</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="info-card profile-card text-center p-4">
            <h3 class="mb-1"><?= htmlspecialchars($fullName) ?></h3>
            <p class="mb-0"><?= htmlspecialchars($specialization) ?></p>
        </div>
    </div>
    <div class="col-md-8">
        <div class="info-card p-4 text-center">
            <h3 class="mb-0"><?= count($todayAppts) ?></h3>
            <p class="mb-0 text-muted">Today's Appointments</p>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="tab-btn active" data-target="today">View Today's Appointment</button>
    <button class="tab-btn" data-target="upcoming">View Upcoming Appointment</button>
    <button class="tab-btn" data-target="history">View Appointment History</button>
</div>

<!-- TABLES -->
<div id="today" class="table-section">
    <div class="info-card">
        <h4 class="mb-3 text-dark">TODAY'S APPOINTMENTS</h4>
        <?php if ($todayAppts): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Service</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayAppts as $a): ?>
                    <tr>
                        <td contenteditable="true"><?= $a['APPT_ID'] ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['APPT_REASON']) ?></td>
                        <td contenteditable="true"><?= $a['APPT_TIME'] ?></td>
                        <td contenteditable="true"><span class="badge bg-warning">Scheduled</span></td>
                        <td>
                            <button class="btn btn-sm action-btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#viewModal<?= $a['APPT_ID'] ?>">View</button>
                            <button class="btn btn-sm action-btn btn-outline-light">Update</button>
                        </td>
                    </tr>

                    <!-- VIEW MODAL -->
                    <div class="modal fade" id="viewModal<?= $a['APPT_ID'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Patient Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    // FAKE PATIENT DATA (REPLACE WITH DB FETCH)
                                    $patData = [
                                        'PAT_ID' => $a['PAT_ID'],
                                        'PAT_FIRST_NAME' => 'John',
                                        'PAT_MIDDLE_INIT' => 'D',
                                        'PAT_LAST_NAME' => 'Doe',
                                        'PAT_DOB' => '1990-05-15',
                                        'PAT_GENDER' => 'Male',
                                        'PAT_CONTACT_NUM' => '09123456789',
                                        'PAT_EMAIL' => 'john.doe@example.com',
                                        'PAT_ADDRESS' => 'Manila, Philippines'
                                    ];
                                    $age = floor((time() - strtotime($patData['PAT_DOB'])) / 31556926);
                                    ?>
                                    <p><strong>Appointment ID:</strong> <?= $a['APPT_ID'] ?></p>
                                    <p><strong>Patient Name:</strong> <?= htmlspecialchars("{$patData['PAT_FIRST_NAME']} {$patData['PAT_MIDDLE_INIT']}. {$patData['PAT_LAST_NAME']}") ?></p>
                                    <p><strong>DOB:</strong> <?= $patData['PAT_DOB'] ?></p>
                                    <p><strong>Age:</strong> <?= $age ?> years</p>
                                    <p><strong>Gender:</strong> <?= $patData['PAT_GENDER'] ?></p>
                                    <p><strong>Contact Number:</strong> <?= $patData['PAT_CONTACT_NUM'] ?></p>
                                    <p><strong>Email:</strong> <?= $patData['PAT_EMAIL'] ?></p>
                                    <p><strong>Address:</strong> <?= $patData['PAT_ADDRESS'] ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-center text-white">No appointments today.</p>
        <?php endif; ?>
    </div>
</div>

<div id="upcoming" class="table-section" style="display:none;">
    <div class="info-card">
        <h4 class="mb-3 text-dark">UPCOMING APPOINTMENTS</h4>
        <?php if ($upcomingAppts): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Service</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingAppts as $a): ?>
                    <tr>
                        <td contenteditable="true"><?= $a['APPT_ID'] ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['APPT_REASON']) ?></td>
                        <td contenteditable="true"><?= $a['APPT_TIME'] ?></td>
                        <td contenteditable="true"><span class="badge bg-warning">Scheduled</span></td>
                        <td>
                            <button class="btn btn-sm action-btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#viewModal<?= $a['APPT_ID'] ?>">View</button>
                            <button class="btn btn-sm action-btn btn-outline-light">Update</button>
                        </td>
                    </tr>
                    <div class="modal fade" id="viewModal<?= $a['APPT_ID'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Patient Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $patData = [
                                        'PAT_ID' => $a['PAT_ID'],
                                        'PAT_FIRST_NAME' => 'Jane',
                                        'PAT_MIDDLE_INIT' => 'E',
                                        'PAT_LAST_NAME' => 'Smith',
                                        'PAT_DOB' => '1985-08-22',
                                        'PAT_GENDER' => 'Female',
                                        'PAT_CONTACT_NUM' => '09187654321',
                                        'PAT_EMAIL' => 'jane.smith@example.com',
                                        'PAT_ADDRESS' => 'Quezon City, Philippines'
                                    ];
                                    $age = floor((time() - strtotime($patData['PAT_DOB'])) / 31556926);
                                    ?>
                                    <p><strong>Appointment ID:</strong> <?= $a['APPT_ID'] ?></p>
                                    <p><strong>Patient Name:</strong> <?= htmlspecialchars("{$patData['PAT_FIRST_NAME']} {$patData['PAT_MIDDLE_INIT']}. {$patData['PAT_LAST_NAME']}") ?></p>
                                    <p><strong>DOB:</strong> <?= $patData['PAT_DOB'] ?></p>
                                    <p><strong>Age:</strong> <?= $age ?> years</p>
                                    <p><strong>Gender:</strong> <?= $patData['PAT_GENDER'] ?></p>
                                    <p><strong>Contact Number:</strong> <?= $patData['PAT_CONTACT_NUM'] ?></p>
                                    <p><strong>Email:</strong> <?= $patData['PAT_EMAIL'] ?></p>
                                    <p><strong>Address:</strong> <?= $patData['PAT_ADDRESS'] ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-center text-white">No upcoming appointments.</p>
        <?php endif; ?>
    </div>
</div>

<div id="history" class="table-section" style="display:none;">
    <div class="info-card">
        <h4 class="mb-3 text-dark">APPOINTMENT HISTORY</h4>
        <?php if ($historyAppts): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Service</th>
                        <th>Appointment Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historyAppts as $a): ?>
                    <tr>
                        <td contenteditable="true"><?= $a['APPT_ID'] ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td contenteditable="true"><?= htmlspecialchars($a['APPT_REASON']) ?></td>
                        <td contenteditable="true"><?= $a['APPT_TIME'] ?></td>
                        <td contenteditable="true"><span class="badge bg-success">Completed</span></td>
                        <td>
                            <button class="btn btn-sm action-btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#viewModal<?= $a['APPT_ID'] ?>">View</button>
                            <button class="btn btn-sm action-btn btn-outline-light">Update</button>
                        </td>
                    </tr>
                    <div class="modal fade" id="viewModal<?= $a['APPT_ID'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Patient Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $patData = [
                                        'PAT_ID' => $a['PAT_ID'],
                                        'PAT_FIRST_NAME' => 'Pedro',
                                        'PAT_MIDDLE_INIT' => 'R',
                                        'PAT_LAST_NAME' => 'Garcia',
                                        'PAT_DOB' => '1975-03-10',
                                        'PAT_GENDER' => 'Male',
                                        'PAT_CONTACT_NUM' => '09134567890',
                                        'PAT_EMAIL' => 'pedro.garcia@example.com',
                                        'PAT_ADDRESS' => 'Davao City, Philippines'
                                    ];
                                    $age = floor((time() - strtotime($patData['PAT_DOB'])) / 31556926);
                                    ?>
                                    <p><strong>Appointment ID:</strong> <?= $a['APPT_ID'] ?></p>
                                    <p><strong>Patient Name:</strong> <?= htmlspecialchars("{$patData['PAT_FIRST_NAME']} {$patData['PAT_MIDDLE_INIT']}. {$patData['PAT_LAST_NAME']}") ?></p>
                                    <p><strong>DOB:</strong> <?= $patData['PAT_DOB'] ?></p>
                                    <p><strong>Age:</strong> <?= $age ?> years</p>
                                    <p><strong>Gender:</strong> <?= $patData['PAT_GENDER'] ?></p>
                                    <p><strong>Contact Number:</strong> <?= $patData['PAT_CONTACT_NUM'] ?></p>
                                    <p><strong>Email:</strong> <?= $patData['PAT_EMAIL'] ?></p>
                                    <p><strong>Address:</strong> <?= $patData['PAT_ADDRESS'] ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-center text-white">No past appointments.</p>
        <?php endif; ?>
    </div>
</div>

</div><!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/doctor_dashboard.js"></script>
</body>
</html>