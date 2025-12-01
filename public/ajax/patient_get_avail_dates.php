<?php
/**
 * ============================================================================
 * FILE: public/ajax/patient_get_avail_dates.php
 * PURPOSE: Fetch available dates based on doctor schedules (Monday-Saturday)
 * ============================================================================
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/Database.php';

error_log("=== patient_get_avail_dates.php START ===");

// Validate required parameter
if (!isset($_GET['spec_id']) || empty($_GET['spec_id'])) {
    error_log("ERROR: Missing spec_id parameter");
    echo json_encode([
        'success' => false,
        'message' => 'Specialization ID is required'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    
    $specId = intval($_GET['spec_id']);
    error_log("Fetching available dates for spec_id: $specId");

    // Get all unique weekdays that doctors in this specialization work
    $sql = "SELECT DISTINCT s.SCHED_DAYS
            FROM schedule s
            INNER JOIN doctor d ON s.DOC_ID = d.DOC_ID
            WHERE d.SPEC_ID = :spec_id
              AND s.SCHED_DAYS IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
            ORDER BY 
                CASE s.SCHED_DAYS
                    WHEN 'Monday' THEN 1
                    WHEN 'Tuesday' THEN 2
                    WHEN 'Wednesday' THEN 3
                    WHEN 'Thursday' THEN 4
                    WHEN 'Friday' THEN 5
                    WHEN 'Saturday' THEN 6
                END";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId]);
    $weekdays = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Available weekdays: " . implode(', ', $weekdays));

    if (empty($weekdays)) {
        error_log("WARNING: No schedules found for specialization $specId");
        echo json_encode([
            'success' => true,
            'dates' => [],
            'available_weekdays' => [],
            'message' => 'No available dates - no doctors scheduled for this department'
        ]);
        exit;
    }

    // Map weekday names to numbers (1=Monday, 7=Sunday)
    $weekdayMap = [
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6,
        'Sunday' => 0
    ];

    $availableWeekdayNumbers = [];
    foreach ($weekdays as $day) {
        if (isset($weekdayMap[$day])) {
            $availableWeekdayNumbers[] = $weekdayMap[$day];
        }
    }

    // Generate dates for the next 30 days that match available weekdays
    $dates = [];
    $today = new DateTime();
    $endDate = (new DateTime())->modify('+30 days');
    $currentDate = clone $today;

    while ($currentDate <= $endDate) {
        // Get day of week (1=Monday, 7=Sunday in ISO-8601)
        $dayOfWeek = (int)$currentDate->format('N');
        
        // Convert to 0=Sunday format for comparison
        $phpDayOfWeek = ($dayOfWeek == 7) ? 0 : $dayOfWeek;
        
        // Check if this weekday is available
        if (in_array($phpDayOfWeek, $availableWeekdayNumbers)) {
            $dates[] = $currentDate->format('Y-m-d');
        }
        
        $currentDate->modify('+1 day');
    }

    error_log("Generated " . count($dates) . " available dates");

    echo json_encode([
        'success' => true,
        'dates' => $dates,
        'available_weekdays' => $weekdays,
        'debug' => [
            'spec_id' => $specId,
            'weekdays_count' => count($weekdays),
            'dates_count' => count($dates)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error in patient_get_avail_dates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in patient_get_avail_dates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("=== patient_get_avail_dates.php END ===");
?>