<?php

// public/ajax/patient_get_serv_by_spec.php
// for user patient

session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header BEFORE any output
header('Content-Type: application/json');

// Include database
require_once __DIR__ . '/../../config/Database.php';

// Log the request
error_log("Service request received - spec_id: " . ($_GET['spec_id'] ?? 'NOT SET'));

// Validate input
if (!isset($_GET['spec_id']) || empty($_GET['spec_id'])) {
    echo json_encode(['success' => false, 'message' => 'Specialization ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $specId = intval($_GET['spec_id']);
    error_log("Querying services for spec_id: " . $specId);
    
    $sql = "SELECT SERV_ID as serv_id, 
                   SERV_NAME as serv_name, 
                   SERV_DESCRIPTION as serv_description, 
                   SERV_PRICE as serv_price,
                   SPEC_ID as spec_id
            FROM service 
            WHERE SPEC_ID = :spec_id
            ORDER BY SERV_NAME";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($services) . " services");

    if (empty($services)) {
        echo json_encode([
            'success' => true,
            'services' => [],
            'message' => 'No services found for this department'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'services' => $services,
            'count' => count($services)
        ]);
    }
} catch (PDOException $e) {
    error_log("PDO Error fetching services: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>