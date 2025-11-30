// public/js/patient_dashboard.js

document.addEventListener('DOMContentLoaded', function () {

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
        fetch('ajax/patient_get_serv_by_spec.php?spec_id=' + specId)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                return response.text(); // Get as text first to see raw response
            })
            .then(text => {
                console.log('Raw response:', text);
                const data = JSON.parse(text); // Parse manually
                console.log('Parsed data:', data);
                
                if (data.success) {
                    serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
                    if (data.services && data.services.length > 0) {
                        data.services.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.serv_id;
                            option.textContent = service.serv_name + ' - ₱' + parseFloat(service.serv_price).toFixed(2);
                            option.dataset.price = service.serv_price;
                            serviceSelect.appendChild(option);
                        });
                        serviceSelect.disabled = false;
                    } else {
                        serviceSelect.innerHTML = '<option value="">-- No Services Available --</option>';
                    }
                } else {
                    console.error('API returned error:', data.message);
                    serviceSelect.innerHTML = '<option value="">-- No Services Available --</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching services:', error);
                serviceSelect.innerHTML = '<option value="">-- Error Loading Services --</option>';
            });

            // Fetch available dates
            fetch('ajax/patient_get_avail_dates.php?spec_id=' + specId)
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

            // ========================================
            // FIX 1: BLOCK SUNDAYS (Day 0)
            // ========================================
            const selectedDay = new Date(selectedDate + 'T00:00:00');
            const dayOfWeek = selectedDay.getDay();

            if (dayOfWeek === 0) {
                alert('⚠️ CLOSED ON SUNDAYS\n\nOur clinic operates Monday-Saturday only.\nMonday-Friday: 8:00 AM - 6:00 PM\nSaturday: 9:00 AM - 5:00 PM\n\nPlease select another date.');
                this.value = '';
                timeSelect.innerHTML = '<option value="">-- Select Date First --</option>';
                timeSelect.disabled = true;
                
                // Update note
                const noteEl = document.getElementById('dateNote');
                if (noteEl) {
                    noteEl.textContent = '⚠️ Sundays are closed. Please select Monday-Saturday.';
                    noteEl.style.color = '#dc3545';
                }
                return;
            }

            // Reset note color
            const noteEl = document.getElementById('dateNote');
            if (noteEl) {
                noteEl.style.color = '';
            }

            timeSelect.innerHTML = '<option value="">-- Loading... --</option>';
            timeSelect.disabled = true;

            if (!selectedDate || !specId) return;

            // Check if date is available
            const dateAvailable = availableDates.some(d => d === selectedDate);
            if (!dateAvailable) {
                alert('Selected date is not available. Please choose another date.');
                this.value = '';
                timeSelect.innerHTML = '<option value="">-- Select Date First --</option>';
                return;
            }

            // Fetch available time slots
            fetch(`ajax/patient_get_avail_times.php?spec_id=${specId}&date=${selectedDate}${currentApptId ? '&current_appt_id=' + currentApptId : ''}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.timeSlots.length > 0) {
                        timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
                        data.timeSlots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.formatted + ' (Dr. ' + slot.doctor_name.split(',')[0] + ')';
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
            selectedServicePrice = 0;
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

    // ============================================================================
    // PROFESSIONAL MODAL-BASED PAYMENT SYSTEM
    // ============================================================================

    // ========================================================================
    // ELEMENT REFERENCES
    // ========================================================================
    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    const proceedPaymentBtn = document.getElementById('proceedPaymentBtn');
    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    const paymentModalElement = document.getElementById('paymentModal');
    const paymentFormContainer = document.getElementById('paymentFormContainer');
    const paymentIcon = document.getElementById('paymentIcon');
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentModalTitle = document.getElementById('paymentModalTitle');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const bookBtn = document.getElementById('bookBtn');

    let paymentModal = null;
    if (paymentModalElement) {
        paymentModal = new bootstrap.Modal(paymentModalElement);
    }

    let selectedPaymentMethod = null;
    let selectedPaymentMethodName = null;

    // ========================================================================
    // PAYMENT METHOD SELECTION
    // ========================================================================
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            selectedPaymentMethod = this.value;
            selectedPaymentMethodName = this.options[this.selectedIndex].text;

            if (selectedPaymentMethod) {
                proceedPaymentBtn.disabled = false;
            } else {
                proceedPaymentBtn.disabled = true;
            }
        });
    }

    // ========================================================================
    // PROCEED TO PAYMENT BUTTON
    // ========================================================================
    if (proceedPaymentBtn) {
        proceedPaymentBtn.addEventListener('click', function() {
            if (!selectedPaymentMethod) {
                alert('Please select a payment method');
                return;
            }

            // Get price from summary
            const priceText = document.getElementById('summaryPrice').textContent;
            selectedServicePrice = parseFloat(priceText.replace('₱', '').replace(',', ''));

            // Update modal with payment method details
            updatePaymentModal(selectedPaymentMethod, selectedPaymentMethodName, selectedServicePrice);

            // Show modal
            if (paymentModal) {
                paymentModal.show();
            }
        });
    }

    // ========================================================================
    // CANCEL PAYMENT
    // ========================================================================
    if (cancelPaymentBtn) {
        cancelPaymentBtn.addEventListener('click', function() {
            // Reset payment section
            paymentMethodSelect.value = '';
            proceedPaymentBtn.disabled = true;
            selectedPaymentMethod = null;
            selectedPaymentMethodName = null;
            
            // Go back to appointment selection
            historyTab.classList.add('active');
            bookTab.classList.remove('active');
            historySection.style.display = 'block';
            bookSection.style.display = 'none';
            paymentSection.style.display = 'none';
        });
    }

    // ========================================================================
    // UPDATE PAYMENT MODAL BASED ON SELECTED METHOD
    // ========================================================================
    function updatePaymentModal(methodId, methodName, price) {
        // Update title and amount
        paymentModalTitle.textContent = methodName;
        paymentAmount.textContent = '₱' + price.toFixed(2);

        // Clear container
        paymentFormContainer.innerHTML = '';

        // Update icon based on payment method
        const lowerMethodName = methodName.toLowerCase();
        updatePaymentIcon(lowerMethodName);

        // Generate form based on payment method
        let formHTML = '';

        if (lowerMethodName.includes('cash')) {
            formHTML = generateCashForm(price);
        } else if (lowerMethodName.includes('debit')) {
            formHTML = generateCardForm('debit', price);
        } else if (lowerMethodName.includes('credit')) {
            formHTML = generateCardForm('credit', price);
        } else if (lowerMethodName.includes('bank')) {
            formHTML = generateBankTransferForm(price);
        } else if (lowerMethodName.includes('mobile') || lowerMethodName.includes('gcash') || lowerMethodName.includes('paymaya')) {
            formHTML = generateMobilePaymentForm(price, methodName);
        } else {
            formHTML = generateDefaultPaymentForm(price, methodName);
        }

        paymentFormContainer.innerHTML = formHTML;
        
        // Attach event listeners for form inputs
        attachFormListeners();
    }

    // ========================================================================
    // PAYMENT ICON UPDATER
    // ========================================================================
    function updatePaymentIcon(methodName) {
        const iconMap = {
            'cash': 'bi-cash-coin',
            'debit': 'bi-credit-card',
            'credit': 'bi-credit-card-2-front',
            'bank': 'bi-bank2',
            'mobile': 'bi-phone',
            'gcash': 'bi-wallet2',
            'paymaya': 'bi-wallet2'
        };

        let iconClass = 'bi-credit-card';
        for (const [key, icon] of Object.entries(iconMap)) {
            if (methodName.includes(key)) {
                iconClass = icon;
                break;
            }
        }

        if (paymentIcon) {
            paymentIcon.className = `bi ${iconClass}`;
        }
    }

    // ========================================================================
    // PAYMENT FORM GENERATORS
    // ========================================================================

    /**
     * CASH PAYMENT FORM
     */
    function generateCashForm(price) {
        return `
            <div class="payment-form-card">
                <div class="d-flex align-items-center justify-content-center mb-4">
                    <div style="width: 100px; height: 60px; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-cash-coin" style="font-size: 2rem; color: #333;"></i>
                    </div>
                </div>
                
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Payment Instructions</h6>
                    <ul class="mb-0 mt-2">
                        <li>Pay at the clinic upon arrival</li>
                        <li><strong>Amount to pay: ₱${price.toFixed(2)}</strong></li>
                        <li>Bring exact amount if possible</li>
                        <li>Payment will be marked as PENDING until confirmed</li>
                    </ul>
                </div>

                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="confirmCash" required>
                    <label class="form-check-label" for="confirmCash">
                        I understand and accept cash payment terms
                    </label>
                </div>
            </div>
        `;
    }

    /**
     * DEBIT/CREDIT CARD FORM
     */
    function generateCardForm(cardType, price) {
        const isCredit = cardType === 'credit';
        const title = isCredit ? 'Credit Card' : 'Debit Card';
        const icon = isCredit ? 'bi-credit-card-2-front' : 'bi-credit-card';
        const gradient = isCredit ? 'linear-gradient(135deg, #6e759dff 0%, #b07bf5ff 100%)' : 'linear-gradient(135deg, #34a853 0%, #4285f4 100%)';
        
        return `
            <div class="payment-form-card">
                <!-- Card Preview -->
                <div class="card-preview mb-4" style="background: ${gradient}; border-radius: 12px; padding: 30px; color: white; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <i class="bi ${icon}" style="font-size: 2.5rem;"></i>
                    </div>
                    <div>
                        <p class="mb-2" style="font-size: 0.9rem; opacity: 0.8;">Card Number</p>
                        <p style="font-size: 1.2rem; letter-spacing: 2px; font-family: monospace;" id="cardNumberDisplay">•••• •••• •••• ••••</p>
                        <div class="row mt-4">
                            <div class="col-6">
                                <p style="font-size: 0.8rem; opacity: 0.8;">EXPIRY</p>
                                <p style="font-size: 1rem; font-family: monospace;" id="cardExpiryDisplay">MM/YY</p>
                            </div>
                            <div class="col-6 text-end">
                                <p style="font-size: 0.8rem; opacity: 0.8;">CVV</p>
                                <p style="font-size: 1rem; font-family: monospace;" id="cardCVVDisplay">•••</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="mb-3">
                    <label class="form-label">Cardholder Name</label>
                    <input type="text" class="form-control" id="${cardType}CardName" placeholder="Enter full name on card" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" id="${cardType}CardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Expiry Date</label>
                        <input type="text" class="form-control" id="${cardType}Expiry" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">CVV</label>
                        <input type="password" class="form-control" id="${cardType}CVV" placeholder="123" maxlength="4" required>
                    </div>
                </div>

                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="confirm${cardType.charAt(0).toUpperCase() + cardType.slice(1)}" required>
                    <label class="form-check-label" for="confirm${cardType.charAt(0).toUpperCase() + cardType.slice(1)}">
                        I confirm the ${title} details are correct and authorize this payment
                    </label>
                </div>
            </div>
        `;
    }

    /**
     * BANK TRANSFER FORM
     */
    function generateBankTransferForm(price) {
        return `
            <div class="payment-form-card">

                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Bank Transfer Details</h6>
                    <div class="mt-3">
                        <p class="mb-2"><strong>Bank Name:</strong> <code>UnionBank of the Philippines</code></p>
                        <p class="mb-2"><strong>Account Name:</strong> <code>AKSyon Medical Center</code></p>
                        <p class="mb-2"><strong>Account Number:</strong> <code>1234 5678 9012</code></p>
                        <p class="mb-0"><strong>Amount:</strong> <code>₱${price.toFixed(2)}</code></p>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" id="bankRefNumber" placeholder="Enter bank transfer reference" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Transfer Date</label>
                    <input type="date" class="form-control" id="bankTransferDate" max="${new Date().toISOString().split('T')[0]}" required>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmBank" required>
                    <label class="form-check-label" for="confirmBank">
                        I confirm that I have completed the bank transfer
                    </label>
                </div>
            </div>
        `;
    }

    /**
     * MOBILE PAYMENT FORM
     */
    function generateMobilePaymentForm(price, methodName) {
        return `
            <div class="payment-form-card">
                <div class="d-flex align-items-center justify-content-center mb-4">
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #1f2937 0%, #374151 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative; border: 3px solid #e5e7eb;">
                        <div style="text-align: center; color: white;">
                            <i class="bi bi-phone" style="font-size: 2rem; display: block; margin-bottom: 5px;"></i>
                            <small style="font-size: 0.7rem;">E-Wallet</small>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Supported Platforms</h6>
                    <div class="d-flex gap-2 mt-3">
                        <span class="badge bg-primary">GCash</span>
                        <span class="badge bg-info">PayMaya</span>
                        <span class="badge bg-success">GrabPay</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mobile Payment Platform</label>
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
                    <input type="text" class="form-control" id="mobileRefNumber" placeholder="Enter reference number from your app" required>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmMobile" required>
                    <label class="form-check-label" for="confirmMobile">
                        I confirm the mobile payment details and have completed the transaction
                    </label>
                </div>
            </div>
        `;
    }

    /**
     * DEFAULT PAYMENT FORM
     */
    function generateDefaultPaymentForm(price, methodName) {
        return `
            <div class="payment-form-card">
                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Payment Instructions</h6>
                    <ul class="mb-0 mt-2">
                        <li>Payment will be processed upon your arrival at the clinic</li>
                        <li>Please arrive 10-15 minutes before your scheduled appointment</li>
                        <li>Bring valid ID and appointment confirmation</li>
                        <li><strong>Amount to pay: ₱${price.toFixed(2)}</strong></li>
                    </ul>
                </div>

                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Important:</strong> Failure to arrive on time may result in appointment cancellation.
                </div>

                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="confirmOther" required>
                    <label class="form-check-label" for="confirmOther">
                        I understand the payment terms and conditions
                    </label>
                </div>
            </div>
        `;
    }

    // ========================================================================
    // ATTACH FORM LISTENERS (Card Input Formatting)
    // ========================================================================
    function attachFormListeners() {
        // Format card number and update display
        const cardNumberInputs = document.querySelectorAll('[id$="CardNumber"]');
        cardNumberInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;

                // Update display
                if (e.target.id.includes('debit') || e.target.id.includes('credit')) {
                    const display = document.getElementById('cardNumberDisplay');
                    if (display) {
                        const lastFour = value.slice(-4) || '••••';
                        display.textContent = `•••• •••• •••• ${lastFour}`;
                    }
                }
            });
        });

        // Format expiry date
        const expiryInputs = document.querySelectorAll('[id$="Expiry"]');
        expiryInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2, 4);
                }
                e.target.value = value;

                // Update display
                const display = document.getElementById('cardExpiryDisplay');
                if (display) {
                    display.textContent = value || 'MM/YY';
                }
            });
        });

        // Format CVV display
        const cvvInputs = document.querySelectorAll('[id$="CVV"]');
        cvvInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);

                // Update display
                const display = document.getElementById('cardCVVDisplay');
                if (display) {
                    display.textContent = '•'.repeat(e.target.value.length) || '•••';
                }
            });
        });

        // Format mobile number
        const mobileInputs = document.querySelectorAll('[id*="Mobile"]');
        mobileInputs.forEach(input => {
            if (input.id === 'mobileNumber') {
                input.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 11);
                });
            }
        });

        // Format cardholder name (letters only)
        const nameInputs = document.querySelectorAll('[id$="CardName"]');
        nameInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
            });
        });
    }

    // ========================================================================
    // CONFIRM PAYMENT & BOOK APPOINTMENT
    // ========================================================================
    if (confirmPaymentBtn) {
        confirmPaymentBtn.addEventListener('click', function() {
            if (!validatePaymentForm()) {
                alert('Please fill in all required fields correctly');
                return;
            }

            // Get appointment data
            const appointmentData = {
                pat_id: document.getElementById('patientId').value,
                doc_id: document.getElementById('selectedDoctorId').value,
                serv_id: serviceSelect.value,
                appt_date: dateInput.value,
                appt_time: timeSelect.value,
                stat_id: 1,
                pymt_meth_id: selectedPaymentMethod,
                pymt_amount: selectedServicePrice
            };

            // Show loading
            confirmPaymentBtn.disabled = true;
            confirmPaymentBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            // Submit appointment
            fetch('ajax/patient_book_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(appointmentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appointment booked successfully!');
                    if (paymentModal) {
                        paymentModal.hide();
                    }
                    setTimeout(() => {
                        window.location.href = 'patient_dashb.php';
                    }, 1000);
                } else {
                    alert('✗ Error: ' + (data.message || 'Failed to book appointment'));
                    confirmPaymentBtn.disabled = false;
                    confirmPaymentBtn.innerHTML = 'CONFIRM & BOOK';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                confirmPaymentBtn.disabled = false;
                confirmPaymentBtn.innerHTML = 'CONFIRM & BOOK';
            });
        });
    }

    // ========================================================================
    // VALIDATE PAYMENT FORM
    // ========================================================================
    function validatePaymentForm() {
        const methodName = selectedPaymentMethodName.toLowerCase();

        if (methodName.includes('cash')) {
            return document.getElementById('confirmCash')?.checked;
        } else if (methodName.includes('debit')) {
            return validateCardForm('debit');
        } else if (methodName.includes('credit')) {
            return validateCardForm('credit');
        } else if (methodName.includes('bank')) {
            return document.getElementById('bankRefNumber')?.value &&
                   document.getElementById('bankTransferDate')?.value &&
                   document.getElementById('confirmBank')?.checked;
        } else if (methodName.includes('mobile') || methodName.includes('gcash') || methodName.includes('paymaya')) {
            return document.getElementById('mobilePlatform')?.value &&
                   document.getElementById('mobileNumber')?.value &&
                   document.getElementById('mobileRefNumber')?.value &&
                   document.getElementById('confirmMobile')?.checked;
        } else {
            return document.getElementById('confirmOther')?.checked;
        }
    }

    function validateCardForm(cardType) {
        const cardNum = document.getElementById(`${cardType}CardNumber`)?.value.replace(/\s/g, '');
        const expiry = document.getElementById(`${cardType}Expiry`)?.value;
        const cvv = document.getElementById(`${cardType}CVV`)?.value;
        const name = document.getElementById(`${cardType}CardName`)?.value;
        const confirm = document.getElementById(`confirm${cardType.charAt(0).toUpperCase() + cardType.slice(1)}`)?.checked;

        return cardNum && cardNum.length >= 13 && expiry && expiry.length === 5 && cvv && cvv.length >= 3 && name && confirm;
    }

    // ===============================
    // FILTER FUNCTIONALITY
    // ===============================

    const filterApptId = document.getElementById('filterApptId');
    const filterDate = document.getElementById('filterDate');
    const filterStatus = document.getElementById('filterStatus');
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const appointmentsTable = document.getElementById('appointmentsTable');
    const totalResults = document.getElementById('totalResults');

    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function() {
            const apptIdFilter = filterApptId.value.trim().toLowerCase();
            const dateFilter = filterDate.value;
            const statusFilter = filterStatus.value;

            const rows = appointmentsTable.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const apptId = row.dataset.apptId?.toLowerCase() || '';
                const date = row.dataset.date || '';
                const status = row.dataset.status || '';

                let showRow = true;

                if (apptIdFilter && !apptId.includes(apptIdFilter)) {
                    showRow = false;
                }

                if (dateFilter && date !== dateFilter) {
                    showRow = false;
                }

                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }

                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            totalResults.textContent = visibleCount;
        });
    }

    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', function() {
            filterApptId.value = '';
            filterDate.value = '';
            filterStatus.value = '';

            const rows = appointmentsTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });

            totalResults.textContent = rows.length;
        });
    }

    // ===============================
    // TAB SWITCHING FOR DASHBOARD
    // ===============================

    const todayBtn = document.getElementById('todayBtn');
    const upcomingBtn = document.getElementById('upcomingBtn');
    const todaySection = document.getElementById('todaySection');
    const upcomingSection = document.getElementById('upcomingSection');

    if (todayBtn && upcomingBtn) {
        todayBtn.addEventListener('click', function() {
            todayBtn.classList.add('active');
            upcomingBtn.classList.remove('active');
            todaySection.style.display = 'block';
            upcomingSection.style.display = 'none';
        });

        upcomingBtn.addEventListener('click', function() {
            upcomingBtn.classList.add('active');
            todayBtn.classList.remove('active');
            upcomingSection.style.display = 'block';
            todaySection.style.display = 'none';
        });
    }

    // ===============================
    // UPDATE FORM SUBMISSION HANDLER
    // ===============================
    const updateForm = document.getElementById('updateForm');

    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';

            fetch('ajax/patient_update_appt.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Appointment updated successfully!');
                    location.reload();
                } else {
                    alert('✗ Error: ' + (data.message || 'Failed to update appointment'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error updating appointment:', error);
                alert('✗ An error occurred while updating the appointment');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    // ===============================
    // VIEW PATIENTS - SEARCH/FILTER
    // ===============================

    const searchName = document.getElementById('searchName');
    const applySearchBtn = document.getElementById('applySearchBtn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const patientsTable = document.getElementById('patientsTable');
    const filterTotal = document.getElementById('filterTotal');

    if (applySearchBtn) {
        applySearchBtn.addEventListener('click', function() {
            filterPatients();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchName) {
                searchName.value = '';
            }
            filterPatients();
        });
    }

    if (searchName) {
        searchName.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterPatients();
            }
        });
    }

    function filterPatients() {
        if (!patientsTable) return;

        const searchTerm = searchName ? searchName.value.trim().toLowerCase() : '';
        const rows = patientsTable.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const nameData = row.dataset.name || '';
            
            if (!searchTerm || nameData.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (filterTotal) {
            filterTotal.textContent = visibleCount;
        }
    }

    function updateInitialCounts() {
        if (patientsTable && filterTotal) {
            const totalRows = patientsTable.querySelectorAll('tbody tr').length;
            filterTotal.textContent = totalRows;
        }
    }

    updateInitialCounts();
});

// ===============================
// VIEW APPOINTMENT (GLOBAL FUNCTION)
// ===============================

function viewAppointment(appt) {
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    const modalBody = document.getElementById('viewModalBody');

    const statusBadge = appt.app_status == 1 ? '<span class="badge bg-warning text-dark">Scheduled</span>' :
                        appt.app_status == 2 ? '<span class="badge bg-success">Completed</span>' :
                        '<span class="badge bg-danger">Cancelled</span>';

    const serviceName = appt.service_name || 'N/A';
    const servicePrice = appt.service_price ? '₱' + parseFloat(appt.service_price).toFixed(2) : 'N/A';
    const paymentMethod = appt.payment_method || 'N/A';
    const paymentAmount = appt.payment_amount ? '₱' + parseFloat(appt.payment_amount).toFixed(2) : 'N/A';
    const paymentStatus = appt.payment_status || 'Pending';

    modalBody.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <strong><i class="bi bi-hash"></i> Appointment ID:</strong>
                <p class="mb-0">${appt.app_id}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-person-badge"></i> Doctor:</strong>
                <p class="mb-0">${appt.doctor_name}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-heart-pulse"></i> Specialization:</strong>
                <p class="mb-0">${appt.doc_specialization || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-clipboard2-pulse"></i> Service:</strong>
                <p class="mb-0">${serviceName}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-cash-coin"></i> Service Price:</strong>
                <p class="mb-0">${servicePrice}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-credit-card"></i> Payment Method:</strong>
                <p class="mb-0">${paymentMethod}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-cash-stack"></i> Amount Paid:</strong>
                <p class="mb-0">${paymentAmount}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-check-circle"></i> Payment Status:</strong>
                <p class="mb-0"><span class="badge bg-${paymentStatus === 'Paid' ? 'success' : 'warning'}">${paymentStatus}</span></p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-calendar-event"></i> Date:</strong>
                <p class="mb-0">${appt.formatted_app_date}</p>
            </div>
            <div class="col-md-6">
                <strong><i class="bi bi-clock"></i> Time:</strong>
                <p class="mb-0">${appt.formatted_app_time} <small class="text-muted">(30 min)</small></p>
            </div>
            <div class="col-md-12">
                <strong><i class="bi bi-info-circle"></i> Status:</strong>
                <p class="mb-0">${statusBadge}</p>
            </div>
        </div>
    `;

    modal.show();
}

// ===============================
// UPDATE APPOINTMENT (GLOBAL FUNCTION) - FIXED VERSION
// ===============================

function updateAppointment(appId, appt) {
    console.log('=== UPDATE APPOINTMENT DEBUG ===');
    console.log('appId:', appId);
    console.log('appt object:', appt);
    console.log('spec_id:', appt.spec_id);
    console.log('doc_id:', appt.doc_id);
    console.log('app_date:', appt.app_date);
    console.log('app_time:', appt.app_time);
    console.log('app_status:', appt.app_status);
    console.log('================================');
    
    const modal = new bootstrap.Modal(document.getElementById('updateModal'));
    
    // Populate form fields
    document.getElementById('update_app_id').value = appId;
    document.getElementById('update_spec_id').value = appt.spec_id;
    document.getElementById('update_doc_id').value = appt.doc_id;
    document.getElementById('update_date').value = appt.app_date;
    document.getElementById('update_status').value = appt.app_status;
    
    // Store current values
    const dateInput = document.getElementById('update_date');
    const timeSelect = document.getElementById('update_time');
    
    dateInput.dataset.specId = appt.spec_id;
    dateInput.dataset.currentDate = appt.app_date;
    dateInput.dataset.currentTime = appt.app_time;
    dateInput.dataset.currentApptId = appId; // IMPORTANT: Store current appointment ID
    
    // Set date constraints
    const today = new Date().toISOString().split('T')[0];
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    dateInput.min = today;
    dateInput.max = maxDate.toISOString().split('T')[0];
    
    // Fetch available dates
    console.log('Fetching available dates for spec_id:', appt.spec_id);
    
    fetch(`ajax/patient_get_avail_dates.php?spec_id=${appt.spec_id}`)
        .then(response => response.json())
        .then(data => {
            console.log('Available dates response:', data);
            
            if (data.success && data.dates) {
                dateInput.dataset.availableDates = JSON.stringify(data.dates);
                
                const noteEl = document.getElementById('update_date_note');
                if (noteEl) {
                    noteEl.textContent = `${data.dates.length} dates available (Mon-Sat only)`;
                    noteEl.style.color = '';
                }
                
                // Fetch time slots for current date
                return fetchAvailableTimesForUpdate(appt.spec_id, appt.app_date, appt.app_time, appId);
            } else {
                throw new Error('Failed to load available dates');
            }
        })
        .catch(error => {
            console.error('Error in updateAppointment:', error);
            const noteEl = document.getElementById('update_date_note');
            if (noteEl) {
                noteEl.textContent = 'Error loading dates. Please try again.';
                noteEl.style.color = '#dc3545';
            }
        });
    
    // Add date change listener
    dateInput.removeEventListener('change', handleUpdateDateChange);
    dateInput.addEventListener('change', handleUpdateDateChange);
    
    modal.show();
}

// Handle date change in update modal
function handleUpdateDateChange(e) {
    const selectedDate = e.target.value;
    const specId = e.target.dataset.specId;
    const availableDates = JSON.parse(e.target.dataset.availableDates || '[]');
    const currentTime = e.target.dataset.currentTime;
    const currentApptId = e.target.dataset.currentApptId;
    
    console.log('Date changed to:', selectedDate);
    console.log('Available dates:', availableDates);
    
    // Check if Sunday
    const selectedDay = new Date(selectedDate + 'T00:00:00');
    const dayOfWeek = selectedDay.getDay();
    
    if (dayOfWeek === 0) {
        alert('⚠️ CLOSED ON SUNDAYS\n\nOur clinic operates Monday-Saturday only.\nPlease select another date.');
        e.target.value = e.target.dataset.currentDate || '';
        
        const noteEl = document.getElementById('update_date_note');
        if (noteEl) {
            noteEl.textContent = '⚠️ Sundays are closed. Please select Monday-Saturday.';
            noteEl.style.color = '#dc3545';
        }
        return;
    }
    
    // Reset note color
    const noteEl = document.getElementById('update_date_note');
    if (noteEl) {
        noteEl.style.color = '';
    }
    
    // Fetch new time slots - pass null for currentTime since date changed
    fetchAvailableTimesForUpdate(specId, selectedDate, null, currentApptId);
}

function fetchAvailableTimesForUpdate(specId, selectedDate, currentTime, currentApptId) {
    const timeSelect = document.getElementById('update_time');
    timeSelect.innerHTML = '<option value="">-- Loading... --</option>';
    timeSelect.disabled = true;

    let url = `ajax/patient_get_avail_times.php?spec_id=${specId}&date=${selectedDate}`;
    if (currentApptId) {
        url += `&current_appt_id=${currentApptId}`;
    }

    return fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('Available times response:', data);
            
            if (data.success) {
                timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
                
                let hasCurrentTime = false;
                
                // Add available time slots
                if (data.timeSlots && data.timeSlots.length > 0) {
                    data.timeSlots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = `${slot.formatted} (Dr. ${slot.doctor_name.split(',')[0]})`;
                        option.dataset.doctorId = slot.doctor_id;
                        
                        // Check if this is the current time
                        if (currentTime && slot.time === currentTime) {
                            option.selected = true;
                            hasCurrentTime = true;
                        }
                        
                        timeSelect.appendChild(option);
                    });
                }
                
                // If current time is not in the list (different date), add it
                if (currentTime && !hasCurrentTime && selectedDate === document.getElementById('update_date').dataset.currentDate) {
                    const currentOption = document.createElement('option');
                    currentOption.value = currentTime;
                    const timeObj = new Date('1970-01-01T' + currentTime);
                    const formattedTime = timeObj.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        hour12: true 
                    });
                    currentOption.textContent = `${formattedTime} (Current Time)`;
                    currentOption.selected = true;
                    timeSelect.insertBefore(currentOption, timeSelect.firstChild.nextSibling);
                }
                
                timeSelect.disabled = false;
                
                // Show message if no slots
                if ((!data.timeSlots || data.timeSlots.length === 0) && !currentTime) {
                    timeSelect.innerHTML = '<option value="">-- No Time Slots Available --</option>';
                }
            } else {
                timeSelect.innerHTML = '<option value="">-- No Time Slots Available --</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching time slots:', error);
            timeSelect.innerHTML = '<option value="">-- Error Loading Times --</option>';
        });
}

// ===============================
// CANCEL APPOINTMENT (GLOBAL FUNCTION)
// ===============================

function cancelAppointment(appId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }

    fetch('ajax/patient_cancel_appt.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ app_id: appId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Appointment cancelled successfully!');
            location.reload();
        } else {
            alert('✗ Error: ' + (data.message || 'Failed to cancel appointment'));
        }
    })
    .catch(error => {
        console.error('Error cancelling appointment:', error);
        alert('✗ An error occurred while cancelling the appointment');
    });
}

// ===============================
// PATIENT SETTINGS - PASSWORD TOGGLE
// ===============================

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field && icon) {
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}

// ===============================
// PATIENT SETTINGS - EDIT FORM SUBMISSION
// ===============================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSettingsForm);
} else {
    initSettingsForm();
}

function initSettingsForm() {
    const editForm = document.getElementById('editForm');
    
    if (editForm && !editForm.dataset.listenerAttached) {
        editForm.dataset.listenerAttached = 'true';
        
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const currentPassword = document.getElementById('current_password')?.value;
            const newPassword = document.getElementById('new_password')?.value;
            const confirmPassword = document.getElementById('confirm_password')?.value;

            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword) {
                    alert('Please enter your current password');
                    return;
                }
                if (!newPassword) {
                    alert('Please enter a new password');
                    return;
                }
                if (!confirmPassword) {
                    alert('Please confirm your new password');
                    return;
                }
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    return;
                }
                if (newPassword.length < 6) {
                    alert('New password must be at least 6 characters');
                    return;
                }
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            fetch('ajax/patient_update_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    location.reload();
                } else {
                    alert('✗ Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ An error occurred while updating your account');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
}
window.togglePassword = togglePassword;