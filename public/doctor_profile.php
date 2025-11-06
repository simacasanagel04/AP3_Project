<?php
// public/doctor_profile.php

// session_start();
// require_once '../config/Database.php';
// require_once '../classes/Doctor.php';

// // COMMENTED OUT: Strict authentication check that prevents view access
// if (!isset($_SESSION['doc_id'])) {
//     header('Location: ../index.php');
//     exit;
// }

// $doc_id = $_SESSION['doc_id'];
// $database = new Database();
// $db = $database->connect();
// $doctorObj = new Doctor($db);

// $doctor = $doctorObj->findById($doc_id);
// // COMMENTED OUT: Check if doctor exists
// if (!$doctor || $doctor === 0) {
//     session_destroy();
//     header('Location: ../index.php');
//     exit;
// }

// --- START FAKE DATA BLOCK ---
// Using fake data to ensure the page loads for design/editing purposes.
$doc_id = 1; 
$doctor = [
    'doc_first_name' => 'John',
    'doc_middle_init' => 'R',
    'doc_last_name' => 'Doe',
    'doc_email' => 'john.doe@fakeclinic.com',
    'doc_contact_num' => '09123456789',
    'spec_name' => 'General Medicine',
];

// Define doctorName for the header (since it's not being dynamically fetched)
$doctorName = "Dr. {$doctor['doc_first_name']} {$doctor['doc_last_name']}";
// --- END FAKE DATA BLOCK ---


// Handle form submissions - ENTIRE BLOCK COMMENTED OUT to remove strict DB dependency
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (isset($_POST['action']) && $_POST['action'] === 'update') {
//         // $result = $doctorObj->updateProfile(
//         //     $doc_id,
//         //     $_POST['first_name'],
//         //     strtoupper($_POST['middle_init'] ?? ''),
//         //     $_POST['last_name'],
//         //     $_POST['contact'],
//         //     $_POST['email'],
//         //     $_POST['new_password'] ?? ''
//         // );
//         // echo json_encode(['success' => $result, 'message' => $result ? 'Profile updated!' : 'Update failed.']);
//         // exit;
//     }

//     if (isset($_POST['action']) && $_POST['action'] === 'delete') {
//         // $result = $doctorObj->deleteAccount($doc_id);
//         // if ($result) {
//         //     session_destroy();
//         //     echo json_encode(['success' => true, 'redirect' => '../index.php']);
//         // } else {
//         //     echo json_encode(['success' => false, 'message' => 'Deletion failed.']);
//         // }
//         // exit;
//     }
// }

require_once '../includes/doctor_header.php';
?>

<h2 class="mb-4 text-white">DOCTOR PROFILE</h2>

<div class="row g-4">
    <!-- ACCOUNT SETTINGS -->
    <div class="col-md-6 col-lg-4">
        <div class="info-card p-4 text-center cursor-pointer" data-bs-toggle="modal" data-bs-target="#editModal">
            <i class="bi bi-gear-fill text-primary" style="font-size: 2rem;"></i>
            <h5 class="mt-3 mb-1">Account Settings</h5>
            <p class="text-muted small mb-0">Edit your Account Details & Change Password</p>
        </div>
    </div>

    <!-- VIEW DETAILS -->
    <div class="col-md-6 col-lg-4">
        <div class="info-card p-4 text-center cursor-pointer" data-bs-toggle="modal" data-bs-target="#viewModal">
            <i class="bi bi-eye-fill text-success" style="font-size: 2rem;"></i>
            <h5 class="mt-3 mb-1">View Account Details</h5>
            <p class="text-muted small mb-0">View Personal Information About Your Account</p>
        </div>
    </div>

    <!-- DELETE ACCOUNT -->
    <div class="col-md-6 col-lg-4">
        <div class="info-card p-4 text-center cursor-pointer" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-person-x-fill text-danger" style="font-size: 2rem;"></i>
            <h5 class="mt-3 mb-1">Delete Account</h5>
            <p class="text-muted small mb-0">Will Permanently Remove your Account</p>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Account Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Full Name</label>
                        <!-- Updated to use fake data -->
                        <p class="form-control-plaintext"><?= htmlspecialchars("Dr. {$doctor['doc_first_name']} {$doctor['doc_middle_init']}. {$doctor['doc_last_name']}") ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Specialization</label>
                        <!-- Updated to use fake data -->
                        <p class="form-control-plaintext"><?= htmlspecialchars($doctor['spec_name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Email (Login)</label>
                        <!-- Updated to use fake data -->
                        <p class="form-control-plaintext"><?= htmlspecialchars($doctor['doc_email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Contact Number</label>
                        <!-- Updated to use fake data -->
                        <p class="form-control-plaintext"><?= htmlspecialchars($doctor['doc_contact_num'] ?? 'Not set') ?></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editForm">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Account Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <!-- Updated to use fake data -->
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($doctor['doc_first_name']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">M.I.</label>
                            <!-- Updated to use fake data -->
                            <input type="text" class="form-control" name="middle_init" value="<?= htmlspecialchars($doctor['doc_middle_init']) ?>" maxlength="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <!-- Updated to use fake data -->
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($doctor['doc_last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email (Login)</label>
                            <!-- Updated to use fake data -->
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($doctor['doc_email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <!-- Updated to use fake data -->
                            <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($doctor['doc_contact_num'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="new_password" minlength="6">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Clear</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="deleteForm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong>permanently delete</strong> your account?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                    <input type="hidden" name="action" value="delete">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
                </div>
            </div>
        </form>
    </div>
</div>

</div><!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/doctor_dashboard.js"></script>

<script>
// Handle Edit & Delete via AJAX (NOTE: PHP POST handler is commented out, so these will currently just send data but not persist it)
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            // Using console.log instead of alert for better debugging experience
            console.log('Update attempt sent:', data);
            // alert(data.message || 'Profile update simulated (No DB connection)');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        });
});

document.getElementById('deleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!confirm('This action is IRREVERSIBLE. Continue?')) return;
    const formData = new FormData(this);
    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.redirect) {
                // Since the PHP logic is commented out, this branch won't execute naturally.
                // alert('Account deletion simulated.');
                // window.location.href = data.redirect;
            } else {
                // alert(data.message || 'Deletion failed (No DB connection)');
                console.log('Delete attempt sent:', data);
            }
        });
});

// Replaced original JS alerts with custom modal message boxes or console logs for better user experience.
function confirm(message) {
    return window.confirm(message); // Using native confirm as a quick fix for a dev environment
}

</script>
</body>
</html>
