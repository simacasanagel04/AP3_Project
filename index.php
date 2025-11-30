<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/header.php'; ?>

<body>

<!-- IDEX.PHP WELCOME -->
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

<!-- FIRST SECTION -->
<section class="first-section text-dark py-5 position-relative overflow-hidden">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="text-dark-blue display-4 fw-bold mb-4">
                    Because Health Needs More Than Words,<br>
                    <span class="text-dark">It Needs Action</span>
                </h1>
                <p class="lead mb-4">
                    AKSyon Medical Center is committed to providing quality healthcare through timely action and compassionate service. We believe your health deserves proactive care, centered on diagnosis, treatment, and recovery.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <!-- <a href="public/patient_book_appt.php" class="btn btn-primary btn-lg px-4">MAKE APPOINTMENT</a> -->
                    <a href="#working-procedure" class="btn btn-outline-primary btn-lg px-4">HOW IT WORKS</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/1_xiulxy.png" 
                alt="Medical Team" 
                class="img-fluid rounded soft-edge">
            </div>
        </div>
    </div>
</section>

<!-- QUICK ACTIONS -->
<section class="quick-actions py-5" style="margin-top: -40px; position: relative; z-index: 10;">
    <div id="about" class="container">
        <div class="row g-4">

            <!-- OPENING HOURS-->
            <div class="col-md-4">
                <div class="action-card text-white p-4 rounded-5 shadow h-100 text-center" style="background-color: #267476;">
                    <h4 class="fw-bold bi bi-clock fs-2 mb-4"> Opening Hours</h4>
                    <p class="small mb-1">
                        <strong style="margin-right: 20px;">Monday - Friday</strong>08:00 AM- 06:00 PM
                    </p>
                    <p class="small mb-0">
                        <strong style="margin-right: 60px;">Saturday</strong>09:00 AM- 05:00 PM
                    </p>
                    <p class="small">
                        <strong style="margin-right: 140px;">Sunday</strong>Closed</p>
                </div>
            </div>

            <!-- Emergency-->
            <div class="col-md-4">
                <div class="action-card text-white p-4 rounded-5 shadow h-100 text-center" style="background-color: #267476;">
                    <h3 class="bi bi-telephone fs-2 mb-4 fw-bold"> Emergency</h3>
                    <p class="fs-3 fw-bold mb-2">(032) 328 9238</p>
                    <p class="small">Stay safe and share this number to help others in need!</p>
                </div>
            </div>

            <!-- Appointment-->
            <div class="col-md-4">
                <div class="action-card text-white p-4 rounded-5 shadow h-100 text-center" style="background-color: #267476;">
                    <h3 class="bi bi-calendar-check mb-4 fw-bold"> Make an Appointment</h3>
                    <p class="small">Schedule your medical consultation with ease and convenience.<br>
                         We ensure that every appointment is handled promptly and professionally, 
                         giving you access to the care you need, when you need it most.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WORKING PROCEDURE -->
<section id="working-procedure" class="working-procedure py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="h3 fw-bold">OUR WORKING PROCEDURE</h2>
        </div>

        <div class="row g-4">

              <!-- REGISTER OR LOG IN CARD -->
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm border border-1 border-dark">
                    <div class="procedure-number text-white fs-2 mx-auto" style="background-color: #1b3c85ff;">1</div>
                    <h4 class="h6 fw-bold mt-3">REGISTER OR LOG IN</h4>
                    <p class="small text-muted">Create an account or log in to access the online booking system securely.</p>
                </div>
            </div>

            <!-- BOOK AN APPOINTMENT -->
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm border border-1 border-dark">
                    <div class="procedure-number text-white mx-auto" style="background-color: #aa0f1cff;">2</div>
                    <h4 class="h6 fw-bold mt-3">BOOK AN APPOINTMENT</h4>
                    <p class="small text-muted">Select a service and preferred date.</p>
                </div>
            </div>

            <!-- CONSULTATION -->
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm border border-1 border-dark">
                    <div class="procedure-number text-white mx-auto" style="background-color: #bd8111ff;">3</div>
                    <h4 class="h6 fw-bold mt-3">CONSULTATION DAY</h4>
                    <p class="small text-muted">Attend your appointment at the clinic in your scheduled time.</p>
                </div>
            </div>

            <!-- REPORTS -->
            <div class="col-md-3">
                <div class="procedure-card bg-light p-4 rounded-3 text-center h-100 shadow-sm border border-1 border-dark">
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
    <div class="row justify-content-center align-items-center text-center text-lg-start">
      <!-- TEXT -->
      <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="heartbeat-line mx-auto mx-lg-0 mb-3"></div>
        <h2 class=" fw-bold mb-4">Dedicated Healthcare Professionals <br> You Can Trust</h2>
        <p class="lead mb-4">
          Our medical team is composed of skilled and compassionate professionals committed to your well-being. 
          <br>At AKSyon Medical Center, we work together to ensure accurate diagnosis, effective treatment, and genuine concern for your health.
        </p>
        <a href="#" class="btn btn-outline-primary">READ MORE</a>
      </div> 

      <!-- IMAGE -->
      <div class="pic_2 col-lg-4 text-center">
        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/2_jmbage.png" alt="Doctor" class="img-fluid rounded shadow w-75">
      </div>
    </div>
  </div>
</section>

<!-- MEET OUR SPECIALISTS -->
<section id="doctors" class="specialists" style="padding-top: 6rem; padding-bottom: 6rem;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="fw-bold" style="font-family: 'Times New Roman';">MEET SOME OF OUR SPECIALISTS ACROSS MEDICAL DEPARTMENTS</h2>
        </div>

        <div class="row g-3 g-md-4">

            <!-- Pediatrics 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/3_nfybwt.png" alt="Dr. Angela Dela Cruz" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Angela Dela Cruz</h6>
                        <p class="x-small mb-0">MD, FPCPS<br>Pediatrics</p>
                    </div>
                </div>
            </div>

            <!-- Pediatrics 2 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/8_jrtdsg.jpg" alt="Dr. Angela Dela Cruz" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Flordeliza Booc</h6>
                        <p class="x-small mb-0">MD, FPCPS<br>Pediatrics</p>
                    </div>
                </div>
            </div>

            <!-- Cardiology 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/4_oixvoi.jpg" alt="Dr. Rafael Santos" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Rafael Santos</h6>
                        <p class="x-small mb-0">MD, FPCC<br>Cardiology</p>
                    </div>
                </div>
            </div>

            <!-- Cardiology 2 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/9_amzdra.webp" alt="Dr. Rafael Santos" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Joy Manawari</h6>
                        <p class="x-small mb-0">MD, FPCC<br>Cardiology</p>
                    </div>
                </div>
            </div>

            <!-- Neurology 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/5_b9kfjq.jpg" alt="Dr. Miguel Ramirez" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Miguel Ramirez</h6>
                        <p class="x-small mb-0">MD, FPNA<br>Neurology</p>
                    </div>
                </div>
            </div>

            <!-- Neurology 2 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/10_ivakd0.jpg" alt="Dr. Miguel Ramirez" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Kendric Rodriguez</h6>
                        <p class="x-small mb-0">MD, FPNA<br>Neurology</p>
                    </div>
                </div>
            </div>

            <!-- Ophthalmology 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/6_uyyimo.jpg" alt="Dr. Kristine Bautista" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Kristine Bautista</h6>
                        <p class="x-small mb-0">MD, FPAO<br>Ophthalmology</p>
                    </div>
                </div>
            </div>

            <!-- Ophthalmology 2 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/11_fcvo8p.jpg" alt="Dr. Kristine Bautista" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Antonio Aquilino</h6>
                        <p class="x-small mb-0">MD, FPAO<br>Ophthalmology</p>
                    </div>
                </div>
            </div>

            <!-- Dental Care 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/7_n4yeyw.jpg" alt="Dr. Carlo Reyes" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Carlo Reyes</h6>
                        <p class="x-small mb-0">DMD<br>Dental Care</p>
                    </div>
                </div>
            </div>

            <!-- Dental Care 2 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="specialist-card rounded-3 overflow-hidden shadow-sm h-100">
                    <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/12_xzhzb4.webp" alt="Dr. Carlo Reyes" class="w-100" style="height: 250px; object-fit: cover;">
                    <div class="p-2 bg-primary text-white text-center">
                        <h6 class="mb-1 small">Dr. Luna Consuelo</h6>
                        <p class="x-small mb-0">DMD<br>Dental Care</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- SERVICES -->
<section id="services" class="services py-5 bg-white">
    <div class="container-fluid px-4">
        <div class="text-center mb-5">
            <div class="heartbeat-line mx-auto"></div>
            <h2 class="services-main-title fw-bold">OUR SERVICES</h2>
            <p class="text-muted">Explore our specialized care areas and the services we offer under each department.</p>
        </div>

        <!-- =================================== PEDIATRIC SERVICES =================================== -->
        <div class="service-category mb-5" id="pediatrics">
            <h3 class="service-category-title">PEDIATRIC SERVICES</h3>
            <div class="service-row-wrapper">
                <div class="service-row d-flex gap-3 flex-nowrap">

                    <!-- Pediatric service 1 -->
                    <div  class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/pedia_ser1_txz3aq.jpg" alt="Well Baby Clinic" class="w-100">
                        <div class="service-content">
                            <h5>GENERAL CHECK-UP FOR CHILDREN</h5>
                            <p>Regular health assessments to monitor your child's overall well-being.</p>
                        </div>
                    </div>

                    <!-- Pediatric service 2 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/pedia_ser2_zkq2qv.webp" alt="Immunization" class="w-100">
                        <div class="service-content">
                            <h5>VACCINATIONS / IMMUNIZATIONS</h5>
                            <p>Protects children from common and preventable diseases.</p>
                        </div>
                    </div>

                    <!-- Pediatric service 3 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/pedia_ser3_imbvyi.webp" alt="Newborn Screening" class="w-100">
                        <div class="service-content">
                            <h5>GROWTH AND DEVELOPMENT ASSESSMENT</h5>
                            <p>Tracks your child's physical and mental development milestones.</p>
                        </div>
                    </div>

                    <!-- Pediatric service 4 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/pedia_ser4_u7y7ni.webp" alt="Nutritional Assessment" class="w-100">
                        <div class="service-content">
                            <h5>NUTRITIONAL COUNSELING</h5>
                            <p>Provides guidance for healthy eating habits and balanced diets.</p>
                        </div>
                    </div>

                    <!-- Pediatric service 5 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154292/pedia_ser5_tfe3hq.webp" alt="Pediatric Consultation" class="w-100">
                        <div class="service-content">
                            <h5>FEVER / ILLNESS CONSULTATION</h5>
                            <p>Expert care and treatment for childhood fevers and illnesses.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- =================================== CARDIOLOGY SERVICES =================================== -->
        <div class="service-category mb-5" id="cardiology">
            <h3 class="service-category-title">CARDIOLOGY SERVICES</h3>
            <div class="service-row-wrapper">
                <div class="service-row d-flex gap-3 flex-nowrap">

                    <!-- Cardiology service 1 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/car_ser1_jqtr3r.webp" alt="ECG" class="w-100">
                        <div class="service-content">
                            <h5>ECG / ELECTROCARDIOGRAM</h5>
                            <p>Records heart activity to detect irregular rhythms and heart problems.</p>
                        </div>
                    </div>

                    <!-- Cardiology service 2 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154285/car_ser2_bc8wet.webp" alt="2D Echo" class="w-100">
                        <div class="service-content">
                            <h5>STRESS TEST / TREADMILL TEST</h5>
                            <p>Measures how your heart responds to physical activity.</p>
                        </div>
                    </div>

                    <!-- Cardiology service 3 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154281/car_ser3_nbhlqs.webp" alt="Stress Test" class="w-100">
                        <div class="service-content">
                            <h5>ECHOCARDIOGRAM</h5>
                            <p>Uses ultrasound to examine your heart's structure and function.</p>
                        </div>
                    </div>

                    <!-- Cardiology service 4 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154281/car_ser4_jocbqj.webp" alt="Holter Monitoring" class="w-100">
                        <div class="service-content">
                            <h5>BLOOD PRESSURE MONITORING</h5>
                            <p>Tracks blood pressure levels to prevent heart-related issues.</p>
                        </div>
                    </div>

                    <!-- Cardiology service 5 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154281/car_ser5_kn7jnv.webp" alt="Blood Pressure Monitoring" class="w-100">
                        <div class="service-content">
                            <h5>HEART DISEASE CONSULTATION</h5>
                            <p>Professional evaluation and management for heart conditions.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- =================================== NEUROLOGY SERVICES =================================== -->
        <div class="service-category mb-5" id="neurology">
            <h3 class="service-category-title">NEUROLOGY SERVICES</h3>
            <div class="service-row-wrapper">
                <div class="service-row d-flex gap-3 flex-nowrap">

                    <!-- Neurology service 1 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154286/neu_ser1_h91rwz.webp" alt="Neurological Consultation" class="w-100">
                        <div class="service-content">
                            <h5>EEG (ELECTROENCEPHALOGRAM)</h5>
                            <p>Measures brain activity to detect neurological disorders.</p>
                        </div>
                    </div>

                    <!-- Neurology service 2 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154285/neu_ser2_urobox.webp" alt="EEG" class="w-100">
                        <div class="service-content">
                            <h5>HEADACHE / MIGRAINE CONSULTATION</h5>
                            <p>Diagnosis and treatment for recurring headaches and migraines.</p>
                        </div>
                    </div>

                    <!-- Neurology service 3 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154285/neu_ser3_ncbxd8.jpg" alt="Nerve Conduction Study" class="w-100">
                        <div class="service-content">
                            <h5>STROKE RISK ASSESSMENT</h5>
                            <p>Identifies factors that increase the chance of stroke.</p>
                        </div>
                    </div>

                    <!-- Neurology service 4 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154285/neu_ser4_c2r2zo.webp" alt="EMG" class="w-100">
                        <div class="service-content">
                            <h5>NERVE CONDUCTION STUDY</h5>
                            <p>Tests how well electrical signals move through your nerves.</p>
                        </div>
                    </div>

                    <!-- Neurology service 5 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154286/neu_ser5_s1au8m.png" alt="Stroke Management" class="w-100">
                        <div class="service-content">
                            <h5>MEMORY / COGNITIVE EVALUATION</h5>
                            <p>Assesses memory, focus, and cognitive functions.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- =================================== OPHTHALMOLOGY SERVICES =================================== -->
        <div class="service-category mb-5" id="ophthalmology">
            <h3 class="service-category-title">OPHTHALMOLOGY SERVICES</h3>
            <div class="service-row-wrapper">
                <div class="service-row d-flex gap-3 flex-nowrap">

                    <!-- Ophthalmology service 1 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154286/opt_ser1_yl4zou.jpg" alt="Eye Examination" class="w-100">
                        <div class="service-content">
                            <h5>EYE CHECK-UP / VISION SCREENING</h5>
                            <p>Tests vision clarity and detects eye health issues early.</p>
                        </div>
                    </div>

                    <!-- Ophthalmology service 2 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/opt_ser2_txqspd.webp" alt="Refraction" class="w-100">
                        <div class="service-content">
                            <h5>CATARACT CONSULTATION</h5>
                            <p>Evaluation and advice for cataract treatment or surgery.</p>
                        </div>
                    </div>

                    <!-- Ophthalmology service 3 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154286/opt_ser3_iccbza.webp" alt="Contact Lens Fitting" class="w-100">
                        <div class="service-content">
                            <h5>GLAUCOMA TEST</h5>
                            <p>Measures eye pressure to prevent vision loss from glaucoma.</p>
                        </div>
                    </div>

                    <!-- Ophthalmology service 4 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154286/opt_ser4_ofhjkf.webp" alt="Glaucoma Screening" class="w-100">
                        <div class="service-content">
                            <h5>LASER EYE TREATMENT</h5>
                            <p>Corrects certain vision problems using laser technology.</p>
                        </div>
                    </div>

                    <!-- Ophthalmology service 5 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154287/opt_ser5_jochde.webp" alt="Cataract Evaluation" class="w-100">
                        <div class="service-content">
                            <h5>PRESCRIPTION GLASSES / CONTACT LENS CONSULTATION</h5>
                            <p>Guidance for choosing the right eyewear for your vision needs.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- =================================== DENTAL CARE SERVICES =================================== -->
        <div class="service-category mb-5" id="dental">
            <h3 class="service-category-title">DENTAL CARE SERVICES</h3>
            <div class="service-row-wrapper">
                <div class="service-row d-flex gap-3 flex-nowrap">

                    <!-- Dental Care service 1 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/den_ser1_xcnsgv.webp" alt="Oral Prophylaxis" class="w-100">
                        <div class="service-content">
                            <h5>TEETH CLEANING / PROPHYLAXIS</h5>
                            <p>Removes plaque and tartar for a healthier smile.</p>
                        </div>
                    </div>

                    <!-- Dental Care service 2 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154282/den_ser2_ahozdl.jpg" alt="Tooth Extraction" class="w-100">
                        <div class="service-content">
                            <h5>TOOTH EXTRACTION</h5>
                            <p>Safe removal of damaged or decayed teeth.</p>
                        </div>
                    </div>

                    <!-- Dental Care service 3 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154283/den_ser3_arrpy0.webp" alt="Dental Filling" class="w-100">
                        <div class="service-content">
                            <h5>CAVITY FILLING</h5>
                            <p>Restores teeth damaged by cavities or decay.</p>
                        </div>
                    </div>

                    <!-- Dental Care service 4 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/den_ser4_i4vjzq.jpg" alt="Teeth Whitening" class="w-100">
                        <div class="service-content">
                            <h5>TEETH WHITENING</h5>
                            <p>Enhances your smile with safe whitening procedures.</p>
                        </div>
                    </div>

                    <!-- Dental Care service 5 -->
                    <div class="service-card flex-shrink-0">
                        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154284/den_ser5_k6tdtq.jpg" alt="Orthodontics" class="w-100">
                        <div class="service-content">
                            <h5>ORTHODONTIC CONSULTATION</h5>
                            <p>Professional advice for braces or alignment treatments.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Bottom Image Section -->
    <section class="bottom-image">
        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763154285/footer_q6t3il.png" alt="Medical Team Footer" class="img-fluid w-100">
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="public/js/modal.js"></script>
</body>
</html>