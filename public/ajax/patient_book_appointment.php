<?php
// public/ajax/patient_book_appointment.php
// FIXED VERSION - Properly retrieves custom APPT_ID from trigger

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
    
    // ========================================
    // FIX: Call stored procedure to get APPT_ID first
    // ========================================
    $sqlGetId = "CALL generate_appointment_id(:appt_date, @new_appt_id)";
    $stmtGetId = $db->prepare($sqlGetId);
    $stmtGetId->execute([':appt_date' => $input['appt_date']]);
    
    // Retrieve the generated ID
    $resultId = $db->query("SELECT @new_appt_id as appt_id")->fetch(PDO::FETCH_ASSOC);
    $apptId = $resultId['appt_id'];
    
    if (!$apptId) {
        throw new Exception('Failed to generate appointment ID');
    }
    
    // ========================================
    // FIX: Insert appointment WITH the generated APPT_ID
    // ========================================
    $sqlAppt = "INSERT INTO appointment 
                (APPT_ID, APPT_DATE, APPT_TIME, APPT_CREATED_AT, PAT_ID, DOC_ID, SERV_ID, STAT_ID)
                VALUES (:appt_id, :appt_date, :appt_time, NOW(), :pat_id, :doc_id, :serv_id, 1)";
    
    $stmtAppt = $db->prepare($sqlAppt);
    $apptCreated = $stmtAppt->execute([
        ':appt_id' => $apptId,
        ':appt_date' => $input['appt_date'],
        ':appt_time' => $input['appt_time'],
        ':pat_id' => $input['pat_id'],
        ':doc_id' => $input['doc_id'],
        ':serv_id' => $input['serv_id']
    ]);
    
    if (!$apptCreated) {
        throw new Exception('Failed to create appointment');
    }
    
    // ========================================
    // FIX: Now create payment with the correct APPT_ID
    // ========================================
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