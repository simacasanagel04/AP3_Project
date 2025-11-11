<?php
// public/ajax/patient_book_appointment.php
// for user patient

session_start();
require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: application/json');

// Check authentication
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

// Verify patient ID matches session
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
    $sqlAppt = "INSERT INTO appointment 
                (APPT_DATE, APPT_TIME, APPT_CREATED_AT, PAT_ID, DOC_ID, SERV_ID, STAT_ID)
                VALUES (:appt_date, :appt_time, NOW(), :pat_id, :doc_id, :serv_id, 1)";
    
    $stmtAppt = $db->prepare($sqlAppt);
    $apptCreated = $stmtAppt->execute([
        ':appt_date' => $input['appt_date'],
        ':appt_time' => $input['appt_time'],
        ':pat_id' => $input['pat_id'],
        ':doc_id' => $input['doc_id'],
        ':serv_id' => $input['serv_id']
    ]);
    
    if (!$apptCreated) {
        throw new Exception('Failed to create appointment');
    }
    
    // Get appointment ID
    $apptId = $db->lastInsertId();
    
    // Create payment record
    $sqlPayment = "INSERT INTO payment 
                   (PAYMT_AMOUNT_PAID, PAYMT_DATE, PYMT_CREATED_AT, PYMT_METH_ID, PYMT_STAT_ID, APPT_ID)
                   VALUES (:amount, NOW(), NOW(), :method, 2, :appt_id)";
    
    $stmtPayment = $db->prepare($sqlPayment);
    $paymentCreated = $stmtPayment->execute([
        ':amount' => $input['pymt_amount'],
        ':method' => $input['pymt_meth_id'],
        ':appt_id' => $apptId
    ]);
    
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
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Booking error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to book appointment: ' . $e->getMessage()
    ]);
}
?>