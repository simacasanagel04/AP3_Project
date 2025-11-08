<?php
// public/ajax/delete_appointment.php
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Check if user is logged in as doctor
if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appt_id = $_POST['appt_id'] ?? null;
    
    if (!$appt_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        $sql = "DELETE FROM appointment WHERE APPT_ID = ? AND DOC_ID = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$appt_id, $_SESSION['doc_id']]);

        echo json_encode(['success' => $result]);
    } catch (PDOException $e) {
        error_log("Delete appointment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>