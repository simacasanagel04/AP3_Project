<?php
// public/ajax/patient_update_account.php
// FIXED VERSION - Mirrors doctor's password change logic exactly

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

    // ========================================
    // PASSWORD CHANGE LOGIC (SAME AS DOCTOR VERSION)
    // ========================================
    $updatePassword = false;
    $currentPwd = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $newPwd = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirmPwd = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // If password change is requested
    if (!empty($newPwd)) {
        // Validate current password is provided
        if (empty($currentPwd)) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Please enter your current password']);
            exit;
        }

        // Validate password confirmation
        if (empty($confirmPwd)) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Please confirm your new password']);
            exit;
        }

        if ($newPwd !== $confirmPwd) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }

        // Validate password length
        if (strlen($newPwd) < 6) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }

        // ========================================
        // FIX: Verify old password (handles both hashed & plain text)
        // EXACT SAME LOGIC AS DOCTOR VERSION
        // ========================================
        try {
            $sqlUser = "SELECT PASSWORD FROM users WHERE PAT_ID = :pat_id";
            $stmtUser = $db->prepare($sqlUser);
            $stmtUser->execute([':pat_id' => $pat_id]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'User account not found']);
                exit;
            }
            
            // Check if password is hashed or plain text (SAME AS DOCTOR)
            $passwordCorrect = false;
            
            if (substr($user['PASSWORD'], 0, 4) === '$2y$') {
                // Hashed password - use password_verify
                $passwordCorrect = password_verify($currentPwd, $user['PASSWORD']);
            } else {
                // Plain text password - direct comparison (legacy support)
                $passwordCorrect = ($currentPwd === $user['PASSWORD']);
            }
            
            if (!$passwordCorrect) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit;
            }
            
            $updatePassword = true;
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error verifying password: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            exit;
        }
    }

    // ========================================
    // Update password if verification passed
    // ========================================
    if ($updatePassword) {
        $hashedPassword = password_hash($newPwd, PASSWORD_DEFAULT);
        $sqlUpdatePwd = "UPDATE users 
                        SET PASSWORD = :password, 
                            USER_UPDATED_AT = NOW() 
                        WHERE PAT_ID = :pat_id";
        
        $stmtPwd = $db->prepare($sqlUpdatePwd);
        $stmtPwd->execute([
            ':password' => $hashedPassword,
            ':pat_id' => $pat_id
        ]);
    }

    // Commit transaction
    $db->commit();

    $message = 'Account updated successfully!';
    if ($updatePassword) {
        $message = 'Profile and password updated successfully!';
    }

    echo json_encode([
        'success' => true, 
        'message' => $message
    ]);

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
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("General error updating account: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating your account']);
}
?>