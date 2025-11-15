<?php
// -----------------------------------------------------
// public/staff_service.php
// Staff: View, Read, Update Services (NO Create/Delete)
// -----------------------------------------------------

session_start();
require_once '../config/Database.php';
require_once '../classes/Service.php';

// Check if logged in BEFORE including header
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$database = new Database();
$db = $database->connect();
$service = new Service($db);

$message = '';

// ✅ Handle Update
if (isset($_POST['update'])) {
    $service->serv_id          = (int)$_POST['serv_id'];
    $service->serv_name        = trim($_POST['serv_name']);
    $service->serv_description = trim($_POST['serv_description']);
    $service->serv_price       = (float)$_POST['serv_price'];
    $service->serv_updated_at  = date('Y-m-d H:i:s'); // ✅ Set update timestamp

    if ($service->update()) {
        $message = "<div class='alert alert-success text-center'>Service updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Failed to update service.</div>";
    }
}

// ✅ Load single service for editing
$editMode = false;
if (isset($_GET['edit'])) {
    $service->serv_id = (int)$_GET['edit'];
    if (!$service->readSingle()) {
        $message = "<div class='alert alert-danger text-center'>Service not found.</div>";
    } else {
        $editMode = true;
    }
}

// ✅ Fetch all services
$stmt = $service->read();

// NOW include header after all logic
require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Management | AKSyon Medical Center</title>
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
    <h2 class="text-center text-primary fw-bold mb-4">Service Management</h2>

    <?= $message ?>

    <!-- 🛠️ Update Form -->
    <?php if ($editMode): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                Edit Service #<?= htmlspecialchars($service->serv_id) ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="serv_id" value="<?= htmlspecialchars($service->serv_id) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="serv_name" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($service->serv_name) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="serv_description" class="form-control" rows="3" maxlength="200"><?= htmlspecialchars($service->serv_description ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" name="serv_price" step="0.01" min="0" class="form-control"
                               value="<?= htmlspecialchars($service->serv_price) ?>" required>
                    </div>

                    <p class="text-muted small">
                        Created: <?= htmlspecialchars($service->serv_created_at) ?> |
                        Updated: <?= htmlspecialchars($service->serv_updated_at ?? 'Never') ?>
                    </p>

                    <div class="text-end">
                        <button type="submit" name="update" class="btn btn-success">Update Service</button>
                        <a href="staff_service.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- 📋 List of All Services -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Service List</div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price (₱)</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt && $stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['SERV_ID']) ?></td>
                                <td><?= htmlspecialchars($row['SERV_NAME']) ?></td>
                                <td><?= htmlspecialchars($row['SERV_DESCRIPTION'] ?? '-') ?></td>
                                <td>₱ <?= number_format($row['SERV_PRICE'], 2) ?></td>
                                <td><?= date('d/m/Y h:i A', strtotime($row['SERV_CREATED_AT'])) ?></td>
                                <td>
                                    <a href="?edit=<?= htmlspecialchars($row['SERV_ID']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No services found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ✅ Footer Always Visible -->
<?php require_once '../includes/footer.php'; ?>
</body>
</html>