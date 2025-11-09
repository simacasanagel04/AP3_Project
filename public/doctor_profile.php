<?php
// public/doctor_profile.php
session_start();
require_once '../config/Database.php';
require_once '../classes/Doctor.php';

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doc_id = $_SESSION['doc_id'];
$database = new Database();
$db = $database->connect();
$doctorObj = new Doctor($db);

// Handle AJAX form submission - MUST BE BEFORE ANY HTML OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    header('Content-Type: application/json');
    
    $firstName = trim($_POST['first_name']);
    $middleInit = trim($_POST['middle_init']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $specId = intval($_POST['spec_id']);
    $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $oldPassword = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    
    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
        exit;
    }
    
    // If password change requested, verify old password
    $updatePassword = false;
    if (!empty($newPassword)) {
        if (empty($oldPassword)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your current password']);
            exit;
        }
        
        // Verify old password
        try {
            $sqlUser = "SELECT u.PASSWORD FROM users u 
                       INNER JOIN doctor d ON u.DOC_ID = d.DOC_ID 
                       WHERE d.DOC_ID = :doc_id";
            $stmtUser = $db->prepare($sqlUser);
            $stmtUser->execute([':doc_id' => $doc_id]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($oldPassword, $user['PASSWORD'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            $updatePassword = true;
        } catch (PDOException $e) {
            error_log("Error verifying password: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error verifying password']);
            exit;
        }
    }
    
    try {
        $db->beginTransaction();
        
        // Update doctor table
        $sqlUpdate = "UPDATE doctor SET 
                     DOC_FIRST_NAME = :first_name,
                     DOC_MIDDLE_INIT = :middle_init,
                     DOC_LAST_NAME = :last_name,
                     DOC_EMAIL = :email,
                     DOC_CONTACT_NUM = :contact,
                     SPEC_ID = :spec_id,
                     DOC_UPDATED_AT = NOW()
                     WHERE DOC_ID = :doc_id";
        
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':first_name' => $firstName,
            ':middle_init' => strtoupper(substr($middleInit, 0, 1)),
            ':last_name' => $lastName,
            ':email' => $email,
            ':contact' => $contact,
            ':spec_id' => $specId,
            ':doc_id' => $doc_id
        ]);
        
        // Update users table email
        $sqlUpdateUser = "UPDATE users SET USER_NAME = :email";
        if ($updatePassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sqlUpdateUser .= ", PASSWORD = :password";
        }
        $sqlUpdateUser .= ", USER_UPDATED_AT = NOW() WHERE DOC_ID = :doc_id";
        
        $stmtUpdateUser = $db->prepare($sqlUpdateUser);
        $params = [':email' => $email, ':doc_id' => $doc_id];
        if ($updatePassword) {
            $params[':password'] = $hashedPassword;
        }
        $stmtUpdateUser->execute($params);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error updating profile: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()]);
        exit;
    }
}

// Fetch doctor data with specialization
try {
    $sql = "SELECT d.*, s.SPEC_NAME, s.SPEC_ID
            FROM doctor d
            LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE d.DOC_ID = :doc_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching doctor: " . $e->getMessage());
    $doctor = null;
}

if (!$doctor) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Fetch all specializations for dropdown
try {
    $sqlSpec = "SELECT SPEC_ID, SPEC_NAME FROM specialization ORDER BY SPEC_NAME";
    $stmtSpec = $db->query($sqlSpec);
    $specializations = $stmtSpec->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching specializations: " . $e->getMessage());
    $specializations = [];
}

$fullName = trim("{$doctor['DOC_FIRST_NAME']} {$doctor['DOC_MIDDLE_INIT']}. {$doctor['DOC_LAST_NAME']}");
$specialization = $doctor['SPEC_NAME'] ?? 'General';

require_once '../includes/doctor_header.php';
?>

<h2 class="mb-4 text-white">DOCTOR PROFILE</h2>

<div class="row g-4">
    <!-- ACCOUNT SETTINGS -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center cursor-pointer" data-bs-toggle="modal" data-bs-target="#editModal">
            <i class="bi bi-gear-fill text-primary" style="font-size: 2rem;"></i>
            <h5 class="mt-3 mb-1">Account Settings</h5>
            <p class="text-muted small mb-0">Edit your Account Details & Change Password</p>
        </div>
    </div>

    <!-- VIEW DETAILS -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center cursor-pointer" data-bs-toggle="modal" data-bs-target="#viewModal">
            <i class="bi bi-eye-fill text-success" style="font-size: 2rem;"></i>
            <h5 class="mt-3 mb-1">View Account Details</h5>
            <p class="text-muted small mb-0">View Personal Information About Your Account</p>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-badge"></i> Account Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="row g-4">
                    <div class="col-12 text-center mb-3">
                        <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2.5rem;">
                            <i class="bi bi-person-circle"></i>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-person-fill"></i> Full Name</label>
                        <p class="form-control-plaintext fw-bold">Dr. <?= htmlspecialchars($fullName) ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-bookmark-star-fill"></i> Specialization</label>
                        <p class="form-control-plaintext fw-bold"><?= htmlspecialchars($specialization) ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-envelope-fill"></i> Email (Login)</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($doctor['DOC_EMAIL']) ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-telephone-fill"></i> Contact Number</label>
                        <p class="form-control-plaintext"><?= htmlspecialchars($doctor['DOC_CONTACT_NUM'] ?? 'Not set') ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-calendar-plus-fill"></i> Account Created</label>
                        <p class="form-control-plaintext"><?= date('M d, Y', strtotime($doctor['DOC_CREATED_AT'])) ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted"><i class="bi bi-clock-history"></i> Last Updated</label>
                        <p class="form-control-plaintext"><?= $doctor['DOC_UPDATED_AT'] ? date('M d, Y', strtotime($doctor['DOC_UPDATED_AT'])) : 'Never' ?></p>
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
        <form id="editProfileForm">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Account Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i> Leave password fields blank if you don't want to change your password
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-person"></i> First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($doctor['DOC_FIRST_NAME']) ?>" required>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">M.I.</label>
                            <input type="text" class="form-control text-center" name="middle_init" value="<?= htmlspecialchars($doctor['DOC_MIDDLE_INIT']) ?>" maxlength="1">
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label"><i class="bi bi-person"></i> Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($doctor['DOC_LAST_NAME']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-envelope"></i> Email (Login) <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($doctor['DOC_EMAIL']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-telephone"></i> Contact Number</label>
                            <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($doctor['DOC_CONTACT_NUM'] ?? '') ?>" maxlength="11" pattern="[0-9]{11}" placeholder="09123456789">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label"><i class="bi bi-bookmark-star"></i> Specialization <span class="text-danger">*</span></label>
                            <select class="form-select" name="spec_id" required>
                                <option value="">Select Specialization</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?= $spec['SPEC_ID'] ?>" <?= ($doctor['SPEC_ID'] == $spec['SPEC_ID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($spec['SPEC_NAME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <hr>
                            <h6 class="text-muted"><i class="bi bi-shield-lock"></i> Change Password (Optional)</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-lock"></i> Current Password</label>
                            <input type="password" class="form-control" name="old_password" id="old_password" minlength="6">
                            <small class="text-muted">Required only if changing password</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-key"></i> New Password</label>
                            <input type="password" class="form-control" name="new_password" id="new_password" minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>

</div><!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/doctor_dashboard.js"></script>

<script>
// DOCTOR PROFILE - EDIT FORM AJAX SUBMISSION
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    
    fetch('doctor_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
            
            // Close the edit modal
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            editModal.hide();
            
            // Refresh the page to show updated data
            location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating profile');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

// DOCTOR PROFILE - REFRESH VIEW MODAL DATA
document.getElementById('viewModal').addEventListener('show.bs.modal', function() {
    // Fetch fresh data when opening view modal
    fetch('ajax/get_doctor_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const doctor = data.doctor;
                const fullName = `${doctor.DOC_FIRST_NAME} ${doctor.DOC_MIDDLE_INIT}. ${doctor.DOC_LAST_NAME}`;
                const specialization = doctor.SPEC_NAME || 'General';
                const createdDate = new Date(doctor.DOC_CREATED_AT).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                const updatedDate = doctor.DOC_UPDATED_AT ? new Date(doctor.DOC_UPDATED_AT).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }) : 'Never';
                
                // Update modal content
                document.getElementById('viewModalBody').innerHTML = `
                    <div class="row g-4">
                        <div class="col-12 text-center mb-3">
                            <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2.5rem;">
                                <i class="bi bi-person-circle"></i>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-person-fill"></i> Full Name</label>
                            <p class="form-control-plaintext fw-bold">Dr. ${fullName}</p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-bookmark-star-fill"></i> Specialization</label>
                            <p class="form-control-plaintext fw-bold">${specialization}</p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-envelope-fill"></i> Email (Login)</label>
                            <p class="form-control-plaintext">${doctor.DOC_EMAIL}</p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-telephone-fill"></i> Contact Number</label>
                            <p class="form-control-plaintext">${doctor.DOC_CONTACT_NUM || 'Not set'}</p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-calendar-plus-fill"></i> Account Created</label>
                            <p class="form-control-plaintext">${createdDate}</p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label text-muted"><i class="bi bi-clock-history"></i> Last Updated</label>
                            <p class="form-control-plaintext">${updatedDate}</p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching profile:', error);
        });
});
</script>

</body>
</html>