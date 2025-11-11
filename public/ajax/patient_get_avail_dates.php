<?php
// public/ajax/patient_get_avail_dates.php
// for user patient

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
    // Get all doctors in this specialization with schedules
    $sql = "SELECT DISTINCT s.SCHED_DAYS
            FROM schedule s
            INNER JOIN doctor d ON s.DOC_ID = d.DOC_ID
            WHERE d.SPEC_ID = :spec_id
            AND (s.SCHED_DAYS >= CURDATE() OR s.SCHED_DAYS = '0000-00-00')
            ORDER BY s.SCHED_DAYS";

    $stmt = $db->prepare($sql);
    $stmt->execute([':spec_id' => $specId]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dates = [];
    $today = new DateTime();
    $endDate = (new DateTime())->modify('+30 days');

    foreach ($schedules as $sched) {
        if ($sched['SCHED_DAYS'] == '0000-00-00') {
            // Recurring schedule - add next 30 days
            $currentDate = clone $today;
            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        } else {
            // Specific date
            $schedDate = new DateTime($sched['SCHED_DAYS']);
            if ($schedDate >= $today && $schedDate <= $endDate) {
                $dates[] = $sched['SCHED_DAYS'];
            }
        }
    }

    $dates = array_unique($dates);
    sort($dates);

    echo json_encode([
        'success' => true,
        'dates' => array_values($dates)
    ]);
} catch (PDOException $e) {
    error_log("Error fetching dates: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>