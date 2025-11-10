<?php include '../includes/patient_header.php'; ?>

<h2>ACCOUNT SETTINGS</h2>

<div class="row g-4">
    <!-- ACCOUNT SETTINGS CARD -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center">
            <h5>ACCOUNT SETTINGS</h5>
            <p class="text-muted small mb-3">Edit your account details and change password</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">EDIT DETAILS</button>
        </div>
    </div>

    <!-- DELETE ACCOUNT CARD -->
    <div class="col-md-6">
        <div class="info-card p-4 text-center">
            <h5 class="text-danger">DELETE ACCOUNT</h5>
            <p class="text-muted small mb-3">Will permanently remove your account</p>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">DELETE ACCOUNT</button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Account Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <?php
                    // === FETCH FROM DB (UNCOMMENT WHEN READY) ===
                    // $stmt = $pdo->prepare("SELECT * FROM PATIENT WHERE PAT_ID = ?");
                    // $stmt->execute([$pat_id]);
                    // $patientData = $stmt->fetch();

                    // === FAKE DATA ===
                    $patientData = [
                        'PAT_ID' => '100035',
                        'PAT_FIRST_NAME' => 'ZZZ',
                        'PAT_MIDDLE_INIT' => 'A',
                        'PAT_LAST_NAME' => 'Test',
                        'PAT_DOB' => '2000-01-01',
                        'PAT_GENDER' => 'Male',
                        'PAT_CONTACT_NUM' => '09123456789',
                        'PAT_EMAIL' => 'zzz@test.com',
                        'PAT_ADDRESS' => 'Cebu City',
                        'PAT_CREATED_AT' => '2025-10-01 10:00:00'
                    ];
                    $age = floor((time() - strtotime($patientData['PAT_DOB'])) / 31556926);
                    ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?= $patientData['PAT_FIRST_NAME'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Initial</label>
                            <input type="text" class="form-control" name="middle_init" value="<?= $patientData['PAT_MIDDLE_INIT'] ?>" maxlength="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?= $patientData['PAT_LAST_NAME'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" value="<?= $patientData['PAT_DOB'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Age</label>
                            <input type="text" class="form-control" value="<?= $age ?> years" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="Male" <?= $patientData['PAT_GENDER'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $patientData['PAT_GENDER'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" name="contact" value="<?= $patientData['PAT_CONTACT_NUM'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= $patientData['PAT_EMAIL'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" value="<?= $patientData['PAT_ADDRESS'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter new password">
                            <small class="text-muted">Leave blank to keep current</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirm Account Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account?</p>
                <p class="text-danger"><strong>This action is permanent.</strong></p>
                <p class="text-muted small">Only your email and password will be removed. Other data will remain for records.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Account</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

</div> <!-- END .main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/patient_dashboard.js"></script>
<script>
    // Edit Form Submit
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Account updated successfully! (Simulated)');
        const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
        modal.hide();
        // In real: Send to PHP via AJAX
    });

    // Delete Account
    document.getElementById('confirmDelete').addEventListener('click', function() {
        alert('Account deleted. Redirecting...');
        window.location.href = '../index.php';
        // In real: Call delete endpoint, remove email/password only
    });
</script>
</body>
</html>