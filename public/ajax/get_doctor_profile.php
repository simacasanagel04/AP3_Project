<?php
// public/ajax/get_doctor_profile.php
// Fetches doctor profile data for the view modal

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = (new Database())->connect();
    $doc_id = $_SESSION['doc_id'];
    
    // Fetch doctor data with specialization
    $sql = "SELECT 
                d.DOC_ID,
                d.DOC_FIRST_NAME,
                d.DOC_MIDDLE_INIT,
                d.DOC_LAST_NAME,
                d.DOC_EMAIL,
                d.DOC_CONTACT_NUM,
                d.DOC_CREATED_AT,
                d.DOC_UPDATED_AT,
                d.SPEC_ID,
                s.SPEC_NAME
            FROM doctor d
            LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE d.DOC_ID = :doc_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':doc_id' => $doc_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doctor) {
        echo json_encode([
            'success' => true,
            'doctor' => $doctor
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Doctor profile not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("get_doctor_profile error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>