<?php
require_once dirname(__DIR__, 3) . '/classes/Staff.php';
require_once dirname(__DIR__, 3) . '/classes/User.php';

$staff = new Staff($db);
$user = new User($db);

$message = '';
$userMessage = '';
$user_type = $_SESSION['user_type'] ?? 'super_admin';
$search = $_GET['search_staff'] ?? '';
$newStaff = null; // will store the newly added staff details

// Handle Add, Update, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ ADD STAFF
    if (isset($_POST['add'])) {
        $message = $staff->addStaff($_POST, $user_type);

        // Get last inserted staff if successful
        if (str_contains($message, "✅")) {
            // Using uppercase 'STAFF' for consistency
            $stmt = $db->query("SELECT * FROM STAFF ORDER BY STAFF_ID DESC LIMIT 1"); 
            $newStaff = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    // ✅ CREATE USER ACCOUNT (from modal)
    elseif (isset($_POST['create_user'])) {
        $userData = [
            'user_name' => $_POST['user_name'],
            'password'  => $_POST['password'],
            'linked_id' => $_POST['linked_id'],
            'user_type' => 'staff'
        ];
        $userMessage = $user->addLinkedAccount($userData);
    }

    // ✅ UPDATE STAFF
    elseif (isset($_POST['update'])) {
        $message = $staff->updateStaff($_POST, $user_type);
    }

    // ✅ DELETE STAFF
    elseif (isset($_POST['delete'])) {
        $message = $staff->deleteStaff($_POST['delete'], $user_type);
    }
}

// ✅ Fetch staff records (Search or All)
if (!empty($search)) {
    $records = $staff->searchStaff($search, $user_type);
} else {
    $records = $staff->viewAllStaff($user_type);
}

if (is_string($records)) {
    $message = $records;
    $records = [];
}

// ✅ Generate random password helper
function generateRandomPassword($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($chars), 0, $length);
}
?>

<h1 class="fw-bold mb-4">Staff Management</h1>

<?php if ($message): ?>
<div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($userMessage): ?>
<?php $alertClass = str_contains($userMessage, "✅") ? 'alert-success' : 'alert-danger'; ?>
<div class="alert <?= $alertClass ?>"><?= htmlspecialchars($userMessage) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="GET">
        <input type="hidden" name="module" value="staff">
        <input class="form-control me-2" type="search" name="search_staff" placeholder="Search by name..."
                value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?module=staff" class="btn btn-outline-secondary ms-2">Reset</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addForm">Add New Staff</button>
</div>

<div id="addForm" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3" onsubmit="return validateStaffForm(this)">
            <div class="col-md-4">
                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="middle_init" class="form-control" placeholder="Middle Initial (optional)">
            </div>
            <div class="col-md-4">
                <input type="text" name="phone" class="form-control" placeholder="Contact Number (11 digits)" required
                        pattern="^09\d{9}$"
                        title="Enter a valid 11-digit number starting with 09 (e.g., 09123456789)">
            </div>
            <div class="col-md-4">
                <input type="email" name="email" class="form-control" placeholder="Email" required
                        pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$"
                        title="Enter a valid email address (e.g., example@mail.com)">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" name="add" class="btn btn-primary">Save Staff</button>
            </div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5>All Staff Records</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>First</th>
                    <th>Last</th>
                    <th>Middle</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="9" class="text-center">No staff found.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <form method="POST" onsubmit="return validateStaffForm(this)">
                                <td><?= htmlspecialchars($r['staff_id']) ?></td>
                                <td><input type="text" name="first_name" value="<?= htmlspecialchars($r['first_name']) ?>" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="last_name" value="<?= htmlspecialchars($r['last_name']) ?>" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="middle_init" value="<?= htmlspecialchars($r['middle_init'] ?? '') ?>" class="form-control form-control-sm"></td>
                                <td><input type="text" name="phone" value="<?= htmlspecialchars($r['phone']) ?>" class="form-control form-control-sm"
                                                pattern="^09\d{9}$"
                                                title="Enter a valid 11-digit number starting with 09" required></td>
                                <td><input type="email" name="email" value="<?= htmlspecialchars($r['email']) ?>" class="form-control form-control-sm"
                                                pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$"
                                                title="Enter a valid email address" required></td>
                                <td><?= htmlspecialchars($r['created_at'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['updated_at'] ?? '-') ?></td>
                                <td>
                                    <input type="hidden" name="staff_id" value="<?= htmlspecialchars($r['staff_id']) ?>">
                                    <button type="submit" name="update" class="btn btn-sm btn-success mb-1 w-100">Update</button>
                                    <button type="submit" name="delete" value="<?= htmlspecialchars($r['staff_id']) ?>"
                                                 class="btn btn-sm btn-danger w-100"
                                                 onclick="return confirm('Delete this staff?');">Delete</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="autoUserModal" tabindex="-1" aria-labelledby="autoUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" class="p-3">
        <div class="modal-header">
          <h5 class="modal-title" id="autoUserModalLabel">User Account Created</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="linked_id" id="linked_id" value="<?= htmlspecialchars($newStaff['STAFF_ID'] ?? '') ?>">
            <div class="mb-3">
                <label class="form-label">Username (Email)</label>
                <input type="text" name="user_name" id="user_name" class="form-control" value="<?= htmlspecialchars($newStaff['STAFF_EMAIL'] ?? '') ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Generated Password</label>
                <input type="text" name="password" id="password" class="form-control" value="<?= generateRandomPassword(10) ?>" readonly>
            </div>
            <p class="small text-muted">✅ Please provide these credentials to the staff member.</p>
        </div>
        <div class="modal-footer">
          <button type="submit" name="create_user" class="btn btn-primary">Confirm Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function validateStaffForm(form) {
    const email = form.email?.value.trim();
    const phone = form.phone?.value.trim();
    if (email && !/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$/.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }
    if (phone && !/^09\d{9}$/.test(phone)) {
        alert("Please enter a valid 11-digit number starting with 09.");
        return false;
    }
    return true;
}

<?php if ($newStaff): ?>
document.addEventListener("DOMContentLoaded", () => {
    // Show the modal after a successful staff creation
    const modal = new bootstrap.Modal(document.getElementById('autoUserModal'));
    modal.show();
});
<?php endif; ?>
</script>