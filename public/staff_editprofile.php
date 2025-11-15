<?php
// public/staff_editprofile.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Staff.php';

// Redirect if not logged in (BEFORE including header)
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->connect();
$staffModel = new Staff($db);
$staff = $staffModel->getStaffById($_SESSION['staff_id']);

if (!$staff) {
    session_destroy();
    header("Location: login.php?error=invalid");
    exit();
}

// Handle form submission
$success = $error = '';
if ($_POST) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_init = strtoupper(trim($_POST['middle_init']));
    $contact_num = trim($_POST['contact_num']);
    $email = trim($_POST['email']);

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Update staff
        $staffModel->STAFF_ID = $_SESSION['staff_id'];
        $staffModel->STAFF_FIRST_NAME = $first_name;
        $staffModel->STAFF_LAST_NAME = $last_name;
        $staffModel->STAFF_MIDDLE_INIT = $middle_init;
        $staffModel->STAFF_CONTACT_NUM = $contact_num;
        $staffModel->STAFF_EMAIL = $email;

        if ($staffModel->updateProfile()) {
            $success = "Profile updated successfully!";
            // Refresh staff data
            $staff = $staffModel->getStaffById($_SESSION['staff_id']);
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// NOW include header after all logic
require_once '../includes/staff_header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-circle"></i> Edit Profile</h4>
                </div>
                <div class="card-body p-5">

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Middle Initial</label>
                                <input type="text" name="middle_init" class="form-control" maxlength="1" 
                                       value="<?php echo htmlspecialchars($staff['STAFF_MIDDLE_INIT'] ?? ''); ?>" placeholder="e.g. D">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?>" required>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">Contact Number</label>
                            <input type="text" name="contact_num" class="form-control" 
                                   value="<?php echo htmlspecialchars($staff['STAFF_CONTACT_NUM'] ?? ''); ?>" 
                                   placeholder="e.g. 09171234567">
                        </div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <a href="staff_myprofile.php" class="btn btn-secondary btn-lg px-5">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>