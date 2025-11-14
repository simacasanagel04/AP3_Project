<?php 

// public/patient_book_appt.php
// for user patient

include '../includes/patient_header.php';

// Include required classes
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Specialization.php';
require_once __DIR__ . '/../classes/Payment_Method.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/Payment_Status.php';

// Initialize classes
$appointment = new Appointment($db);
$service = new Service($db);
$specialization = new Specialization($db);
$paymentMethod = new Payment_Method($db);
$payment = new Payment($db);
$paymentStatus = new Payment_Status($db);

// Fetch all data
$allSpecializations = $specialization->all();
$allPaymentMethods = $paymentMethod->all();
$patientAppointments = $appointment->getByPatientId($pat_id);

// Get appointments with payment info
$appointmentsWithPayment = [];
foreach ($patientAppointments as $appt) {
    $paymentInfo = $db->query("
        SELECT 
            p.PAYMT_AMOUNT_PAID,
            pm.pymt_meth_name,
            ps.PYMT_STAT_NAME
        FROM payment p
        LEFT JOIN payment_method pm ON p.pymt_meth_id = pm.pymt_meth_id
        LEFT JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
        WHERE p.APPT_ID = " . $appt['app_id']
    )->fetch(PDO::FETCH_ASSOC);
    
    $appt['payment_info'] = $paymentInfo;
    $appointmentsWithPayment[] = $appt;
}

// Count appointments
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

<!-- BOOK FORM -->
<div id="bookSection" class="info-card" style="display: none;">
    <p class="mb-3"><strong>Note:</strong> Each service has a duration (default duration is 30 mins)</p>

    <form id="apptForm">
        <input type="hidden" id="patientId" value="<?= $pat_id ?>">
        <input type="hidden" id="selectedDoctorId" value="">

        <div class="mb-3">
            <label class="form-label"><strong>SELECT DEPARTMENT</strong></label>
            <select class="form-select" id="department" required>
                <option value="">-- Select Department --</option>
                <?php foreach ($allSpecializations as $spec): ?>
                    <option value="<?= $spec['SPEC_ID'] ?>"><?= htmlspecialchars($spec['SPEC_NAME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>SELECT SERVICE</strong></label>
            <select class="form-select" id="service" required disabled>
                <option value="">-- Select Department First --</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>SERVICE PRICE</strong></label>
            <div class="form-control bg-light" id="servicePrice" style="font-weight:600; color:var(--blue);">₱0.00</div>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>SELECT DATE</strong></label>
            <input type="date" class="form-control" id="date" required disabled>
            <small class="text-muted" id="dateNote">Select a department first to see available dates</small>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>SELECT TIME</strong></label>
            <select class="form-select" id="time" required disabled>
                <option value="">-- Select Date First --</option>
            </select>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" id="clearBtn">CLEAR</button>
            <button type="submit" class="btn btn-primary">CONTINUE</button>
        </div>
    </form>
</div>

<!-- HISTORY TABLE -->
<div id="historySection" class="info-card">
    <h4 class="mb-3">BOOKED APPOINTMENTS HISTORY</h4>
    
    <!-- FILTER CARD -->
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">Filter Appointments</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Appointment ID</label>
                    <input type="text" class="form-control" id="filterApptId" placeholder="Enter appointment ID">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Scheduled Date</label>
                    <input type="date" class="form-control" id="filterDate">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus">
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

    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="appointmentsTable">
            <thead>
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
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-calendar-x" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="mt-2">No appointments found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointmentsWithPayment as $appt): 
                        $statusText = $appt['app_status'] == 1 ? 'Scheduled' : 
                                     ($appt['app_status'] == 2 ? 'Completed' : 'Cancelled');
                        $statusClass = $appt['app_status'] == 1 ? 'bg-warning' : 
                                      ($appt['app_status'] == 2 ? 'bg-success' : 'bg-danger');
                        
                        $payInfo = $appt['payment_info'];
                        $payAmount = $payInfo ? '₱' . number_format($payInfo['PAYMT_AMOUNT_PAID'], 2) : 'N/A';
                        $payMethod = $payInfo ? $payInfo['pymt_meth_name'] : 'N/A';
                        $payStatus = $payInfo ? $payInfo['PYMT_STAT_NAME'] : 'N/A';
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
                            <small class="<?= $payStatusClass ?>"><?= $payStatus ?></small>
                        </td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($appt['app_date']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PAYMENT SECTION -->
<div id="paymentSection" class="payment-section" style="display: none;">
    <div class="payment-header">
        Please select a payment method to complete your booking.<br>
    </div>

    <div class="service-summary">
        <span id="summaryService">Service Name</span>
        <strong id="summaryPrice">₱0.00</strong>
    </div>

    <div class="mb-3">
        <label class="form-label"><strong>PAYMENT METHOD</strong></label>
        <select class="form-select" id="paymentMethodSelect" required>
            <option value="">-- Select Payment Method --</option>
            <?php foreach ($allPaymentMethods as $method): ?>
                <option value="<?= $method['pymt_meth_id'] ?>"><?= htmlspecialchars($method['pymt_meth_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="paymentCardContainer"></div>

    <button type="button" class="btn btn-book" id="bookBtn" disabled>BOOK APPOINTMENT</button>
</div>

</div> <!-- END .main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/patient_dashboard.js"></script>
</body>
</html>