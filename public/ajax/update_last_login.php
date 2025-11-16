<?php
// public/ajax/update_last_login.php
// Updates user's last login timestamp in real-time

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $db = (new Database())->connect();
    $user_id = $_SESSION['user_id'];
    
    $query = "UPDATE users SET USER_LAST_LOGIN = NOW() WHERE USER_ID = :user_id";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([':user_id' => $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>