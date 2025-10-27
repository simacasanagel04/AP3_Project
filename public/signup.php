<?php
// signup.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Patient.php';
require_once '../classes/User.php';

$db = (new Database())->connect();
$patient = new Patient($db);
$user = new User($db);

$errors = [];

//  GET PENDING PATIENT ID FROM REGISTRATION 
$pending_pat_id = $_SESSION['pending_pat_id'] ?? null;
$pending_email  = $_SESSION['pending_email']  ?? '';

if (!$pending_pat_id) {
    // No patient registered yet → redirect back
    header("Location: patient_create.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    //  VALIDATION 
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($password)) $errors[] = "Password is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    //  CHECK EMAIL MATCHES PENDING ONE (optional) 
    if ($email !== $pending_email) {
        $errors[] = "Email must match the one used in patient registration.";
    }

    if (empty($errors)) {
        //  CHECK IF EMAIL ALREADY REGISTERED 
        if ($user->emailExists($email)) {
            $errors[] = "This email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            //  CREATE USER ACCOUNT 
            if ($user->create([
                'user_name' => $email,
                'password'  => $hashed,
                'pat_id'    => $pending_pat_id
            ])) {
                //  LOGIN USER 
                $userData = $user->findByUsername($email);
                $_SESSION['user_id']   = $userData['USER_ID'];
                $_SESSION['pat_id']    = $pending_pat_id;
                $_SESSION['user_type'] = 'patient';
                $_SESSION['just_registered'] = true;

                // Clear pending data
                unset($_SESSION['pending_pat_id'], $_SESSION['pending_email']);

                header("Location: ../index.php");
                exit();
            } else {
                $errors[] = "Failed to create account. Try again.";
            }
        }
    }
}
?>
<!-- Content -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Times+New+Roman:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../classes/patient_style.css">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <img src="../assets/logo/logo.png" alt="AKSyon Medical Center" height="50" class="mb-3">
                        <h4 class="logo-font-aksyon text-primary">AKSyon</h4>
                        <p class="logo-font-medical text-muted">Medical Center</p>
                        <h5 class="mt-3" style="font-family: 'Times New Roman', serif;">Create Account</h5>
                        <p class="text-muted small">Complete your registration.</p>
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
                            <small class="text-muted">Must match patient registration email</small>
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
                            <a href="patient_create.php" class="btn btn-outline-secondary px-4">Back</a>
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