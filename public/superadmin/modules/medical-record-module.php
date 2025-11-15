<?php
// modules/medical-record-module.php
require_once dirname(__DIR__, 3) . '/classes/Medical_Record.php';

$medicalRecord = new MedicalRecord($db);
$message = '';

// --- ACCESS CONTROL ---
$user_role = $_SESSION['user_type'] ?? 'unknown';
$user_id   = $_SESSION['user_id'] ?? 0;

if (!in_array($user_role, ['super_admin', 'staff', 'doctor'])) {
    echo '<div class="alert alert-danger">Access denied. Only Super Admin, Staff, and Doctors can access this module.</div>';
    return;
}

// --- FETCH AVAILABLE APPOINTMENTS ---
$available_appointments = [];
try {
    // Fetch appointments with patient and doctor names
    $sql_appts = "SELECT 
                    a.APPT_ID,
                    CONCAT(p.PAT_LAST_NAME, ', ', p.PAT_FIRST_NAME) AS patient_name,
                    CONCAT('Dr. ', d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME) AS doctor_name,
                    DATE_FORMAT(a.APPT_DATE, '%Y-%m-%d') AS appt_date,
                    s.SERV_NAME AS service_name
                  FROM appointment a
                  JOIN patient p ON a.PAT_ID = p.PAT_ID
                  JOIN doctor d ON a.DOC_ID = d.DOC_ID
                  LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
                  ORDER BY a.APPT_DATE DESC, a.APPT_ID DESC
                  LIMIT 200";
    $stmt_appts = $db->query($sql_appts);
    $available_appointments = $stmt_appts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load appointments: " . $e->getMessage());
}

// --- SEARCH QUERY ---
$search_query = trim($_GET['q'] ?? '');
$action = $_GET['action'] ?? 'view_all';
$record_id = $_GET['id'] ?? null;
$current_title = "Medical Record Management";

// --- HANDLE POST (Create, Update, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'medrec_id'    => trim($_POST['medrec_id'] ?? ''),
        'appt_id'      => trim($_POST['appt_id'] ?? ''),
        'visit_date'   => trim($_POST['visit_date'] ?? date('Y-m-d')),
        'diagnosis'    => trim($_POST['diagnosis'] ?? ''),
        'prescription' => trim($_POST['prescription'] ?? ''),
    ];

    if (isset($_POST['create'])) {
        $result = $medicalRecord->createRecord($data);
        $message = $result === true
            ? "New Medical Record created successfully. ID: {$medicalRecord->getMedRecId()}"
            : "Failed to create Medical Record. " . (is_string($result) ? $result : '');
        $action = 'view_all';
    } elseif (isset($_POST['update'])) {
        $result = $medicalRecord->updateRecord($data);
        $message = $result === true
            ? "Medical Record ID {$data['medrec_id']} updated successfully."
            : "Failed to update Medical Record. " . (is_string($result) ? $result : '');
        $action = 'view_all';
    } elseif (isset($_POST['delete'])) {
        $medrec_id = (int)$_POST['delete'];
        $result = $medicalRecord->deleteRecord($medrec_id);
        $message = $result === true
            ? "Medical Record ID {$medrec_id} deleted successfully."
            : "Failed to delete Medical Record.";
        $action = 'view_all';
    }
}

// --- FETCH DATA ---
if ($action === 'view_all') {
    $data_list = $search_query
        ? $medicalRecord->search($search_query)
        : $medicalRecord->all();

    $data_list = is_array($data_list) ? $data_list : [];
    $current_title = "All Medical Records (" . count($data_list) . ")";

} elseif ($action === 'create') {
    $current_title = "Create New Medical Record";
    $record_data = $_POST ? $data : [
        'medrec_id' => '', 'appt_id' => '', 'visit_date' => date('Y-m-d'),
        'diagnosis' => '', 'prescription' => ''
    ];

} elseif ($action === 'edit' && $record_id) {
    $db_record = $medicalRecord->get($record_id);
    if ($db_record) {
        $current_title = "Edit Medical Record #{$record_id}";
        $record_data = [
            'medrec_id'    => $db_record['MED_REC_ID'],
            'appt_id'      => $db_record['APPT_ID'],
            'visit_date'   => $db_record['MED_REC_VISIT_DATE'],
            'diagnosis'    => $db_record['MED_REC_DIAGNOSIS'],
            'prescription' => $db_record['MED_REC_PRESCRIPTION'],
            'patient_name' => $db_record['PATIENT_NAME'] ?? 'N/A',
            'doctor_name'  => $db_record['DOCTOR_NAME'] ?? 'N/A',
        ];
    } else {
        $message = "Record ID {$record_id} not found.";
        $action = 'view_all';
    }
}

// Build URL params for search
$current_params = $_GET;
unset($current_params['q']);
$url_params = http_build_query($current_params);
?>

<h1 class="fw-bold mb-4">Medical Record Management</h1>

<?php if ($message): ?>
<div class="alert <?= strpos($message, 'Failed') !== false ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm border">
    <?php if ($action === 'view_all'): ?>
    <form method="GET" class="d-flex w-50">
        <input type="hidden" name="module" value="medical-record">
        <input type="hidden" name="action" value="view_all">
        <?php foreach ($current_params as $k => $v): ?>
            <?php if ($k !== 'module' && $k !== 'action'): ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="text" name="q" class="form-control me-2 rounded-pill border-primary" 
               placeholder="Search by Patient, Doctor, Diagnosis..." 
               value="<?= htmlspecialchars($search_query) ?>">
        <button class="btn btn-primary rounded-pill" type="submit">Search</button>
        <?php if ($search_query): ?>
            <a href="?module=medical-record&action=view_all" class="btn btn-outline-secondary ms-2 rounded-pill">Reset</a>
        <?php endif; ?>
    </form>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
    
    <div class="d-flex gap-2">
        <?php if (in_array($user_role, ['super_admin', 'staff', 'doctor'])): ?>
        <a href="?module=medical-record&action=create" class="btn btn-sm <?= $action === 'create' ? 'btn-success' : 'btn-outline-success' ?>">
            Create New
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card p-3 shadow-sm">
    <h5 class="mb-3"><?= $current_title ?></h5>

    <?php if ($action === 'view_all'): ?>
        <!-- TABLE -->
        <?php if (empty($data_list)): ?>
            <div class="alert alert-warning">
                No medical records found<?= $search_query ? ' matching "' . htmlspecialchars($search_query) . '"' : '' ?>.
                <?php if ($search_query): ?>
                    <br><a href="?module=medical-record&action=view_all" class="btn btn-sm btn-outline-primary mt-2">Clear Search</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Visit Date</th>
                        <th>Appt ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Diagnosis</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_list as $r): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= htmlspecialchars($r['MED_REC_ID']) ?></td>
                            <td><?= htmlspecialchars($r['MED_REC_VISIT_DATE']) ?></td>
                            <td><?= htmlspecialchars($r['APPT_ID']) ?></td>
                            <td><?= htmlspecialchars($r['PATIENT_NAME'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($r['DOCTOR_NAME'] ?? 'N/A') ?></td>
                            <td>
                                <?= htmlspecialchars(substr($r['MED_REC_DIAGNOSIS'], 0, 50)) ?>
                                <?= strlen($r['MED_REC_DIAGNOSIS']) > 50 ? '...' : '' ?>
                            </td>
                            <td class="text-center">
                                <a href="?module=medical-record&action=edit&id=<?= $r['MED_REC_ID'] ?>" 
                                   class="btn btn-sm btn-success">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteMedRecModal" 
                                        data-record-id="<?= $r['MED_REC_ID'] ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- CREATE / EDIT FORM -->
        <form method="POST" class="row g-3">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="medrec_id" value="<?= htmlspecialchars($record_data['medrec_id']) ?>">
                <div class="col-12">
                    <div class="alert alert-info">
                        <strong>Record ID:</strong> <?= $record_data['medrec_id'] ?><br>
                        <strong>Patient:</strong> <?= $record_data['patient_name'] ?><br>
                        <strong>Doctor:</strong> <?= $record_data['doctor_name'] ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-<?= $action === 'edit' ? '12' : '6' ?>">
                <label class="form-label fw-semibold">Appointment *</label>
                <?php if ($action === 'edit'): ?>
                    <input type="text" name="appt_id" class="form-control" 
                           value="<?= htmlspecialchars($record_data['appt_id'] ?? '') ?>" readonly>
                    <div class="form-text">Cannot change Appointment ID after creation.</div>
                <?php else: ?>
                    <select name="appt_id" class="form-select" required>
                        <option value="">-- Select Appointment --</option>
                        <?php foreach ($available_appointments as $appt): ?>
                            <option value="<?= htmlspecialchars($appt['APPT_ID']) ?>"
                                    <?= (isset($record_data['appt_id']) && $record_data['appt_id'] == $appt['APPT_ID']) ? 'selected' : '' ?>>
                                #<?= htmlspecialchars($appt['APPT_ID']) ?> - 
                                <?= htmlspecialchars($appt['patient_name']) ?> 
                                (<?= htmlspecialchars($appt['doctor_name']) ?> - 
                                <?= htmlspecialchars($appt['appt_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Select the appointment for this medical record.</div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Visit Date *</label>
                <input type="date" name="visit_date" class="form-control" 
                       value="<?= htmlspecialchars($record_data['visit_date'] ?? date('Y-m-d')) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Diagnosis *</label>
                <textarea name="diagnosis" class="form-control" rows="4" required 
                          placeholder="Enter patient diagnosis..."><?= htmlspecialchars($record_data['diagnosis'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Prescription / Treatment *</label>
                <textarea name="prescription" class="form-control" rows="6" required 
                          placeholder="Enter prescription and treatment plan..."><?= htmlspecialchars($record_data['prescription'] ?? '') ?></textarea>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" name="<?= $action === 'create' ? 'create' : 'update' ?>" 
                        class="btn btn-lg <?= $action === 'create' ? 'btn-success' : 'btn-primary' ?>">
                    <?= $action === 'create' ? 'Create Record' : 'Update Record' ?>
                </button>
                <a href="?module=medical-record&action=view_all" class="btn btn-secondary btn-lg ms-2">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMedRecModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete Medical Record ID: <strong id="modalRecordIdDisplay"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
                <form method="POST" id="deleteMedRecForm">
                    <input type="hidden" name="delete" id="deleteRecordIdInput">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteMedRecForm" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete modal
    document.querySelectorAll('[data-bs-target="#deleteMedRecModal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-record-id');
            document.getElementById('modalRecordIdDisplay').textContent = id;
            document.getElementById('deleteRecordIdInput').value = id;
        });
    });
});
</script>