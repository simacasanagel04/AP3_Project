<?php
// Include the Status class (for Appointment Status) and the database connection
// Adjust the path as necessary for your file structure
require_once dirname(__DIR__, 3) . '/classes/Status.php';

// Assuming $db is your established PDO database connection
$appointmentStatus = new Status($db);
$message = '';
$user_type = $_SESSION['user_type'] ?? '';

// Restrict access - FIXED: Check for 'superadmin' (no underscore)
if ($user_type !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// --- Handle Form Submissions (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE (Add new status)
    if (isset($_POST['add_status']) && !empty(trim($_POST['status_name']))) {
        $name = trim($_POST['status_name']);
        
        // Removed ENUM validation since it's VARCHAR - allow any non-empty string
        if ($appointmentStatus->create($name)) {
            $message = "✅ Appointment Status '{$name}' added successfully.";
        } else {
            $message = "❌ Failed to add Appointment Status. It might already exist or a database error occurred.";
        }
    }

    // UPDATE (Edit existing status)
    elseif (isset($_POST['update_status'])) {
        $id = $_POST['stat_id'];
        $name = trim($_POST['status_name']);

        // Removed ENUM validation since it's VARCHAR - allow any non-empty string
        if ($appointmentStatus->update($id, $name)) {
            $message = "✅ Appointment Status ID {$id} updated successfully to '{$name}'.";
        } else {
            $message = "❌ Failed to update Appointment Status ID {$id}. Check for duplicates or constraints.";
        }
    }

    // DELETE (Delete status)
    elseif (isset($_POST['delete_status'])) {
        $id = $_POST['delete_status'];
        if ($appointmentStatus->delete($id)) {
            $message = "✅ Appointment Status ID {$id} deleted successfully.";
        } else {
            $message = "❌ Failed to delete Appointment Status ID {$id}. It may be linked to existing appointment records.";
        }
    }
}

// --- Fetch all records for display ---
$records = $appointmentStatus->all();
?>

<h1 class="fw-bold mb-4">Appointment Status Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Add New Appointment Status
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="status_name" class="form-label">Status Name</label>
                        <input type="text" class="form-control" name="status_name" id="status_name" placeholder="e.g., Scheduled" required>
                    </div>
                    <button type="submit" name="add_status" class="btn btn-primary w-100">Add Status</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">
                Existing Appointment Statuses
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="4" class="text-center">No appointment statuses found.</td></tr>
                            <?php else: foreach ($records as $r): ?>
                                <tr>
                                    <form method="POST">
                                        <td><?= $r['stat_id'] ?></td>
                                        <td>
                                            <input type="hidden" name="stat_id" value="<?= $r['stat_id'] ?>">
                                            <select class="form-select form-select-sm" name="status_name" required>
                                                <?php foreach ($records as $status): ?>
                                                    <option value="<?= htmlspecialchars($status['status_name']) ?>" <?= $r['status_name'] == $status['status_name'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($status['status_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><?= date('M d, Y H:i A', strtotime($r['STAT_CREATED_AT'])) ?></td>
                                        <td class="text-nowrap">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success">Update</button>
                                            <button type="submit" name="delete_status" value="<?= $r['stat_id'] ?>" 
                                                    class="btn btn-sm btn-danger" onclick="return confirm('WARNING: Deleting this may break linked appointment records. Proceed?')">Delete</button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
