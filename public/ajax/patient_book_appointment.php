<?php
/**
 * ============================================================================
 * FILE: public/ajax/patient_book_appointment.php
 * PURPOSE: Handle patient appointment booking with payment
 * 
 * COLLATION FIX APPLIED:
 * - Explicitly uses COLLATE utf8mb4_general_ci in LIKE comparison
 * - Prevents collation mismatch between connection and column collations
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/../../config/Database.php';

// Set JSON header for API response
header('Content-Type: application/json');

// ============================================================================
// AUTHENTICATION CHECK
// ============================================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['pat_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// ============================================================================
// GET AND VALIDATE INPUT DATA
// ============================================================================
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields exist
$required = ['pat_id', 'doc_id', 'serv_id', 'appt_date', 'appt_time', 'pymt_meth_id', 'pymt_amount'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Verify patient ID matches session (security check)
if ($input['pat_id'] != $_SESSION['pat_id']) {
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit;
}

// ============================================================================
// DATABASE TRANSACTION - BOOK APPOINTMENT + CREATE PAYMENT
// ============================================================================
try {
    $database = new Database();
    $db = $database->connect();
    
    // Start transaction for data integrity
    $db->beginTransaction();
    
    // ========================================================================
    // STEP 1: GENERATE APPOINTMENT ID (COLLATION FIX APPLIED)
    // ========================================================================
    $year = date('Y', strtotime($input['appt_date']));
    $month = date('m', strtotime($input['appt_date']));
    
    // CRITICAL FIX: Cast both sides to same collation to prevent mismatch
    // This ensures the LIKE comparison uses utf8mb4_general_ci for both operands
    $sqlGetLastId = "SELECT APPT_ID FROM appointment 
                     WHERE APPT_ID COLLATE utf8mb4_general_ci LIKE :prefix COLLATE utf8mb4_general_ci
                     ORDER BY APPT_ID DESC 
                     LIMIT 1";
    
    $stmtGetLastId = $db->prepare($sqlGetLastId);
    $stmtGetLastId->execute([':prefix' => $year . '-' . $month . '-%']);
    $lastId = $stmtGetLastId->fetchColumn();
    
    // Generate next sequence number
    if ($lastId) {
        // Extract sequence number from last ID (format: YYYY-MM-0000001)
        $lastSequence = (int)substr($lastId, 8); // Get the last 7 digits
        $nextSequence = $lastSequence + 1;
    } else {
        // First appointment of this month
        $nextSequence = 1;
    }
    
    // Format: YYYY-MM-0000001
    $apptId = $year . '-' . $month . '-' . str_pad($nextSequence, 7, '0', STR_PAD_LEFT);
    
    if (!$apptId) {
        throw new Exception('Failed to generate appointment ID');
    }
    
    // ========================================================================
    // STEP 2: INSERT APPOINTMENT RECORD
    // ========================================================================
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
    
    // ========================================================================
    // STEP 3: CREATE PAYMENT RECORD (Status 2 = Pending)
    // ========================================================================
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
    
    // ========================================================================
    // STEP 4: COMMIT TRANSACTION (All operations successful)
    // ========================================================================
    $db->commit();
    
    // Log success for debugging
    error_log("✓ Appointment booked successfully: $apptId for patient {$input['pat_id']}");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully',
        'appointment_id' => $apptId
    ]);
    
} catch (Exception $e) {
    // ========================================================================
    // ERROR HANDLING - ROLLBACK TRANSACTION
    // ========================================================================
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log error for debugging
    error_log("✗ Booking error: " . $e->getMessage());
    error_log("   Patient ID: " . ($input['pat_id'] ?? 'N/A'));
    error_log("   Date: " . ($input['appt_date'] ?? 'N/A'));
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to book appointment: ' . $e->getMessage()
    ]);
}
?>