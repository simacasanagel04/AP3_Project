<?php
// payment-method-module.php
require_once dirname(__DIR__, 3) . '/classes/Payment_Method.php';

// NOTE: $db and $_SESSION['user_type'] are assumed to be available.

$paymentMethod = new Payment_Method($db);
$message = '';
$user_type = $_SESSION['user_type'] ?? '';
$search = $_GET['search_payment_method'] ?? '';

// --- ACCESS CONTROL ---
if ($user_type !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CREATE (Add new payment method)
    if (isset($_POST['add'])) {
        $data = ['pymt_meth_name' => trim($_POST['pymt_meth_name'])];
        $result = $paymentMethod->create($data);

        if ($result) {
            $message = "✅ Payment Method '{$data['pymt_meth_name']}' added successfully.";
        } else {
            $message = "❌ Failed to add payment method. The name might already exist or a database error occurred.";
        }
    }

    // UPDATE (Edit payment method)
    elseif (isset($_POST['update'])) {
        $data = [
            'pymt_meth_id'   => $_POST['pymt_meth_id'],
            'pymt_meth_name' => trim($_POST['pymt_meth_name'])
        ];
        $success = $paymentMethod->update($data);
        $message = $success ? "✅ Payment Method updated successfully." : "❌ Failed to update payment method.";
    }

    // DELETE (Delete payment method)
    elseif (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        $success = $paymentMethod->delete($id);
        $message = $success ? "✅ Payment Method deleted successfully." : "❌ Failed to delete payment method.";
    }
}

// Fetch records
$records = !empty($search)
    ? $paymentMethod->search($search)
    : $paymentMethod->all();
?>

<h1 class="fw-bold mb-4">Payment Method Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="GET">
        <input type="hidden" name="module" value="payment-method">
        <input class="form-control me-2" type="search" name="search_payment_method" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?module=payment-method" class="btn btn-outline-secondary ms-2">Reset</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormPaymentMethod">+ Add New Method</button>
</div>

<div id="addFormPaymentMethod" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="pymt_meth_name" class="form-control" placeholder="Method Name (e.g., Cash, Debit Card) *" required>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" name="add" class="btn btn-primary w-100">💾 Save</button>
            </div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5 class="mb-0">All Payment Methods (<?= count($records) ?>)</h5>
    <div class="table-responsive mt-3">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Method Name</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No payment methods found.</td></tr>
                <?php else: foreach ($records as $r): ?>
                    <tr>
                        <form method="POST">
                            <td class="text-center"><?= $r['pymt_meth_id'] ?></td>
                            <td>
                                <input type="hidden" name="pymt_meth_id" value="<?= $r['pymt_meth_id'] ?>">
                                <input name="pymt_meth_name" value="<?= htmlspecialchars($r['pymt_meth_name']) ?>" class="form-control form-control-sm" required>
                            </td>
                            <td class="text-center small"><?= $r['formatted_created_at'] ?? '-' ?></td>
                            <td class="text-center small"><?= $r['formatted_updated_at'] ?? '-' ?></td>
                            <td>
                                <button name="update" class="btn btn-sm btn-success mb-1 w-100">✏️ Update</button>
                                <button name="delete" value="<?= $r['pymt_meth_id'] ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('⚠️ Delete this payment method? This cannot be undone.')">🗑️ Delete</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>