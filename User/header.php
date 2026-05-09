<head>
    <title>Carbook</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,500,600,700,800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">

    <link rel="stylesheet" href="css/aos.css">

    <link rel="stylesheet" href="css/ionicons.min.css">

    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">

    
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/icomoon.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
      .user-dropdown:hover .dropdown-menu {
          display: block;
          margin-top: 0;
      }

      .user-card {
          min-width: 220px;
          border-radius: 15px;
          overflow: hidden;
          box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      }
    </style>
  </head>

  <body>
    <?php
      // ── User side: always use 'user_session' so it stays isolated ────────────
      if (session_status() === PHP_SESSION_NONE) {
          session_name('user_session');
          session_start();
      }

      $base_url = "/carbook/";
      $current_page = basename($_SERVER['PHP_SELF']);

      include("connect.php");

      $user = null;

      if(isset($_SESSION['user_id'])) {
          $id = intval($_SESSION['user_id']);
          $q = mysqli_query($con,"SELECT * FROM users_master WHERE ui = $id");
          if($q && mysqli_num_rows($q) > 0){
              $user = mysqli_fetch_assoc($q);
          }
      }
    ?>
	  <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
	    <div class="container">
	      <a class="navbar-brand" href="index.php">Car<span>Book</span></a>
	      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
	        <span class="oi oi-menu"></span> Menu
	      </button>

	      <div class="collapse navbar-collapse" id="ftco-nav">
	        <ul class="navbar-nav ml-auto">
	          <li class="nav-item <?php if($current_page == 'index.php') echo 'active'; ?>">
              <a href="index.php" class="nav-link">Home</a>
            </li>
            <li class="nav-item <?php if($current_page == 'car.php') echo 'active'; ?>">
              <a href="car.php" class="nav-link">Cars</a>
            </li>
	          <li class="nav-item <?php if($current_page == 'blog.php') echo 'active'; ?>">
              <a href="blog.php" class="nav-link">Blog</a>
            </li>
	          <li class="nav-item <?php if($current_page == 'contact.php') echo 'active'; ?>">
              <a href="contact.php" class="nav-link">Contact</a>
            </li>
             <li class="nav-item <?php if($current_page == 'about.php') echo 'active'; ?>">
              <a href="about.php" class="nav-link">About</a>
            </li>
            <li class="nav-item dropdown user-dropdown">
  <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button">

    <?php if(isset($_SESSION['user_id'])) { ?>

        <!-- Logged In User Image -->
        <img src="user_profile/<?php echo $user['photo'];?>" 
             class="rounded-circle mr-2" width="35" height="35">

        <!-- Username -->
        <span><?php echo $user['uname']; ?></span>

    <?php } else { ?>

        <!-- Guest Icon -->
        <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" 
             class="rounded-circle mr-2" width="35" height="35">

        <span>Guest</span>

    <?php } ?>

  </a>

  <!-- Dropdown Card -->
  <div class="dropdown-menu dropdown-menu-right user-card">

    <?php if(isset($_SESSION['user_id'])) { ?>

        <!-- Logged In View -->
        <div class="text-center p-3">
            <img src="user_profile/<?php echo $user['photo']; ?>" 
                 class="rounded-circle mb-2" width="70">
            <h6 class="mb-0"><?php echo $user['uname']; ?></h6>
            <small class="text-muted"><?php echo $user['email']; ?></small>
        </div>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="profile.php">Profile</a>
        <a class="dropdown-item text-danger" href="logout.php">Logout</a>

    <?php } else { ?>

        <!-- Guest View -->
        <div class="text-center p-3">
            <h6 class="mb-2">Welcome Guest</h6>
            <a href="register.php" class="btn btn-primary btn-sm w-100">Log In</a>
        </div>

    <?php } ?>

  </div>
</li>
	        </ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->