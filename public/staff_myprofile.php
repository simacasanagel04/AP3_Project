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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | AKSyon Medical Center</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fa; }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .profile-avatar { width: 100px; height: 100px; font-size: 3rem; color: #667eea; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .info-label { width: 180px; }
        footer { background: #e5e2e2; border-top: 1px solid #ddd; }
        @media (max-width: 768px) {
            .info-label { width: 100%; }
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <main class="container mt-4 mb-5 flex-fill">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show text-center">
                <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card rounded-4 border-0 shadow">
                    <!-- Profile Header -->
                    <div class="profile-header text-white text-center rounded-top-4 p-4">
                        <div class="profile-avatar bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h2 class="fw-bold mb-2">
                            <?php echo htmlspecialchars($staff['STAFF_FIRST_NAME'] . ' ' . $staff['STAFF_LAST_NAME']); ?>
                        </h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-envelope me-2"></i>
                            <?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?>
                        </p>
                    </div>

                    <!-- Profile Body -->
                    <div class="card-body p-4 p-md-5">
                        <h5 class="text-primary fw-bold mb-4">
                            <i class="bi bi-person-vcard me-2"></i>Personal Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-hash text-primary"></i> Staff ID:
                                    </span>
                                    <span class="text-dark">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($staff['STAFF_ID']); ?></span>
                                    </span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-person text-primary"></i> First Name:
                                    </span>
                                    <span class="text-dark"><?php echo htmlspecialchars($staff['STAFF_FIRST_NAME']); ?></span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-person text-primary"></i> Last Name:
                                    </span>
                                    <span class="text-dark"><?php echo htmlspecialchars($staff['STAFF_LAST_NAME']); ?></span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-alphabet text-primary"></i> Middle Initial:
                                    </span>
                                    <span class="text-dark"><?php echo htmlspecialchars($staff['STAFF_MIDDLE_INIT'] ?? 'N/A'); ?></span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-calendar-plus text-primary"></i> Date Registered:
                                    </span>
                                    <span class="text-dark"><?php echo date('F j, Y', strtotime($staff['STAFF_CREATED_AT'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-envelope text-success"></i> Email:
                                    </span>
                                    <span class="text-dark"><?php echo htmlspecialchars($staff['STAFF_EMAIL']); ?></span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-telephone text-success"></i> Contact Number:
                                    </span>
                                    <span class="text-dark"><?php echo htmlspecialchars($staff['STAFF_CONTACT_NUM'] ?? 'N/A'); ?></span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-clock-history text-success"></i> Last Updated:
                                    </span>
                                    <span class="text-dark">
                                        <?php echo $staff['STAFF_UPDATED_AT'] ? date('F j, Y g:i A', strtotime($staff['STAFF_UPDATED_AT'])) : '<span class="text-muted">Never</span>'; ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex flex-column flex-sm-row mb-3">
                                    <span class="info-label fw-semibold text-secondary">
                                        <i class="bi bi-check-circle text-success"></i> Status:
                                    </span>
                                    <span class="text-dark">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill me-1"></i>Active
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Action Buttons -->
                        <div class="text-center">
                            <a href="staff_editprofile.php" class="btn btn-primary btn-lg px-4 me-0 me-md-3 mb-2 mb-md-0">
                                <i class="bi bi-pencil-square me-2"></i>Edit Profile
                            </a>
                            <a href="staff_changepassword.php" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="bi bi-key me-2"></i>Change Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-3 mt-auto">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 text-center text-md-start mb-2 mb-md-0">
                    <p class="mb-0 text-black">© 2025 AKSyon Medical Center. All rights reserved.</p>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="https://www.facebook.com/" class="text-black mx-2" target="_blank">
                        <i class="bi bi-facebook fs-5"></i>
                    </a>
                    <a href="https://www.instagram.com/" class="text-black mx-2" target="_blank">
                        <i class="bi bi-instagram fs-5"></i>
                    </a>
                    <a href="https://www.linkedin.com/" class="text-black mx-2" target="_blank">
                        <i class="bi bi-linkedin fs-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (CRITICAL FOR DROPDOWN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>