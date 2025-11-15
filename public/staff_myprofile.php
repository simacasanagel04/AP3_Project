<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Staff.php';

// Redirect if not logged in (BEFORE including header)
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to database
$database = new Database();
$db = $database->connect();

// Get staff info
$staffModel = new Staff($db);
$staff = $staffModel->getStaffById($_SESSION['staff_id']);

if (!$staff) {
    session_destroy();
    header("Location: login.php?error=invalid");
    exit();
}

// NOW include header AFTER all redirects
require_once '../includes/staff_header.php';
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            Welcome, <?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?>!
                        </h2>
                        <p class="text-muted"><?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr><th width="180">Staff ID:</th><td><?php echo htmlspecialchars($staff['STAFF_ID']); ?></td></tr>
                                <tr><th>First Name:</th><td><?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?></td></tr>
                                <tr><th>Last Name:</th><td><?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?></td></tr>
                                <tr><th>Middle Initial:</th><td><?php echo htmlspecialchars($staff['STAFF_MIDDLE_INIT'] ?? 'N/A'); ?></td></tr>
                                <tr><th>Date Registered:</th><td><?php echo date('F j, Y', strtotime($staff['STAFF_CREATED_AT'])); ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr><th>Email:</th><td><?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?></td></tr>
                                <tr><th>Contact Number:</th><td><?php echo htmlspecialchars($staff['STAFF_CONTACT_NUM'] ?? 'N/A'); ?></td></tr>
                                <tr><th>Last Updated:</th><td><?php echo $staff['STAFF_UPDATED_AT'] ? date('F j, Y g:i A', strtotime($staff['STAFF_UPDATED_AT'])) : 'Never'; ?></td></tr>
                                <tr><th>Status:</th><td><span class="badge bg-success fs-6">Active</span></td></tr>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="staff_editprofile.php" class="btn btn-primary btn-lg px-4 me-3">
                            Edit Profile
                        </a>
                        <a href="staff_changepassword.php" class="btn btn-outline-secondary btn-lg px-4">
                            Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row align-items-center small p-3 px-5" style="background-color: #e5e2e2;">
    <div class="col-md-8 text-center text-md-start ps-5">
        <p class="mb-0 text-black">© 2025 AKSyon Medical Center. All rights reserved.</p>
    </div>
    <div class="col-md-4 text-center text-md-end pe-5">
        <div class="social-links">
            <a href="https://www.facebook.com/" class="text-black mx-2"><i class="bi bi-facebook fs-5"></i></a>
            <a href="https://www.instagram.com/" class="text-black mx-2"><i class="bi bi-instagram fs-5"></i></a>
            <a href="https://www.linkedin.com/" class="text-black mx-2"><i class="bi bi-linkedin fs-5"></i></a>
        </div>
    </div>
</div>