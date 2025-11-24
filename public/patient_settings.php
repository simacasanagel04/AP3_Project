<!-- public/patient_settings.php -->

<?php include '../includes/patient_header.php'; 

// Fetch user account details
try {
    $sqlUser = "SELECT USER_NAME FROM users WHERE PAT_ID = :pat_id";
    $stmtUser = $db->prepare($sqlUser);
    $stmtUser->execute([':pat_id' => $pat_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $userData = null;
}

// Calculate age from date of birth
$age = 0;
if (!empty($patientData['pat_dob'])) {
    $dob = new DateTime($patientData['pat_dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}
?>

<h2>ACCOUNT SETTINGS</h2>

<div class="row g-4">
    <!-- ACCOUNT SETTINGS CARD -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center">
            <i class="bi bi-person-gear display-4 text-primary mb-3"></i>
            <h5>ACCOUNT SETTINGS</h5>
            <p class="text-muted small mb-3">Edit your account details and change password</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="bi bi-pencil-square me-2"></i>EDIT DETAILS
            </button>
        </div>
    </div>

    <!-- VIEW ACCOUNT DETAILS CARD -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center">
            <i class="bi bi-person-badge display-4 text-info mb-3"></i>
            <h5>ACCOUNT DETAILS</h5>
            <p class="text-muted small mb-3">View your complete profile information</p>
            <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#viewModal">
                <i class="bi bi-eye me-2"></i>VIEW DETAILS
            </button>
        </div>
    </div>
</div>

<!-- VIEW DETAILS MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Account Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong><i class="bi bi-hash me-2"></i>Patient ID:</strong>
                        <p class="mb-0"><?= htmlspecialchars($pat_id) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-person me-2"></i>Full Name:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['pat_first_name'] . ' ' . 
                            (!empty($patientData['pat_middle_init']) ? $patientData['pat_middle_init'] . '. ' : '') . 
                            $patientData['pat_last_name']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-calendar-event me-2"></i>Date of Birth:</strong>
                        <p class="mb-0"><?= date('F d, Y', strtotime($patientData['pat_dob'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-hourglass-split me-2"></i>Age:</strong>
                        <p class="mb-0"><?= $age ?> years old</p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-gender-ambiguous me-2"></i>Gender:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['pat_gender']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-telephone me-2"></i>Contact Number:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['pat_contact_num']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-envelope me-2"></i>Email:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['pat_email'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-geo-alt me-2"></i>Address:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['pat_address']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-person-check me-2"></i>Username:</strong>
                        <p class="mb-0"><?= htmlspecialchars($userData['USER_NAME'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="bi bi-calendar-plus me-2"></i>Account Created:</strong>
                        <p class="mb-0"><?= htmlspecialchars($patientData['formatted_created_at']) ?></p>
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
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Account Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="pat_id" value="<?= $pat_id ?>">
                    
                    <!-- Personal Information Section -->
                    <h6 class="text-primary mb-3"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?= htmlspecialchars($patientData['pat_first_name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" name="middle_init" 
                                   value="<?= htmlspecialchars($patientData['pat_middle_init'] ?? '') ?>" 
                                   maxlength="1" style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?= htmlspecialchars($patientData['pat_last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="dob" 
                                   value="<?= $patientData['pat_dob'] ?>" 
                                   max="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Age</label>
                            <input type="text" class="form-control" value="<?= $age ?> years" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" name="gender" required>
                                <option value="Male" <?= $patientData['pat_gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $patientData['pat_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact" 
                                   value="<?= htmlspecialchars($patientData['pat_contact_num']) ?>" 
                                   pattern="[0-9]{11}" maxlength="11" 
                                   placeholder="09XXXXXXXXX" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($patientData['pat_email'] ?? '') ?>"
                                   placeholder="email@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" 
                                   value="<?= htmlspecialchars($patientData['pat_address']) ?>" required>
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <h6 class="text-primary mb-3"><i class="bi bi-shield-lock me-2"></i>Change Password</h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Leave password fields blank if you don't want to change your password.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="current_password" 
                                       id="current_password" placeholder="Enter current password">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('current_password')">
                                    <i class="bi bi-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" 
                                       id="new_password" placeholder="Enter new password"
                                       minlength="6">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('new_password')">
                                    <i class="bi bi-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" 
                                       id="confirm_password" placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="togglePassword('confirm_password')">
                                    <i class="bi bi-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div> <!-- END .main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/patient_dashboard.js"></script>
</body>
</html>