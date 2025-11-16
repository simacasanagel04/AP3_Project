<?php
// public/ajax/staff_payment.php

session_start();
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../classes/Payment.php';

header('Content-Type: application/json');

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
            
            // Format appointment details for frontend
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
            
            $paymentDetails = $payment->findById($paymtId);
            
            if (!$paymentDetails || $paymentDetails === 0) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            // Get additional IDs needed for the form
            $sqlIds = "SELECT PYMT_METH_ID, PYMT_STAT_ID, APPT_ID 
                      FROM payment 
                      WHERE PAYMT_ID = :paymt_id";
            $stmtIds = $db->prepare($sqlIds);
            $stmtIds->execute([':paymt_id' => $paymtId]);
            $ids = $stmtIds->fetch(PDO::FETCH_ASSOC);
            
            // Format response
            $response = [
                'success' => true,
                'payment' => [
                    'PAYMT_ID' => $paymentDetails['paymt_id'],
                    'APPT_ID' => $ids['APPT_ID'] ?? '',
                    'PAYMT_AMOUNT_PAID' => $paymentDetails['paymt_amount_paid'],
                    'PAYMT_DATE' => $paymentDetails['paymt_date'],
                    'PYMT_METH_ID' => $ids['PYMT_METH_ID'] ?? '',
                    'PYMT_STAT_ID' => $ids['PYMT_STAT_ID'] ?? ''
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
            
            // Convert datetime-local format to MySQL datetime format
            if (strpos($paymentDate, 'T') !== false) {
                $paymentDate = str_replace('T', ' ', $paymentDate) . ':00';
            }
            
            // Prepare data for insertion
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
            
            // Convert datetime-local format to MySQL datetime format
            if (strpos($paymentDate, 'T') !== false) {
                $paymentDate = str_replace('T', ' ', $paymentDate) . ':00';
            }
            
            // Get current appointment ID (we need this for update)
            $currentPayment = $payment->findById($paymtId);
            if (!$currentPayment || $currentPayment === 0) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                exit;
            }
            
            // Prepare data for update
            $paymentData = [
                'paymt_id' => $paymtId,
                'appt_id' => $currentPayment['app_id'],
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
} catch (Exception $e) {
    error_log("Payment AJAX Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please contact administrator.',
        'debug' => $e->getMessage() // Remove this in production
    ]);
}
?>