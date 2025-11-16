<?php
// public/ajax/get_appointment_details.php
// FIXED VERSION - Allows duplicates & better error handling

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Validate appointment ID parameter
if (!isset($_GET['appt_id']) || empty(trim($_GET['appt_id']))) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

try {
    $db = (new Database())->connect();
    $appt_id = trim($_GET['appt_id']);
    $doc_id = $_SESSION['doc_id'] ?? null;
    
    if (!$doc_id) {
        echo json_encode(['success' => false, 'message' => 'Doctor session not found. Please log in again.']);
        exit;
    }
    
    // ========================================
    // FIX: Query WITHOUT doctor restriction first
    // Then check authorization separately
    // ========================================
    $query = "SELECT 
        a.APPT_ID,
        a.DOC_ID,
        p.PAT_ID,
        p.PAT_FIRST_NAME,
        p.PAT_LAST_NAME,
        p.PAT_DOB,
        p.PAT_GENDER,
        s.SERV_ID,
        s.SERV_NAME,
        d.DOC_FIRST_NAME,
        d.DOC_LAST_NAME
    FROM appointment a
    INNER JOIN patient p ON a.PAT_ID = p.PAT_ID
    INNER JOIN service s ON a.SERV_ID = s.SERV_ID
    INNER JOIN doctor d ON a.DOC_ID = d.DOC_ID
    WHERE a.APPT_ID = :appt_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':appt_id' => $appt_id]);
    
    // Check if appointment exists at all
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Appointment ID not found in the system. Please verify the ID and try again.'
        ]);
        exit;
    }
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ========================================
    // FIX: Check if appointment belongs to this doctor
    // ========================================
    if ($appointment['DOC_ID'] != $doc_id) {
        echo json_encode([
            'success' => false,
            'message' => 'This appointment belongs to Dr. ' . $appointment['DOC_FIRST_NAME'] . ' ' . $appointment['DOC_LAST_NAME'] . '. You cannot create medical records for other doctors\' appointments.'
        ]);
        exit;
    }
    
    // Calculate patient age
    $dob = new DateTime($appointment['PAT_DOB']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
    
    // ========================================
    // FIX: Check if record exists (for warning, NOT blocking)
    // ========================================
    $checkQuery = "SELECT COUNT(*) as record_count FROM medical_record WHERE APPT_ID = :appt_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':appt_id' => $appt_id]);
    $recordCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['record_count'];
    
    // Success response with all data
    echo json_encode([
        'success' => true,
        'recordExists' => $recordCount > 0,  // For warning message
        'recordCount' => $recordCount,        // How many exist
        'appointment' => [
            'APPT_ID' => $appointment['APPT_ID'],
            'PAT_FIRST_NAME' => $appointment['PAT_FIRST_NAME'],
            'PAT_LAST_NAME' => $appointment['PAT_LAST_NAME'],
            'PAT_AGE' => $age,
            'PAT_GENDER' => $appointment['PAT_GENDER'],
            'SERV_ID' => $appointment['SERV_ID'],
            'SERV_NAME' => $appointment['SERV_NAME']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("get_appointment_details error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again or contact support.'
    ]);
} catch (Exception $e) {
    error_log("get_appointment_details exception: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred: ' . $e->getMessage()
    ]);
}
?>