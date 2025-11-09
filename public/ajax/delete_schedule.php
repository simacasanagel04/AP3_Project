<?php
// public/ajax/delete_schedule.php
// for doctor_schedule.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sched_id = $_POST['sched_id'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$sched_id) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        $sql = "DELETE FROM schedule WHERE SCHED_ID = ? AND DOC_ID = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$sched_id, $doc_id]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Schedule not found or already deleted']);
        }
    } catch (PDOException $e) {
        error_log("Delete schedule error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>