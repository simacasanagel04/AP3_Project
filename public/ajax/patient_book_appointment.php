// ajax/patient_book_appt.php

<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Payment.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['pat_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['pat_id', 'doc_id', 'serv_id', 'appt_date', 'appt_time', 'pymt_meth_id', 'pymt_amount'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Verify the patient ID matches the logged-in user
if ($input['pat_id'] != $_SESSION['pat_id']) {
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Start transaction
    $db->beginTransaction();
    
    // Create appointment
    $appointment = new Appointment($db);
    $appointmentData = [
        'app_date' => $input['appt_date'],
        'app_time' => $input['appt_time'],
        'pat_id' => $input['pat_id'],
        'doc_id' => $input['doc_id'],
        'serv_id' => $input['serv_id'],
        'stat_id' => 1 // Scheduled
    ];
    
    $apptCreated = $appointment->create($appointmentData);
    
    if (!$apptCreated) {
        throw new Exception('Failed to create appointment');
    }
    
    // Get the last inserted appointment ID
    $apptId = $db->lastInsertId();
    
    // Create payment record
    $payment = new Payment($db);
    $paymentData = [
        'paymt_amount_paid' => $input['pymt_amount'],
        'paymt_date' => date('Y-m-d H:i:s'),
        'pymt_meth_id' => $input['pymt_meth_id'],
        'pymt_stat_id' => 2, // Pending
        'appt_id' => $apptId
    ];
    
    $paymentCreated = $payment->create($paymentData);
    
    if (!$paymentCreated) {
        throw new Exception('Failed to create payment record');
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully',
        'appointment_id' => $apptId
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to book appointment: ' . $e->getMessage()
    ]);
}
?>