<?php
// public/staff_manage.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start(); 
require_once '../config/Database.php';
require_once '../classes/Staff.php';

// Redirect if not logged in (BEFORE including header)
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to database with error handling
try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db instanceof PDO) {
        throw new Exception('Database connection failed');
    }
    
    $staff = new Staff($db);
    
} catch (Exception $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

// Handle form actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create') {
        // Map form fields to what Staff::create() expects
        $data = [
            'first_name' => $_POST['STAFF_FIRST_NAME'],
            'last_name' => $_POST['STAFF_LAST_NAME'],
            'middle_init' => $_POST['STAFF_MIDDLE_INIT'] ?? null,
            'phone' => $_POST['STAFF_CONTACT_NUM'],
            'email' => $_POST['STAFF_EMAIL']
        ];
        $result = $staff->create($data, 'super_admin');
        error_log("Staff create result: " . print_r($result, true));
        header("Location: staff_manage.php");
        exit;
    } elseif ($_POST['action'] === 'update') {
        // Map form fields to what Staff::update() expects
        $data = [
            'staff_id' => $_POST['STAFF_ID'],
            'first_name' => $_POST['STAFF_FIRST_NAME'],
            'last_name' => $_POST['STAFF_LAST_NAME'],
            'middle_init' => $_POST['STAFF_MIDDLE_INIT'] ?? null,
            'phone' => $_POST['STAFF_CONTACT_NUM'],
            'email' => $_POST['STAFF_EMAIL']
        ];
        $result = $staff->update($data, 'super_admin');
        error_log("Staff update result: " . print_r($result, true));
        header("Location: staff_manage.php");
        exit;
    }
}

// Get search parameter and sanitize
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Log the search attempt
error_log("Staff search initiated - Term: '" . $search . "'");

// Fetch staff list
$staffList = $staff->readAll($search);

// Log results
error_log("Staff search results - Count: " . count($staffList));

// Get edit data if editing
$editData = isset($_GET['edit']) ? $staff->readOne($_GET['edit']) : null;

require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; }
        .card { border-radius: 10px; }
        footer { background: #e5e2e2; color: #333; padding: 15px 0; border-top: 1px solid #ddd; }
    </style>
    
</head>

<body>
    <main class="container mt-5 mb-5">
        <h2 class="text-center text-primary fw-bold mb-4">Staff Management</h2>

        <!-- Search Form -->
        <form class="mb-3 d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control"
                placeholder="Search by ID, name, email, contact, etc."
                value="<?= htmlspecialchars($search) ?>"
                autocomplete="off">

            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Search
            </button>

            <!-- CLEAR BUTTON ADDED -->
            <a href="staff_manage.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle"></i> Clear
            </a>
        </form>

        <!-- Staff Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">
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
                            <label class="form-label fw-semibold">First Name</label>
                            <input type="text" name="STAFF_FIRST_NAME" class="form-control" required 
                                   value="<?= htmlspecialchars($editData['STAFF_FIRST_NAME'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">M.I.</label>
                            <input type="text" name="STAFF_MIDDLE_INIT" class="form-control" maxlength="5" 
                                   value="<?= htmlspecialchars($editData['STAFF_MIDDLE_INIT'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" name="STAFF_LAST_NAME" class="form-control" required 
                                   value="<?= htmlspecialchars($editData['STAFF_LAST_NAME'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Number</label>
                            <input type="text" name="STAFF_CONTACT_NUM" class="form-control" required 
                                   value="<?= htmlspecialchars($editData['STAFF_CONTACT_NUM'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="STAFF_EMAIL" class="form-control" required 
                                   value="<?= htmlspecialchars($editData['STAFF_EMAIL'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-success">
                            <?= $editData ? "Update Staff" : "Add Staff" ?>
                        </button>
                        <?php if ($editData): ?>
                            <a href="staff_manage.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Staff Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Staff List</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-uppercase">
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
                                        <td><?= htmlspecialchars($row['staff_id']) ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                                        <td><?= htmlspecialchars($row['updated_at']) ?></td>
                                        <td>
                                            <a href="?edit=<?= $row['staff_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No staff found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row align-items-center small">
                <div class="col-md-8 text-center text-md-start">
                    <p class="mb-0 text-black">© 2025 AKSyon Medical Center. All rights reserved.</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="https://www.facebook.com/" class="text-black mx-2"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="https://www.instagram.com/" class="text-black mx-2"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="https://www.linkedin.com/" class="text-black mx-2"><i class="bi bi-linkedin fs-5"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
