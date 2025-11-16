<?php
// public/ajax/patient_update_appt.php
session_start();
require_once '../../config/Database.php';
require_once '../../classes/Appointment.php';

header('Content-Type: application/json');

// Check if user is logged in as patient
if (!isset($_SESSION['pat_id']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = $_POST['app_id'] ?? null;
    $app_date = $_POST['app_date'] ?? null;
    $app_time = $_POST['app_time'] ?? null;
    $app_status = $_POST['app_status'] ?? null;

    // Validate required fields
    if (!$app_id || !$app_date || !$app_time || !$app_status) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        // ========================================
        // FIX 2: SUNDAY VALIDATION (Server-side)
        // ========================================
        $dayOfWeek = date('w', strtotime($app_date)); // 0=Sun, 6=Sat
        
        if ($dayOfWeek === 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot schedule on Sundays. Clinic is closed. Please select Monday-Saturday.'
            ]);
            exit;
        }

        // Working hours validation
        $time_start = strtotime($app_time);
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

        if (!$isValidTime) {
            $hours = ($dayOfWeek == 6) 
                ? '9:00 AM - 5:00 PM' 
                : '8:00 AM - 6:00 PM';
            echo json_encode([
                'success' => false, 
                'message' => "Selected time is outside working hours ($hours)"
            ]);
            exit;
        }

        // Verify appointment belongs to this patient
        $checkQuery = "SELECT PAT_ID FROM appointment WHERE APPT_ID = :app_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([':app_id' => $app_id]);
        $appt = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appt) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        if ($appt['PAT_ID'] != $_SESSION['pat_id']) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: This is not your appointment']);
            exit;
        }

        // ========================================
        // FIX 3: UPDATE WITH STATUS (was missing)
        // ========================================
        $updateQuery = "UPDATE appointment 
                       SET APPT_DATE = :app_date,
                           APPT_TIME = :app_time,
                           STAT_ID = :stat_id,
                           APPT_UPDATED_AT = NOW()
                       WHERE APPT_ID = :app_id 
                       AND PAT_ID = :pat_id";
        
        $stmt = $db->prepare($updateQuery);
        $result = $stmt->execute([
            ':app_date' => $app_date,
            ':app_time' => $app_time,
            ':stat_id' => $app_status,  // ← THIS WAS THE MISSING FIX!
            ':app_id' => $app_id,
            ':pat_id' => $_SESSION['pat_id']
        ]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No changes made or appointment not found'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Patient update appointment error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>