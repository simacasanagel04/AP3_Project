// Staff Payments Management Script
// public/js/staff_payments_script.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize
    loadPaymentMethods();
    loadPaymentStatuses();
    
    // Add Payment Card Click
    const addPaymentCard = document.getElementById('addPaymentCard');
    const addPaymentFormCard = document.getElementById('addPaymentFormCard');
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    
    if (addPaymentCard) {
        addPaymentCard.addEventListener('click', function() {
            addPaymentFormCard.classList.remove('d-none');
            addPaymentFormCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }
    
    if (cancelAddBtn) {
        cancelAddBtn.addEventListener('click', function() {
            addPaymentFormCard.classList.add('d-none');
            document.getElementById('addPaymentForm').reset();
        });
    }
    
    // Load Appointment Details on Appointment ID Input
    const addApptIdInput = document.getElementById('add_appt_id');
    if (addApptIdInput) {
        addApptIdInput.addEventListener('blur', function() {
            const apptId = this.value.trim();
            if (apptId) {
                loadAppointmentDetails(apptId, 'add');
            }
        });
    }
    
    // Add Payment Form Submit
    const addPaymentForm = document.getElementById('addPaymentForm');
    if (addPaymentForm) {
        addPaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitAddPayment();
        });
    }
    
    // Update Payment Buttons
    const updateButtons = document.querySelectorAll('.update-payment-btn');
    updateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const paymtId = this.dataset.id;
            const apptId = this.dataset.appt;
            const patientName = this.dataset.patient;
            const amount = this.dataset.amount;
            const date = this.dataset.date;
            
            openUpdateModal(paymtId, apptId, patientName, amount, date);
        });
    });
    
    // Update Payment Form Submit
    const updatePaymentForm = document.getElementById('updatePaymentForm');
    if (updatePaymentForm) {
        updatePaymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitUpdatePayment();
        });
    }
    
    // Set current datetime for add form
    const addPaymentDate = document.getElementById('add_payment_date');
    if (addPaymentDate) {
        const now = new Date();
        const datetime = now.toISOString().slice(0, 16);
        addPaymentDate.value = datetime;
    }
});

// Load Payment Methods
function loadPaymentMethods() {
    fetch('ajax/staff_get_payment_meth.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populatePaymentMethodDropdowns(data.methods);
            }
        })
        .catch(error => console.error('Error loading payment methods:', error));
}

// Load Payment Statuses
function loadPaymentStatuses() {
    fetch('ajax/get_payment_statuses.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populatePaymentStatusDropdowns(data.statuses);
            }
        })
        .catch(error => console.error('Error loading payment statuses:', error));
}

// Populate Payment Method Dropdowns
function populatePaymentMethodDropdowns(methods) {
    const addSelect = document.getElementById('add_payment_method');
    const updateSelect = document.getElementById('update_payment_method');
    
    methods.forEach(method => {
        if (addSelect) {
            const option = new Option(method.PYMT_METH_NAME, method.PYMT_METH_ID);
            addSelect.add(option);
        }
        if (updateSelect) {
            const option = new Option(method.PYMT_METH_NAME, method.PYMT_METH_ID);
            updateSelect.add(option);
        }
    });
}

// Populate Payment Status Dropdowns
function populatePaymentStatusDropdowns(statuses) {
    const addSelect = document.getElementById('add_payment_status');
    const updateSelect = document.getElementById('update_payment_status');
    
    statuses.forEach(status => {
        if (addSelect) {
            const option = new Option(status.PYMT_STAT_NAME, status.PYMT_STAT_ID);
            addSelect.add(option);
        }
        if (updateSelect) {
            const option = new Option(status.PYMT_STAT_NAME, status.PYMT_STAT_ID);
            updateSelect.add(option);
        }
    });
}

// Load Appointment Details
function loadAppointmentDetails(apptId, formType) {
    fetch(`ajax/get_appointment_details.php?appt_id=${apptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const prefix = formType === 'add' ? 'add' : 'update';
                
                document.getElementById(`${prefix}_patient_name`).value = data.patient_name || '';
                document.getElementById(`${prefix}_amount`).value = data.service_price || '';
                
                if (data.payment_method_id) {
                    document.getElementById(`${prefix}_payment_method`).value = data.payment_method_id;
                }
                if (data.payment_status_id) {
                    document.getElementById(`${prefix}_payment_status`).value = data.payment_status_id;
                }
            } else {
                showAlert('Appointment not found or invalid', 'danger');
                if (formType === 'add') {
                    document.getElementById('add_patient_name').value = '';
                    document.getElementById('add_amount').value = '';
                }
            }
        })
        .catch(error => {
            console.error('Error loading appointment details:', error);
            showAlert('Error loading appointment details', 'danger');
        });
}

// Submit Add Payment
function submitAddPayment() {
    const formData = new FormData(document.getElementById('addPaymentForm'));
    
    fetch('ajax/add_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Payment record added successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message || 'Error adding payment record', 'danger');
        }
    })
    .catch(error => {
        console.error('Error adding payment:', error);
        showAlert('Error adding payment record', 'danger');
    });
}

// Open Update Modal
function openUpdateModal(paymtId, apptId, patientName, amount, date) {
    // Set read-only fields
    document.getElementById('update_paymt_id').value = paymtId;
    document.getElementById('update_paymt_id_display').value = paymtId;
    document.getElementById('update_appt_id').value = apptId;
    document.getElementById('update_patient_name').value = patientName;
    
    // Set editable fields
    document.getElementById('update_amount').value = amount;
    document.getElementById('update_payment_date').value = date;
    
    // Load current payment method and status
    fetch(`ajax/get_payment_details.php?paymt_id=${paymtId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('update_payment_method').value = data.pymt_meth_id;
                document.getElementById('update_payment_status').value = data.pymt_stat_id;
            }
        })
        .catch(error => console.error('Error loading payment details:', error));
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('updatePaymentModal'));
    modal.show();
}

// Submit Update Payment
function submitUpdatePayment() {
    const formData = new FormData(document.getElementById('updatePaymentForm'));
    
    fetch('ajax/update_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Payment record updated successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message || 'Error updating payment record', 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating payment:', error);
        showAlert('Error updating payment record', 'danger');
    });
}

// Show Alert
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert_' + Date.now();
    
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }
    }, 5000);
}