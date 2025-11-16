<?php
// public/api/gdoc-by-service.php

// **ASSUMED PATHS**: If config/ and classes/ are in the project root (AP3 PROJECT/)
require_once '../../config/Database.php'; 
require_once '../../classes/Service.php';
require_once '../../classes/Doctor.php';

header('Content-Type: application/json');
error_reporting(E_ALL);

$response = ['success' => false, 'doctors' => [], 'message' => ''];

// More robust check for service ID
if (!isset($_GET['serv_id']) || !is_numeric($_GET['serv_id']) || trim($_GET['serv_id']) == "") {
    $response['message'] = 'Invalid or Missing Service ID provided.';
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
        // DO NOT exit here, let the final echo run
    } else {
        if (empty($service_data['SPEC_ID'])) {
            $response['message'] = 'Service "' . $service_data['SERV_NAME'] . '" is not linked to any specialization.';
            // DO NOT exit here
        } else {
            $spec_id = intval($service_data['SPEC_ID']);
            
            // Get doctors by specialization
            $doctor = new Doctor($db);
            // Assuming Doctor class has a working getDoctorsBySpecialization method
            $doctors_list = $doctor->getDoctorsBySpecialization($spec_id);
            
            if (empty($doctors_list)) {
                $response['message'] = 'No doctors available for this specialization.';
            } else {
                $response['success'] = true;
                $response['doctors'] = $doctors_list;
                $response['message'] = 'Doctors loaded successfully.';
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error in gdoc-by-service.php: " . $e->getMessage());
    $response['message'] = 'Database error occurred: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in gdoc-by-service.php: " . $e->getMessage()); 
    $response['message'] = 'An unexpected error occurred.';
}

// CRITICAL: Ensure the response is always sent
echo json_encode($response);
?>