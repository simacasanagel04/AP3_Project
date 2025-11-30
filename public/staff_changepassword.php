<?php
// public/staff_changepassword.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Staff.php';

// Check if logged in BEFORE including header
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$staffModel = new Staff($db);

$success = $error = '';
if ($_POST) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        // Get current password from users table
        $currentPasswordHash = $staffModel->getStaffPassword($_SESSION['staff_id']);
        
        if (!$currentPasswordHash) {
            $error = "Unable to verify current password.";
        } else {
            // Check if password is hashed or plain text
            $passwordMatch = false;
            if (substr($currentPasswordHash, 0, 4) === '$2y$') {
                // Hashed password
                $passwordMatch = password_verify($current, $currentPasswordHash);
            } else {
                // Plain text password (old system)
                $passwordMatch = ($current === $currentPasswordHash);
            }
            
            if ($passwordMatch) {
                // Update password
                $newHashedPassword = password_hash($new, PASSWORD_DEFAULT);
                if ($staffModel->updatePassword($_SESSION['staff_id'], $newHashedPassword)) {
                $_SESSION['success'] = "Password changed successfully!";
                header("Location: staff_myprofile.php");
                exit();
            } else {
                    $error = "Failed to update password. Try again.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
}

// NOW include header after all logic
require_once '../includes/staff_header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Change Password</h4>
                </div>
                <div class="card-body p-5">

                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-key"></i> Update Password
                            </button>
                            <a href="staff_myprofile.php" class="btn btn-secondary btn-lg px-5">
                                Back to Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>