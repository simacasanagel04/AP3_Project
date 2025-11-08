<?php
// public/ajax/update_status.php
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
    $status_id = $_POST['status_id'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$appt_id || !$status_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $db = (new Database())->connect();

        $sql = "UPDATE appointment SET STAT_ID = ?, APPT_UPDATED_AT = NOW()
                WHERE APPT_ID = ? AND DOC_ID = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$status_id, $appt_id, $doc_id]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Status Updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status or no changes made']);
        }
    } catch (PDOException $e) {
        error_log("Update status error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>