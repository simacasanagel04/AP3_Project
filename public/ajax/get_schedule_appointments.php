<?php
// public/ajax/get_schedule_appointments.php
// for doctor_schedule.php


// Fetches appointments for a specific schedule date
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sched_id = $_GET['sched_id'] ?? null;
    $sched_date = $_GET['sched_date'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$sched_id || !$sched_date) {
        echo json_encode(['success' => false, 'message' => 'Schedule ID and date are required']);
        exit;
    }

    try {
        $db = (new Database())->connect();
        
        // Get appointments for this schedule date
        $sql = "SELECT 
                    a.APPT_ID,
                    CONCAT(p.PAT_FIRST_NAME, ' ', 
                           COALESCE(CONCAT(p.PAT_MIDDLE_INIT, '. '), ''),
                           p.PAT_LAST_NAME) as patient_name,
                    a.APPT_TIME,
                    DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_time,
                    s.STATUS_NAME
                FROM appointment a
                JOIN patient p ON a.PAT_ID = p.PAT_ID
                JOIN status s ON a.STAT_ID = s.STAT_ID
                WHERE a.DOC_ID = ?
                AND a.APPT_DATE = ?
                ORDER BY a.APPT_TIME ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$doc_id, $sched_date]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'appointments' => $appointments,
            'count' => count($appointments)
        ]);
    } catch (PDOException $e) {
        error_log("Get schedule appointments error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>