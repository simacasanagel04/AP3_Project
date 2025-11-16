<?php
// public/doctor_med_rec.php
require_once '../includes/doctor_header.php';
require_once '../config/Database.php';

$db = (new Database())->connect();

// Get doctor ID from session
$doc_id = $_SESSION['doc_id'] ?? null;

// Fetch all medical records for this doctor
$query = "SELECT 
    mr.MED_REC_ID,
    mr.MED_REC_DIAGNOSIS,
    mr.MED_REC_PRESCRIPTION,
    mr.MED_REC_VISIT_DATE,
    p.PAT_FIRST_NAME,
    p.PAT_LAST_NAME,
    p.PAT_DOB,
    p.PAT_GENDER,
    p.PAT_CONTACT_NUM,
    p.PAT_EMAIL,
    a.APPT_ID,
    s.SERV_ID,
    s.SERV_NAME,
    st.STAT_NAME as STATUS_NAME,
    a.STAT_ID
FROM medical_record mr
INNER JOIN appointment a ON mr.APPT_ID = a.APPT_ID
INNER JOIN patient p ON a.PAT_ID = p.PAT_ID
INNER JOIN service s ON a.SERV_ID = s.SERV_ID
INNER JOIN status st ON a.STAT_ID = st.STAT_ID
WHERE a.DOC_ID = :doc_id
ORDER BY mr.MED_REC_VISIT_DATE DESC, mr.MED_REC_ID DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':doc_id', $doc_id);
$stmt->execute();
$medicalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate patient age
foreach ($medicalRecords as &$record) {
    $dob = new DateTime($record['PAT_DOB']);
    $now = new DateTime();
    $record['PAT_AGE'] = $now->diff($dob)->y;
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="info-card">
                <h2 class="mb-0"><i class="bi bi-journal-medical"></i> Medical Records</h2>
            </div>
        </div>
    </div>

    <!-- Total Cards Row -->
    <div class="row mb-4" id="totalCardsRow">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="total-card">
                <h5 class="text-primary mb-2"><i class="bi bi-files"></i> Total Medical Records</h5>
                <h2 class="mb-0" id="totalRecordsCount"><?= count($medicalRecords) ?></h2>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3" id="filteredCardWrapper" style="display: none;">
            <div class="total-card filtered-card">
                <h5 class="text-success mb-2"><i class="bi bi-funnel"></i> Filtered Results</h5>
                <h2 class="mb-0" id="filteredRecordsCount">0</h2>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="total-card add-new-card cursor-pointer" id="addNewRecordBtn">
                <h5 class="text-success mb-2"><i class="bi bi-plus-circle"></i> Add New Record</h5>
                <h2 class="mb-0"><i class="bi bi-file-earmark-plus"></i></h2>
            </div>
        </div>
    </div>

    <!-- Add New Medical Record Form (Hidden by default) -->
    <div class="row mb-4" id="addRecordFormWrapper" style="display: none;">
        <div class="col-12">
            <div class="info-card">
                <h4><i class="bi bi-plus-square"></i> Create New Medical Record</h4>
                <form id="addMedicalRecordForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Appointment ID <span class="text-danger">*</span></strong></label>
                            <input type="text" class="form-control" id="new_appt_id" name="appt_id" placeholder="Enter Appointment ID" required>
                            <small class="text-muted">Press Enter or Tab to fetch patient details</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Patient Name</strong></label>
                            <input type="text" class="form-control" id="new_patient_name" readonly placeholder="Auto-filled">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Age</strong></label>
                            <input type="text" class="form-control" id="new_patient_age" readonly placeholder="Auto-filled">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Gender</strong></label>
                            <input type="text" class="form-control" id="new_patient_gender" readonly placeholder="Auto-filled">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Service</strong></label>
                            <input type="text" class="form-control" id="new_service_name" readonly placeholder="Auto-filled">
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Diagnosis <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="new_diagnosis" name="diagnosis" rows="3" required placeholder="Enter diagnosis details"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Prescription <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="new_prescription" name="prescription" rows="3" required placeholder="Enter prescription details"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Visit Date <span class="text-danger">*</span></strong></label>
                            <input type="date" class="form-control" id="new_visit_date" name="visit_date" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-check-circle"></i> Create Medical Record
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelAddRecordBtn">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="info-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-calendar3"></i> Filter by Date</strong></label>
                        <input type="date" class="form-control" id="filterByDate">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-person-search"></i> Search Patient Name</strong></label>
                        <input type="text" class="form-control" id="searchPatientName" placeholder="Enter patient name">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-hash"></i> Appointment ID</strong></label>
                        <input type="text" class="form-control" id="searchApptId" placeholder="Appt ID">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-file-medical"></i> Medical Record ID</strong></label>
                        <input type="text" class="form-control" id="searchMedRecId" placeholder="Med Rec ID">
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <button class="btn btn-primary me-2" id="filterBtn">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <button class="btn btn-secondary" id="clearFilterBtn">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <h4><i class="bi bi-table"></i> Medical Records List</h4>
                <div class="table-responsive">
                    <table class="table table-hover" id="medRecTable">
                        <thead>
                            <tr>
                                <th>Med Rec ID</th>
                                <th>Appt ID</th>
                                <th>Patient Name</th>
                                <th>Diagnosis</th>
                                <th>Prescription</th>
                                <th>Visit Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($medicalRecords) > 0): ?>
                                <?php foreach ($medicalRecords as $record): ?>
                                    <tr data-date="<?= htmlspecialchars($record['MED_REC_VISIT_DATE']) ?>" 
                                        data-patient="<?= htmlspecialchars(strtolower($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME'])) ?>"
                                        data-apptid="<?= htmlspecialchars($record['APPT_ID']) ?>"
                                        data-medrecid="<?= htmlspecialchars($record['MED_REC_ID']) ?>"
                                        data-medrec='<?= htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8') ?>'>
                                        <td><?= htmlspecialchars($record['MED_REC_ID']) ?></td>
                                        <td><?= htmlspecialchars($record['APPT_ID']) ?></td>
                                        <td><?= htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']) ?></td>
                                        <td><?= htmlspecialchars($record['MED_REC_DIAGNOSIS']) ?></td>
                                        <td><?= htmlspecialchars($record['MED_REC_PRESCRIPTION']) ?></td>
                                        <td><?= date('M d, Y', strtotime($record['MED_REC_VISIT_DATE'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm action-btn btn-update-medrec" data-medrec-id="<?= $record['MED_REC_ID'] ?>">
                                                <i class="bi bi-pencil-square"></i> UPDATE
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No medical records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Medical Record Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Update Medical Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateForm">
                <div class="modal-body">
                    <input type="hidden" id="update_med_rec_id" name="med_rec_id">
                    <input type="hidden" id="update_appt_id_hidden" name="appt_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Medical Record ID</strong></label>
                            <input type="text" class="form-control" id="update_med_rec_id_display" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Appointment ID</strong></label>
                            <input type="text" class="form-control" id="update_appt_id" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Patient Name</strong></label>
                            <input type="text" class="form-control" id="update_patient_name" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Service</strong></label>
                            <input type="text" class="form-control" id="update_service" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Diagnosis <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="update_diagnosis" name="MED_REC_DIAGNOSIS" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Prescription <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="update_prescription" name="MED_REC_PRESCRIPTION" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Visit Date <span class="text-danger">*</span></strong></label>
                            <input type="date" class="form-control" id="update_visit_date" name="visit_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="js/doctor_dashboard.js"></script>

</body>
</html>