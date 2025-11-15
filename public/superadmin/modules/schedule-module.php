<?php
// /public/superadmin/modules/schedule-module.php
require_once dirname(__DIR__, 3) . '/classes/Schedule.php';

$schedule = new Schedule($db);
$message = '';

// --- ACCESS CONTROL ---
$user_role = $_SESSION['user_type'] ?? 'unknown';
$logged_doc_id = $_SESSION['doc_id'] ?? null;

if (!in_array($user_role, ['super_admin', 'doctor'])) {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin and Doctors can access this module.</div>';
    return;
}

// Get doctors for dropdown
$doctors = $user_role === 'super_admin' ? $schedule->getAllDoctors() : [];
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// --- HANDLE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = $user_role === 'doctor' ? $logged_doc_id : ($_POST['DOC_ID'] ?? null);

    // CREATE
    if (isset($_POST['create'])) {
        $data = [
            'DOC_ID'           => $doc_id,
            'SCHED_DAYS'       => trim($_POST['SCHED_DAYS'] ?? ''),
            'SCHED_START_TIME' => trim($_POST['SCHED_START_TIME'] ?? ''),
            'SCHED_END_TIME'   => trim($_POST['SCHED_END_TIME'] ?? '')
        ];

        if (empty($doc_id) || empty($data['SCHED_DAYS']) || empty($data['SCHED_START_TIME']) || empty($data['SCHED_END_TIME'])) {
            $message = "❌ Missing Doctor, Schedule Day, or Time fields.";
        } else {
            $result = $schedule->create($data);
            $message = is_string($result) 
                ? $result 
                : ($result === true ? "✅ Schedule added successfully." : "❌ Failed to add schedule.");
        }
    }

    // UPDATE
    elseif (isset($_POST['update'])) {
        $data = [
            'SCHED_ID'         => trim($_POST['SCHED_ID'] ?? ''),
            'DOC_ID'           => $doc_id,
            'SCHED_DAYS'       => trim($_POST['SCHED_DAYS'] ?? ''),
            'SCHED_START_TIME' => trim($_POST['SCHED_START_TIME'] ?? ''),
            'SCHED_END_TIME'   => trim($_POST['SCHED_END_TIME'] ?? '')
        ];
        
        $result = $schedule->update($data);
        $message = $result === true 
            ? "✅ Schedule ID {$_POST['SCHED_ID']} updated successfully." 
            : "❌ Failed to update Schedule ID {$_POST['SCHED_ID']}.";
    }

    // DELETE
    elseif (isset($_POST['delete'])) {
        $sched_id = $_POST['delete'];
        $result = $schedule->delete($sched_id);
        $message = $result === true 
            ? "🗑️ Schedule ID {$sched_id} deleted successfully." 
            : "❌ Failed to delete Schedule ID {$sched_id}.";
    }
}

// --- DETERMINE VIEW ---
$action = $_GET['action'] ?? ($user_role === 'doctor' ? 'view_my' : 'view_all');
$data_list = [];
$current_title = "Schedule Management";

// Force Philippine time for display
date_default_timezone_set('Asia/Manila');
$today_display = date('l, M d, Y'); 

if ($action === 'view_all' && $user_role === 'super_admin') {
    $data_list = $schedule->all();
    $current_title = "All Doctor Schedules";

} elseif ($action === 'view_today') {
    $data_list = $schedule->todaySchedule();
    $current_title = "Today's Schedules ($today_display)";

} elseif ($action === 'view_my' && $user_role === 'doctor') {
    if ($logged_doc_id) {
        $data_list = $schedule->getByDoctorId($logged_doc_id);
        $current_title = "My Schedules";
    } else {
        $message = "⚠️ Doctor ID not found in session.";
    }
}
?>

<h1 class="fw-bold mb-4">Schedule Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : (strpos($message, '❌') !== false || strpos($message, '⚠️') !== false || strpos($message, 'Missing') !== false ? 'alert-danger' : 'alert-info') ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex gap-2 mb-4">
    <?php if ($user_role === 'super_admin'): ?>
    <a href="?module=schedule&action=view_all" class="btn btn-sm <?= $action === 'view_all' ? 'btn-primary' : 'btn-outline-primary' ?>">
        View All
    </a>
    <?php else: ?>
    <a href="?module=schedule&action=view_my" class="btn btn-sm <?= $action === 'view_my' ? 'btn-primary' : 'btn-outline-primary' ?>">
        My Schedules
    </a>
    <?php endif; ?>
    <a href="?module=schedule&action=view_today" class="btn btn-sm <?= $action === 'view_today' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Today's Schedules
    </a>
</div>

<div class="card p-3 shadow-sm">
    <h5 class="mb-3"><?= $current_title ?></h5>

    <form method="POST" class="d-flex flex-wrap gap-2 mb-4 p-3 border rounded bg-light align-items-center">
        <label class="form-label mb-0 fw-bold">Add Schedule:</label>

        <?php if ($user_role === 'super_admin'): ?>
        <select name="DOC_ID" class="form-select w-auto" required>
            <option value="">-- Doctor --</option>
            <?php foreach($doctors as $doc): ?>
                <option value="<?= htmlspecialchars($doc['DOC_ID']) ?>"><?= htmlspecialchars($doc['doctor_name']) ?> (#<?= htmlspecialchars($doc['DOC_ID']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <select name="SCHED_DAYS" class="form-select w-auto" required>
            <option value="">-- Day --</option>
            <?php foreach($days_of_week as $day): ?>
                <option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="time" name="SCHED_START_TIME" class="form-control w-auto" required>
        <span>to</span>
        <input type="time" name="SCHED_END_TIME" class="form-control w-auto" required>

        <button type="submit" name="create" class="btn btn-success">Add</button>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <?php if ($user_role === 'super_admin' || $action === 'view_today'): ?>
                    <th>Doctor</th>
                    <?php endif; ?>
                    <th>Day</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Created/Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_list)): ?>
                    <tr><td colspan="<?= ($user_role === 'super_admin' || $action === 'view_today') ? 7 : 6 ?>" class="text-center text-muted">No schedules found.</td></tr>
                <?php else: foreach ($data_list as $r): ?>
                    <tr>
                        <form method="POST">
                            <td class="small fw-bold"><?= htmlspecialchars($r['SCHED_ID']) ?></td>
                            <?php if ($user_role === 'super_admin' || $action === 'view_today'): ?>
                            <td class="small text-nowrap"><?= htmlspecialchars($r['doctor_name']) ?> (#<?= htmlspecialchars($r['DOC_ID']) ?>)</td>
                            <?php endif; ?>

                            <td>
                                <select name="SCHED_DAYS" class="form-select form-select-sm" required>
                                    <?php foreach($days_of_week as $day): ?>
                                        <option value="<?= htmlspecialchars($day) ?>" <?= $r['SCHED_DAYS'] === $day ? 'selected' : '' ?>><?= htmlspecialchars($day) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                            <td>
                                <span class="d-block mb-1 small text-muted"><?= htmlspecialchars($r['formatted_start_time'] ?? date('h:i A', strtotime($r['SCHED_START_TIME']))) ?></span>
                                <input type="time" name="SCHED_START_TIME" value="<?= htmlspecialchars($r['SCHED_START_TIME']) ?>" class="form-control form-control-sm" required>
                            </td>
                            <td>
                                <span class="d-block mb-1 small text-muted"><?= htmlspecialchars($r['formatted_end_time'] ?? date('h:i A', strtotime($r['SCHED_END_TIME']))) ?></span>
                                <input type="time" name="SCHED_END_TIME" value="<?= htmlspecialchars($r['SCHED_END_TIME']) ?>" class="form-control form-control-sm" required>
                            </td>

                            <td class="small text-muted text-nowrap"><?= $r['formatted_created_at'] ?></td>

                            <td class="text-nowrap">
                                <input type="hidden" name="SCHED_ID" value="<?= htmlspecialchars($r['SCHED_ID']) ?>">
                                <input type="hidden" name="DOC_ID" value="<?= htmlspecialchars($r['DOC_ID']) ?>">
                                <button name="update" class="btn btn-sm btn-success">Update</button>
                                <button name="delete" value="<?= htmlspecialchars($r['SCHED_ID']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete ID <?= htmlspecialchars($r['SCHED_ID']) ?>?')">Delete</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>