<?php
/**
 * SERVICE MANAGEMENT MODULE (service-module.php)
 * * This file handles all CRUD operations for the Service entity 
 * and is designed to be included within the superadmin_dashboard.php content area.
 * * NOTE: This requires 'database.php' and your 'Service.php' class file 
 * to be available in the correct paths (as defined in superadmin_dashboard.php).
 */

// Ensure dependencies are loaded, assuming the path is correct from superadmin_dashboard.php
require_once dirname(__DIR__, 3) . '/classes/Service.php';
// NOTE: $db is assumed to be available from superadmin_dashboard.php scope.

$service = new Service($db);

$message = '';
$search = trim($_GET['search_service'] ?? ''); // Trim the search term
$action = $_GET['action'] ?? 'view_all'; // Default action is view_all
$current_datetime = date('Y-m-d H:i:s'); 
$user_type = $_SESSION['user_type'] ?? 'super_admin'; // Assuming Superadmin access is guaranteed by dashboard header.

// Helper function (already present, kept for reference/consistency)
function formatDate($timestamp) {
    if (!$timestamp) {
        return '—';
    }
    return date('F j, Y h:i A', strtotime($timestamp)); 
}


// --- ACCESS CONTROL ---
if ($user_type !== 'super_admin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// --- CRUD Handlers (Moved to POST block for consistency) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle ADD (Create) Service
    if (isset($_POST['add'])) {
        $serv_name = trim(htmlspecialchars($_POST['serv_name']));
        $serv_description = trim(htmlspecialchars($_POST['serv_description'] ?? ''));
        $serv_price = filter_var($_POST['serv_price'], FILTER_VALIDATE_FLOAT);

        if ($serv_name && $serv_price !== false && $serv_price >= 0) {
            try {
                $service->setServName($serv_name);
                $service->setServDescription($serv_description);
                $service->setServPrice($serv_price);
                $service->setServCreatedAt($current_datetime);

                if ($service->create()) {
                    $message = "✅ Service '{$serv_name}' added successfully!";
                } else {
                    $message = "❌ Failed to add service. Database error or service name already exists.";
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid input. Please check the Service Name and Price.";
        }
    }

    // 2. Handle UPDATE Service
    elseif (isset($_POST['update'])) {
        $serv_id = filter_var($_POST['serv_id'], FILTER_VALIDATE_INT);
        $serv_name = trim(htmlspecialchars($_POST['serv_name']));
        $serv_description = trim(htmlspecialchars($_POST['serv_description'] ?? ''));
        $serv_price = filter_var($_POST['serv_price'], FILTER_VALIDATE_FLOAT);

        if ($serv_id && $serv_name && $serv_price !== false && $serv_price >= 0) {
            try {
                $service->setServId($serv_id);
                $service->setServName($serv_name);
                $service->setServDescription($serv_description);
                $service->setServPrice($serv_price);
                $service->setServUpdatedAt($current_datetime);
                
                if ($service->update()) {
                    $message = "✅ Service ID {$serv_id} updated successfully!";
                } else {
                    $message = "✅ Service ID {$serv_id} updated (or no changes detected)."; // Using info message for no change/no error
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid input for update operation.";
        }
    }

    // 3. Handle DELETE Service
    elseif (isset($_POST['delete'])) {
        $serv_id = filter_var($_POST['delete'], FILTER_VALIDATE_INT);

        if ($serv_id) {
            try {
                $service->setServId($serv_id);
                if ($service->delete()) {
                    $message = "✅ Service ID {$serv_id} deleted successfully.";
                } else {
                    $message = "❌ Failed to delete service ID {$serv_id}. It might be linked to appointments.";
                }
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Invalid Service ID for deletion.";
        }
    }
}

$services = []; // Initialize as empty array for safety

if (!empty($search)) {
    // Both methods now return the final array of data
    $services = $service->search($search);
} else {
    $services = $service->readAll();
}

// Ensure $services is an array (readAll and search should handle this now, 
// but a final check is safe)
if (!is_array($services)) {
    $services = [];
}
?>

<h1 class="fw-bold mb-4">Service Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="GET">
        <input type="hidden" name="module" value="service">
        <input class="form-control me-2" type="search" name="search_service" placeholder="Search by name, description, or price..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?module=service" class="btn btn-outline-secondary ms-2">Reset</a>
        <?php endif; ?>
    </form>
    
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormService">Add New Service</button>
</div>

<div id="addFormService" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="serv_name" placeholder="Service Name (e.g., Consultation)" required class="form-control">
            </div>
            <div class="col-md-5">
                <input type="text" name="serv_description" placeholder="Short description..." class="form-control">
            </div>
            <div class="col-md-2">
                <input type="number" name="serv_price" placeholder="Price (₱)" step="0.01" min="0" required class="form-control">
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" name="add" class="btn btn-primary w-100">Save Service</button>
            </div>
        </form>
    </div>
</div>
<div class="card p-3 shadow-sm">
    <h5>All Services <?= $search ? '(Filtered Results)' : '' ?></h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Price (₱)</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th style="width: 250px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="7" class="text-center">
                            <?php if ($search): ?>
                                No services found matching "<?= htmlspecialchars($search) ?>". 
                            <?php else: ?>
                                No services found. Start by adding a new service.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: foreach ($services as $s): ?>
                    <tr>
                        <form method="POST">
                            <td><?= htmlspecialchars($s['SERV_ID'] ?? '') ?></td> 
                            <td>
                                <input type="text" name="serv_name" value="<?= htmlspecialchars($s['SERV_NAME'] ?? '') ?>"    
                                class="form-control form-control-sm" required>
                            </td>
                            <td>
                                <input type="text" name="serv_description" class="form-control form-control-sm" value="<?= htmlspecialchars($s['SERV_DESCRIPTION'] ?? 'N/A') ?>" >
                            </td>
                            <td>
                                <input type="number" name="serv_price" value="<?= htmlspecialchars($s['SERV_PRICE'] ?? 0.00) ?>" step="0.01" min="0" required class="form-control form-control-sm"> </td>
                            <td><?= formatDate($s['SERV_CREATED_AT'] ?? '') ?></td>
                            <td><?= formatDate($s['SERV_UPDATED_AT'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <input type="hidden" name="serv_id" value="<?= $s['SERV_ID'] ?? '' ?>">
                                <button name="update" class="btn btn-sm btn-success me-1">Update</button>
                                
                                <button name="delete" value="<?= $s['SERV_ID'] ?? '' ?>" class="btn btn-sm btn-danger me-1" 
                                    onclick="return confirm('WARNING: Delete Service ID <?= $s['SERV_ID'] ?? '???' ?> (<?= htmlspecialchars($s['SERV_NAME'] ?? '') ?>)? This may fail if appointments are linked.')">
                                    Delete
                                </button>
                                
                                <a href="?module=appointment&service_id=<?= $s['SERV_ID'] ?? '' ?>" class="btn btn-sm btn-info" title="View Appointments for <?= htmlspecialchars($s['SERV_NAME'] ?? '') ?>">
                                    View Appts
                                </a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
...
        </table>
    </div>
</div>

<script>
    // Simple script to auto-hide alerts (if you use this module outside the dashboard)
    document.addEventListener('DOMContentLoaded', (event) => {
        setTimeout(function() {
            var alert = document.querySelector('.alert');
            if (alert) {
                // Ensure it's a Bootstrap alert before trying to close it
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    // This assumes Bootstrap JS is loaded on the page
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } else {
                    // Fallback if Bootstrap JS is not available
                    alert.style.display = 'none';
                }
            }
        }, 5000); // 5 seconds
    });
</script>