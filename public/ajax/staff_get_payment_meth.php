<?php
// for Staff Payment

session_start();
require_once '../../config/Database.php';

header('Content-Type: application/json');

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = (new Database())->connect();
    $sql = "SELECT PYMT_METH_ID, PYMT_METH_NAME FROM payment_method ORDER BY PYMT_METH_NAME";
    $stmt = $db->query($sql);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'methods' => $methods]);
} catch (PDOException $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>