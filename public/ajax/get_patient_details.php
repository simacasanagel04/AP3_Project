<?php
// public/ajax/get_patient_details.php
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Check if user is logged in as doctor
if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pat_id = $_GET['pat_id'] ?? null;
    $appt_id = $_GET['appt_id'] ?? null;

    if (!$pat_id || !$appt_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        $query = "SELECT 
                    p.PAT_ID,
                    p.PAT_FIRST_NAME,
                    p.PAT_MIDDLE_INIT,
                    p.PAT_LAST_NAME,
                    p.PAT_DOB,
                    p.PAT_GENDER,
                    p.PAT_CONTACT_NUM,
                    p.PAT_EMAIL,
                    p.PAT_ADDRESS
                  FROM patient p
                  INNER JOIN appointment a ON p.PAT_ID = a.PAT_ID
                  WHERE p.PAT_ID = :pat_id
                  AND a.APPT_ID = :appt_id
                  AND a.DOC_ID = :doc_id
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':pat_id' => $pat_id,
            ':appt_id' => $appt_id,
            ':doc_id' => $_SESSION['doc_id']
        ]);
        
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patient) {
            echo json_encode(['success' => true, 'patient' => $patient]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
        }
    } catch (PDOException $e) {
        error_log("Get patient details error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>