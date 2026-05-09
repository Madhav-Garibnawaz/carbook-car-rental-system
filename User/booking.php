<?php
  require("connect.php");
  session_name('user_session');
  session_start();

  if(!isset($_SESSION['user_id'])){
    header("location: register.php");
    exit();
  }

  $uid   = (int)$_SESSION['user_id'];
  $brand = (int)$_GET['brand_id'];
  $car   = (int)$_GET['car_id'];

  $query = "SELECT 
      u.*, 
      c.*, 
      b.*
  FROM users_master u
  LEFT JOIN car_master c 
      ON c.car_id = $car
  LEFT JOIN brand_master b 
      ON b.brand_id = $brand
  WHERE u.ui = $uid
  ";

  $q = mysqli_query($con,$query);
  $row = mysqli_fetch_assoc($q);

  // Fetch ALL active drivers for JS-based filtering
  $all_drivers_query = mysqli_query($con,
    "SELECT dm.driver_id, dm.driver_name, dm.experience_years
     FROM driver_master dm
     WHERE dm.status != 2
     ORDER BY dm.driver_name ASC"
  );
  $all_drivers = [];
  while($d = mysqli_fetch_assoc($all_drivers_query)){
    $all_drivers[] = $d;
  }

  // Fetch all Approved bookings that have a driver assigned (for conflict checking in JS)
  $booked_drivers_query = mysqli_query($con,
    "SELECT bm.driver_id, bm.pickup_datetime, bm.actual_return_datetime
     FROM booking_master bm
     WHERE bm.booking_status = 'Approved'
       AND bm.driver_id IS NOT NULL"
  );
  $booked_drivers = [];
  while($bd = mysqli_fetch_assoc($booked_drivers_query)){
    $booked_drivers[] = $bd;
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://apis.mappls.com/advancedmaps/api/fbb415f8d99d062344407e2cd37b2496/map_sdk?v=3.0&layer=vector"></script>

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      margin: 0;
      padding: 0;
      background: #0d1117;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── HERO ── */
    .hero-wrap {
      position: relative;
      min-height: 100vh;
      background-image: url('images/bg_1.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero-wrap .overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(13,17,23,0.88) 0%, rgba(13,17,23,0.72) 100%);
      z-index: 0;
    }

    .hero-inner {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      padding: 60px 24px;
      display: grid;
      grid-template-columns: 1fr 480px;
      gap: 56px;
      align-items: center;
    }

    /* ── HERO TEXT ── */
    .hero-text { animation: fadeUp .6s ease both; }

    .hero-text h1 {
      font-size: clamp(28px, 4vw, 46px);
      font-weight: 800;
      color: #ffffff;
      line-height: 1.2;
      margin-bottom: 20px;
      letter-spacing: -.5px;
    }

    .hero-text p {
      font-size: 16px;
      color: rgba(255,255,255,0.55);
      line-height: 1.75;
      margin-bottom: 32px;
      max-width: 440px;
    }

    .hero-play {
      display: inline-flex;
      align-items: center;
      gap: 18px;
      text-decoration: none;
      margin-top: 8px;
    }

    .hero-play .play-btn {
      width: 52px;
      height: 52px;
      border: 2px solid rgba(255,255,255,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 16px;
      flex-shrink: 0;
      transition: background .2s, border-color .2s;
      border-radius: 50%;
    }

    .hero-play:hover .play-btn {
      background: #22c55e;
      border-color: #22c55e;
    }

    .hero-play .play-label {
      font-size: 14px;
      font-weight: 600;
      color: rgba(255,255,255,0.7);
      letter-spacing: .03em;
    }

    /* ── CARD (DARK PREMIUM) ── */
    .card {
      background: rgba(22, 27, 34, 0.96);
      border: 1px solid rgba(255,255,255,0.08);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      padding: 32px 28px 28px;
      border-radius: 16px;
      width: 100%;
      min-width: 0;
      animation: fadeUp .6s ease both .15s;
      box-shadow: 0 24px 64px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.04);
    }

    .card-header-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }

    .card-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: #fff;
      flex-shrink: 0;
    }

    .card h2 {
      color: #ffffff;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -.3px;
      margin: 0;
    }

    .card h2 span {
      display: block;
      font-size: 11px;
      font-weight: 500;
      color: rgba(255,255,255,0.4);
      letter-spacing: .05em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    /* ── SECTION LABEL ── */
    .section-label {
      font-size: 9px;
      font-weight: 800;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.3);
      margin: 18px 0 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: rgba(255,255,255,0.06);
    }

    /* ── GRID ── */
    .row-two {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      width: 100%;
    }

    .form-group {
      margin-bottom: 10px;
      min-width: 0;
    }

    .form-label {
      display: block;
      color: rgba(255,255,255,0.45);
      font-size: 9.5px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      margin-bottom: 5px;
    }

    /* ── INPUTS ── */
    .input-wrap {
      position: relative;
      width: 100%;
    }

    .input-wrap i.field-icon {
      position: absolute;
      left: 11px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,0.25);
      font-size: 11px;
      pointer-events: none;
      z-index: 1;
    }

    .form-control,
    .form-select {
      display: block;
      width: 100%;
      min-width: 0;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 8px;
      padding: 10px 10px 10px 32px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12.5px;
      color: #ffffff;
      outline: none;
      transition: background .2s, border-color .2s, box-shadow .2s;
      -webkit-appearance: none;
      appearance: none;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .form-control::placeholder { color: rgba(255,255,255,0.2); }

    .form-control:focus,
    .form-select:focus {
      background: rgba(255,255,255,0.08);
      border-color: rgba(34,197,94,0.5);
      box-shadow: 0 0 0 3px rgba(34,197,94,0.1);
    }

    /* readonly display fields */
    .form-control[readonly],
    span.form-control {
      background: rgba(255,255,255,0.03);
      border-color: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.6);
      cursor: default;
    }

    span.form-control {
      display: flex;
      align-items: center;
      min-height: 38px;
    }

    .form-select {
      cursor: pointer;
      background-color: rgba(255,255,255,0.05);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='rgba(255,255,255,0.4)' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 26px;
    }

    .form-select option {
      background: #1c2333;
      color: #ffffff;
    }

    .form-select option:disabled {
      color: rgba(255,255,255,0.25);
      font-style: italic;
    }

    .form-control[type="datetime-local"] {
      color-scheme: dark;
      color: rgba(255,255,255,0.8);
      padding-right: 4px;
    }

    /* ── VALIDATION STATES (override Bootstrap fully) ── */
    #pan_aadhar_no.is-valid,
    #pan_aadhar_no.is-valid:focus {
      border-color: #22c55e !important;
      background: rgba(34,197,94,0.07) !important;
      box-shadow: 0 0 0 3px rgba(34,197,94,0.15) !important;
      color: #ffffff !important;
      background-image: none !important;
    }
    #pan_aadhar_no.is-invalid,
    #pan_aadhar_no.is-invalid:focus {
      border-color: #ef4444 !important;
      background: rgba(239,68,68,0.07) !important;
      box-shadow: 0 0 0 3px rgba(239,68,68,0.15) !important;
      color: #ffffff !important;
      background-image: none !important;
    }

    .field-feedback {
      font-size: 10.5px;
      font-weight: 600;
      margin-top: 6px;
      padding: 6px 10px;
      border-radius: 6px;
      display: none;
      align-items: center;
      gap: 6px;
      line-height: 1.3;
    }
    .field-feedback.show { display: flex; }
    .field-feedback.ok  {
      color: #4ade80;
      background: rgba(34,197,94,0.12);
      border: 1px solid rgba(34,197,94,0.25);
    }
    .field-feedback.err {
      color: #f87171;
      background: rgba(239,68,68,0.12);
      border: 1px solid rgba(239,68,68,0.25);
    }

    /* ── DRIVER NOTICE ── */
    .driver-notice {
      font-size: 10px;
      margin-top: 5px;
      padding: 5px 8px;
      border-radius: 5px;
      display: none;
      align-items: center;
      gap: 5px;
    }
    .driver-notice.show { display: flex; }
    .driver-notice.info { background: rgba(34,197,94,0.1); color: rgba(255,255,255,0.6); }
    .driver-notice.warn { background: rgba(251,191,36,0.15); color: #fbbf24; }

    /* ── DATE RESTRICTION NOTICE ── */
    .date-notice {
      font-size: 10px;
      margin-top: 4px;
      padding: 4px 8px;
      border-radius: 5px;
      color: #fbbf24;
      background: rgba(251,191,36,0.1);
      display: none;
      align-items: center;
      gap: 5px;
    }
    .date-notice.show { display: flex; }

    /* ── SUBMIT BUTTON ── */
    .btn-submit {
      display: block;
      width: 100%;
      margin-top: 22px;
      padding: 13px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #ffffff;
      font-family: 'DM Sans', sans-serif;
      font-weight: 700;
      font-size: 14px;
      letter-spacing: .02em;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(34,197,94,0.3);
      transition: opacity .2s, transform .15s, box-shadow .2s;
    }

    .btn-submit:hover {
      opacity: .92;
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(34,197,94,0.4);
    }

    .btn-submit:active { transform: translateY(0); }

    /* ── AUTOCOMPLETE ── */
    .autocomplete-list {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: #1c2333;
      border: 1px solid rgba(255,255,255,0.1);
      color: #e2e8f0;
      list-style: none;
      margin: 3px 0 0;
      padding: 0;
      border-radius: 8px;
      box-shadow: 0 12px 32px rgba(0,0,0,0.5);
      z-index: 1000;
      max-height: 200px;
      overflow-y: auto;
      display: none;
    }
    .autocomplete-list.show { display: block; }
    .autocomplete-list li {
      padding: 9px 13px;
      font-size: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      cursor: pointer;
      transition: background .15s;
      white-space: normal;
      line-height: 1.4;
    }
    .autocomplete-list li:last-child { border-bottom: none; }
    .autocomplete-list li:hover { background: rgba(34,197,94,0.12); color: #4ade80; }
    .autocomplete-list li strong { color: #fff; }
    .autocomplete-list li small  { color: rgba(255,255,255,0.4); font-size: 10px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 860px) {
      .hero-inner { grid-template-columns: 1fr; gap: 36px; padding: 50px 20px; }
      .hero-text  { text-align: center; }
      .hero-text p{ margin: 0 auto 28px; }
      .hero-play  { justify-content: center; }
      .card       { max-width: 500px; margin: 0 auto; width: 100%; }
    }

    @media (max-width: 480px) {
      .row-two { grid-template-columns: 1fr; }
      .card     { padding: 24px 16px 20px; }
    }

    /* Fix input text color always white */
input.form-control {
  color: #ffffff !important;
}

/* Fix for typing & focus */
input.form-control:focus {
  color: #ffffff !important;
}

/* Fix Chrome autofill (VERY IMPORTANT) */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-text-fill-color: #ffffff !important;
  transition: background-color 5000s ease-in-out 0s;
}
  </style>
</head>
<body>

  <div class="hero-wrap">
    <div class="overlay"></div>

    <div class="hero-inner">

      <!-- Left: Hero Text -->
      <div class="hero-text">
        <h1>Fast &amp; Easy Way To Rent A Car</h1>
        <p>Easily rent a car with or without a driver and enjoy the ride in a luxurious car ride with your loved one throughtout the country. </p>
        <a href="car.php" class="hero-play popup-vimeo">
          <div class="play-btn"><i class="fas fa-play"></i></div>
          <div class="play-label">Easy steps for renting a car</div>
        </a>
      </div>

      <!-- Right: Booking Form -->
      <div class="card">
        <div class="card-header-row">
          <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
          <h2>Book Your Car <span>Fill in your details below</span></h2>
        </div>

        <?php
        if(isset($_POST['booking'])){
          $pan_aadhar_no          = mysqli_real_escape_string($con, $_POST['pan_aadhar_no']);
          $driver_id_raw          = $_POST['driver_id'];
          $driver_id_val          = (!empty($driver_id_raw) && is_numeric($driver_id_raw)) ? intval($driver_id_raw) : 0;
          $pickup_datetime        = mysqli_real_escape_string($con, $_POST['pickup_datetime']);
          $actual_return_datetime = mysqli_real_escape_string($con, $_POST['actual_return_datetime']);
          $car_safe               = intval($car);
          $uid_safe               = intval($uid);

          $pickup_location = mysqli_real_escape_string($con,$_POST['pickup_location']);
          $drop_location   = mysqli_real_escape_string($con,$_POST['drop_location']);
          $pickup_lat      = mysqli_real_escape_string($con,$_POST['pickup_lat']);
          $pickup_lng      = mysqli_real_escape_string($con,$_POST['pickup_lng']);
          $drop_lat        = mysqli_real_escape_string($con,$_POST['drop_lat']);
          $drop_lng        = mysqli_real_escape_string($con,$_POST['drop_lng']);

          // Server-side Aadhaar validation: exactly 12 digits
          if(!preg_match('/^\d{12}$/', $pan_aadhar_no)){
            echo "<script>alert('Invalid Aadhaar Number. Please enter exactly 12 digits.');</script>";
          } else {

          // ── Validate driver is not already booked for this time ─────────────
          $driver_conflict = false;
          if($driver_id_val > 0 && !empty($pickup_datetime)){
            $return_for_check = !empty($actual_return_datetime) ? "'".$actual_return_datetime."'" : "DATE_ADD('$pickup_datetime', INTERVAL 1 DAY)";
            $conflict_check = mysqli_query($con,
              "SELECT booking_id FROM booking_master
               WHERE driver_id = $driver_id_val
                 AND booking_status = 'Approved'
                 AND driver_id IS NOT NULL
                 AND pickup_datetime < $return_for_check
                 AND (actual_return_datetime IS NULL OR actual_return_datetime = '0000-00-00 00:00:00'
                      OR actual_return_datetime > '$pickup_datetime')
               LIMIT 1"
            );
            if($conflict_check && mysqli_num_rows($conflict_check) > 0){
              $driver_conflict = true;
            }
          }

          if($driver_conflict){
            echo "<script>alert('Sorry, the selected driver is already booked during your chosen dates. Please select a different driver or choose Self Drive.')</script>";
          } else {

          $driver_sql = $driver_id_val > 0 ? $driver_id_val : 'NULL';

          $qry = mysqli_query($con,
            "INSERT INTO booking_master
              (ui, car_id, driver_id, pickup_location, drop_location, pickup_lat, pickup_lng, drop_lat, drop_lng, pickup_datetime, actual_return_datetime, pan_aadhar_no, booking_status, created_at)
             VALUES
               ($uid_safe, $car_safe, $driver_sql,
                '$pickup_location', '$drop_location',
                '$pickup_lat', '$pickup_lng',
                '$drop_lat', '$drop_lng',
                '$pickup_datetime', '$actual_return_datetime',
                '$pan_aadhar_no', 'Pending', NOW())"
          );

          if($qry){
            $booking_id = mysqli_insert_id($con);
            $ppd = 0; $pph = 0; $security_deposit = 0; $late_fee_ph = 0;

            $priceQ = mysqli_query($con,
              "SELECT price_per_day, price_per_hour, security_deposit, late_fee_per_hour
               FROM car_pricing WHERE car_id = $car_safe ORDER BY pricing_id DESC LIMIT 1"
            );
            if($priceQ && mysqli_num_rows($priceQ) > 0){
              $priceRow         = mysqli_fetch_assoc($priceQ);
              $ppd              = floatval($priceRow['price_per_day']    ?? 0);
              $pph              = floatval($priceRow['price_per_hour']   ?? 0);
              $security_deposit = floatval($priceRow['security_deposit']  ?? 0);
              $late_fee_ph      = floatval($priceRow['late_fee_per_hour'] ?? 0);
            }

            $total_days = 1; $extra_hours = 0;
            if(!empty($actual_return_datetime) && $actual_return_datetime !== '0000-00-00T00:00'){
              try {
                $pickup_dt = new DateTime($pickup_datetime);
                $return_dt = new DateTime($actual_return_datetime);
                $diff      = $pickup_dt->diff($return_dt);
                $total_days  = max(0, $diff->days);
                $extra_hours = $diff->h + ($diff->i > 0 ? 1 : 0);
              } catch(Exception $e){ $total_days = 1; }
            }

            $base_amount   = max($total_days * $ppd + ($extra_hours > 0 && $pph > 0 ? $extra_hours * $pph : 0), $ppd);
            $driver_amount = ($driver_id_val > 0) ? round($base_amount * 0.10, 2) : 0;
            $taxable       = $base_amount + $driver_amount;
            $gst           = round($taxable * 0.05, 2);
            $total_amount  = round($taxable + $gst + $security_deposit, 2);

            $detailQ = mysqli_query($con,
              "INSERT INTO booking_details
                 (booking_id, car_id, ui, base_amount, driver_amount, security_deposit, total_amount, late_fee_per_hour, booking_status, created_at)
               VALUES
                 ($booking_id, $car_safe, $uid_safe,
                  $base_amount, $driver_amount, $security_deposit,
                  $total_amount, $late_fee_ph, 'Pending', NOW())"
            );

            if($detailQ){
              echo "<script>alert('Booking Confirmed! Your booking has been submitted successfully.');window.location.href='booking_details.php';</script>";
            } else {
              echo "<script>alert('Booking placed but pricing details could not be saved. Please contact support.');window.location.href='booking_details.php';</script>";
            }
          } else {
            echo "<script>alert('Something went wrong! Please try again.')</script>";
          }

          } // end driver_conflict else
          } // end aadhaar check
        }
        ?>

        <form method="POST" action="#" id="bookingForm" novalidate>

          <!-- Customer Info -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">User Name</label>
              <div class="input-wrap">
                <i class="fas fa-user field-icon"></i>
                <input type="text" name="user_name" class="form-control" value="<?php echo htmlspecialchars($row['uname']); ?>" readonly>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">User Email</label>
              <div class="input-wrap">
                <i class="fas fa-envelope field-icon"></i>
                <input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" readonly>
              </div>
            </div>
          </div>

          <!-- Vehicle Info -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">Car Brand</label>
              <div class="input-wrap">
                <i class="fas fa-tag field-icon"></i>
                <span class="form-control"><?php echo htmlspecialchars($row['brand_name']); ?></span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Car Name</label>
              <div class="input-wrap">
                <i class="fas fa-car field-icon"></i>
                <span class="form-control"><?php echo htmlspecialchars($row['car_display_name']); ?></span>
              </div>
            </div>
          </div>

          <!-- ID Verification -->
          <div class="form-group">
            <label class="form-label">Aadhaar Number</label>
            <div class="input-wrap">
              <i class="fas fa-id-card field-icon"></i>
              <input type="text" name="pan_aadhar_no" id="pan_aadhar_no" class="form-control"
                     placeholder="Enter 12-digit Aadhaar number"
                     maxlength="12"
                     inputmode="numeric"
                     autocomplete="off"
                     required>
            </div>
            <div class="field-feedback ok" id="aadhaar_ok"><i class="fas fa-check-circle"></i> Valid Aadhaar number</div>
            <div class="field-feedback err" id="aadhaar_err"><i class="fas fa-exclamation-circle"></i> Must be exactly 12 digits</div>
          </div>

          <!-- Locations -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">Pickup Location</label>
              <div class="input-wrap">
                <i class="fas fa-map-marker-alt field-icon"></i>
                <input type="text" id="pickup_location" name="pickup_location" class="form-control" placeholder="Enter pickup location" required>
                <input type="hidden" name="pickup_lat" id="pickup_lat">
                <input type="hidden" name="pickup_lng" id="pickup_lng">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Drop Location</label>
              <div class="input-wrap">
                <i class="fas fa-location-dot field-icon"></i>
                <input type="text" id="drop_location" name="drop_location" class="form-control" placeholder="Enter drop location" required>
                <input type="hidden" name="drop_lat" id="drop_lat">
                <input type="hidden" name="drop_lng" id="drop_lng">
              </div>
            </div>
          </div>

          <!-- Rental Dates -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">Pickup Date &amp; Time</label>
              <div class="input-wrap">
                <i class="fas fa-arrow-right field-icon"></i>
                <input type="datetime-local" name="pickup_datetime" id="pickup_datetime" class="form-control" required>
              </div>
              <div class="date-notice" id="pickup_notice"><i class="fas fa-exclamation-triangle"></i> Cannot select past date/time</div>
            </div>
            <div class="form-group">
              <label class="form-label">Return Date &amp; Time</label>
              <div class="input-wrap">
                <i class="fas fa-arrow-left field-icon"></i>
                <input type="datetime-local" name="actual_return_datetime" id="actual_return_datetime" class="form-control">
              </div>
              <div class="date-notice" id="return_notice"><i class="fas fa-exclamation-triangle"></i> Return must be within 7 days of pickup</div>
            </div>
          </div>

          <!-- Driver -->
          <div class="form-group">
            <label class="form-label">Driver Name</label>
            <div class="input-wrap">
              <i class="fas fa-steering-wheel field-icon"></i>
              <select name="driver_id" id="driver_select" class="form-select">
                <option value="">-- Select dates first to see available drivers --</option>
              </select>
            </div>
            <div class="driver-notice info" id="driverNotice">
              <i class="fas fa-check-circle"></i> Showing drivers available for your selected dates.
            </div>
            <div class="driver-notice warn" id="driverWarn">
              <i class="fas fa-exclamation-triangle"></i> No drivers available. You can still proceed with Self Drive.
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-submit" name="booking">
            <i class="fas fa-check" style="margin-right:8px"></i>Confirm Booking
          </button>
        </form>
      </div>

    </div>
  </div>

  <!-- ── JAVASCRIPT ── -->
  <script>
    // ── Helpers ────────────────────────────────────────────────────────────────
    function getNowLocal() {
      // Returns current datetime as "YYYY-MM-DDTHH:MM" string in local time
      const now = new Date();
      const pad = n => String(n).padStart(2,'0');
      return now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) +
             'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
    }

    function addDays(datetimeStr, days) {
      const d = new Date(datetimeStr);
      d.setDate(d.getDate() + days);
      const pad = n => String(n).padStart(2,'0');
      return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) +
             'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // ── Set min for pickup = now (today's date, allow past time on today) ──────
    const pickupEl  = document.getElementById('pickup_datetime');
    const returnEl  = document.getElementById('actual_return_datetime');
    const pickupNotice = document.getElementById('pickup_notice');
    const returnNotice = document.getElementById('return_notice');

    // Min = start of today (00:00) so user can pick any time today
    function getTodayMin() {
      const now = new Date();
      const pad = n => String(n).padStart(2,'0');
      return now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + 'T00:00';
    }

    pickupEl.min = getTodayMin();

    pickupEl.addEventListener('change', function() {
      const val = this.value;
      pickupNotice.classList.remove('show');

      if (!val) {
        returnEl.min = '';
        returnEl.max = '';
        returnEl.value = '';
        return;
      }

      // Validate: pickup must not be before today's date
      const todayStr = getTodayMin();
      const pickupDate = val.split('T')[0];
      const todayDate  = todayStr.split('T')[0];
      if (pickupDate < todayDate) {
        this.value = '';
        pickupNotice.classList.add('show');
        returnEl.min = '';
        returnEl.max = '';
        return;
      }

      // Set return min = pickup, max = pickup + 7 days
      returnEl.min = val;
      returnEl.max = addDays(val, 7);

      // If existing return is outside range, clear it
      if (returnEl.value && (returnEl.value <= val || returnEl.value > addDays(val, 7))) {
        returnEl.value = '';
        returnNotice.classList.remove('show');
      }

      updateDriverDropdown();
    });

    returnEl.addEventListener('change', function() {
      const pickupVal = pickupEl.value;
      const returnVal = this.value;
      returnNotice.classList.remove('show');

      if (!pickupVal || !returnVal) { updateDriverDropdown(); return; }

      const maxReturn = addDays(pickupVal, 7);
      if (returnVal > maxReturn) {
        this.value = maxReturn; // clamp to max
        returnNotice.classList.add('show');
      }
      if (returnVal <= pickupVal) {
        this.value = '';
        returnNotice.classList.add('show');
      }
      updateDriverDropdown();
    });

    // ── Aadhaar validation ─────────────────────────────────────────────────────
    const aadhaarEl  = document.getElementById('pan_aadhar_no');
    const aadhaarOk  = document.getElementById('aadhaar_ok');
    const aadhaarErr = document.getElementById('aadhaar_err');

    aadhaarEl.addEventListener('input', function() {
      // Strip non-digits
      this.value = this.value.replace(/\D/g,'');
      const v = this.value;

      aadhaarOk.classList.remove('show');
      aadhaarErr.classList.remove('show');
      this.classList.remove('is-valid','is-invalid');

      if (v.length === 0) return;
      if (v.length === 12) {
        this.classList.add('is-valid');
        aadhaarOk.classList.add('show');
      } else {
        this.classList.add('is-invalid');
        aadhaarErr.classList.add('show');
      }
    });

    // ── Driver availability ────────────────────────────────────────────────────
    const ALL_DRIVERS  = <?php echo json_encode($all_drivers); ?>;
    const BOOKED_SLOTS = <?php echo json_encode($booked_drivers); ?>;

    function rangesOverlap(aStart, aEnd, bStart, bEnd) {
      return aStart < bEnd && aEnd > bStart;
    }

    function getUnavailableDriverIds(pickupStr, returnStr) {
      if (!pickupStr) return new Set();
      const pickupMs = new Date(pickupStr).getTime();
      const returnMs = returnStr ? new Date(returnStr).getTime() : pickupMs + 86400000;
      const unavailable = new Set();
      BOOKED_SLOTS.forEach(function(slot) {
        if (!slot.driver_id) return;
        const slotStart = new Date(slot.pickup_datetime).getTime();
        const slotEndRaw = slot.actual_return_datetime;
        const slotEnd = (slotEndRaw && slotEndRaw !== '0000-00-00 00:00:00')
          ? new Date(slotEndRaw).getTime() : slotStart + 86400000;
        if (rangesOverlap(pickupMs, returnMs, slotStart, slotEnd)) {
          unavailable.add(parseInt(slot.driver_id));
        }
      });
      return unavailable;
    }

    function updateDriverDropdown() {
      const pickupVal = document.getElementById('pickup_datetime').value;
      const returnVal = document.getElementById('actual_return_datetime').value;
      const select    = document.getElementById('driver_select');
      const notice    = document.getElementById('driverNotice');
      const warn      = document.getElementById('driverWarn');

      select.innerHTML = '';

      if (!pickupVal) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- Select dates first to see available drivers --';
        select.appendChild(opt);
        notice.classList.remove('show');
        warn.classList.remove('show');
        return;
      }

      const unavailableIds = getUnavailableDriverIds(pickupVal, returnVal);
      const selfOpt = document.createElement('option');
      selfOpt.value = '';
      selfOpt.textContent = '-- Self Drive (No Driver) --';
      select.appendChild(selfOpt);

      let availableCount = 0;
      ALL_DRIVERS.forEach(function(driver) {
        const opt = document.createElement('option');
        opt.value = driver.driver_id;
        const exp = driver.experience_years ? ' (' + driver.experience_years + 'yr exp)' : '';
        if (unavailableIds.has(parseInt(driver.driver_id))) {
          opt.textContent = driver.driver_name + exp + ' — Unavailable';
          opt.disabled = true;
        } else {
          opt.textContent = driver.driver_name + exp;
          availableCount++;
        }
        select.appendChild(opt);
      });

      notice.classList.toggle('show', availableCount > 0);
      warn.classList.toggle('show', availableCount === 0 && ALL_DRIVERS.length > 0);
    }

    // ── Form submit validation ─────────────────────────────────────────────────
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      // 1. Aadhaar check
      const aadVal = aadhaarEl.value.trim();
      if (!/^\d{12}$/.test(aadVal)) {
        e.preventDefault();
        aadhaarEl.classList.add('is-invalid');
        aadhaarErr.classList.add('show');
        aadhaarEl.focus();
        return;
      }

      // 2. Pickup date check
      const pickupVal = pickupEl.value;
      if (!pickupVal) { e.preventDefault(); alert('Please select a pickup date and time.'); return; }
      const todayDate = getTodayMin().split('T')[0];
      if (pickupVal.split('T')[0] < todayDate) {
        e.preventDefault();
        pickupNotice.classList.add('show');
        pickupEl.focus();
        return;
      }

      // 3. Return date check (if provided)
      const returnVal = returnEl.value;
      if (returnVal) {
        if (returnVal <= pickupVal) {
          e.preventDefault();
          alert('Return date must be after pickup date.');
          return;
        }
        const maxRet = addDays(pickupVal, 7);
        if (returnVal > maxRet) {
          e.preventDefault();
          returnNotice.classList.add('show');
          returnEl.focus();
          return;
        }
      }

      // 4. Driver conflict check
      const driverSel = document.getElementById('driver_select');
      const driverId  = parseInt(driverSel.value);
      if (driverId > 0 && pickupVal) {
        const unavailableIds = getUnavailableDriverIds(pickupVal, returnVal);
        if (unavailableIds.has(driverId)) {
          e.preventDefault();
          alert('The selected driver is not available for your chosen dates. Please select another driver or choose Self Drive.');
          updateDriverDropdown();
          return;
        }
      }

      // 5. Location coords check
      const pLat = document.getElementById('pickup_lat').value;
      const dLat = document.getElementById('drop_lat').value;
      if (!pLat || !dLat) {
        e.preventDefault();
        alert('Please select both locations from the dropdown list to fetch accurate map coordinates!');
        return;
      }
    });

    updateDriverDropdown();
  </script>

  <!-- ── LOCATION AUTOCOMPLETE ── -->
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const debounce = (func, delay) => {
      let timeout;
      return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    };

    async function fetchSuggestions(query, inputId, latId, lngId) {
      if (query.length < 3) return;
      const input = document.getElementById(inputId);
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=in`;
      try {
        const response = await fetch(url, { headers: { "User-Agent": "CarRentalApp/1.0" } });
        const data = await response.json();
        showDropdown(input, data, latId, lngId);
      } catch (error) { console.error("Geocoding error:", error); }
    }

    function showDropdown(input, results, latId, lngId) {
      let oldList = document.getElementById(input.id + "-list");
      if (oldList) oldList.remove();
      if (results.length === 0) return;

      const list = document.createElement("ul");
      list.id = input.id + "-list";
      list.className = "autocomplete-list show";

      results.forEach(item => {
        const li = document.createElement("li");
        li.innerHTML = `<strong>${item.display_name.split(',')[0]}</strong><br><small>${item.display_name}</small>`;
        li.onclick = function() {
          input.value = item.display_name;
          document.getElementById(latId).value = item.lat;
          document.getElementById(lngId).value = item.lon;
          list.remove();
        };
        list.appendChild(li);
      });

      input.parentNode.appendChild(list);
    }

    const pickupSearch = debounce((e) => fetchSuggestions(e.target.value, "pickup_location", "pickup_lat", "pickup_lng"), 500);
    const dropSearch   = debounce((e) => fetchSuggestions(e.target.value, "drop_location", "drop_lat", "drop_lng"), 500);

    document.getElementById("pickup_location").addEventListener("input", pickupSearch);
    document.getElementById("drop_location").addEventListener("input", dropSearch);

    document.addEventListener("click", function(e) {
      if (!e.target.matches('.form-control')) {
        document.querySelectorAll('.autocomplete-list').forEach(el => el.remove());
      }
    });
  });
  </script>

</body>
</html>