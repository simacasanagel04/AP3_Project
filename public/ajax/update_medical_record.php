<?php
// public/ajax/update_medical_record.php
// for doctor_schedule.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $med_rec_id = $_POST['med_rec_id'] ?? null;
    $diagnosis = $_POST['MED_REC_DIAGNOSIS'] ?? null;
    $prescription = $_POST['MED_REC_PRESCRIPTION'] ?? null;
    $visit_date = $_POST['visit_date'] ?? null;

    if (!$med_rec_id || !$diagnosis || !$prescription || !$visit_date) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        $sql = "UPDATE medical_record 
                SET MED_REC_DIAGNOSIS = ?, 
                    MED_REC_PRESCRIPTION = ?, 
                    MED_REC_VISIT_DATE = ?,
                    MED_REC_UPDATED_AT = NOW()
                WHERE MED_REC_ID = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$diagnosis, $prescription, $visit_date, $med_rec_id]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Medical record updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or record not found']);
        }
    } catch (PDOException $e) {
        error_log("Update medical record error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>