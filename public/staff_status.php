<?php
// -----------------------------------------------------
// public/staff_status.php
// -----------------------------------------------------
require_once '../config/Database.php';
require_once '../classes/Status.php';
require_once '../includes/staff_header.php';

// 2. Database connection
$database = new Database();
$db = $database->connect();
$status = new Status($db);

$message = '';

// ✅ CREATE
if (isset($_POST['create'])) {
    $status->status_name = trim($_POST['status_name']);
    $newId = $status->create();

    $message = $newId
        ? "<div class='alert alert-success text-center'>Status created (ID: " . htmlspecialchars($newId) . ").</div>"
        : "<div class='alert alert-danger text-center'>Status already exists or creation failed.</div>";
}

// ✅ UPDATE
if (isset($_POST['update'])) {
    $status->stat_id = (int)$_POST['stat_id'];
    $status->status_name = trim($_POST['status_name']);

    $message = $status->update()
        ? "<div class='alert alert-success text-center'>Status updated successfully.</div>"
        : "<div class='alert alert-danger text-center'>Update failed.</div>";
}

// ✅ EDIT MODE
$editMode = false;
if (isset($_GET['edit'])) {
    $status->stat_id = (int)$_GET['edit'];
    if ($status->readSingle()) {
        $editMode = true;
    } else {
        $message = "<div class='alert alert-danger text-center'>Status not found.</div>";
    }
}

// ✅ FETCH ALL
$stmt = $status->read();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Status Management | AKSyon Medical Center</title>
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
            background: #f1f1f1;
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
        <h2 class="text-center text-primary fw-bold mb-4">Status Management</h2>

        <?= $message ?>

        <!-- ✅ Add/Edit Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <?= $editMode ? "Edit Status" : "Add New Status" ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="stat_id" value="<?= htmlspecialchars($status->stat_id) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Name</label>
                        <input type="text" name="status_name" class="form-control" required
                            value="<?= $editMode ? htmlspecialchars($status->status_name) : '' ?>">
                    </div>

                    <div class="text-end">
                        <button type="submit" name="<?= $editMode ? 'update' : 'create' ?>" class="btn btn-success">
                            <?= $editMode ? 'Update' : 'Add' ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="staff_status.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- ✅ Status Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Status List</div>
            <div class="card-body">
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
                        <?php if ($stmt && $stmt->rowCount() > 0): ?>
                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['STAT_ID']) ?></td>
                                    <td><?= htmlspecialchars($row['STATUS_NAME']) ?></td>
                                    <td><?= htmlspecialchars($row['STATUS_CREATED_AT']) ?></td>
                                    <td><?= htmlspecialchars($row['STATUS_UPDATED_AT'] ?? '-') ?></td>
                                    <td>
                                        <a href="?edit=<?= htmlspecialchars($row['STAT_ID']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>