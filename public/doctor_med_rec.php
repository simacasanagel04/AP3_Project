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
    a.STAT_ID
FROM MEDICAL_RECORD mr
INNER JOIN APPOINTMENT a ON mr.APPT_ID = a.APPT_ID
INNER JOIN PATIENT p ON a.PAT_ID = p.PAT_ID
INNER JOIN SERVICE s ON a.SERV_ID = s.SERV_ID
WHERE a.DOC_ID = :doc_id
ORDER BY mr.MED_REC_VISIT_DATE DESC, mr.MED_REC_ID DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':doc_id', $doc_id);
$stmt->execute();
$medicalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><strong><i class="bi bi-hash"></i> Search Appointment ID</strong></label>
                        <input type="text" class="form-control" id="searchApptId" placeholder="Enter appointment ID">
                    </div>
                    <div class="col-md-3 col-sm-6">
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
                                <th>MedRec ID</th>
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
                                        data-medrec='<?= htmlspecialchars(json_encode($record), ENT_QUOTES, 'UTF-8') ?>'>
                                        <td><?= htmlspecialchars($record['MED_REC_ID']) ?></td>
                                        <td><?= htmlspecialchars($record['PAT_FIRST_NAME'] . ' ' . $record['PAT_LAST_NAME']) ?></td>
                                        <td><?= htmlspecialchars($record['MED_REC_DIAGNOSIS']) ?></td>
                                        <td><?= htmlspecialchars($record['MED_REC_PRESCRIPTION']) ?></td>
                                        <td><?= date('M d, Y', strtotime($record['MED_REC_VISIT_DATE'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm action-btn btn-view" data-medrec-id="<?= $record['MED_REC_ID'] ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm action-btn btn-edit" data-medrec-id="<?= $record['MED_REC_ID'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No medical records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Medical Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted"><strong>Appointment ID</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_appt_id">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted"><strong>Patient Name</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_patient_name">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted"><strong>Age</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_age">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted"><strong>Gender</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_gender">-</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted"><strong>Status</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_status">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted"><strong>Contact Number</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_contact">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted"><strong>Email</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_email">-</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted"><strong>Service</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_service">-</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted"><strong>Diagnosis</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_diagnosis">-</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted"><strong>Prescription</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_prescription">-</p>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted"><strong>Visit Date</strong></label>
                        <p class="form-control-plaintext border-bottom" id="view_visit_date">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Medical Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_med_rec_id" name="med_rec_id">
                    <input type="hidden" id="edit_appt_id_hidden" name="appt_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Appointment ID</strong></label>
                            <input type="text" class="form-control" id="edit_appt_id" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Patient Name</strong></label>
                            <input type="text" class="form-control" id="edit_patient_name" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Age</strong></label>
                            <input type="text" class="form-control" id="edit_age" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Gender</strong></label>
                            <input type="text" class="form-control" id="edit_gender" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><strong>Status</strong></label>
                            <input type="text" class="form-control" id="edit_status" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Contact Number</strong></label>
                            <input type="text" class="form-control" id="edit_contact" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><strong>Email</strong></label>
                            <input type="text" class="form-control" id="edit_email" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Service</strong></label>
                            <input type="text" class="form-control" id="edit_service" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Diagnosis <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="edit_diagnosis" name="MED_REC_DIAGNOSIS" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Prescription <span class="text-danger">*</span></strong></label>
                            <textarea class="form-control" id="edit_prescription" name="MED_REC_PRESCRIPTION" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label"><strong>Visit Date <span class="text-danger">*</span></strong></label>
                            <input type="date" class="form-control" id="edit_visit_date" name="visit_date" required>
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
<script src="../public/js/doctor_dashboard.js"></script>

</body>
</html>