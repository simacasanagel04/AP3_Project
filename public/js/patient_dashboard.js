document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    // Toggle Sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('hidden');
        toggleBtn.classList.toggle('open');
    }
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    // Responsive
    function checkScreen() {
        if (window.innerWidth <= 992) {
            if (sidebar) sidebar.classList.add('hidden');
            if (toggleBtn) {
                toggleBtn.classList.add('open');
                toggleBtn.style.display = 'flex';
            }
        } else {
            if (sidebar) sidebar.classList.remove('hidden');
            if (toggleBtn) {
                toggleBtn.classList.remove('open');
                toggleBtn.style.display = 'none';
            }
        }
    }
    checkScreen();
    window.addEventListener('resize', checkScreen);

    // Active Nav Link
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('active');
        }
    });

    // Live Clock
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
    // APPOINTMENT BOOKING FUNCTIONALITY
    // ===============================

    const bookTab = document.getElementById('bookTab');
    const historyTab = document.getElementById('historyTab');
    const bookSection = document.getElementById('bookSection');
    const historySection = document.getElementById('historySection');
    const paymentSection = document.getElementById('paymentSection');

    // Tab switching
    if (bookTab && historyTab) {
        bookTab.addEventListener('click', function() {
            bookTab.classList.add('active');
            historyTab.classList.remove('active');
            bookSection.style.display = 'block';
            historySection.style.display = 'none';
            paymentSection.style.display = 'none';
        });

        historyTab.addEventListener('click', function() {
            historyTab.classList.add('active');
            bookTab.classList.remove('active');
            historySection.style.display = 'block';
            bookSection.style.display = 'none';
            paymentSection.style.display = 'none';
        });
    }

    // Form elements
    const departmentSelect = document.getElementById('department');
    const serviceSelect = document.getElementById('service');
    const servicePriceDiv = document.getElementById('servicePrice');
    const dateInput = document.getElementById('date');
    const timeSelect = document.getElementById('time');
    const apptForm = document.getElementById('apptForm');
    const clearBtn = document.getElementById('clearBtn');

    let availableDates = [];
    let selectedServicePrice = 0;

    // 1. Department change - Load services
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            const specId = this.value;
            
            // Reset dependent fields
            serviceSelect.innerHTML = '<option value="">-- Loading... --</option>';
            serviceSelect.disabled = true;
            servicePriceDiv.textContent = '₱0.00';
            dateInput.disabled = true;
            dateInput.value = '';
            timeSelect.innerHTML = '<option value="">-- Select Date First --</option>';
            timeSelect.disabled = true;

            if (!specId) {
                serviceSelect.innerHTML = '<option value="">-- Select Department First --</option>';
                return;
            }

            // Fetch services by specialization
            fetch('../ajax/patient_get_serv_by_spec.php?spec_id=' + specId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
                        data.services.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.SERV_ID;
                            option.textContent = service.SERV_NAME + ' - ₱' + parseFloat(service.SERV_PRICE).toFixed(2);
                            option.dataset.price = service.SERV_PRICE;
                            serviceSelect.appendChild(option);
                        });
                        serviceSelect.disabled = false;
                    } else {
                        serviceSelect.innerHTML = '<option value="">-- No Services Available --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching services:', error);
                    serviceSelect.innerHTML = '<option value="">-- Error Loading Services --</option>';
                });

            // Fetch available dates
            fetch('../ajax/patient_get_avail_dates.php?spec_id=' + specId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        availableDates = data.dates;
                        dateInput.disabled = false;
                        
                        // Set min and max date
                        const today = new Date().toISOString().split('T')[0];
                        const maxDate = new Date();
                        maxDate.setDate(maxDate.getDate() + 30);
                        dateInput.min = today;
                        dateInput.max = maxDate.toISOString().split('T')[0];
                        
                        document.getElementById('dateNote').textContent = 
                            availableDates.length > 0 
                            ? `${availableDates.length} dates available in the next 30 days` 
                            : 'No available dates found';
                    }
                })
                .catch(error => {
                    console.error('Error fetching dates:', error);
                });
        });
    }

    // 2. Service change - Update price
    if (serviceSelect) {
        serviceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.price) {
                const price = parseFloat(selectedOption.dataset.price);
                selectedServicePrice = price;
                servicePriceDiv.textContent = '₱' + price.toFixed(2);
            } else {
                selectedServicePrice = 0;
                servicePriceDiv.textContent = '₱0.00';
            }
        });
    }

    // 3. Date change - Load available time slots
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            const specId = departmentSelect.value;

            timeSelect.innerHTML = '<option value="">-- Loading... --</option>';
            timeSelect.disabled = true;

            if (!selectedDate || !specId) return;

            // Check if date is available
            const dateAvailable = availableDates.some(d => d === selectedDate);
            if (!dateAvailable) {
                alert('Selected date is not available. Please choose another date.');
                this.value = '';
                return;
            }

            // Fetch available time slots
            fetch(`../ajax/patient_get_avail_times.php?spec_id=${specId}&date=${selectedDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.timeSlots.length > 0) {
                        timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
                        data.timeSlots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.formatted;
                            option.dataset.doctorId = slot.doctor_id;
                            timeSelect.appendChild(option);
                        });
                        timeSelect.disabled = false;
                    } else {
                        timeSelect.innerHTML = '<option value="">-- No Time Slots Available --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching time slots:', error);
                    timeSelect.innerHTML = '<option value="">-- Error Loading Times --</option>';
                });
        });
    }

    // 4. Time selection - Store doctor ID
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.doctorId) {
                document.getElementById('selectedDoctorId').value = selectedOption.dataset.doctorId;
            }
        });
    }

    // 5. Clear button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            apptForm.reset();
            serviceSelect.innerHTML = '<option value="">-- Select Department First --</option>';
            serviceSelect.disabled = true;
            servicePriceDiv.textContent = '₱0.00';
            dateInput.disabled = true;
            timeSelect.innerHTML = '<option value="">-- Select Date First --</option>';
            timeSelect.disabled = true;
            document.getElementById('selectedDoctorId').value = '';
        });
    }

    // 6. Form submission - Show payment section
    if (apptForm) {
        apptForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const serviceName = serviceSelect.options[serviceSelect.selectedIndex].text.split(' - ')[0];
            
            // Update payment summary
            document.getElementById('summaryService').textContent = serviceName;
            document.getElementById('summaryPrice').textContent = '₱' + selectedServicePrice.toFixed(2);

            // Show payment section
            paymentSection.style.display = 'block';
            paymentSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // ===============================
    // PAYMENT FUNCTIONALITY
    // ===============================

    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    const paymentCardContainer = document.getElementById('paymentCardContainer');
    const bookBtn = document.getElementById('bookBtn');

    let selectedPaymentMethod = null;

    // Payment method selection
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            selectedPaymentMethod = this.value;
            const methodName = this.options[this.selectedIndex].text;

            if (!selectedPaymentMethod) {
                paymentCardContainer.innerHTML = '';
                bookBtn.disabled = true;
                return;
            }

            // Generate payment confirmation card based on method
            let cardHTML = '';

            switch(methodName.toLowerCase()) {
                case 'cash':
                    cardHTML = `
                        <div class="card-form active">
                            <h5><i class="bi bi-cash-coin"></i> Cash Payment</h5>
                            <div class="alert alert-info mt-3">
                                <strong>Payment Instructions:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Pay at the clinic upon arrival</li>
                                    <li>Bring exact amount if possible</li>
                                    <li>Payment status will be marked as PENDING until confirmed by staff</li>
                                </ul>
                            </div>
                             <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="confirmCash" required>
                                        <label class="form-check-label" for="confirmCash">
                                            I understand that I need to pay ₱${selectedServicePrice.toFixed(2)} in cash at the clinic
                                        </label>
                            </div>
                            </div>
                            `;
                            break;
                case 'debit card':
                cardHTML = `
                    <div class="card-form active">
                        <h5><i class="bi bi-credit-card"></i> Debit Card Payment</h5>
                        <p class="text-muted">Enter your debit card details (For simulation only - No actual charges)</p>
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="debitCardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="debitExpiry" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" id="debitCVV" placeholder="123" maxlength="3" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="debitCardName" placeholder="JOHN DOE" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDebit" required>
                            <label class="form-check-label" for="confirmDebit">
                                I confirm the card details are correct (Simulation only)
                            </label>
                        </div>
                    </div>
                `;
                break;

            case 'credit card':
                cardHTML = `
                    <div class="card-form active">
                        <h5><i class="bi bi-credit-card-2-front"></i> Credit Card Payment</h5>
                        <p class="text-muted">Enter your credit card details (For simulation only - No actual charges)</p>
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="creditCardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                            <small class="text-muted">We accept Visa, MasterCard, American Express</small>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="creditExpiry" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" id="creditCVV" placeholder="123" maxlength="4" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="creditCardName" placeholder="JOHN DOE" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmCredit" required>
                            <label class="form-check-label" for="confirmCredit">
                                I confirm the card details are correct (Simulation only)
                            </label>
                        </div>
                    </div>
                `;
                break;

            case 'bank transfer':
                cardHTML = `
                    <div class="card-form active">
                        <h5><i class="bi bi-bank"></i> Bank Transfer</h5>
                        <div class="alert alert-warning mt-3">
                            <strong>Bank Transfer Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Bank Name:</strong> AKSyon Bank</li>
                                <li><strong>Account Name:</strong> AKSyon Medical Center</li>
                                <li><strong>Account Number:</strong> 1234-5678-9012</li>
                                <li><strong>Amount:</strong> ₱${selectedServicePrice.toFixed(2)}</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="bankRefNumber" placeholder="Enter transfer reference number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transfer Date</label>
                            <input type="date" class="form-control" id="bankTransferDate" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmBank" required>
                            <label class="form-check-label" for="confirmBank">
                                I confirm that I have completed the bank transfer (Simulation only)
                            </label>
                        </div>
                    </div>
                `;
                break;

            case 'mobile payment':
                cardHTML = `
                    <div class="card-form active">
                        <h5><i class="bi bi-phone"></i> Mobile Payment</h5>
                        <div class="alert alert-info mt-3">
                            <strong>Supported Mobile Payment Platforms:</strong>
                            <p class="mb-0 mt-2">GCash, PayMaya, GrabPay</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Platform</label>
                            <select class="form-select" id="mobilePlatform" required>
                                <option value="">-- Select Platform --</option>
                                <option value="GCash">GCash</option>
                                <option value="PayMaya">PayMaya</option>
                                <option value="GrabPay">GrabPay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobileNumber" placeholder="09XX XXX XXXX" maxlength="11" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" id="mobileRefNumber" placeholder="Enter transaction reference" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmMobile" required>
                            <label class="form-check-label" for="confirmMobile">
                                I confirm the mobile payment details (Simulation only)
                            </label>
                        </div>
                    </div>
                `;
                break;

            default:
                cardHTML = `
                    <div class="card-form active">
                        <div class="alert alert-warning">
                            <strong>Payment method details not configured</strong>
                        </div>
                    </div>
                `;
        }

        paymentCardContainer.innerHTML = cardHTML;
        bookBtn.disabled = false;

        // Auto-format card numbers
        formatCardInputs();
    });
}

// Book appointment button
if (bookBtn) {
    bookBtn.addEventListener('click', function() {
        if (!validatePaymentForm()) {
            alert('Please fill in all required payment fields');
            return;
        }

        // Prepare appointment data
        const appointmentData = {
            pat_id: document.getElementById('patientId').value,
            doc_id: document.getElementById('selectedDoctorId').value,
            serv_id: serviceSelect.value,
            appt_date: dateInput.value,
            appt_time: timeSelect.value,
            stat_id: 1, // Scheduled
            pymt_meth_id: selectedPaymentMethod,
            pymt_amount: selectedServicePrice,
            pymt_stat_id: 2 // Pending
        };

        // Show loading state
        bookBtn.disabled = true;
        bookBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Booking...';

        // Submit appointment
        fetch('../ajax/patient_book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(appointmentData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Appointment booked successfully! You will be redirected to your dashboard.');
                window.location.href = 'patient_dashb.php';
            } else {
                alert('✗ Error: ' + (data.message || 'Failed to book appointment'));
                bookBtn.disabled = false;
                bookBtn.innerHTML = 'BOOK APPOINTMENT';
            }
        })
        .catch(error => {
            console.error('Error booking appointment:', error);
            alert('✗ An error occurred while booking the appointment');
            bookBtn.disabled = false;
            bookBtn.innerHTML = 'BOOK APPOINTMENT';
        });
    });
}

// Validate payment form based on selected method
function validatePaymentForm() {
    const methodName = paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text.toLowerCase();

    switch(methodName) {
        case 'cash':
            return document.getElementById('confirmCash')?.checked;
        
        case 'debit card':
            return document.getElementById('debitCardNumber')?.value &&
                   document.getElementById('debitExpiry')?.value &&
                   document.getElementById('debitCVV')?.value &&
                   document.getElementById('debitCardName')?.value &&
                   document.getElementById('confirmDebit')?.checked;
        
        case 'credit card':
            return document.getElementById('creditCardNumber')?.value &&
                   document.getElementById('creditExpiry')?.value &&
                   document.getElementById('creditCVV')?.value &&
                   document.getElementById('creditCardName')?.value &&
                   document.getElementById('confirmCredit')?.checked;
        
        case 'bank transfer':
            return document.getElementById('bankRefNumber')?.value &&
                   document.getElementById('bankTransferDate')?.value &&
                   document.getElementById('confirmBank')?.checked;
        
        case 'mobile payment':
            return document.getElementById('mobilePlatform')?.value &&
                   document.getElementById('mobileNumber')?.value &&
                   document.getElementById('mobileRefNumber')?.value &&
                   document.getElementById('confirmMobile')?.checked;
        
        default:
            return false;
    }
}

// Format card number inputs
function formatCardInputs() {
    const cardInputs = [
        document.getElementById('debitCardNumber'),
        document.getElementById('creditCardNumber')
    ];

    cardInputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            });
        }
    });

    // Format expiry date
    const expiryInputs = [
        document.getElementById('debitExpiry'),
        document.getElementById('creditExpiry')
    ];

    expiryInputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;
            });
        }
    });

    // Format mobile number
    const mobileInput = document.getElementById('mobileNumber');
    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value.slice(0, 11);
        });
    }
}
});
