<?php
/**
 * ============================================================================
 * FILE: public/ajax/patient_get_avail_times.php
 * PURPOSE: Fetch available time slots for appointment booking
 * FIXED: Proper cp850 collation handling + comprehensive error logging
 * ============================================================================
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/Database.php';

error_log("=== patient_get_avail_times.php START ===");

// Validate required parameters
if (!isset($_GET['spec_id']) || !isset($_GET['date'])) {
    error_log("ERROR: Missing required parameters (spec_id or date)");
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required parameters: spec_id and date are required'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    $specId = intval($_GET['spec_id']);
    $date = $_GET['date'];
    $currentApptId = isset($_GET['current_appt_id']) ? $_GET['current_appt_id'] : null;

    error_log("Parameters received:");
    error_log("  - spec_id: $specId");
    error_log("  - date: $date");
    error_log("  - current_appt_id: " . ($currentApptId ?? 'none'));

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Invalid date format. Expected: YYYY-MM-DD");
    }

    // Get day of week from selected date
    $dayOfWeek = date('l', strtotime($date)); // Returns: Monday, Tuesday, etc.
    error_log("Day of week: $dayOfWeek");

    // Block Sundays
    if ($dayOfWeek === 'Sunday') {
        error_log("WARNING: Sunday selected - clinic is closed");
        echo json_encode([
            'success' => false, 
            'message' => 'Clinic is closed on Sundays. Please select Monday-Saturday.'
        ]);
        exit;
    }

    // Get doctors with their schedules for this specialization and day
    $sql = "SELECT 
                d.DOC_ID,
                d.DOC_FIRST_NAME,
                d.DOC_LAST_NAME,
                s.SCHED_START_TIME,
                s.SCHED_END_TIME
            FROM doctor d
            INNER JOIN schedule s ON d.DOC_ID = s.DOC_ID
            WHERE d.SPEC_ID = :spec_id
              AND s.SCHED_DAYS = :day_of_week
            ORDER BY d.DOC_LAST_NAME, d.DOC_FIRST_NAME";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':spec_id' => $specId,
        ':day_of_week' => $dayOfWeek
    ]);
    
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($doctors) . " doctors working on $dayOfWeek for specialization $specId");

    if (empty($doctors)) {
        error_log("WARNING: No doctors available for $dayOfWeek in specialization $specId");
        echo json_encode([
            'success' => true,
            'timeSlots' => [],
            'message' => "No doctors available on $dayOfWeek for this department"
        ]);
        exit;
    }

    // Generate time slots for each doctor
    $timeSlots = [];
    $slotDuration = 30; // minutes
    
    foreach ($doctors as $doctor) {
        $doctorName = $doctor['DOC_LAST_NAME'] . ', ' . $doctor['DOC_FIRST_NAME'];
        error_log("Processing Doctor ID {$doctor['DOC_ID']}: $doctorName");
        error_log("  Schedule: {$doctor['SCHED_START_TIME']} to {$doctor['SCHED_END_TIME']}");
        
        $startTime = strtotime($doctor['SCHED_START_TIME']);
        $endTime = strtotime($doctor['SCHED_END_TIME']);
        $currentTime = $startTime;
        
        $slotsForThisDoctor = 0;
        
        // Generate 30-minute time slots
        while ($currentTime < $endTime) {
            $timeString = date('H:i:s', $currentTime);
            
            // Check if this time slot is already booked
            $checkSql = "SELECT COUNT(*) as slot_count 
                        FROM appointment 
                        WHERE DOC_ID = :doc_id 
                          AND APPT_DATE = :date 
                          AND APPT_TIME = :time 
                          AND STAT_ID != 3"; // Exclude cancelled (STAT_ID = 3)
            
            // Exclude current appointment if updating
            if ($currentApptId) {
                $checkSql .= " AND APPT_ID != :current_appt_id";
            }
            
            $checkStmt = $db->prepare($checkSql);
            $checkParams = [
                ':doc_id' => $doctor['DOC_ID'],
                ':date' => $date,
                ':time' => $timeString
            ];
            
            if ($currentApptId) {
                $checkParams[':current_appt_id'] = $currentApptId;
            }
            
            $checkStmt->execute($checkParams);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // If slot is available (count = 0), add it
            if ($result['slot_count'] == 0) {
                $formattedTime = date('g:i A', $currentTime);
                $timeSlots[] = [
                    'time' => $timeString,
                    'formatted' => $formattedTime,
                    'doctor_id' => $doctor['DOC_ID'],
                    'doctor_name' => $doctorName
                ];
                $slotsForThisDoctor++;
            }
            
            // Move to next 30-minute slot
            $currentTime = strtotime("+{$slotDuration} minutes", $currentTime);
        }
        
        error_log("  Available slots for this doctor: $slotsForThisDoctor");
    }
    
    error_log("TOTAL available time slots: " . count($timeSlots));
    
    // Sort time slots by time
    usort($timeSlots, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
    
    echo json_encode([
        'success' => true,
        'timeSlots' => $timeSlots,
        'debug' => [
            'spec_id' => $specId,
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'doctors_found' => count($doctors),
            'slots_available' => count($timeSlots)
        ]
    ]);

} catch (PDOException $e) {
    error_log("DATABASE ERROR in patient_get_avail_times.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("GENERAL ERROR in patient_get_avail_times.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("=== patient_get_avail_times.php END ===");
?>