// ajax/patient_get_avail_times.php

<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Schedule.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$database = new Database();
$db = $database->connect();
$schedule = new Schedule($db);

$specId = intval($_GET['spec_id']);
$date = $_GET['date'];

// Get available doctors for this date and specialization
$doctors = $schedule->getAvailableDoctors($specId, $date);

$allTimeSlots = [];

foreach ($doctors as $doctor) {
    $slots = $schedule->getAvailableTimeSlots($doctor['DOC_ID'], $date);
    
    foreach ($slots as $slot) {
        $allTimeSlots[] = [
            'time' => $slot['time'],
            'formatted' => $slot['formatted'],
            'doctor_id' => $doctor['DOC_ID'],
            'doctor_name' => $doctor['doctor_name']
        ];
    }
}

// Sort by time
usort($allTimeSlots, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});

echo json_encode([
    'success' => true,
    'timeSlots' => $allTimeSlots
]);
?>