// ajax/patient_get_avail_dates.php

<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Schedule.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id'])) {
    echo json_encode(['success' => false, 'message' => 'Specialization ID required']);
    exit;
}

$database = new Database();
$db = $database->connect();
$schedule = new Schedule($db);

$specId = intval($_GET['spec_id']);
$availableDates = $schedule->getAvailableDatesBySpecialization($specId, 30);

// Extract just the dates
$dates = array_unique(array_map(function($item) {
    return $item['SCHED_DAYS'];
}, $availableDates));

echo json_encode([
    'success' => true,
    'dates' => array_values($dates)
]);
?>