// public/js/staff_payments_script.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for appointment dropdown
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
                    search: params.term
                };
            },
            processResults: function(data) {
                if (data.success) {
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

    // Load initial appointments on dropdown open
    $('#add_appt_id').on('select2:open', function() {
        if ($('#add_appt_id option').length <= 1) {
            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'GET',
                data: { action: 'get_all_appointments' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.appointments.length > 0) {
                        response.appointments.forEach(function(appt) {
                            var option = new Option(appt.appt_display, appt.APPT_ID, false, false);
                            $('#add_appt_id').append(option);
                        });
                    }
                }
            });
        }
    });

    // Toggle Add Payment Form
    const addPaymentCard = document.getElementById('addPaymentCard');
    const addPaymentFormCard = document.getElementById('addPaymentFormCard');
    const cancelAddBtn = document.getElementById('cancelAddBtn');

    if (addPaymentCard) {
        addPaymentCard.addEventListener('click', function() {
            addPaymentFormCard.classList.remove('d-none');
            addPaymentCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
    document.getElementById('clearApptBtn')?.addEventListener('click', function() {
        $('#add_appt_id').val(null).trigger('change');
        document.getElementById('appointmentDetails').classList.add('d-none');
        document.getElementById('submitPaymentBtn').disabled = true;
    });

    // Load appointment details when selected
    $('#add_appt_id').on('change', function() {
        const apptId = $(this).val();
        
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
                    if (response.success) {
                        // Populate appointment details
                        document.getElementById('add_patient_name').value = response.details.patient_name || 'N/A';
                        document.getElementById('add_service_name').value = response.details.SERV_NAME || 'N/A';
                        document.getElementById('add_appt_date').value = formatDate(response.details.APPT_DATE) || 'N/A';
                        document.getElementById('add_service_price').value = parseFloat(response.details.SERV_PRICE || 0).toFixed(2);

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
                    }
                },
                error: function() {
                    showAlert('error', 'Error loading appointment details');
                }
            });
        } else {
            document.getElementById('appointmentDetails').classList.add('d-none');
            document.getElementById('submitPaymentBtn').disabled = true;
        }
    });

    // Display previous payments
    function displayPreviousPayments(payments) {
        const alertDiv = document.getElementById('previousPaymentsAlert');
        const listDiv = document.getElementById('previousPaymentsList');
        
        let html = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Payment ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        
        payments.forEach(function(payment) {
            html += `<tr>
                <td>${payment.PAYMT_ID}</td>
                <td>₱${parseFloat(payment.PAYMT_AMOUNT_PAID).toFixed(2)}</td>
                <td>${payment.PYMT_METH_NAME}</td>
                <td><span class="badge bg-${payment.PYMT_STAT_NAME === 'Paid' ? 'success' : 'warning'}">${payment.PYMT_STAT_NAME}</span></td>
                <td>${payment.formatted_date}</td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        listDiv.innerHTML = html;
        alertDiv.style.display = 'block';
    }

    // Payment status change handler - enable/disable date field
    document.getElementById('add_payment_status')?.addEventListener('change', function() {
        const dateField = document.getElementById('add_payment_date');
        const statusText = this.options[this.selectedIndex].text;
        
        if (statusText === 'Paid') {
            dateField.disabled = false;
            dateField.required = true;
            // Set current datetime if empty
            if (!dateField.value) {
                dateField.value = getCurrentDateTime();
            }
        } else {
            dateField.disabled = true;
            dateField.required = false;
            dateField.value = '';
        }
    });

    // Update modal - same logic for status
    document.getElementById('update_payment_status')?.addEventListener('change', function() {
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

    // Add Payment Form Submission
    document.getElementById('addPaymentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'add_payment');
        formData.append('appt_id', document.getElementById('add_appt_id').value);
        formData.append('amount', document.getElementById('add_amount').value);
        formData.append('payment_method', document.getElementById('add_payment_method').value);
        formData.append('payment_status', document.getElementById('add_payment_status').value);
        
        const dateField = document.getElementById('add_payment_date');
        if (!dateField.disabled && dateField.value) {
            formData.append('payment_date', dateField.value);
        } else {
            formData.append('payment_date', getCurrentDateTime());
        }

        $.ajax({
            url: 'ajax/staff_payment.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('error', response.message || 'Failed to add payment');
                }
            },
            error: function() {
                showAlert('error', 'Error processing payment');
            }
        });
    });

    // Update Payment Button Click
    document.querySelectorAll('.update-payment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const paymtId = this.dataset.id;
            
            $.ajax({
                url: 'ajax/staff_payment.php',
                method: 'GET',
                data: {
                    action: 'get_payment_details',
                    paymt_id: paymtId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const payment = response.payment;
                        
                        document.getElementById('update_paymt_id').value = payment.PAYMT_ID;
                        document.getElementById('update_paymt_id_display').value = payment.PAYMT_ID;
                        document.getElementById('update_appt_id').value = payment.APPT_ID;
                        document.getElementById('update_amount').value = parseFloat(payment.PAYMT_AMOUNT_PAID).toFixed(2);
                        document.getElementById('update_payment_method').value = payment.PYMT_METH_ID;
                        document.getElementById('update_payment_status').value = payment.PYMT_STAT_ID;
                        
                        if (payment.PAYMT_DATE) {
                            const date = new Date(payment.PAYMT_DATE);
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
                error: function() {
                    showAlert('error', 'Error loading payment details');
                }
            });
        });
    });

    // Update Payment Form Submission
    document.getElementById('updatePaymentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'update_payment');
        formData.append('paymt_id', document.getElementById('update_paymt_id').value);
        formData.append('amount', document.getElementById('update_amount').value);
        formData.append('payment_method', document.getElementById('update_payment_method').value);
        formData.append('payment_status', document.getElementById('update_payment_status').value);
        
        const dateField = document.getElementById('update_payment_date');
        if (!dateField.disabled && dateField.value) {
            formData.append('payment_date', dateField.value);
        } else {
            formData.append('payment_date', getCurrentDateTime());
        }

        $.ajax({
            url: 'ajax/staff_payment.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('error', response.message || 'Failed to update payment');
                }
            },
            error: function() {
                showAlert('error', 'Error updating payment');
            }
        });
    });

    // ========== PAYMENT METHOD PAGE FUNCTIONALITY ==========
    
    // Open Add Modal
    document.getElementById('openAddModalBtn')?.addEventListener('click', function() {
        // Set current date/time in the Created At field
        const now = new Date();
        const formatted = now.toLocaleString('en-US', { 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric', 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        document.getElementById('add_created_at').value = formatted;
        
        const modal = new bootstrap.Modal(document.getElementById('addModal'));
        modal.show();
    });
    
    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            document.getElementById('edit_method_id').value = id;
            document.getElementById('edit_method_name').value = name;
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Utility Functions
    function showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer');
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
            if (alert) {
                alert.remove();
            }
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