<?php
// doctor_schedule.php

require_once '../includes/doctor_header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="info-card">
                <h2 class="mb-0">Schedule Management</h2>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary tab-btn active" data-target="todaySection">
                    <i class="bi bi-calendar-day"></i> View Today's Schedule
                </button>
                <button class="btn btn-outline-primary tab-btn" data-target="allSection">
                    <i class="bi bi-calendar3"></i> View All Schedule
                </button>
                <button class="btn btn-outline-success tab-btn" data-target="addSection">
                    <i class="bi bi-plus-circle"></i> Add New Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- TODAY'S SCHEDULE SECTION -->
    <div id="todaySection" class="table-section">
        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-calendar-check"></i> Today's Schedule</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="todayTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Booked Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2</td>
                                    <td>2 PM</td>
                                    <td>6 PM</td>
                                    <td>5</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal2">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>8 AM</td>
                                    <td>11 AM</td>
                                    <td>3</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal3">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="total-card">
                    <h5 class="text-primary mb-2">Today's Total Schedule</h5>
                    <h2 class="mb-0" id="todayScheduleCount">2</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW ALL SCHEDULE SECTION -->
    <div id="allSection" class="table-section">
        <div class="row mb-3">
            <div class="col-12">
                <div class="info-card">
                    <div class="row g-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label mb-1"><strong>Go to:</strong></label>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label mb-1">Date</label>
                            <input type="date" class="form-control" id="filterDate">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" id="searchBtn">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <button class="btn btn-secondary" id="clearBtn">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-list-ul"></i> All Schedules</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="allTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Booked Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-date="2025-10-31">
                                    <td>2</td>
                                    <td>Oct 31, 2025</td>
                                    <td>2 PM</td>
                                    <td>6 PM</td>
                                    <td>5</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal2">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                                <tr data-date="2025-10-31">
                                    <td>3</td>
                                    <td>Oct 31, 2025</td>
                                    <td>8 AM</td>
                                    <td>12 PM</td>
                                    <td>3</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal3">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="total-card">
                    <h5 class="text-primary mb-2">Total Schedule</h5>
                    <h2 class="mb-0" id="allScheduleCount">2</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD NEW SCHEDULE SECTION -->
    <div id="addSection" class="table-section">
        <div class="row mb-3">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-plus-square"></i> Add New Schedule</h4>
                    <form id="addForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label"><strong>Pick Date</strong></label>
                                <input type="date" class="form-control" id="newDate" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label"><strong>Start Time</strong></label>
                                <input type="time" class="form-control" id="newStartTime" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label"><strong>End Time</strong></label>
                                <input type="time" class="form-control" id="newEndTime" required>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-plus-circle"></i> Add Schedule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-card">
                    <h4><i class="bi bi-list-ul"></i> All Schedules</h4>
                    <div class="table-responsive">
                        <table class="table table-hover" id="newTable">
                            <thead>
                                <tr>
                                    <th>Schedule ID</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Total Booked Appointments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2</td>
                                    <td>Oct 31, 2025</td>
                                    <td>2 PM</td>
                                    <td>6 PM</td>
                                    <td>5</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal2">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Oct 31, 2025</td>
                                    <td>8 AM</td>
                                    <td>12 PM</td>
                                    <td>3</td>
                                    <td>
                                        <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal3">View</button>
                                        <button class="btn btn-sm action-btn">Edit</button>
                                        <button class="btn btn-sm action-btn btn-delete">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="total-card">
                    <h5 class="text-primary mb-2">Total Schedule</h5>
                    <h2 class="mb-0" id="newScheduleCount">2</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Modal (Sample) -->
<div class="modal fade" id="viewModal2" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointments for Schedule #2</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Service</th>
                            <th>Time From</th>
                            <th>Time To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>A001</td>
                            <td>John Doe</td>
                            <td>Consultation</td>
                            <td>2:00 PM</td>
                            <td>2:30 PM</td>
                            <td><span class="badge bg-success">Confirmed</span></td>
                        </tr>
                        <tr>
                            <td>A002</td>
                            <td>Jane Smith</td>
                            <td>Follow-up</td>
                            <td>3:00 PM</td>
                            <td>3:30 PM</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../public/js/doctor_dashboard.js"></script>

</body>
</html>