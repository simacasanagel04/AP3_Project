<?php
// ============================================
// CRITICAL: Check if accessed properly
// ============================================
if (!isset($db)) {
  die('
    <div class="alert alert-danger">
      <h4><i class="bi bi-exclamation-triangle"></i> Access Error</h4>
      <p><strong>This module cannot be accessed directly.</strong></p>
      <p>Please access it through the Super Admin Dashboard:</p>
      <p class="mb-0">
        <a href="../superadmin_dashboard.php?module=medical-record" class="btn btn-primary">
          Go to Dashboard → Medical Records
        </a>
      </p>
    </div>
  ');
}

// NOW your require_once statements can safely use $db
require_once __DIR__ . '/../../../classes/MedicalRecord.php';
require_once __DIR__ . '/../../../classes/Appointment.php';

$doctor = new Doctor($db);
$user = new User($db);

$message = '';
$userMessage = '';
$user_type = $_SESSION['user_type'] ?? 'super_admin';
$search = $_GET['search_doctor'] ?? '';
$newDoctor = null; // This will hold the newly added doctor's details for the modal

// --- Fetch Specializations ---
// ... (Specialization fetch block remains unchanged)
$specializations = [];
try {
    $stmt_spec = $db->query("SELECT spec_id, spec_name FROM specialization ORDER BY spec_name");
    $specializations = $stmt_spec->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load specializations: " . $e->getMessage());
    $message = "⚠️ Could not load specializations. Check database connection and 'specialization' table.";
}

// Restrict access
if ($user_type !== 'super_admin') {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin can access this module.</div>';
    return;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD DOCTOR (FIRST POST: DOCTOR RECORD CREATION)
    if (isset($_POST['add'])) {
        $data = [
            'doc_first_name'    => trim($_POST['doc_first_name']),
            'doc_middle_init' => trim($_POST['doc_middle_init']),
            'doc_last_name'   => trim($_POST['doc_last_name']),
            'doc_contact_num' => trim($_POST['doc_contact_num']),
            'doc_email'       => trim($_POST['doc_email']),
            'spec_id'         => $_POST['spec_id']
        ];

        $success = $doctor->create($data); // Assuming create() now returns boolean or error message
        if ($success === true) {
            $message = "✅ Doctor added successfully.";
            $lastId = $db->lastInsertId();
            
            // Fetch the newly created doctor's details for display in the modal
            $stmt = $db->prepare("SELECT doc_id, doc_email FROM doctor WHERE doc_id = ?");
            $stmt->execute([$lastId]);
            $newDoctor = $stmt->fetch(PDO::FETCH_ASSOC);

            // Store details in session for the modal to pick up after refresh
            if ($newDoctor) {
                 $_SESSION['new_doctor_id'] = $newDoctor['doc_id'];
                 $_SESSION['new_doctor_email'] = $newDoctor['doc_email'];
            }
        } else {
            // Error handling for doctor creation failure
            if (is_string($success) && strpos($success, 'DUPLICATE_CONTACT_NUMBER') !== false) {
                 $message = "❌ **Failed to add doctor.** The contact number **" . htmlspecialchars($data['doc_contact_num']) . "** is already registered to another doctor. Contact numbers must be unique.";
            } else {
                 $message = "❌ Failed to add doctor. **Error: " . htmlspecialchars($success) . "**";
            }
        }
    }
    
    // CREATE USER (SECOND POST: TRIGGERED BY MODAL BUTTON)
    elseif (isset($_POST['create_user'])) {
        $linked_id = $_POST['linked_id'] ?? null;
        $temp_password = $_POST['password'] ?? null; // Capture the temporary password
        
        if (empty($linked_id)) {
            $userMessage = "❌ Doctor ID missing. Please ensure the doctor record exists before creating the user account.";
        } else {
            $userData = [
                'user_name' => $_POST['user_name'],
                'password'  => password_hash($temp_password, PASSWORD_DEFAULT),
                'linked_id' => $linked_id,
                'doc_id'    => $linked_id, // Link to doctor table
                'user_type' => 'doctor'
            ];
            
            // Assuming $user->addLinkedAccount() returns true on success or an error string/false
            $userCreationResult = $user->addLinkedAccount($userData);

            if ($userCreationResult === true || (is_string($userCreationResult) && strpos($userCreationResult, '✅') !== false)) {
                 $userMessage = "✅ User account created for " . htmlspecialchars($userData['user_name']) . " with temporary password: **" . htmlspecialchars($temp_password) . "**";
                 unset($_SESSION['new_doctor_id']);
                 unset($_SESSION['new_doctor_email']);
            } else {
                 // Display the specific reason for user creation failure
                 $userMessage = "❌ Failed to create user account. Reason: " . htmlspecialchars($userCreationResult); 
            }
        }
    }

    // UPDATE
    elseif (isset($_POST['update'])) {
        $data = [
            'doc_id'          => $_POST['doc_id'],
            'doc_first_name'  => trim($_POST['doc_first_name']),
            'doc_middle_init' => trim($_POST['doc_middle_init']),
            'doc_last_name'   => trim($_POST['doc_last_name']),
            'doc_contact_num' => trim($_POST['doc_contact_num']),
            'doc_email'       => trim($_POST['doc_email']),
            'spec_id'         => $_POST['spec_id']
        ];

        $success = $doctor->update($data);
        $message = $success ? "✅ Doctor updated successfully." : "❌ Failed to update doctor.";
    }

    // DELETE
    elseif (isset($_POST['delete'])) {
        $id = $_POST['delete'];
        $success = $doctor->delete($id);
        // Also delete the linked user account if it exists
        $user->deleteLinkedAccount($id, 'doctor'); 
        $message = $success ? "✅ Doctor deleted successfully." : "❌ Failed to delete doctor.";
    }
}

// Fetch doctor records
$records = !empty($search)
    ? $doctor->searchWithAppointments($search)
    : $doctor->all();

// Password generator
function generateRandomPassword($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($chars), 0, $length);
}
?>
<h1 class="fw-bold mb-4">Doctor Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-info' ?>"><?= $message ?></div>
<?php endif; ?>

<?php if ($userMessage): ?>
<div class="alert alert-success"><?= $userMessage ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="GET">
        <input type="hidden" name="module" value="doctor">
        <input class="form-control me-2" type="search" name="search_doctor" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-primary" type="submit">Search</button>
        <?php if ($search): ?>
            <a href="?module=doctor" class="btn btn-outline-secondary ms-2">Reset</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormDoctor">Add New Doctor</button>
</div>

<div id="addFormDoctor" class="collapse mb-4">
    <div class="card card-body shadow-sm">
        <form method="POST" class="row g-3" onsubmit="return validateDoctorForm(this)">
            <div class="col-md-4"><input type="text" name="doc_first_name" class="form-control" placeholder="First Name" required></div>
            <div class="col-md-4"><input type="text" name="doc_middle_init" class="form-control" placeholder="Middle Initial"></div>
            <div class="col-md-4"><input type="text" name="doc_last_name" class="form-control" placeholder="Last Name" required></div>
            <div class="col-md-4"><input type="text" name="doc_contact_num" class="form-control" placeholder="Contact (09xxxxxxxxx)" pattern="^09\d{9}$" required></div>
            <div class="col-md-4"><input type="email" name="doc_email" class="form-control" placeholder="Email" required></div>
            <div class="col-md-4">
                <select name="spec_id" class="form-select" required>
                    <option value="">-- Select Specialization --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= htmlspecialchars($spec['spec_id']) ?>"><?= htmlspecialchars($spec['spec_name']) ?> (ID: <?= $spec['spec_id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12 text-end"><button type="submit" name="add" class="btn btn-primary">Save Doctor</button></div>
        </form>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5>All Doctor Records</h5>
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table table-bordered table-striped align-middle mt-3" style="min-width: 1200px;">
            <thead class="table-light">
                <tr>
                    <th>ID</th><th>First</th><th>Middle</th><th>Last</th>
                    <th>Contact</th><th>Email</th><th>Specialization</th>
                    <th>Created</th><th>Updated</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="10" class="text-center">No records found.</td></tr>
                <?php else: foreach ($records as $r): ?>
                    <tr>
                        <form method="POST" onsubmit="return validateDoctorForm(this)">
                            <td><?= $r['doc_id'] ?></td>
                            <td><input name="doc_first_name" value="<?= htmlspecialchars($r['doc_first_name']) ?>" class="form-control form-control-sm" required></td>
                            <td><input name="doc_middle_init" value="<?= htmlspecialchars($r['doc_middle_init']) ?>" class="form-control form-control-sm"></td>
                            <td><input name="doc_last_name" value="<?= htmlspecialchars($r['doc_last_name']) ?>" class="form-control form-control-sm" required></td>
                            <td><input name="doc_contact_num" value="<?= htmlspecialchars($r['doc_contact_num']) ?>" class="form-control form-control-sm" pattern="^09\d{9}$" required></td>
                            <td><input name="doc_email" value="<?= htmlspecialchars($r['doc_email']) ?>" class="form-control form-control-sm" type="email" required></td>
                            <td>
                                <select name="spec_id" class="form-select form-select-sm" required>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?= htmlspecialchars($spec['spec_id']) ?>" <?= ($r['spec_id'] == $spec['spec_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($spec['spec_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?= $r['formatted_created_at'] ?? '-' ?></td>
                            <td><?= $r['formatted_updated_at'] ?? '-' ?></td>
                            
                               <td class="text-center">
                                <input type="hidden" name="doc_id" value="<?= $r['doc_id'] ?>">                           
                                <button name="update" class="btn btn-sm btn-success mb-1 w-100">Update</button>                                
                                <button name="delete" value="<?= $r['doc_id'] ?>" 
                                        class="btn btn-sm btn-danger mb-1 w-100" 
                                        onclick="return confirm('Delete this doctor?')">
                                    Delete
                                </button>
                                   <a href="?module=appointment&doc_id=<?= $r['doc_id'] ?>" 
                                   class="btn btn-sm btn-info mb-1 w-100" 
                                   title="View Appointments for Doctor ID: <?= $r['doc_id'] ?>">
                                    View Appts
                                </a>
                            </td>

                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="autoUserDoctorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" class="p-3"> 
                <div class="modal-header">
                    <h5 class="modal-title">User Account Created</h5> 
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
                </div>
                <div class="modal-body">
                    <p>A doctor record was saved successfully. Please create a user account for them now.</p>
                    
                    <div class="mb-3">
                        <label>Username (Email):</label>
                        <input type="text" name="user_name" class="form-control" value="<?= htmlspecialchars($_SESSION['new_doctor_email'] ?? '') ?>" readonly required>
                    </div>
                    <div class="mb-3">
                        <label>Temporary Password:</label>
                        <?php $tempPassword = generateRandomPassword(); ?>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($tempPassword) ?>" readonly>
                        <input type="hidden" name="password" value="<?= htmlspecialchars($tempPassword) ?>">
                    </div>
                    
                    <input type="hidden" name="linked_id" value="<?= htmlspecialchars($_SESSION['new_doctor_id'] ?? '') ?>">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="create_user" class="btn btn-primary">Create Account</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateDoctorForm(f) {
    if (f.doc_email && !/^[^@]+@[^@]+\.[a-z]+$/i.test(f.doc_email.value)) {
        alert("Invalid email");
        return false;
    }
    if (f.doc_contact_num && !/^09\d{9}$/.test(f.doc_contact_num.value)) {
        alert("Invalid phone");
        return false;
    }
    return true;
}

// Show the modal only if a new doctor was just added (before the user account is created)
<?php 
// Only check for new_doctor_id. new_doctor_email might be empty if the record failed to save.
if (isset($_SESSION['new_doctor_id'])): 
?>
document.addEventListener("DOMContentLoaded", () => {
    // Check if the linked_id is present (meaning a doctor was just created)
    if (document.querySelector('input[name="linked_id"]').value) {
        const modal = new bootstrap.Modal(document.getElementById('autoUserDoctorModal'));
        modal.show();
    }
    
    // NOTE: Session variables are now unset *only* after successful user creation (in the PHP block)
    // or when the user closes the modal without creating an account (which we can't control easily).
});

// We need a way to clear the session if the user clicks close without creating the account.
// This client-side AJAX call handles the 'Close' button click.
document.getElementById('autoUserDoctorModal').addEventListener('hidden.bs.modal', function (event) {
    // Use the Fetch API to send a request to clear the session variables, 
    // but this would require a separate PHP endpoint or a GET request.
    // For simplicity, we rely on the user creation POST clearing it, or a page refresh clearing it eventually.
    
    // For now, let's keep the session clear logic server-side in the create_user block.
});

<?php endif; ?>
</script>