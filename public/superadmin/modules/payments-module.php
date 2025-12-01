<?php

// public/superadmin/modules/payments-module.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 3) . '/classes/Payment.php';
require_once dirname(__DIR__, 3) . '/classes/Payment_Method.php';
require_once dirname(__DIR__, 3) . '/classes/Payment_Status.php';

if (!isset($db)) {
    die('<div class="alert alert-danger">Database connection ($db) not available.</div>');
}

$payment = new Payment($db);
$paymentMethod = new Payment_Method($db);
$paymentStatus = new Payment_Status($db);

$message = '';
$search = $_GET['search_payment'] ?? '';
$user_type = $_SESSION['user_type'] ?? null;

// Normalize user type
$user_type_lower = strtolower(str_replace('_', '', $user_type));
$is_superadmin = in_array($user_type_lower, ['superadmin', 'super_admin']);

// Access Control
if (!$is_superadmin) {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// Pagination settings
$records_per_page = 30;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

// Fetch dropdown data
$methods = $paymentMethod->getAllForDropdown();
$statuses = $paymentStatus->all();

// Fetch Appointments with Patient Names
$appointments = [];
try {
    $sql_appointments = "SELECT a.APPT_ID, 
                               CONCAT(p.PAT_LAST_NAME, ', ', p.PAT_FIRST_NAME) as patient_name,
                               DATE_FORMAT(a.APPT_DATE, '%Y-%m-%d') as appt_date
                         FROM appointment a
                         JOIN patient p ON a.PAT_ID = p.PAT_ID
                         ORDER BY a.APPT_ID DESC 
                         LIMIT 100";
    $stmt_appts = $db->query($sql_appointments);
    $appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load appointments: " . $e->getMessage());
    $message .= "⚠️ Could not load appointments. ";
}

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['add'])) {
        $data = [
            'appt_id'           => $_POST['appt_id'],
            'paymt_amount_paid' => $_POST['paymt_amount_paid'],
            'paymt_date'        => $_POST['paymt_date'],
            'pymt_meth_id'      => $_POST['pymt_meth_id'],
            'pymt_stat_id'      => $_POST['pymt_stat_id']
        ];
        
        $newId = $payment->create($data);
        
        if ($newId !== false && is_numeric($newId)) {
            $message = "✅ Payment record #{$newId} added successfully.";
        } else {
            $message = "❌ Failed to add payment record.";
        }
    }
    
    elseif (isset($_POST['update'])) {
        $data = [
            'paymt_id'          => $_POST['paymt_id'],
            'appt_id'           => $_POST['appt_id'], 
            'paymt_amount_paid' => $_POST['paymt_amount_paid'],
            'paymt_date'        => $_POST['paymt_date'],
            'pymt_meth_id'      => $_POST['pymt_meth_id'],
            'pymt_stat_id'      => $_POST['pymt_stat_id']
        ];

        error_log("Updating payment with data: " . json_encode($data));
        $success = $payment->update($data);
        $message = $success 
            ? "✅ Payment ID {$data['paymt_id']} updated successfully." 
            : "❌ Failed to update payment. Check error logs.";
    }
    
    elseif (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        $success = $payment->delete($id);
        $message = $success 
            ? "✅ Payment ID {$id} deleted successfully." 
            : "❌ Failed to delete payment.";
    }
}

// Fetch payment records WITH patient names directly from database
try {
    if (!empty($search)) {
        // Search query with patient name
        $sql = "SELECT 
                    p.PAYMT_ID as paymt_id,
                    p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                    p.PAYMT_DATE as paymt_date,
                    pm.PYMT_METH_NAME as pymt_meth_name,
                    ps.PYMT_STAT_NAME as pymt_stat_name,
                    a.APPT_ID as app_id,
                    CONCAT(pat.PAT_LAST_NAME, ', ', pat.PAT_FIRST_NAME) as patient_name,
                    DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at
                FROM payment p
                LEFT JOIN payment_method pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                LEFT JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                LEFT JOIN appointment a ON p.APPT_ID = a.APPT_ID
                LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                WHERE p.PAYMT_ID LIKE :search1
                   OR pm.PYMT_METH_NAME LIKE :search2
                   OR ps.PYMT_STAT_NAME LIKE :search3
                   OR CAST(p.PAYMT_AMOUNT_PAID AS CHAR) LIKE :search4
                   OR CONCAT(pat.PAT_LAST_NAME, ', ', pat.PAT_FIRST_NAME) LIKE :search5
                ORDER BY p.PAYMT_ID DESC";
        
        $stmt = $db->prepare($sql);
        $searchParam = '%' . trim($search) . '%';
        $stmt->execute([
            ':search1' => $searchParam,
            ':search2' => $searchParam,
            ':search3' => $searchParam,
            ':search4' => $searchParam,
            ':search5' => $searchParam
        ]);
        $all_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_records = count($all_records);
    } else {
        // Count total records
        $count_sql = "SELECT COUNT(*) as total FROM payment";
        $count_stmt = $db->query($count_sql);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Fetch paginated records with patient name
        $offset = ($current_page - 1) * $records_per_page;
        $sql = "SELECT 
                    p.PAYMT_ID as paymt_id,
                    p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                    p.PAYMT_DATE as paymt_date,
                    pm.PYMT_METH_NAME as pymt_meth_name,
                    ps.PYMT_STAT_NAME as pymt_stat_name,
                    a.APPT_ID as app_id,
                    CONCAT(pat.PAT_LAST_NAME, ', ', pat.PAT_FIRST_NAME) as patient_name,
                    DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at
                FROM payment p
                LEFT JOIN payment_method pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                LEFT JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                LEFT JOIN appointment a ON p.APPT_ID = a.APPT_ID
                LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                ORDER BY p.PAYMT_ID DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $all_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $display_records = $all_records;
    
} catch (Exception $e) {
    error_log("Failed to load payment records: " . $e->getMessage());
    $display_records = [];
    $total_records = 0;
}

// Calculate pagination
$total_pages = ceil($total_records / $records_per_page);

// Build URL params for search
$current_params = $_GET;
unset($current_params['search_payment']);
unset($current_params['page']);
$url_params = http_build_query($current_params);
?>

<h1 class="fw-bold mb-4">Payment Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false || strpos($message, '⚠️') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm border gap-3">
    <form class="d-flex w-100 w-md-50" method="GET">
        <?php foreach ($current_params as $k => $v): ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
        <input class="form-control me-2 rounded-pill border-primary" type="search" name="search_payment" placeholder="Search by ID, Method, Status, Amount, or Patient..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary text-nowrap" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?<?= $url_params ?>" class="btn btn-outline-secondary ms-2 rounded-pill text-nowrap">Reset</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-success text-nowrap" data-bs-toggle="collapse" data-bs-target="#addFormPayment">Add New Payment</button>
</div>

<div id="addFormPayment" class="collapse mb-4">
    <div class="card card-body shadow-sm border rounded bg-light">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Appointment & Patient *</label>
                <select name="appt_id" class="form-select" required>
                    <option value="">-- Select Appointment --</option>
                    <?php foreach ($appointments as $appt): ?>
                        <option value="<?= htmlspecialchars($appt['APPT_ID']) ?>">
                            #<?= htmlspecialchars($appt['APPT_ID']) ?> - <?= htmlspecialchars($appt['patient_name']) ?> (<?= htmlspecialchars($appt['appt_date']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Amount Paid *</label>
                <input type="number" step="0.01" name="paymt_amount_paid" class="form-control" placeholder="0.00" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Payment Date *</label>
                <input type="date" name="paymt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Method *</label>
                <select name="pymt_meth_id" class="form-select" required>
                    <option value="">-- Select Method --</option>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?= htmlspecialchars($method['pymt_meth_id']) ?>">
                            <?= htmlspecialchars($method['pymt_meth_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Payment Status *</label>
                <select name="pymt_stat_id" class="form-select" required>
                    <option value="">-- Select Status --</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= htmlspecialchars($status['pymt_stat_id']) ?>">
                            <?= htmlspecialchars($status['pymt_stat_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" name="add" class="btn btn-primary btn-lg">Save Payment Record</button>
            </div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Payment Records (Total: <?= $total_records ?>)</h5>
        <?php if (!$search && $total_pages > 1): ?>
            <small class="text-muted">Page <?= $current_page ?> of <?= $total_pages ?></small>
        <?php endif; ?>
    </div>
    
    <?php if (empty($display_records)): ?>
        <div class="alert alert-warning">
            No payment records found<?= $search ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>.
            <?php if ($search): ?>
                <br><a href="?<?= $url_params ?>" class="btn btn-sm btn-outline-primary mt-2">Clear Search</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Payment ID</th>
                    <th>Appt ID</th>
                    <th>Patient</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($display_records as $r): ?>
                <tr>
                    <form method="POST">
                        <td class="text-center fw-bold"><?= $r['paymt_id'] ?></td>
                        <td>
                            <input type="text" value="<?= htmlspecialchars($r['app_id']) ?>" class="form-control form-control-sm" readonly>
                        </td>
                        <td><?= htmlspecialchars($r['patient_name'] ?? 'N/A') ?></td>
                        <td>
                            <input type="number" step="0.01" name="paymt_amount_paid" value="<?= htmlspecialchars($r['paymt_amount_paid']) ?>" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <select name="pymt_meth_id" class="form-select form-select-sm" required>
                                <?php foreach ($methods as $method): 
                                    $is_selected = ($r['pymt_meth_name'] == $method['pymt_meth_name']); 
                                ?>
                                    <option value="<?= htmlspecialchars($method['pymt_meth_id']) ?>" <?= $is_selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($method['pymt_meth_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="pymt_stat_id" class="form-select form-select-sm" required>
                                <?php foreach ($statuses as $status): 
                                    $is_selected = ($r['pymt_stat_name'] == $status['pymt_stat_name']);
                                ?>
                                    <option value="<?= htmlspecialchars($status['pymt_stat_id']) ?>" <?= $is_selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status['pymt_stat_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php 
                                $payment_date_only = date('Y-m-d', strtotime($r['paymt_date']));
                            ?>
                            <input type="date" name="paymt_date" value="<?= htmlspecialchars($payment_date_only) ?>" class="form-control form-control-sm" required>
                        </td>
                        <td class="text-nowrap small text-muted"><?= $r['formatted_created_at'] ?? '-' ?></td>
                        <td class="text-center">
                            <input type="hidden" name="paymt_id" value="<?= $r['paymt_id'] ?>">
                            <input type="hidden" name="appt_id" value="<?= htmlspecialchars($r['app_id']) ?>">
                            <button type="submit" name="update" value="1" class="btn btn-sm btn-success mb-1">Update</button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deletePaymentModal" data-payment-id="<?= $r['paymt_id'] ?>">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (!$search && $total_pages > 1): ?>
    <!-- Pagination -->
    <nav aria-label="Payment records pagination" class="mt-3">
        <ul class="pagination justify-content-center flex-wrap">
            <!-- First Page -->
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $url_params ?>&page=1">First</a>
                </li>
            <?php endif; ?>
            
            <!-- Previous Page -->
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $url_params ?>&page=<?= $current_page - 1 ?>">Previous</a>
                </li>
            <?php endif; ?>
            
            <!-- Page Numbers (show 5 pages around current) -->
            <?php 
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $url_params ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next Page -->
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $url_params ?>&page=<?= $current_page + 1 ?>">Next</a>
                </li>
            <?php endif; ?>
            
            <!-- Last Page -->
            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= $url_params ?>&page=<?= $total_pages ?>">Last</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete Payment ID: <strong id="modalPaymentIdDisplay"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
                <form method="POST" id="deletePaymentForm">
                    <input type="hidden" name="delete" id="deletePaymentIdInput">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deletePaymentForm" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete modal
    document.querySelectorAll('[data-bs-target="#deletePaymentModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-payment-id');
            document.getElementById('modalPaymentIdDisplay').textContent = id;
            document.getElementById('deletePaymentIdInput').value = id;
        });
    });
});
</script>