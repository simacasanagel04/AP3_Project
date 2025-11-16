<?php
// public/ajax/patient_get_avail_dates.php
// FIXED VERSION - Returns dates based on weekday schedules

session_start();
require_once __DIR__ . '/../../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['spec_id'])) {
    echo json_encode(['success' => false, 'message' => 'Specialization ID required']);
    exit;
}

$database = new Database();
$db = $database->connect();

$specId = intval($_GET['spec_id']);

try {
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
                    ELSE 7
                END";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId]);
    $weekdays = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Convert weekday names to numbers (1=Monday, 7=Sunday)
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
        $dayOfWeek = (int)$currentDate->format('N'); // 1=Monday, 7=Sunday
        
        // Convert to PHP's 0=Sunday format for comparison
        $phpDayOfWeek = ($dayOfWeek == 7) ? 0 : $dayOfWeek;
        
        // Check if this weekday is available
        if (in_array($phpDayOfWeek, $availableWeekdayNumbers)) {
            $dates[] = $currentDate->format('Y-m-d');
        }
        
        $currentDate->modify('+1 day');
    }

    echo json_encode([
        'success' => true,
        'dates' => $dates,
        'available_weekdays' => $weekdays,
        'debug' => [
            'spec_id' => $specId,
            'weekdays_found' => count($weekdays),
            'dates_generated' => count($dates)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error fetching dates: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>