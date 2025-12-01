<?php
/**
 * ============================================================================
 * FILE: public/ajax/patient_get_serv_by_spec.php
 * PURPOSE: Fetch services by specialization for patient booking
 * ============================================================================
 */

session_start();

// Disable HTML error output, enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CRITICAL: Set JSON header BEFORE any output
header('Content-Type: application/json; charset=UTF-8');

// Include database connection
require_once __DIR__ . '/../../config/Database.php';

error_log("=== patient_get_serv_by_spec.php START ===");

// Validate required parameter
if (!isset($_GET['spec_id']) || empty($_GET['spec_id'])) {
    error_log("ERROR: Missing or empty spec_id parameter");
    echo json_encode([
        'success' => false,
        'message' => 'Specialization ID is required'
    ]);
    exit;
}

try {
    // Connect to database
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $specId = intval($_GET['spec_id']);
    error_log("Fetching services for spec_id: $specId");
    
    // Query to fetch services by specialization
    $sql = "SELECT 
                SERV_ID as serv_id, 
                SERV_NAME as serv_name, 
                SERV_DESCRIPTION as serv_description, 
                SERV_PRICE as serv_price,
                SPEC_ID as spec_id
            FROM service 
            WHERE SPEC_ID = :spec_id
            ORDER BY SERV_NAME ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($services) . " services for spec_id $specId");

    if (empty($services)) {
        echo json_encode([
            'success' => true,
            'services' => [],
            'message' => 'No services found for this department'
        ]);
    } else {
        // Log first service for verification
        if (count($services) > 0) {
            error_log("Sample service: " . json_encode($services[0]));
        }
        
        echo json_encode([
            'success' => true,
            'services' => $services,
            'count' => count($services)
        ]);
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in patient_get_serv_by_spec.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error in patient_get_serv_by_spec.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("=== patient_get_serv_by_spec.php END ===");
?>