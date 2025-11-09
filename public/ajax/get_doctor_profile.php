<?php
// public/ajax/get_doctor_profile.php
// for doctor_profile.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = (new Database())->connect();
    $doc_id = $_SESSION['doc_id'];
    
    // Fetch doctor data with specialization
    $sql = "SELECT d.*, s.SPEC_NAME, s.SPEC_ID
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
            'message' => 'Doctor not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>