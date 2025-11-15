<?php
// CRITICAL: Adjust these paths based on where you place this file
// Example assumes this file is in 'api' and the classes/config folders are two levels up.
require_once dirname(__DIR__, 2) . '/config/database.php'; 
require_once dirname(__DIR__, 2) . '/classes/Service.php';
require_once dirname(__DIR__, 2) . '/classes/Doctor.php';

header('Content-Type: application/json');

$response = ['success' => false, 'doctors' => [], 'message' => ''];

if (isset($_GET['serv_id']) && is_numeric($_GET['serv_id'])) {
    $serv_id = $_GET['serv_id'];

    try {
        // Initialize DB connection and classes (assuming Database class is defined)
        $database = new Database(); 
        $db = $database->connect();
        $service = new Service($db);
        $doctor = new Doctor($db); 

        // 1. Get the service data to find the SPEC_ID
        $service_data = $service->readOne($serv_id);
        
        if ($service_data && !empty($service_data['SPEC_ID'])) {
            $spec_id = $service_data['SPEC_ID'];
            
            // 2. Get doctors belonging to that specialization
            $doctors_list = $doctor->getDoctorsBySpecialization($spec_id);

            $response['success'] = true;
            $response['doctors'] = $doctors_list;
        } else {
            $response['message'] = 'Service not found or is not linked to a specialization.';
        }
    } catch (Exception $e) {
        // Log the error for internal debugging
        error_log("Doctor filtering error: " . $e->getMessage()); 
        $response['message'] = 'A server error occurred while fetching doctors.';
    }
} else {
    $response['message'] = 'Invalid Service ID provided.';
}

echo json_encode($response);
exit();

?>
