<?php
header('Content-Type: application/json');
include("connect.php");

$where = "WHERE c.is_enabled = 1 AND b.is_active = 1";

/* ── Search ── */
if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($con, trim($_GET['search']));
    $where .= " AND (
        c.car_display_name  LIKE '%$s%'
        OR b.brand_name     LIKE '%$s%'
        OR mm.model_name    LIKE '%$s%'
        OR cm.category_name LIKE '%$s%'
    )";
}

/* ── Brand ── */
if (!empty($_GET['brand'])) {
    $ids = implode(',', array_map('intval', explode(',', $_GET['brand'])));
    $where .= " AND c.brand_id IN ($ids)";
}

/* ── Category by NAME (SUV of Tata + SUV of Audi = one "SUV" filter) ── */
if (!empty($_GET['category'])) {
    $names = array_map(function($n) use ($con) {
        return "'".mysqli_real_escape_string($con, trim($n))."'";
    }, explode(',', $_GET['category']));
    $where .= " AND cm.category_name IN (".implode(',', $names).")";
}

/* ── Seater ── */
if (!empty($_GET['seater'])) {
    $seats = implode(',', array_map('intval', explode(',', $_GET['seater'])));
    $where .= " AND c.seating_capacity IN ($seats)";
}

/* ── Fuel ── */
if (!empty($_GET['fuel'])) {
    $fuels = array_map(function($f) use ($con) {
        return "'".mysqli_real_escape_string($con, trim($f))."'";
    }, explode(',', $_GET['fuel']));
    $where .= " AND c.fuel_type IN (".implode(',', $fuels).")";
}

/* ── Gear ── */
if (!empty($_GET['gear'])) {
    $gears = array_map(function($g) use ($con) {
        return "'".mysqli_real_escape_string($con, trim($g))."'";
    }, explode(',', $_GET['gear']));
    $where .= " AND c.gear_type IN (".implode(',', $gears).")";
}

/* ── Base query ── */
$baseSQL = "
    FROM car_master c
    INNER JOIN brand_master b   ON c.brand_id  = b.brand_id
    LEFT JOIN  model_master mm  ON c.model_id  = mm.model_id
    LEFT JOIN  category_master cm ON mm.category_id = cm.category_id
    LEFT JOIN  car_pricing p    ON p.car_id    = c.car_id
    $where
";

/* ── Total count ── */
$countResult = mysqli_query($con, "SELECT COUNT(DISTINCT c.car_id) as total $baseSQL");
$total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;

/* ── Pagination ── */
$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 9;
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* ── Fetch page ── */
$q = mysqli_query($con, "
    SELECT DISTINCT c.car_id, c.brand_id, c.car_display_name, c.primary_image,
                    c.fuel_type, c.seating_capacity, c.gear_type,
                    b.brand_name, p.price_per_day
    $baseSQL
    ORDER BY c.car_id DESC
    LIMIT $offset, $limit
");

if (!$q) {
    echo json_encode(['total'=>0,'showing'=>0,'html'=>'','error'=>mysqli_error($con)]);
    exit;
}

$html = ''; $showing = 0;

while ($row = mysqli_fetch_assoc($q)) {
    $showing++;
    $fuel  = !empty($row['fuel_type'])        ? htmlspecialchars($row['fuel_type'])        : '—';
    $seats = !empty($row['seating_capacity']) ? htmlspecialchars($row['seating_capacity']) : '—';
    $gear  = !empty($row['gear_type'])        ? htmlspecialchars($row['gear_type'])        : '—';
    $price = !empty($row['price_per_day'])
             ? '&#8377;'.number_format($row['price_per_day'],0).'/day' : 'N/A';
    $bn  = htmlspecialchars($row['brand_name']);
    $cn  = htmlspecialchars($row['car_display_name']);
    $img = htmlspecialchars($row['primary_image']);
    $cid = (int)$row['car_id'];
    $bid = (int)$row['brand_id'];

    $html .= "
    <div class='col-md-4 mb-4'>
      <div class='car-wrap rounded' style='opacity:1;transform:none;'>
        <div style='position:relative;background:linear-gradient(160deg,#f0faf4,#e8f8ef);padding:28px 20px 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;height:200px;'>
          <span class='brand-badge'>$bn</span>
          <span class='price-badge'>$price</span>
          <img src='../Admin/pages/images/car_images/$img' class='img rounded' alt='$cn'
               style='max-width:260px;height:130px;object-fit:contain;position:relative;z-index:1;'
               onerror='this.style.opacity=\".15\"'>
          <div style='position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(to top,#fff,transparent);pointer-events:none'></div>
        </div>
        <div class='text'>
          <h2 class='mb-0'>$cn</h2>
          <div class='car-specs-strip'>
            <div class='car-spec-item'><span class='car-spec-val'>$seats</span><span class='car-spec-lbl'>Seats</span></div>
            <div class='car-spec-item'><span class='car-spec-val'>$fuel</span><span class='car-spec-lbl'>Fuel</span></div>
            <div class='car-spec-item'><span class='car-spec-val'>$gear</span><span class='car-spec-lbl'>Gear</span></div>
          </div>
          <p class='card-footer p-0 mt-auto'>
            <a href='booking.php?car_id=$cid&brand_id=$bid' class='btn btn-primary w-100 py-3 rounded'>Book Now</a>
          </p>
        </div>
      </div>
    </div>";
}

echo json_encode(['total'=>$total,'showing'=>$showing,'html'=>$html]);
?>