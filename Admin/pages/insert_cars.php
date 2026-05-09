<?php 
    include('connect.php');
    session_name('admin_session');
    session_start();
    include('../components/navbar.php'); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Categories</title>

    <style>
        /* ---- YOUR EXISTING CSS (UNCHANGED) ---- */

        html, body {
            height: 100%;
            /* overflow: hidden; */
        }

        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --border-color: #dee2e6;
            --focus-color: rgba(13, 110, 253, 0.25);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content {
                min-height: calc(100vh - 70px); /* adjust if navbar height differs */
                display: flex;
                align-items: center;
                justify-content: center; /* horizontal center */
        }

        .main-container {
                width: 100%;
        }

        .form-card {
            max-width: 600px;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-footer {
            background: #f8f9fa;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <script>
        function getModel(brand_id)
        {
            var xhr = new XMLHttpRequest();
            xhr.open("GET","getModel.php?brand_id=" + brand_id, true);
            xhr.onload = function(){
                document.getElementById("ddlmodel").innerHTML = this.responseText;
            };
            xhr.send();
        }

  function calculatePricing() {

    // Brand name from selected option
    const brandSelect = document.getElementById("ddlbrand");
    const brandName = brandSelect.options[brandSelect.selectedIndex]?.text || "";

    const pricePerHour = parseFloat(document.querySelector("[name='price_per_hour']").value) || 0;

    let securityDeposit = 0;

    // ---- SWITCH CASE FOR SECURITY DEPOSIT ----
    switch (brandName) {

    case 'Mercedes-Benz':
    case 'BMW':
    case 'Audi':
    case 'Volvo':
        $security_deposite = 15000;
        break;

    case 'Land Rover':
        $security_deposite = 20000;
        break;

    case 'Toyota':
        $security_deposite = 10000;
        break;

    case 'Mahindra':
    case 'TATA':
    case 'Hyundai':
    case 'Kia':
        $security_deposite = 5000;
        break;

    default:
        $security_deposite = 4000;
}

    // ---- LATE FEE = PRICE PER HOUR + 10% ----
    const lateFee = pricePerHour + (pricePerHour * 0.10);

    // ---- SET VALUES ----
    document.getElementById("security_deposite").value = securityDeposit;
    document.getElementById("late_fee_per_hour").value = Math.round(lateFee);
  }

  // FIX 3: Auto-format number plate as user types → GJ 05 AB 1234
  function formatNumberPlate(input) {
      let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
      let formatted = '';

      if (val.length > 0)  formatted  = val.substring(0, 2);           // GJ
      if (val.length > 2)  formatted += ' ' + val.substring(2, 4);    // 05
      if (val.length > 4)  formatted += ' ' + val.substring(4, 6);    // AB
      if (val.length > 6)  formatted += ' ' + val.substring(6, 10);   // 1234

      input.value = formatted;
  }
    </script>

</head>

<body>

<!-- ===== MAIN PANEL CONTENT ===== -->
<div class="main-panel1">
    <div class="content mt-3">
        <div class="container mt-5">

 <?php
if (isset($_POST['insbrand'])) {

    // ---------- FORM DATA ----------
    $brand_id = $_POST['ddlbrand'];
    $model_id = $_POST['ddlmodel'];
    $car_display_name = $_POST['car_display_name'];
    $car_number_plate = $_POST['car_number_plate'];
    $gear_type = $_POST['gear_type'];
    $fuel_type = $_POST['fuel_type'];
    $seating_capacity = $_POST['seating_capacity'];
    $car_desc = $_POST['car_desc'];
    $price_per_hour = $_POST['price_per_hour'];
    $price_per_day = $_POST['price_per_day'];
    // FIX 2: effective_from is always today, not from form
    $effective_from = date('Y-m-d');

    $car_primary_image = $_FILES['car_primary_image']['name'];
    $tmp = $_FILES['car_primary_image']['tmp_name'];
    $dst = "./images/car_images/".$car_primary_image;

    // ---------- AUTO PRICING ----------
    // ---- FETCH BRAND NAME ----
$brandQuery = mysqli_query($con, "SELECT brand_name FROM brand_master WHERE brand_id='$brand_id'");
$brandRow = mysqli_fetch_assoc($brandQuery);
$brandName = $brandRow['brand_name'];

// ---- SECURITY DEPOSIT SWITCH ----
switch ($brandName) {

    case 'Audi':
    case 'BMW':
    case 'Mercedes-Benz':
    case 'Volvo':
        $security_deposite = 200000;
        break;

    case 'Land Rover':
        $security_deposite = 250000;
        break;

    case 'Toyota':
        $security_deposite = 150000;
        break;

    case 'Mahindra':
    case 'TATA':
    case 'Hyundai':
    case 'Kia':
        $security_deposite = 100000;
        break;

    default:
        $security_deposite = 0;
}

// ---- LATE FEE = PRICE PER HOUR + 10% ----
$late_fee_per_hour = $price_per_hour + ($price_per_hour * 0.10);

    // FIX 1: DUPLICATE CHECK — same display name OR same number plate
    $dn = mysqli_real_escape_string($con, $car_display_name);
    $np = mysqli_real_escape_string($con, $car_number_plate);
    $dupCheck = mysqli_query($con, "SELECT car_id FROM car_master WHERE car_display_name='$dn' OR car_number_plate='$np'");
    if (mysqli_num_rows($dupCheck) > 0) {
        echo "<script>alert('Car with the same name or number plate already exists!');</script>";
    } else {

    // ---------- IMAGE UPLOAD ----------
    if (!move_uploaded_file($tmp, $dst)) {
        die("<div class='alert alert-danger'>Image upload failed</div>");
    }

    // ---------- INSERT INTO car_master ----------
    $insertCar = "
        INSERT INTO car_master SET
            brand_id = '$brand_id',
            model_id = '$model_id',
            car_display_name = '$car_display_name',
            car_number_plate = '$car_number_plate',
            gear_type = '$gear_type',
            fuel_type = '$fuel_type',
            seating_capacity = '$seating_capacity',
            car_description = '$car_desc',
            primary_image = '$car_primary_image',
            is_enabled = 1
    ";

    if (!mysqli_query($con, $insertCar)) {
        die("<div class='alert alert-danger'>
            Car insert failed: ".mysqli_error($con)."
        </div>");
    }

    // ---------- FETCH car_id (SAFE METHOD) ----------
    $car_id = mysqli_insert_id($con);

    // ---------- INSERT INTO car_pricing ----------
    $insertPricing = "
        INSERT INTO car_pricing SET
            car_id = '$car_id',
            price_per_hour = '$price_per_hour',
            price_per_day = '$price_per_day',
            security_deposit = '$security_deposite',
            late_fee_per_hour = '$late_fee_per_hour',
            effective_from = '$effective_from'
    ";

    if (!mysqli_query($con, $insertPricing)) {
        die("<div class='alert alert-danger'>
            Pricing insert failed: ".mysqli_error($con)."
        </div>");
    }

    echo "
    <div class='alert alert-success alert-dismissible fade show w-75 mx-auto text-center'>
        ✅ Car & Pricing Added Successfully!
        <a href='view_cars.php'>View</a>
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";

    } // end duplicate check else
}
?>



            <!-- CENTERED FORM -->

            <div class="container-fluid">
              <div class="row justify-content-center">
                <div class="col-12">

                  <div class="card shadow-sm rounded-4">

                    <!-- HEADER -->
                    <div class="card-body border-bottom bg-light">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <h4 class="fw-bold mb-0">
                            <i class="bi bi-car-front-fill text-primary"></i> Insert Cars
                          </h4>
                          <small class="text-muted">Add cars to your system</small>
                        </div>
                        <span class="badge bg-primary">Admin Panel</span>
                      </div>
                    </div>
                    <div class="card-body">
                      <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <!-- VEHICLE INFO -->
                          <div class="mb-4">
                              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                                  Vehicle Information
                              </h6>
                              <div class="row mt-3">

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Brand *</label>
                                      <select name="ddlbrand" id="ddlbrand" class="form-select"
                                              onchange="getModel(this.value); calculatePricing();">
                                          <option value="">Select Brand</option>
                                          <?php
                                              $q = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
                                              while ($row = mysqli_fetch_assoc($q)) {
                                                  echo "<option value='".$row['brand_id']."'>".$row['brand_name']."</option>";
                                              }
                                          ?>
                                      </select>
                                      <span id="err_brand" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Model *</label>
                                      <select name="ddlmodel" id="ddlmodel" class="form-select">
                                          <option value="">Select Model</option>
                                      </select>
                                      <span id="err_model" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Display Name *</label>
                                      <input type="text" name="car_display_name" class="form-control">
                                      <span id="err_display" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Number Plate *</label>
                                      <!-- FIX 3: oninput triggers auto-format, placeholder shows expected format -->
                                      <input type="text" name="car_number_plate" class="form-control"
                                             maxlength="13" placeholder="GJ 05 AB 1234"
                                             oninput="formatNumberPlate(this)">
                                      <span id="err_plate" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                              </div>
                          </div>

                          <!-- MEDIA & CAPACITY -->
                          <div class="mb-4">
                              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                                  Media & Capacity
                              </h6>
                              <div class="row mt-3">

                                  <div class="col-lg-3 mb-3">
                                    <label class="form-label fw-semibold">Seating *</label>
                                    <select name="seating_capacity" class="form-select">
                                        <option value="">Select Seating</option>
                                        <option value="2">2</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                    </select>
                                    <span id="err_seating" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Primary Image *</label>
                                      <input type="file" name="car_primary_image" class="form-control" accept="image/*">
                                      <span id="err_image" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Gear Type *</label><br>
                                      <div class="form-check form-check-inline">
                                          <input class="form-check-input" type="radio" name="gear_type" value="Manual"> Manual
                                      </div>
                                      <div class="form-check form-check-inline">
                                          <input class="form-check-input" type="radio" name="gear_type" value="Automatic"> Automatic
                                      </div>
                                      <span id="err_gear" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-3 mb-3">
                                      <label class="form-label fw-semibold">Fuel Type *</label><br>
                                      <div class="form-check form-check-inline">
                                          <input class="form-check-input" type="radio" name="fuel_type" value="Petrol"> Petrol
                                      </div>
                                      <div class="form-check form-check-inline">
                                          <input class="form-check-input" type="radio" name="fuel_type" value="Diesel"> Diesel
                                      </div>
                                      <div class="form-check form-check-inline">
                                          <input class="form-check-input" type="radio" name="fuel_type" value="Electric"> Electric
                                      </div>
                                      <span id="err_fuel" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                              </div>
                          </div>

                          <!-- DESCRIPTION -->
                          <div class="mb-4">
                              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                                  Description
                              </h6>
                              <textarea name="car_desc" class="form-control mt-3" rows="2"></textarea>
                              <span id="err_desc" class="text-danger small d-block mt-1" style="display:none!important"></span>
                          </div>

                          <!-- PRICING -->
                          <div class="mb-4">
                              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                                  Pricing Details
                              </h6>
                              <div class="row mt-3">

                                  <div class="col-lg-2 mb-3">
                                      <label class="form-label fw-semibold">Hour *</label>
                                      <input type="number" name="price_per_hour" class="form-control" oninput="calculatePricing()" min="0">
                                      <span id="err_hour" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-2 mb-3">
                                      <label class="form-label fw-semibold">Day *</label>
                                      <input type="number" name="price_per_day" class="form-control" min="0">
                                      <span id="err_day" class="text-danger small d-block mt-1" style="display:none!important"></span>
                                  </div>

                                  <div class="col-lg-2 mb-3">
                                      <label class="form-label fw-semibold">Late Fee</label>
                                      <input type="number" name="late_fee_per_hour" id="late_fee_per_hour" class="form-control" readonly>
                                  </div>

                                  <!-- FIX 2: Effective date field removed from form; date is set to today in PHP -->

                                  <div class="col-lg-2 d-flex align-items-end mb-3">
                                      <button type="submit" name="insbrand" class="btn btn-primary w-100">
                                          Save
                                      </button>
                                  </div>

                              </div>
                          </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- END CENTERED FORM -->
        </div>
    </div>
</div>

<script>
  function validateForm() {

    let valid = true;

    // ── HELPER: show/clear error ──
    function showError(id, msg) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.style.display = "block";
        valid = false;
    }

    function clearError(id) {
        const el = document.getElementById(id);
        el.textContent = "";
        el.style.display = "none";
    }

    // ── CLEAR ALL FIRST ──
    const errorIds = [
        "err_brand", "err_model", "err_display", "err_plate",
        "err_seating", "err_image", "err_gear", "err_fuel",
        "err_desc", "err_hour", "err_day"
    ];
    errorIds.forEach(id => clearError(id));

    // ── REGEX ──
    const lettersNumHyphen  = /^[a-zA-Z0-9\- ]+$/;
    const mustHaveLetter    = /[a-zA-Z]/;
    const descAllowed       = /^[a-zA-Z0-9\-\., ]+$/;
    const positiveNumber    = /^\d+(\.\d+)?$/;
    // FIX 3: Indian number plate format: 2 letters, space, 2 digits, space, 2 letters, space, 4 digits
    const plateFormat       = /^[A-Z]{2} \d{2} [A-Z]{2} \d{4}$/;

    // ── 1. BRAND ──
    const brand = document.getElementById("ddlbrand").value;
    if (brand === "") {
        showError("err_brand", "Please select a brand.");
    }

    // ── 2. MODEL ──
    const model = document.getElementById("ddlmodel").value;
    if (model === "" || model === null) {
        showError("err_model", "Please select a model.");
    }

    // ── 3. DISPLAY NAME ──
    const displayName = document.querySelector("[name='car_display_name']").value.trim();
    if (displayName === "") {
        showError("err_display", "Display name is required.");
    } else if (!lettersNumHyphen.test(displayName)) {
        showError("err_display", "Only letters, numbers, spaces and '-' allowed.");
    } else if (!mustHaveLetter.test(displayName)) {
        showError("err_display", "Display name cannot be numbers only.");
    } else if (displayName.length < 3 || displayName.length > 50) {
        showError("err_display", "Display name must be 3–50 characters.");
    }

    // ── 4. NUMBER PLATE ──
    const plate = document.querySelector("[name='car_number_plate']").value.trim();
    if (plate === "") {
        showError("err_plate", "Number plate is required.");
    } else if (!plateFormat.test(plate)) {
        // FIX 3: validate against correct Indian plate format
        showError("err_plate", "Format must be: GJ 05 AB 1234");
    }

    // ── 5. SEATING CAPACITY ──
    const allowedSeating = ["2", "4", "5", "6", "7"];
    const seating = document.querySelector("[name='seating_capacity']").value;
    if (seating === "") {
        showError("err_seating", "Please select seating capacity.");
    } else if (!allowedSeating.includes(seating)) {
        showError("err_seating", "Invalid seating capacity selected.");
    }

    // ── 6. PRIMARY IMAGE ──
    const imgInput = document.querySelector("[name='car_primary_image']");
    if (imgInput.files.length === 0) {
        showError("err_image", "Please upload a primary image.");
    } else {
        const file     = imgInput.files[0];
        const ext      = file.name.split('.').pop().toLowerCase();
        const allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        const maxSize  = 2 * 1024 * 1024; // 2MB

        if (!allowed.includes(ext)) {
            showError("err_image", "Only JPG, PNG, WEBP allowed.");
        } else if (file.size > maxSize) {
            showError("err_image", "Image must be under 2MB.");
        }
    }

    // ── 7. GEAR TYPE ──
    const gear = document.querySelector("input[name='gear_type']:checked");
    if (!gear) {
        showError("err_gear", "Please select gear type.");
    }

    // ── 8. FUEL TYPE ──
    const fuel = document.querySelector("input[name='fuel_type']:checked");
    if (!fuel) {
        showError("err_fuel", "Please select fuel type.");
    }

    // ── 9. DESCRIPTION ──
    const desc = document.querySelector("[name='car_desc']").value.trim();
    if (desc === "") {
        showError("err_desc", "Description is required.");
    } else if (!descAllowed.test(desc)) {
        showError("err_desc", "Only letters, numbers, spaces, '.', ',' and '-' allowed.");
    } else if (desc.length < 5 || desc.length > 500) {
        showError("err_desc", "Description must be 5–500 characters.");
    }

    // ── 10. PRICE PER HOUR ──
    const hour = document.querySelector("[name='price_per_hour']").value.trim();
    if (hour === "") {
        showError("err_hour", "Price per hour is required.");
    } else if (!positiveNumber.test(hour) || parseFloat(hour) < 0) {
        showError("err_hour", "Enter a valid positive price.");
    }

    // ── 11. PRICE PER DAY ──
    const day = document.querySelector("[name='price_per_day']").value.trim();
    if (day === "") {
        showError("err_day", "Price per day is required.");
    } else if (!positiveNumber.test(day) || parseFloat(day) < 0) {
        showError("err_day", "Enter a valid positive price.");
    }

    // FIX 2: err_date removed — effective date is no longer a form field

    return valid;
  }
</script>

<?php include('../components/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>