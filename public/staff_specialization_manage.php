<?php
// public/staff_specialization_manage.php

require_once '../config/Database.php';
require_once '../classes/Specialization.php'; 
require_once '../includes/staff_header.php'; // ✅ keep the same header layout

$database = new Database();
$db = $database->connect();
$specialization = new Specialization($db);

$search = $_GET['search'] ?? '';
$specList = $specialization->readAll($search);

// ✅ Handle Create or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create') {
        $specialization->create($_POST);
        header("Location: staff_myprofile.php");
        exit;
    } elseif ($_POST['action'] === 'update') {
        $specialization->update($_POST);
        header("Location: staff_myprofile.php");
        exit;
    }
}

// ✅ Handle Edit
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $specialization->readOne($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Specialization Management | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        footer {
            background-color: #f1f1f1;
            color: #333;
            text-align: center;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            margin-top: auto;
        }
    </style>
</head>

<body>
    <main class="container mt-5">
        <h2 class="text-center mb-4 text-primary fw-bold">Specialization Management</h2>

        <!-- 🔍 Search Form -->
        <form method="GET" class="d-flex mb-3">
            <input type="text" name="search" class="form-control me-2" 
                placeholder="Search by ID or Name" 
                value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>

        <!-- 📝 Add/Edit Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
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
                        <input type="text" 
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
                            <a href="staff_myprofile.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 📋 Specialization Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Specialization List</div>
            <div class="card-body">
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
                                    <td><?= htmlspecialchars($spec['SPEC_CREATED_AT']) ?></td>
                                    <td><?= htmlspecialchars($spec['SPEC_UPDATED_AT'] ?? '—') ?></td>
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
    </main>

    <!-- ✅ Visible Footer (Always at Bottom) -->
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>