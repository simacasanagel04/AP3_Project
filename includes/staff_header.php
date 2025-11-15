<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AKSyon Medical Center</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    .navbar {
      background-color: #e9e8e8;
    }

    .navbar-brand {
      font-weight: 600;
      color: #000;
    }

    .navbar-brand small {
      font-size: 0.8rem;
      color: #555;
    }
  </style>
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg shadow-sm sticky-top">
    <div class="container-fluid">

      <!-- Logo and Brand -->
      <a class="navbar-brand d-flex align-items-center" href="staff_dashboard.php">
        <img src="../assets/logo/logo-no-margn.png" alt="AKSyon Logo" height="45" class="me-2">
        <div>
          <div>AKSyon</div>
          <small>Medical Center</small>
        </div>
      </a>

      <!-- Hamburger Toggle Button -->
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list fs-2"></i>
      </button>

      <!-- Navbar Links -->
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link fw-medium text-dark" href="../index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium text-dark" href="#about">About Us</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium text-dark" href="#services">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium text-dark" href="#contact">Contact</a>
          </li>

          <!-- Dropdown Menu -->
          <li class="nav-item dropdown ms-lg-3">
            <a class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" href="#" id="staffDropdown" 
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-2"></i> Menu
            </a>
            <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow" aria-labelledby="staffDropdown">
              <li><a class="dropdown-item py-2" href="staff_myprofile.php"><i class="bi bi-person-fill me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item py-2" href="staff_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2" href="staff_manage.php"><i class="bi bi-people-fill me-2"></i>Staff</a></li>
              <li><a class="dropdown-item py-2" href="staff_specialization_manage.php"><i class="bi bi-bookmark-fill me-2"></i>Specialization</a></li>
              <li><a class="dropdown-item py-2" href="staff_status.php"><i class="bi bi-check-circle-fill me-2"></i>Status</a></li>
              <li><a class="dropdown-item py-2" href="staff_service.php"><i class="bi bi-briefcase-fill me-2"></i>Service</a></li>
              <li><a class="dropdown-item py-2" href="staff_medical_records.php"><i class="bi bi-file-medical-fill me-2"></i>Medical Records</a></li>
              <li><a class="dropdown-item py-2" href="staff_payment.php"><i class="bi bi-credit-card-fill me-2"></i>Payment</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Bootstrap JS (Bundle includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>