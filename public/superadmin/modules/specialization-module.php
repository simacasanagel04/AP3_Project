<?php
// /public/superadmin/modules/specialization-module.php
require_once dirname(__DIR__, 3) . '/classes/Specialization.php';
// NOTE: Assuming the Specialization class has a method getDoctorsBySpecialization()
// and that 'doctor' module handles the 'filter' parameter for SPEC_ID.

$specialization = new Specialization($db);
$message = '';

// Check accessibility: Super Admin (*) and Staff (*) can access
$user_role = $_SESSION['user_type'] ?? 'unknown';
if (!in_array($user_role, ['super_admin', 'staff'])) {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin and Staff can access this module.</div>';
    return;
}

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spec_name = trim($_POST['SPEC_NAME'] ?? '');
    
    // CREATE
    if (isset($_POST['create']) && $user_role === 'super_admin') {
        if (empty($spec_name)) {
            $message = "❌ Specialization name cannot be empty.";
        } else {
            $result = $specialization->create($spec_name);
            if ($result === true) {
                $message = "✅ Specialization '{$spec_name}' added successfully.";
            } else {
                $message = $result; // Contains error message (e.g., already exists)
            }
        }
    }

    // UPDATE
    elseif (isset($_POST['update']) && $user_role === 'super_admin') {
        $data = [
            'SPEC_ID'   => $_POST['SPEC_ID'],
            'SPEC_NAME' => $spec_name
        ];
        
        $result = $specialization->update($data);
        if ($result === true) {
            $message = "✅ Specialization ID {$_POST['SPEC_ID']} updated successfully.";
        } else {
            $message = $result;
        }
    }
    
    // DELETE
    elseif (isset($_POST['delete']) && $user_role === 'super_admin') {
        $spec_id = $_POST['delete'];
        $result = $specialization->delete($spec_id);
        
        if ($result === true) {
            $message = "✅ Specialization ID {$spec_id} deleted successfully.";
        } else {
            $message = $result; // Contains FK constraint error message
        }
    }
}

// --- Determine View and Fetch Data ---
$action = $_GET['action'] ?? 'view_all';
$data_list = [];
$current_title = "Specialization Management";

if ($action === 'view_all') {
    // View all specialization (default view)
    $data_list = $specialization->all();
    $current_title = "All Specializations";
} elseif ($action === 'view_doctors') {
    // View doctors grouped by specialization (required feature)
    $data_list = $specialization->getDoctorsBySpecialization();
    $current_title = "Doctors Grouped by Specialization";
}

?>

<h1 class="fw-bold mb-4">Specialization Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="d-flex gap-2 mb-4">
    <!-- Links corrected to use 'specialization' (singular) for the module parameter -->
    <a href="?module=specialization&action=view_all" class="btn btn-sm <?= $action === 'view_all' ? 'btn-primary' : 'btn-outline-primary' ?>">
        View All Specializations
    </a>
    <a href="?module=specialization&action=view_doctors" class="btn btn-sm <?= $action === 'view_doctors' ? 'btn-primary' : 'btn-outline-primary' ?>">
        View Doctors by Specialization
    </a>
</div>

<div class="card p-3 shadow-sm">
    <h5 class="mb-3"><?= $current_title ?></h5>

    <?php if ($user_role === 'super_admin' && $action === 'view_all'): ?>
    <form method="POST" class="d-flex gap-2 mb-4 p-3 border rounded bg-light align-items-center">
        <label for="new_spec" class="form-label mb-0 fw-bold">Add New:</label>
        <input type="text" name="SPEC_NAME" id="new_spec" placeholder="e.g., Family Medicine" class="form-control" required>
        <button type="submit" name="create" class="btn btn-success text-nowrap">Add Specialization</button>
    </form>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Specialization Name</th>
                    <?php if ($action === 'view_doctors'): ?>
                        <th>Doctor Count</th>
                        <th>Action</th>
                    <?php else: ?>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_list)): ?>
                    <tr><td colspan="<?= $action === 'view_doctors' ? 4 : 5 ?>" class="text-center">No records found.</td></tr>
                <?php else: foreach ($data_list as $r): ?>
                    <tr>
                        <form method="POST">
                            <td><?= $r['SPEC_ID'] ?></td>
                            <td>
                                <?php if ($action === 'view_all' && $user_role === 'super_admin'): ?>
                                    <input type="text" name="SPEC_NAME" value="<?= htmlspecialchars($r['SPEC_NAME']) ?>" class="form-control form-control-sm" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($r['SPEC_NAME']) ?>
                                <?php endif; ?>
                            </td>

                            <?php if ($action === 'view_doctors'): ?>
                                <td><?= $r['doctor_count'] ?></td>
                                <td>
                                    <!-- This link assumes a 'doctor' module that can filter by specialization ID -->
                                    <a href="?module=doctor&filter=<?= $r['SPEC_ID'] ?>" class="btn btn-sm btn-info">
                                        Browse Doctors 
                                    </a>
                                </td>
                            <?php else: ?>
                                <td><?= $r['formatted_created_at'] ?></td>
                                <td><?= $r['formatted_updated_at'] ?></td>
                                <td class="text-nowrap">
                                    <?php if ($user_role === 'super_admin'): ?>
                                        <input type="hidden" name="SPEC_ID" value="<?= $r['SPEC_ID'] ?>">
                                        <button name="update" class="btn btn-sm btn-success">Update</button>
                                        <button name="delete" value="<?= $r['SPEC_ID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('WARNING: Delete Specialization ID <?= $r['SPEC_ID'] ?>? This may fail if doctors are linked.')">Delete</button>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
