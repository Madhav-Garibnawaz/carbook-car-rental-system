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
    <title>Edit Cars</title>

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

        case "Audi":
        case "BMW":
        case "Mercedes-Benz":
        case "Volvo":
            securityDeposit = 200000;
            break;

        case "Land Rover":
            securityDeposit = 250000;
            break;

        case "Toyota":
            securityDeposit = 150000;
            break;

        case "Mahindra":
        case "TATA":
        case "Hyundai":
        case "Kia":
            securityDeposit = 100000;
            break;

        default:
            securityDeposit = 0;
    }

    // ---- LATE FEE = PRICE PER HOUR + 10% ----
    const lateFee = pricePerHour + (pricePerHour * 0.10);

    // ---- SET VALUES ----
    document.getElementById("security_deposite").value = securityDeposit;
    document.getElementById("late_fee_per_hour").value = Math.round(lateFee);
  }
    </script>

</head>

<body>

<!-- ===== MAIN PANEL CONTENT ===== -->
<div class="main-panel1">
    <div class="content mt-3">
        <div class="container mt-5">

 <?php
 $car_id = $_GET['id'] ?? 0;

$carQuery = mysqli_query($con, "
    SELECT c.*, p.price_per_hour, p.price_per_day,
           p.security_deposit, p.late_fee_per_hour, p.effective_from
    FROM car_master c
    JOIN car_pricing p ON c.car_id = p.car_id
    WHERE c.car_id = $car_id
");

$car = mysqli_fetch_assoc($carQuery);

if (!$car) {
    echo "<script>
        alert('Invalid Car ID');
        window.location.href='view_cars.php';
    </script>";
    exit;
}

if (isset($_POST['insbrand'])) {

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
    $effective_from = $_POST['effective_from'];

    /* ---------- IMAGE ---------- */
    if (!empty($_FILES['car_primary_image']['name'])) {
        $car_primary_image = $_FILES['car_primary_image']['name'];
        move_uploaded_file(
            $_FILES['car_primary_image']['tmp_name'],
            "./images/car_images/" . $car_primary_image
        );
    } else {
        $car_primary_image = $car['primary_image'];
    }

    /* ---------- FETCH BRAND NAME ---------- */
    $brandQuery = mysqli_query($con, "SELECT brand_name FROM brand_master WHERE brand_id='$brand_id'");
    $brandRow = mysqli_fetch_assoc($brandQuery);
    $brandName = $brandRow['brand_name'];

    /* ---------- SWITCH CASE (UNCHANGED) ---------- */
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

    $late_fee_per_hour = $price_per_hour + ($price_per_hour * 0.10);

    /* ---------- UPDATE car_master ---------- */
    mysqli_query($con, "
        UPDATE car_master SET
            brand_id = '$brand_id',
            model_id = '$model_id',
            car_display_name = '$car_display_name',
            car_number_plate = '$car_number_plate',
            gear_type = '$gear_type',
            fuel_type = '$fuel_type',
            seating_capacity = '$seating_capacity',
            car_description = '$car_desc',
            primary_image = '$car_primary_image'
        WHERE car_id = '$car_id'
    ");

    /* ---------- UPDATE car_pricing ---------- */
    mysqli_query($con, "
        UPDATE car_pricing SET
            price_per_hour = '$price_per_hour',
            price_per_day = '$price_per_day',
            security_deposit = '$security_deposite',
            late_fee_per_hour = '$late_fee_per_hour',
            effective_from = '$effective_from'
        WHERE car_id = '$car_id'
    ");

    echo "<script>
        alert('Car updated successfully');
        window.location.href='view_cars.php';
    </script>";
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
                <i class="bi bi-car-front-fill text-primary"></i> Edit Cars
              </h4>
              <small class="text-muted">Add cars to your system</small>
            </div>
            <span class="badge bg-primary">Admin Panel</span>
          </div>
        </div>

        <div class="card-body">

          <form method="POST" enctype="multipart/form-data">

            <!-- VEHICLE INFO -->
            <div class="mb-4">
              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                Vehicle Information
              </h6>

              <div class="row mt-3">
                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Brand</label>
                  <select name="ddlbrand" id="ddlbrand" class="form-select"
                        onchange="getModel(this.value); calculatePricing();" required>
                        <option value="">Select Brand</option>
                        <?php
                        $q = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1");
                        while ($row = mysqli_fetch_assoc($q)) {
                            $selected = ($row['brand_id'] == $car['brand_id']) ? 'selected' : '';
                            echo "<option value='{$row['brand_id']}' $selected>{$row['brand_name']}</option>";
                        }
                        ?>
                    </select>

                                    </div>

                                    <div class="col-lg-3 mb-3">
                                    <label class="form-label fw-semibold">Model</label>
                                    <select name="ddlmodel" id="ddlmodel" class="form-select" required>
                        <option value="">Select Model</option>

                        <?php
                        $m = mysqli_query($con, "
                            SELECT * FROM model_master 
                            WHERE brand_id = '{$car['brand_id']}'
                        ");

                        while ($r = mysqli_fetch_assoc($m)) {
                            $sel = ($r['model_id'] == $car['model_id']) ? 'selected' : '';
                            echo "<option value='{$r['model_id']}' $sel>{$r['model_name']}</option>";
                        }
                        ?>
                    </select>

                </div>

                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Display Name</label>
                  <input type="text" name="car_display_name" value="<?=$car['car_display_name']?>" class="form-control">
                </div>

                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Number Plate</label>
                  <input type="text" name="car_number_plate"
                    value="<?=$car['car_number_plate']?>" class="form-control">
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
                  <label class="form-label fw-semibold">Seating</label>
                  <input type="number" name="seating_capacity"
                    value="<?=$car['seating_capacity']?>" class="form-control">
                </div>
                
                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Primary Image</label>
                  <input type="file" name="car_primary_image" class="form-control">
                    <small class="text-muted">Leave empty to keep current image</small>
                </div>
                
                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Gear Type</label><br>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="gear_type" value="Manual" <?=($car['gear_type']=='Manual')?'checked':''?> required> Manual
                  </div>
                  <div class="form-check form-check-inline">
                   <input class="form-check-input" type="radio" name="gear_type" value="Automatic" <?=($car['gear_type']=='Automatic')?'checked':''?>> Automatic
                  </div>
                </div>

                <div class="col-lg-3 mb-3">
                  <label class="form-label fw-semibold">Fuel Type</label><br>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fuel_type" value="Petrol"
                        <?=($car['fuel_type']=='Petrol')?'checked':''?> required> Petrol
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fuel_type" value="Diesel"
                        <?=($car['fuel_type']=='Diesel')?'checked':''?>> Diesel
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="fuel_type" value="Electric"
                        <?=($car['fuel_type']=='Electric')?'checked':''?>> Electric
                  </div>
                </div>
              </div>
            </div>

            <!-- DESCRIPTION -->
            <div class="mb-4">
              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                Description
              </h6>

              <textarea name="car_desc" class="form-control"><?=$car['car_description']?></textarea>
            </div>

            <!-- PRICING -->
            <div class="mb-4">
              <h6 class="fw-bold text-uppercase text-secondary border-start border-4 ps-2">
                Pricing Details
              </h6>

              <div class="row mt-3">
                <div class="col-lg-2 mb-3">
                  <label class="form-label fw-semibold">Hour</label>
                  <input type="number" name="price_per_hour" value="<?=$car['price_per_hour']?>" oninput="calculatePricing()">
                </div>

                <div class="col-lg-2 mb-3">
                  <label class="form-label fw-semibold">Day</label>
                  <input type="number" name="price_per_day" value="<?=$car['price_per_day']?>" class="form-control">
                </div>

                <div class="col-lg-2 mb-3">
                  <label class="form-label fw-semibold">Deposit</label>
                  <input type="number" name="security_deposite" id="security_deposite" class="form-control" readonly>
                </div>

                <div class="col-lg-2 mb-3">
                  <label class="form-label fw-semibold">Late Fee</label>
                  <input type="number" name="late_fee_per_hour" id="late_fee_per_hour" class="form-control" readonly>
                </div>

                <div class="col-lg-2 mb-3">
                  <label class="form-label fw-semibold">Effective</label>
                  <input type="date" name="effective_from" value="<?=$car['effective_from']?>">
                </div>

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
window.onload = function () {
    calculatePricing();
};
</script>


<?php include('../components/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>