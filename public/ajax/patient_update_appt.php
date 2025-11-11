<?php
// public/ajax/patient_update_appt.php
// for patient_dashb.php

session_start();
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../classes/Appointment.php';

header('Content-Type: application/json');

// Check if patient is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = $_POST['app_id'] ?? '';
    $app_date = $_POST['app_date'] ?? '';
    $app_time = $_POST['app_time'] ?? '';
    $app_status = $_POST['app_status'] ?? '';

    if (empty($app_id) || empty($app_date) || empty($app_time) || empty($app_status)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        $appointment = new Appointment($db);

        $data = [
            'app_id' => $app_id,
            'app_date' => $app_date,
            'app_time' => $app_time,
            'stat_id' => $app_status
        ];

        $result = $appointment->update($data);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update appointment']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>