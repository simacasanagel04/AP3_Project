<?php

// public/ajax/patient_get_avail_times.php
// for user patient

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

try {
    // Get doctors with schedules for this specialization and date
    $sql = "SELECT DISTINCT 
                d.DOC_ID as doctor_id,
                CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME) as doctor_name,
                s.SCHED_START_TIME,
                s.SCHED_END_TIME
            FROM doctor d
            INNER JOIN schedule s ON d.DOC_ID = s.DOC_ID
            WHERE d.SPEC_ID = :spec_id
            AND (s.SCHED_DAYS = :date OR s.SCHED_DAYS = '0000-00-00')";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':spec_id' => $specId,
        ':date' => $date
    ]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $timeSlots = [];
    foreach ($doctors as $doctor) {
        $startTime = new DateTime($doctor['SCHED_START_TIME']);
        $endTime = new DateTime($doctor['SCHED_END_TIME']);
        
        while ($startTime < $endTime) {
            $time = $startTime->format('H:i:s');
            $formatted = $startTime->format('g:i A');
            
            // Check if slot is already booked
            $checkSql = "SELECT COUNT(*) as count FROM appointment 
                        WHERE DOC_ID = :doc_id 
                        AND APPT_DATE = :date 
                        AND APPT_TIME = :time
                        AND STAT_ID != 3"; // Exclude cancelled
            
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([
                ':doc_id' => $doctor['doctor_id'],
                ':date' => $date,
                ':time' => $time
            ]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                $timeSlots[] = [
                    'time' => $time,
                    'formatted' => $formatted,
                    'doctor_id' => $doctor['doctor_id'],
                    'doctor_name' => $doctor['doctor_name']
                ];
            }
            
            $startTime->modify('+30 minutes'); // 30-minute slots
        }
    }

    echo json_encode([
        'success' => true,
        'timeSlots' => $timeSlots
    ]);
} catch (PDOException $e) {
    error_log("Error fetching time slots: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>