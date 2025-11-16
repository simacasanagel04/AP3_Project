<?php
// public/api/gdoc-by-service.php

require_once dirname(__DIR__, 2) . '/config/Database.php'; 
require_once dirname(__DIR__, 2) . '/classes/Service.php';
require_once dirname(__DIR__, 2) . '/classes/Doctor.php';

header('Content-Type: application/json');
error_reporting(E_ALL);

$response = ['success' => false, 'doctors' => [], 'message' => ''];

if (!isset($_GET['serv_id']) || !is_numeric($_GET['serv_id'])) {
    $response['message'] = 'Invalid Service ID provided.';
    echo json_encode($response);
    exit();
}

$serv_id = intval($_GET['serv_id']);

try {
    // Database connection
    $database = new Database(); 
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get service data
    $service = new Service($db);
    $service->setServId($serv_id);
    $service_data = $service->readOne();
    
    if (!$service_data) {
        $response['message'] = 'Service not found (ID: ' . $serv_id . ')';
        echo json_encode($response);
        exit();
    }
    
    if (empty($service_data['SPEC_ID'])) {
        $response['message'] = 'Service "' . $service_data['SERV_NAME'] . '" is not linked to any specialization.';
        echo json_encode($response);
        exit();
    }
    
    $spec_id = intval($service_data['SPEC_ID']);
    
    // Get doctors by specialization
    $doctor = new Doctor($db);
    $doctors_list = $doctor->getDoctorsBySpecialization($spec_id);
    
    if (empty($doctors_list)) {
        $response['message'] = 'No doctors available for this specialization.';
    } else {
        $response['success'] = true;
        $response['doctors'] = $doctors_list;
    }
    
} catch (PDOException $e) {
    error_log("Database error in gdoc-by-service.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
} catch (Exception $e) {
    error_log("Error in gdoc-by-service.php: " . $e->getMessage()); 
    $response['message'] = 'Server error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>