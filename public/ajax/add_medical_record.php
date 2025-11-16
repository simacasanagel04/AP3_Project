<?php
// public/ajax/add_medical_record.php
// FIXED VERSION - Allows duplicate records

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = (new Database())->connect();
    
    // Get form data
    $appt_id = trim($_POST['appt_id'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $visit_date = trim($_POST['visit_date'] ?? '');
    $doc_id = $_SESSION['doc_id'] ?? null;
    
    // Validate doctor session
    if (!$doc_id) {
        echo json_encode(['success' => false, 'message' => 'Doctor session expired. Please log in again.']);
        exit;
    }
    
    // Validate required fields
    if (empty($appt_id)) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
        exit;
    }
    
    if (empty($diagnosis)) {
        echo json_encode(['success' => false, 'message' => 'Diagnosis is required']);
        exit;
    }
    
    if (empty($prescription)) {
        echo json_encode(['success' => false, 'message' => 'Prescription is required']);
        exit;
    }
    
    if (empty($visit_date)) {
        echo json_encode(['success' => false, 'message' => 'Visit date is required']);
        exit;
    }
    
    // Validate visit date format and range
    $visitDateTime = DateTime::createFromFormat('Y-m-d', $visit_date);
    if (!$visitDateTime) {
        echo json_encode(['success' => false, 'message' => 'Invalid visit date format']);
        exit;
    }
    
    // ========================================
    // FIX: Verify appointment exists AND belongs to this doctor
    // ========================================
    $checkQuery = "SELECT a.APPT_ID, a.DOC_ID, a.APPT_DATE 
                   FROM appointment a 
                   WHERE a.APPT_ID = :appt_id";
    
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':appt_id' => $appt_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID not found in the system']);
        exit;
    }
    
    $apptData = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check doctor authorization
    if ($apptData['DOC_ID'] != $doc_id) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to create medical records for this appointment']);
        exit;
    }
    
    // ========================================
    // FIX: REMOVED duplicate check - Allow multiple records
    // Medical records can have multiple entries for follow-ups
    // ========================================
    
    // Insert new medical record
    $insertQuery = "INSERT INTO medical_record 
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
        $newRecordId = $db->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Medical record created successfully',
            'med_rec_id' => $newRecordId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create medical record. Please try again.']);
    }
    
} catch (PDOException $e) {
    error_log("add_medical_record PDO error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please contact support.'
    ]);
} catch (Exception $e) {
    error_log("add_medical_record exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
?>