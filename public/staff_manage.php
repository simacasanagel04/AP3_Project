<?php
// public/staff_manage.php

require_once '../includes/staff_header.php';
require_once '../config/Database.php';
require_once '../classes/Staff.php';

$database = new Database();
$db = $database->connect();
$staff = new Staff($db);

$search = $_GET['search'] ?? '';
$staffList = $staff->readAll($search);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create') {
        $staff->create($_POST);
        header("Location: staff_manage.php");
        exit;
    } elseif ($_POST['action'] === 'update') {
        $staff->update($_POST);
        header("Location: staff_manage.php");
        exit;
    }
}

$editData = isset($_GET['edit']) ? $staff->readOne($_GET['edit']) : null;
?>

<div class="content container-fluid mt-5 pt-4">
    <h2 class="text-primary mb-4">Staff Management</h2>

    <!-- Search -->
    <form class="mb-3 d-flex" method="GET">
        <input type="text" name="search" class="form-control me-2" placeholder="Search by ID, name, email, etc." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <!-- Staff Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <?= $editData ? "Edit Staff" : "Add New Staff" ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
                <?php if ($editData): ?>
                    <input type="hidden" name="STAFF_ID" value="<?= htmlspecialchars($editData['STAFF_ID']) ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label>First Name</label>
                        <input type="text" name="STAFF_FIRST_NAME" class="form-control" required value="<?= $editData['STAFF_FIRST_NAME'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label>M.I.</label>
                        <input type="text" name="STAFF_MIDDLE_INIT" class="form-control" maxlength="5" value="<?= $editData['STAFF_MIDDLE_INIT'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="STAFF_LAST_NAME" class="form-control" required value="<?= $editData['STAFF_LAST_NAME'] ?? '' ?>">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label>Contact Number</label>
                        <input type="text" name="STAFF_CONTACT_NUM" class="form-control" required value="<?= $editData['STAFF_CONTACT_NUM'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input type="email" name="STAFF_EMAIL" class="form-control" required value="<?= $editData['STAFF_EMAIL'] ?? '' ?>">
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success"><?= $editData ? "Update Staff" : "Add Staff" ?></button>
                    <?php if ($editData): ?>
                        <a href="staff_manage.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-dark text-white">Staff List</div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staffList): ?>
                        <?php foreach ($staffList as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['STAFF_ID']) ?></td>
                                <td><?= htmlspecialchars($row['STAFF_FIRST_NAME'] . ' ' . $row['STAFF_LAST_NAME']) ?></td>
                                <td><?= htmlspecialchars($row['STAFF_EMAIL']) ?></td>
                                <td><?= htmlspecialchars($row['STAFF_CONTACT_NUM']) ?></td>
                                <td><?= htmlspecialchars($row['STAFF_CREATED_AT']) ?></td>
                                <td><?= htmlspecialchars($row['STAFF_UPDATED_AT']) ?></td>
                                <td>
                                    <a href="?edit=<?= $row['STAFF_ID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No staff found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>