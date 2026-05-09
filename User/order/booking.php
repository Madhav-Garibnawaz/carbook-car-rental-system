<?php
require("connect.php");
session_name('user_session');
session_start();

  $uid   = $_SESSION['user_id'];
  if(!isset($uid)){
    header("location: register.php");
  }
  $brand = $_GET['brand_id'];
  $car   = $_GET['car_id'];

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

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      margin: 0;
      padding: 0;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

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
      background: rgba(0, 0, 0, 0.62);
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
      grid-template-columns: 1fr 460px;
      gap: 56px;
      align-items: center;
    }

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
      color: rgba(255,255,255,0.72);
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
      border: 2px solid rgba(255,255,255,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 16px;
      flex-shrink: 0;
      transition: background .2s, border-color .2s;
    }

    .hero-play:hover .play-btn {
      background: #1a8cff;
      border-color: #1a8cff;
    }

    .hero-play .play-label {
      font-size: 14px;
      font-weight: 600;
      color: rgba(255,255,255,0.85);
      letter-spacing: .03em;
    }

    .card {
      background: #1a8cff;
      padding: 36px 28px 30px;
      overflow: hidden;
      width: 100%;
      min-width: 0;
      animation: fadeUp .6s ease both .15s;
    }

    .card h2 {
      color: #ffffff;
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 22px;
      letter-spacing: -.3px;
    }

    .row-two {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      width: 100%;
    }

    .form-group {
      margin-bottom: 13px;
      min-width: 0;
    }

    .form-label {
      display: block;
      color: #ffffff;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .09em;
      text-transform: uppercase;
      margin-bottom: 6px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .input-wrap {
      position: relative;
      width: 100%;
    }

    .input-wrap i {
      position: absolute;
      left: 11px;
      top: 50%;
      transform: translateY(-50%);
      color: #90caff;
      font-size: 11px;
      pointer-events: none;
      z-index: 1;
    }

    .form-control,
    .form-select {
      display: block;
      width: 100%;
      min-width: 0;
      background: rgba(255,255,255,0.18);
      border: 1.5px solid rgba(255,255,255,0.28);
      border-radius: 0;
      padding: 10px 10px 10px 30px;
      font-family: 'DM Sans', sans-serif;
      font-size: 12.5px;
      color: #ffffff;
      outline: none;
      transition: background .2s, border-color .2s;
      -webkit-appearance: none;
      appearance: none;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .form-control::placeholder { color: rgba(255,255,255,0.45); }

    .form-control:focus,
    .form-select:focus {
      background: rgba(255,255,255,0.26);
      border-color: rgba(255,255,255,0.65);
    }

    .form-select {
      cursor: pointer;
      background-color: rgba(255,255,255,0.18);
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 26px;
    }

    .form-select option {
      background: #1a6fd4;
      color: #ffffff;
    }

    .form-select option:disabled {
      color: rgba(255,255,255,0.35);
      font-style: italic;
    }

    .form-control[type="datetime-local"] {
      color-scheme: dark;
      color: rgba(255,255,255,0.85);
      padding-right: 4px;
    }

    /* Driver availability notice */
    .driver-notice {
      font-size: 10px;
      margin-top: 5px;
      padding: 5px 8px;
      border-radius: 3px;
      display: none;
    }
    .driver-notice.show { display: block; }
    .driver-notice.info {
      background: rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.8);
    }
    .driver-notice.warn {
      background: rgba(255,193,7,0.25);
      color: #fff3cd;
    }

    .btn-submit {
      display: block;
      width: 100%;
      margin-top: 20px;
      padding: 13px;
      background: #22c55e;
      color: #ffffff;
      font-family: 'DM Sans', sans-serif;
      font-weight: 700;
      font-size: 14px;
      letter-spacing: .02em;
      border: none;
      border-radius: 0;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(34,197,94,.28);
      transition: background .2s, transform .15s, box-shadow .2s;
    }

    .btn-submit:hover {
      background: #16a34a;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(34,197,94,.36);
    }

    .btn-submit:active { transform: translateY(0); }

    @media (max-width: 860px) {
      .hero-inner {
        grid-template-columns: 1fr;
        gap: 36px;
        padding: 50px 20px;
      }
      .hero-text { text-align: center; }
      .hero-text p { margin: 0 auto 28px; }
      .hero-play  { justify-content: center; }
      .card { max-width: 500px; margin: 0 auto; width: 100%; }
    }

    @media (max-width: 480px) {
      .row-two { grid-template-columns: 1fr; }
      .card     { padding: 26px 16px 22px; }
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
        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts.</p>
        <a href="https://vimeo.com/45830194" class="hero-play popup-vimeo">
          <div class="play-btn">
            <i class="fas fa-play"></i>
          </div>
          <div class="play-label">Easy steps for renting a car</div>
        </a>
      </div>

      <!-- Right: Booking Form -->
      <div class="card">
        <h2>Book Your Car</h2>

        <?php
        if(isset($_POST['booking'])){
          $pan_aadhar_no          = mysqli_real_escape_string($con, $_POST['pan_aadhar_no']);
          $driver_id_raw          = $_POST['driver_id'];
          $driver_id_val          = (!empty($driver_id_raw) && is_numeric($driver_id_raw)) ? intval($driver_id_raw) : 0;
          $pickup_datetime        = mysqli_real_escape_string($con, $_POST['pickup_datetime']);
          $actual_return_datetime = mysqli_real_escape_string($con, $_POST['actual_return_datetime']);
          $car_safe               = intval($car);
          $uid_safe               = intval($uid);

          // ── Validate driver is not already booked for this time ────────────
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

          // Driver value for SQL (NULL if self-drive)
          $driver_sql = $driver_id_val > 0 ? $driver_id_val : 'NULL';

          // ── Step 1: Insert into booking_master ──────────────────────────────
          $qry = mysqli_query($con,
            "INSERT INTO booking_master
               (ui, car_id, driver_id, pickup_datetime, actual_return_datetime, pan_aadhar_no, booking_status, created_at)
             VALUES
               ($uid_safe, $car_safe, $driver_sql, '$pickup_datetime', '$actual_return_datetime', '$pan_aadhar_no', 'Pending', NOW())"
          );

          if($qry){
            $booking_id = mysqli_insert_id($con); // get the new booking_id

            // ── Step 2: Calculate amounts from car_pricing ───────────────────
            $ppd = 0; $pph = 0; $security_deposit = 0; $late_fee_ph = 0;

            $priceQ = mysqli_query($con,
              "SELECT price_per_day, price_per_hour, security_deposit, late_fee_per_hour
               FROM car_pricing WHERE car_id = $car_safe ORDER BY pricing_id DESC LIMIT 1"
            );
            if($priceQ && mysqli_num_rows($priceQ) > 0){
              $priceRow       = mysqli_fetch_assoc($priceQ);
              $ppd            = floatval($priceRow['price_per_day']    ?? 0);
              $pph            = floatval($priceRow['price_per_hour']   ?? 0);
              $security_deposit = floatval($priceRow['security_deposit']  ?? 0);
              $late_fee_ph    = floatval($priceRow['late_fee_per_hour'] ?? 0);
            }

            // ── Step 3: Calculate duration ───────────────────────────────────
            $total_days  = 1;
            $extra_hours = 0;
            $day_amount  = 0;
            $hour_amount = 0;

            if(!empty($actual_return_datetime) && $actual_return_datetime !== '0000-00-00T00:00'){
              try {
                $pickup_dt = new DateTime($pickup_datetime);
                $return_dt = new DateTime($actual_return_datetime);
                $diff      = $pickup_dt->diff($return_dt);
                $total_days  = max(0, $diff->days);
                $extra_hours = $diff->h + ($diff->i > 0 ? 1 : 0);
              } catch(Exception $e) {
                $total_days = 1;
              }
            }

            $day_amount  = $total_days  * $ppd;
            $hour_amount = ($extra_hours > 0 && $pph > 0) ? $extra_hours * $pph : 0;
            $base_amount = $day_amount + $hour_amount;
            if($base_amount <= 0) $base_amount = $ppd; // fallback: at least 1 day

            // ── Step 4: Driver charge = 10% of base ─────────────────────────
            $driver_amount = ($driver_id_val > 0) ? round($base_amount * 0.10, 2) : 0;

            // ── Step 5: GST 18% on (base + driver) ──────────────────────────
            $taxable     = $base_amount + $driver_amount;
            $gst         = round($taxable * 0.18, 2);

            // ── Step 6: Total = taxable + GST + security deposit ─────────────
            $total_amount = round($taxable + $gst + $security_deposit, 2);

            // ── Step 7: Insert into booking_details ─────────────────────────
            $detailQ = mysqli_query($con,
              "INSERT INTO booking_details
                 (booking_id, car_id, ui, base_amount, driver_amount, security_deposit, total_amount, late_fee_per_hour, booking_status, created_at)
               VALUES
                 ($booking_id, $car_safe, $uid_safe,
                  $base_amount, $driver_amount, $security_deposit,
                  $total_amount, $late_fee_ph, 'Pending', NOW())"
            );

            if($detailQ){
              echo "<script>
                alert('Booking Confirmed! Your booking has been submitted successfully.');
                window.location.href = 'booking_details.php';
              </script>";
            } else {
              echo "<script>
                alert('Booking placed but pricing details could not be saved. Please contact support.');
                window.location.href = 'booking_details.php';
              </script>";
            }

          } else {
            echo "<script>alert('Something went wrong! Please try again.')</script>";
          }

          } // end driver_conflict else
        }
        ?>

        <form method="POST" action="#" id="bookingForm">

          <!-- User Name & Email -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">User Name</label>
              <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" name="user_name" class="form-control" placeholder="John Doe" value="<?php echo htmlspecialchars($row['uname']); ?>" readonly>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">User Email</label>
              <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="user_email" class="form-control" placeholder="john@email.com" value="<?php echo htmlspecialchars($row['email']); ?>" readonly>
              </div>
            </div>
          </div>

          <!-- Car Brand & Car Name -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">Car Brand</label>
              <div class="input-wrap">
                <i class="fas fa-tag"></i>
                <span class="form-control"><?php echo htmlspecialchars($row['brand_name']); ?></span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Car Name</label>
              <div class="input-wrap">
                <i class="fas fa-car"></i>
                <span class="form-control"><?php echo htmlspecialchars($row['car_display_name']); ?></span>
              </div>
            </div>
          </div>

          <!-- PAN / Aadhaar -->
          <div class="form-group">
            <label class="form-label">PAN / Aadhaar Number</label>
            <div class="input-wrap">
              <i class="fas fa-id-card"></i>
              <input type="text" name="pan_aadhar_no" class="form-control"
                     placeholder="Enter PAN or Aadhaar"
                     pattern="[A-Za-z0-9]{8,20}"
                     title="Enter valid PAN or Aadhaar" required>
            </div>
          </div>

          <!-- Pickup & Return DateTime (BEFORE driver so JS can react) -->
          <div class="row-two">
            <div class="form-group">
              <label class="form-label">Pickup Date &amp; Time</label>
              <div class="input-wrap">
                <i class="fas fa-arrow-right"></i>
                <input type="datetime-local" name="pickup_datetime" id="pickup_datetime" class="form-control" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Return Date &amp; Time</label>
              <div class="input-wrap">
                <i class="fas fa-arrow-left"></i>
                <input type="datetime-local" name="actual_return_datetime" id="actual_return_datetime" class="form-control">
              </div>
            </div>
          </div>

          <!-- Driver -->
          <div class="form-group">
            <label class="form-label">Driver Name</label>
            <div class="input-wrap">
              <i class="fas fa-steering-wheel"></i>
              <select name="driver_id" id="driver_select" class="form-select">
                <option value="">-- Select dates first to see available drivers --</option>
              </select>
            </div>
            <div class="driver-notice info" id="driverNotice">
              <i class="fas fa-info-circle"></i> Showing drivers available for your selected dates.
            </div>
            <div class="driver-notice warn" id="driverWarn">
              <i class="fas fa-exclamation-triangle"></i> No drivers available for the selected dates. You can still proceed with Self Drive.
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-submit" name="booking">
            <i class="fas fa-check" style="margin-right:7px;"></i>Confirm Booking
          </button>
        </form>
      </div>

    </div>
  </div>

  <!-- Pass PHP data to JavaScript -->
  <script>
    // All active drivers
    const ALL_DRIVERS = <?php echo json_encode($all_drivers); ?>;

    // All approved bookings with drivers (for overlap checking)
    const BOOKED_SLOTS = <?php echo json_encode($booked_drivers); ?>;

    /**
     * Returns true if two date ranges overlap.
     * Range A: [aStart, aEnd)   Range B: [bStart, bEnd)
     * Overlap condition: aStart < bEnd && aEnd > bStart
     */
    function rangesOverlap(aStart, aEnd, bStart, bEnd) {
      return aStart < bEnd && aEnd > bStart;
    }

    /**
     * Given pickup + return datetime strings, return array of driver_ids
     * that are NOT available (already booked in Approved bookings).
     */
    function getUnavailableDriverIds(pickupStr, returnStr) {
      if (!pickupStr) return [];

      const pickupMs = new Date(pickupStr).getTime();
      // If no return set, assume 24 hours for conflict checking
      const returnMs = returnStr
        ? new Date(returnStr).getTime()
        : pickupMs + 86400000;

      const unavailable = new Set();
      BOOKED_SLOTS.forEach(function(slot) {
        if (!slot.driver_id) return;
        const slotStart = new Date(slot.pickup_datetime).getTime();
        // If slot has no return, assume 24h from pickup
        const slotEndRaw = slot.actual_return_datetime;
        const slotEnd = (slotEndRaw && slotEndRaw !== '0000-00-00 00:00:00')
          ? new Date(slotEndRaw).getTime()
          : slotStart + 86400000;

        if (rangesOverlap(pickupMs, returnMs, slotStart, slotEnd)) {
          unavailable.add(parseInt(slot.driver_id));
        }
      });
      return unavailable;
    }

    /**
     * Rebuild the driver <select> based on current pickup/return values.
     */
    function updateDriverDropdown() {
      const pickupVal = document.getElementById('pickup_datetime').value;
      const returnVal = document.getElementById('actual_return_datetime').value;
      const select    = document.getElementById('driver_select');
      const notice    = document.getElementById('driverNotice');
      const warn      = document.getElementById('driverWarn');

      // Clear existing options
      select.innerHTML = '';

      if (!pickupVal) {
        // No dates selected yet — show placeholder
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '-- Select dates first to see available drivers --';
        select.appendChild(opt);
        notice.classList.remove('show');
        warn.classList.remove('show');
        return;
      }

      const unavailableIds = getUnavailableDriverIds(pickupVal, returnVal);

      // Self-drive option always first
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
          // Show as unavailable but disabled
          opt.textContent = driver.driver_name + exp + ' — Unavailable on selected dates';
          opt.disabled = true;
          opt.style.color = 'rgba(255,255,255,0.3)';
        } else {
          opt.textContent = driver.driver_name + exp;
          availableCount++;
        }
        select.appendChild(opt);
      });

      // Show/hide notices
      notice.classList.toggle('show', availableCount > 0);
      warn.classList.toggle('show', availableCount === 0 && ALL_DRIVERS.length > 0);
    }

    // Attach listeners
    document.getElementById('pickup_datetime').addEventListener('change', updateDriverDropdown);
    document.getElementById('actual_return_datetime').addEventListener('change', updateDriverDropdown);

    // Validate on form submit: if selected driver became unavailable somehow
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
      const pickupVal  = document.getElementById('pickup_datetime').value;
      const returnVal  = document.getElementById('actual_return_datetime').value;
      const driverSel  = document.getElementById('driver_select');
      const driverId   = parseInt(driverSel.value);

      if (driverId > 0 && pickupVal) {
        const unavailableIds = getUnavailableDriverIds(pickupVal, returnVal);
        if (unavailableIds.has(driverId)) {
          e.preventDefault();
          alert('The selected driver is not available for your chosen dates. Please select another driver or choose Self Drive.');
          updateDriverDropdown();
        }
      }
    });

    // Run once on load (in case browser restores values)
    updateDriverDropdown();
  </script>

</body>
</html>