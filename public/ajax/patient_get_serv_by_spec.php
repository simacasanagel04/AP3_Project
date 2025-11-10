// ajax/patient_get_serv_by_spec.php

<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Specialization.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id'])) {
    echo json_encode(['success' => false, 'message' => 'Specialization ID required']);
    exit;
}

$database = new Database();
$db = $database->connect();
$specialization = new Specialization($db);

$specId = intval($_GET['spec_id']);
$services = $specialization->getServicesBySpecialization($specId);

echo json_encode([
    'success' => true,
    'services' => $services
]);
?>