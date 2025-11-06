<?php
// public/signup.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Patient.php';
require_once '../classes/Doctor.php';
require_once '../classes/User.php';

$db = (new Database())->connect();
$patient = new Patient($db);
$doctor = new Doctor($db);
$user = new User($db);

$errors = [];

// Check if patient or doctor registration
$pending_pat_id = $_SESSION['pending_pat_id'] ?? null;
$pending_doc_id = $_SESSION['pending_doc_id'] ?? null;
$pending_email  = $_SESSION['pending_email']  ?? '';
$user_type = $_SESSION['pending_user_type'] ?? 'patient';

// If neither patient nor doctor registered, redirect
if (!$pending_pat_id && !$pending_doc_id) {
    header("Location: patient_create.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // VALIDATION
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password)) $errors[] = "Password is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    // CHECK EMAIL MATCHES PENDING ONE
    if ($email !== $pending_email) {
        $errors[] = "Email must match the one used in registration.";
    }

    if (empty($errors)) {
        // CHECK IF EMAIL ALREADY REGISTERED
        if ($user->emailExists($email)) {
            $errors[] = "This email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Prepare user data based on type
            $userData = [
                'user_name' => $email,
                'password'  => $hashed
            ];

            if ($pending_doc_id) {
                $userData['doc_id'] = $pending_doc_id;
            } else {
                $userData['pat_id'] = $pending_pat_id;
            }

            // CREATE USER ACCOUNT
            if ($user->create($userData)) {
                // LOGIN USER
                $userData = $user->findByUsername($email);
                $_SESSION['user_id']   = $userData['USER_ID'];
                $_SESSION['username']  = $email;
                
                if ($pending_doc_id) {
                    $_SESSION['doc_id']    = $pending_doc_id;
                    $_SESSION['user_type'] = 'doctor';
                    $redirect = 'doctor_dashb.php';
                } else {
                    $_SESSION['pat_id']    = $pending_pat_id;
                    $_SESSION['user_type'] = 'patient';
                    $redirect = '../index.php';
                }
                
                $_SESSION['just_registered'] = true;

                // Clear pending data
                unset($_SESSION['pending_pat_id'], $_SESSION['pending_doc_id'], 
                      $_SESSION['pending_email'], $_SESSION['pending_user_type']);

                header("Location: $redirect");
                exit();
            } else {
                $errors[] = "Failed to create account. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <a href="../index.php"><img src="../assets/logo/logo_white_bg.png" alt="AKSyon Medical Center" height="80"></a>
                        <h5 class="mt-3" style="font-family: 'Times New Roman', serif;">Create Account</h5>
                        <p class="text-muted small">Complete your registration as a <?= ucfirst($user_type) ?>.</p>
                    </div>

                    <!-- ERRORS -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 text-start">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- FORM -->
                    <form method="POST" action="">
                        <div class="mb-3 text-start">
                            <label class="form-label"><strong>Email:</strong></label>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email"
                                   value="<?= htmlspecialchars($pending_email) ?>" required>
                            <small class="text-muted">Must match registration email</small>
                        </div>

                        <div class="mb-3 text-start">
                            <label class="form-label"><strong>Password:</strong></label>
                            <input type="password" name="password" class="form-control" placeholder="Create password" required>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label"><strong>Confirm Password:</strong></label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <a href="<?= $pending_doc_id ? 'doctor_create.php' : 'patient_create.php' ?>" class="btn btn-outline-secondary px-4">Back</a>
                            <button type="submit" class="btn btn-primary px-4" style="background-color: #336d96; border: none;">SIGN UP</button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <small class="text-muted">
                            Already have an account? <a href="login.php" class="text-primary text-decoration-none">Login</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>