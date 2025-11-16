<?php
// -----------------------------------------------------
// public/staff_service.php
// Staff: View, Read, Update Services (NO Create/Delete)
// -----------------------------------------------------

session_start();
require_once '../config/Database.php';
require_once '../classes/Service.php';

// Check if logged in (correct session variable)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$database = new Database();
$db = $database->connect();
$service = new Service($db);

$message = '';

// Handle Update
if (isset($_POST['update'])) {
    $service->serv_id          = (int)$_POST['serv_id'];
    $service->serv_name        = trim($_POST['serv_name']);
    $service->serv_description = trim($_POST['serv_description']);
    $service->serv_price       = (float)$_POST['serv_price'];
    $service->spec_id          = (int)$_POST['spec_id'];
    $service->serv_updated_at  = date('Y-m-d H:i:s');

    if ($service->update()) {
        $message = "<div class='alert alert-success text-center'>Service updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Failed to update service.</div>";
    }
}

// Load single service for editing
$editMode = false;
$editData = null;
if (isset($_GET['edit'])) {
    $service->serv_id = (int)$_GET['edit'];
    $editData = $service->readSingle();
    if (!$editData) {
        $message = "<div class='alert alert-danger text-center'>Service not found.</div>";
    } else {
        $editMode = true;
    }
}

// Fetch all services (returns ARRAY)
$serviceList = $service->read();

// NOW include header after all logic
require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light d-flex flex-column min-vh-100">
<main class="container my-5 flex-grow-1">
    <h2 class="text-center text-primary fw-bold mb-4">Service Management</h2>

    <?= $message ?>

    <!-- Update Form -->
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

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Specialization ID <span class="text-danger">*</span></label>
                        <input type="number" name="spec_id" class="form-control" 
                               value="<?= htmlspecialchars($service->spec_id) ?>" required readonly>
                        <small class="text-muted">Specialization cannot be changed</small>
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

    <!-- Filter Card -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Service ID</label>
                    <input type="number" id="filterID" class="form-control" placeholder="Enter Service ID...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Service Name</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Specialization</label>
                    <select id="filterSpec" class="form-select">
                        <option value="">All Specializations</option>
                        <?php 
                        $specs = array_unique(array_column($serviceList ?: [], 'SPEC_NAME'));
                        foreach ($specs as $spec): 
                            if ($spec): ?>
                                <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
                            <?php endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="button" id="searchBtn" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <button type="button" id="clearBtn" class="btn btn-secondary flex-fill">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- List of All Services -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Service List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="serviceTable">
                    <thead class="table-light text-uppercase">
                        <tr>
                            <th>ID</th>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Price (₱)</th>
                            <th>Specialization</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($serviceList && count($serviceList) > 0): ?>
                            <?php foreach ($serviceList as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['SERV_ID']) ?></td>
                                    <td><?= htmlspecialchars($row['SERV_NAME']) ?></td>
                                    <td><?= htmlspecialchars($row['SERV_DESCRIPTION'] ?? '-') ?></td>
                                    <td>₱ <?= number_format($row['SERV_PRICE'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['SPEC_NAME'] ?? 'N/A') ?></td>
                                    <td><?= date('d/m/Y h:i A', strtotime($row['SERV_CREATED_AT'])) ?></td>
                                    <td>
                                        <a href="?edit=<?= htmlspecialchars($row['SERV_ID']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No services found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<footer class="bg-body-secondary text-center py-3 border-top mt-auto">
    <div class="container">
        <div class="row align-items-center small">
            <div class="col-md-8 text-center text-md-start">
                <p class="mb-0">© 2025 AKSyon Medical Center. All rights reserved.</p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <a href="https://www.facebook.com/" class="text-dark mx-2"><i class="bi bi-facebook fs-5"></i></a>
                <a href="https://www.instagram.com/" class="text-dark mx-2"><i class="bi bi-instagram fs-5"></i></a>
                <a href="https://www.linkedin.com/" class="text-dark mx-2"><i class="bi bi-linkedin fs-5"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterID = document.getElementById('filterID');
    const searchInput = document.getElementById('searchInput');
    const filterSpec = document.getElementById('filterSpec');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearBtn');
    const table = document.getElementById('serviceTable');
    const rows = table.querySelectorAll('tbody tr');

    function filterTable() {
        const idFilter = filterID.value.trim();
        const searchTerm = searchInput.value.toLowerCase();
        const specFilter = filterSpec.value.toLowerCase();

        rows.forEach(row => {
            if (row.cells.length === 1) return; // Skip "no data" row
            
            const serviceID = row.cells[0].textContent.trim();
            const serviceName = row.cells[1].textContent.toLowerCase();
            const spec = row.cells[4].textContent.toLowerCase();

            const matchesID = !idFilter || serviceID === idFilter;
            const matchesSearch = serviceName.includes(searchTerm);
            const matchesSpec = !specFilter || spec.includes(specFilter);

            row.style.display = (matchesID && matchesSearch && matchesSpec) ? '' : 'none';
        });
    }

    function clearFilters() {
        filterID.value = '';
        searchInput.value = '';
        filterSpec.value = '';
        filterTable();
    }

    // Event listeners
    searchBtn.addEventListener('click', filterTable);
    clearBtn.addEventListener('click', clearFilters);
    
    // Also filter on Enter key
    filterID.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') filterTable();
    });
    
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') filterTable();
    });
    
    filterSpec.addEventListener('change', filterTable);
});
</script>
</body>
</html>