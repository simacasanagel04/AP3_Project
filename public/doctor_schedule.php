<?php
// public/doctor_schedule.php
session_start();
require_once '../config/Database.php';

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doc_id = $_SESSION['doc_id'];
$db = (new Database())->connect();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Fetch all schedules for this doctor (WEEKDAY-BASED)
try {
    $sql = "SELECT 
                s.SCHED_ID,
                s.SCHED_DAYS,
                s.SCHED_START_TIME,
                s.SCHED_END_TIME,
                s.SCHED_CREATED_AT,
                DATE_FORMAT(s.SCHED_START_TIME, '%h:%i %p') as formatted_start,
                DATE_FORMAT(s.SCHED_END_TIME, '%h:%i %p') as formatted_end
            FROM schedule s
            WHERE s.DOC_ID = :doc_id
            ORDER BY 
                CASE s.SCHED_DAYS
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                    ELSE 7
                END,
                s.SCHED_START_TIME";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each schedule, count appointments that fall on that weekday
    foreach ($schedules as &$schedule) {
        $countSql = "SELECT COUNT(*) as total 
                     FROM appointment 
                     WHERE DOC_ID = :doc_id 
                     AND DAYNAME(APPT_DATE) = :weekday";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute([
            ':doc_id' => $doc_id,
            ':weekday' => $schedule['SCHED_DAYS']
        ]);
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $schedule['total_appointments'] = $countResult['total'];
    }
    unset($schedule);
    
} catch (PDOException $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $schedules = [];
}

// Filter today's schedules - get current weekday name
$todayWeekday = date('l'); // Returns 'Monday', 'Tuesday', etc.
$todaySchedules = array_filter($schedules, function($s) use ($todayWeekday) {
    return $s['SCHED_DAYS'] === $todayWeekday;
});

require_once '../includes/doctor_header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12 col-lg-6 mb-3 mb-lg-0">
            <div class="info-card">
                <h2 class="mb-0"><i class="bi bi-calendar-check"></i> SCHEDULE MANAGEMENT</h2>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="total-card" id="scheduleCountCard">
                <h5 class="text-primary mb-2" id="scheduleCountLabel">
                    <i class="bi bi-calendar-day"></i> Today's Total Schedules (<?= $todayWeekday ?>)
                </h5>
                <h2 class="mb-0" id="scheduleCount"><?= count($todaySchedules) ?></h2>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary tab-btn active" data-target="todaySection" data-count="<?= count($todaySchedules) ?>" data-label="Today's Total Schedules (<?= $todayWeekday ?>)">
                    <i class="bi bi-calendar-day"></i> View Today's Schedule
                </button>
                <button class="btn btn-primary tab-btn" data-target="allSection" data-count="<?= count($schedules) ?>" data-label="Total Schedules">
                    <i class="bi bi-calendar3"></i> View All Schedules
                </button>
                <button class="btn btn-success" id="addNewScheduleBtn">
                    <i class="bi bi-plus-circle"></i> Add New Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-3" id="filterSection">
        <div class="col-12">
            <div class="info-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-calendar-week"></i> Filter by Weekday</strong></label>
                        <select class="form-select" id="filterByWeekday">
                            <option value="">-- All Days --</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-hash"></i> Search Schedule ID</strong></label>
                        <input type="number" class="form-control" id="searchScheduleId" placeholder="Enter schedule ID">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <button class="btn btn-primary w-100" id="applyScheduleFilterBtn">
                            <i class="bi bi-funnel"></i> Apply Filter
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <button class="btn btn-secondary w-100" id="clearScheduleFilterBtn">
                            <i class="bi bi-x-circle"></i> Clear Filter
                        </button>
                    </div>
                </div>
                <!-- Filtered Results Card -->
                <div class="row mt-3" id="scheduleFilteredResultsWrapper" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div>
                                <strong>Filtered Results:</strong> <span id="scheduleFilteredCount">0</span> schedule(s) found
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Schedule Form -->
    <div class="row mb-3" id="addScheduleForm" style="display: none;">
        <div class="col-12">
            <div class="info-card border-success">
                <h4><i class="bi bi-plus-square"></i> Add New Schedule</h4>
                <form id="scheduleForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label"><strong>Select Weekday</strong></label>
                            <select class="form-select" id="newScheduleWeekday" required>
                                <option value="">-- Select Day --</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                            <small class="text-muted">Sunday is closed</small>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label"><strong>Start Time</strong></label>
                            <input type="time" class="form-control" id="newScheduleStartTime" required disabled>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label"><strong>End Time</strong></label>
                            <input type="time" class="form-control" id="newScheduleEndTime" required disabled>
                            <small class="text-muted" id="scheduleTimeRestriction">Select weekday first</small>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle"></i> Add Schedule
                            </button>
                            <button type="button" class="btn btn-secondary w-100 mt-2" id="clearScheduleFormBtn">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TODAY'S SCHEDULE SECTION -->
    <div id="todaySection" class="table-section">
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-calendar-check"></i> Today's Schedules (<?= $todayWeekday ?>, <?= date('F d, Y') ?>)</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="todayScheduleTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Weekday</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($todaySchedules) > 0): ?>
                                    <?php foreach ($todaySchedules as $schedule): ?>
                                        <tr data-sched-id="<?= $schedule['SCHED_ID'] ?>" 
                                            data-weekday="<?= $schedule['SCHED_DAYS'] ?>">
                                            <td><?= htmlspecialchars($schedule['SCHED_ID']) ?></td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($schedule['SCHED_DAYS']) ?></span></td>
                                            <td><?= htmlspecialchars($schedule['formatted_start']) ?></td>
                                            <td><?= htmlspecialchars($schedule['formatted_end']) ?></td>
                                            <td><?= htmlspecialchars($schedule['total_appointments']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm action-btn btn-view-schedule" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>"
                                                    data-weekday="<?= $schedule['SCHED_DAYS'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm action-btn btn-edit-schedule btn-warning" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>"
                                                    data-weekday="<?= htmlspecialchars($schedule['SCHED_DAYS']) ?>"
                                                    data-start="<?= $schedule['SCHED_START_TIME'] ?>"
                                                    data-end="<?= $schedule['SCHED_END_TIME'] ?>">
                                                    <i class="bi bi-pencil"></i> Update
                                                </button>
                                                <button class="btn btn-sm action-btn btn-delete-schedule btn-danger" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No schedules for today (<?= $todayWeekday ?>)</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALL SCHEDULES SECTION -->
    <div id="allSection" class="table-section" style="display:none;">
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-list-ul"></i> All Schedules</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="allScheduleTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Weekday</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($schedules) > 0): ?>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr data-sched-id="<?= $schedule['SCHED_ID'] ?>"
                                            data-weekday="<?= $schedule['SCHED_DAYS'] ?>">
                                            <td><?= htmlspecialchars($schedule['SCHED_ID']) ?></td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($schedule['SCHED_DAYS']) ?></span></td>
                                            <td><?= htmlspecialchars($schedule['formatted_start']) ?></td>
                                            <td><?= htmlspecialchars($schedule['formatted_end']) ?></td>
                                            <td><?= htmlspecialchars($schedule['total_appointments']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm action-btn btn-view-schedule" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>"
                                                    data-weekday="<?= $schedule['SCHED_DAYS'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm action-btn btn-edit-schedule btn-warning" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>"
                                                    data-weekday="<?= htmlspecialchars($schedule['SCHED_DAYS']) ?>"
                                                    data-start="<?= $schedule['SCHED_START_TIME'] ?>"
                                                    data-end="<?= $schedule['SCHED_END_TIME'] ?>">
                                                    <i class="bi bi-pencil"></i> Update
                                                </button>
                                                <button class="btn btn-sm action-btn btn-delete-schedule btn-danger" 
                                                    data-sched-id="<?= $schedule['SCHED_ID'] ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No schedules found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Schedule Modal -->
<div class="modal fade" id="viewScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Appointments for this Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Schedule ID:</strong> <span id="view_modal_sched_id"></span> | 
                    <strong>Weekday:</strong> <span id="view_modal_weekday"></span>
                </div>
                <div id="scheduleAppointmentsList">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Update Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editScheduleForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_sched_id" name="sched_id">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Schedule ID</strong></label>
                        <input type="text" class="form-control" id="edit_sched_id_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Weekday</strong></label>
                        <select class="form-select" id="edit_sched_weekday" required>
                            <option value="">-- Select Day --</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                        <small class="text-muted">Sunday is closed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Start Time</strong></label>
                        <input type="time" class="form-control" id="edit_sched_start_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>End Time</strong></label>
                        <input type="time" class="form-control" id="edit_sched_end_time" required>
                        <small class="text-muted" id="editScheduleTimeRestriction">Saturday: 9:00 AM - 5:00 PM | Mon-Fri: 8:00 AM - 6:00 PM</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/doctor_dashboard.js"></script>

</body>
</html>