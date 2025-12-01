<?php 
/**
 * ============================================================================
 * FILE: public/patient_book_appt.php
 * PURPOSE: Patient appointment booking and management interface
 * USER ROLE: Patient
 * ============================================================================
 */

// Include patient authentication and header
include '../includes/patient_header.php';

// Include required classes
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Specialization.php';
require_once __DIR__ . '/../classes/Payment_Method.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/Payment_Status.php';

// ============================================================================
// INITIALIZE CLASSES
// ============================================================================
$appointment = new Appointment($db);
$service = new Service($db);
$specialization = new Specialization($db);
$paymentMethod = new Payment_Method($db);
$payment = new Payment($db);
$paymentStatus = new Payment_Status($db);

// ============================================================================
// FETCH DATA FOR PAGE
// ============================================================================
$allSpecializations = $specialization->all();
$allPaymentMethods = $paymentMethod->all();
$patientAppointments = $appointment->getByPatientId($pat_id);

// ============================================================================
// GET APPOINTMENTS WITH PAYMENT INFORMATION
// ============================================================================
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
        
    } catch (PDOException $e) {
        error_log("Payment query error for appointment {$appt['app_id']}: " . $e->getMessage());
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