// public/js/doctor_dashboard.js

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    // ===============================
    // NAVIGATION - Set active nav link based on current page
    // ===============================
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // ===============================
    // SIDEBAR TOGGLE
    // ===============================
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('active');
        });
    }

    // ===============================
    // RESPONSIVE BEHAVIOR
    // ===============================
    function checkScreen() {
        if (window.innerWidth <= 992) {
            sidebar.classList.add('hidden');
            if (toggleBtn) toggleBtn.style.display = 'flex';
        } else {
            sidebar.classList.remove('hidden');
            sidebar.classList.remove('active');
            if (toggleBtn) toggleBtn.style.display = 'none';
        }
    }
    checkScreen();
    window.addEventListener('resize', checkScreen);

    // ===============================
    // LIVE CLOCK
    // ===============================
    function updateClock() {
        const now = new Date();
        const str = now.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        document.querySelectorAll('#current-time').forEach(el => el.textContent = str);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ===============================
    // TAB SWITCHING (FOR SCHEDULE PAGE)
    // ===============================
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tableSections = document.querySelectorAll('.table-section');

    if (tabButtons.length > 0) {
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Hide all sections
                tableSections.forEach(section => {
                    section.style.display = 'none';
                });

                // Remove active from all buttons
                tabButtons.forEach(b => b.classList.remove('active'));

                // Activate clicked button
                btn.classList.add('active');

                // Show target section
                const targetId = btn.getAttribute('data-target');
                const target = document.getElementById(targetId);
                if (target) {
                    target.style.display = 'block';
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            });
        });

        // Show default section (Today's Schedule)
        const defaultSection = document.getElementById('todaySection');
        if (defaultSection) {
            defaultSection.style.display = 'block';
        }
    }

    // ===============================
    // DATE VALIDATION (No Sundays, Time Restrictions) - FOR SCHEDULE PAGE
    // ===============================
    const newDate = document.getElementById('newDate');
    const newStartTime = document.getElementById('newStartTime');
    const newEndTime = document.getElementById('newEndTime');

    if (newDate) {
        newDate.addEventListener('change', function () {
            const selectedDate = new Date(this.value);
            const day = selectedDate.getUTCDay();

            // Disable Sundays
            if (day === 0) {
                alert('Sunday is closed. Please select another day.');
                this.value = '';
                return;
            }

            // Set time restrictions based on day
            if (newStartTime && newEndTime) {
                if (day === 6) { // Saturday
                    newStartTime.min = '09:30';
                    newStartTime.max = '17:00';
                    newEndTime.min = '09:30';
                    newEndTime.max = '17:00';
                } else { // Monday-Friday
                    newStartTime.min = '08:00';
                    newStartTime.max = '19:00';
                    newEndTime.min = '08:00';
                    newEndTime.max = '19:00';
                }
            }
        });
    }

    // ===============================
    // ADD SCHEDULE FUNCTIONALITY - FOR SCHEDULE PAGE
    // ===============================
    const addForm = document.getElementById('addForm');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const date = newDate.value;
            const start = newStartTime.value;
            const end = newEndTime.value;

            if (!date || !start || !end) {
                alert('Please fill in all fields');
                return;
            }

            // Validate end time is after start time
            if (start >= end) {
                alert('End time must be after start time');
                return;
            }

            // Generate new ID
            const existingIds = Array.from(document.querySelectorAll('#newTable tbody tr'))
                .map(tr => parseInt(tr.cells[0].textContent))
                .filter(id => !isNaN(id));
            const newId = existingIds.length > 0 ? Math.max(...existingIds) + 1 : 1;

            const row = document.createElement('tr');
            row.setAttribute('data-date', date);
            row.innerHTML = `
                <td>${newId}</td>
                <td>${formatDate(date)}</td>
                <td>${formatTime(start)}</td>
                <td>${formatTime(end)}</td>
                <td>0</td>
                <td>
                    <button class="btn btn-sm action-btn" data-bs-toggle="modal" data-bs-target="#viewModal${newId}">View</button>
                    <button class="btn btn-sm action-btn">Edit</button>
                    <button class="btn btn-sm action-btn btn-delete">Delete</button>
                </td>
            `;
            
            document.querySelector('#newTable tbody').prepend(row);
            addForm.reset();
            updateNewCount();
            alert('Schedule added successfully!');
        });
    }

    // ===============================
    // FILTER BY DATE (View All Schedule) - FOR SCHEDULE PAGE
    // ===============================
    const filterDate = document.getElementById('filterDate');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearBtn');

    if (searchBtn && filterDate) {
        searchBtn.addEventListener('click', () => {
            const val = filterDate.value;
            if (!val) {
                alert('Please select a date to filter');
                return;
            }

            let visibleCount = 0;
            document.querySelectorAll('#allTable tbody tr').forEach(tr => {
                if (tr.getAttribute('data-date') === val) {
                    tr.style.display = '';
                    visibleCount++;
                } else {
                    tr.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                alert('No schedules found for this date');
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (filterDate) filterDate.value = '';
            document.querySelectorAll('#allTable tbody tr').forEach(tr => {
                tr.style.display = '';
            });
        });
    }

    // ===============================
    // DELETE SCHEDULE - FOR SCHEDULE PAGE
    // ===============================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-delete')) {
            if (confirm('Are you sure you want to delete this schedule?')) {
                e.target.closest('tr').remove();
                updateTodayCount();
                updateNewCount();
                updateAllCount();
            }
        }
    });

    // ===============================
    // UPDATE COUNTS - FOR SCHEDULE PAGE
    // ===============================
    function updateTodayCount() {
        const el = document.getElementById('todayScheduleCount');
        if (el) {
            const count = document.querySelectorAll('#todayTable tbody tr').length;
            el.textContent = count;
        }
    }

    function updateNewCount() {
        const el = document.getElementById('newScheduleCount');
        if (el) {
            const count = document.querySelectorAll('#newTable tbody tr').length;
            el.textContent = count;
        }
    }

    function updateAllCount() {
        const el = document.getElementById('allScheduleCount');
        if (el) {
            const count = document.querySelectorAll('#allTable tbody tr').length;
            el.textContent = count;
        }
    }

    // Initialize counts on page load for schedule page
    if (document.getElementById('todayScheduleCount')) {
        updateTodayCount();
        updateNewCount();
        updateAllCount();
    }

    // ===============================
    // MEDICAL RECORDS PAGE - FILTER FUNCTIONALITY
    // ===============================
    const medRecFilterBtn = document.getElementById('filterBtn');
    const medRecClearFilterBtn = document.getElementById('clearFilterBtn');
    const filterByDate = document.getElementById('filterByDate');
    const searchPatientName = document.getElementById('searchPatientName');
    const searchApptId = document.getElementById('searchApptId');
    const medRecTableRows = document.querySelectorAll('#medRecTable tbody tr');
    const filteredCardWrapper = document.getElementById('filteredCardWrapper');
    const filteredRecordsCount = document.getElementById('filteredRecordsCount');

    if (medRecFilterBtn) {
        medRecFilterBtn.addEventListener('click', function () {
            const dateValue = filterByDate.value;
            const nameValue = searchPatientName.value.toLowerCase().trim();
            const apptIdValue = searchApptId.value.trim();

            let visibleCount = 0;

            medRecTableRows.forEach(row => {
                // Skip the "no records" row
                if (row.cells.length < 6) {
                    row.style.display = 'none';
                    return;
                }

                const rowDate = row.getAttribute('data-date');
                const rowPatient = row.getAttribute('data-patient');
                const rowApptId = row.getAttribute('data-apptid');

                let matchDate = true;
                let matchName = true;
                let matchApptId = true;

                // Check date filter
                if (dateValue && rowDate !== dateValue) {
                    matchDate = false;
                }

                // Check name filter
                if (nameValue && !rowPatient.includes(nameValue)) {
                    matchName = false;
                }

                // Check appointment ID filter
                if (apptIdValue && rowApptId !== apptIdValue) {
                    matchApptId = false;
                }

                // Show row if all filters match
                if (matchDate && matchName && matchApptId) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show filtered count card
            if (dateValue || nameValue || apptIdValue) {
                filteredCardWrapper.style.display = 'block';
                filteredRecordsCount.textContent = visibleCount;
            }

            if (visibleCount === 0) {
                alert('No records match your filter criteria');
            }
        });
    }

    // ===============================
    // MEDICAL RECORDS - CLEAR FILTER
    // ===============================
    if (medRecClearFilterBtn) {
        medRecClearFilterBtn.addEventListener('click', function () {
            if (filterByDate) filterByDate.value = '';
            if (searchPatientName) searchPatientName.value = '';
            if (searchApptId) searchApptId.value = '';
            
            medRecTableRows.forEach(row => {
                row.style.display = '';
            });

            if (filteredCardWrapper) filteredCardWrapper.style.display = 'none';
        });
    }

    // ===============================
    // MEDICAL RECORDS - VIEW BUTTON
    // ===============================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-view') || e.target.closest('.btn-view')) {
            const btn = e.target.classList.contains('btn-view') ? e.target : e.target.closest('.btn-view');
            const row = btn.closest('tr');
            const dataAttr = row.getAttribute('data-medrec');
            
            if (!dataAttr) return;
            
            const data = JSON.parse(dataAttr);

            // Populate view modal
            document.getElementById('view_appt_id').textContent = data.APPT_ID || '-';
            document.getElementById('view_patient_name').textContent = `${data.PAT_FNAME} ${data.PAT_LNAME}`;
            document.getElementById('view_age').textContent = data.PAT_AGE || '-';
            document.getElementById('view_gender').textContent = data.PAT_GENDER || '-';
            document.getElementById('view_contact').textContent = data.PAT_CONTACT_NUM || '-';
            document.getElementById('view_email').textContent = data.PAT_EMAIL || '-';
            document.getElementById('view_service').textContent = data.SERVICE_NAME || '-';
            document.getElementById('view_status').textContent = data.APPT_STATUS || '-';
            document.getElementById('view_diagnosis').textContent = data.DIAGNOSIS || '-';
            document.getElementById('view_prescription').textContent = data.PRESCRIPTION || '-';
            document.getElementById('view_visit_date').textContent = formatDisplayDate(data.MED_REC_DATE);

            // Show modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }
    });

    // ===============================
    // MEDICAL RECORDS - EDIT BUTTON
    // ===============================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-edit') || e.target.closest('.btn-edit')) {
            const btn = e.target.classList.contains('btn-edit') ? e.target : e.target.closest('.btn-edit');
            const row = btn.closest('tr');
            const dataAttr = row.getAttribute('data-medrec');
            
            if (!dataAttr) return;
            
            const data = JSON.parse(dataAttr);

            // Populate edit modal
            document.getElementById('edit_medrec_id').value = data.MEDREC_ID;
            document.getElementById('edit_appt_id').value = data.APPT_ID;
            document.getElementById('edit_appt_id_hidden').value = data.APPT_ID;
            document.getElementById('edit_patient_name').value = `${data.PAT_FNAME} ${data.PAT_LNAME}`;
            document.getElementById('edit_age').value = data.PAT_AGE || '-';
            document.getElementById('edit_gender').value = data.PAT_GENDER || '-';
            document.getElementById('edit_contact').value = data.PAT_CONTACT_NUM || '-';
            document.getElementById('edit_email').value = data.PAT_EMAIL || '-';
            document.getElementById('edit_service').value = data.SERVICE_NAME || '-';
            document.getElementById('edit_status').value = data.APPT_STATUS || '-';
            document.getElementById('edit_diagnosis').value = data.DIAGNOSIS || '';
            document.getElementById('edit_prescription').value = data.PRESCRIPTION || '';
            document.getElementById('edit_visit_date').value = data.MED_REC_DATE || '';

            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }
    });

    // ===============================
    // MEDICAL RECORDS - EDIT FORM SUBMISSION
    // ===============================
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(editForm);

            fetch('ajax/update_medical_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Medical record updated successfully!');
                    
                    // Close modal
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    editModal.hide();
                    
                    // Reload page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert('Error updating record: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the record');
            });
        });
    }

    // ===============================
    // Date Time
    // ===============================
    function formatTime(timeStr) {
        if (!timeStr) return '';
        const [h, m] = timeStr.split(':');
        const hour = parseInt(h);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
        return `${displayHour}:${m} ${ampm}`;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function formatDisplayDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
});