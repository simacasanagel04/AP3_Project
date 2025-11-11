<?php include '../includes/patient_header.php'; ?>

<h3 class="mb-4"><i class="bi bi-people-fill me-2"></i>VIEW PATIENTS</h3>

<!-- FILTER CARD -->
<div class="info-card mb-4">
    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Patients</h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Search by Name</label>
            <input type="text" id="searchName" class="form-control" placeholder="Enter first name or last name">
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
            <button type="button" class="btn btn-primary flex-fill" id="applySearchBtn">
                <i class="bi bi-search me-1"></i> Search
            </button>
            <button type="button" class="btn btn-secondary flex-fill" id="clearSearchBtn">
                <i class="bi bi-x-circle me-1"></i> Clear
            </button>
        </div>
    </div>
    <div class="mt-3">
        <span class="badge bg-info">
            <i class="bi bi-info-circle me-1"></i>
            Showing <span id="filterTotal">0</span> patient(s)
        </span>
    </div>
</div>

<!-- TOTAL PATIENTS CARD -->
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3 id="totalPatients">0</h3>
                <p>Total Registered Patients</p>
            </div>
        </div>
    </div>
</div>

<!-- PATIENTS TABLE -->
<div class="info-card">
    <h4 class="mb-3">
        <i class="bi bi-table me-2 text-primary"></i>ALL PATIENTS
    </h4>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="patientsTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Middle Init</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all patients using Patient class
                require_once __DIR__ . '/../classes/Patient.php';
                $patientModel = new Patient($db);
                $allPatients = $patientModel->all();

                // Sort by last name alphabetically
                usort($allPatients, function($a, $b) {
                    return strcasecmp($a['pat_last_name'], $b['pat_last_name']);
                });

                if (empty($allPatients)):
                ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-2 text-muted">No patients registered yet</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allPatients as $index => $patient): ?>
                    <tr data-name="<?= strtolower(htmlspecialchars($patient['pat_first_name'] . ' ' . $patient['pat_last_name'])) ?>">
                        <td><?= $index + 1 ?></td>
                        <td><strong><?= htmlspecialchars($patient['pat_last_name']) ?></strong></td>
                        <td><?= htmlspecialchars($patient['pat_first_name']) ?></td>
                        <td><?= htmlspecialchars($patient['pat_middle_init'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div> <!-- END .main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/patient_dashboard.js"></script>
<script>
    // Initialize total counts
    document.addEventListener('DOMContentLoaded', function() {
        updateCounts();
    });

    function updateCounts() {
        const totalRows = document.querySelectorAll('#patientsTable tbody tr').length;
        const visibleRows = Array.from(document.querySelectorAll('#patientsTable tbody tr')).filter(row => row.style.display !== 'none').length;
        
        document.getElementById('totalPatients').textContent = totalRows;
        document.getElementById('filterTotal').textContent = visibleRows;
    }
</script>
</body>
</html>