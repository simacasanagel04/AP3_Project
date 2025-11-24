<!-- staff_header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff | AKSyon</title>

  <!-- FAVICON -->
    <link rel="icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
    <link rel="shortcut icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png" type="image/png">
    <link rel="apple-touch-icon" href="https://res.cloudinary.com/dibojpqg2/image/upload/v1763945513/AKSyon_favicon_1_foov82.png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons (for toggle & profile icon) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f8f9fa;
    }

    .navbar {
      background-color: #e9e8e8;
      padding: 0.75rem 1rem;
    }

    .navbar-brand {
      font-weight: 600;
      color: #000;
    }

    .navbar-brand small {
      font-size: 0.8rem;
      color: #555;
    }

    .nav-link {
      font-weight: 500;
      color: #000 !important;
    }

    .nav-link:hover {
      color: #0d6efd !important;
    }

    .dropdown-menu {
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .dropdown-item:hover {
      background-color: #f1f1f1;
    }

    .navbar-toggler {
      border: none;
    }

    .navbar-toggler:focus {
      box-shadow: none;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg shadow-sm sticky-top">
    <div class="container-fluid">

      <!-- Logo and Brand -->
      <a class="navbar-brand d-flex align-items-center" href="../index.php">
        <img src="https://res.cloudinary.com/dibojpqg2/image/upload/v1763156755/logo-no-margn_ovy6na.png" alt="AKSyon Logo" height="55" class="me-2">
      </a>

      <!-- Hamburger / Toggle Button -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list fs-2"></i>
      </button>

      <!-- Navbar Links -->
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#about">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#services">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>

          <!-- Dropdown Menu -->
          <li class="nav-item dropdown ms-lg-3">
            <a class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" href="#" id="staffDropdown" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-2"></i> Menu
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="staffDropdown">
              <li><a class="dropdown-item" href="staff_myprofile.php">My Profile</a></li>
              <li><a class="dropdown-item" href="staff_dashboard.php">Dashboard</a></li>
              <li><a class="dropdown-item" href="staff_manage.php">Staff</a></li>
              <li><a class="dropdown-item" href="staff_specialization_manage.php">Specialization</a></li>
              <li><a class="dropdown-item" href="staff_status.php">Status</a></li>
              <li><a class="dropdown-item" href="staff_service.php">Service</a></li>
              <li><a class="dropdown-item" href="staff_medical_records.php">Medical Records</a></li>
              <li><a class="dropdown-item" href="staff_payment.php">Payment</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php">Log Out</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

</body>
</html>