<?php
include("connect.php");

$brand_id = isset($_GET['brand_id']) ? $_GET['brand_id'] : 'all';

$where = "WHERE c.is_enabled = 1 AND b.is_active = 1";

if ($brand_id !== 'all' && is_numeric($brand_id)) {
    $brand_id = (int)$brand_id;
    $where .= " AND c.brand_id = $brand_id";
}

$sql = "
    SELECT c.*, b.brand_name, p.price_per_day
    FROM car_master c
    INNER JOIN brand_master b ON c.brand_id = b.brand_id
    LEFT JOIN car_pricing p ON p.car_id = c.car_id
    $where
    ORDER BY c.car_id DESC
    LIMIT 9
";

$q = mysqli_query($con, $sql);

// DEBUG: temporarily uncomment below to see the error
// if (!$q) { echo mysqli_error($con); exit; }

if (!$q || mysqli_num_rows($q) == 0) {
    echo "<div class='col-12 text-center py-5'>
            <h5 class='mt-3 text-muted'>No cars available for this brand. (brand_id: $brand_id)</h5>
          </div>";
} else {
    while ($row = mysqli_fetch_assoc($q)) {
        $fuel  = !empty($row['fuel_type'])        ? $row['fuel_type']        : '—';
        $seats = !empty($row['seating_capacity']) ? $row['seating_capacity'] : '—';
        $gear  = !empty($row['gear_type'])        ? $row['gear_type']        : '—';
        $price = !empty($row['price_per_day'])    ? '&#8377;'.number_format($row['price_per_day'], 0).'/day' : 'Price N/A';
        echo '
        <div class="col-md-4 mb-4">
          <div class="car-wrap rounded" style="opacity:1;transform:none;">
            <div style="position:relative;background:linear-gradient(160deg,#f0faf4,#e8f8ef);padding:28px 20px 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;height:200px;">
              <span class="brand-badge">'.htmlspecialchars($row['brand_name']).'</span>
              <span class="price-badge">'.$price.'</span>
              <img src="../Admin/pages/images/car_images/'.htmlspecialchars($row['primary_image']).'"
                   class="img rounded"
                   alt="'.htmlspecialchars($row['car_display_name']).'"
                   style="max-width:260px;height:130px;object-fit:contain;position:relative;z-index:1;"
                   onerror="this.src=\'images/default_car.png\'">
              <div style="position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(to top,#fff,transparent);pointer-events:none"></div>
            </div>
            <div class="text" style="padding:16px 18px 18px;flex:1;display:flex;flex-direction:column;border-top:1px solid rgba(0,0,0,0.05);">
              <h2 class="mb-0" style="font-size:1.05rem;font-weight:700;color:#1a1a2e;margin-bottom:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'.htmlspecialchars($row['car_display_name']).'</h2>
              <div class="car-specs-strip" style="display:flex;border:1px solid rgba(0,0,0,0.07);border-radius:9px;overflow:hidden;margin-bottom:14px;background:#f8fffe;">
                <div class="car-spec-item" style="flex:1;text-align:center;padding:7px 4px;border-right:1px solid rgba(0,0,0,0.07);">
                  <span class="car-spec-val" style="display:block;font-size:0.78rem;font-weight:700;color:#1a1a2e;">'.htmlspecialchars($seats).'</span>
                  <span class="car-spec-lbl" style="display:block;font-size:0.58rem;color:#999;text-transform:uppercase;">Seats</span>
                </div>
                <div class="car-spec-item" style="flex:1;text-align:center;padding:7px 4px;border-right:1px solid rgba(0,0,0,0.07);">
                  <span class="car-spec-val" style="display:block;font-size:0.78rem;font-weight:700;color:#1a1a2e;">'.htmlspecialchars($fuel).'</span>
                  <span class="car-spec-lbl" style="display:block;font-size:0.58rem;color:#999;text-transform:uppercase;">Fuel</span>
                </div>
                <div class="car-spec-item" style="flex:1;text-align:center;padding:7px 4px;">
                  <span class="car-spec-val" style="display:block;font-size:0.78rem;font-weight:700;color:#1a1a2e;">'.htmlspecialchars($gear).'</span>
                  <span class="car-spec-lbl" style="display:block;font-size:0.58rem;color:#999;text-transform:uppercase;">Gear</span>
                </div>
              </div>
              <p style="margin:0;margin-top:auto;">
                <a href="booking.php?car_id='.$row['car_id'].'&brand_id='.$row['brand_id'].'"
                   class="btn btn-primary w-100 py-3 rounded"
                   style="background:#2ecc71;border:2px solid #2ecc71;font-weight:700;">Book Now</a>
              </p>
            </div>
          </div>
        </div>';
    }
}
?>