<?php
// public/ajax/patient_update_account.php 
// for public/patient_settings.php 

session_start();
require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['pat_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$database = new Database();
$db = $database->connect();
$pat_id = $_SESSION['pat_id'];

try {
    // Validate required fields
    $required = ['first_name', 'last_name', 'dob', 'gender', 'contact', 'address'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            exit;
        }
    }

    // Start transaction
    $db->beginTransaction();

    // Update patient table
    $sqlPatient = "UPDATE patient SET 
                    PAT_FIRST_NAME = :first_name,
                    PAT_MIDDLE_INIT = :middle_init,
                    PAT_LAST_NAME = :last_name,
                    PAT_DOB = :dob,
                    PAT_GENDER = :gender,
                    PAT_CONTACT_NUM = :contact,
                    PAT_EMAIL = :email,
                    PAT_ADDRESS = :address,
                    PAT_UPDATED_AT = NOW()
                   WHERE PAT_ID = :pat_id";

    $stmtPatient = $db->prepare($sqlPatient);
    $stmtPatient->execute([
        ':first_name' => trim($_POST['first_name']),
        ':middle_init' => !empty($_POST['middle_init']) ? strtoupper(trim($_POST['middle_init'])) : null,
        ':last_name' => trim($_POST['last_name']),
        ':dob' => $_POST['dob'],
        ':gender' => $_POST['gender'],
        ':contact' => trim($_POST['contact']),
        ':email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
        ':address' => trim($_POST['address']),
        ':pat_id' => $pat_id
    ]);

    // Handle password change if provided
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        // Verify current password
        $sqlCheckPwd = "SELECT PASSWORD FROM users WHERE PAT_ID = :pat_id";
        $stmtCheck = $db->prepare($sqlCheckPwd);
        $stmtCheck->execute([':pat_id' => $pat_id]);
        $user = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($_POST['current_password'], $user['PASSWORD'])) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        // Verify new password confirmation
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }

        // Validate password length
        if (strlen($_POST['new_password']) < 6) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }

        // Update password
        $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $sqlUpdatePwd = "UPDATE users SET PASSWORD = :password, USER_UPDATED_AT = NOW() 
                        WHERE PAT_ID = :pat_id";
        $stmtPwd = $db->prepare($sqlUpdatePwd);
        $stmtPwd->execute([
            ':password' => $hashedPassword,
            ':pat_id' => $pat_id
        ]);

        $passwordChanged = true;
    } else {
        $passwordChanged = false;
    }

    // Commit transaction
    $db->commit();

    $message = 'Account updated successfully!';
    if ($passwordChanged) {
        $message .= ' Password has been changed.';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error updating account: " . $e->getMessage());
    
    // Check for duplicate contact or email
    if (strpos($e->getMessage(), 'AK_PAT_CONTACT_NUM') !== false) {
        echo json_encode(['success' => false, 'message' => 'Contact number already in use']);
    } elseif (strpos($e->getMessage(), 'AK_PAT_EMAIL') !== false) {
        echo json_encode(['success' => false, 'message' => 'Email already in use']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?>