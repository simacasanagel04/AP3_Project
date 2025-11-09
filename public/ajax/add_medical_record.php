<?php
// public/ajax/add_medical_record.php
// for doctor_med_rec.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    $appt_id = $_POST['appt_id'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $prescription = $_POST['prescription'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $doc_id = $_SESSION['doc_id'] ?? null;
    
    // Validate required fields
    if (empty($appt_id) || empty($diagnosis) || empty($prescription) || empty($visit_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Verify appointment belongs to this doctor
    $checkQuery = "SELECT a.APPT_ID FROM APPOINTMENT a WHERE a.APPT_ID = :appt_id AND a.DOC_ID = :doc_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':appt_id' => $appt_id, ':doc_id' => $doc_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID or not authorized']);
        exit;
    }
    
    // Check if medical record already exists for this appointment
    $existsQuery = "SELECT MED_REC_ID FROM MEDICAL_RECORD WHERE APPT_ID = :appt_id";
    $existsStmt = $db->prepare($existsQuery);
    $existsStmt->execute([':appt_id' => $appt_id]);
    
    if ($existsStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Medical record already exists for this appointment']);
        exit;
    }
    
    // Insert new medical record
    $insertQuery = "INSERT INTO MEDICAL_RECORD 
                    (MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE, MED_REC_CREATED_AT, APPT_ID) 
                    VALUES (:diagnosis, :prescription, :visit_date, NOW(), :appt_id)";
    
    $insertStmt = $db->prepare($insertQuery);
    $result = $insertStmt->execute([
        ':diagnosis' => $diagnosis,
        ':prescription' => $prescription,
        ':visit_date' => $visit_date,
        ':appt_id' => $appt_id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Medical record created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create medical record']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>