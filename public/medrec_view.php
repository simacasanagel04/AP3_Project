<?php
// view medical records

// --- 1. DEPENDENCIES AND SETUP ---
require_once '../config/Database.php'; 
require_once '../classes/Medical_Record.php'; 

// Initialize database connection
$database = new Database();
$db = $database->connect(); 
$medical_record = new MedicalRecord($db);

$action = $_GET['action'] ?? 'view';
$message = '';

// Handle search parameters
$search_medrec_id = $_GET['search_medrec_id'] ?? '';
$search_appt_id = $_GET['search_appt_id'] ?? '';

// --- 2. ACTION HANDLING (Controller Logic) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_datetime = date('Y-m-d H:i:s');
    
    // Best Practice: Sanitize input before setting to object
    $medical_record->setDiagnosis(strip_tags($_POST['diagnosis']));
    $medical_record->setPrescription(strip_tags($_POST['prescription']));
    $medical_record->setVisitDate($_POST['visit_date']);
    
    if ($action === 'create' && isset($_POST['create_record'])) {
        try {
            $medical_record->setCreatedAt($current_datetime);
            $medical_record->setApptId($_POST['appt_id']);
            
            if ($medical_record->create()) {
                $message = "Medical Record created successfully! ID: " . $medical_record->getMedRecId();
                $action = 'view';
            } else {
                $message = "Failed to create Medical Record.";
            }
        } catch (Exception $e) {
            $message = "Error creating record: " . $e->getMessage();
        }
    } elseif ($action === 'update' && isset($_POST['update_record'])) {
        try {
            $medical_record->setMedRecId($_POST['medrec_id']);
            
            if ($medical_record->update()) {
                $message = "Medical Record ID " . $medical_record->getMedRecId() . " updated successfully!";
                $action = 'view';
            } else {
                $message = "Failed to update Medical Record ID " . $_POST['medrec_id'] . ".";
            }
        } catch (Exception $e) {
            $message = "Error updating record: " . $e->getMessage();
        }
    }
} elseif ($action === 'delete') {
    if (isset($_GET['id'])) {
        $medical_record->setMedRecId($_GET['id']);
        if ($medical_record->delete()) {
            $message = "Medical Record ID " . $_GET['id'] . " deleted successfully!";
        } else {
            $message = "Failed to delete Medical Record ID " . $_GET['id'] . ".";
        }
        $action = 'view';
    } else {
        $message = "No ID provided for deletion.";
        $action = 'view';
    }
}


// --- 3. HTML OUTPUT (View Logic - Plain HTML) ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin: Medical Records</title>
</head>
<body style="padding: 20px; font-family: Arial, sans-serif;">
    <h1>Superadmin: Medical Records Management</h1>

    <?php if ($message): ?>
        <p style="color: green; font-weight: bold; border: 1px solid green; padding: 10px; background-color: #e6ffe6;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <p style="margin-bottom: 20px;">
        <a href="?action=view" style="margin-right: 10px;">View All Records</a> | 
        <a href="?action=create_form">Create New Record</a>
    </p>
    <hr>

    <?php if ($action === 'view'): ?>
        <h2>All Medical Records (Live View)</h2>
        
        <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
            <h3>Search Records</h3>
            <form method="GET" action="?" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="action" value="view">
                
                <label for="search_appt_id" style="margin-left: 15px;">Appointment ID:</label>
                <input type="text" id="search_appt_id" name="search_appt_id" value="<?php echo htmlspecialchars($search_appt_id); ?>" placeholder="e.g., 2025-01-0000001" style="padding: 5px;">
                
                <input type="submit" value="Search Records" style="padding: 5px 10px;">
                <?php if (!empty($search_medrec_id) || !empty($search_appt_id)): ?>
                    <a href="?action=view" style="margin-left: 10px;">Clear Search</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php
        // 🚨 The readAllWithDetails method is called with both search parameters
        $stmt_details = $medical_record->readAllWithDetails($search_medrec_id, $search_appt_id);
        $num = $stmt_details->rowCount();
        
        if (!empty($search_medrec_id) || !empty($search_appt_id)) {
            $search_query = [];
            if (!empty($search_medrec_id)) $search_query[] = "Medical ID: <strong>" . htmlspecialchars($search_medrec_id) . "</strong>";
            if (!empty($search_appt_id)) $search_query[] = "Appointment ID: <strong>" . htmlspecialchars($search_appt_id) . "</strong>";
            echo "<p style='font-style: italic;'>Displaying search results for " . implode(" and ", $search_query) . ".</p>";
        }

        if ($num > 0):
        ?>
            <table border="1" cellpadding="10" cellspacing="0" width="100%">
                </table>
        <?php else: ?>
            <p style="color: red; border: 1px dashed red; padding: 10px;">
                No medical records found.
                <?php if (!empty($search_medrec_id) || !empty($search_appt_id)): ?>
                    Please check your search criteria and try again.
                <?php endif; ?>
            </p>
        <?php endif; ?>

    <?php elseif ($action === 'read_one' && isset($_GET['id'])): ?>
        <?php elseif ($action === 'create_form'): ?>
        <div style="border: 1px solid #000; padding: 15px; background-color: #f9f9f9;">
            <h2>Create New Medical Record</h2>
            <?php 
            echo MedicalRecord::renderForm($medical_record, 'create', 'create_record', 'Create Record', 'plain'); 
            ?>
        </div>

    <?php elseif ($action === 'update_form' && isset($_GET['id'])): ?>
        <div style="border: 1px solid #000; padding: 15px; background-color: #f9f9f9;">
            <h2>Update Medical Record</h2>
            <?php
            $medical_record->setMedRecId($_GET['id']);
            if ($medical_record->readOne()):
                 echo MedicalRecord::renderForm($medical_record, 'update', 'update_record', 'Update Record', 'plain'); 
            else: ?>
                <p style="color: red;">Medical Record not found for update.</p>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</body>
</html>