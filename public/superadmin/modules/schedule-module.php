<?php
/**
 * SCHEDULE MANAGEMENT MODULE (schedule-module.php)
 * This file handles all CRUD operations for the Schedule entity
 * and includes a view for doctors with today's schedule.
 * Designed to be included within the superadmin_dashboard.php content area.
 * NOTE: Requires 'database.php' and 'Schedule.php' class file to be available.
 */

// Ensure dependencies are loaded
require_once dirname(__DIR__, 3) . '/classes/Schedule.php';
// NOTE: $db is assumed to be available from superadmin_dashboard.php scope.

$schedule = new Schedule($db);

$message = '';
$action = $_GET['action'] ?? 'view_all';
$current_datetime = date('Y-m-d H:i:s');
$user_type = $_SESSION['user_type'] ?? '';

// Helper function
function formatDate($timestamp) {
    if (!$timestamp) {
        return '—';
    }
    return date('F j, Y h:i A', strtotime($timestamp));
}

// Access Control
if ($user_type !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// Fetch all doctors for dropdown
$doctors = [];
try {
    $doctors = $schedule->getAllDoctors();
} catch (Exception $e) {
    $message = "❌ Error fetching doctors: " . $e->getMessage();
}

// Fetch doctors with today's schedule
$todaysDoctors = [];
try {
    $todaysDoctors = $schedule->getDoctorsWithTodaySchedule();
} catch (Exception $e) {
    $message = "❌ Error fetching today's doctors: " . $e->getMessage();
}

// CRUD Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle ADD Schedule
    if (isset($_POST['add'])) {
        $doc_id = filter_var($_POST['doc_id'], FILTER_VALIDATE_INT);
        $sched_days = trim($_POST['sched_days']);
        $start_time = trim($_POST['start_time']);
        $end_time = trim($_POST['end_time']);

        if ($doc_id && $sched_days && $start_time && $end_time) {
            try {
                $data = [
                    'DOC_ID' => $doc_id,
                    'SCHED_DAYS' => $sched_days,
                    'SCHED_START_TIME' => $start_time,
                    'SCHED_END_TIME' => $end_time
                ];
                if ($schedule->create($data)) {
                    $message = "✅ Schedule added successfully!";
                } else {
                    $message = "❌ Failed to add schedule.";
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid input. Please check all fields.";
        }
    }

    // Handle UPDATE Schedule
    elseif (isset($_POST['update'])) {
        $sched_id = filter_var($_POST['sched_id'], FILTER_VALIDATE_INT);
        $doc_id = filter_var($_POST['doc_id'], FILTER_VALIDATE_INT);
        $sched_days = trim($_POST['sched_days']);
        $start_time = trim($_POST['start_time']);
        $end_time = trim($_POST['end_time']);

        if ($sched_id && $doc_id && $sched_days && $start_time && $end_time) {
            try {
                $data = [
                    'SCHED_ID' => $sched_id,
                    'DOC_ID' => $doc_id,
                    'SCHED_DAYS' => $sched_days,
                    'SCHED_START_TIME' => $start_time,
                    'SCHED_END_TIME' => $end_time
                ];
                if ($schedule->update($data)) {
                    $message = "✅ Schedule ID {$sched_id} updated successfully!";
                } else {
                    $message = "✅ Schedule ID {$sched_id} updated (or no changes detected).";
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid input for update.";
        }
    }

    // Handle DELETE Schedule
    elseif (isset($_POST['delete'])) {
        $sched_id = filter_var($_POST['delete'], FILTER_VALIDATE_INT);

        if ($sched_id) {
            try {
                if ($schedule->delete($sched_id)) {
                    $message = "✅ Schedule ID {$sched_id} deleted successfully.";
                } else {
                    $message = "❌ Failed to delete schedule ID {$sched_id}.";
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid Schedule ID for deletion.";
        }
    }
}

// Fetch schedules (no search, just all)
$schedules = $schedule->all();

if (!is_array($schedules)) {
    $schedules = [];
}
?>

<h1 class="fw-bold mb-4">Schedule Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<!-- Today's Doctors Section -->
<div class="mb-4">
    <button class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#todaysDoctorsSection">View Doctors with Today's Schedule</button>
    <div id="todaysDoctorsSection" class="collapse mt-3">
        <div class="card card-body shadow-sm">
            <h5>Doctors Scheduled Today (<?= date('F j, Y') ?>)</h5>
            <?php if (empty($todaysDoctors)): ?>
                <p class="text-muted">No doctors have schedules for today.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($todaysDoctors as $doctor): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($doctor['doctor_name']) ?></strong> (ID: <?= htmlspecialchars($doctor['DOC_ID']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end align-items-center mb-3">
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormSchedule">Add New Schedule</button>
</div>

<div id="addFormSchedule" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">Doctor *</label>
                <select name="doc_id" required class="form-select">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['DOC_ID'] ?>"><?= htmlspecialchars($doc['doctor_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Date *</label>
                <input type="date" name="sched_days" required class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Start Time *</label>
                <input type="time" name="start_time" required class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small">End Time *</label>
                <input type="time" name="end_time" required class="form-control">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" name="add" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5>All Schedules</h5>
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table table-bordered table-striped align-middle mt-3" style="min-width: 1300px;">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th style="width: 250px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                        No schedules found. Start by adding a new schedule.
                    </td>
                    </tr>
                <?php else: foreach ($schedules as $s): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($s['SCHED_ID']) ?></td>
                            <td>
                                <select name="doc_id" required class="form-select form-select-sm">
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= $doc['DOC_ID'] ?>" <?= $s['DOC_ID'] == $doc['DOC_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($doc['doctor_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="date" name="sched_days" value="<?= htmlspecialchars($s['SCHED_DAYS']) ?>" class="form-control form-control-sm" required>
                            </td>
                            <td>
                                <input type="time" name="start_time" value="<?= htmlspecialchars($s['SCHED_START_TIME']) ?>" class="form-control form-control-sm" required>
                            </td>
                            <td>
                                <input type="time" name="end_time" value="<?= htmlspecialchars($s['SCHED_END_TIME']) ?>" class="form-control form-control-sm" required>
                            </td>
                            <td><?= formatDate($s['SCHED_CREATED_AT']) ?></td>
                            <td><?= formatDate($s['SCHED_UPDATED_AT']) ?></td>
                            <td class="text-nowrap">
                                <input type="hidden" name="sched_id" value="<?= $s['SCHED_ID'] ?>">
                                <button name="update" class="btn btn-sm btn-success me-1">Update</button>
                                <button name="delete" value="<?= $s['SCHED_ID'] ?>" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete Schedule ID <?= $s['SCHED_ID'] ?>?')">
                                    Delete
                                </button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } else {
                    alert.style.display = 'none';
                }
            }
        }, 5000);
    });
</script>