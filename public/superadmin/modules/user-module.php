<?php
// /public/superadmin/modules/user-module.php
// Adjust the path as necessary
require_once dirname(__DIR__, 3) . '/classes/User.php';

// Initialize object (assuming $db is the PDO connection)
$user = new User($db);

$message = '';
$search = $_GET['search_user'] ?? '';
// Filter parameter: 'filter' will be 'all', 'doctor', 'patient', 'staff', or 'superadmin'
$filter = $_GET['filter'] ?? 'all'; 
$user_type = $_SESSION['user_type'] ?? '';

// Restrict access - FIXED: Check for 'superadmin' instead of 'super_admin'
if ($user_type !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// --- Handle Actions (Only Delete remains) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // DELETE (Revoke Access)
    if (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        
        $success = $user->delete($id);

        if ($success) {
            $message = "✅ User ID {$id} access revoked successfully. The associated record (Doctor/Staff/Patient) remains.";
        } else {
            $message = "❌ Failed to revoke access for User ID {$id}. A database error occurred.";
        }
    }
}

// --- Fetch records based on filter and search ---
if (!empty($search)) {
    // Search overrides filters
    $records = $user->search($search);
    $current_title = "Search Results for '{$search}'";
} elseif ($filter === 'all') {
    $records = $user->all();
    $current_title = "All System User Accounts";
} else {
    // Filter by type (doctor, patient, staff, superadmin)
    $records = $user->allByType($filter);
    $current_title = ucfirst($filter) . " Accounts";
}

// Define filter links for the navigation
$filters = [
    'all' => 'All Users',
    'doctor' => 'Doctors',
    'patient' => 'Patients',
    'staff' => 'Staff',
    'superadmin' => 'Super Admins'
];
?>

<h1 class="fw-bold mb-4">User Account Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card p-3 shadow-sm mb-4">
    <h5>Filter Accounts</h5>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($filters as $key => $label): ?>
            <a href="?module=user&filter=<?= $key ?>" class="btn btn-sm <?= $filter === $key && empty($search) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div class="d-flex justify-content-between align-items-center">
        <form class="d-flex w-50" method="GET">
            <input type="hidden" name="module" value="user">
            <input class="form-control me-2" type="search" name="search_user" placeholder="Search by Username or Linked Name..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <?php if ($search): ?>
                <a href="?module=user&filter=<?= $filter ?>" class="btn btn-outline-secondary ms-2">Reset</a>
            <?php endif; ?>
        </form>
        
        <!-- UPDATED: Button now triggers a modal for selection -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userTypeModal">
            + Create New User
        </button>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5 class="mb-3"><?= $current_title ?> (Total: <?= count($records) ?>)</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Username (Email)</th>
                    <th>User Type</th>
                    <th>Linked Record</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="6" class="text-center">No user accounts found matching the criteria.</td></tr>
                <?php else: foreach ($records as $r): 
                    
                    // Determine which name to display based on the user type
                    $linked_name = 'N/A';
                    if ($r['user_type'] === 'Patient' && $r['PAT_ID'] !== null) {
                        $linked_name = "Patient #{$r['PAT_ID']} ({$r['patient_name']})";
                    } elseif ($r['user_type'] === 'Staff' && $r['STAFF_ID'] !== null) {
                        $linked_name = "Staff #{$r['STAFF_ID']} ({$r['staff_name']})";
                    } elseif ($r['user_type'] === 'Doctor' && $r['DOC_ID'] !== null) {
                        $linked_name = "Doctor #{$r['DOC_ID']} ({$r['doctor_name']})";
                    } elseif ($r['user_type'] === 'Super Admin') {
                        $linked_name = 'SYSTEM ADMINISTRATOR';
                    }

                ?>
                    <tr>
                        <td><?= $r['USER_ID'] ?></td>
                        <td><?= htmlspecialchars($r['USER_NAME']) ?></td>
                        <td>
                            <span class="badge bg-<?= 
                                $r['user_type'] === 'Super Admin' ? 'danger' : 
                                ($r['user_type'] === 'Doctor' ? 'info' : 
                                ($r['user_type'] === 'Staff' ? 'warning' : 'success')) ?>">
                                <?= htmlspecialchars($r['user_type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($linked_name) ?></td>
                        <td><?= $r['formatted_created_at'] ?></td>
                        <td class="text-nowrap">
                            <form method="POST" class="d-inline">
                                <?php if ($r['USER_ID'] != ($_SESSION['user_id'] ?? 0)): ?>
                                    <button name="delete" value="<?= $r['USER_ID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('REVOKE ACCESS: Delete User #<?= $r['USER_ID'] ?>? This action removes login access but keeps the linked Doctor/Staff/Patient record.')">
                                        Revoke Access
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary disabled">Current User</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Modal for User Type Selection -->
<div class="modal fade" id="userTypeModal" tabindex="-1" aria-labelledby="userTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userTypeModalLabel">Select User Type to Register</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Registering a new account will redirect you to the corresponding management module to create the record (Doctor/Patient/Staff) and automatically generate their user credentials.</p>
                <div class="d-grid gap-3">
                    <!-- Links redirect to the specific modules -->
                    <a href="?module=doctor" class="btn btn-info btn-lg shadow">Register Doctor</a>
                    <a href="?module=patient" class="btn btn-success btn-lg shadow">Register Patient</a>
                    <a href="?module=staff" class="btn btn-warning btn-lg shadow">Register Staff</a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>