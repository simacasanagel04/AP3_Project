<?php
// public/ajax/patient_get_avail_times.php
session_start();
require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$database = new Database();
$db = $database->connect();

$specId = intval($_GET['spec_id']);
$date = $_GET['date'];
$currentApptId = isset($_GET['current_appt_id']) ? $_GET['current_appt_id'] : null;

try {
    // Get day name from date (e.g., "Monday")
    $dayName = date('l', strtotime($date));
    
    // Get doctors with schedules for this specialization on this day
    $sql = "SELECT DISTINCT 
                d.DOC_ID as doctor_id,
                CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME) as doctor_name,
                s.SCHED_START_TIME,
                s.SCHED_END_TIME
            FROM doctor d
            INNER JOIN schedule s ON d.DOC_ID = s.DOC_ID
            WHERE d.SPEC_ID = :spec_id
            AND s.SCHED_DAYS = :day_name";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':spec_id' => $specId,
        ':day_name' => $dayName
    ]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $timeSlots = [];
    
    foreach ($doctors as $doctor) {
        $startTime = new DateTime($doctor['SCHED_START_TIME']);
        $endTime = new DateTime($doctor['SCHED_END_TIME']);
        
        while ($startTime < $endTime) {
            $time = $startTime->format('H:i:s');
            $formatted = $startTime->format('g:i A');
            
            // Check if slot is already booked (excluding current appointment if updating)
            $checkSql = "SELECT COUNT(*) as count FROM appointment 
                        WHERE DOC_ID = :doc_id 
                        AND APPT_DATE = :date 
                        AND APPT_TIME = :time
                        AND STAT_ID != 3"; // Exclude cancelled
            
            // If updating, exclude the current appointment
            if ($currentApptId) {
                $checkSql .= " AND APPT_ID != :current_appt_id";
            }
            
            $checkStmt = $db->prepare($checkSql);
            $params = [
                ':doc_id' => $doctor['doctor_id'],
                ':date' => $date,
                ':time' => $time
            ];
            
            if ($currentApptId) {
                $params[':current_appt_id'] = $currentApptId;
            }
            
            $checkStmt->execute($params);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Add time slot if not booked
            if ($result['count'] == 0) {
                $timeSlots[] = [
                    'time' => $time,
                    'formatted' => $formatted,
                    'doctor_id' => $doctor['doctor_id'],
                    'doctor_name' => $doctor['doctor_name']
                ];
            }
            
            $startTime->modify('+30 minutes');
        }
    }

    echo json_encode([
        'success' => true,
        'timeSlots' => $timeSlots,
        'debug' => [
            'date' => $date,
            'day_name' => $dayName,
            'doctors_found' => count($doctors),
            'slots_available' => count($timeSlots)
        ]
    ]);
} catch (PDOException $e) {
    error_log("Error fetching time slots: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>