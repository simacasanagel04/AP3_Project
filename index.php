<!DOCTYPE html>
<html lang="en">
<?php
include 'includes/header.php';
?>

<body>

<!-- WELCOME TOAST -->
<?php if (isset($_SESSION['just_registered']) && $_SESSION['just_registered']): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <strong>Welcome!</strong> Your account has been created successfully.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toast = new bootstrap.Toast(document.querySelector('.toast'), { delay: 5000 });
        toast.show();
    });
</script>
<?php unset($_SESSION['just_registered']); ?>
<?php endif; ?>

<!-- HERO SECTION -->
<section class="hero-section bg-gradient text-dark py-5 position-relative overflow-hidden">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold text-primary mb-4">
                    Because Health Needs More Than Words,<br>
                    <span class="text-dark">It Needs Action</span>
                </h1>
                <p class="lead mb-4">
                    AKSyon Medical Center is committed to providing quality healthcare through timely action and compassionate service. We believe your health deserves proactive care, centered on diagnosis, treatment, and recovery.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#" class="btn btn-primary btn-lg px-4">MAKE APPOINTMENT</a>
                    <a href="#" class="btn btn-outline-primary btn-lg px-4">HOW IT WORKS</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/hero-team.jpg" alt="Medical Team" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
    <img src="assets/images/stethoscope.png" alt="Stethoscope" class="position-absolute bottom-0 start-0 w-25 opacity-25">
</section>

<!-- QUICK ACTIONS -->
<section class="quick-actions py-5 bg-white" style="margin-top: -50px; position: relative; z-index: 10;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="action-card bg-teal text-white p-4 rounded-3 shadow-sm h-100 text-center">
                    <i class="bi bi-clock fs-1 mb-3"></i>
                    <h3 class="h5 fw-bold">Opening Hours</h3>
                    <p class="small mb-1"><strong>Monday - Friday</strong><br>08:00 - 05:00 PM</p>
                    <p class="small mb-0"><strong>Saturday</strong><br>09:00 - 01:00 PM</p>
                    <p class="small"><strong>Sunday</strong><br>Closed</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card bg-teal text-white p-4 rounded-3 shadow-sm h-100 text-center">
                    <i class="bi bi-telephone fs-1 mb-3"></i>
                    <h3 class="h5 fw-bold">Emergency</h3>
                    <p class="display-6 fw-bold mb-2">(032) 328 9238</p>
                    <p class="small">Stay safe and share this number to help others in need!</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card bg-teal text-white p-4 rounded-3 shadow-sm h-100 text-center">
                    <i class="bi bi-calendar-check fs-1 mb-3"></i>
                    <h3 class="h5 fw-bold">Make an Appointment</h3>
                    <p class="small">Schedule your medical consultation with ease and convenience.</p>
                    <button class="btn btn-light btn-sm mt-2">BOOK NOW</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WORKING PROCEDURE -->
<section class="working-procedure py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="h3 fw-bold">OUR WORKING PROCEDURE</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm">
                    <div class="procedure-number bg-primary text-white mx-auto">1</div>
                    <h4 class="h6 fw-bold mt-3">REGISTER OR LOG IN</h4>
                    <p class="small text-muted">Create an account or log in to access the online booking system securely.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm">
                    <div class="procedure-number bg-danger text-white mx-auto">2</div>
                    <h4 class="h6 fw-bold mt-3">BOOK AN APPOINTMENT</h4>
                    <p class="small text-muted">Select your doctor, specialization, and preferred date, choose in-clinic or online video consultation.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm">
                    <div class="procedure-number bg-warning text-white mx-auto">3</div>
                    <h4 class="h6 fw-bold mt-3">CONSULTATION DAY</h4>
                    <p class="small text-muted">Attend your appointment at the clinic or join via video call at your scheduled time.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm">
                    <div class="procedure-number bg-teal text-white mx-auto">4</div>
                    <h4 class="h6 fw-bold mt-3">GET YOUR REPORTS</h4>
                    <p class="small text-muted">Receive detailed medical reports and follow-up care instructions from your physician.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DEDICATED PROFESSIONALS -->
<section class="professionals py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="h4 fw-bold">Dedicated Healthcare Professionals You Can Trust</h2>
        </div>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="lead">
                    Our medical team is composed of skilled and compassionate professionals committed to your well-being. At AKSyon Medical Center, we work together to ensure accurate diagnosis, effective treatment, and genuine concern for your health.
                </p>
                <a href="#" class="btn btn-outline-primary">READ MORE</a>
            </div>
            <div class="col-lg-4 text-center">
                <img src="assets/images/doctor-female.jpg" alt="Doctor" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- MEET OUR SPECIALISTS -->
<section class="specialists py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="h4 fw-bold">MEET OUR SPECIALISTS ACROSS MEDICAL DEPARTMENTS</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="assets/images/doctor1.jpg" alt="Dr. Angela Dela Cruz" class="w-100" style="height: 220px; object-fit: cover;">
                    <div class="p-3 bg-primary text-white text-center">
                        <h5 class="h6 mb-1">Dr. Angela Dela Cruz</h5>
                        <p class="small mb-0">MD, FPCPS<br>Pediatrics</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="assets/images/doctor2.jpg" alt="Dr. Rafael Santos" class="w-100" style="height: 220px; object-fit: cover;">
                    <div class="p-3 bg-primary text-white text-center">
                        <h5 class="h6 mb-1">Dr. Rafael Santos</h5>
                        <p class="small mb-0">MD, FPCC<br>Cardiology</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="assets/images/doctor3.jpg" alt="Dr. Miguel Ramirez" class="w-100" style="height: 220px; object-fit: cover;">
                    <div class="p-3 bg-primary text-white text-center">
                        <h5 class="h6 mb-1">Dr. Miguel Ramirez</h5>
                        <p class="small mb-0">MD, FPNA<br>Neurology</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="assets/images/doctor4.jpg" alt="Dr. Kristine Bautista" class="w-100" style="height: 220px; object-fit: cover;">
                    <div class="p-3 bg-primary text-white text-center">
                        <h5 class="h6 mb-1">Dr. Kristine Bautista</h5>
                        <p class="small mb-0">MD, FPAO<br>Ophthalmology</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="assets/images/doctor5.jpg" alt="Dr. Carlo Reyes" class="w-100" style="height: 220px; object-fit: cover;">
                    <div class="p-3 bg-primary text-white text-center">
                        <h5 class="h6 mb-1">Dr. Carlo Reyes</h5>
                        <p class="small mb-0">DMD<br>Dental Care</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="services py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="h4 fw-bold">OUR SERVICES</h2>
            <p class="text-muted">Explore our specialized care areas and the services we offer under each department.</p>
        </div>

        <!-- Pediatric -->
        <div class="service-category mb-5">
            <h3 class="h5 fw-bold text-primary border-start border-primary border-5 ps-3 mb-4">PEDIATRIC SERVICES</h3>
            <div class="row g-3">
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="service-card bg-light rounded-3 overflow-hidden shadow-sm text-center">
                        <img src="assets/images/pediatric1.jpg" alt="Well Baby Clinic" class="w-100" style="height: 120px; object-fit: cover;">
                        <p class="p-2 small fw-medium">Well Baby Clinic</p>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="service-card bg-light rounded-3 overflow-hidden shadow-sm text-center">
                        <img src="assets/images/pediatric2.jpg" alt="Immunization" class="w-100" style="height: 120px; object-fit: cover;">
                        <p class="p-2 small fw-medium">Immunization</p>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="service-card bg-light rounded-3 overflow-hidden shadow-sm text-center">
                        <img src="assets/images/pediatric3.jpg" alt="Newborn Screening" class="w-100" style="height: 120px; object-fit: cover;">
                        <p class="p-2 small fw-medium">Newborn Screening</p>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="service-card bg-light rounded-3 overflow-hidden shadow-sm text-center">
                        <img src="assets/images/pediatric4.jpg" alt="Nutritional Assessment" class="w-100" style="height: 120px; object-fit: cover;">
                        <p class="p-2 small fw-medium">Nutritional Assessment</p>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="service-card bg-light rounded-3 overflow-hidden shadow-sm text-center">
                        <img src="assets/images/pediatric5.jpg" alt="Pediatric Consultation" class="w-100" style="height: 120px; object-fit: cover;">
                        <p class="p-2 small fw-medium">Pediatric Consultation</p>
                    </div>
                </div>
            </div>
        </div>
<!-- ================================================================================== -->
            <!-- Cardiology Services -->
            <div class="service-category mb-5">
                <h3 class="service-category-title">CARDIOLOGY SERVICES</h3>
                <div class="row g-3">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/cardio1.jpg" alt="ECG" class="img-fluid">
                            <p>ECG</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/cardio2.jpg" alt="2D Echo" class="img-fluid">
                            <p>2D Echo</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/cardio3.jpg" alt="Stress Test" class="img-fluid">
                            <p>Stress Test</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/cardio4.jpg" alt="Holter Monitoring" class="img-fluid">
                            <p>Holter Monitoring</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/cardio5.jpg" alt="Blood Pressure Monitoring" class="img-fluid">
                            <p>Blood Pressure Monitoring</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Neurology Services -->
            <div class="service-category mb-5">
                <h3 class="service-category-title">NEUROLOGY SERVICES</h3>
                <div class="row g-3">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/neuro1.jpg" alt="Neurological Consultation" class="img-fluid">
                            <p>Neurological Consultation</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/neuro2.jpg" alt="EEG" class="img-fluid">
                            <p>EEG</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/neuro3.jpg" alt="Nerve Conduction Study" class="img-fluid">
                            <p>Nerve Conduction Study</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/neuro4.jpg" alt="EMG" class="img-fluid">
                            <p>EMG</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/neuro5.jpg" alt="Stroke Management" class="img-fluid">
                            <p>Stroke Management</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ophthalmology Services -->
            <div class="service-category mb-5">
                <h3 class="service-category-title">OPHTHALMOLOGY SERVICES</h3>
                <div class="row g-3">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/opthal1.jpg" alt="Eye Examination" class="img-fluid">
                            <p>Eye Examination</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/opthal2.jpg" alt="Refraction" class="img-fluid">
                            <p>Refraction</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/opthal3.jpg" alt="Contact Lens Fitting" class="img-fluid">
                            <p>Contact Lens Fitting</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/opthal4.jpg" alt="Glaucoma Screening" class="img-fluid">
                            <p>Glaucoma Screening</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/opthal5.jpg" alt="Cataract Evaluation" class="img-fluid">
                            <p>Cataract Evaluation</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dental Care Services -->
            <div class="service-category mb-5">
                <h3 class="service-category-title">DENTAL CARE SERVICES</h3>
                <div class="row g-3">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/dental1.jpg" alt="Oral Prophylaxis" class="img-fluid">
                            <p>Oral Prophylaxis</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/dental2.jpg" alt="Tooth Extraction" class="img-fluid">
                            <p>Tooth Extraction</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/dental3.jpg" alt="Dental Filling" class="img-fluid">
                            <p>Dental Filling</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/dental4.jpg" alt="Teeth Whitening" class="img-fluid">
                            <p>Teeth Whitening</p>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="service-card">
                            <img src="assets/images/dental5.jpg" alt="Orthodontics" class="img-fluid">
                            <p>Orthodontics</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom Image Section -->
    <section class="bottom-image">
        <img src="assets/images/team-meeting.jpg" alt="Medical Team Meeting" class="img-fluid w-100">
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom JS -->
    <script src="public/js/modal.js"></script>
</body>
</html>