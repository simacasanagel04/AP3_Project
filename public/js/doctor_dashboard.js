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
    // FOR SCHEDULE PAGE - TAB SWITCHING
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
    // FOR SCHEDULE PAGE - DATE VALIDATION (No Sundays, Time Restrictions)
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
    // FOR SCHEDULE PAGE - ADD SCHEDULE FUNCTIONALITY - 
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
    // FOR SCHEDULE PAGE - FILTER BY DATE (View All Schedule)
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
    // FOR SCHEDULE PAGE - DELETE SCHEDULE
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
    // FOR SCHEDULE PAGE - UPDATE COUNTS
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
    // FORM VALIDATION FOR ../doctor_create.php
    // ===============================
    const form = document.getElementById('doctorForm');
    const nextBtn = document.getElementById('nextBtn');
    if (form && nextBtn) {
        const required = form.querySelectorAll('[required]');

        function validate() {
            let valid = true;
            required.forEach(field => {
                const value = field.value.trim();
                if (!value) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            nextBtn.disabled = !valid;
            if (valid) {
                nextBtn.classList.remove('btn-secondary');
                nextBtn.classList.add('btn-primary');
            } else {
                nextBtn.classList.remove('btn-primary');
                nextBtn.classList.add('btn-secondary');
            }
        }

        required.forEach(field => {
            field.addEventListener('input', validate);
            field.addEventListener('change', validate);
        });

        validate();
    }

    // ========================================================================
    // DOCTOR DASHBOARD - APPOINTMENT MANAGEMENT (AJAX)
    // This section handles all AJAX operations for the doctor dashboard
    // including: View, Edit, Delete appointments and Status updates
    // ========================================================================

    // DASHBOARD - WORKING HOURS VALIDATION FOR EDIT MODAL
    // Purpose: Validates that appointments can only be scheduled during working hours
    // Working Hours:
    // - Monday to Friday: 8:00 AM - 6:00 PM
    // - Saturday: 9:00 AM - 5:00 PM
    // - Sunday: CLOSED
    // ===============================

    const editApptDate = document.getElementById('edit_appt_date');
    const editApptTime = document.getElementById('edit_appt_time');
    const timeRestrictionMsg = document.getElementById('time_restriction_msg');

    if (editApptDate && editApptTime) {
        // Listen for date changes to validate and set time restrictions
        editApptDate.addEventListener('change', function() {
            const selectedDate = new Date(this.value + 'T00:00:00');
            const day = selectedDate.getDay(); // 0=Sunday, 1=Monday, ..., 6=Saturday

            // Block Sunday appointments (day === 0)
            if (day === 0) {
                alert('Sunday is closed. Please select another day.');
                this.value = '';
                editApptTime.value = '';
                editApptTime.disabled = true;
                timeRestrictionMsg.textContent = 'Sunday is closed';
                return;
            }

            // Enable time input when valid day is selected
            editApptTime.disabled = false;

            // Set time restrictions based on selected day
            if (day === 6) { 
                // Saturday hours: 9:00 AM - 5:00 PM
                editApptTime.min = '09:00';
                editApptTime.max = '17:00';
                timeRestrictionMsg.textContent = 'Saturday: 9:00 AM - 5:00 PM';
            } else { 
                // Monday-Friday hours: 8:00 AM - 6:00 PM
                editApptTime.min = '08:00';
                editApptTime.max = '18:00';
                timeRestrictionMsg.textContent = 'Monday-Friday: 8:00 AM - 6:00 PM';
            }
        });

        // Validate time input when changed
        editApptTime.addEventListener('change', function() {
            // Ensure date is selected first
            if (!editApptDate.value) {
                alert('Please select a date first');
                this.value = '';
                return;
            }

            const selectedDate = new Date(editApptDate.value + 'T00:00:00');
            const day = selectedDate.getDay();
            const time = this.value;

            // Validate time is within working hours for Saturday
            if (day === 6) { 
                if (time < '09:00' || time > '17:00') {
                    alert('Saturday working hours: 9:00 AM - 5:00 PM');
                    this.value = '';
                }
            } else { 
                // Validate time is within working hours for Monday-Friday
                if (time < '08:00' || time > '18:00') {
                    alert('Monday-Friday working hours: 8:00 AM - 6:00 PM');
                    this.value = '';
                }
            }
        });
    }

    // ===============================
    // DASHBOARD - STATUS DROPDOWN CHANGE (INLINE UPDATE)
    // Purpose: Allows quick status updates directly from the table without opening a modal
    // Status Options: Scheduled, Completed, Cancelled
    // ===============================

    document.querySelectorAll('.status-select').forEach(select => {
        // Store original value to revert if update fails
        select.dataset.original = select.value;

        select.addEventListener('change', function() {
            const apptId = this.dataset.apptId;
            const statusId = this.value;
            const option = this.options[this.selectedIndex];
            const color = option.dataset.color; // For badge color update

            // Confirm status change with user
            if (confirm('Change status to ' + option.text + '?')) {
                const formData = new FormData();
                formData.append('appt_id', apptId);
                formData.append('status_id', statusId);

                // Send AJAX request to update status
                fetch('ajax/update_status.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated!');
                        // Update badge color if exists (for history view)
                        const badge = select.closest('td').querySelector('.badge');
                        if (badge) {
                            badge.className = `badge bg-${color}`;
                            badge.textContent = option.text;
                        }
                        // Update stored original value
                        select.dataset.original = statusId;
                    } else {
                        alert('Error: ' + data.message);
                        // Revert to original value on error
                        select.value = select.dataset.original;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating status');
                    // Revert to original value on error
                    select.value = select.dataset.original;
                });
            } else {
                // User cancelled - revert to original value
                select.value = select.dataset.original;
            }
        });
    });

    // ===============================
    // DASHBOARD - VIEW PATIENT DETAILS BUTTON
    // Purpose: Opens modal showing complete patient information; Fetches data from server via AJAX
    // ===============================
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const patId = this.dataset.patId;
            const apptId = this.dataset.apptId;
            const modal = new bootstrap.Modal(document.getElementById('viewPatientModal'));
            const content = document.getElementById('patientDetailsContent');

            // Show loading spinner while fetching data
            content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
            modal.show();

            // Fetch patient details from server
            fetch(`ajax/get_patient_details.php?pat_id=${patId}&appt_id=${apptId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const p = data.patient;
                        // Calculate age from date of birth
                        const age = new Date().getFullYear() - new Date(p.PAT_DOB).getFullYear();
                        
                        // Display patient information in modal
                        content.innerHTML = `
                            <p><strong>Appointment ID:</strong> ${apptId}</p>
                            <p><strong>Name:</strong> ${p.PAT_FIRST_NAME} ${p.PAT_MIDDLE_INIT}. ${p.PAT_LAST_NAME}</p>
                            <p><strong>DOB:</strong> ${p.PAT_DOB} (Age: ${age})</p>
                            <p><strong>Gender:</strong> ${p.PAT_GENDER}</p>
                            <p><strong>Contact:</strong> ${p.PAT_CONTACT_NUM}</p>
                            <p><strong>Email:</strong> ${p.PAT_EMAIL}</p>
                            <p><strong>Address:</strong> ${p.PAT_ADDRESS}</p>
                        `;
                    } else {
                        content.innerHTML = '<p class="text-danger">Failed to load patient details.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<p class="text-danger">An error occurred while loading patient details.</p>';
                });
        });
    });

    // ===============================
    // DASHBOARD - EDIT APPOINTMENT BUTTON
    // Purpose: Opens modal with appointment details for editing; Pre-fills form with current appointment data
    // ===============================
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            // Get appointment data from button's data attributes
            document.getElementById('edit_appt_id').value = this.dataset.apptId;
            document.getElementById('edit_appt_id_display').value = this.dataset.apptId;
            document.getElementById('edit_appt_date').value = this.dataset.apptDate;
            document.getElementById('edit_appt_time').value = this.dataset.apptTime;
            document.getElementById('edit_service').value = this.dataset.serviceId;
            document.getElementById('edit_status').value = this.dataset.status;

            // Trigger date change event to set proper time restrictions
            // This ensures the time picker shows correct min/max values
            editApptDate.dispatchEvent(new Event('change'));

            // Show the edit modal
            new bootstrap.Modal(document.getElementById('editApptModal')).show();
        });
    });

    // ===============================
    // DASHBOARD - EDIT APPOINTMENT FORM SUBMISSION
    // Purpose: Submits updated appointment data to server via AJAX; Validates working hours and updates appointment details
    // ===============================
    const editApptForm = document.getElementById('editApptForm');
    if (editApptForm) {
        editApptForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const formData = new FormData(this);

            // Send AJAX request to update appointment
            fetch('ajax/update_appointment.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('UPDATED SUCCESSFULLY');
                    // Reload page to show updated data in tables
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the appointment');
            });
        });
    }

    // ===============================
    // DASHBOARD - DELETE APPOINTMENT BUTTON
    // Purpose: Removes appointment from database; Requires confirmation before deletion
    // ===============================
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const apptId = this.dataset.apptId;
            const row = this.closest('tr'); // Get table row for removal

            // Confirm deletion with user
            if (confirm('Delete appointment ' + apptId + '?')) {
                const formData = new FormData();
                formData.append('appt_id', apptId);

                // Send AJAX request to delete appointment
                fetch('ajax/delete_appointment.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Remove row from table immediately for better UX
                        row.remove();
                        alert('Appointment deleted.');
                        // Reload to update counts and ensure data consistency
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the appointment');
                });
            }
        });
    });

    // ===============================
    // UTILITY FUNCTIONS - Date & Time Formatting
    // These functions are used across multiple pages
    // Format 24-hour time to 12-hour format with AM/PM
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