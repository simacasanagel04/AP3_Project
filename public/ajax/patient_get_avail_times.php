<?php
// public/ajax/patient_get_avail_times.php
session_start();
require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$database = new Database();
$db = $database->connect();

$specId = intval($_GET['spec_id']);
$date = $_GET['date'];
$currentApptId = $_GET['current_appt_id'] ?? null; // For update modal

try {
    // Get the day of week from the selected date
    $dayOfWeek = date('l', strtotime($date)); // Returns 'Monday', 'Tuesday', etc.
    
    // Validate not Sunday
    if ($dayOfWeek === 'Sunday') {
        echo json_encode([
            'success' => false,
            'message' => 'Clinic is closed on Sundays'
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
            ORDER BY s.SCHED_START_TIME";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':spec_id' => $specId,
        ':day_of_week' => $dayOfWeek
    ]);
    
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($doctors)) {
        echo json_encode([
            'success' => true,
            'timeSlots' => [],
            'message' => 'No doctors available on this day'
        ]);
        exit;
    }
    
    // Generate time slots for each doctor
    $timeSlots = [];
    
    foreach ($doctors as $doctor) {
        $startTime = strtotime($doctor['SCHED_START_TIME']);
        $endTime = strtotime($doctor['SCHED_END_TIME']);
        
        // Generate 30-minute slots
        $currentTime = $startTime;
        while ($currentTime < $endTime) {
            $timeStr = date('H:i:s', $currentTime);
            
            // Check if this time slot is already booked (COLLATION FIX)
            $checkSql = "SELECT COUNT(*) as count 
                        FROM appointment 
                        WHERE DOC_ID = :doc_id 
                        AND APPT_DATE = :date 
                        AND APPT_TIME = :time 
                        AND STAT_ID != 3"; // Exclude cancelled
            
            // If updating existing appointment, exclude current appointment
            if ($currentApptId) {
                $checkSql .= " AND APPT_ID COLLATE utf8mb4_0900_ai_ci != :current_appt_id COLLATE utf8mb4_0900_ai_ci";
            }
            
            $checkStmt = $db->prepare($checkSql);
            $params = [
                ':doc_id' => $doctor['DOC_ID'],
                ':date' => $date,
                ':time' => $timeStr
            ];
            
            if ($currentApptId) {
                $params[':current_appt_id'] = $currentApptId;
            }
            
            $checkStmt->execute($params);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Only add if not booked
            if ($result['count'] == 0) {
                $timeSlots[] = [
                    'time' => $timeStr,
                    'formatted' => date('g:i A', $currentTime),
                    'doctor_id' => $doctor['DOC_ID'],
                    'doctor_name' => $doctor['DOC_LAST_NAME'] . ', ' . $doctor['DOC_FIRST_NAME']
                ];
            }
            
            $currentTime = strtotime('+30 minutes', $currentTime);
        }
    }
    
    echo json_encode([
        'success' => true,
        'timeSlots' => $timeSlots,
        'debug' => [
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'doctors_found' => count($doctors),
            'slots_available' => count($timeSlots)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching time slots: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>