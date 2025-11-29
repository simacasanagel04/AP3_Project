<?php 
// public/superadmin/modules/appointment-module.php
if (session_status() === PHP_SESSION_NONE) { 
  session_start(); 
} 
 
require_once dirname(__DIR__, 3) . '/classes/Appointment.php'; 
require_once dirname(__DIR__, 3) . '/classes/Patient.php'; 
require_once dirname(__DIR__, 3) . '/classes/Doctor.php'; 
require_once dirname(__DIR__, 3) . '/classes/Status.php'; 
require_once dirname(__DIR__, 3) . '/classes/Service.php'; 
 
if (!isset($db)) { 
  die('<div class="alert alert-danger">Database connection ($db) not available.</div>'); 
} 
 
$user_type = $_SESSION['user_type'] ?? null; 
 
if (!$user_type) { 
  echo '<div class="alert alert-danger">Access denied. Please log in.</div>'; 
  return; 
} 
 
$appointment = new Appointment($db); 
$patientObj = new Patient($db); 
$doctorObj = new Doctor($db); 
$statusObj = new Status($db); 
$serviceObj = new Service($db); 
 
$message = ''; 
 
// Normalize user type 
$user_type_lower = strtolower(str_replace('_', '', $user_type)); 
 
// Determine permissions 
$is_superadmin = in_array($user_type_lower, ['superadmin', 'super_admin']); 
$is_staff = ($user_type_lower === 'staff'); 
$is_patient = ($user_type_lower === 'patient'); 
 
// Access Control 
if (!$is_superadmin) { 
  echo '<div class="alert alert-danger">Access denied. Only administrators can manage appointments.</div>'; 
  return; 
} 
 
function formatDateTime($timestamp) { 
  return $timestamp ? date('F j, Y h:i A', strtotime($timestamp)) : '—'; 
} 
 
// Load dropdowns 
try { 
  $patients = $patientObj->all() ?? []; 
  $doctors = $doctorObj->all() ?? []; 
  $services = $serviceObj->readAll() ?? []; 
  $statuses = $statusObj->all() ?? []; 
} catch (Exception $e) { 
  $message = "Failed to load dropdown data: " . $e->getMessage(); 
  $patients = $doctors = $services = $statuses = []; 
} 
 
// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
  $appt_id = trim($_POST['APPT_ID'] ?? ''); 
  $data = [ 
    'APPT_DATE' => trim($_POST['APPT_DATE'] ?? ''), 
    'APPT_TIME' => trim($_POST['APPT_TIME'] ?? ''), 
    'pat_id'    => filter_input(INPUT_POST, 'PAT_ID', FILTER_VALIDATE_INT), 
    'doc_id'    => filter_input(INPUT_POST, 'DOC_ID', FILTER_VALIDATE_INT), 
    'serv_id'   => filter_input(INPUT_POST, 'SERV_ID', FILTER_VALIDATE_INT), 
    'stat_id'   => filter_input(INPUT_POST, 'STAT_ID', FILTER_VALIDATE_INT), 
    'APPT_ID'   => $appt_id 
  ]; 
 
  $is_valid = $data['APPT_DATE'] && $data['APPT_TIME'] && $data['pat_id'] && $data['doc_id'] && $data['serv_id'] && $data['stat_id']; 
 
  if (isset($_POST['add']) && $is_valid) { 
    $result = $appointment->create($data); 
    $message = is_array($result) && $result['success'] 
      ? "New appointment record added! ID: <strong class='text-primary'>{$result['appt_id']}</strong>." 
      : "Failed to schedule: " . ($result ?? 'Unknown error'); 
  } elseif (isset($_POST['update']) && $appt_id && $is_valid) { 
    $data['PAT_ID'] = $data['pat_id']; 
    $data['DOC_ID'] = $data['doc_id']; 
    $data['SERV_ID'] = $data['serv_id']; 
    $data['STAT_ID'] = $data['stat_id']; 
     
    $message = $appointment->update($data) 
      ? "Appointment ID {$appt_id} updated!" 
      : "Update failed."; 
  } elseif (isset($_POST['delete']) && $appt_id) { 
    $message = $appointment->delete($appt_id) 
      ? "Appointment ID {$appt_id} deleted." 
      : "Failed to delete."; 
  } 
} 
 
// Fetch appointments 
$search_term = trim($_GET['search_appt'] ?? ''); 
$search_param = ($search_term !== '') ? $search_term : null; 
 
if ($is_superadmin || $is_staff) { 
  $appointments = $appointment->readAll($search_param); 
} elseif ($is_patient && !empty($_SESSION['PAT_ID'])) { 
  $appointments = $appointment->readByPatient($_SESSION['PAT_ID'], $search_param); 
} else { 
  $appointments = []; 
} 
 
// Build URL params for search reset 
$current_params = $_GET; 
unset($current_params['search_appt']); 
$url_params = http_build_query($current_params); 
?> 
 
<h1 class="fw-bold mb-4">Appointment Management</h1> 
 
<?php if ($message): ?> 
<div class="alert <?= strpos($message, 'added') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show"> 
  <?= $message ?> 
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> 
</div> 
<?php endif; ?> 
 
<div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 shadow-sm border"> 
  <form class="d-flex w-50" method="GET"> 
    <?php foreach ($current_params as $k => $v): ?> 
      <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>"> 
    <?php endforeach; ?> 
    <label for="search_appt" class="visually-hidden">Search appointments</label>
    <input id="search_appt" class="form-control me-2 rounded-pill border-primary" type="search" name="search_appt" placeholder="Search by Appointment ID..." value="<?= htmlspecialchars($search_term) ?>" aria-label="Search by Appointment ID"> 
    <button class="btn btn-primary rounded-pill" type="submit">Search</button> 
    <?php if ($search_term): ?> 
      <a href="?<?= $url_params ?>" class="btn btn-outline-secondary ms-2 rounded-pill">Reset</a> 
    <?php endif; ?> 
  </form> 
  <?php if ($is_superadmin || $is_staff): ?> 
    <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addFormAppt" aria-expanded="false" aria-controls="addFormAppt">Schedule New Appointment</button> 
  <?php endif; ?> 
</div> 
 
<!-- ===================== ADD FORM ===================== --> 
<div id="addFormAppt" class="collapse mb-4"> 
  <div class="card card-body shadow-sm border rounded bg-light"> 
    <form method="POST" class="row g-3"> 
      <input type="hidden" name="add" value="1"> 
      <div class="col-md-3"> 
        <label for="appt_date_add" class="form-label fw-semibold">Date *</label> 
        <input id="appt_date_add" type="date" name="APPT_DATE" required class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"> 
      </div> 
      <div class="col-md-3"> 
        <label for="appt_time_add" class="form-label fw-semibold">Time *</label> 
        <input id="appt_time_add" type="time" name="APPT_TIME" required class="form-control"> 
      </div> 
      <div class="col-md-6"> 
        <label for="pat_id_add" class="form-label fw-semibold">Patient *</label> 
        <select id="pat_id_add" name="PAT_ID" required class="form-select"> 
          <option value="" disabled selected>Select Patient</option> 
          <?php foreach ($patients as $p): 
            $id = $p['pat_id'] ?? $p['PAT_ID']; 
            $name = ($p['pat_last_name'] ?? $p['PAT_LAST_NAME']) . ', ' . ($p['pat_first_name'] ?? $p['PAT_FIRST_NAME']); 
          ?> 
            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option> 
          <?php endforeach; ?> 
        </select> 
      </div> 
 
      <div class="col-md-6"> 
        <label for="appointment_service" class="form-label fw-semibold">Service *</label> 
        <select id="appointment_service" name="SERV_ID" required class="form-select"> 
          <option value="" disabled selected>Select Service</option> 
          <?php foreach ($services as $s): ?> 
            <?php $label = "{$s['SERV_NAME']} - ₱" . number_format($s['SERV_PRICE'] ?? 0, 2); ?> 
            <option value="<?= $s['SERV_ID'] ?>"><?= htmlspecialchars($label) ?></option> 
          <?php endforeach; ?> 
        </select> 
      </div> 
 
      <div class="col-md-6"> 
        <label for="appointment_doctor" class="form-label fw-semibold">Doctor *</label> 
        <select id="appointment_doctor" name="DOC_ID" required class="form-select" disabled> 
          <option>Select Service first</option> 
        </select> 
        <div id="doctor_loading_indicator" class="text-primary small mt-1" style="display:none;">Loading doctors...</div> 
      </div> 
 
      <div class="col-md-4"> 
        <label for="stat_id_add" class="form-label fw-semibold">Status *</label> 
        <select id="stat_id_add" name="STAT_ID" required class="form-select"> 
          <option value="" disabled selected>Select Status</option> 
          <?php foreach ($statuses as $st): 
            $id = $st['stat_id'] ?? $st['STAT_ID']; 
            $name = $st['status_name'] ?? $st['STAT_NAME']; 
          ?> 
            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option> 
          <?php endforeach; ?> 
        </select> 
      </div> 
      <div class="col-12 text-end"> 
        <button type="submit" name="add" class="btn btn-primary btn-lg">Save Appointment</button> 
      </div> 
    </form> 
  </div> 
</div> 
 
<!-- ===================== APPOINTMENT LIST ===================== --> 
<?php if ($is_superadmin || $is_staff): ?> 
<div class="card p-3 shadow-sm"> 
  <h5>Appointment List (Total: <?= count($appointments) ?>)</h5> 
  <?php if (empty($appointments)): ?> 
    <div class="alert alert-warning"> 
      No appointments found<?= $search_term ? ' matching "' . htmlspecialchars($search_term) . '"' : '' ?>. 
      <?php if ($search_term): ?> 
        <br><a href="?<?= $url_params ?>" class="btn btn-sm btn-outline-primary mt-2">Clear Search</a> 
      <?php endif; ?> 
    </div> 
  <?php else: ?> 
  <div class="table-responsive"> 
    <table class="table table-hover align-middle"> 
      <thead class="table-light"> 
        <tr> 
          <th scope="col">ID</th> 
          <th scope="col">Patient</th> 
          <th scope="col">Doctor</th> 
          <th scope="col">Service</th> 
          <th scope="col">Service Fee</th> 
          <th scope="col">Date & Time</th> 
          <th scope="col">Status</th> 
          <th scope="col">Created</th> 
          <th scope="col">Actions</th> 
        </tr> 
      </thead> 
      <tbody> 
        <?php foreach ($appointments as $a): ?> 
          <tr> 
            <form method="POST"> 
              <input type="hidden" name="APPT_ID" value="<?= htmlspecialchars($a['APPT_ID']) ?>"> 
              <td class="text-center fw-bold"><?= htmlspecialchars($a['APPT_ID']) ?></td> 
              <td> 
                <?= htmlspecialchars($a['patient_last_name'] . ', ' . $a['patient_first_name']) ?> 
                <label for="pat_id_<?= $a['APPT_ID'] ?>" class="visually-hidden">Patient</label>
                <select id="pat_id_<?= $a['APPT_ID'] ?>" name="PAT_ID" class="form-select form-select-sm mt-1"> 
                  <?php foreach ($patients as $p): 
                    $id = $p['pat_id'] ?? $p['PAT_ID']; 
                    $name = ($p['pat_last_name'] ?? $p['PAT_LAST_NAME']) . ', ' . ($p['pat_first_name'] ?? $p['PAT_FIRST_NAME']); 
                  ?> 
                    <option value="<?= $id ?>" <?= $a['PAT_ID'] == $id ? 'selected' : '' ?>> 
                      <?= htmlspecialchars($name) ?> 
                    </option> 
                  <?php endforeach; ?> 
                </select> 
              </td> 
              <td> 
                <?= htmlspecialchars('Dr. ' . $a['doctor_last_name'] . ', ' . $a['doctor_first_name']) ?> 
                <label for="doc_id_<?= $a['APPT_ID'] ?>" class="visually-hidden">Doctor</label>
                <select id="doc_id_<?= $a['APPT_ID'] ?>" name="DOC_ID" class="form-select form-select-sm mt-1"> 
                  <?php foreach ($doctors as $d): 
                    $id = $d['doc_id'] ?? $d['DOC_ID']; 
                    $name = ($d['doc_last_name'] ?? $d['DOC_LAST_NAME']) . ', ' . ($d['doc_first_name'] ?? $d['DOC_FIRST_NAME']); 
                  ?> 
                    <option value="<?= $id ?>" <?= $a['DOC_ID'] == $id ? 'selected' : '' ?>> 
                      Dr. <?= htmlspecialchars($name) ?> 
                    </option> 
                  <?php endforeach; ?> 
                </select> 
              </td> 
              <td> 
                <?= htmlspecialchars($a['service_name']) ?> 
                <label for="serv_id_<?= $a['APPT_ID'] ?>" class="visually-hidden">Service</label>
                <select id="serv_id_<?= $a['APPT_ID'] ?>" name="SERV_ID" class="form-select form-select-sm mt-1"> 
                  <?php foreach ($services as $s): ?> 
                    <?php $label = "{$s['SERV_NAME']} - ₱" . number_format($s['SERV_PRICE'] ?? 0, 2); ?> 
                    <option value="<?= $s['SERV_ID'] ?>" <?= $a['SERV_ID'] == $s['SERV_ID'] ? 'selected' : '' ?>> 
                      <?= htmlspecialchars($label) ?> 
                    </option> 
                  <?php endforeach; ?> 
                </select> 
              </td> 
              <td class="text-end"> 
                <?php 
                  $service_match = array_filter($services, fn($srv) => $srv['SERV_ID'] == $a['SERV_ID']); 
                  $price = $service_match ? number_format(array_values($service_match)[0]['SERV_PRICE'], 2) : '0.00'; 
                ?> 
                ₱<?= $price ?> 
              </td> 
              <td> 
                <label for="appt_date_<?= $a['APPT_ID'] ?>" class="visually-hidden">Date</label>
                <input id="appt_date_<?= $a['APPT_ID'] ?>" type="date" name="APPT_DATE" value="<?= htmlspecialchars($a['APPT_DATE']) ?>" class="form-control form-control-sm mb-1"> 
                <label for="appt_time_<?= $a['APPT_ID'] ?>" class="visually-hidden">Time</label>
                <input id="appt_time_<?= $a['APPT_ID'] ?>" type="time" name="APPT_TIME" value="<?= htmlspecialchars($a['APPT_TIME']) ?>" class="form-control form-control-sm"> 
              </td> 
              <td> 
                <?= htmlspecialchars($a['status_name']) ?> 
                <label for="stat_id_<?= $a['APPT_ID'] ?>" class="visually-hidden">Status</label>
                <select id="stat_id_<?= $a['APPT_ID'] ?>" name="STAT_ID" class="form-select form-select-sm"> 
                  <?php foreach ($statuses as $st): 
                    $id = $st['stat_id'] ?? $st['STAT_ID']; 
                    $name = $st['status_name'] ?? $st['STAT_NAME']; 
                  ?> 
                    <option value="<?= $id ?>" <?= $a['STAT_ID'] == $id ? 'selected' : '' ?>> 
                      <?= htmlspecialchars($name) ?> 
                    </option> 
                  <?php endforeach; ?> 
                </select> 
              </td> 
              <td class="text-nowrap small text-muted"><?= formatDateTime($a['APPT_CREATED_AT']) ?></td> 
              <td class="text-center"> 
                <button type="submit" name="update" class="btn btn-sm btn-success mb-1">Update</button> 
                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteApptModal" data-appt-id="<?= htmlspecialchars($a['APPT_ID']) ?>">Delete</button> 
              </td> 
            </form> 
          </tr> 
        <?php endforeach; ?> 
      </tbody> 
    </table> 
  </div> 
  <?php endif; ?> 
</div> 
<?php endif; ?> 
 
<!-- ===================== DELETE MODAL ===================== --> 
<div class="modal fade" id="deleteApptModal" tabindex="-1" aria-labelledby="deleteApptModalLabel" aria-hidden="true"> 
  <div class="modal-dialog"> 
    <div class="modal-content"> 
      <div class="modal-header bg-danger text-white"> 
        <h5 class="modal-title" id="deleteApptModalLabel">Confirm Deletion</h5> 
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
      </div> 
      <div class="modal-body"> 
        <p>Delete this appointment <strong id="modalApptIdDisplay"></strong>?</p> 
        <form method="POST" id="deleteApptForm"> 
          <input type="hidden" name="APPT_ID" id="deleteApptIdInput"> 
          <input type="hidden" name="delete" value="1"> 
        </form> 
      </div> 
      <div class="modal-footer"> 
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> 
        <button type="submit" form="deleteApptForm" class="btn btn-danger">Delete</button> 
      </div> 
    </div> 
  </div> 
</div> 
 
<script src="../../public/js/appointment.js"></script>