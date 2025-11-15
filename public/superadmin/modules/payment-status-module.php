<?php
// Include the Payment_Status class and the database connection
// Adjust the path as necessary for your file structure
require_once dirname(__DIR__, 3) . '/classes/Payment_Status.php';

// Assuming $db is your established PDO database connection
$paymentStatus = new Payment_Status($db);
$message = '';
$user_type = $_SESSION['user_type'] ?? 'super_admin';

// Restrict access
if ($user_type !== 'super_admin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// --- Handle Form Submissions (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CREATE (Add new status)
    if (isset($_POST['add_status']) && !empty(trim($_POST['pymt_stat_name']))) {
        $name = trim($_POST['pymt_stat_name']);
        
        // Ensure the input is one of the valid ENUM values
        if (!in_array($name, ['Paid', 'Pending', 'Refunded'])) {
             $message = "❌ Invalid status name. Must be Paid, Pending, or Refunded.";
        } elseif ($paymentStatus->create($name)) {
            $message = "✅ Payment Status '{$name}' added successfully.";
        } else {
            $message = "❌ Failed to add Payment Status. It might already exist or a database error occurred.";
        }
    }

    // UPDATE (Edit existing status)
    elseif (isset($_POST['update_status'])) {
        $id = $_POST['pymt_stat_id'];
        $name = trim($_POST['pymt_stat_name']);
        
        if (!in_array($name, ['Paid', 'Pending', 'Refunded'])) {
             $message = "❌ Invalid status name. Must be Paid, Pending, or Refunded.";
        } elseif ($paymentStatus->update($id, $name)) {
            $message = "✅ Payment Status ID {$id} updated successfully to '{$name}'.";
        } else {
            $message = "❌ Failed to update Payment Status ID {$id}. Check for duplicates or constraints.";
        }
    }

    // DELETE (Delete status)
    elseif (isset($_POST['delete_status'])) {
        $id = $_POST['delete_status'];
        if ($paymentStatus->delete($id)) {
            $message = "✅ Payment Status ID {$id} deleted successfully.";
        } else {
            $message = "❌ Failed to delete Payment Status ID {$id}. It may be linked to existing payment records.";
        }
    }
}

// --- Fetch all records for display ---
$records = $paymentStatus->all();
?>

<h1 class="fw-bold mb-4">Payment Status Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                Add New Payment Status
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="pymt_stat_name" class="form-label">Status Name</label>
                        <select class="form-select" name="pymt_stat_name" id="pymt_stat_name" required>
                            <option value="">-- Select Status --</option>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Refunded">Refunded</option>
                        </select>
                    </div>
                    <button type="submit" name="add_status" class="btn btn-primary w-100">Add Status</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">
                Existing Payment Statuses
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
                                <tr><td colspan="4" class="text-center">No payment statuses found.</td></tr>
                            <?php else: foreach ($records as $r): ?>
                                <tr>
                                    <form method="POST">
                                        <td><?= $r['pymt_stat_id'] ?></td>
                                        <td>
                                            <input type="hidden" name="pymt_stat_id" value="<?= $r['pymt_stat_id'] ?>">
                                            <select class="form-select form-select-sm" name="pymt_stat_name" required>
                                                <option value="Paid" <?= $r['pymt_stat_name'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="Pending" <?= $r['pymt_stat_name'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Refunded" <?= $r['pymt_stat_name'] == 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                            </select>
                                        </td>
                                        <td><?= date('M d, Y H:i A', strtotime($r['PYMT_STAT_CREATED_AT'])) ?></td>
                                        <td class="text-nowrap">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success">Update</button>
                                            <button type="submit" name="delete_status" value="<?= $r['pymt_stat_id'] ?>" 
                                                    class="btn btn-sm btn-danger" onclick="return confirm('WARNING: Deleting this may break linked payment records. Proceed?')">Delete</button>
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