<?php
// public/ajax/update_schedule.php
// for doctor_schedule.php

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sched_id = $_POST['sched_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$sched_id || !$date || !$start_time || !$end_time) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate working hours (same as add_schedule.php)
    $dayOfWeek = date('w', strtotime($date));
    $start = strtotime($start_time);
    $end = strtotime($end_time);

    if ($dayOfWeek == 0) {
        echo json_encode(['success' => false, 'message' => 'Sunday is closed']);
        exit;
    }

    if ($start >= $end) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }

    if ($dayOfWeek == 6) {
        $min_start = strtotime('09:00');
        $max_end = strtotime('17:00');
        if ($start < $min_start || $end > $max_end) {
            echo json_encode(['success' => false, 'message' => 'Saturday hours: 9:00 AM - 5:00 PM']);
            exit;
        }
    } else {
        $min_start = strtotime('08:00');
        $max_end = strtotime('18:00');
        if ($start < $min_start || $end > $max_end) {
            echo json_encode(['success' => false, 'message' => 'Monday-Friday hours: 8:00 AM - 6:00 PM']);
            exit;
        }
    }

    try {
        $db = (new Database())->connect();
        
        $sql = "UPDATE schedule 
                SET SCHED_DAYS = ?, 
                    SCHED_START_TIME = ?, 
                    SCHED_END_TIME = ?, 
                    SCHED_UPDATED_AT = NOW() 
                WHERE SCHED_ID = ? AND DOC_ID = ?";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$date, $start_time, $end_time, $sched_id, $doc_id]);

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or schedule not found']);
        }
    } catch (PDOException $e) {
        error_log("Update schedule error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>