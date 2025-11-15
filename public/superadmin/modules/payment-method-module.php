<?php
// Adjust the path as necessary to include your DB connection and Payment_Method class
require_once dirname(__DIR__, 3) . '/classes/Payment_Method.php';

// Initialize the Payment_Method object (assuming $db is defined in your main index/header)
$paymentMethod = new Payment_Method($db);

$message = '';
$search = $_GET['search_payment_method'] ?? '';
$user_type = $_SESSION['user_type'] ?? 'super_admin';

// Restrict access
if ($user_type !== 'super_admin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// Handle actions (Add, Update, Delete)
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
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormPaymentMethod">Add New Method</button>
</div>

<div id="addFormPaymentMethod" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="pymt_meth_name" class="form-control" placeholder="Method Name (e.g., Cash, Debit Card)" required>
            </div>
            <div class="col-md-2 text-end">
                <button type="submit" name="add" class="btn btn-primary w-100">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5>All Payment Methods</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mt-3">
            <thead class="table-light">
                <tr>
                    <th>ID</th><th>Method Name</th><th>Created At</th><th>Updated At</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="5" class="text-center">No payment methods found.</td></tr>
                <?php else: foreach ($records as $r): ?>
                    <tr>
                        <form method="POST">
                            <td><?= $r['pymt_meth_id'] ?></td>
                            <td><input name="pymt_meth_name" value="<?= htmlspecialchars($r['pymt_meth_name']) ?>" class="form-control form-control-sm" required></td>
                            <td><?= $r['formatted_created_at'] ?? '-' ?></td>
                            <td><?= $r['formatted_updated_at'] ?? '-' ?></td>
                            <td>
                                <input type="hidden" name="pymt_meth_id" value="<?= $r['pymt_meth_id'] ?>">
                                <button name="update" class="btn btn-sm btn-success mb-1 w-100">Update</button>
                                <button name="delete" value="<?= $r['pymt_meth_id'] ?>" class="btn btn-sm btn-danger w-100" onclick="return confirm('Delete this payment method? This cannot be undone.')">Delete</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>