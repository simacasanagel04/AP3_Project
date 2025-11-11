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
        case 'get_appointment_details':
            $apptId = $_GET['appt_id'] ?? '';
            if (empty($apptId)) {
                echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
                exit;
            }
            
            $details = $payment->getAppointmentDetails($apptId);
            $previousPayments = $payment->getPaymentsByAppointment($apptId);
            
            if ($details) {
                echo json_encode([
                    'success' => true,
                    'details' => $details,
                    'previous_payments' => $previousPayments
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            }
            break;

        case 'search_appointments':
            $search = $_GET['search'] ?? '';
            $appointments = $payment->searchAppointments($search);
            echo json_encode(['success' => true, 'appointments' => $appointments]);
            break;

        case 'get_all_appointments':
            $appointments = $payment->getAllAppointmentsForDropdown();
            echo json_encode(['success' => true, 'appointments' => $appointments]);
            break;

        case 'add_payment':
            $data = [
                'appt_id' => $_POST['appt_id'] ?? '',
                'paymt_amount_paid' => $_POST['amount'] ?? 0,
                'pymt_meth_id' => $_POST['payment_method'] ?? '',
                'pymt_stat_id' => $_POST['payment_status'] ?? '',
                'paymt_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s')
            ];
            
            // Validate required fields
            if (empty($data['appt_id']) || empty($data['paymt_amount_paid']) || 
                empty($data['pymt_meth_id']) || empty($data['pymt_stat_id'])) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            $result = $payment->create($data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment added successfully', 'payment_id' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add payment']);
            }
            break;

        case 'get_payment_details':
            $paymtId = $_GET['paymt_id'] ?? '';
            if (empty($paymtId)) {
                echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
                exit;
            }
            
            $paymentDetails = $payment->findById($paymtId);
            
            if ($paymentDetails && $paymentDetails !== 0) {
                echo json_encode(['success' => true, 'payment' => $paymentDetails]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
            }
            break;

        case 'update_payment':
            $data = [
                'paymt_id' => $_POST['paymt_id'] ?? '',
                'paymt_amount_paid' => $_POST['amount'] ?? 0,
                'pymt_meth_id' => $_POST['payment_method'] ?? '',
                'pymt_stat_id' => $_POST['payment_status'] ?? '',
                'paymt_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s')
            ];
            
            // Validate required fields
            if (empty($data['paymt_id']) || empty($data['paymt_amount_paid']) || 
                empty($data['pymt_meth_id']) || empty($data['pymt_stat_id'])) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            $result = $payment->update($data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update payment']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Payment AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>