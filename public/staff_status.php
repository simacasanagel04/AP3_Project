<?php
// public/staff_status.php

session_start();  
require_once '../config/Database.php';
require_once '../classes/Status.php'; 

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {  
    header("Location: login.php");
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Status.php';

// Database connection
$database = new Database();
$db = $database->connect();
$status = new Status($db);

// MESSAGE HOLDER
$message = '';

// SEARCH
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// CREATE
if (isset($_POST['create'])) {
    $status_name = trim($_POST['status_name']);

    if (empty($status_name)) {
        $message = "<div class='alert alert-danger text-center'>Status name cannot be empty.</div>";
    } else {
        $result = $status->create($status_name);
        $message = $result
            ? "<div class='alert alert-success text-center'>Status created successfully.</div>"
            : "<div class='alert alert-danger text-center'>Failed to create status.</div>";
    }
}

// UPDATE
if (isset($_POST['update'])) {
    $stat_id = (int)$_POST['stat_id'];
    $status_name = trim($_POST['status_name']);

    if (empty($status_name)) {
        $message = "<div class='alert alert-danger text-center'>Status name cannot be empty.</div>";
    } else {
        $result = $status->update($stat_id, $status_name);
        $message = $result
            ? "<div class='alert alert-success text-center'>Status updated successfully.</div>"
            : "<div class='alert alert-danger text-center'>Failed to update status.</div>";
    }
}

// EDIT MODE
$editMode = false;
$editData = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $allStatuses = $status->all();

    foreach ($allStatuses as $s) {
        if ($s['stat_id'] == $edit_id) {
            $editData = $s;
            $editMode = true;
            break;
        }
    }

    if (!$editMode) {
        $message = "<div class='alert alert-danger text-center'>Status not found.</div>";
    }
}

// FETCH ALL WITH SEARCH
$statusList = $status->all();

if (!empty($search)) {
    $statusList = array_filter($statusList, function ($row) use ($search) {
        return stripos($row['status_name'], $search) !== false ||
               stripos($row['stat_id'], $search) !== false;
    });
}

require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Management | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        footer { background: #e5e2e2; border-top: 1px solid #ddd; }
    </style>
</head>

<body>
    <main class="container mt-4 mb-5">

        <h2 class="text-center text-primary fw-bold mb-4">Status Management</h2>

        <!-- ACTION MESSAGE -->
        <?= $message ?>

        <!-- SEARCH + CLEAR FORM -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-8 col-12">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search by ID or Status Name"
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-2 col-6 d-grid">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>

            <div class="col-md-2 col-6 d-grid">
                <a href="staff_status.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <!-- Add/Edit Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <?= $editMode ? "Edit Status" : "Add New Status" ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="stat_id" value="<?= htmlspecialchars($editData['stat_id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Name</label>
                        <input type="text" 
                               name="status_name" 
                               class="form-control" 
                               required
                               value="<?= $editMode ? htmlspecialchars($editData['status_name']) : '' ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" 
                                name="<?= $editMode ? 'update' : 'create' ?>" 
                                class="btn btn-success">
                            <?= $editMode ? 'Update' : 'Add' ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a href="staff_status.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Status Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Status List</div>
            <div class="card-body table-responsive">

                <table class="table table-hover align-middle">
                    <thead class="table-light text-uppercase">
                        <tr>
                            <th>ID</th>
                            <th>Status Name</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ($statusList && count($statusList) > 0): ?>
                            <?php foreach ($statusList as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['stat_id']) ?></td>
                                    <td><?= htmlspecialchars($row['status_name']) ?></td>
                                    <td><?= htmlspecialchars($row['STAT_CREATED_AT']) ?></td>
                                    <td><?= htmlspecialchars($row['STAT_UPDATED_AT'] ?? '—') ?></td>
                                    <td>
                                        <a href="?edit=<?= htmlspecialchars($row['stat_id']) ?>" 
                                           class="btn btn-sm btn-warning">
                                           Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No statuses found.</td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </main>
    
    <footer class="py-3">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>