<?php
// public/ajax/patient_get_avail_times.php
// FULLY FIXED for cp850 + update modal + current appointment exclusion

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
$currentApptId = $_GET['current_appt_id'] ?? null;

try {
    $dayOfWeek = date('l', strtotime($date)); // Monday, Tuesday, etc.

    if ($dayOfWeek === 'Sunday') {
        echo json_encode(['success' => false, 'message' => 'Clinic closed on Sundays']);
        exit;
    }

    $sql = "SELECT 
                d.DOC_ID,
                d.DOC_FIRST_NAME,
                d.DOC_LAST_NAME,
                s.SCHED_START_TIME,
                s.SCHED_END_TIME
            FROM doctor d
            INNER JOIN schedule s ON d.DOC_ID = s.DOC_ID
            WHERE d.SPEC_ID = :spec_id
              AND s.SCHED_DAYS = :day_of_week";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId, ':day_of_week' => $dayOfWeek]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($doctors)) {
        echo json_encode(['success' => true, 'timeSlots' => []]);
        exit;
    }

    $timeSlots = [];

    foreach ($doctors as $doctor) {
        $start = strtotime($doctor['SCHED_START_TIME']);
        $end = strtotime($doctor['SCHED_END_TIME']);
        $current = $start;

        while ($current < $end) {
            $timeStr = date('H:i:s', $current);

            // SAFE COLLATION-FREE CHECK (works on cp850, latin1, utf8mb4)
            $checkSql = "SELECT 1 FROM appointment 
                         WHERE DOC_ID = :doc_id 
                           AND APPT_DATE = :date 
                           AND APPT_TIME = :time 
                           AND STAT_ID != 3";

            if ($currentApptId) {
                $checkSql .= " AND APPT_ID != :current_appt_id"; // ← NO COLLATE!
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
            
            if ($checkStmt->rowCount() === 0) {
                $timeSlots[] = [
                    'time' => $timeStr,
                    'formatted' => date('g:i A', $current),
                    'doctor_id' => $doctor['DOC_ID'],
                    'doctor_name' => $doctor['DOC_LAST_NAME'] . ', ' . $doctor['DOC_FIRST_NAME']
                ];
            }

            $current = strtotime('+30 minutes', $current);
        }
    }

    echo json_encode([
        'success' => true,
        'timeSlots' => $timeSlots
    ]);

} catch (Exception $e) {
    error_log("patient_get_avail_times.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>