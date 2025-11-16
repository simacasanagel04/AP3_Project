<?php
// public/ajax/staff_payment.php

session_start();
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../classes/Payment.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = (new Database())->connect();
$payment = new Payment($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        // Search appointments for Select2 dropdown
        case 'search_appointments':
            $search = $_GET['search'] ?? '';
            $appointments = $payment->searchAppointments($search);
            
            // Format response for Select2
            $result = [];
            foreach ($appointments as $appt) {
                $result[] = [
                    'APPT_ID' => $appt['id'],
                    'appt_display' => $appt['text']
                ];
            }
            
            echo json_encode(['success' => true, 'appointments' => $result]);
            break;

        // Get all appointments for initial dropdown load
        case 'get_all_appointments':
            $appointments = $payment->getAllAppointmentsForDropdown();
            
            // Format response for Select2
            $result = [];
            foreach ($appointments as $appt) {
                $result[] = [
                    'APPT_ID' => $appt['id'],
                    'appt_display' => $appt['text']
                ];
            }
            
            echo json_encode(['success' => true, 'appointments' => $result]);
            break;

        // Get appointment details when appointment is selected
        case 'get_appointment_details':
            $apptId = $_GET['appt_id'] ?? '';
            
            if (empty($apptId)) {
                echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
                exit;
            }
            
            // Get appointment details
            $details = $payment->getAppointmentDetails($apptId);
            
            if (!$details) {
                echo json_encode(['success' => false, 'message' => 'Appointment not found']);
                exit;
            }
            
            // Get previous payments for this appointment
            $previousPayments = $payment->getPaymentsByAppointment($apptId);
            
            // Format previous payments for display
            $formattedPrevious = [];
            foreach ($previousPayments as $prev) {
                $formattedPrevious[] = [
                    'PAYMT_ID' => $prev['paymt_id'],
                    'PAYMT_AMOUNT_PAID' => $prev['paymt_amount_paid'],
                    'PYMT_METH_NAME' => $prev['pymt_meth_name'],
                    'PYMT_STAT_NAME' => $prev['pymt_stat_name'],
                    'formatted_date' => $prev['formatted_paymt_date']
                ];
            }
            
            // Format appointment details for frontend (matching Payment class column names)
            $response = [
                'success' => true,
                'details' => [
                    'APPT_ID' => $details['app_id'],
                    'patient_name' => $details['patient_name'] ?? 'N/A',
                    'SERV_NAME' => $details['serv_name'] ?? 'N/A',
                    'APPT_DATE' => $details['app_date'] ?? '',
                    'SERV_PRICE' => $details['serv_price'] ?? 0
                ],
                'previous_payments' => $formattedPrevious
            ];
            
            echo json_encode($response);
            break;

        // Get payment details for update modal
        case 'get_payment_details':
            $paymtId = $_GET['paymt_id'] ?? '';
            
            if (empty($paymtId)) {
                echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
                exit;
            }
            
            // Get payment details using findById
            $paymentDetails = $payment->findById($paymtId);
            
            if (!$paymentDetails || $paymentDetails === 0) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            // Get full payment record with IDs
            $sqlPayment = "SELECT 
                            p.PAYMT_ID,
                            p.APPT_ID,
                            p.PAYMT_AMOUNT_PAID,
                            p.PAYMT_DATE,
                            p.PYMT_METH_ID,
                            p.PYMT_STAT_ID
                          FROM payment p
                          WHERE p.PAYMT_ID = :paymt_id";
            $stmtPayment = $db->prepare($sqlPayment);
            $stmtPayment->execute([':paymt_id' => $paymtId]);
            $fullPayment = $stmtPayment->fetch(PDO::FETCH_ASSOC);
            
            if (!$fullPayment) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            // Format response
            $response = [
                'success' => true,
                'payment' => [
                    'PAYMT_ID' => $fullPayment['PAYMT_ID'],
                    'APPT_ID' => $fullPayment['APPT_ID'],
                    'PAYMT_AMOUNT_PAID' => $fullPayment['PAYMT_AMOUNT_PAID'],
                    'PAYMT_DATE' => $fullPayment['PAYMT_DATE'],
                    'PYMT_METH_ID' => $fullPayment['PYMT_METH_ID'],
                    'PYMT_STAT_ID' => $fullPayment['PYMT_STAT_ID']
                ]
            ];
            
            echo json_encode($response);
            break;

        // Add new payment
        case 'add_payment':
            $apptId = $_POST['appt_id'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $methodId = $_POST['payment_method'] ?? '';
            $statusId = $_POST['payment_status'] ?? '';
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
            
            // Validate required fields
            if (empty($apptId) || empty($amount) || empty($methodId) || empty($statusId)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            // Validate amount is positive
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than zero']);
                exit;
            }
            
            // Convert datetime-local format to MySQL datetime format
            if (strpos($paymentDate, 'T') !== false) {
                $paymentDate = str_replace('T', ' ', $paymentDate) . ':00';
            }
            
            // Prepare data for insertion (matching Payment class parameters)
            $paymentData = [
                'appt_id' => $apptId,
                'paymt_amount_paid' => $amount,
                'paymt_date' => $paymentDate,
                'pymt_meth_id' => $methodId,
                'pymt_stat_id' => $statusId
            ];
            
            // Insert payment
            $result = $payment->create($paymentData);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment added successfully!',
                    'payment_id' => $result
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add payment. Please try again.']);
            }
            break;

        // Update existing payment
        case 'update_payment':
            $paymtId = $_POST['paymt_id'] ?? '';
            $amount = $_POST['amount'] ?? 0;
            $methodId = $_POST['payment_method'] ?? '';
            $statusId = $_POST['payment_status'] ?? '';
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
            
            // Validate required fields
            if (empty($paymtId) || empty($amount) || empty($methodId) || empty($statusId)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            // Validate amount is positive
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than zero']);
                exit;
            }
            
            // Convert datetime-local format to MySQL datetime format
            if (strpos($paymentDate, 'T') !== false) {
                $paymentDate = str_replace('T', ' ', $paymentDate) . ':00';
            }
            
            // Get current payment to retrieve appointment ID
            $sqlCurrent = "SELECT APPT_ID FROM payment WHERE PAYMT_ID = :paymt_id";
            $stmtCurrent = $db->prepare($sqlCurrent);
            $stmtCurrent->execute([':paymt_id' => $paymtId]);
            $currentPayment = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentPayment) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            // Prepare data for update (matching Payment class parameters)
            $paymentData = [
                'paymt_id' => $paymtId,
                'appt_id' => $currentPayment['APPT_ID'],
                'paymt_amount_paid' => $amount,
                'paymt_date' => $paymentDate,
                'pymt_meth_id' => $methodId,
                'pymt_stat_id' => $statusId
            ];
            
            // Update payment
            $result = $payment->update($paymentData);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment updated successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment. Please try again.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
            break;
    }
} catch (PDOException $e) {
    error_log("Payment AJAX Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again.',
        'debug' => $e->getMessage() // Remove this line in production
    ]);
} catch (Exception $e) {
    error_log("Payment AJAX Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please contact administrator.',
        'debug' => $e->getMessage() // Remove this line in production
    ]);
}
?>