<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/User.php';
require_once '../classes/Patient.php';

$db = (new Database())->connect();
$user = new User($db);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        $userData = $user->findByUsername($email);

        if ($userData && password_verify($password, $userData['PASSWORD'])) {
            $_SESSION['user_id']   = $userData['USER_ID'];
            $_SESSION['pat_id']    = $userData['PAT_ID'];
            $_SESSION['user_type'] = 'patient';

            header("Location: patient_dashb.php");
            exit();
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../classes/patient_style.css">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-lg-5 col-md-7 col-sm-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <a href="../index.php"><img src="../assets/logo/logo_white_bg.png" alt="AKSyon Medical Center" height="80" class="mb-3"></a>
                        <h5 class="mt-3" style="font-family: 'Times New Roman', serif;">Welcome Back</h5>
                        <p class="text-muted small">Login to your account.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 text-start">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3 text-start">
                            <label class="form-label"><strong>Email:</strong></label>
                            <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                        </div>

                        <div class="mb-4 text-start">
                            <label class="form-label"><strong>Password:</strong></label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="background-color: #336d96; border: none;">
                            LOGIN
                        </button>
                    </form>

                    <div class="mt-4">
                        <small class="text-muted">
                            Don't have an account? <a href="patient_create.php" class="text-primary text-decoration-none">Register</a>
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