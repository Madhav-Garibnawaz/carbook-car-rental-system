<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <!-- CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/plugins.min.css">
  <link rel="stylesheet" href="../assets/css/kaiadmin.min.css">
  <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
</head>
<style>
  .navbar-brand {
  display: inline-block;
  padding-top: 0.3125rem;
  padding-bottom: 0.3125rem;
  margin-right: 1rem;
  font-size: 1.25rem;
  line-height: inherit;
  white-space: nowrap; }
  .navbar-brand:hover, .navbar-brand:focus {
    text-decoration: none; }
    .navbar-brand {
  font-weight: 800;
  font-size: 20px;
  text-transform: uppercase; }
  .ftco-navbar-light .navbar-brand {
    color: #fff; }
    .ftco-navbar-light .navbar-brand span {
      color: #01d28e; }
    @media (max-width: 991.98px) {
      .ftco-navbar-light .navbar-brand {
        color: #fff; } }
        .ftco-navbar-light.scrolled .navbar-brand {
      color: #000000; }
       .logo-header {
    background: linear-gradient(135deg, #5f728a, #7f94ab);
}

.navbar-brand {
    font-weight: 800;
    font-size: 22px;
    letter-spacing: 1px;
    color: #ffffff !important;
}

.navbar-brand span {
    color: #3ad29f;
}
</style>
<body>

<?php
  // ── Admin side: always use 'admin_session' so it stays isolated ──────────────
  if (session_status() === PHP_SESSION_NONE) {
      session_name('admin_session');
      session_start();
  }
?>

<div class="wrapper">
  <!-- Sidebar -->
  <div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
      <!-- Logo Header -->
      <div class="logo-header" data-background-color="dark">
        <a class="navbar-brand" href="index.php">Car<span>Book</span></a>
        <div class="nav-toggle">
          <button class="btn btn-toggle toggle-sidebar">
            <i class="gg-menu-right"></i>
          </button>
          <button class="btn btn-toggle sidenav-toggler">
            <i class="gg-menu-left"></i>
          </button>
        </div>
        <button class="topbar-toggler more">
          <i class="gg-more-vertical-alt"></i>
        </button>
      </div>
      <!-- End Logo Header -->
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
      <div class="sidebar-content">
        <ul class="nav nav-secondary">
          <li class="nav-item">
            <a href="index.php">
              <i class="fas fa-home"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-section">
            <span class="sidebar-mini-icon">
              <i class="fa fa-ellipsis-h"></i>
            </span>
            <h4 class="text-section">Components</h4>
          </li>
          <!-- Brands -->
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#brandsMenu">
              <i class="fa fa-building"></i>
              <p>Brands</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="brandsMenu">
              <ul class="nav nav-collapse">
                <li>
                  <a href="insert_brands.php">
                    <span class="sub-item">Insert Brands</span>
                  </a>
                </li>
                <li>
                  <a href="view_brands.php">
                    <span class="sub-item">View Brands</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <!-- Categories -->
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#categoriesMenu">
              <i class="fa fa-tags"></i>
              <p>Categories</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="categoriesMenu">
              <ul class="nav nav-collapse">
                <li>
                  <a href="insert_categories.php">
                    <span class="sub-item">Insert Categories</span>
                  </a>
                </li>
                <li>
                  <a href="view_categories.php">
                    <span class="sub-item">View Categories</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <!-- Models -->
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#modelMenu">
              <i class="fa fa-layer-group"></i>
              <p>Models</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="modelMenu">
              <ul class="nav nav-collapse">
                <li>
                  <a href="insert_models.php">
                    <span class="sub-item">Insert Models</span>
                  </a>
                </li>
                <li>
                  <a href="view_models.php">
                    <span class="sub-item">View Models</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <!-- Cars -->
          <li class="nav-item">
            <a data-bs-toggle="collapse" href="#carsMenu">
              <i class="fas fa-car"></i>
              <p>Cars</p>
              <span class="caret"></span>
            </a>
            <div class="collapse" id="carsMenu">
              <ul class="nav nav-collapse">
                <li>
                  <a href="insert_cars.php">
                    <span class="sub-item">Insert Cars</span>
                  </a>
                </li>
                <li>
                  <a href="view_cars.php">
                    <span class="sub-item">View Cars</span>
                  </a>
                </li>
              </ul>
            </div>
            <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Tasks</h4>
            </li>
            <li class="nav-item">
              <a href="booking_master.php">
                <i class="fas fa-calendar-check me-2"></i>
                <p>Bookings</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="contact_master.php">
                <i class="fa fa-headset me-2"></i>
                <p>Support Requests</p>
              </a>
            </li>
          </li>
            <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Users</h4>
            </li>
            <li class="nav-item">
              <a href="user_master.php">
                <i class="fas fa-user me-2"></i>
                <p>Show Users</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="driver_master.php">
                <i class="fas fa-id-card me-2"></i>
                <p>Show Drivers</p>
              </a>
            </li>
          </li>

        </ul>
      </div>
    </div>
  </div>
  <!-- End Sidebar -->

  <div class="main-panel">
    <div class="main-header">
      <div class="main-header-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="index.php" class="logo">
            <img
              src="../assets/img/kaiadmin/logo_light.svg"
              alt="navbar brand"
              class="navbar-brand"
              height="20"
            />
          </a>
          <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
              <i class="gg-menu-right"></i>
            </button>
            <button class="btn btn-toggle sidenav-toggler">
              <i class="gg-menu-left"></i>
            </button>
          </div>
          <button class="topbar-toggler more">
            <i class="gg-more-vertical-alt"></i>
          </button>
        </div>
        <!-- End Logo Header -->
      </div>
      <!-- Navbar Header -->
      <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
        <div class="container-fluid">
          <nav class="navbar navbar-header-left navbar-expand-lg navbar-form nav-search p-0 d-none d-lg-flex">
          </nav>

          <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">

            <li class="nav-item topbar-user dropdown hidden-caret">
  <a
    class="dropdown-toggle profile-pic"
    data-bs-toggle="dropdown"
    href="#"
    aria-expanded="false"
  >
    <div class="avatar-sm">
      <?php if (!empty($_SESSION['admin_photo'])): ?>
        <img
          src="./images/admin_profile/<?php echo $_SESSION['admin_photo']; ?>"
          alt="..."
          class="avatar-img rounded-circle"
        />
      <?php else: ?>
        <img
          src="../assets/img/profile.jpg"
          alt="..."
          class="avatar-img rounded-circle"
        />
      <?php endif; ?>
    </div>
    <span class="profile-username">
      <span class="op-7">Hi,</span>
      <span class="fw-bold"><?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?></span>
    </span>
  </a>

  <ul class="dropdown-menu dropdown-user animated fadeIn">
    <div class="dropdown-user-scroll scrollbar-outer">
      <li>
        <div class="user-box">
          <div class="avatar-lg">
            <?php if (!empty($_SESSION['admin_photo'])): ?>
              <img
                src="./images/admin_profile/<?php echo $_SESSION['admin_photo']; ?>"
                alt="image profile"
                class="avatar-img rounded"
              />
            <?php else: ?>
              <img
                src="../assets/img/profile.jpg"
                alt="image profile"
                class="avatar-img rounded"
              />
            <?php endif; ?>
          </div>
          <div class="u-text">
            <h4><?php echo isset($_SESSION['admin_name'])  ? $_SESSION['admin_name']  : 'Admin'; ?></h4>
            <p class="text-muted"><?php echo isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : ''; ?></p>
          </div>
        </div>
      </li>
      <li>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="profile.php">
          <i class="fas fa-user me-2"></i>My Profile
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="admin_logout.php">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
      </li>
    </div>
  </ul>
</li>
          </ul>
        </div>
      </nav>
      <!-- End Navbar -->
    </div>