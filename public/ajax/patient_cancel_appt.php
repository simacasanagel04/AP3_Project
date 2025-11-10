<?php
// public/patient_cancel_appointment.php
// for patient_dashb.php

session_start();
require_once '../config/Database.php';
require_once '../classes/Appointment.php';

header('Content-Type: application/json');

// Check if patient is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $app_id = $input['app_id'] ?? '';

    if (empty($app_id)) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        $appointment = new Appointment($db);

        // Update status to 3 (Cancelled)
        $data = [
            'app_id' => $app_id,
            'stat_id' => 3  // 3 = Cancelled
        ];

        $result = $appointment->updateStatus($data);

        if ($result) {
            // TODO: Notify doctor (email/notification system can be added here)
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>