<?php 
/**
 * ============================================================================
 * FILE: public/patient_book_appt.php
 * PURPOSE: Patient appointment booking and management interface
 * USER ROLE: Patient
 * 
 * 🔍 DEBUG MODE ENABLED - Shows detailed error tracking
 * ============================================================================
 */

// ============================================
// DEBUG PANEL INITIALIZATION
// ============================================
$debugLog = [];
function addDebug($message, $data = null, $type = 'info') {
    global $debugLog;
    $debugLog[] = [
        'time' => date('H:i:s.u'),
        'type' => $type,
        'message' => $message,
        'data' => $data
    ];
}

addDebug("=== PAGE LOAD STARTED ===", null, 'header');

// Include patient authentication and header
try {
    include '../includes/patient_header.php';
    addDebug("✅ Patient header included", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to include patient_header.php", $e->getMessage(), 'error');
}

// Include required classes
$requiredClasses = [
    'Appointment' => __DIR__ . '/../classes/Appointment.php',
    'Service' => __DIR__ . '/../classes/Service.php',
    'Specialization' => __DIR__ . '/../classes/Specialization.php',
    'Payment_Method' => __DIR__ . '/../classes/Payment_Method.php',
    'Payment' => __DIR__ . '/../classes/Payment.php',
    'Payment_Status' => __DIR__ . '/../classes/Payment_Status.php'
];

foreach ($requiredClasses as $className => $filePath) {
    try {
        require_once $filePath;
        addDebug("✅ Loaded class: $className", $filePath, 'success');
    } catch (Exception $e) {
        addDebug("❌ Failed to load class: $className", [
            'file' => $filePath,
            'error' => $e->getMessage()
        ], 'error');
    }
}

// ============================================================================
// DATABASE CONNECTION CHECK
// ============================================================================
addDebug("Checking database connection...");
if (!isset($db)) {
    addDebug("❌ Database connection NOT available", null, 'error');
} else {
    addDebug("✅ Database connection available", get_class($db), 'success');
    
    // Test database connection
    try {
        $testQuery = $db->query("SELECT 1 as test");
        $testResult = $testQuery->fetch();
        addDebug("✅ Database test query SUCCESS", $testResult, 'success');
    } catch (Exception $e) {
        addDebug("❌ Database test query FAILED", $e->getMessage(), 'error');
    }
    
    // Check database collation
    try {
        $collationQuery = $db->query("SELECT @@collation_database as db_collation, @@character_set_database as db_charset");
        $collationResult = $collationQuery->fetch();
        addDebug("📊 Database collation info", $collationResult, 'info');
    } catch (Exception $e) {
        addDebug("❌ Failed to get database collation", $e->getMessage(), 'error');
    }
    
    // Check appointment table collation
    try {
        $tableCollation = $db->query("SHOW TABLE STATUS WHERE Name = 'appointment'");
        $tableInfo = $tableCollation->fetch();
        addDebug("📊 Appointment table collation", [
            'Collation' => $tableInfo['Collation'] ?? 'N/A',
            'Engine' => $tableInfo['Engine'] ?? 'N/A'
        ], 'info');
    } catch (Exception $e) {
        addDebug("❌ Failed to get table collation", $e->getMessage(), 'error');
    }
    
    // Check APPT_ID column collation
    try {
        $columnCollation = $db->query("SHOW FULL COLUMNS FROM appointment WHERE Field = 'APPT_ID'");
        $columnInfo = $columnCollation->fetch();
        addDebug("📊 APPT_ID column info", [
            'Collation' => $columnInfo['Collation'] ?? 'N/A',
            'Type' => $columnInfo['Type'] ?? 'N/A'
        ], 'info');
    } catch (Exception $e) {
        addDebug("❌ Failed to get column collation", $e->getMessage(), 'error');
    }
}

// ============================================================================
// SESSION CHECK
// ============================================================================
addDebug("Checking session data...");
addDebug("Session user_type", $_SESSION['user_type'] ?? 'NOT SET', 'info');
addDebug("Session pat_id", $_SESSION['pat_id'] ?? 'NOT SET', 'info');
addDebug("Session user_id", $_SESSION['user_id'] ?? 'NOT SET', 'info');

// ============================================================================
// INITIALIZE CLASSES
// ============================================================================
try {
    $appointment = new Appointment($db);
    addDebug("✅ Appointment class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Appointment class", $e->getMessage(), 'error');
}

try {
    $service = new Service($db);
    addDebug("✅ Service class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Service class", $e->getMessage(), 'error');
}

try {
    $specialization = new Specialization($db);
    addDebug("✅ Specialization class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Specialization class", $e->getMessage(), 'error');
}

try {
    $paymentMethod = new Payment_Method($db);
    addDebug("✅ Payment_Method class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Payment_Method class", $e->getMessage(), 'error');
}

try {
    $payment = new Payment($db);
    addDebug("✅ Payment class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Payment class", $e->getMessage(), 'error');
}

try {
    $paymentStatus = new Payment_Status($db);
    addDebug("✅ Payment_Status class initialized", null, 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to initialize Payment_Status class", $e->getMessage(), 'error');
}

// ============================================================================
// FETCH DATA FOR PAGE
// ============================================================================
addDebug("Fetching specializations...");
try {
    $allSpecializations = $specialization->all();
    addDebug("✅ Loaded specializations", "Count: " . count($allSpecializations), 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to load specializations", $e->getMessage(), 'error');
    $allSpecializations = [];
}

addDebug("Fetching payment methods...");
try {
    $allPaymentMethods = $paymentMethod->all();
    addDebug("✅ Loaded payment methods", "Count: " . count($allPaymentMethods), 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to load payment methods", $e->getMessage(), 'error');
    $allPaymentMethods = [];
}

addDebug("Fetching patient appointments...");
try {
    $patientAppointments = $appointment->getByPatientId($pat_id);
    addDebug("✅ Loaded patient appointments", "Count: " . count($patientAppointments), 'success');
} catch (Exception $e) {
    addDebug("❌ Failed to load patient appointments", $e->getMessage(), 'error');
    $patientAppointments = [];
}

// ============================================================================
// GET APPOINTMENTS WITH PAYMENT INFORMATION
// ============================================================================
addDebug("Fetching payment information for appointments...");
$appointmentsWithPayment = [];
foreach ($patientAppointments as $appt) {
    try {
        // Query to get payment details for each appointment
        $paymentQuery = "
            SELECT 
                p.PAYMT_AMOUNT_PAID,
                pm.PYMT_METH_NAME,
                ps.PYMT_STAT_NAME
            FROM payment p
            LEFT JOIN payment_method pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
            LEFT JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
            WHERE p.APPT_ID = :appt_id
            LIMIT 1
        ";
        
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->bindParam(':appt_id', $appt['app_id'], PDO::PARAM_STR);
        $paymentStmt->execute();
        
        $paymentInfo = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        $appt['payment_info'] = $paymentInfo;
        
        addDebug("✅ Payment info for appointment {$appt['app_id']}", $paymentInfo ? 'Found' : 'Not found', 'info');
        
    } catch (PDOException $e) {
        addDebug("❌ Payment query error for appointment {$appt['app_id']}", $e->getMessage(), 'error');
        $appt['payment_info'] = null;
    }
    
    $appointmentsWithPayment[] = $appt;
}

// ============================================================================
// CALCULATE APPOINTMENT STATISTICS
// ============================================================================
$todayCount = 0;
$totalCount = count($appointmentsWithPayment);
$today = date('Y-m-d');

foreach ($appointmentsWithPayment as $appt) {
    if ($appt['app_date'] == $today) {
        $todayCount++;
    }
}

addDebug("📊 Appointment statistics", [
    'today' => $todayCount,
    'total' => $totalCount
], 'info');

addDebug("=== PAGE LOAD COMPLETED ===", null, 'header');

// ============================================================================
// RENDER DEBUG PANEL
// ============================================================================
function renderDebugPanel() {
    global $debugLog;
    
    $typeColors = [
        'header' => '#9333ea',
        'success' => '#16a34a',
        'error' => '#dc2626',
        'warning' => '#ea580c',
        'info' => '#2563eb'
    ];
    
    $typeIcons = [
        'header' => '🔷',
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    ?>
    <div style="position: fixed; top: 10px; right: 10px; width: 450px; max-height: 90vh; overflow-y: auto; z-index: 9999; background: white; border: 3px solid #dc2626; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #dc2626, #991b1b); color: white; padding: 15px; border-radius: 9px 9px 0 0; position: sticky; top: 0; z-index: 10;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 700;">🔍 DEBUG PANEL</h3>
                <button onclick="this.closest('div').parentElement.style.display='none'" 
                        style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                    ✕ Close
                </button>
            </div>
            <div style="font-size: 11px; margin-top: 5px; opacity: 0.9;">
                Total Logs: <?= count($debugLog) ?>
            </div>
        </div>
        
        <!-- Content -->
        <div style="padding: 15px; font-family: 'Courier New', monospace; font-size: 12px; background: #f9fafb;">
            <?php foreach ($debugLog as $entry): 
                $color = $typeColors[$entry['type']] ?? '#6b7280';
                $icon = $typeIcons[$entry['type']] ?? '•';
            ?>
                <div style="margin-bottom: 12px; padding: 10px; background: white; border-left: 4px solid <?= $color ?>; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <!-- Time & Type -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="color: <?= $color ?>; font-weight: 700; font-size: 13px;">
                            <?= $icon ?> <?= strtoupper($entry['type']) ?>
                        </span>
                        <span style="color: #6b7280; font-size: 10px;"><?= $entry['time'] ?></span>
                    </div>
                    
                    <!-- Message -->
                    <div style="color: #1f2937; font-weight: 600; margin-bottom: 6px;">
                        <?= htmlspecialchars($entry['message']) ?>
                    </div>
                    
                    <!-- Data -->
                    <?php if ($entry['data'] !== null): ?>
                        <div style="background: #f3f4f6; padding: 8px; border-radius: 4px; margin-top: 6px; overflow-x: auto; max-height: 200px;">
                            <pre style="margin: 0; font-size: 11px; color: #374151; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars(is_array($entry['data']) || is_object($entry['data']) ? json_encode($entry['data'], JSON_PRETTY_PRINT) : $entry['data']) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Footer -->
        <div style="background: #f3f4f6; padding: 10px 15px; border-radius: 0 0 9px 9px; text-align: center; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb;">
            <strong>⚡ Real-time Debug Monitor</strong> • Scroll for full log
        </div>
    </div>
    <?php
}

// Render debug panel at the top of the page
renderDebugPanel();
?>

<!-- DASHBOARD HEADER -->
<div class="dashboard-header">
    <h1>APPOINTMENTS</h1>
    <h3>Welcome, <?= htmlspecialchars($patientData['pat_first_name']) ?>!</h3>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $todayCount ?></h3>
                <p>Today's Appointments</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalCount ?></h3>
                <p>Total Appointments</p>
            </div>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="d-flex gap-2 mb-4">
    <button class="tab-btn" id="bookTab">Book an Appointment Now</button>
    <button class="tab-btn active" id="historyTab">Booked Appointments History</button>
</div>

<!-- ============================================================================
     BOOK APPOINTMENT FORM
     ============================================================================ -->
<div id="bookSection" class="info-card" style="display: none;">
    <p class="mb-3"><strong>Note:</strong> Each service has a duration (default duration is 30 mins)</p>

    <form id="apptForm">
        <!-- Hidden fields -->
        <input type="hidden" id="patientId" value="<?= $pat_id ?>">
        <input type="hidden" id="selectedDoctorId" value="">

        <!-- Department Selection -->
        <div class="mb-3">
            <label class="form-label"><strong>SELECT DEPARTMENT</strong></label>
            <select class="form-select" id="department" required title="Select a department">
                <option value="">-- Select Department --</option>
                <?php foreach ($allSpecializations as $spec): ?>
                    <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Service Selection -->
        <div class="mb-3">
            <label class="form-label"><strong>SELECT SERVICE</strong></label>
            <select class="form-select" id="service" required disabled title="Select a service">
                <option value="">-- Select Department First --</option>
            </select>
        </div>

        <!-- Service Price Display -->
        <div class="mb-3">
            <label class="form-label"><strong>SERVICE PRICE</strong></label>
            <div class="form-control bg-light fw-semibold text-primary" id="servicePrice">₱0.00</div>
        </div>

        <!-- Date Selection -->
        <div class="mb-3">
            <label class="form-label"><strong>SELECT DATE</strong></label>
            <input type="date" class="form-control" id="date" required disabled title="Select appointment date" placeholder="YYYY-MM-DD">
            <small class="text-muted" id="dateNote">Select a department first to see available dates</small>
        </div>

        <!-- Time Selection -->
        <div class="mb-3">
            <label class="form-label"><strong>SELECT TIME</strong></label>
            <select class="form-select" id="time" required disabled title="Select appointment time">
                <option value="">-- Select Date First --</option>
            </select>
        </div>

        <!-- Form Actions -->
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" id="clearBtn">CLEAR</button>
            <button type="submit" class="btn btn-primary">CONTINUE</button>
        </div>
    </form>
</div>

<!-- ============================================================================
     APPOINTMENTS HISTORY TABLE
     ============================================================================ -->
<div id="historySection" class="info-card">
    <h4 class="mb-3">BOOKED APPOINTMENTS HISTORY</h4>
    
    <!-- FILTER CARD -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3 fw-semibold">Filter Appointments</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Appointment ID</label>
                    <input type="text" class="form-control" id="filterApptId" placeholder="Enter appointment ID" title="Filter by appointment ID">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Scheduled Date</label>
                    <input type="date" class="form-control" id="filterDate" title="Filter by date" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus" title="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="1">Scheduled</option>
                        <option value="2">Completed</option>
                        <option value="3">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="btn btn-secondary btn-sm" id="clearFilterBtn">CLEAR</button>
                <button type="button" class="btn btn-primary btn-sm" id="applyFilterBtn">APPLY FILTER</button>
            </div>
            <small class="text-muted d-block mt-2">Total Results: <span id="totalResults"><?= count($appointmentsWithPayment) ?></span></small>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="appointmentsTable">
            <thead class="table-light">
                <tr>
                    <th>Appointment ID</th>
                    <th>Doctor</th>
                    <th>Healthcare Service</th>
                    <th>Scheduled Date</th>
                    <th>Scheduled Time</th>
                    <th>Status</th>
                    <th>Payment<br><small>(Amount / Method / Status)</small></th>
                    <th>Booking Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointmentsWithPayment)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="bi bi-calendar-x opacity-25" style="font-size: 3rem;"></i>
                            <p class="mt-2 text-muted">No appointments found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointmentsWithPayment as $appt): 
                        // Determine status display
                        $statusText = $appt['app_status'] == 1 ? 'Scheduled' : 
                                     ($appt['app_status'] == 2 ? 'Completed' : 'Cancelled');
                        $statusClass = $appt['app_status'] == 1 ? 'bg-warning' : 
                                      ($appt['app_status'] == 2 ? 'bg-success' : 'bg-danger');
                        
                        // Format payment information
                        $payInfo = $appt['payment_info'];
                        $payAmount = $payInfo && $payInfo['PAYMT_AMOUNT_PAID'] ? '₱' . number_format($payInfo['PAYMT_AMOUNT_PAID'], 2) : 'N/A';
                        $payMethod = $payInfo && $payInfo['PYMT_METH_NAME'] ? $payInfo['PYMT_METH_NAME'] : 'N/A';
                        $payStatus = $payInfo && $payInfo['PYMT_STAT_NAME'] ? $payInfo['PYMT_STAT_NAME'] : 'N/A';
                        $payStatusClass = $payInfo && $payInfo['PYMT_STAT_NAME'] == 'Paid' ? 'text-success' : 
                                         ($payInfo && $payInfo['PYMT_STAT_NAME'] == 'Pending' ? 'text-warning' : 'text-danger');
                    ?>
                    <tr data-appt-id="<?= $appt['app_id'] ?>" data-date="<?= $appt['app_date'] ?>" data-status="<?= $appt['app_status'] ?>">
                        <td><?= htmlspecialchars($appt['app_id']) ?></td>
                        <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_date']) ?></td>
                        <td><?= htmlspecialchars($appt['formatted_app_time']) ?> <small class="text-muted">(30 min)</small></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                        <td>
                            <strong><?= $payAmount ?></strong><br>
                            <small class="text-muted"><?= $payMethod ?></small><br>
                            <small class="fw-semibold <?= $payStatusClass ?>"><?= $payStatus ?></small>
                        </td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($appt['app_date']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================================
     PAYMENT SECTION
     ============================================================================ -->
<div id="paymentSection" class="payment-section border border-2 border-primary rounded-3 p-4 bg-light bg-gradient" style="display: none;">
    <div class="bg-primary bg-gradient text-white p-3 rounded-3 text-center mb-4">
        <h5 class="mb-0 fw-semibold">Complete Your Payment</h5>
    </div>

    <!-- Payment Summary -->
    <div class="bg-white border border-2 p-3 rounded-3 d-flex justify-content-between align-items-center fw-semibold mb-4 shadow-sm">
        <span id="summaryService" class="fs-6 text-dark">Service Name</span>
        <strong id="summaryPrice" class="fs-6 text-primary">₱0.00</strong>
    </div>

    <!-- Payment Method Selection -->
    <div class="mb-4">
        <label class="form-label fw-bold text-uppercase">Select Payment Method</label>
        <select class="fs-6 form-select border-2" id="paymentMethodSelect" required title="Select payment method">
            <option value="">-- Choose Payment Method --</option>
            <?php foreach ($allPaymentMethods as $method): ?>
                <option value="<?= $method['pymt_meth_id'] ?>"><?= htmlspecialchars($method['pymt_meth_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Payment Actions -->
    <div class="d-flex gap-3 justify-content-center">
        <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="cancelPaymentBtn">CANCEL</button>
        <button type="button" class="btn btn-primary px-4 fw-semibold text-uppercase" id="proceedPaymentBtn" disabled>PROCEED TO PAYMENT</button>
    </div>
</div>

<!-- ============================================================================
     PAYMENT MODAL
     ============================================================================ -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <!-- Modal Header -->
            <div class="modal-header bg-primary bg-gradient text-white p-4 border-0">
                <div>
                    <h5 class="modal-title fw-semibold mb-1" id="paymentModalTitle">Payment Method</h5>
                    <small class="opacity-75">Complete your appointment booking</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Amount Display -->
                <div class="text-center mb-4">
                    <p class="text-muted mb-1">Amount to Pay:</p>
                    <h3 class="fw-bold" id="paymentAmount">₱0.00</h3>
                </div>

                <hr class="my-4">

                <!-- Payment Form Container (dynamically populated) -->
                <div id="paymentFormContainer"></div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light p-4 border-top">
                <button type="button" class="btn btn-outline-secondary px-5" data-bs-dismiss="modal">CANCEL</button>
                <button type="button" class="btn btn-success px-5 fw-semibold" id="confirmPaymentBtn">CONFIRM & BOOK</button>
            </div>
        </div>
    </div>
</div>

</div> <!-- END .main-content -->

<!-- ============================================================================
     SCRIPTS
     ============================================================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/patient_dashboard.js"></script>

</body>
</html>