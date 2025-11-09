<?php
// public/doctor_dashb.php
session_start();
require_once '../config/Database.php';
require_once '../classes/Doctor.php';
require_once '../classes/Patient.php';

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doc_id = $_SESSION['doc_id'];
$database = new Database();
$db = $database->connect();
$doctorObj = new Doctor($db);
$patientObj = new Patient($db);

// Fetch doctor data
try {
    $sql = "SELECT d.*, s.SPEC_NAME, s.SPEC_ID
            FROM doctor d
            LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE d.DOC_ID = :doc_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $doctorData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching doctor: " . $e->getMessage());
    $doctorData = null;
}

if (!$doctorData) {
    echo "Doctor not found! Session doc_id: " . htmlspecialchars($doc_id);
    echo "<br><a href='login.php'>Back to Login</a>";
    exit();
}

$fullName = trim("{$doctorData['DOC_FIRST_NAME']} {$doctorData['DOC_MIDDLE_INIT']}. {$doctorData['DOC_LAST_NAME']}");
$specialization = $doctorData['SPEC_NAME'] ?? 'General';
$spec_id = $doctorData['SPEC_ID'] ?? null;

// Fetch appointments
try {
    $sql = "SELECT
                a.APPT_ID,
                a.APPT_DATE,
                a.APPT_TIME,
                a.PAT_ID,
                a.STAT_ID,
                a.SERV_ID,
                CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_MIDDLE_INIT, '. ', p.PAT_LAST_NAME) as patient_name,
                s.SERV_NAME as service_name,
                st.STATUS_NAME,
                DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_date,
                DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_time_start,
                DATE_FORMAT(DATE_ADD(a.APPT_TIME, INTERVAL 1 HOUR), '%h:%i %p') as formatted_time_end
            FROM appointment a
            INNER JOIN patient p ON a.PAT_ID = p.PAT_ID
            INNER JOIN service s ON a.SERV_ID = s.SERV_ID
            INNER JOIN status st ON a.STAT_ID = st.STAT_ID
            WHERE a.DOC_ID = :doc_id
            ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
   
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

// Fetch statuses
try {
    $sqlStatus = "SELECT STAT_ID, STATUS_NAME FROM status ORDER BY STAT_ID";
    $stmtStatus = $db->query($sqlStatus);
    $statuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching statuses: " . $e->getMessage());
    $statuses = [];
}

// Fetch services based on doctor's specialization
try {
    $sqlServices = "SELECT SERV_ID, SERV_NAME, SERV_DESCRIPTION, SERV_PRICE 
                    FROM service 
                    ORDER BY SERV_NAME";
    $stmtServices = $db->query($sqlServices);
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching services: " . $e->getMessage());
    $services = [];
}

$today = date('Y-m-d');
$todayAppts = array_filter($appointments, fn($a) => $a['APPT_DATE'] === $today);
$upcomingAppts = array_filter($appointments, fn($a) => $a['APPT_DATE'] > $today);
$historyAppts = array_filter($appointments, fn($a) => $a['APPT_DATE'] < $today);

require_once '../includes/doctor_header.php';
?>

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
        <div class="info-card p-4 text-center" id="dynamicCountCard">
            <h3 class="mb-0" id="appointmentCount"><?= count($todayAppts) ?></h3>
            <p class="mb-0 text-muted" id="appointmentLabel">Today's Appointments</p>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <button class="tab-btn btn btn-outline-light active" data-target="today" data-count="<?= count($todayAppts) ?>" data-label="Today's Appointments">
        View Today's Appointment
    </button>
    <button class="tab-btn btn btn-outline-light" data-target="upcoming" data-count="<?= count($upcomingAppts) ?>" data-label="Upcoming Appointments">
        View Upcoming Appointment
    </button>
    <button class="tab-btn btn btn-outline-light" data-target="history" data-count="<?= count($historyAppts) ?>" data-label="Past Appointments">
        View Appointment History
    </button>
</div>

<!-- FILTER SECTION -->
<div class="info-card mb-4" id="filterSection">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="filterByDate" class="form-label"><i class="bi bi-calendar-date"></i> Filter by Date</label>
            <input type="date" class="form-control" id="filterByDate">
        </div>
        <div class="col-md-3">
            <label for="searchPatientName" class="form-label"><i class="bi bi-person-search"></i> Search Patient Name</label>
            <input type="text" class="form-control" id="searchPatientName" placeholder="Enter patient name">
        </div>
        <div class="col-md-3">
            <label for="searchApptId" class="form-label"><i class="bi bi-search"></i> Search Appointment ID</label>
            <input type="text" class="form-control" id="searchApptId" placeholder="Enter appointment ID">
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-primary w-100" id="applyFilterBtn">
                <i class="bi bi-funnel"></i> Apply Filter
            </button>
            <button type="button" class="btn btn-secondary w-100 mt-2" id="clearFilterBtn">
                <i class="bi bi-x-circle"></i> Clear Filter
            </button>
        </div>
    </div>
    <!-- Filtered Results Card (Hidden by default) -->
    <div class="row mt-3" id="filteredResultsWrapper" style="display: none;">
        <div class="col-md-12">
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>
                    <strong>Filtered Results:</strong> <span id="filteredCount">0</span> appointment(s) found
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TODAY'S APPOINTMENTS -->
<div id="today" class="table-section">
    <div class="info-card">
        <h4 class="mb-3 text-dark">TODAY'S APPOINTMENTS</h4>
        <?php if (count($todayAppts) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Service</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="todayTableBody">
                        <?php foreach ($todayAppts as $a): ?>
                            <tr data-appt-id="<?= $a['APPT_ID'] ?>" 
                                data-date="<?= $a['APPT_DATE'] ?>"
                                data-patient-name="<?= strtolower($a['patient_name']) ?>">
                                <td><?= htmlspecialchars($a['APPT_ID']) ?></td>
                                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                <td><?= htmlspecialchars($a['service_name']) ?></td>
                                <td><?= htmlspecialchars($a['formatted_time_start']) ?> - <?= htmlspecialchars($a['formatted_time_end']) ?></td>
                                <td>
                                    <select class="form-select status-select" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        <?php foreach ($statuses as $status): 
                                            $color = $status['STATUS_NAME'] === 'Completed' ? 'success' : 
                                                    ($status['STATUS_NAME'] === 'Scheduled' ? 'warning' : 'danger');
                                        ?>
                                            <option value="<?= $status['STAT_ID'] ?>" 
                                                <?= ($a['STAT_ID'] == $status['STAT_ID']) ? 'selected' : '' ?>
                                                data-color="<?= $color ?>">
                                                <?= htmlspecialchars($status['STATUS_NAME']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm action-btn btn-view" data-pat-id="<?= $a['PAT_ID'] ?>" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        View
                                    </button>
                                    <button class="btn btn-sm action-btn btn-edit" 
                                        data-appt-id="<?= $a['APPT_ID'] ?>"
                                        data-appt-date="<?= $a['APPT_DATE'] ?>"
                                        data-appt-time="<?= substr($a['APPT_TIME'], 0, 5) ?>"
                                        data-service-id="<?= $a['SERV_ID'] ?>"
                                        data-service="<?= htmlspecialchars($a['service_name']) ?>"
                                        data-status="<?= $a['STAT_ID'] ?>">
                                        Update
                                    </button>
                                    <button class="btn btn-sm action-btn btn-delete btn-danger" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No appointments today.</p>
        <?php endif; ?>
    </div>
</div>

<!-- UPCOMING APPOINTMENTS -->
<div id="upcoming" class="table-section" style="display:none;">
    <div class="info-card">
        <h4 class="mb-3 text-dark">UPCOMING APPOINTMENTS</h4>
        <?php if (count($upcomingAppts) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="upcomingTableBody">
                        <?php foreach ($upcomingAppts as $a): ?>
                            <tr data-appt-id="<?= $a['APPT_ID'] ?>"
                                data-date="<?= $a['APPT_DATE'] ?>"
                                data-patient-name="<?= strtolower($a['patient_name']) ?>">
                                <td><?= htmlspecialchars($a['APPT_ID']) ?></td>
                                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                <td><?= htmlspecialchars($a['service_name']) ?></td>
                                <td><?= htmlspecialchars($a['formatted_date']) ?></td>
                                <td><?= htmlspecialchars($a['formatted_time_start']) ?> - <?= htmlspecialchars($a['formatted_time_end']) ?></td>
                                <td>
                                    <select class="form-select status-select" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        <?php foreach ($statuses as $status): 
                                            $color = $status['STATUS_NAME'] === 'Completed' ? 'success' : 
                                                    ($status['STATUS_NAME'] === 'Scheduled' ? 'warning' : 'danger');
                                        ?>
                                            <option value="<?= $status['STAT_ID'] ?>" 
                                                <?= ($a['STAT_ID'] == $status['STAT_ID']) ? 'selected' : '' ?>
                                                data-color="<?= $color ?>">
                                                <?= htmlspecialchars($status['STATUS_NAME']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm action-btn btn-view" data-pat-id="<?= $a['PAT_ID'] ?>" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        View
                                    </button>
                                    <button class="btn btn-sm action-btn btn-edit" 
                                        data-appt-id="<?= $a['APPT_ID'] ?>"
                                        data-appt-date="<?= $a['APPT_DATE'] ?>"
                                        data-appt-time="<?= substr($a['APPT_TIME'], 0, 5) ?>"
                                        data-service-id="<?= $a['SERV_ID'] ?>"
                                        data-service="<?= htmlspecialchars($a['service_name']) ?>"
                                        data-status="<?= $a['STAT_ID'] ?>">
                                        Update
                                    </button>
                                    <button class="btn btn-sm action-btn btn-delete btn-danger" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No upcoming appointments.</p>
        <?php endif; ?>
    </div>
</div>

<!-- APPOINTMENT HISTORY -->
<div id="history" class="table-section" style="display:none;">
    <div class="info-card">
        <h4 class="mb-3 text-dark">APPOINTMENT HISTORY</h4>
        <?php if (count($historyAppts) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php foreach ($historyAppts as $a): ?>
                            <tr data-appt-id="<?= $a['APPT_ID'] ?>"
                                data-date="<?= $a['APPT_DATE'] ?>"
                                data-patient-name="<?= strtolower($a['patient_name']) ?>">
                                <td><?= htmlspecialchars($a['APPT_ID']) ?></td>
                                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                                <td><?= htmlspecialchars($a['service_name']) ?></td>
                                <td><?= htmlspecialchars($a['formatted_date']) ?></td>
                                <td><?= htmlspecialchars($a['formatted_time_start']) ?> - <?= htmlspecialchars($a['formatted_time_end']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $a['STATUS_NAME'] === 'Completed' ? 'success' : 
                                        ($a['STATUS_NAME'] === 'Scheduled' ? 'warning' : 'danger')
                                    ?>"><?= htmlspecialchars($a['STATUS_NAME']) ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm action-btn btn-view" data-pat-id="<?= $a['PAT_ID'] ?>" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        View
                                    </button>
                                    <button class="btn btn-sm action-btn btn-edit" 
                                        data-appt-id="<?= $a['APPT_ID'] ?>"
                                        data-appt-date="<?= $a['APPT_DATE'] ?>"
                                        data-appt-time="<?= substr($a['APPT_TIME'], 0, 5) ?>"
                                        data-service-id="<?= $a['SERV_ID'] ?>"
                                        data-service="<?= htmlspecialchars($a['service_name']) ?>"
                                        data-status="<?= $a['STAT_ID'] ?>">
                                        Update
                                    </button>
                                    <button class="btn btn-sm action-btn btn-delete btn-danger" data-appt-id="<?= $a['APPT_ID'] ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No past appointments.</p>
        <?php endif; ?>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Patient Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="patientDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editApptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Edit Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editApptForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_appt_id" name="appt_id">
                    <div class="mb-3">
                        <label class="form-label"><strong>Appointment ID</strong></label>
                        <input type="text" class="form-control" id="edit_appt_id_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Date</strong></label>
                        <input type="date" class="form-control" id="edit_appt_date" name="appt_date" required>
                        <small class="text-muted">Working Hours: Mon-Fri (8AM-6PM), Sat (9AM-5PM), Sun (Closed)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Time</strong></label>
                        <input type="time" class="form-control" id="edit_appt_time" name="appt_time" required>
                        <small class="text-muted" id="time_restriction_msg">Please select a date first</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Service</strong></label>
                        <select class="form-select" id="edit_service" name="service_id" required>
                            <option value="">Select Service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= $service['SERV_ID'] ?>">
                                    <?= htmlspecialchars($service['SERV_NAME']) ?> - ₱<?= number_format($service['SERV_PRICE'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Status</strong></label>
                        <select class="form-select" id="edit_status" name="status_id" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['STAT_ID'] ?>">
                                    <?= htmlspecialchars($status['STATUS_NAME']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div><!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/doctor_dashboard.js"></script>

</body>
</html>