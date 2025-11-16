<?php
// public/ajax/get_schedule_appointments.php
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sched_id = $_GET['sched_id'] ?? null;
    $weekday = $_GET['weekday'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$sched_id || !$weekday) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID and weekday are required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        // Get appointments that fall on this weekday
        $sql = "SELECT 
                    a.APPT_ID,
                    CONCAT(p.PAT_FIRST_NAME, ' ', 
                           COALESCE(CONCAT(p.PAT_MIDDLE_INIT, '. '), ''),
                           p.PAT_LAST_NAME) as patient_name,
                    a.APPT_DATE,
                    a.APPT_TIME,
                    DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_time,
                    DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_date,
                    s.STAT_NAME as STATUS_NAME
                FROM appointment a
                JOIN patient p ON a.PAT_ID = p.PAT_ID
                JOIN status s ON a.STAT_ID = s.STAT_ID
                WHERE a.DOC_ID = ?
                AND DAYNAME(a.APPT_DATE) = ?
                ORDER BY a.APPT_DATE DESC, a.APPT_TIME ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$doc_id, $weekday]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'appointments' => $appointments,
            'count' => count($appointments),
            'weekday' => $weekday
        ]);
    } catch (PDOException $e) {
        error_log("Get schedule appointments error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>