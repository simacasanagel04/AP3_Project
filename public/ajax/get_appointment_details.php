<?php
// public/ajax/get_appointment_details.php
// for doctor_med_rec.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['appt_id'])) {
    echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
    exit;
}

try {
    $db = (new Database())->connect();
    $appt_id = $_GET['appt_id'];
    $doc_id = $_SESSION['doc_id'] ?? null;
    
    // Fetch appointment details with patient and service info
    $query = "SELECT 
        a.APPT_ID,
        p.PAT_ID,
        p.PAT_FIRST_NAME,
        p.PAT_LAST_NAME,
        p.PAT_DOB,
        p.PAT_GENDER,
        s.SERV_NAME
    FROM APPOINTMENT a
    INNER JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
    INNER JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
    WHERE a.APPT_ID = :appt_id AND a.DOC_ID = :doc_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':appt_id' => $appt_id, ':doc_id' => $doc_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found or not authorized']);
        exit;
    }
    
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate age
    $dob = new DateTime($appointment['PAT_DOB']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
    
    echo json_encode([
        'success' => true,
        'appointment' => [
            'APPT_ID' => $appointment['APPT_ID'],
            'PAT_FIRST_NAME' => $appointment['PAT_FIRST_NAME'],
            'PAT_LAST_NAME' => $appointment['PAT_LAST_NAME'],
            'PAT_AGE' => $age,
            'PAT_GENDER' => $appointment['PAT_GENDER'],
            'SERV_NAME' => $appointment['SERV_NAME']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>