<?php
// public/ajax/update_appointment.php
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Check if user is logged in as doctor
if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appt_id = $_POST['appt_id'] ?? null;
    $appt_date = $_POST['appt_date'] ?? null;
    $appt_time = $_POST['appt_time'] ?? null;
    $status_id = $_POST['status_id'] ?? null;
    // ADDED: Retrieve the new service ID
    $service_id = $_POST['service_id'] ?? null; 

    if (!$appt_id || !$appt_date || !$appt_time || !$status_id || !$service_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: APPT_ID, Date, Time, Status, or Service.']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        // Server-Side Working Hours Validation
        // This is a minimal security measure. A robust check would require the service's duration.
        $dayOfWeek = date('w', strtotime($appt_date)); // 0=Sun, 6=Sat
        $time_start = strtotime($appt_time);
        
        $isValidTime = false;
        
        // Monday to Friday (1-5): 08:00 AM - 06:00 PM
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $start_time = strtotime('08:00');
            $end_time = strtotime('18:00');
            if ($time_start >= $start_time && $time_start <= $end_time) {
                $isValidTime = true;
            }
        } 
        // Saturday (6): 09:00 AM - 05:00 PM
        else if ($dayOfWeek == 6) {
            $start_time = strtotime('09:00');
            $end_time = strtotime('17:00');
            if ($time_start >= $start_time && $time_start <= $end_time) {
                $isValidTime = true;
            }
        } 
        // Sunday (0): Closed
        else {
            $isValidTime = false;
        }

        if (!$isValidTime) {
            echo json_encode(['success' => false, 'message' => 'The selected date/time is outside the doctor\'s working hours.']);
            exit;
        }

        // UPDATED: Added SERV_ID to the update query
        $query = "UPDATE appointment 
                  SET APPT_DATE = :appt_date,
                      APPT_TIME = :appt_time,
                      STAT_ID = :status_id,
                      SERV_ID = :service_id, 
                      APPT_UPDATED_AT = NOW()
                  WHERE APPT_ID = :appt_id
                  AND DOC_ID = :doc_id";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':appt_date' => $appt_date,
            ':appt_time' => $appt_time,
            ':status_id' => $status_id,
            ':service_id' => $service_id, // Bind new parameter
            ':appt_id' => $appt_id,
            ':doc_id' => $_SESSION['doc_id']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update appointment: No rows affected.']);
        }
    } catch (PDOException $e) {
        error_log("Update appointment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}