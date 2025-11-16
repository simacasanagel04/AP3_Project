<?php
// public/ajax/add_schedule.php
session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['doc_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $doc_id = $_SESSION['doc_id'];

    if (!$date || !$start_time || !$end_time) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate working hours
    $dayOfWeek = date('w', strtotime($date));
    $start = strtotime($start_time);
    $end = strtotime($end_time);

    // Check if Sunday
    if ($dayOfWeek == 0) {
        echo json_encode(['success' => false, 'message' => 'Sunday is closed']);
        exit;
    }

    // Validate time range
    if ($start >= $end) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }

    // Check working hours
    if ($dayOfWeek == 6) { // Saturday
        $min_start = strtotime('09:00');
        $max_end = strtotime('17:00');
        if ($start < $min_start || $end > $max_end) {
            echo json_encode(['success' => false, 'message' => 'Saturday hours: 9:00 AM - 5:00 PM']);
            exit;
        }
    } else { // Monday-Friday
        $min_start = strtotime('08:00');
        $max_end = strtotime('18:00');
        if ($start < $min_start || $end > $max_end) {
            echo json_encode(['success' => false, 'message' => 'Monday-Friday hours: 8:00 AM - 6:00 PM']);
            exit;
        }
    }

    try {
        $db = (new Database())->connect();
        
        // Check for overlapping schedules on the SAME DATE
        $checkSql = "SELECT COUNT(*) FROM schedule 
                     WHERE DOC_ID = ? 
                     AND SCHED_DAYS = ?
                     AND ((SCHED_START_TIME < ? AND SCHED_END_TIME > ?) 
                     OR (SCHED_START_TIME < ? AND SCHED_END_TIME > ?))";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$doc_id, $date, $end_time, $start_time, $end_time, $start_time]);
        
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Schedule overlaps with existing schedule']);
            exit;
        }

        // Insert the schedule with the actual DATE
        $sql = "INSERT INTO schedule (DOC_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, SCHED_CREATED_AT) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([$doc_id, $date, $start_time, $end_time]);

        if ($result) {
            $sched_id = $db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Schedule added successfully',
                'sched_id' => $sched_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add schedule']);
        }
    } catch (PDOException $e) {
        error_log("Add schedule error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>