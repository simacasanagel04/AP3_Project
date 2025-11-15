<?php
// -----------------------------------------------------
// public/staff_medical_records.php
// Staff: VIEW ONLY Medical Records (NO edit/delete/create)
// -----------------------------------------------------

session_start();
require_once '../config/Database.php';
require_once '../classes/Medical_Record.php';

// Check if logged in BEFORE including header
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$database = new Database();
$db = $database->connect();
$record = new MedicalRecord($db); //

// Fetch all medical records
$stmt = $record->readAll();

// NOW include header after session check
require_once '../includes/staff_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Records | AKSyon Medical Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        footer {
            background: #f1f1f1;
            color: #333;
            text-align: center;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            margin-top: auto;
        }
    </style>
</head>

<body>
<main class="container mt-5">
    <h2 class="text-center text-primary fw-bold mb-4">Medical Records</h2>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            Patient Medical Record List
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th>ID</th>
                        <th>Visit Date</th>
                        <th>Diagnosis</th>
                        <th>Prescription</th>
                        <th>Appointment ID</th>
                        <th>Created</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stmt && $stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['MED_REC_ID']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['MED_REC_VISIT_DATE'])) ?></td>
                                <td><?= htmlspecialchars($row['MED_REC_DIAGNOSIS']) ?></td>
                                <td><?= htmlspecialchars($row['MED_REC_PRESCRIPTION']) ?></td>
                                <td><?= htmlspecialchars($row['APPT_ID'] ?? '-') ?></td>
                                <td><?= date('d/m/Y h:i A', strtotime($row['MED_REC_CREATED_AT'])) ?></td>
                                <td><?= $row['MED_REC_UPDATED_AT']
                                        ? date('d/m/Y h:i A', strtotime($row['MED_REC_UPDATED_AT']))
                                        : '-' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No medical records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>