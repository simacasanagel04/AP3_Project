// public/js/staff_payments_script.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Staff Payments Script Loaded');

    // Initialize Select2 for appointment dropdown
    if ($('#add_appt_id').length) {
        $('#add_appt_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Select or Search Appointment --',
            allowClear: true,
            ajax: {
                url: 'ajax/staff_payment.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'search_appointments',
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    console.log('Search results:', data);
                    if (data.success && data.appointments) {
                        return {
                            results: data.appointments.map(function(appt) {
                                return {
                                    id: appt.APPT_ID,
                                    text: appt.appt_display
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Load initial appointments on first open
        $('#add_appt_id').on('select2:open', function() {
            if ($('#add_appt_id option').length <= 1) {
                $.ajax({
                    url: 'ajax/staff_payment.php',
                    method: 'GET',
                    data: { action: 'get_all_appointments' },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Initial appointments:', response);
                        if (response.success && response.appointments && response.appointments.length > 0) {
                            response.appointments.forEach(function(appt) {
                                var option = new Option(appt.appt_display, appt.APPT_ID, false, false);
                                $('#add_appt_id').append(option);
                            });
                            $('#add_appt_id').trigger('change');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading appointments:', error);
                    }
                });
            }
        });
    }

    // Toggle Add Payment Form
    const addPaymentCard = document.getElementById('addPaymentCard');
    const addPaymentFormCard = document.getElementById('addPaymentFormCard');
    const cancelAddBtn = document.getElementById('cancelAddBtn');

    if (addPaymentCard) {
        addPaymentCard.addEventListener('click', function() {
            addPaymentFormCard.classList.remove('d-none');
            addPaymentFormCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', function() {
            addPaymentFormCard.classList.add('d-none');
            document.getElementById('addPaymentForm').reset();
            $('#add_appt_id').val(null).trigger('change');
            document.getElementById('appointmentDetails').classList.add('d-none');
            document.getElementById('submitPaymentBtn').disabled = true;
        });
    }

    // Clear appointment selection
    const clearApptBtn = document.getElementById('clearApptBtn');
    if (clearApptBtn) {
        clearApptBtn.addEventListener('click', function() {
            $('#add_appt_id').val(null).trigger('change');
            document.getElementById('appointmentDetails').classList.add('d-none');
            document.getElementById('submitPaymentBtn').disabled = true;
        });
    }

    // Load appointment details when selected
    $('#add_appt_id').on('change', function() {
        const apptId = $(this).val();
        console.log('Selected Appointment ID:', apptId);
        
        if (apptId) {
            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'GET',
                data: {
                    action: 'get_appointment_details',
                    appt_id: apptId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Appointment details response:', response);
                    if (response.success && response.details) {
                        // Populate appointment details
                        document.getElementById('add_patient_name').value = response.details.patient_name || 'N/A';
                        document.getElementById('add_service_name').value = response.details.serv_name || 'N/A';
                        document.getElementById('add_appt_date').value = response.details.formatted_appt_date || 'N/A';
                        document.getElementById('add_service_price').value = parseFloat(response.details.serv_price || 0).toFixed(2);

                        // Show appointment details
                        document.getElementById('appointmentDetails').classList.remove('d-none');
                        document.getElementById('submitPaymentBtn').disabled = false;

                        // Handle previous payments
                        if (response.previous_payments && response.previous_payments.length > 0) {
                            displayPreviousPayments(response.previous_payments);
                        } else {
                            document.getElementById('previousPaymentsAlert').style.display = 'none';
                        }
                    } else {
                        showAlert('error', response.message || 'Failed to load appointment details');
                        document.getElementById('appointmentDetails').classList.add('d-none');
                        document.getElementById('submitPaymentBtn').disabled = true;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading appointment details:', error);
                    showAlert('error', 'Error loading appointment details');
                    document.getElementById('submitPaymentBtn').disabled = true;
                }
            });
        } else {
            document.getElementById('appointmentDetails').classList.add('d-none');
            document.getElementById('submitPaymentBtn').disabled = true;
            document.getElementById('previousPaymentsAlert').style.display = 'none';
        }
    });

    // Display previous payments
    function displayPreviousPayments(payments) {
        const alertDiv = document.getElementById('previousPaymentsAlert');
        const listDiv = document.getElementById('previousPaymentsList');
        
        if (!payments || payments.length === 0) {
            alertDiv.style.display = 'none';
            return;
        }

        let html = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Payment ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        
        payments.forEach(function(payment) {
            const statusClass = payment.PYMT_STAT_NAME === 'Paid' ? 'success' : 
                               payment.PYMT_STAT_NAME === 'Pending' ? 'warning' : 
                               payment.PYMT_STAT_NAME === 'Refunded' ? 'secondary' : 'dark';
            html += `<tr>
                <td>${payment.PAYMT_ID}</td>
                <td>₱${parseFloat(payment.PAYMT_AMOUNT_PAID).toFixed(2)}</td>
                <td>${payment.PYMT_METH_NAME || 'N/A'}</td>
                <td><span class="badge bg-${statusClass}">${payment.PYMT_STAT_NAME}</span></td>
                <td>${payment.formatted_date || 'N/A'}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        listDiv.innerHTML = html;
        alertDiv.style.display = 'block';
    }

    // Payment status change handler - enable/disable date field
    const addPaymentStatus = document.getElementById('add_payment_status');
    if (addPaymentStatus) {
        addPaymentStatus.addEventListener('change', function() {
            const dateField = document.getElementById('add_payment_date');
            const statusText = this.options[this.selectedIndex].text;
            
            if (statusText === 'Paid') {
                dateField.disabled = false;
                dateField.required = true;
                if (!dateField.value) {
                    dateField.value = getCurrentDateTime();
                }
            } else {
                dateField.disabled = true;
                dateField.required = false;
                dateField.value = '';
            }
        });
    }

    // Update modal - same logic for status
    const updatePaymentStatus = document.getElementById('update_payment_status');
    if (updatePaymentStatus) {
        updatePaymentStatus.addEventListener('change', function() {
            const dateField = document.getElementById('update_payment_date');
            const statusText = this.options[this.selectedIndex].text;
            
            if (statusText === 'Paid') {
                dateField.disabled = false;
                dateField.required = true;
                if (!dateField.value) {
                    dateField.value = getCurrentDateTime();
                }
            } else {
                dateField.disabled = true;
                dateField.required = false;
                dateField.value = '';
            }
        });
    }

    // Add Payment Form Submission
    const addPaymentForm = document.getElementById('addPaymentForm');
    if (addPaymentForm) {
        addPaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add_payment');
            formData.append('appt_id', document.getElementById('add_appt_id').value);
            formData.append('amount', document.getElementById('add_amount').value);
            formData.append('payment_method', document.getElementById('add_payment_method').value);
            formData.append('payment_status', document.getElementById('add_payment_status').value);
            
            const dateField = document.getElementById('add_payment_date');
            const statusSelect = document.getElementById('add_payment_status');
            const statusText = statusSelect.options[statusSelect.selectedIndex].text;

            if (statusText === 'Paid' && dateField.value) {
                formData.append('payment_date', dateField.value);
            } else {
                formData.append('payment_date', getCurrentDateTime());
            }

            console.log('Submitting add payment:', Array.from(formData.entries()));

            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Add payment response:', response);
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', response.message || 'Failed to add payment');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    showAlert('error', 'Server error. Please try again.');
                }
            });
        });
    }

    // Update Payment Button Click
    document.querySelectorAll('.update-payment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const paymtId = this.dataset.id;
            console.log('Loading payment for update:', paymtId);
            
            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'GET',
                data: {
                    action: 'get_payment_details',
                    paymt_id: paymtId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Payment details for update:', response);
                    if (response.success && response.payment) {
                        const payment = response.payment;
                        
                        document.getElementById('update_paymt_id').value = payment.paymt_id;
                        document.getElementById('update_paymt_id_display').value = payment.paymt_id;
                        document.getElementById('update_appt_id').value = payment.app_id;
                        document.getElementById('update_appt_id_hidden').value = payment.app_id;
                        document.getElementById('update_amount').value = parseFloat(payment.paymt_amount_paid).toFixed(2);
                        document.getElementById('update_payment_method').value = payment.PYMT_METH_ID;
                        document.getElementById('update_payment_status').value = payment.PYMT_STAT_ID;
                        
                        if (payment.formatted_paymt_date) {
                            document.getElementById('update_payment_date').value = payment.formatted_paymt_date;
                        } else if (payment.paymt_date) {
                            const date = new Date(payment.paymt_date);
                            document.getElementById('update_payment_date').value = formatDateTimeLocal(date);
                        }
                        
                        // Trigger status change to handle date field
                        document.getElementById('update_payment_status').dispatchEvent(new Event('change'));
                        
                        const modal = new bootstrap.Modal(document.getElementById('updatePaymentModal'));
                        modal.show();
                    } else {
                        showAlert('error', response.message || 'Failed to load payment details');
                    }
                },
                error: function(xhr) {
                    console.error('Error loading payment details:', xhr.responseText);
                    showAlert('error', 'Error loading payment details');
                }
            });
        });
    });

    // Update Payment Form Submission
    const updatePaymentForm = document.getElementById('updatePaymentForm');
    if (updatePaymentForm) {
        updatePaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_payment');
            formData.append('paymt_id', document.getElementById('update_paymt_id').value);
            formData.append('appt_id', document.getElementById('update_appt_id_hidden').value);
            formData.append('amount', document.getElementById('update_amount').value);
            formData.append('payment_method', document.getElementById('update_payment_method').value);
            formData.append('payment_status', document.getElementById('update_payment_status').value);
            
            const dateField = document.getElementById('update_payment_date');
            const statusSelect = document.getElementById('update_payment_status');
            const statusText = statusSelect.options[statusSelect.selectedIndex].text;

            if (statusText === 'Paid' && dateField.value) {
                formData.append('payment_date', dateField.value);
            } else {
                formData.append('payment_date', getCurrentDateTime());
            }

            console.log('Submitting update payment:', Array.from(formData.entries()));

            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    console.log('Update payment response:', response);
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('error', response.message || 'Failed to update payment');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    showAlert('error', 'Error updating payment');
                }
            });
        });
    }

    // ========== PAYMENT METHOD PAGE FUNCTIONALITY ==========
    
    // Open Add Modal
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    if (openAddModalBtn) {
        openAddModalBtn.addEventListener('click', function() {
            const now = new Date();
            const formatted = now.toLocaleString('en-US', { 
                month: 'long', 
                day: 'numeric', 
                year: 'numeric', 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            const createdAtField = document.getElementById('add_created_at');
            if (createdAtField) {
                createdAtField.value = formatted;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('addModal'));
            modal.show();
        });
    }
    
    // Edit button for payment methods/statuses
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            const editMethodId = document.getElementById('edit_method_id');
            const editStatusId = document.getElementById('edit_status_id');
            
            if (editMethodId) {
                editMethodId.value = id;
                document.getElementById('edit_method_name').value = name;
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            } else if (editStatusId) {
                editStatusId.value = id;
                document.getElementById('edit_status_name').value = name;
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Utility Functions
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;
        
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';
        
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="bi bi-${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alertHTML;
        
        setTimeout(function() {
            const alert = alertContainer.querySelector('.alert');
            if (alert) alert.remove();
        }, 5000);
    }

    function getCurrentDateTime() {
        const now = new Date();
        return formatDateTimeLocal(now);
    }

    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }
});