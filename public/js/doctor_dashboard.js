// public/js/doctor_dashboard.js
// ========================================================================
// Used for all doctor pages: dashboard, schedule, medical records, etc.
// ========================================================================

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    // ========================================================================
    // SECTION 0: AUTO-UPDATE LAST LOGIN TIMESTAMP
    // ========================================================================

    // Update last login every 5 minutes
    setInterval(function() {
        fetch('ajax/update_last_login.php', {
            method: 'POST'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                console.log('Last login updated:', data.timestamp);
            }
        })
        .catch(err => console.error('Failed to update last login:', err));
    }, 300000); // 5 minutes = 300000ms

    // Initial update on page load
    fetch('ajax/update_last_login.php', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            console.log('Initial login timestamp updated:', data.timestamp);
        }
    })
    .catch(err => console.error('Failed to update login timestamp:', err));

    // ========================================================================
    // SECTION 1: GENERAL NAVIGATION & UI
    // ========================================================================

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

    // ========================================================================
    // SECTION 2: SCHEDULE PAGE - TAB SWITCHING & MANAGEMENT
    // ========================================================================

    // ===============================
    // TAB SWITCHING FOR SCHEDULE PAGE
    // ===============================
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tableSections = document.querySelectorAll('.table-section');
    const addScheduleForm = document.getElementById('addScheduleForm');
    const addNewScheduleBtn = document.getElementById('addNewScheduleBtn');
    const scheduleCountCard = document.getElementById('scheduleCountCard');
    const scheduleCount = document.getElementById('scheduleCount');
    const scheduleCountLabel = document.getElementById('scheduleCountLabel');

    // Handle "Add New Schedule" button click
    if (addNewScheduleBtn) {
        addNewScheduleBtn.addEventListener('click', function() {
            // Hide all table sections
            tableSections.forEach(section => section.style.display = 'none');
            
            // Remove active from all tab buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Show "All Schedules" section
            const allSection = document.getElementById('allSection');
            if (allSection) allSection.style.display = 'block';
            
            // Show add schedule form
            if (addScheduleForm) {
                addScheduleForm.style.display = 'block';
                // Scroll to form
                addScheduleForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // Handle tab button clicks for schedule page
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.id === 'addNewScheduleBtn') return; // Skip for add button
            
            // Hide add schedule form when switching tabs
            if (addScheduleForm) addScheduleForm.style.display = 'none';
            
            // Hide all sections
            tableSections.forEach(section => section.style.display = 'none');
            
            // Remove active from all buttons
            tabButtons.forEach(b => b.classList.remove('active'));
            
            // Activate clicked button
            this.classList.add('active');
            
            // Show target section
            const targetId = this.getAttribute('data-target');
            const target = document.getElementById(targetId);
            if (target) {
                target.style.display = 'block';
                
                // Update count card
                const count = this.getAttribute('data-count');
                const label = this.getAttribute('data-label');
                if (scheduleCount && scheduleCountLabel) {
                    scheduleCount.textContent = count;
                    scheduleCountLabel.innerHTML = '<i class="bi bi-calendar-day"></i> ' + label;
                }
                
                setTimeout(() => {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        });
    });

    // ========================================================================
    // SECTION 3: SCHEDULE PAGE - WORKING HOURS VALIDATION
    // ========================================================================

   // ===============================
    // WORKING HOURS VALIDATION FOR NEW SCHEDULE
    // ===============================
    const newScheduleWeekday = document.getElementById('newScheduleWeekday');
    const newScheduleStartTime = document.getElementById('newScheduleStartTime');
    const newScheduleEndTime = document.getElementById('newScheduleEndTime');
    const scheduleTimeRestriction = document.getElementById('scheduleTimeRestriction');

    if (newScheduleWeekday) {
        newScheduleWeekday.addEventListener('change', function() {
            const selectedWeekday = this.value;

            if (!selectedWeekday) {
                if (newScheduleStartTime) newScheduleStartTime.disabled = true;
                if (newScheduleEndTime) newScheduleEndTime.disabled = true;
                if (scheduleTimeRestriction) scheduleTimeRestriction.textContent = 'Select weekday first';
                return;
            }

            // Enable time inputs
            if (newScheduleStartTime) newScheduleStartTime.disabled = false;
            if (newScheduleEndTime) newScheduleEndTime.disabled = false;

            if (selectedWeekday === 'Saturday') {
                if (newScheduleStartTime) {
                    newScheduleStartTime.min = '09:00';
                    newScheduleStartTime.max = '17:00';
                }
                if (newScheduleEndTime) {
                    newScheduleEndTime.min = '09:00';
                    newScheduleEndTime.max = '17:00';
                }
                if (scheduleTimeRestriction) scheduleTimeRestriction.textContent = 'Saturday: 9:00 AM - 5:00 PM';
            } else { // Monday-Friday
                if (newScheduleStartTime) {
                    newScheduleStartTime.min = '08:00';
                    newScheduleStartTime.max = '18:00';
                }
                if (newScheduleEndTime) {
                    newScheduleEndTime.min = '08:00';
                    newScheduleEndTime.max = '18:00';
                }
                if (scheduleTimeRestriction) scheduleTimeRestriction.textContent = 'Monday-Friday: 8:00 AM - 6:00 PM';
            }
        });
    }

    // ===============================
    // WORKING HOURS VALIDATION FOR EDIT SCHEDULE
    // ===============================
    const editSchedWeekday = document.getElementById('edit_sched_weekday');
    const editSchedStartTime = document.getElementById('edit_sched_start_time');
    const editSchedEndTime = document.getElementById('edit_sched_end_time');
    const editScheduleTimeRestriction = document.getElementById('editScheduleTimeRestriction');

    if (editSchedWeekday) {
        editSchedWeekday.addEventListener('change', function() {
            const selectedWeekday = this.value;

            if (!selectedWeekday) {
                if (editSchedStartTime) editSchedStartTime.disabled = true;
                if (editSchedEndTime) editSchedEndTime.disabled = true;
                if (editScheduleTimeRestriction) editScheduleTimeRestriction.textContent = 'Select weekday first';
                return;
            }

            if (editSchedStartTime) editSchedStartTime.disabled = false;
            if (editSchedEndTime) editSchedEndTime.disabled = false;

            if (selectedWeekday === 'Saturday') {
                if (editSchedStartTime) {
                    editSchedStartTime.min = '09:00';
                    editSchedStartTime.max = '17:00';
                }
                if (editSchedEndTime) {
                    editSchedEndTime.min = '09:00';
                    editSchedEndTime.max = '17:00';
                }
                if (editScheduleTimeRestriction) editScheduleTimeRestriction.textContent = 'Saturday: 9:00 AM - 5:00 PM';
            } else {
                if (editSchedStartTime) {
                    editSchedStartTime.min = '08:00';
                    editSchedStartTime.max = '18:00';
                }
                if (editSchedEndTime) {
                    editSchedEndTime.min = '08:00';
                    editSchedEndTime.max = '18:00';
                }
                if (editScheduleTimeRestriction) editScheduleTimeRestriction.textContent = 'Monday-Friday: 8:00 AM - 6:00 PM';
            }
        });
    }

    // ========================================================================
    // SECTION 4: SCHEDULE PAGE - ADD NEW SCHEDULE
    // ========================================================================

    // ===============================
    // ADD NEW SCHEDULE FORM SUBMISSION
    // ===============================
    const scheduleForm = document.getElementById('scheduleForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const weekday = newScheduleWeekday ? newScheduleWeekday.value : '';
            const startTime = newScheduleStartTime ? newScheduleStartTime.value : '';
            const endTime = newScheduleEndTime ? newScheduleEndTime.value : '';

            if (!weekday || !startTime || !endTime) {
                alert('Please fill in all fields');
                return;
            }

            if (startTime >= endTime) {
                alert('End time must be after start time');
                return;
            }

            const formData = new FormData();
            formData.append('weekday', weekday);
            formData.append('start_time', startTime);
            formData.append('end_time', endTime);

            fetch('ajax/add_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule added successfully!');
                    if (addScheduleForm) addScheduleForm.style.display = 'none';
                    scheduleForm.reset();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the schedule');
            });
        });
    }

    // ===============================
    // CLEAR SCHEDULE FORM
    // ===============================
    const clearScheduleFormBtn = document.getElementById('clearScheduleFormBtn');
    if (clearScheduleFormBtn && scheduleForm) {
        clearScheduleFormBtn.addEventListener('click', function() {
            scheduleForm.reset();
            if (newScheduleStartTime) newScheduleStartTime.disabled = true;
            if (newScheduleEndTime) newScheduleEndTime.disabled = true;
            if (scheduleTimeRestriction) scheduleTimeRestriction.textContent = 'Select date first';
        });
    }

    // ========================================================================
    // SECTION 5: SCHEDULE PAGE - VIEW SCHEDULE MODAL
    // ========================================================================

    // ===============================
    // VIEW SCHEDULE BUTTON - Shows appointments for this schedule
    // ===============================
    document.querySelectorAll('.btn-view-schedule').forEach(btn => {
        btn.addEventListener('click', function() {
            const schedId = this.getAttribute('data-sched-id');
            const weekday = this.getAttribute('data-weekday');
            
            // Set modal title info
            const viewModalSchedId = document.getElementById('view_modal_sched_id');
            const viewModalWeekday = document.getElementById('view_modal_weekday');
            const appointmentsList = document.getElementById('scheduleAppointmentsList');
            
            if (viewModalSchedId) viewModalSchedId.textContent = schedId;
            if (viewModalWeekday) viewModalWeekday.textContent = weekday;
            
            // Show loading
            if (appointmentsList) {
                appointmentsList.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            }
            
            // Show modal
            new bootstrap.Modal(document.getElementById('viewScheduleModal')).show();
            
            // Fetch appointments for this weekday
            fetch(`ajax/get_schedule_appointments.php?sched_id=${schedId}&weekday=${encodeURIComponent(weekday)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.appointments.length > 0) {
                            let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Appointment ID</th><th>Patient Name</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
                            
                            data.appointments.forEach(appt => {
                                const statusBadge = appt.STATUS_NAME === 'Scheduled' ? 'warning' : 
                                                appt.STATUS_NAME === 'Completed' ? 'success' : 'danger';
                                html += `<tr>
                                    <td>${appt.APPT_ID}</td>
                                    <td>${appt.patient_name}</td>
                                    <td>${appt.formatted_date}</td>
                                    <td>${appt.formatted_time}</td>
                                    <td><span class="badge bg-${statusBadge}">${appt.STATUS_NAME}</span></td>
                                </tr>`;
                            });
                            
                            html += '</tbody></table></div>';
                            appointmentsList.innerHTML = html;
                        } else {
                            appointmentsList.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No appointments scheduled for ' + weekday + '.</div>';
                        }
                    } else {
                        appointmentsList.innerHTML = '<div class="alert alert-danger">Failed to load appointments: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    appointmentsList.innerHTML = '<div class="alert alert-danger">An error occurred while loading appointments.</div>';
                });
        });
    });

    // ========================================================================
    // SECTION 6: SCHEDULE PAGE - EDIT SCHEDULE MODAL
    // ========================================================================

    // ===============================
    // EDIT SCHEDULE BUTTON
    // ===============================
    document.querySelectorAll('.btn-edit-schedule').forEach(btn => {
        btn.addEventListener('click', function() {
        const schedId = this.getAttribute('data-sched-id');
        const weekday = this.getAttribute('data-weekday');
        const startTime = this.getAttribute('data-start');
        const endTime = this.getAttribute('data-end');

        const editSchedId = document.getElementById('edit_sched_id');
        const editSchedIdDisplay = document.getElementById('edit_sched_id_display');

        if (editSchedId) editSchedId.value = schedId;
        if (editSchedIdDisplay) editSchedIdDisplay.value = schedId;
        if (editSchedWeekday) editSchedWeekday.value = weekday;
        if (editSchedStartTime) editSchedStartTime.value = startTime;
        if (editSchedEndTime) editSchedEndTime.value = endTime;

        // Trigger change event to set time restrictions
        if (editSchedWeekday) editSchedWeekday.dispatchEvent(new Event('change'));

        new bootstrap.Modal(document.getElementById('editScheduleModal')).show();
    });
});

    // ===============================
    // EDIT SCHEDULE FORM SUBMISSION
    // ===============================
    const editScheduleForm = document.getElementById('editScheduleForm');
    if (editScheduleForm) {
        editScheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            if (editSchedWeekday) formData.append('weekday', editSchedWeekday.value);
            if (editSchedStartTime) formData.append('start_time', editSchedStartTime.value);
            if (editSchedEndTime) formData.append('end_time', editSchedEndTime.value);

            fetch('ajax/update_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('UPDATED SUCCESSFULLY');
                    bootstrap.Modal.getInstance(document.getElementById('editScheduleModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the schedule');
            });
        });
    }

    // ========================================================================
    // SECTION 7: SCHEDULE PAGE - DELETE SCHEDULE
    // ========================================================================

    // ===============================
    // DELETE SCHEDULE BUTTON
    // ===============================
    document.querySelectorAll('.btn-delete-schedule').forEach(btn => {
        btn.addEventListener('click', function() {
            const schedId = this.getAttribute('data-sched-id');
            const row = this.closest('tr');

            if (confirm('Are you sure you want to delete this schedule?')) {
                const formData = new FormData();
                formData.append('sched_id', schedId);

                fetch('ajax/delete_schedule.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        row.remove();
                        alert('Schedule deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the schedule');
                });
            }
        });
    });

    // ========================================================================
    // SECTION 8: SCHEDULE PAGE - FILTER FUNCTIONALITY
    // ========================================================================

    // ===============================
    // SCHEDULE FILTER FUNCTIONALITY
    // ===============================
    const schedFilterByWeekday = document.getElementById('filterByWeekday');
    const schedSearchScheduleId = document.getElementById('searchScheduleId');
    const applyScheduleFilterBtn = document.getElementById('applyScheduleFilterBtn');
    const clearScheduleFilterBtn = document.getElementById('clearScheduleFilterBtn');
    const scheduleFilteredResultsWrapper = document.getElementById('scheduleFilteredResultsWrapper');
    const scheduleFilteredCount = document.getElementById('scheduleFilteredCount');

    if (applyScheduleFilterBtn) {
        applyScheduleFilterBtn.addEventListener('click', function() {
            const weekdayValue = schedFilterByWeekday ? schedFilterByWeekday.value : '';
            const schedIdValue = schedSearchScheduleId ? schedSearchScheduleId.value.trim() : '';

            const activeSection = document.querySelector('.table-section:not([style*="display: none"])');
            if (!activeSection) return;

            const rows = activeSection.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                if (row.cells.length < 5) {
                    row.style.display = 'none';
                    return;
                }

                const rowSchedId = row.getAttribute('data-sched-id') || '';
                const rowWeekday = row.getAttribute('data-weekday') || '';

                let matchWeekday = true;
                let matchSchedId = true;

                if (weekdayValue && rowWeekday !== weekdayValue) {
                    matchWeekday = false;
                }

                if (schedIdValue && !rowSchedId.includes(schedIdValue)) {
                    matchSchedId = false;
                }

                if (matchWeekday && matchSchedId) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (weekdayValue || schedIdValue) {
                if (scheduleFilteredResultsWrapper) scheduleFilteredResultsWrapper.style.display = 'block';
                if (scheduleFilteredCount) scheduleFilteredCount.textContent = visibleCount;

                if (visibleCount === 0) {
                    alert('No schedules match your filter criteria');
                }
            } else {
                alert('Please enter at least one filter criteria');
            }
        });
    }

    if (clearScheduleFilterBtn) {
        clearScheduleFilterBtn.addEventListener('click', function() {
            if (schedFilterByWeekday) schedFilterByWeekday.value = '';
            if (schedSearchScheduleId) schedSearchScheduleId.value = '';

            document.querySelectorAll('.table-section tbody tr').forEach(row => {
                row.style.display = '';
            });

            if (scheduleFilteredResultsWrapper) {
                scheduleFilteredResultsWrapper.style.display = 'none';
            }
        });
    }

    // ========================================================================
    // SECTION 9: MEDICAL RECORDS PAGE - FILTER FUNCTIONALITY (CONSOLIDATED)
    // ========================================================================

    // ===============================
    // MEDICAL RECORDS PAGE - UNIFIED FILTER FUNCTIONALITY
    // ===============================
    const medRecFilterBtn = document.getElementById('filterBtn');
    const medRecClearFilterBtn = document.getElementById('clearFilterBtn');
    const medRecFilterByDate = document.getElementById('filterByDate');
    const medRecSearchPatientName = document.getElementById('searchPatientName');
    const medRecSearchApptId = document.getElementById('searchApptId');
    const medRecSearchMedRecId = document.getElementById('searchMedRecId');
    const filteredCardWrapper = document.getElementById('filteredCardWrapper');
    const filteredRecordsCount = document.getElementById('filteredRecordsCount');

    if (medRecFilterBtn) {
        medRecFilterBtn.addEventListener('click', function () {
            const dateValue = medRecFilterByDate ? medRecFilterByDate.value : '';
            const nameValue = medRecSearchPatientName ? medRecSearchPatientName.value.toLowerCase().trim() : '';
            const apptIdValue = medRecSearchApptId ? medRecSearchApptId.value.trim() : '';
            const medRecIdValue = medRecSearchMedRecId ? medRecSearchMedRecId.value.trim() : '';

            // Get all table rows ONCE
            const medRecTableRows = document.querySelectorAll('#medRecTable tbody tr');
            let visibleCount = 0;

            medRecTableRows.forEach(row => {
                // Skip the "no records" row
                if (row.cells.length < 7) {
                    row.style.display = 'none';
                    return;
                }

                const rowDate = row.getAttribute('data-date');
                const rowPatient = row.getAttribute('data-patient');
                const rowApptId = row.getAttribute('data-apptid');
                const rowMedRecId = row.getAttribute('data-medrecid');

                let matchDate = true;
                let matchName = true;
                let matchApptId = true;
                let matchMedRecId = true;

                // Check date filter
                if (dateValue && rowDate !== dateValue) {
                    matchDate = false;
                }

                // Check name filter (already lowercase from PHP)
                if (nameValue) {
                    if (!rowPatient.includes(nameValue)) {
                        matchName = false;
                    }
                }

                // Check appointment ID filter
                if (apptIdValue && rowApptId !== apptIdValue) {
                    matchApptId = false;
                }

                // Check medical record ID filter
                if (medRecIdValue && rowMedRecId !== medRecIdValue) {
                    matchMedRecId = false;
                }

                // Show row if all filters match
                if (matchDate && matchName && matchApptId && matchMedRecId) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show filtered count card
            if (dateValue || nameValue || apptIdValue || medRecIdValue) {
                if (filteredCardWrapper) filteredCardWrapper.style.display = 'block';
                if (filteredRecordsCount) filteredRecordsCount.textContent = visibleCount;

                if (visibleCount === 0) {
                    alert('No records match your filter criteria');
                }
            } else {
                alert('Please enter at least one filter criteria');
            }
        });
    }

    // ===============================
    // MEDICAL RECORDS - CLEAR FILTER
    // ===============================
    if (medRecClearFilterBtn) {
        medRecClearFilterBtn.addEventListener('click', function () {
            if (medRecFilterByDate) medRecFilterByDate.value = '';
            if (medRecSearchPatientName) medRecSearchPatientName.value = '';
            if (medRecSearchApptId) medRecSearchApptId.value = '';
            if (medRecSearchMedRecId) medRecSearchMedRecId.value = '';
            
            // Reset all table rows ONCE
            document.querySelectorAll('#medRecTable tbody tr').forEach(row => {
                row.style.display = '';
            });

            if (filteredCardWrapper) filteredCardWrapper.style.display = 'none';
        });
    }

    // ========================================================================
    // SECTION 10: MEDICAL RECORDS PAGE - VIEW & EDIT BUTTONS
    // ========================================================================

    // ===============================
    // MEDICAL RECORDS - VIEW BUTTON
    // ===============================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-view') || e.target.closest('.btn-view')) {
            const btn = e.target.classList.contains('btn-view') ? e.target : e.target.closest('.btn-view');
            const row = btn.closest('tr');
            
            // Check if this is a medical record view button (has data-medrec)
            const dataAttr = row.getAttribute('data-medrec');
            
            if (!dataAttr) return;
            
            const data = JSON.parse(dataAttr);

            // Populate view modal
            const viewApptId = document.getElementById('view_appt_id');
            const viewPatientName = document.getElementById('view_patient_name');
            const viewAge = document.getElementById('view_age');
            const viewGender = document.getElementById('view_gender');
            const viewContact = document.getElementById('view_contact');
            const viewEmail = document.getElementById('view_email');
            const viewService = document.getElementById('view_service');
            const viewStatus = document.getElementById('view_status');
            const viewDiagnosis = document.getElementById('view_diagnosis');
            const viewPrescription = document.getElementById('view_prescription');
            const viewVisitDate = document.getElementById('view_visit_date');

            if (viewApptId) viewApptId.textContent = data.APPT_ID || '-';
            if (viewPatientName) viewPatientName.textContent = `${data.PAT_FIRST_NAME} ${data.PAT_LAST_NAME}`;
            if (viewAge) viewAge.textContent = data.PAT_AGE || '-';
            if (viewGender) viewGender.textContent = data.PAT_GENDER || '-';
            if (viewContact) viewContact.textContent = data.PAT_CONTACT_NUM || '-';
            if (viewEmail) viewEmail.textContent = data.PAT_EMAIL || '-';
            if (viewService) viewService.textContent = data.SERVICE_NAME || '-';
            if (viewStatus) viewStatus.textContent = data.APPT_STATUS || '-';
            if (viewDiagnosis) viewDiagnosis.textContent = data.MED_REC_DIAGNOSIS || '-';
            if (viewPrescription) viewPrescription.textContent = data.MED_REC_PRESCRIPTION || '-';
            if (viewVisitDate) viewVisitDate.textContent = formatDisplayDate(data.MED_REC_VISIT_DATE);

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
            
            // Check if this is a medical record edit button (has data-medrec)
            const dataAttr = row.getAttribute('data-medrec');
            
            if (!dataAttr) return;
            
            const data = JSON.parse(dataAttr);

            // Populate edit modal
            const editMedRecId = document.getElementById('edit_med_rec_id');
            const editApptId = document.getElementById('edit_appt_id');
            const editApptIdHidden = document.getElementById('edit_appt_id_hidden');
            const editPatientName = document.getElementById('edit_patient_name');
            const editAge = document.getElementById('edit_age');
            const editGender = document.getElementById('edit_gender');
            const editContact = document.getElementById('edit_contact');
            const editEmail = document.getElementById('edit_email');
            const editService = document.getElementById('edit_service');
            const editStatus = document.getElementById('edit_status');
            const editDiagnosis = document.getElementById('edit_diagnosis');
            const editPrescription = document.getElementById('edit_prescription');
            const editVisitDate = document.getElementById('edit_visit_date');

            if (editMedRecId) editMedRecId.value = data.MED_REC_ID;
            if (editApptId) editApptId.value = data.APPT_ID;
            if (editApptIdHidden) editApptIdHidden.value = data.APPT_ID;
            if (editPatientName) editPatientName.value = `${data.PAT_FIRST_NAME} ${data.PAT_LAST_NAME}`;
            if (editAge) editAge.value = data.PAT_AGE || '-';
            if (editGender) editGender.value = data.PAT_GENDER || '-';
            if (editContact) editContact.value = data.PAT_CONTACT_NUM || '-';
            if (editEmail) editEmail.value = data.PAT_EMAIL || '-';
            if (editService) editService.value = data.SERVICE_NAME || '-';
            if (editStatus) editStatus.value = data.APPT_STATUS || '-';
            if (editDiagnosis) editDiagnosis.value = data.MED_REC_DIAGNOSIS || '';
            if (editPrescription) editPrescription.value = data.MED_REC_PRESCRIPTION || '';
            if (editVisitDate) editVisitDate.value = data.MED_REC_VISIT_DATE || '';

            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }
    });

    // ===============================
    // MEDICAL RECORDS - EDIT FORM SUBMISSION
    // ===============================
    const medRecEditForm = document.getElementById('editForm');
    if (medRecEditForm) {
        medRecEditForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(medRecEditForm);

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

    // ========================================================================
    // SECTION 11: FORM VALIDATION FOR DOCTOR CREATION PAGE
    // ========================================================================

    // ===============================
    // FORM VALIDATION FOR ../doctor_create.php
    // ===============================
    const doctorForm = document.getElementById('doctorForm');
    const nextBtn = document.getElementById('nextBtn');
    if (doctorForm && nextBtn) {
        const required = doctorForm.querySelectorAll('[required]');

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
    // SECTION 12: DASHBOARD - APPOINTMENT MANAGEMENT (AJAX)
    // This section handles all AJAX operations for the doctor dashboard
    // including: View appointments
    // NOTE: Update, Delete, and Status Change functionalities have been removed
    // Status is now displayed as read-only badges from the database
    // ========================================================================

    // ===============================
    // DASHBOARD - VIEW PATIENT DETAILS BUTTON
    // Purpose: Opens modal showing complete patient information
    // Fetches data from server via AJAX
    // ===============================
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const patId = this.dataset.patId;
            const apptId = this.dataset.apptId;
            
            // Skip if this is a medical records view button (different handling)
            if (!patId) return;
            
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
                            <p><strong>Age:</strong> ${age}</p>
                            <p><strong>Gender:</strong> ${p.PAT_GENDER}</p>
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

    // ========================================================================
    // SECTION 13: DASHBOARD - CARD UPDATE & FILTER FUNCTIONALITY
    // ========================================================================

    // ===============================
    // DASHBOARD - UPDATE APPOINTMENT COUNT CARD
    // Updates the count card when switching between tabs
    // ===============================
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const count = this.dataset.count;
            const label = this.dataset.label;
            
            const appointmentCount = document.getElementById('appointmentCount');
            const appointmentLabel = document.getElementById('appointmentLabel');

            if (appointmentCount) appointmentCount.textContent = count;
            if (appointmentLabel) appointmentLabel.textContent = label;
        });
    });

    // ===============================
    // DASHBOARD FILTER FUNCTIONALITY
    // ===============================
    
    const dashFilterByDate = document.getElementById('filterByDate');
    const dashSearchPatientName = document.getElementById('searchPatientName');
    const dashSearchApptId = document.getElementById('searchApptId');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const dashClearFilterBtn = document.getElementById('clearFilterBtn');
    const filteredResultsWrapper = document.getElementById('filteredResultsWrapper');
    const filteredCount = document.getElementById('filteredCount');

    // Apply Filter Button
    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function() {
            const dateValue = dashFilterByDate ? dashFilterByDate.value : '';
            const nameValue = dashSearchPatientName ? dashSearchPatientName.value.toLowerCase().trim() : '';
            const apptIdValue = dashSearchApptId ? dashSearchApptId.value.trim() : '';

            // Get current active table
            const activeSection = document.querySelector('.table-section:not([style*="display: none"])');
            if (!activeSection) return;

            const tableBody = activeSection.querySelector('tbody');
            if (!tableBody) return;

            const rows = tableBody.querySelectorAll('tr');
            let visibleCount = 0;

            rows.forEach(row => {
                // Skip empty state rows
                if (row.cells.length < 6) {
                    row.style.display = 'none';
                    return;
                }

                const rowDate = row.dataset.date;
                const rowPatient = row.getAttribute('data-patient-name') || '';
                const rowApptId = row.dataset.apptId || '';

                let matchDate = true;
                let matchName = true;
                let matchApptId = true;

                // Check date filter
                if (dateValue && rowDate !== dateValue) {
                    matchDate = false;
                }

                // Check name filter (case-insensitive partial match)
                if (nameValue) {
                    if (!rowPatient.toLowerCase().includes(nameValue)) {
                        matchName = false;
                    }
                }

                // Check appointment ID filter
                if (apptIdValue && !rowApptId.includes(apptIdValue)) {
                    matchApptId = false;
                }

                // Show/hide row based on all filters
                if (matchDate && matchName && matchApptId) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show filtered results if any filter is applied
            if (dateValue || nameValue || apptIdValue) {
                if (filteredResultsWrapper) filteredResultsWrapper.style.display = 'block';
                if (filteredCount) filteredCount.textContent = visibleCount;

                if (visibleCount === 0) {
                    alert('No appointments match your filter criteria');
                }
            } else {
                alert('Please enter at least one filter criteria');
            }
        });
    }

    // Clear Filter Button
    if (dashClearFilterBtn) {
        dashClearFilterBtn.addEventListener('click', function() {
            // Clear all input fields
            if (dashFilterByDate) dashFilterByDate.value = '';
            if (dashSearchPatientName) dashSearchPatientName.value = '';
            if (dashSearchApptId) dashSearchApptId.value = '';

            // Show all rows in all tables
            document.querySelectorAll('.table-section tbody tr').forEach(row => {
                row.style.display = '';
            });

            // Hide filtered results card
            if (filteredResultsWrapper) {
                filteredResultsWrapper.style.display = 'none';
            }
        });
    }

    // ===============================
    // REAL-TIME SEARCH (Optional - triggers filter on Enter key)
    // ===============================
    [dashSearchPatientName, dashSearchApptId].forEach(input => {
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (applyFilterBtn) applyFilterBtn.click();
                }
            });
        }
    });

    // ========================================================================
    // SECTION 14: UTILITY FUNCTIONS - Date & Time Formatting
    // These functions are used across multiple pages
    // Format 24-hour time to 12-hour format with AM/PM
    // ========================================================================

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

    // ========================================================================
    // SECTION 15: MEDICAL RECORDS - ADD NEW RECORD FUNCTIONALITY
    // ========================================================================

    // ===============================
    // SHOW ADD NEW RECORD FORM
    // ===============================
    const addNewRecordBtn = document.getElementById('addNewRecordBtn');
const addRecordFormWrapper = document.getElementById('addRecordFormWrapper');
const cancelAddRecordBtn = document.getElementById('cancelAddRecordBtn');
const addMedicalRecordForm = document.getElementById('addMedicalRecordForm');

if (addNewRecordBtn) {
    addNewRecordBtn.addEventListener('click', function() {
        if (addRecordFormWrapper) {
            addRecordFormWrapper.style.display = 'block';
            addRecordFormWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Reset form
            if (addMedicalRecordForm) addMedicalRecordForm.reset();
            document.getElementById('new_patient_name').value = '';
            document.getElementById('new_patient_age').value = '';
            document.getElementById('new_patient_gender').value = '';
            document.getElementById('new_service_name').value = '';
            
            // Remove any existing warning messages
            const existingWarning = document.getElementById('existingRecordWarning');
            if (existingWarning) existingWarning.remove();
        }
    });
}

// ===============================
// CANCEL ADD NEW RECORD
// ===============================
if (cancelAddRecordBtn) {
    cancelAddRecordBtn.addEventListener('click', function() {
        if (addRecordFormWrapper) {
            addRecordFormWrapper.style.display = 'none';
        }
        if (addMedicalRecordForm) addMedicalRecordForm.reset();
        document.getElementById('new_patient_name').value = '';
        document.getElementById('new_patient_age').value = '';
        document.getElementById('new_patient_gender').value = '';
        document.getElementById('new_service_name').value = '';
        
        // Remove any existing warning messages
        const existingWarning = document.getElementById('existingRecordWarning');
        if (existingWarning) existingWarning.remove();
    });
}

// ===============================
// FETCH APPOINTMENT DETAILS WHEN APPT ID IS ENTERED
// ===============================
const newApptIdInput = document.getElementById('new_appt_id');
if (newApptIdInput) {
    newApptIdInput.addEventListener('blur', fetchAppointmentDetails);
    newApptIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            fetchAppointmentDetails();
        }
    });
}

function fetchAppointmentDetails() {
    const apptId = newApptIdInput.value.trim();
    if (!apptId) return;

    // Show loading state
    document.getElementById('new_patient_name').value = 'Loading...';
    document.getElementById('new_patient_age').value = '';
    document.getElementById('new_patient_gender').value = '';
    document.getElementById('new_service_name').value = '';
    
    // Remove any existing warning messages
    const existingWarning = document.getElementById('existingRecordWarning');
    if (existingWarning) existingWarning.remove();

    fetch(`ajax/get_appointment_details.php?appt_id=${encodeURIComponent(apptId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const appt = data.appointment;
                document.getElementById('new_patient_name').value = `${appt.PAT_FIRST_NAME} ${appt.PAT_LAST_NAME}`;
                document.getElementById('new_patient_age').value = appt.PAT_AGE;
                document.getElementById('new_patient_gender').value = appt.PAT_GENDER;
                document.getElementById('new_service_name').value = appt.SERV_NAME;
                
                // ========================================
                // FIX: Show warning ONLY, don't block
                // ========================================
                if (data.recordExists) {
                    const warningDiv = document.createElement('div');
                    warningDiv.id = 'existingRecordWarning';
                    warningDiv.className = 'alert alert-warning mt-3';
                    warningDiv.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Note:</strong> ${data.recordCount} medical record(s) already exist for this appointment. 
                        You can still add another record for follow-up visits.
                    `;
                    
                    // Insert warning after the service name field
                    const serviceCol = document.getElementById('new_service_name').closest('.col-md-4');
                    serviceCol.parentNode.insertBefore(warningDiv, serviceCol.nextSibling);
                }
            } else {
                // ========================================
                // FIX: Show error ONCE, not in loop
                // ========================================
                alert('⚠️ ' + data.message);
                document.getElementById('new_patient_name').value = '';
                document.getElementById('new_patient_age').value = '';
                document.getElementById('new_patient_gender').value = '';
                document.getElementById('new_service_name').value = '';
                
                // Clear the appointment ID field to prevent retry
                newApptIdInput.value = '';
                newApptIdInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('⚠️ Network error occurred while fetching appointment details. Please check your connection and try again.');
            document.getElementById('new_patient_name').value = '';
            document.getElementById('new_patient_age').value = '';
            document.getElementById('new_patient_gender').value = '';
            document.getElementById('new_service_name').value = '';
        });
}

// ===============================
// SUBMIT ADD NEW MEDICAL RECORD FORM
// ===============================
if (addMedicalRecordForm) {
    addMedicalRecordForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Show loading state on submit button
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

        fetch('ajax/add_medical_record.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Medical record created successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the medical record');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
}

    // ========================================================================
    // SECTION 16: MEDICAL RECORDS - UPDATE BUTTON (RENAMED FROM EDIT)
    // ========================================================================

    // ===============================
    // UPDATE BUTTON CLICK EVENT
    // ===============================
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-update-medrec') || e.target.closest('.btn-update-medrec')) {
            const btn = e.target.classList.contains('btn-update-medrec') ? e.target : e.target.closest('.btn-update-medrec');
            const row = btn.closest('tr');
            
            const dataAttr = row.getAttribute('data-medrec');
            
            if (!dataAttr) return;
            
            const data = JSON.parse(dataAttr);

            // Populate update modal
            const updateMedRecId = document.getElementById('update_med_rec_id');
            const updateMedRecIdDisplay = document.getElementById('update_med_rec_id_display');
            const updateApptId = document.getElementById('update_appt_id');
            const updateApptIdHidden = document.getElementById('update_appt_id_hidden');
            const updatePatientName = document.getElementById('update_patient_name');
            const updateService = document.getElementById('update_service');
            const updateDiagnosis = document.getElementById('update_diagnosis');
            const updatePrescription = document.getElementById('update_prescription');
            const updateVisitDate = document.getElementById('update_visit_date');

            if (updateMedRecId) updateMedRecId.value = data.MED_REC_ID;
            if (updateMedRecIdDisplay) updateMedRecIdDisplay.value = data.MED_REC_ID;
            if (updateApptId) updateApptId.value = data.APPT_ID;
            if (updateApptIdHidden) updateApptIdHidden.value = data.APPT_ID;
            if (updatePatientName) updatePatientName.value = `${data.PAT_FIRST_NAME} ${data.PAT_LAST_NAME}`;
            if (updateService) updateService.value = data.SERV_NAME || '-';
            if (updateDiagnosis) updateDiagnosis.value = data.MED_REC_DIAGNOSIS || '';
            if (updatePrescription) updatePrescription.value = data.MED_REC_PRESCRIPTION || '';
            if (updateVisitDate) updateVisitDate.value = data.MED_REC_VISIT_DATE || '';

            // Show modal
            const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
            updateModal.show();
        }
    });

    // ===============================
    // UPDATE FORM SUBMISSION
    // ===============================
    const medRecUpdateForm = document.getElementById('updateForm');
    if (medRecUpdateForm) {
        medRecUpdateForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(medRecUpdateForm);

            fetch('ajax/update_medical_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Medical record updated successfully!');
                    
                    // Close modal
                    const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateModal'));
                    updateModal.hide();
                    
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

});