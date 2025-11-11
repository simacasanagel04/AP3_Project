<?php 
// public/patient_dashb.php

include '../includes/patient_header.php'; 

// Get filter parameters
$filter_id = isset($_GET['filter_id']) ? trim($_GET['filter_id']) : '';
$filter_date = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : '';

// Fetch all appointments for this patient
$appointments = $patient->getPatientAppointments($pat_id);

// Apply filters
$filtered_appointments = $appointments;

if (!empty($filter_id)) {
    $filtered_appointments = array_filter($filtered_appointments, function($appt) use ($filter_id) {
        return stripos($appt['app_id'], $filter_id) !== false;
    });
}

if (!empty($filter_date)) {
    $filtered_appointments = array_filter($filtered_appointments, function($appt) use ($filter_date) {
        return $appt['app_date'] === $filter_date;
    });
}

// Separate appointments
$today = date('Y-m-d');
$now = new DateTime();

$today_appointments = array_filter($filtered_appointments, function($appt) use ($today) {
    return $appt['app_date'] === $today && $appt['app_status'] != 3; // Exclude cancelled
});

$upcoming_appointments = array_filter($filtered_appointments, function($appt) use ($today) {
    return $appt['app_date'] > $today && $appt['app_status'] == 1; // Only scheduled
});

// Count totals
$total_today = count($today_appointments);
$total_upcoming = count($upcoming_appointments);
$total_appointments = count($appointments);
?>

<!-- DASHBOARD HEADER -->
<div class="dashboard-header mb-4">
    <h1 class="display-5 fw-bold text-primary">DASHBOARD</h1>
    <h2 class="mb-0">Welcome, 
        <?php 
        echo isset($patientData['pat_first_name']) 
            ? htmlspecialchars($patientData['pat_first_name']) 
            : 'Patient'; 
        ?>!
    </h2>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_appointments ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_today ?></h3>
                <p>Today's Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="bi bi-calendar-plus"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_upcoming ?></h3>
                <p>Upcoming Appointments</p>
            </div>
        </div>
    </div>
</div>

<!-- FILTER CARD -->
<div class="info-card mb-4">
    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Appointments</h5>
    <form method="GET" action="" id="filterForm">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search by Appointment ID</label>
                <input type="text" name="filter_id" class="form-control" placeholder="Enter Appointment ID" value="<?= htmlspecialchars($filter_id) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Date</label>
                <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="patient_dashb.php" class="btn btn-secondary flex-fill">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
            </div>
        </div>
        <?php if (!empty($filter_id) || !empty($filter_date)): ?>
        <div class="mt-3">
            <span class="badge bg-info">
                <i class="bi bi-info-circle me-1"></i>
                Showing <?= count($filtered_appointments) ?> filtered result(s)
            </span>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- VIEW TABS -->
<div class="d-flex gap-2 mb-4">
    <button class="tab-btn active" id="todayBtn">
        <i class="bi bi-calendar-day me-2"></i>View Today's Appointments
    </button>
    <button class="tab-btn" id="upcomingBtn">
        <i class="bi bi-calendar-week me-2"></i>View Upcoming Appointments
    </button>
    <button class="tab-btn" onclick="location.href='patient_book_appt.php'">
        <i class="bi bi-plus-circle me-2"></i>Book New Appointment
    </button>
</div>

<!-- TODAY'S APPOINTMENTS TABLE -->
<div class="info-card appointment-section" id="todaySection">
    <h4 class="mb-3">
        <i class="bi bi-calendar-day me-2 text-warning"></i>TODAY'S APPOINTMENTS
    </h4>
    <?php if (!empty($today_appointments)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Appointment ID</th>
                        <th>Doctor</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_appointments as $appt): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($appt['app_id']) ?></strong></td>
                        <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_date']) ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_time']) ?></td>
                        <td>
                            <?php 
                            $status = $appt['app_status'];
                            if ($status == 1) {
                                echo '<span class="badge bg-warning text-dark">Scheduled</span>';
                            } elseif ($status == 2) {
                                echo '<span class="badge bg-success">Completed</span>';
                            } else {
                                echo '<span class="badge bg-danger">Cancelled</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="viewAppointment(<?= htmlspecialchars(json_encode($appt)) ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <?php if ($status == 1): ?>
                            <button class="btn btn-sm btn-warning me-1" onclick="updateAppointment(<?= $appt['app_id'] ?>, <?= htmlspecialchars(json_encode($appt)) ?>)">
                                <i class="bi bi-pencil"></i> Update
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $appt['app_id'] ?>)">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-1 text-muted"></i>
            <p class="text-muted mt-3">No appointments scheduled for today.</p>
        </div>
    <?php endif; ?>
</div>

<!-- UPCOMING APPOINTMENTS TABLE -->
<div class="info-card appointment-section" id="upcomingSection" style="display: none;">
    <h4 class="mb-3">
        <i class="bi bi-calendar-week me-2 text-success"></i>UPCOMING APPOINTMENTS
    </h4>
    <?php if (!empty($upcoming_appointments)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Appointment ID</th>
                        <th>Doctor</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_appointments as $appt): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($appt['app_id']) ?></strong></td>
                        <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_date']) ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_time']) ?></td>
                        <td>
                            <?php 
                            $status = $appt['app_status'];
                            if ($status == 1) {
                                echo '<span class="badge bg-warning text-dark">Scheduled</span>';
                            } elseif ($status == 2) {
                                echo '<span class="badge bg-success">Completed</span>';
                            } else {
                                echo '<span class="badge bg-danger">Cancelled</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info me-1" onclick="viewAppointment(<?= htmlspecialchars(json_encode($appt)) ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <?php if ($status == 1): ?>
                            <button class="btn btn-sm btn-warning me-1" onclick="updateAppointment(<?= $appt['app_id'] ?>, <?= htmlspecialchars(json_encode($appt)) ?>)">
                                <i class="bi bi-pencil"></i> Update
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?= $appt['app_id'] ?>)">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-1 text-muted"></i>
            <p class="text-muted mt-3">No upcoming appointments scheduled.</p>
        </div>
    <?php endif; ?>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Appointment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be inserted by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- UPDATE MODAL -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Update Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateForm">
                <div class="modal-body">
                    <input type="hidden" id="update_app_id" name="app_id">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> You can only reschedule to dates when doctors with the same specialization are available.
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" id="update_date" name="app_date" required>
                            <small class="text-muted" id="update_date_note">Available dates will be loaded</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Appointment Time</label>
                            <select class="form-select" id="update_time" name="app_time" required>
                                <option value="">-- Select Date First --</option>
                            </select>
                            <small class="text-muted">30-minute time slots</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="update_status" name="app_status" required>
                                <option value="1">Scheduled</option>
                                <option value="2">Completed</option>
                                <option value="3">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/patient_dashboard.js"></script>
</body>
</html>