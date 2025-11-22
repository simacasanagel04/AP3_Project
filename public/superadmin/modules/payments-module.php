<?php
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

// Fetch payment records
$records = !empty($search) ? $payment->searchWithDetails($search) : $payment->all();

// Merge patient names into payment records
$display_records = [];
if (!empty($records)) {
    try {
        $appt_ids = array_filter(array_unique(array_column($records, 'app_id')));
        
        if (!empty($appt_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($appt_ids), '?'));
            
            $sql_patient_names = "SELECT a.APPT_ID as app_id, 
                                        CONCAT(p.PAT_LAST_NAME, ', ', p.PAT_FIRST_NAME) as patient_name
                                  FROM appointment a
                                  JOIN patient p ON a.PAT_ID = p.PAT_ID
                                  WHERE a.APPT_ID IN ({$ids_placeholder})";
            
            $stmt_names = $db->prepare($sql_patient_names);
            $stmt_names->execute($appt_ids);
            
            $patient_name_map = $stmt_names->fetchAll(PDO::FETCH_KEY_PAIR);
            
            foreach ($records as $record) {
                $app_id = $record['app_id'];
                $record['patient_name'] = $patient_name_map[$app_id] ?? 'N/A';
                $display_records[] = $record;
            }
        } else {
            $display_records = $records;
        }
    } catch (Exception $e) {
        error_log("Failed to load patient names: " . $e->getMessage());
        $display_records = $records;
    }
}

// Build URL params for search
$current_params = $_GET;
unset($current_params['search_payment']);
$url_params = http_build_query($current_params);
?>

<h1 class="fw-bold mb-4">Payment Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false || strpos($message, '⚠️') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm border">
    <form class="d-flex w-50" method="GET">
        <?php foreach ($current_params as $k => $v): ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
        <?php endforeach; ?>
        <input class="form-control me-2 rounded-pill border-primary" type="search" name="search_payment" placeholder="Search by ID, Method, Status, or Amount..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?<?= $url_params ?>" class="btn btn-outline-secondary ms-2 rounded-pill">Reset</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormPayment">Add New Payment</button>
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
    <h5>Payment Records (Total: <?= count($display_records) ?>)</h5>
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
                    <th>Actions</th>
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