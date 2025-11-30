<?php
// /public/superadmin/modules/user-module.php
// Adjust the path as necessary
require_once dirname(__DIR__, 3) . '/classes/User.php';

// Initialize object (assuming $db is the PDO connection)
$user = new User($db);
$message = '';
$search = trim($_GET['search_user'] ?? '');
// Filter parameter: 'all', 'doctor', 'patient', 'staff', or 'superadmin'
$filter = $_GET['filter'] ?? 'all';
$user_type = $_SESSION['user_type'] ?? '';

// Restrict access - FIXED: Check for 'superadmin' instead of 'super_admin'
if ($user_type !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// Pagination settings
$limit = 30; // Changed to 30 per page as requested
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// --- Handle Actions (Only Delete remains) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DELETE (Revoke Access)
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
       
        $success = $user->delete($id);
        if ($success) {
            $message = "User ID {$id} access revoked successfully. The associated record (Doctor/Staff/Patient) remains.";
        } else {
            $message = "Failed to revoke access for User ID {$id}. A database error occurred.";
        }
    }
}

// --- Fetch records using your User class with PROPER PAGINATION ---
try {
    if (!empty($search)) {
        $all_records = $user->search($search);
        $current_title = "Search Results for '" . htmlspecialchars($search) . "'";
    } elseif ($filter === 'all') {
        $all_records = $user->all();
        $current_title = "All System User Accounts";
    } else {
        $all_records = $user->allByType($filter);
        $current_title = ucfirst($filter) . " Accounts";
    }
} catch (Exception $e) {
    error_log("User Module Error: " . $e->getMessage());
    $all_records = [];
    $message = "Error loading user data. Please check database connection.";
}

// Apply pagination in PHP
$total_records = count($all_records);
$total_pages = max(1, ceil($total_records / $limit));

// Ensure page doesn't exceed total pages
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

$records = array_slice($all_records, $offset, $limit);

// Define filter links for the navigation
$filters = [
    'all' => 'All Users',
    'doctor' => 'Doctors',
    'patient' => 'Patients',
    'staff' => 'Staff',
    'superadmin' => 'Super Admins'
];

// Build base URL for pagination
$base_url = '?module=user';
if (!empty($search)) {
    $base_url .= '&search_user=' . urlencode($search);
} else {
    $base_url .= '&filter=' . $filter;
}
?>
<h1 class="fw-bold mb-4">User Account Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, 'Failed') !== false || strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-info' ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card p-3 shadow-sm mb-4">
    <h5 class="mb-3">Filter Accounts</h5>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($filters as $key => $label): ?>
            <a href="?module=user&filter=<?= $key ?>" 
               class="btn btn-sm <?= $filter === $key && empty($search) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
   
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <form class="d-flex w-100 w-md-50" method="GET" style="max-width: 500px;">
            <input type="hidden" name="module" value="user">
            <input class="form-control me-2" 
                   type="search" 
                   name="search_user" 
                   placeholder="Search by Username or Linked Name..." 
                   value="<?= htmlspecialchars($search) ?>"
                   autocomplete="off">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="?module=user&filter=all" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            <?php endif; ?>
        </form>
       
        <!-- UPDATED: Button now triggers a modal for selection -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userTypeModal">
            <i class="bi bi-plus-circle"></i> Create New User
        </button>
    </div>
</div>

<!-- Summary + Pagination -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
    <div>
        <strong><?= $current_title ?></strong> 
        <span class="text-muted">(Showing <?= count($records) ?> of <?= $total_records ?> records - Page <?= $page ?> of <?= $total_pages ?>)</span>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav aria-label="User pagination">
        <ul class="pagination pagination-sm mb-0">
            <!-- First Page -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=1" aria-label="First">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
            
            <!-- Previous Page -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= max(1, $page - 1) ?>" aria-label="Previous">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <?php
            // Smart pagination - show 5 pages at a time
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            // Adjust if we're at the beginning or end
            if ($page <= 3) {
                $end = min($total_pages, 5);
            }
            if ($page > $total_pages - 3) {
                $start = max(1, $total_pages - 4);
            }
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Page -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= min($total_pages, $page + 1) ?>" aria-label="Next">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            
            <!-- Last Page -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= $total_pages ?>" aria-label="Last">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="card p-3 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 25%;">Username (Email)</th>
                    <th style="width: 12%;">User Type</th>
                    <th style="width: 28%;">Linked Record</th>
                    <th style="width: 18%;">Created At</th>
                    <th style="width: 12%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox text-muted fs-1"></i>
                            <p class="text-muted mb-0 mt-2">No user accounts found matching the criteria.</p>
                            <?php if (!empty($search)): ?>
                                <a href="?module=user&filter=all" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-arrow-left"></i> View All Users
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): 
                        // Determine which name to display based on the user type
                        $linked_name = 'N/A';
                        if ($r['user_type'] === 'Patient' && !empty($r['PAT_ID'])) {
                            $linked_name = "Patient #{$r['PAT_ID']} ({$r['patient_name']})";
                        } elseif ($r['user_type'] === 'Staff' && !empty($r['STAFF_ID'])) {
                            $linked_name = "Staff #{$r['STAFF_ID']} ({$r['staff_name']})";
                        } elseif ($r['user_type'] === 'Doctor' && !empty($r['DOC_ID'])) {
                            $linked_name = "Doctor #{$r['DOC_ID']} ({$r['doctor_name']})";
                        } elseif ($r['user_type'] === 'Super Admin') {
                            $linked_name = 'SYSTEM ADMINISTRATOR';
                        }
                    ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($r['USER_ID']) ?></td>
                            <td>
                                <small class="text-muted d-block">
                                    <i class="bi bi-envelope"></i>
                                </small>
                                <?= htmlspecialchars($r['USER_NAME']) ?>
                            </td>
                            <td>
                                <span class="badge bg-<?=
                                    $r['user_type'] === 'Super Admin' ? 'danger' :
                                    ($r['user_type'] === 'Doctor' ? 'info' :
                                    ($r['user_type'] === 'Staff' ? 'warning' : 'success')) ?>">
                                    <?= htmlspecialchars($r['user_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($linked_name) ?></td>
                            <td>
                                <small>
                                    <i class="bi bi-calendar-check text-muted"></i>
                                    <?= htmlspecialchars($r['formatted_created_at']) ?>
                                </small>
                            </td>
                            <td class="text-nowrap">
                                <?php if ($r['USER_ID'] != ($_SESSION['user_id'] ?? 0)): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('⚠️ REVOKE ACCESS\n\nDelete User #<?= $r['USER_ID'] ?> (<?= htmlspecialchars($r['USER_NAME']) ?>)?\n\nThis action removes login access but keeps the linked Doctor/Staff/Patient record.\n\nClick OK to proceed.')">
                                        <button name="delete" 
                                                value="<?= $r['USER_ID'] ?>" 
                                                class="btn btn-sm btn-danger">
                                            <i class="bi bi-person-x"></i> Revoke Access
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary disabled" disabled>
                                        <i class="bi bi-shield-check"></i> Current User
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bottom Pagination (duplicate for convenience) -->
<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-3">
    <nav aria-label="User pagination">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=1"><i class="bi bi-chevron-double-left"></i></a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= max(1, $page - 1) ?>"><i class="bi bi-chevron-left"></i></a>
            </li>

            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            if ($page <= 3) $end = min($total_pages, 5);
            if ($page > $total_pages - 3) $start = max(1, $total_pages - 4);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= min($total_pages, $page + 1) ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $base_url ?>&page=<?= $total_pages ?>"><i class="bi bi-chevron-double-right"></i></a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<!-- New Modal for User Type Selection -->
<div class="modal fade" id="userTypeModal" tabindex="-1" aria-labelledby="userTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userTypeModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Select User Type to Register
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">
                    <i class="bi bi-info-circle"></i>
                    Registering a new account will redirect you to the corresponding management module to create the record (Doctor/Patient/Staff) and automatically generate their user credentials.
                </p>
                <div class="d-grid gap-3">
                    <!-- Links redirect to the specific modules -->
                    <a href="?module=doctor" class="btn btn-info btn-lg shadow-sm">
                        <i class="bi bi-hospital me-2"></i>Register Doctor
                    </a>
                    <a href="?module=patient" class="btn btn-success btn-lg shadow-sm">
                        <i class="bi bi-person-heart me-2"></i>Register Patient
                    </a>
                    <a href="?module=staff" class="btn btn-warning btn-lg shadow-sm">
                        <i class="bi bi-people me-2"></i>Register Staff
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>