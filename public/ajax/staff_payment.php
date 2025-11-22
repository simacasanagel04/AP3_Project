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
                // Ensure previous_payments uses correct key: formatted_date
                $formattedPayments = array_map(function($p) {
                    return [
                        'PAYMT_ID' => $p['paymt_id'] ?? $p['PAYMT_ID'] ?? '',
                        'PAYMT_AMOUNT_PAID' => $p['paymt_amount_paid'] ?? $p['PAYMT_AMOUNT_PAID'] ?? 0,
                        'PYMT_METH_NAME' => $p['pymt_meth_name'] ?? $p['PYMT_METH_NAME'] ?? 'N/A',
                        'PYMT_STAT_NAME' => $p['pymt_stat_name'] ?? $p['PYMT_STAT_NAME'] ?? 'N/A',
                        'formatted_date' => $p['formatted_paymt_date'] ?? $p['formatted_date'] ?? 'N/A'
                    ];
                }, $previousPayments);

                echo json_encode([
                    'success' => true,
                    'details' => $details,
                    'previous_payments' => $formattedPayments
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            }
            break;

        case 'search_appointments':
            $search = $_GET['search'] ?? '';
            $appointments = $payment->searchAppointments($search);
            
            // Ensure JS receives APPT_ID and appt_display
            $formattedAppointments = array_map(function($appt) {
                return [
                    'APPT_ID' => $appt['id'] ?? $appt['APPT_ID'] ?? '',
                    'appt_display' => $appt['text'] ?? $appt['appt_display'] ?? 'Unknown'
                ];
            }, $appointments);

            echo json_encode(['success' => true, 'appointments' => $formattedAppointments]);
            break;

        case 'get_all_appointments':
            $appointments = $payment->getAllAppointmentsForDropdown();
            
            $formattedAppointments = array_map(function($appt) {
                return [
                    'APPT_ID' => $appt['id'] ?? $appt['APPT_ID'] ?? '',
                    'appt_display' => $appt['text'] ?? $appt['appt_display'] ?? 'Unknown'
                ];
            }, $appointments);

            echo json_encode(['success' => true, 'appointments' => $formattedAppointments]);
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
            
            if ($result && $result !== 0) {
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
                // Ensure date is in correct format for datetime-local
                if (isset($paymentDetails['paymt_date'])) {
                    $date = new DateTime($paymentDetails['paymt_date']);
                    $paymentDetails['formatted_paymt_date'] = $date->format('Y-m-d\TH:i');
                }
                echo json_encode(['success' => true, 'payment' => $paymentDetails]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
            }
            break;

        case 'update_payment':
            $data = [
                'paymt_id' => $_POST['paymt_id'] ?? '',
                'appt_id' => $_POST['appt_id'] ?? '',
                'paymt_amount_paid' => $_POST['amount'] ?? 0,
                'pymt_meth_id' => $_POST['payment_method'] ?? '',
                'pymt_stat_id' => $_POST['payment_status'] ?? '',
                'paymt_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s')
            ];
            
            // Validate required fields
            if (empty($data['paymt_id']) || empty($data['appt_id']) || empty($data['paymt_amount_paid']) || 
                empty($data['pymt_meth_id']) || empty($data['pymt_stat_id'])) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            error_log("Update payment data: " . json_encode($data));
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
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
?>