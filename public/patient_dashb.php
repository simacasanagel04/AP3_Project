<?php 
// public/patient_dashb.php
include '../includes/patient_header.php'; 
?>

            <!-- WELCOME + DATE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Welcome, <?= htmlspecialchars($patientData['pat_first_name']) ?>!</h2>
                <div class="text-end">
                    <small class="text-muted">Today's Date and Time:</small><br>
                    <strong id="current-time"><?= date('Y-m-d H:i:s') ?></strong>
                </div>
            </div>

            <!-- TABS -->
            <div class="d-flex gap-2 mb-4">
                <button class="tab-btn" onclick="location.href='patient_book_appt.php'">Book an Appointment now</button>
                <button class="tab-btn active" onclick="location.href='patient_appointment_history.php'">Booked Appointments History</button>
            </div>

            <!-- GENERAL INFO -->
            <div class="info-card mb-4">
                <h4 class="text-center mb-3">GENERAL INFORMATION</h4>
                <div class="row text-center text-md-start g-3">
                    <div class="col-md-3 col-6"><strong>Patient ID</strong><br><?= $patientData['pat_id'] ?></div>
                    <div class="col-md-3 col-6"><strong>Name</strong><br><?= htmlspecialchars($patientData['pat_first_name'] . ' ' . $patientData['pat_last_name']) ?></div>
                    <div class="col-md-3 col-6"><strong>Date of Birth</strong><br><?= date('M d, Y', strtotime($patientData['pat_dob'])) ?></div>
                    <div class="col-md-3 col-6"><strong>Age</strong><br><?= floor((time() - strtotime($patientData['pat_dob'])) / 31556926) ?></div>
                    <div class="col-md-3 col-6"><strong>Gender</strong><br><?= ucfirst($patientData['pat_gender']) ?></div>
                    <div class="col-md-3 col-6"><strong>Contact Number</strong><br><?= $patientData['pat_contact_num'] ?></div>
                    <div class="col-md-3 col-6"><strong>Email</strong><br><?= $patientData['pat_email'] ?></div>
                    <div class="col-md-3 col-6"><strong>Address</strong><br><?= htmlspecialchars($patientData['pat_address']) ?></div>
                    <div class="col-md-3 col-6"><strong>Date Registered</strong><br><?= $patientData['formatted_created_at'] ?></div>
                </div>
            </div>

            <!-- UPCOMING APPOINTMENT -->
            <div class="info-card">
                <h4 class="mb-3">YOUR UPCOMING APPOINTMENT</h4>
                <?php 
                $appointments = $patient->getPatientAppointments($pat_id);
                $upcoming = array_filter($appointments, fn($a) => $a['app_status'] === 'Pending');
                ?>
                <?php if (!empty($upcoming)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Appointment ID</th>
                                    <th>Doctor</th>
                                    <th>Healthcare Service</th>
                                    <th>Scheduled Date</th>
                                    <th>Scheduled Time</th>
                                    <th>Billing and Payment</th>
                                    <th>Status</th>
                                    <th>Booking Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($upcoming, 0, 1) as $appt): ?>
                                <tr>
                                    <td><?= $appt['app_id'] ?></td>
                                    <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                                    <td><?= htmlspecialchars($appt['app_reason']) ?></td>
                                    <td><?= $appt['formatted_app_date'] ?></td>
                                    <td><?= $appt['formatted_app_time'] ?></td>
                                    <td>Debit card<br><small class="text-warning">Pending Payment</small></td>
                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    <td><?= $appt['formatted_app_date'] ?></td>
                                    <td>
                                        <a href="#" class="text-primary me-2">CANCEL</a>
                                        <a href="#" class="text-primary">EDIT</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No upcoming appointments.</p>
                <?php endif; ?>
            </div>

            <!-- STATS -->
            <div class="d-flex gap-3 mt-4 flex-wrap">
                <div class="p-3 bg-white rounded shadow flex-fill text-center">
                    <h3 class="text-primary"><?= count($appointments) ?></h3>
                    <p class="mb-0">Total Appointments</p>
                </div>
                <div class="p-3 bg-white rounded shadow flex-fill text-center">
                    <h3 class="text-success"><?= count($upcoming) ?></h3>
                    <p class="mb-0">Today's Appointment</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LIVE CLOCK -->
<script>
    setInterval(() => {
        const now = new Date();
        document.getElementById('current-time').textContent = 
            now.getFullYear() + '-' + 
            String(now.getMonth()+1).padStart(2,'0') + '-' + 
            String(now.getDate()).padStart(2,'0') + ' ' +
            String(now.getHours()).padStart(2,'0') + ':' +
            String(now.getMinutes()).padStart(2,'0') + ':' +
            String(now.getSeconds()).padStart(2,'0');
    }, 1000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>