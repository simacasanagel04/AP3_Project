<?php
// public/staff_specialization_manage.php

session_start();  
require_once '../config/Database.php';
require_once '../classes/Specialization.php'; 

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) { 
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$specialization = new Specialization($db);

// Handle Create or Update BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create') {
        $specialization->create($_POST);
        header("Location: staff_specialization_manage.php");
        exit;
    } elseif ($_POST['action'] === 'update') {
        $specialization->update($_POST);
        header("Location: staff_specialization_manage.php");
        exit;
    }
}

$search = $_GET['search'] ?? '';
$specList = $specialization->readAll($search);

// Handle Edit
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $specialization->readOne($_GET['edit']);
}

// NOW include the header AFTER all processing
require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialization Management | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        footer { background: #e5e2e2; border-top: 1px solid #ddd; }
    </style>
</head>

<body>
    <main class="container my-5">
        <h2 class="text-center mb-4 text-primary fw-bold">Specialization Management</h2>

        <!-- 🔍 Search Form -->
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-8 col-12">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control"
                    placeholder="Search by ID or Name" 
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-2 col-6 d-grid">
                <button class="btn btn-outline-primary" type="submit">Search</button>
            </div>

            <!-- CLEAR BUTTON -->
            <div class="col-md-2 col-6 d-grid">
                <a href="staff_specialization_manage.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <!-- 📝 Add/Edit Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">
                <?= $editData ? "Edit Specialization" : "Add New Specialization" ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
                    
                    <?php if ($editData): ?>
                        <input type="hidden" name="SPEC_ID" value="<?= htmlspecialchars($editData['SPEC_ID']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Specialization Name</label>
                        <input 
                            type="text" 
                            name="SPEC_NAME" 
                            class="form-control" 
                            required 
                            value="<?= htmlspecialchars($editData['SPEC_NAME'] ?? '') ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">
                            <?= $editData ? 'Update' : 'Add' ?>
                        </button>
                        <?php if ($editData): ?>
                            <a href="staff_specialization_manage.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 📋 Specialization Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Specialization List</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-uppercase">
                            <tr>
                                <th>SPEC ID</th>
                                <th>SPEC NAME</th>
                                <th>CREATED AT</th>
                                <th>UPDATED AT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($specList): ?>
                                <?php foreach ($specList as $spec): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($spec['SPEC_ID']) ?></td>
                                        <td><?= htmlspecialchars($spec['SPEC_NAME']) ?></td>
                                        <td><?= htmlspecialchars($spec['formatted_created_at'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($spec['formatted_updated_at'] ?? '—') ?></td>
                                        <td>
                                            <a href="?edit=<?= $spec['SPEC_ID'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No Specializations Found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>