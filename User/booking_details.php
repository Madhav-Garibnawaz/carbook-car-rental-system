<?php
// Session guard — header.php may already have started it
if (session_status() === PHP_SESSION_NONE) { 
session_name('user_session');  
session_start(); }
require("connect.php");

// --- Haversine Formula for Distance ---
function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earth_radius * $c, 2);
}

function safeQuery($con, $sql, $label) {
    $r = mysqli_query($con, $sql);
    if ($r === false) {
        die("<div style='font-family:monospace;padding:30px;background:#1a0005;color:#ff6b6b;border:2px solid #ff4444;margin:20px;border-radius:8px'>
            <b>Query failed: $label</b><br><br><b>SQL:</b> ".htmlspecialchars($sql)."<br><br>
            <b>MySQL Error:</b> ".htmlspecialchars(mysqli_error($con))."</div>");
    }
    return $r;
}

$uid = $_SESSION['user_id'] ?? null;
if (!isset($uid)) { header("location: register.php"); exit; }
$uid_safe = intval($uid);

// ── Cancel Logic ──────────────────────────────────────────────────────────────
if (isset($_POST['cancel_booking'])) {
    $bid    = intval($_POST['booking_id']);
    $chkQ   = mysqli_query($con, "SELECT booking_status FROM booking_master WHERE booking_id=$bid AND ui=$uid_safe");
    $chkRow = mysqli_fetch_assoc($chkQ);
    if ($chkRow && $chkRow['booking_status'] === 'Rejected') {
        $_SESSION['toast'] = ['type'=>'error','msg'=>'This booking was rejected and cannot be modified.'];
        header("location: booking_details.php"); exit;
    }
   if ($chkRow && $chkRow['booking_status'] === 'Approved') {
    // Check if any payment has been made
    $payCheck = mysqli_query($con, "SELECT COUNT(*) as cnt FROM payment_master WHERE booking_id=$bid");
    $payRow = mysqli_fetch_assoc($payCheck);
    if ($payRow['cnt'] > 0) {
        $_SESSION['toast'] = ['type'=>'error','msg'=>'Cannot cancel a booking with existing payments. Please contact support.'];
        header("location: booking_details.php"); exit;
    }
    // Allow cancel if no payment made — update SQL to also allow Approved
    $cancelQ = mysqli_query($con, "UPDATE booking_master SET booking_status='Cancelled' WHERE booking_id=$bid AND ui=$uid_safe AND booking_status IN ('Pending','Approved')");
    // ... rest of logic
}$_SESSION['toast'] = ($cancelQ && mysqli_affected_rows($con) > 0)
        ? ['type'=>'cancel','msg'=>'Booking has been cancelled successfully.']
        : ['type'=>'error','msg'=>'Could not cancel. Only pending bookings can be cancelled.'];
    header("location: booking_details.php"); exit;
}

// ── Change driver Logic ────────────────────────────────────────────────────────
if (isset($_POST['change_driver'])) {
    $bid        = intval($_POST['booking_id']);
    $new_driver = intval($_POST['new_driver_id']);
    $chkQ2   = mysqli_query($con, "SELECT booking_status, pickup_datetime, actual_return_datetime FROM booking_master WHERE booking_id=$bid AND ui=$uid_safe");
    $chkRow2 = mysqli_fetch_assoc($chkQ2);
    if ($chkRow2 && $chkRow2['booking_status'] === 'Rejected') {
        $_SESSION['toast'] = ['type'=>'error','msg'=>'This booking was rejected and cannot be modified.'];
        header("location: booking_details.php"); exit;
    }
    $driver_conflict = false;
    if ($new_driver > 0 && !empty($chkRow2['pickup_datetime'])) {
        $pu = $chkRow2['pickup_datetime'];
        $re = (!empty($chkRow2['actual_return_datetime']) && $chkRow2['actual_return_datetime'] !== '0000-00-00 00:00:00') ? $chkRow2['actual_return_datetime'] : null;
        $re_check = $re ? "'$re'" : "DATE_ADD('$pu', INTERVAL 1 DAY)";
        $cc = mysqli_query($con, "SELECT booking_id FROM booking_master WHERE driver_id=$new_driver AND booking_status='Approved' AND driver_id IS NOT NULL AND booking_id!=$bid AND pickup_datetime<$re_check AND (actual_return_datetime IS NULL OR actual_return_datetime='0000-00-00 00:00:00' OR actual_return_datetime>'$pu') LIMIT 1");
        if ($cc && mysqli_num_rows($cc) > 0) $driver_conflict = true;
    }
    if ($driver_conflict) {
        $_SESSION['toast'] = ['type'=>'error','msg'=>'Selected driver is already booked during your dates. Please choose another driver.'];
    } else {
        $val = $new_driver > 0 ? $new_driver : 'NULL';
        $cQ  = mysqli_query($con, "UPDATE booking_master SET driver_id=$val WHERE booking_id=$bid AND ui=$uid_safe AND booking_status='Pending'");
        $_SESSION['toast'] = ($cQ && mysqli_affected_rows($con) > 0)
            ? ['type'=>'success','msg'=> $new_driver > 0 ? 'Driver updated successfully!' : 'Switched to Self Drive.']
            : ['type'=>'error','msg'=>'Driver change failed. Only pending bookings can be modified.'];
    }
    header("location: booking_details.php"); exit;
}

// ── Fetch booking ─────────────────────────────────────────────────────────────
if (!empty($_GET['bid'])) {
    $bookingQ = safeQuery($con, "SELECT * FROM booking_master WHERE booking_id=".intval($_GET['bid'])." AND ui=$uid_safe LIMIT 1", "fetch booking by id");
} else {
    $bookingQ = safeQuery($con, "SELECT * FROM booking_master WHERE ui=$uid_safe ORDER BY booking_id DESC LIMIT 1", "fetch latest booking");
}

if (mysqli_num_rows($bookingQ) == 0) {
    die("<div style='font-family:sans-serif;padding:40px;color:#fff;background:#0d0f14;min-height:100vh;'><h2 style='color:#e74c3c'>No booking found.</h2><p style='color:#aaa;margin-top:12px'>No bookings found. <a href='car.php' style='color:#2ecc71'>Book a car first</a></p></div>");
}
$booking = mysqli_fetch_assoc($bookingQ);

// --- CALCULATE TRIP DISTANCE ---
$trip_distance = getDistance($booking['pickup_lat'], $booking['pickup_lng'], $booking['drop_lat'], $booking['drop_lng']);

$car_id     = intval($booking['car_id']     ?? 0);
$driver_id  = intval($booking['driver_id'] ?? 0);
$status     = $booking['booking_status']   ?? 'Pending';
$booking_id = intval($booking['booking_id']);
$is_pending   = ($status === 'Pending');
$is_approved  = ($status === 'Approved');
$is_cancelled = ($status === 'Cancelled');
$is_rejected  = ($status === 'Rejected');

// ── Fetch trip_status from booking_details ────────────────────────────────────
$bdQ = mysqli_query($con, "SELECT trip_status FROM booking_details WHERE booking_id=$booking_id LIMIT 1");
$bdRow = $bdQ ? mysqli_fetch_assoc($bdQ) : [];
$trip_status = $bdRow['trip_status'] ?? 'Not Started';

// ── Related data ──────────────────────────────────────────────────────────────
$carQ    = safeQuery($con, "SELECT c.*,b.brand_name FROM car_master c LEFT JOIN brand_master b ON b.brand_id=c.brand_id WHERE c.car_id=$car_id", "fetch car");
$carRow  = mysqli_fetch_assoc($carQ) ?: [];
$userQ   = safeQuery($con, "SELECT * FROM users_master WHERE ui=$uid_safe", "fetch user");
$userRow = mysqli_fetch_assoc($userQ) ?: [];
$driverRow = [];
if ($driver_id > 0) { $driverQ = safeQuery($con, "SELECT * FROM driver_master WHERE driver_id=$driver_id", "fetch driver"); $driverRow = mysqli_fetch_assoc($driverQ) ?: []; }
$allDriversQ = safeQuery($con, "SELECT driver_id,driver_name,driver_mobile,experience_years,profile_image FROM driver_master WHERE status!=2 ORDER BY driver_name ASC", "all drivers");
$allDrivers = [];
while ($d = mysqli_fetch_assoc($allDriversQ)) $allDrivers[] = $d;
$bookedSlotsQ = safeQuery($con, "SELECT driver_id,pickup_datetime,actual_return_datetime FROM booking_master WHERE booking_status='Approved' AND driver_id IS NOT NULL AND booking_id!=$booking_id", "booked slots");
$bookedSlots = [];
while ($bs = mysqli_fetch_assoc($bookedSlotsQ)) $bookedSlots[] = $bs;
$pricingQ = safeQuery($con, "SELECT * FROM car_pricing WHERE car_id=$car_id ORDER BY pricing_id DESC LIMIT 1", "pricing");
$priceRow = mysqli_fetch_assoc($pricingQ) ?: [];

// ── Calculations ──────────────────────────────────────────────────────────────
$ppd          = floatval($priceRow['price_per_day']    ?? $carRow['price_per_day']    ?? 0);
$pph          = floatval($priceRow['price_per_hour']   ?? $carRow['price_per_hour']   ?? 0);
$security_dep = floatval($priceRow['security_deposit'] ?? $carRow['security_deposit'] ?? 0);
$pickup   = new DateTime($booking['pickup_datetime']);
$returnDT = (!empty($booking['actual_return_datetime']) && $booking['actual_return_datetime'] !== '0000-00-00 00:00:00') ? new DateTime($booking['actual_return_datetime']) : null;
$total_days = 0; $extra_hours = 0; $diff = null;
if ($returnDT) { $diff = $pickup->diff($returnDT); $total_days = $diff->days; $extra_hours = $diff->h + ($diff->i > 0 ? 1 : 0); } else { $total_days = 1; }

$day_amount  = $total_days * $ppd;
$hour_amount = ($returnDT && $extra_hours > 0 && $pph > 0) ? $extra_hours * $pph : 0;
$base_amount = $day_amount + $hour_amount;

$driver_charge = ($driver_id > 0) ? round($base_amount * 0.10, 2) : 0;
$taxable       = $base_amount + $driver_charge;
$gst           = round($taxable * 0.05, 2);
$grand_total   = $taxable + $gst + $security_dep;
$booking_ref   = 'CB-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);

$rent_only     = $taxable + $gst;
$full_payment  = $grand_total;
$deposit_only  = $security_dep;
$current_choice = $_SESSION['payment_choice'] ?? 'deposit';

if ($current_choice == 'full') {
    $current_payable = $full_payment;
} else {
    $current_payable = $deposit_only;
}
if (!isset($_SESSION['payment_choice'])) {
    $_SESSION['payment_choice'] = 'deposit';
}

// ── Fetch ALL Payments for this booking ──────────────────────────────────────
$all_payments = [];
$total_paid   = 0;
$is_deposit_paid = false;
$is_fully_paid   = false;
$deposit_payment_info = null;
$full_payment_info    = null;
$last_payment_info    = null;

$pmt_all_q = mysqli_query($con,
    "SELECT * FROM payment_master 
     WHERE booking_id = $booking_id
     ORDER BY payid ASC"
);
if ($pmt_all_q) {
    while ($pr = mysqli_fetch_assoc($pmt_all_q)) {
        $all_payments[] = $pr;
        $total_paid += floatval($pr['paid_amount']);
        if (($pr['payment_status'] ?? 0) == 2) {
            $is_fully_paid = true;
            $full_payment_info = $pr;
        }
        if (($pr['payment_type'] ?? '') === 'deposit') {
            $is_deposit_paid = true;
            $deposit_payment_info = $pr;
        }
        $last_payment_info = $pr;
    }
}

$is_paid = ($is_deposit_paid || $is_fully_paid);
$remaining_due = max(0, $grand_total - $total_paid);
$deposit_paid_amount  = $is_deposit_paid  ? floatval($deposit_payment_info['paid_amount'] ?? $security_dep) : 0;
$full_paid_amount     = $is_fully_paid    ? floatval($full_payment_info['paid_amount']    ?? $full_payment)  : 0;

// ── HANDLE PAYMENT SELECTION ────────────────────────
if (isset($_POST['select_payment'])) {
    $_SESSION['payment_choice'] = $_POST['payment_type'];
    header("location: orderconfirm.php");
    exit;
}

$_SESSION['booking_payment'] = [
    'booking_id'    => $booking_id,
    'booking_ref'   => $booking_ref,
    'grand_total'   => $grand_total,
    'rent_only'     => $rent_only,
    'full_payment'  => $full_payment,
    'deposit_only'  => $deposit_only,
    'payment_choice'=> $_SESSION['payment_choice'],
    'security_dep'  => $security_dep,
    'car_name'      => $carRow['car_display_name'] ?? '',
    'car_image'     => $carRow['primary_image']    ?? '',
    'brand_name'    => $carRow['brand_name']       ?? '',
    'pickup_date'   => $booking['pickup_datetime'],
    'return_date'   => $booking['actual_return_datetime'] ?? '',
    'driver_name'   => $driverRow['driver_name']   ?? '',
    'base_amount'   => $base_amount,
    'driver_charge' => $driver_charge,
    'gst'           => $gst,
    'user_name'     => $userRow['uname']   ?? '',
    'user_email'    => $userRow['email']   ?? '',
    'user_phone'    => $userRow['mobno']   ?? '',
    'user_address'  => $userRow['address'] ?? '',
    'days'          => $total_days,
    'ppd'           => $ppd,
];

$status_cfg = [
    'Pending'   => ['color'=>'#ffc107','bg'=>'rgba(255,193,7,0.08)',   'border'=>'rgba(255,193,7,0.22)',  'icon'=>'fa-clock',        'dot_anim'=>true],
    'Approved'  => ['color'=>'#2ecc71','bg'=>'rgba(46,204,113,0.08)',  'border'=>'rgba(46,204,113,0.22)', 'icon'=>'fa-check-circle', 'dot_anim'=>false],
    'Cancelled' => ['color'=>'#ff6b6b','bg'=>'rgba(255,107,107,0.08)','border'=>'rgba(255,107,107,0.22)','icon'=>'fa-times-circle', 'dot_anim'=>false],
    'Rejected'  => ['color'=>'#ff6b6b','bg'=>'rgba(255,107,107,0.08)','border'=>'rgba(255,107,107,0.22)','icon'=>'fa-ban',           'dot_anim'=>false],
];
$sc = $status_cfg[$status] ?? $status_cfg['Pending'];
$toast = $_SESSION['toast'] ?? null; unset($_SESSION['toast']);
$booking_pickup_ts  = strtotime($booking['pickup_datetime']);
$booking_return_raw = (!empty($booking['actual_return_datetime']) && $booking['actual_return_datetime'] !== '0000-00-00 00:00:00') ? $booking['actual_return_datetime'] : null;
$booking_return_ts  = $booking_return_raw ? strtotime($booking_return_raw) : ($booking_pickup_ts + 86400);

// ── Trip status config ────────────────────────────────────────────────────────
$trip_cfg = [
    'Not Started' => [
        'color'  => '#94a3b8',
        'bg'     => 'rgba(148,163,184,0.08)',
        'border' => 'rgba(148,163,184,0.22)',
        'glow'   => 'rgba(148,163,184,0.15)',
        'icon'   => 'fa-minus-circle',
        'label'  => 'Not Started',
        'sub'    => 'The driver has not started the trip yet.',
        'step'   => 1,
    ],
    'Started' => [
        'color'  => '#1a8cff',
        'bg'     => 'rgba(26,140,255,0.09)',
        'border' => 'rgba(26,140,255,0.30)',
        'glow'   => 'rgba(26,140,255,0.18)',
        'icon'   => 'fa-play-circle',
        'label'  => 'Trip In Progress',
        'sub'    => 'Your driver is on the way or has picked you up.',
        'step'   => 2,
    ],
    'Completed' => [
        'color'  => '#2ecc71',
        'bg'     => 'rgba(46,204,113,0.09)',
        'border' => 'rgba(46,204,113,0.30)',
        'glow'   => 'rgba(46,204,113,0.18)',
        'icon'   => 'fa-flag-checkered',
        'label'  => 'Trip Completed',
        'sub'    => 'Your ride has been successfully completed.',
        'step'   => 3,
    ],
];
$tc = $trip_cfg[$trip_status] ?? $trip_cfg['Not Started'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Details — CarBook</title>
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
  <link rel="stylesheet" href="css/animate.css">
  <link rel="stylesheet" href="css/owl.carousel.min.css">
  <link rel="stylesheet" href="css/owl.theme.default.min.css">
  <link rel="stylesheet" href="css/aos.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/flaticon.css">
  <link rel="stylesheet" href="css/icomoon.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --green:#2ecc71;--blue:#1a8cff;--gold:#f5a623;--red:#ff6b6b;
      --dark:#0d0f14;--dark2:#141720;--dark3:#1c2030;--dark4:#232840;
      --border:rgba(255,255,255,0.07);--text:rgba(255,255,255,0.88);--muted:rgba(255,255,255,0.42);
      --font:'Poppins',sans-serif;--mono:'Space Mono',monospace;
    }
    html{scroll-behavior:smooth}
    html,body{background:#0d0f14 !important}
    body{font-family:var(--font);color:var(--text);min-height:100vh;overflow-x:hidden}

    #ftco-navbar{background:transparent !important;box-shadow:none !important;transition:background .35s ease,box-shadow .35s ease;}
    #ftco-navbar.scrolled{background:rgba(7,9,12,0.98) !important;box-shadow:0 4px 28px rgba(0,0,0,0.7) !important;}

    .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}
    .bg-canvas::before{content:'';position:absolute;width:900px;height:900px;background:radial-gradient(circle,rgba(26,140,255,0.11) 0%,transparent 65%);top:-300px;right:-200px;animation:drift 18s ease-in-out infinite alternate}
    .bg-canvas::after{content:'';position:absolute;width:700px;height:700px;background:radial-gradient(circle,rgba(46,204,113,0.07) 0%,transparent 65%);bottom:-150px;left:-150px;animation:drift 24s ease-in-out infinite alternate-reverse}
    @keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(50px,35px) scale(1.08)}}
    .grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,0.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.022) 1px,transparent 1px);background-size:64px 64px}
    .deco-bottom{position:fixed;bottom:0;left:0;right:0;height:3px;z-index:100;background:linear-gradient(90deg,var(--green),var(--blue),var(--green));background-size:200% 100%;animation:shimmer 3s linear infinite}
    @keyframes shimmer{from{background-position:0%}to{background-position:200%}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    @keyframes blink{0%,100%{opacity:1}50%{opacity:0.25}}
    @keyframes pulse-gold{0%,100%{box-shadow:0 0 0 0 rgba(255,193,7,0.3)}50%{box-shadow:0 0 0 20px rgba(255,193,7,0)}}
    @keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(46,204,113,0.25)}50%{box-shadow:0 0 0 20px rgba(46,204,113,0)}}
    @keyframes tripPulse{0%,100%{box-shadow:0 0 0 0 var(--trip-glow,rgba(26,140,255,0.3))}60%{box-shadow:0 0 0 14px transparent}}
    @keyframes roadMove{from{background-position:0 0}to{background-position:40px 0}}
    @keyframes checkBounce{0%{transform:scale(0)}60%{transform:scale(1.2)}100%{transform:scale(1)}}

    .page-wrap{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:100px 24px 90px}
    .success-banner{display:flex;flex-direction:column;align-items:center;text-align:center;margin-bottom:44px;animation:fadeUp .6s ease both .1s}
    .check-ring{width:86px;height:86px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:18px}
    .check-ring.pending{background:rgba(255,193,7,0.12);border:2px solid rgba(255,193,7,0.35);color:#ffc107;animation:pulse-gold 2.5s ease infinite}
    .check-ring.approved{background:rgba(46,204,113,0.12);border:2px solid rgba(46,204,113,0.35);color:var(--green);animation:pulse-green 2.5s ease infinite}
    .check-ring.cancelled,.check-ring.rejected{background:rgba(255,107,107,0.1);border:2px solid rgba(255,107,107,0.3);color:var(--red)}
    .success-banner h1{font-size:clamp(22px,3.5vw,34px);font-weight:800;letter-spacing:-.5px;color:#fff;margin-bottom:8px}
    .success-banner p{font-size:14px;color:var(--muted);max-width:420px}
    .ref-pill{display:inline-flex;align-items:center;gap:10px;margin-top:16px;padding:8px 20px;background:var(--dark3) !important;border:1px solid var(--border);border-radius:100px}
    .ref-pill .rl{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
    .ref-pill .rv{font-family:var(--mono);font-size:13px;color:var(--green);font-weight:700}
    .ref-pill .cp{background:none;border:none;color:var(--muted);cursor:pointer;font-size:12px;padding:2px 4px;transition:color .2s}
    .ref-pill .cp:hover{color:#fff}

    .paid-banner-badge{display:inline-flex;align-items:center;gap:8px;margin-top:10px;padding:8px 20px;border-radius:100px;font-size:12px;font-weight:700}
    .paid-banner-badge.full-paid{background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.35);color:var(--green)}
    .paid-banner-badge.deposit-paid{background:rgba(245,166,35,0.12);border:1px solid rgba(245,166,35,0.35);color:var(--gold)}
    .paid-banner-badge.rental-due{background:rgba(26,140,255,0.1);border:1px solid rgba(26,140,255,0.28);color:var(--blue)}
    .paid-banner-badge i{font-size:14px}

    .main-grid{display:grid;grid-template-columns:1fr 350px;gap:22px;animation:fadeUp .6s ease both .2s;align-items:start}

    .card{background:var(--dark2) !important;border:1px solid var(--border);border-radius:14px;overflow:hidden}
    .card+.card{margin-top:20px}
    .card-header{padding:18px 24px 16px;border-bottom:1px solid var(--border) !important;background:var(--dark2) !important;display:flex;align-items:center;gap:12px}
    .iw{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
    .ib{background:rgba(26,140,255,0.15);color:var(--blue)}
    .ig{background:rgba(46,204,113,0.15);color:var(--green)}
    .io{background:rgba(245,166,35,0.15);color:var(--gold)}
    .ip{background:rgba(180,100,255,0.15);color:#b464ff}
    .ir{background:rgba(255,107,107,0.15);color:var(--red)}
    .it{background:rgba(26,140,255,0.15);color:var(--blue)}
    .card-header h3{font-size:14px;font-weight:700;color:#fff;letter-spacing:.02em}
    .cbadge{margin-left:auto;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 11px;border-radius:100px}
    .bs{background:rgba(46,204,113,0.12);color:var(--green);border:1px solid rgba(46,204,113,0.28)}
    .bp{background:rgba(255,193,7,0.12);color:#ffc107;border:1px solid rgba(255,193,7,0.28)}
    .bb{background:rgba(26,140,255,0.12);color:var(--blue);border:1px solid rgba(26,140,255,0.28)}
    .bg{background:rgba(245,166,35,0.12);color:var(--gold);border:1px solid rgba(245,166,35,0.28)}
    .card-body{padding:20px 22px;background:var(--dark2) !important;color:var(--text)}

    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .ii{background:var(--dark3) !important;border:1px solid var(--border);border-radius:10px;padding:13px 15px;transition:border-color .2s}
    .ii:hover{border-color:rgba(255,255,255,0.13)}
    .ii.full{grid-column:1/-1}
    .ii-label{font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;display:flex;align-items:center;gap:5px}
    .ii-label i{color:var(--blue);font-size:9px}
    .ii-value{font-size:13.5px;font-weight:600;color:#fff}
    .ii-value.green{color:var(--green)}.ii-value.mono{font-family:var(--mono);font-size:12px}

    .car-showcase{position:relative;background:linear-gradient(135deg,var(--dark3),var(--dark4)) !important;border-radius:12px;overflow:hidden;padding:24px 28px;display:flex;align-items:center;gap:28px;margin-bottom:22px}
    .car-showcase::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 72% 50%,rgba(26,140,255,0.09),transparent 65%)}
    .car-showcase-img{width:210px;height:130px;object-fit:contain;position:relative;z-index:1;filter:drop-shadow(0 14px 28px rgba(0,0,0,0.55));flex-shrink:0;transition:transform .4s ease}
    .car-showcase:hover .car-showcase-img{transform:translateX(8px) scale(1.04)}
    .csi{position:relative;z-index:1}
    .brand-tag{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);background:rgba(26,140,255,0.1);border:1px solid rgba(26,140,255,0.22);padding:3px 10px;border-radius:4px;margin-bottom:7px}
    .csi h2{font-size:19px;font-weight:800;color:#fff;margin-bottom:6px;letter-spacing:-.3px}
    .plate-badge{display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;background:var(--dark4) !important;border:1px solid rgba(255,255,255,0.1);padding:4px 12px;border-radius:4px;font-family:var(--mono);font-size:12px;color:rgba(255,255,255,0.75)}
    .plate-badge i{color:var(--gold);font-size:10px}
    .car-specs{display:flex;gap:14px;flex-wrap:wrap}
    .spec{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--muted)}
    .spec i{color:var(--green);font-size:11px}

    .timeline-row{display:flex;align-items:stretch;background:var(--dark3) !important;border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-top:16px}
    .tp{flex:1;padding:16px 18px;text-align:center}
    .tp-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:6px}
    .tp-icon{font-size:20px;margin-bottom:6px}
    .tg{color:var(--green)}.tb{color:var(--blue)}
    .tp-date{font-family:var(--mono);font-size:12px;color:#fff;font-weight:700}
    .tp-time{font-size:11px;color:var(--muted);margin-top:2px}
    .tdiv{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:0 6px}
    .tdiv .tl{flex:1;width:1px;background:var(--border);min-height:20px}
    .dur-badge{font-size:10px;font-weight:700;color:var(--blue);background:rgba(26,140,255,0.12);border:1px solid rgba(26,140,255,0.25);border-radius:100px;padding:4px 10px;font-family:var(--mono);white-space:nowrap;margin:4px 0}
    .sdiv{display:flex;align-items:center;gap:12px;margin:20px 0 14px}
    .sdiv .sl{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);white-space:nowrap}
    .sdiv .sline{flex:1;height:1px;background:var(--border)}
    .location-box{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;background:var(--dark3) !important;border:1px solid var(--border);border-radius:10px;margin-top:12px}
    .loc-icon{width:36px;height:36px;border-radius:50%;background:rgba(46,204,113,0.12);display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--green);flex-shrink:0;margin-top:2px}
    .loc-text .lt{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:4px}
    .loc-text .lv{font-size:13.5px;font-weight:600;color:#fff;line-height:1.5}

    /* ── TRIP STATUS CARD ──────────────────────────────────────────────────── */
    .trip-status-card {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid var(--trip-border, rgba(148,163,184,0.22));
        background: var(--dark2) !important;
        margin-top: 20px;
    }
    .trip-status-header {
        display: flex; align-items: center; gap: 12px;
        padding: 16px 22px;
        border-bottom: 1px solid var(--trip-border, rgba(148,163,184,0.15));
        background: var(--trip-bg, rgba(148,163,184,0.05)) !important;
    }
    .trip-status-icon-wrap {
        width: 42px; height: 42px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; flex-shrink: 0;
        background: var(--trip-bg, rgba(148,163,184,0.1));
        border: 1.5px solid var(--trip-border, rgba(148,163,184,0.3));
        color: var(--trip-color, #94a3b8);
        --trip-glow: var(--trip-glow-val, rgba(148,163,184,0.2));
    }
    .trip-status-icon-wrap.pulsing { animation: tripPulse 2s ease infinite; }
    .trip-status-label { font-size: 15px; font-weight: 800; color: #fff; }
    .trip-status-sub   { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .trip-status-badge {
        margin-left: auto;
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px; border-radius: 100px;
        font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        background: var(--trip-bg); border: 1px solid var(--trip-border);
        color: var(--trip-color);
    }
    .trip-status-badge .ts-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: currentColor; flex-shrink: 0;
    }
    .trip-status-badge .ts-dot.blink { animation: blink 1.4s ease infinite; }

    /* ── Trip Progress Steps ── */
    .trip-steps {
        display: flex; align-items: center;
        padding: 20px 22px;
        gap: 0;
    }
    .trip-step {
        display: flex; flex-direction: column; align-items: center;
        gap: 6px; flex: 1; position: relative;
    }
    .ts-circle {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; border: 2px solid transparent;
        transition: all .3s;
    }
    .ts-circle.done {
        background: rgba(46,204,113,0.15);
        border-color: rgba(46,204,113,0.5);
        color: var(--green);
    }
    .ts-circle.active {
        background: rgba(26,140,255,0.15);
        border-color: rgba(26,140,255,0.5);
        color: var(--blue);
        animation: tripPulse 2s ease infinite;
        --trip-glow-val: rgba(26,140,255,0.3);
    }
    .ts-circle.inactive {
        background: rgba(255,255,255,0.04);
        border-color: rgba(255,255,255,0.1);
        color: var(--muted);
    }
    .ts-label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; text-align: center; color: var(--muted);
        line-height: 1.3;
    }
    .ts-label.done   { color: var(--green); }
    .ts-label.active { color: var(--blue); }
    .trip-step-line {
        height: 2px; flex: 1;
        background: rgba(255,255,255,0.07);
        margin-bottom: 22px;
        transition: background .4s;
    }
    .trip-step-line.done { background: rgba(46,204,113,0.45); }

    /* ── Completed celebration block ── */
    .trip-complete-block {
        display: flex; align-items: center; gap: 14px;
        padding: 16px 20px;
        background: linear-gradient(135deg,rgba(46,204,113,0.08),rgba(26,140,255,0.04)) !important;
        border-top: 1px solid rgba(46,204,113,0.18);
    }
    .tcb-icon {
        width: 46px; height: 46px; border-radius: 50%;
        background: rgba(46,204,113,0.14);
        border: 2px solid rgba(46,204,113,0.4);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: var(--green); flex-shrink: 0;
        animation: checkBounce .6s cubic-bezier(.34,1.56,.64,1) both;
    }
    .tcb-title { font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 3px; }
    .tcb-sub   { font-size: 12px; color: var(--muted); }

    /* ── Road animation for "Started" ── */
    .road-strip {
        height: 28px;
        background: linear-gradient(90deg, var(--dark4) 0%, rgba(26,140,255,0.06) 50%, var(--dark4) 100%);
        border-top: 1px solid rgba(26,140,255,0.15);
        position: relative; overflow: hidden;
        display: flex; align-items: center; padding: 0 20px; gap: 6px;
    }
    .road-dashes {
        position: absolute; inset: 0;
        background-image: repeating-linear-gradient(90deg, transparent 0px, transparent 16px, rgba(26,140,255,0.25) 16px, rgba(26,140,255,0.25) 28px);
        animation: roadMove 1s linear infinite;
    }
    .road-car { font-size: 14px; position: relative; z-index: 1; }
    .road-label { font-size: 10px; font-weight: 700; color: rgba(26,140,255,0.8); letter-spacing:.05em; position:relative;z-index:1; }

    /* ── Driver details etc (existing) ── */
    .driver-card-inner{display:flex;align-items:center;gap:16px}
    .driver-avatar-wrap{position:relative;flex-shrink:0}
    .driver-avatar{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid rgba(26,140,255,0.5);box-shadow:0 0 0 4px rgba(26,140,255,0.1),0 8px 24px rgba(0,0,0,0.4)}
    .driver-avatar-placeholder{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--dark4),var(--dark3));border:3px solid rgba(26,140,255,0.3);display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--blue);box-shadow:0 0 0 4px rgba(26,140,255,0.08)}
    .driver-exp-badge{position:absolute;bottom:-2px;right:-2px;background:var(--blue);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:100px;border:2px solid var(--dark2);white-space:nowrap}
    .dn{font-size:15px;font-weight:700;color:#fff;margin-bottom:5px}
    .dmeta{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);margin-top:3px}
    .dmeta i{color:var(--green);font-size:10px;width:12px}
    .driver-details-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}
    .dd-item{background:var(--dark3) !important;border:1px solid var(--border);border-radius:8px;padding:9px 12px}
    .dd-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:4px;display:flex;align-items:center;gap:5px}
    .dd-value{font-size:12.5px;font-weight:600;color:#fff}
    .self-drive-badge{display:flex;align-items:center;gap:12px;padding:16px;background:rgba(26,140,255,0.07);border:1px solid rgba(26,140,255,0.18);border-radius:10px}
    .sd-icon{width:44px;height:44px;border-radius:50%;background:rgba(26,140,255,0.15);display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--blue);flex-shrink:0}
    .sdt{font-size:14px;font-weight:700;color:#fff}
    .sds{font-size:12px;color:var(--muted);margin-top:2px}

    .change-driver-panel{margin-top:14px;padding:12px 14px;background:rgba(26,140,255,0.05);border:1px solid rgba(26,140,255,0.15);border-radius:10px}
    .change-driver-panel .cdp-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--blue);margin-bottom:10px;display:flex;align-items:center;gap:7px}
    .driver-select-wrap{position:relative}
    .driver-select-wrap>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(26,140,255,0.6);font-size:12px;pointer-events:none;z-index:1}
    .driver-select{width:100%;padding:10px 12px 10px 34px;background:var(--dark4) !important;border:1.5px solid rgba(255,255,255,0.1);color:#fff;font-family:var(--font);font-size:13px;border-radius:8px;cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;transition:border-color .2s}
    .driver-select:focus{outline:none;border-color:rgba(26,140,255,0.45)}
    .driver-select option{background:#1c2030;color:#fff}
    .driver-select option:disabled{color:rgba(255,255,255,0.3);font-style:italic}
    .btn-change-driver{width:100%;margin-top:10px;padding:10px;background:linear-gradient(135deg,#1a4a8a,#1a6ed4);color:#fff;font-family:var(--font);font-size:13px;font-weight:700;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:filter .2s,transform .15s}
    .btn-change-driver:hover{filter:brightness(1.12);transform:translateY(-1px)}
    .cdp-avail-note{font-size:10px;margin-bottom:8px;padding:6px 10px;border-radius:6px;display:flex;align-items:center;gap:6px}
    .cdp-avail-note.info{background:rgba(26,140,255,0.1);color:rgba(255,255,255,0.7);border:1px solid rgba(26,140,255,0.2)}
    .cdp-avail-note.warn{background:rgba(255,193,7,0.1);color:#fff3cd;border:1px solid rgba(255,193,7,0.2)}

    .sidebar{display:flex;flex-direction:column;gap:12px;position:sticky;top:90px}
    .user-top{display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid var(--border) !important;margin-bottom:16px}
    .user-avatar{width:58px;height:58px;border-radius:50%;object-fit:cover;border:2px solid rgba(46,204,113,0.3);flex-shrink:0}
    .un{font-size:15px;font-weight:700;color:#fff;margin-bottom:2px}
    .ue{font-size:12px;color:var(--muted)}
    .urow{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border)}
    .urow:last-child{border-bottom:none}
    .urow .ui-icon{width:28px;height:28px;border-radius:6px;background:var(--dark4) !important;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--blue);flex-shrink:0}
    .urow .ul{font-size:10px;color:var(--muted);margin-bottom:1px}
    .urow .uv{font-size:12.5px;font-weight:600;color:#fff}

    .price-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px}
    .price-row:last-child{border-bottom:none}
    .price-row .pl{color:var(--muted);display:flex;align-items:center;gap:7px}
    .price-row .pl i{font-size:10px;color:var(--blue)}
    .price-row .pv{font-weight:600;color:#fff}
    .price-row.sub .pv{color:rgba(255,255,255,0.65)}
    .price-row.dr .pv{color:var(--blue)}
    .price-row.dep .pv{color:var(--gold)}
    .price-row.total-row{margin-top:8px;padding-top:18px;border-top:1px solid rgba(255,255,255,0.1);border-bottom:none}
    .price-row.total-row .pl{color:#fff;font-weight:700;font-size:14px}
    .price-row.total-row .pv{font-size:22px;font-weight:800;color:var(--green);font-family:var(--mono)}

    .payment-summary-strip{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:16px}
    .payment-summary-strip.full{background:linear-gradient(135deg,rgba(46,204,113,0.12),rgba(26,140,255,0.06));border:1.5px solid rgba(46,204,113,0.35)}
    .payment-summary-strip.deposit{background:linear-gradient(135deg,rgba(245,166,35,0.10),rgba(26,140,255,0.04));border:1.5px solid rgba(245,166,35,0.30)}
    .pss-left{display:flex;align-items:center;gap:10px}
    .pss-icon{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
    .pss-icon.full{background:rgba(46,204,113,0.15);color:var(--green)}
    .pss-icon.deposit{background:rgba(245,166,35,0.15);color:var(--gold)}
    .pss-title{font-size:13px;font-weight:800;color:#fff}
    .pss-sub{font-size:11px;color:var(--muted);margin-top:1px}
    .pss-right{text-align:right}
    .pss-amount{font-family:var(--mono);font-size:18px;font-weight:800}
    .pss-amount.full{color:var(--green)}
    .pss-amount.deposit{color:var(--gold)}
    .pss-date{font-size:10px;color:var(--muted);margin-top:2px}

    .pay-now-box{background:linear-gradient(135deg,rgba(46,204,113,0.09),rgba(26,140,255,0.05));border:1px solid rgba(46,204,113,0.25);border-radius:12px;padding:16px 18px;margin-top:16px}
    .pay-now-box .pnb-title{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--green);margin-bottom:14px;display:flex;align-items:center;gap:6px}
    .payment-options{display:flex;flex-direction:column;gap:10px;margin-bottom:14px}
    .payment-option-label{display:flex;align-items:center;gap:0;cursor:pointer;position:relative}
    .payment-option-label input[type="radio"]{position:absolute;opacity:0;width:0;height:0;pointer-events:none}
    .payment-option-card{display:flex;align-items:center;gap:12px;width:100%;padding:12px 14px;background:var(--dark3);border:1.5px solid rgba(255,255,255,0.08);border-radius:10px;transition:border-color .2s,background .2s;cursor:pointer}
    .payment-option-label input[type="radio"]:checked + .payment-option-card{border-color:rgba(46,204,113,0.55);background:rgba(46,204,113,0.07)}
    .payment-option-label:hover .payment-option-card{border-color:rgba(255,255,255,0.18);background:rgba(255,255,255,0.03)}
    .payment-option-label input[type="radio"]:checked + .payment-option-card .radio-dot{background:var(--green);border-color:var(--green);box-shadow:0 0 0 3px rgba(46,204,113,0.2)}
    .radio-dot{width:18px;height:18px;border-radius:50%;border:2px solid rgba(255,255,255,0.2);background:transparent;flex-shrink:0;transition:background .2s,border-color .2s,box-shadow .2s;display:flex;align-items:center;justify-content:center}
    .radio-dot::after{content:'';width:7px;height:7px;border-radius:50%;background:#fff;opacity:0;transition:opacity .2s}
    .payment-option-label input[type="radio"]:checked + .payment-option-card .radio-dot::after{opacity:1}
    .poc-info{flex:1}
    .poc-title{font-size:13px;font-weight:700;color:#fff;margin-bottom:2px}
    .poc-sub{font-size:11px;color:var(--muted)}
    .poc-amount{font-family:var(--mono);font-size:14px;font-weight:800;color:var(--green)}
    .poc-amount.gold{color:var(--gold)}

    .paid-box{border-radius:12px;padding:18px 20px;margin-top:14px}
    .paid-box.full-paid{background:linear-gradient(135deg,rgba(46,204,113,0.12),rgba(26,140,255,0.06));border:1.5px solid rgba(46,204,113,0.35)}
    .paid-box.deposit-paid{background:linear-gradient(135deg,rgba(245,166,35,0.10),rgba(26,140,255,0.05));border:1.5px solid rgba(245,166,35,0.30)}
    .paid-box-title{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:14px}
    .paid-box-title.full{color:var(--green)}
    .paid-box-title.deposit{color:var(--gold)}
    .paid-box-title i{font-size:15px}
    .paid-info-row{display:flex;justify-content:space-between;font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.05)}
    .paid-info-row:last-child{border-bottom:none}
    .paid-info-row .pil{color:var(--muted)}
    .paid-info-row .piv{font-weight:600;color:#fff}
    .paid-info-row .piv.green{color:var(--green)}
    .paid-info-row .piv.gold{color:var(--gold)}
    .paid-info-row .piv.blue{color:var(--blue)}

    .pay-remaining-box{background:linear-gradient(135deg,rgba(26,140,255,0.10),rgba(46,204,113,0.05));border:1.5px solid rgba(26,140,255,0.30);border-radius:12px;padding:16px 18px;margin-top:14px}
    .prb-title{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);margin-bottom:12px;display:flex;align-items:center;gap:6px}
    .prb-amount-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
    .prb-label{font-size:13px;font-weight:700;color:#fff}
    .prb-amount{font-family:var(--mono);font-size:22px;font-weight:800;color:var(--blue)}

    .btn-cta{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px 18px;color:#fff;font-family:var(--font);font-size:14px;font-weight:700;border:none;border-radius:9px;cursor:pointer;text-decoration:none;transition:filter .2s,transform .15s,box-shadow .2s;letter-spacing:.02em;min-height:50px}
    .btn-cta:hover{filter:brightness(1.1);transform:translateY(-2px);color:#fff;text-decoration:none}
    .btn-cta:active{transform:translateY(0)}
    .btn-purple{background:linear-gradient(135deg,#6b3fa0,#9b59b6);box-shadow:0 6px 22px rgba(155,89,182,0.28)}
    .btn-blue{background:linear-gradient(135deg,#1a4a8a,#1a6ed4);box-shadow:0 6px 22px rgba(26,140,255,0.22)}
    .btn-green{background:linear-gradient(135deg,#0a7a3e,#2ecc71);box-shadow:0 6px 22px rgba(46,204,113,0.28)}
    .btn-gold{background:linear-gradient(135deg,#b07800,#f5a623);box-shadow:0 6px 22px rgba(245,166,35,0.28)}
    .btn-cancel{background:transparent;border:1.5px solid rgba(255,107,107,0.35);color:var(--red);box-shadow:none}
    .btn-cancel:hover{background:rgba(255,107,107,0.08);border-color:rgba(255,107,107,0.6);box-shadow:none;color:var(--red)}

    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:5000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
    .modal-overlay.open{display:flex;animation:fadeUp .25s ease both}
    .modal-box{background:var(--dark2) !important;border:1px solid rgba(255,107,107,0.25);border-radius:16px;padding:36px 32px;max-width:420px;width:90%;text-align:center;position:relative}
    .modal-box .modal-icon{width:64px;height:64px;border-radius:50%;background:rgba(255,107,107,0.1);border:2px solid rgba(255,107,107,0.3);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--red);margin:0 auto 20px}
    .modal-box h3{font-size:20px;font-weight:800;color:#fff;margin-bottom:10px}
    .modal-box p{font-size:13px;color:var(--muted);line-height:1.65;margin-bottom:8px}
    .modal-box .booking-ref-display{font-family:var(--mono);font-size:13px;color:var(--green);background:var(--dark3) !important;border:1px solid var(--border);padding:8px 16px;border-radius:6px;display:inline-block;margin-bottom:24px}
    .modal-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .modal-btn{padding:12px;border-radius:9px;font-family:var(--font);font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .2s}
    .modal-btn-cancel-confirm{background:var(--red);color:#fff}
    .modal-btn-cancel-confirm:hover{background:#ff4444}
    .modal-btn-keep{background:var(--dark3) !important;color:var(--text);border:1px solid var(--border)}
    .modal-btn-keep:hover{background:var(--dark4) !important;color:#fff}

    .toast-bar{position:fixed;top:90px;left:50%;transform:translateX(-50%) translateY(-80px);padding:13px 26px;border-radius:100px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;z-index:6000;transition:transform .4s cubic-bezier(.34,1.56,.64,1)}
    .toast-bar.show{transform:translateX(-50%) translateY(0)}
    .toast-bar.success{background:rgba(46,204,113,0.15);border:1px solid rgba(46,204,113,0.35);color:var(--green)}
    .toast-bar.cancel{background:rgba(255,107,107,0.12);border:1px solid rgba(255,107,107,0.3);color:var(--red)}
    .toast-bar.error{background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.3);color:#ffc107}
    .copy-toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(10px);background:var(--dark3) !important;border:1px solid var(--border);color:var(--green);font-size:13px;font-weight:600;padding:10px 22px;border-radius:100px;opacity:0;pointer-events:none;transition:opacity .3s,transform .3s;z-index:200}
    .copy-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

    .payment-history{margin-top:16px}
    .payment-history-title{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .ph-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--dark3);border:1px solid var(--border);border-radius:9px;margin-bottom:8px}
    .ph-item:last-child{margin-bottom:0}
    .ph-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
    .ph-dot.full{background:rgba(46,204,113,0.15);color:var(--green)}
    .ph-dot.deposit{background:rgba(245,166,35,0.15);color:var(--gold)}
    .ph-dot.rental{background:rgba(26,140,255,0.15);color:var(--blue)}
    .ph-info{flex:1}
    .ph-label{font-size:12px;font-weight:700;color:#fff}
    .ph-date{font-size:10px;color:var(--muted);margin-top:1px}
    .ph-amt{font-family:var(--mono);font-size:13px;font-weight:800}
    .ph-amt.full{color:var(--green)}
    .ph-amt.deposit{color:var(--gold)}
    .ph-amt.rental{color:var(--blue)}

    @media(max-width:900px){.main-grid{grid-template-columns:1fr}.sidebar{position:static}}
    @media(max-width:540px){.info-grid,.driver-details-grid{grid-template-columns:1fr}.car-showcase{flex-direction:column;text-align:center}.car-showcase-img{width:170px}.car-specs{justify-content:center}.modal-actions{grid-template-columns:1fr}.trip-steps{padding:16px 12px}}
    
    #tripMap { height: 350px; width: 100%; border-radius: 12px; margin-top: 15px; border: 1px solid var(--border); z-index: 1; }
  </style>
</head>
<body>

<div class="bg-canvas"></div>
<div class="grid-lines"></div>
<div class="deco-bottom"></div>

<?php include("header.php"); ?>

<?php if($toast): ?>
<div class="toast-bar <?= $toast['type'] ?>" id="serverToast">
  <i class="fas <?= $toast['type']==='success'?'fa-check-circle':($toast['type']==='cancel'?'fa-times-circle':'fa-exclamation-circle') ?>"></i>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<?php endif; ?>
<div class="copy-toast" id="copyToast"><i class="fas fa-check" style="margin-right:6px"></i>Booking ID Copied!</div>

<div class="modal-overlay" id="cancelModal">
  <div class="modal-box">
    <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
    <h3>Cancel Booking?</h3>
    <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
    <div class="booking-ref-display"><?= $booking_ref ?></div>
    <div class="modal-actions">
      <button class="modal-btn modal-btn-keep" onclick="closeModal()"><i class="fas fa-arrow-left" style="margin-right:6px"></i>Keep It</button>
      <form method="POST" action="" style="margin:0">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
        <button type="submit" name="cancel_booking" class="modal-btn modal-btn-cancel-confirm" style="width:100%"><i class="fas fa-times" style="margin-right:6px"></i>Yes, Cancel</button>
      </form>
    </div>
  </div>
</div>

<div class="page-wrap">

  <!-- ===== BANNER ===== -->
  <div class="success-banner">
    <?php
      $ring_class  = $is_approved ? 'approved' : ($is_rejected ? 'rejected' : ($is_cancelled ? 'cancelled' : 'pending'));
      $banner_icon = $is_approved ? 'fa-check' : ($is_cancelled ? 'fa-times' : ($is_rejected ? 'fa-ban' : 'fa-clock'));
      $banner_title= $is_approved ? 'Booking Approved!' : ($is_cancelled ? 'Booking Cancelled' : ($is_rejected ? 'Booking Rejected' : 'Booking Confirmed!'));

      if ($is_fully_paid && $is_approved) {
          $banner_sub = 'Your booking is confirmed and full payment has been received. Enjoy your ride!';
      } elseif ($is_deposit_paid && $is_approved) {
          $banner_sub = 'Security deposit received. Remaining rental amount is due on vehicle return.';
      } elseif ($is_approved) {
          $banner_sub = 'Your booking has been approved. Complete your payment to confirm the ride.';
      } elseif ($is_cancelled) {
          $banner_sub = 'This booking has been cancelled.';
      } elseif ($is_rejected) {
          $banner_sub = 'Your booking was rejected by the admin. Please contact support or book again.';
      } else {
          $banner_sub = 'Your ride is reserved. Awaiting admin approval.';
      }
    ?>
    <div class="check-ring <?= $ring_class ?>"><i class="fas <?= $banner_icon ?>"></i></div>
    <h1><i class="fas fa-clipboard-list" style="font-size:0.75em;margin-right:10px;opacity:0.7"></i><?= $banner_title ?></h1>
    <p><?= $banner_sub ?></p>
    <div class="ref-pill">
      <span class="rl">Booking Ref</span>
      <span class="rv" id="refVal"><?= htmlspecialchars($booking_ref) ?></span>
      <button class="cp" onclick="copyRef()" title="Copy"><i class="fas fa-copy"></i></button>
    </div>

    <?php if ($is_fully_paid): ?>
      <div class="paid-banner-badge full-paid">
        <i class="fas fa-check-circle"></i>
        Full Payment Received &middot; &#8377;<?= number_format($full_paid_amount, 2) ?> paid on <?= date('d M Y', strtotime($full_payment_info['added_on'] ?? 'now')) ?>
      </div>
    <?php elseif ($is_deposit_paid && !$is_fully_paid): ?>
      <div class="paid-banner-badge deposit-paid">
        <i class="fas fa-shield-alt"></i>
        Security Deposit Paid &middot; &#8377;<?= number_format($deposit_paid_amount, 2) ?>
      </div>
      <div class="paid-banner-badge rental-due">
        <i class="fas fa-clock"></i>
        &#8377;<?= number_format($remaining_due, 2) ?> Rental Due on Vehicle Return
      </div>
    <?php endif; ?>

    <?php if ($is_approved && $trip_status === 'Completed'): ?>
      <div class="paid-banner-badge full-paid" style="margin-top:8px">
        <i class="fas fa-flag-checkered"></i>
        Trip Completed Successfully
      </div>
    <?php elseif ($is_approved && $trip_status === 'Started'): ?>
      <div class="paid-banner-badge rental-due" style="margin-top:8px">
        <i class="fas fa-play-circle"></i>
        Trip Currently In Progress
      </div>
    <?php endif; ?>
  </div>

  <div class="main-grid">

    <!-- ===== MAIN CONTENT ===== -->
    <div style="min-width:0">

      <!-- Route Map -->
      <div class="card">
        <div class="card-header">
            <div class="iw ib"><i class="fas fa-map-marked-alt"></i></div>
            <h3>Route Visualization</h3>
        </div>
        <div class="card-body">
            <div class="location-box">
              <div class="loc-icon"><i class="fas fa-map-marker-alt"></i></div>
              <div class="loc-text">
                <div class="lt">Pickup Location</div>
                <div class="lv"><?= htmlspecialchars($booking['pickup_location']) ?></div>
              </div>
            </div>
            <div class="location-box">
              <div class="loc-icon" style="color:var(--red); background:rgba(255,107,107,0.12);"><i class="fas fa-flag-checkered"></i></div>
              <div class="loc-text">
                <div class="lt">Drop Location</div>
                <div class="lv"><?= htmlspecialchars($booking['drop_location']) ?></div>
              </div>
            </div>
            <div id="tripMap"></div>
        </div>
      </div>

      <!-- ═══ TRIP STATUS CARD ═══ -->
      <?php if ($is_approved): ?>
      <div class="trip-status-card"
           style="--trip-color:<?= $tc['color'] ?>;--trip-bg:<?= $tc['bg'] ?>;--trip-border:<?= $tc['border'] ?>;--trip-glow-val:<?= $tc['glow'] ?>">

        <div class="trip-status-header">
          <div class="trip-status-icon-wrap <?= $trip_status === 'Started' ? 'pulsing' : '' ?>">
            <i class="fas <?= $tc['icon'] ?>"></i>
          </div>
          <div>
            <div class="trip-status-label"><?= $tc['label'] ?></div>
            <div class="trip-status-sub"><?= $tc['sub'] ?></div>
          </div>
          <span class="trip-status-badge">
            <span class="ts-dot <?= $trip_status === 'Started' ? 'blink' : '' ?>"></span>
            <?= strtoupper($trip_status) ?>
          </span>
        </div>

        <!-- Progress Steps -->
        <div class="trip-steps">

          <!-- Step 1: Booking Confirmed -->
          <div class="trip-step">
            <div class="ts-circle done"><i class="fas fa-check" style="font-size:13px"></i></div>
            <div class="ts-label done">Confirmed</div>
          </div>

          <div class="trip-step-line <?= $tc['step'] >= 2 ? 'done' : '' ?>"></div>

          <!-- Step 2: Trip Started -->
          <div class="trip-step">
            <?php if ($tc['step'] === 1): ?>
              <div class="ts-circle inactive"><i class="fas fa-play" style="font-size:11px"></i></div>
              <div class="ts-label inactive">Trip Start</div>
            <?php elseif ($tc['step'] === 2): ?>
              <div class="ts-circle active"><i class="fas fa-play" style="font-size:11px"></i></div>
              <div class="ts-label active">In Progress</div>
            <?php else: ?>
              <div class="ts-circle done"><i class="fas fa-check" style="font-size:13px"></i></div>
              <div class="ts-label done">Started</div>
            <?php endif; ?>
          </div>

          <div class="trip-step-line <?= $tc['step'] === 3 ? 'done' : '' ?>"></div>

          <!-- Step 3: Completed -->
          <div class="trip-step">
            <?php if ($tc['step'] < 3): ?>
              <div class="ts-circle inactive"><i class="fas fa-flag-checkered" style="font-size:11px"></i></div>
              <div class="ts-label inactive">Completed</div>
            <?php else: ?>
              <div class="ts-circle done"><i class="fas fa-flag-checkered" style="font-size:11px"></i></div>
              <div class="ts-label done">Completed</div>
            <?php endif; ?>
          </div>

        </div>

        <?php if ($trip_status === 'Started'): ?>
        <!-- Road animation for in-progress trip -->
        <div class="road-strip">
          <div class="road-dashes"></div>
          <span class="road-car">🚗</span>
          <span class="road-label">Your ride is currently in progress</span>
        </div>
        <?php elseif ($trip_status === 'Completed'): ?>
        <!-- Celebration block -->
        <div class="trip-complete-block">
          <div class="tcb-icon"><i class="fas fa-flag-checkered"></i></div>
          <div>
            <div class="tcb-title">Ride Completed — Thank You!</div>
            <div class="tcb-sub">We hope you had a great experience. See you on your next ride.</div>
          </div>
        </div>
        <?php endif; ?>

      </div>
      <?php endif; ?>

      <!-- Vehicle Details -->
      <div class="card">
        <div class="card-header">
          <div class="iw ib"><i class="fas fa-car"></i></div>
          <h3>Vehicle Details</h3>
          <span class="cbadge bs">Confirmed</span>
        </div>
        <div class="card-body">
          <div class="car-showcase">
            <img src="../Admin/pages/images/car_images/<?= htmlspecialchars($carRow['primary_image'] ?? '') ?>"
                 class="car-showcase-img"
                 alt="<?= htmlspecialchars($carRow['car_display_name'] ?? '') ?>"
                 onerror="this.src='images/bg_1.jpg';this.style.objectFit='cover';this.style.borderRadius='8px'">
            <div class="csi">
              <span class="brand-tag"><?= htmlspecialchars($carRow['brand_name'] ?? 'Brand') ?></span>
              <h2><?= htmlspecialchars($carRow['car_display_name'] ?? 'N/A') ?></h2>
              <?php if(!empty($carRow['car_number_plate'])): ?>
              <div class="plate-badge"><i class="fas fa-id-card"></i><?= htmlspecialchars($carRow['car_number_plate']) ?></div>
              <?php endif; ?>
              <div class="car-specs">
                <?php if(!empty($carRow['seating_capacity'])): ?><div class="spec"><i class="fas fa-users"></i><?= $carRow['seating_capacity'] ?> Seats</div><?php endif; ?>
                <?php if(!empty($carRow['gear_type'])): ?><div class="spec"><i class="fas fa-cogs"></i><?= htmlspecialchars($carRow['gear_type']) ?></div><?php endif; ?>
                <?php if(!empty($carRow['fuel_type'])): ?><div class="spec"><i class="fas fa-gas-pump"></i><?= htmlspecialchars($carRow['fuel_type']) ?></div><?php endif; ?>
                <?php if($ppd > 0): ?><div class="spec"><i class="fas fa-rupee-sign"></i>&#8377;<?= number_format($ppd,0) ?>/day</div><?php endif; ?>
              </div>
            </div>
          </div>

          <div class="sdiv"><span class="sl">Rental Period</span><div class="sline"></div></div>
          <div class="timeline-row">
            <div class="tp">
              <div class="tp-label">Pickup</div>
              <div class="tp-icon tg"><i class="fas fa-map-marker-alt"></i></div>
              <div class="tp-date"><?= date('d M Y', strtotime($booking['pickup_datetime'])) ?></div>
              <div class="tp-time"><?= date('h:i A', strtotime($booking['pickup_datetime'])) ?></div>
            </div>
            <div class="tdiv">
              <div class="tl"></div>
              <div class="dur-badge"><?php
                $parts=[];
                if($total_days>0) $parts[]=$total_days.'d';
                if($returnDT && $diff && $diff->h>0) $parts[]=$diff->h.'h';
                echo $parts ? implode(' + ',$parts) : '1d';
              ?></div>
              <div class="tl"></div>
            </div>
            <div class="tp">
              <div class="tp-label">Return</div>
              <div class="tp-icon tb"><i class="fas fa-flag-checkered"></i></div>
              <?php if($returnDT): ?>
              <div class="tp-date"><?= date('d M Y', strtotime($booking['actual_return_datetime'])) ?></div>
              <div class="tp-time"><?= date('h:i A', strtotime($booking['actual_return_datetime'])) ?></div>
              <?php else: ?><div class="tp-date" style="color:var(--muted);font-size:12px">Not set</div><?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Booking Information -->
      <div class="card">
        <div class="card-header">
          <div class="iw ig"><i class="fas fa-clipboard-list"></i></div>
          <h3>Booking Information</h3>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="ii">
              <div class="ii-label"><i class="fas fa-hashtag"></i>Booking ID</div>
              <div class="ii-value mono green"><?= $booking_ref ?></div>
            </div>
            <div class="ii">
              <div class="ii-label"><i class="fas fa-calendar-alt"></i>Booked On</div>
              <div class="ii-value"><?= date('d M Y', strtotime($booking['created_at'])) ?></div>
            </div>
            <div class="ii full">
              <div class="ii-label"><i class="fas fa-info-circle"></i>Booking Status</div>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div style="width:28px;height:28px;border-radius:50%;background:<?= $sc['bg'] ?>;border:1px solid <?= $sc['border'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;color:<?= $sc['color'] ?>;flex-shrink:0"><i class="fas <?= $sc['icon'] ?>"></i></div>
                <div>
                  <div style="font-size:14px;font-weight:800;color:<?= $sc['color'] ?>;line-height:1.2">
                    <?= $is_pending ? 'Not Approved' : htmlspecialchars($status) ?>
                    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= $sc['color'] ?>;margin-left:6px;vertical-align:middle;<?= $sc['dot_anim'] ? 'animation:blink 1.5s ease infinite' : '' ?>"></span>
                  </div>
                  <div style="font-size:11px;color:var(--muted);margin-top:2px">
                    <?php if($is_pending): ?>Awaiting admin review &amp; approval
                    <?php elseif($is_approved && $is_fully_paid): ?>Approved &amp; Fully Paid — your ride is confirmed!
                    <?php elseif($is_approved && $is_deposit_paid): ?>Approved &amp; Deposit Paid — rental amount due on return.
                    <?php elseif($is_approved): ?>Approved — complete your payment to confirm the ride!
                    <?php elseif($is_cancelled): ?>This booking has been cancelled
                    <?php elseif($is_rejected): ?>Rejected by admin — please contact support or book again
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <?php if ($is_approved): ?>
            <!-- Trip Status inline info block -->
            <div class="ii full" style="background:<?= $tc['bg'] ?> !important; border-color:<?= $tc['border'] ?>">
              <div class="ii-label"><i class="fas <?= $tc['icon'] ?>" style="color:<?= $tc['color'] ?>"></i>Trip Status</div>
              <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:4px">
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="width:28px;height:28px;border-radius:50%;background:<?= $tc['bg'] ?>;border:1px solid <?= $tc['border'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;color:<?= $tc['color'] ?>"><i class="fas <?= $tc['icon'] ?>"></i></div>
                  <div>
                    <div style="font-size:14px;font-weight:800;color:<?= $tc['color'] ?>;line-height:1.2"><?= $tc['label'] ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:1px"><?= $tc['sub'] ?></div>
                  </div>
                </div>
                <?php if ($trip_status === 'Completed'): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:100px;font-size:10px;font-weight:800;background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.3);color:var(--green)">
                  <i class="fas fa-check-circle"></i> RIDE DONE
                </div>
                <?php elseif ($trip_status === 'Started'): ?>
                <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:100px;font-size:10px;font-weight:800;background:rgba(26,140,255,0.1);border:1px solid rgba(26,140,255,0.3);color:var(--blue);animation:blink 1.6s ease infinite">
                  <i class="fas fa-circle"></i> LIVE
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Payment Status -->
            <div class="ii full">
              <div class="ii-label"><i class="fas fa-credit-card"></i>Payment Status</div>
              <?php if ($is_fully_paid): ?>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(46,204,113,.15);border:1px solid rgba(46,204,113,.3);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--green)"><i class="fas fa-check-circle"></i></div>
                <div>
                  <div style="font-size:14px;font-weight:800;color:var(--green);line-height:1.2">Fully Paid <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--green);margin-left:6px;vertical-align:middle"></span></div>
                  <div style="font-size:11px;color:var(--muted);margin-top:2px">&#8377;<?= number_format($full_paid_amount, 2) ?> (Full Payment) &middot; <?= date('d M Y', strtotime($full_payment_info['added_on'] ?? 'now')) ?><span style="display:block;margin-top:2px;color:var(--green)"><i class="fas fa-check" style="margin-right:3px;font-size:9px"></i>No outstanding dues</span></div>
                </div>
              </div>
              <?php elseif ($is_deposit_paid): ?>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(245,166,35,.15);border:1px solid rgba(245,166,35,.3);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--gold)"><i class="fas fa-shield-alt"></i></div>
                <div>
                  <div style="font-size:14px;font-weight:800;color:var(--gold);line-height:1.2">Deposit Paid <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--gold);margin-left:6px;vertical-align:middle"></span></div>
                  <div style="font-size:11px;color:var(--muted);margin-top:2px">&#8377;<?= number_format($deposit_paid_amount, 2) ?> (Security Deposit) &middot; <?= date('d M Y', strtotime($deposit_payment_info['added_on'] ?? 'now')) ?><span style="display:block;margin-top:2px;color:rgba(245,166,35,0.85)"><i class="fas fa-clock" style="margin-right:3px;font-size:9px"></i>&#8377;<?= number_format($remaining_due, 2) ?> due on vehicle return</span></div>
                </div>
              </div>
              <?php elseif($is_approved): ?>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,193,7,.12);border:1px solid rgba(255,193,7,.28);display:flex;align-items:center;justify-content:center;font-size:12px;color:#ffc107"><i class="fas fa-clock"></i></div>
                <div><div style="font-size:14px;font-weight:800;color:#ffc107;line-height:1.2">Payment Pending</div><div style="font-size:11px;color:var(--muted);margin-top:2px">Complete payment to confirm your ride</div></div>
              </div>
              <?php else: ?>
              <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--muted)"><i class="fas fa-minus-circle"></i></div>
                <div style="font-size:13px;color:var(--muted)">Not applicable</div>
              </div>
              <?php endif; ?>
            </div>

            <?php if(!empty($booking['pan_aadhar_no'])): ?>
            <div class="ii full">
              <div class="ii-label"><i class="fas fa-id-card"></i>PAN / Aadhaar</div>
              <div class="ii-value mono"><?= htmlspecialchars($booking['pan_aadhar_no']) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($is_paid): ?>
            <div class="ii full" style="background:<?= $is_fully_paid ? 'rgba(46,204,113,0.07)' : 'rgba(245,166,35,0.06)' ?> !important;border-color:<?= $is_fully_paid ? 'rgba(46,204,113,0.25)' : 'rgba(245,166,35,0.25)' ?>">
              <div class="ii-label"><i class="fas fa-rupee-sign" style="color:<?= $is_fully_paid ? 'var(--green)' : 'var(--gold)' ?>"></i>Amount Paid</div>
              <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:4px">
                <div>
                  <div style="font-size:20px;font-weight:800;font-family:var(--mono);color:<?= $is_fully_paid ? 'var(--green)' : 'var(--gold)' ?>">&#8377;<?= number_format($total_paid, 2) ?></div>
                  <div style="font-size:11px;color:var(--muted);margin-top:3px"><?= $is_fully_paid ? 'Full payment — no dues remaining' : 'Security deposit — &#8377;'.number_format($remaining_due,2).' due on return' ?></div>
                </div>
                <?php if ($remaining_due > 0 && !$is_fully_paid): ?>
                <div style="text-align:right">
                  <div style="font-size:10px;color:var(--muted);margin-bottom:2px">Remaining Due</div>
                  <div style="font-size:16px;font-weight:800;font-family:var(--mono);color:var(--blue)">&#8377;<?= number_format($remaining_due, 2) ?></div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>

      <!-- Driver Details -->
      <div class="card">
        <div class="card-header">
          <div class="iw ip"><i class="fas fa-user-tie"></i></div>
          <h3>Driver Details</h3>
          <?php if($is_approved && $driver_id>0): ?><span class="cbadge bs">Assigned</span>
          <?php elseif($is_pending && $driver_id>0): ?><span class="cbadge bp">Pending Assignment</span>
          <?php elseif($driver_id==0): ?><span class="cbadge bb">Self Drive</span>
          <?php endif; ?>
        </div>
        <div class="card-body">

          <?php if(($is_approved || $is_cancelled) && $driver_id>0 && !empty($driverRow)): ?>
          <div class="driver-card-inner">
            <div class="driver-avatar-wrap">
              <?php if(!empty($driverRow['profile_image'])): ?>
              <img src="../../Driver/images/driver_profile/<?= htmlspecialchars($driverRow['profile_image']) ?>"
                   class="driver-avatar"
                   onerror="this.outerHTML='<div class=\'driver-avatar-placeholder\'><i class=\'fas fa-user-tie\'></i></div>'" alt="Driver">
              <?php else: ?><div class="driver-avatar-placeholder"><i class="fas fa-user-tie"></i></div><?php endif; ?>
              <?php if(!empty($driverRow['experience_years']) && $driverRow['experience_years']>0): ?>
              <div class="driver-exp-badge"><?= $driverRow['experience_years'] ?>yr exp</div>
              <?php endif; ?>
            </div>
            <div>
              <div class="dn"><?= htmlspecialchars($driverRow['driver_name'] ?? 'N/A') ?></div>
              <?php if(!empty($driverRow['driver_mobile'])): ?><div class="dmeta"><i class="fas fa-phone"></i><?= htmlspecialchars($driverRow['driver_mobile']) ?></div><?php endif; ?>
              <?php if(!empty($driverRow['driver_email'])): ?><div class="dmeta"><i class="fas fa-envelope"></i><?= htmlspecialchars($driverRow['driver_email']) ?></div><?php endif; ?>
              <?php if(!empty($driverRow['license_number'])): ?><div class="dmeta"><i class="fas fa-id-badge"></i>License: <?= htmlspecialchars($driverRow['license_number']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php if(!empty($driverRow['license_expiry_date']) || !empty($driverRow['aadhar_number'])): ?>
          <div class="driver-details-grid">
            <?php if(!empty($driverRow['license_expiry_date'])): ?>
            <div class="dd-item"><div class="dd-label"><i class="fas fa-calendar-times" style="color:var(--gold)"></i>License Expiry</div><div class="dd-value"><?= date('d M Y', strtotime($driverRow['license_expiry_date'])) ?></div></div>
            <?php endif; ?>
            <?php if(!empty($driverRow['aadhar_number'])): ?>
            <div class="dd-item"><div class="dd-label"><i class="fas fa-fingerprint" style="color:var(--blue)"></i>Aadhaar</div><div class="dd-value" style="font-family:var(--mono);font-size:12px"><?= htmlspecialchars($driverRow['aadhar_number']) ?></div></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php elseif($is_rejected): ?>
          <div style="display:flex;align-items:center;gap:12px;padding:16px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:10px">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;font-size:18px;color:#ff6b6b;flex-shrink:0"><i class="fas fa-ban"></i></div>
            <div><div style="font-size:14px;font-weight:700;color:#ff6b6b">Booking Rejected</div><div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:3px">Driver details are unavailable for rejected bookings.</div></div>
          </div>

          <?php elseif($is_pending): ?>
            <?php if($driver_id>0 && !empty($driverRow)): ?>
            <div style="background:rgba(255,193,7,0.05);border:1px solid rgba(255,193,7,0.2);border-radius:10px;padding:4px 0 0;overflow:hidden">
              <div style="display:flex;align-items:center;gap:7px;padding:7px 14px;background:rgba(255,193,7,0.08);border-bottom:1px solid rgba(255,193,7,0.15)">
                <i class="fas fa-clock" style="font-size:10px;color:#ffc107"></i>
                <span style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#ffc107">Selected — Awaiting Admin Assignment</span>
              </div>
              <div style="padding:16px">
                <div class="driver-card-inner">
                  <div class="driver-avatar-wrap">
                    <?php if(!empty($driverRow['profile_image'])): ?>
                    <img src="../Admin/pages/images/driver_images/<?= htmlspecialchars($driverRow['profile_image']) ?>"
                         class="driver-avatar"
                         onerror="this.outerHTML='<div class=\'driver-avatar-placeholder\'><i class=\'fas fa-user-tie\'></i></div>'" alt="Driver">
                    <?php else: ?><div class="driver-avatar-placeholder"><i class="fas fa-user-tie"></i></div><?php endif; ?>
                    <?php if(!empty($driverRow['experience_years']) && $driverRow['experience_years']>0): ?>
                    <div class="driver-exp-badge"><?= $driverRow['experience_years'] ?>yr exp</div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="dn"><?= htmlspecialchars($driverRow['driver_name'] ?? 'N/A') ?></div>
                    <?php if(!empty($driverRow['driver_mobile'])): ?><div class="dmeta"><i class="fas fa-phone"></i><?= htmlspecialchars($driverRow['driver_mobile']) ?></div><?php endif; ?>
                    <?php if(!empty($driverRow['driver_email'])): ?><div class="dmeta"><i class="fas fa-envelope"></i><?= htmlspecialchars($driverRow['driver_email']) ?></div><?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php else: ?>
            <div class="self-drive-badge" style="margin-bottom:0">
              <div class="sd-icon"><i class="fas fa-user-circle"></i></div>
              <div><div class="sdt">Self Drive Selected</div><div class="sds">You can assign a driver below before admin approval.</div></div>
            </div>
            <?php endif; ?>

            <div class="change-driver-panel">
              <div class="cdp-label"><i class="fas fa-exchange-alt"></i>Change Driver Preference</div>
              <div class="cdp-avail-note info" id="cdpNote">
                <i class="fas fa-calendar-check"></i>
                Showing drivers available for your booking dates (<?= date('d M',$booking_pickup_ts) ?> – <?= date('d M',$booking_return_ts) ?>).
              </div>
              <form method="POST" action="" id="changeDriverForm">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                <div class="driver-select-wrap">
                  <i class="fas fa-user-tie"></i>
                  <select name="new_driver_id" id="changeDriverSelect" class="driver-select">
                    <option value="0" <?= $driver_id==0?'selected':'' ?>>— Self Drive (No Driver) —</option>
                    <?php foreach($allDrivers as $d):
                      $d_id = intval($d['driver_id']);
                      $is_unavailable = false;
                      foreach($bookedSlots as $slot){
                        if(intval($slot['driver_id'])!==$d_id) continue;
                        $ss = strtotime($slot['pickup_datetime']);
                        $se = (!empty($slot['actual_return_datetime']) && $slot['actual_return_datetime']!=='0000-00-00 00:00:00') ? strtotime($slot['actual_return_datetime']) : ($ss+86400);
                        if($booking_pickup_ts < $se && $booking_return_ts > $ss){ $is_unavailable=true; break; }
                      }
                      $exp = !empty($d['experience_years']) ? ' ('.$d['experience_years'].'yr exp)' : '';
                      $mob = !empty($d['driver_mobile']) ? ' · '.$d['driver_mobile'] : '';
                    ?>
                    <option value="<?= $d_id ?>" <?= $driver_id==$d_id?'selected':'' ?> <?= $is_unavailable?'disabled':'' ?>>
                      <?= htmlspecialchars($d['driver_name'].$exp.$mob) ?><?= $is_unavailable?' — Unavailable on your dates':'' ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" name="change_driver" class="btn-change-driver"><i class="fas fa-check"></i> Update Driver</button>
              </form>
            </div>

          <?php else: ?>
          <div class="self-drive-badge">
            <div class="sd-icon"><i class="fas fa-user-circle"></i></div>
            <div><div class="sdt">Self Drive</div><div class="sds">No driver assigned — you will drive yourself.</div></div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">

      <!-- Customer Details -->
      <div class="card">
        <div class="card-header">
          <div class="iw ib"><i class="fas fa-user"></i></div>
          <h3>Customer Details</h3>
        </div>
        <div class="card-body">
          <div class="user-top">
            <img src="user_profile/<?= htmlspecialchars($userRow['photo'] ?? '') ?>" class="user-avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'" alt="User">
            <div><div class="un"><?= htmlspecialchars($userRow['uname'] ?? '') ?></div><div class="ue"><?= htmlspecialchars($userRow['email'] ?? '') ?></div></div>
          </div>
          <?php if(!empty($userRow['mobno'])): ?>
          <div class="urow"><div class="ui-icon"><i class="fas fa-phone"></i></div><div><div class="ul">Mobile</div><div class="uv"><?= htmlspecialchars($userRow['mobno']) ?></div></div></div>
          <?php endif; ?>
          <div class="urow"><div class="ui-icon"><i class="fas fa-envelope"></i></div><div><div class="ul">Email</div><div class="uv" style="word-break:break-all"><?= htmlspecialchars($userRow['email'] ?? '') ?></div></div></div>
          <?php if(!empty($userRow['address'])): ?>
          <div class="urow"><div class="ui-icon"><i class="fas fa-map-marker-alt"></i></div><div><div class="ul">Address</div><div class="uv"><?= htmlspecialchars($userRow['address']) ?><?= !empty($userRow['pin'])?' - '.htmlspecialchars($userRow['pin']):'' ?></div></div></div>
          <?php endif; ?>
          <?php if(!empty($booking['pan_aadhar_no'])): ?>
          <div class="urow"><div class="ui-icon"><i class="fas fa-id-card"></i></div><div><div class="ul">ID Document</div><div class="uv" style="font-family:var(--mono);font-size:12px"><?= htmlspecialchars($booking['pan_aadhar_no']) ?></div></div></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Fare Breakdown -->
      <div class="card">
        <div class="card-header">
          <div class="iw io"><i class="fas fa-rupee-sign"></i></div>
          <h3>Fare Breakdown</h3>
          <?php if ($is_fully_paid): ?>
            <span class="cbadge bs">Fully Paid</span>
          <?php elseif ($is_deposit_paid): ?>
            <span class="cbadge bg">Deposit Paid</span>
          <?php elseif ($is_approved): ?>
            <span class="cbadge bp">Payment Due</span>
          <?php endif; ?>
        </div>
        <div class="card-body">

          <?php if ($is_fully_paid): ?>
          <div class="payment-summary-strip full">
            <div class="pss-left">
              <div class="pss-icon full"><i class="fas fa-check-circle"></i></div>
              <div><div class="pss-title">Full Payment Done</div><div class="pss-sub">No outstanding dues &middot; Deposit refundable on return</div></div>
            </div>
            <div class="pss-right">
              <div class="pss-amount full">&#8377;<?= number_format($full_paid_amount, 2) ?></div>
              <div class="pss-date"><?= date('d M Y', strtotime($full_payment_info['added_on'] ?? 'now')) ?></div>
            </div>
          </div>
          <?php elseif ($is_deposit_paid): ?>
          <div class="payment-summary-strip deposit">
            <div class="pss-left">
              <div class="pss-icon deposit"><i class="fas fa-shield-alt"></i></div>
              <div><div class="pss-title">Security Deposit Paid</div><div class="pss-sub">&#8377;<?= number_format($remaining_due, 2) ?> rental due on vehicle return</div></div>
            </div>
            <div class="pss-right">
              <div class="pss-amount deposit">&#8377;<?= number_format($deposit_paid_amount, 2) ?></div>
              <div class="pss-date"><?= date('d M Y', strtotime($deposit_payment_info['added_on'] ?? 'now')) ?></div>
            </div>
          </div>
          <?php endif; ?>

          <?php if($total_days>0 && $ppd>0): ?>
          <div class="price-row sub"><span class="pl"><i class="fas fa-calendar-day"></i><?= $total_days ?> day<?= $total_days>1?'s':'' ?> × &#8377;<?= number_format($ppd,0) ?></span><span class="pv">&#8377;<?= number_format($day_amount,2) ?></span></div>
          <?php endif; ?>
          <?php if($returnDT && $extra_hours>0 && $pph>0): ?>
          <div class="price-row sub"><span class="pl"><i class="fas fa-clock"></i><?= $extra_hours ?> hr<?= $extra_hours>1?'s':'' ?> × &#8377;<?= number_format($pph,0) ?></span><span class="pv">&#8377;<?= number_format($hour_amount,2) ?></span></div>
          <?php endif; ?>
          <div class="price-row"><span class="pl"><i class="fas fa-calculator"></i>Base Rent</span><span class="pv">&#8377;<?= number_format($base_amount,2) ?></span></div>
          <?php if($driver_id>0): ?>
          <div class="price-row dr"><span class="pl"><i class="fas fa-user-tie"></i>Driver Charge (10%)</span><span class="pv">&#8377;<?= number_format($driver_charge,2) ?></span></div>
          <?php endif; ?>
          <div class="price-row"><span class="pl"><i class="fas fa-percent"></i>GST (5%)</span><span class="pv" style="color:rgba(255,255,255,0.6)">&#8377;<?= number_format($gst,2) ?></span></div>
          <?php if($security_dep>0): ?>
          <div class="price-row dep"><span class="pl"><i class="fas fa-shield-alt"></i>Security Deposit</span><span class="pv">&#8377;<?= number_format($security_dep,2) ?></span></div>
          <?php endif; ?>
          <div class="price-row total-row"><span class="pl">Grand Total</span><span class="pv">&#8377;<?= number_format($grand_total,2) ?></span></div>

          <?php if ($is_fully_paid): ?>
          <div class="paid-box full-paid">
            <div class="paid-box-title full"><i class="fas fa-check-circle"></i> Full Payment Confirmed</div>
            <div class="paid-info-row"><span class="pil">Amount Paid</span><span class="piv green">&#8377;<?= number_format($full_paid_amount, 2) ?></span></div>
            <div class="paid-info-row"><span class="pil">Payment Type</span><span class="piv">Full Payment</span></div>
            <div class="paid-info-row"><span class="pil">Payment Date</span><span class="piv"><?= date('d M Y', strtotime($full_payment_info['added_on'] ?? 'now')) ?></span></div>
            <div class="paid-info-row"><span class="pil">Balance Due</span><span class="piv green">&#8377;0.00 <span style="font-size:10px;color:var(--muted)">(fully settled)</span></span></div>
          </div>
          <p style="font-size:11px;color:var(--muted);margin-top:10px;line-height:1.65;padding:10px 12px;background:rgba(46,204,113,0.05);border-radius:8px;border:1px solid rgba(46,204,113,0.12)">
            <i class="fas fa-info-circle" style="color:var(--green);margin-right:4px"></i>
            Security deposit of &#8377;<?= number_format($security_dep,2) ?> is fully refundable upon safe vehicle return.
          </p>
          <div class="payment-history">
            <div class="payment-history-title"><i class="fas fa-history"></i> Payment History</div>
            <?php foreach($all_payments as $ph): $ptype = ($ph['payment_type'] ?? 'deposit'); ?>
            <div class="ph-item">
              <div class="ph-dot <?= $ptype ?>"><i class="fas <?= $ptype==='full' ? 'fa-check-circle' : 'fa-shield-alt' ?>"></i></div>
              <div class="ph-info"><div class="ph-label"><?= $ptype==='full' ? 'Full Payment' : 'Security Deposit' ?></div><div class="ph-date"><?= date('d M Y, h:i A', strtotime($ph['added_on'] ?? 'now')) ?></div></div>
              <div class="ph-amt <?= $ptype ?>">&#8377;<?= number_format(floatval($ph['paid_amount']),2) ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php elseif ($is_deposit_paid): ?>
          <div class="paid-box deposit-paid">
            <div class="paid-box-title deposit"><i class="fas fa-shield-alt"></i> Security Deposit Paid</div>
            <div class="paid-info-row"><span class="pil">Deposit Paid</span><span class="piv gold">&#8377;<?= number_format($deposit_paid_amount, 2) ?></span></div>
            <div class="paid-info-row"><span class="pil">Payment Date</span><span class="piv"><?= date('d M Y', strtotime($deposit_payment_info['added_on'] ?? 'now')) ?></span></div>
            <div class="paid-info-row"><span class="pil">Rental Amount</span><span class="piv">&#8377;<?= number_format($rent_only, 2) ?></span></div>
            <div class="paid-info-row"><span class="pil">Balance Due</span><span class="piv blue">&#8377;<?= number_format($remaining_due, 2) ?> <span style="font-size:10px;color:var(--muted)">(on return)</span></span></div>
          </div>
          <div class="pay-remaining-box">
            <div class="prb-title"><i class="fas fa-file-invoice-dollar"></i> Pay Remaining Rental</div>
            <div class="prb-amount-row">
              <div><div class="prb-label">Amount Due</div><div style="font-size:11px;color:var(--muted);margin-top:2px">Rental + GST (excludes deposit already paid)</div></div>
              <div class="prb-amount">&#8377;<?= number_format($remaining_due, 2) ?></div>
            </div>
            <form method="post">
              <input type="hidden" name="payment_type" value="rental">
              <button type="submit" name="select_payment" class="btn-cta btn-blue" style="margin-top:0">
                <i class="fas fa-lock"></i>Pay Remaining Rental
                <span style="margin-left:auto;font-family:var(--mono);font-size:12px;font-weight:700;opacity:0.9">&#8377;<?= number_format($remaining_due, 2) ?></span>
              </button>
            </form>
          </div>
          <p style="font-size:11px;color:var(--muted);margin-top:10px;line-height:1.65;padding:10px 12px;background:rgba(245,166,35,0.05);border-radius:8px;border:1px solid rgba(245,166,35,0.12)">
            <i class="fas fa-info-circle" style="color:var(--gold);margin-right:4px"></i>
            Security deposit of &#8377;<?= number_format($security_dep,2) ?> is fully refundable upon safe vehicle return.
          </p>
          <div class="payment-history">
            <div class="payment-history-title"><i class="fas fa-history"></i> Payment History</div>
            <?php foreach($all_payments as $ph): $ptype = ($ph['payment_type'] ?? 'deposit'); ?>
            <div class="ph-item">
              <div class="ph-dot <?= $ptype ?>"><i class="fas <?= $ptype==='full' ? 'fa-check-circle' : 'fa-shield-alt' ?>"></i></div>
              <div class="ph-info"><div class="ph-label"><?= $ptype==='full' ? 'Full Payment' : 'Security Deposit' ?></div><div class="ph-date"><?= date('d M Y, h:i A', strtotime($ph['added_on'] ?? 'now')) ?></div></div>
              <div class="ph-amt <?= $ptype ?>">&#8377;<?= number_format(floatval($ph['paid_amount']),2) ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php elseif($is_approved): ?>
          <div class="pay-now-box">
            <div class="pnb-title"><i class="fas fa-bolt"></i> Select Payment Option</div>
            <form method="post" id="paymentSelectionForm">
              <div class="payment-options">
                <label class="payment-option-label">
                  <input type="radio" name="payment_type" value="deposit" <?= ($_SESSION['payment_choice'] == 'deposit') ? 'checked' : '' ?> onchange="updatePayBtn(this)">
                  <div class="payment-option-card">
                    <div class="radio-dot"></div>
                    <div class="poc-info"><div class="poc-title">Security Deposit Only</div><div class="poc-sub">Pay deposit now · rental due on return</div></div>
                    <div class="poc-amount gold">&#8377;<?= number_format($deposit_only,2) ?></div>
                  </div>
                </label>
                <label class="payment-option-label">
                  <input type="radio" name="payment_type" value="full" <?= ($_SESSION['payment_choice'] == 'full') ? 'checked' : '' ?> onchange="updatePayBtn(this)">
                  <div class="payment-option-card">
                    <div class="radio-dot"></div>
                    <div class="poc-info"><div class="poc-title">Full Payment</div><div class="poc-sub">Pay everything now · no dues later</div></div>
                    <div class="poc-amount">&#8377;<?= number_format($full_payment,2) ?></div>
                  </div>
                </label>
              </div>
              <button type="submit" name="select_payment" id="payNowBtn" class="btn-cta btn-green" style="margin-top:4px">
                <i class="fas fa-lock"></i>Proceed to Payment
                <span id="payBtnAmount" style="margin-left:auto;font-family:var(--mono);font-size:12px;font-weight:700;opacity:0.9">&#8377;<?= number_format($current_payable,2) ?></span>
              </button>
            </form>
          </div>
          <p style="font-size:11px;color:var(--muted);margin-top:10px;line-height:1.65;padding:10px 12px;background:rgba(26,140,255,0.05);border-radius:8px;border:1px solid rgba(26,140,255,0.12)">
            <i class="fas fa-info-circle" style="color:var(--blue);margin-right:4px"></i>
            Security deposit of &#8377;<?= number_format($security_dep,2) ?> is fully refundable on vehicle return.
          </p>

          <?php else: ?>
          <?php if($security_dep>0): ?>
          <p style="font-size:11px;color:var(--muted);margin-top:12px;line-height:1.65;padding:10px 12px;background:rgba(245,166,35,0.05);border-radius:8px;border:1px solid rgba(245,166,35,0.12)">
            <i class="fas fa-info-circle" style="color:var(--gold);margin-right:5px"></i>
            Security deposit of &#8377;<?= number_format($security_dep,2) ?> is fully refundable upon safe vehicle return.
          </p>
          <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>

      <!-- Action Buttons -->
      <a href="profile.php" class="btn-cta btn-purple"><i class="fas fa-user-circle"></i>Go to My Profile</a>
      <a href="car.php" class="btn-cta btn-blue"><i class="fas fa-car-side"></i>Book Another Car</a>

      <?php if ($is_fully_paid): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:rgba(46,204,113,0.08);border:1px solid rgba(46,204,113,0.25);border-radius:9px;font-size:13px;font-weight:700;color:var(--green)">
        <i class="fas fa-check-circle" style="font-size:16px"></i>Full Payment Completed
      </div>
      <?php elseif ($is_deposit_paid && $is_approved): ?>
      <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:rgba(245,166,35,0.08);border:1px solid rgba(245,166,35,0.25);border-radius:9px;font-size:13px;font-weight:700;color:var(--gold)">
        <i class="fas fa-shield-alt" style="font-size:16px"></i>Deposit Paid · Rental Due on Return
      </div>
      <?php endif; ?>

      <?php if($is_pending || ($is_approved && !$is_paid)): ?>
<button class="btn-cta btn-cancel" onclick="openModal()">
  <i class="fas fa-times-circle"></i>Cancel This Booking
</button>
<?php endif; ?>

      <?php if($is_cancelled): ?>
      <div style="padding:14px 16px;background:rgba(255,107,107,0.07);border:1px solid rgba(255,107,107,0.2);border-radius:10px;text-align:center">
        <i class="fas fa-times-circle" style="color:var(--red);font-size:18px;margin-bottom:8px;display:block"></i>
        <div style="font-size:13px;font-weight:700;color:var(--red)">Booking Cancelled</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">This booking has been cancelled and is no longer active.</div>
      </div>
      <?php endif; ?>

      <?php if($is_rejected): ?>
      <div style="padding:14px 16px;background:rgba(255,107,107,0.07);border:1px solid rgba(255,107,107,0.2);border-radius:10px;text-align:center">
        <i class="fas fa-ban" style="color:var(--red);font-size:18px;margin-bottom:8px;display:block"></i>
        <div style="font-size:13px;font-weight:700;color:var(--red)">Booking Rejected</div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px">Rejected by admin. Please contact support or make a new booking.</div>
      </div>
      <a href="car.php" class="btn-cta btn-blue"><i class="fas fa-redo"></i>Book a New Car</a>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script>
var depositAmt = <?= json_encode(number_format($deposit_only,2)) ?>;
var fullAmt    = <?= json_encode(number_format($full_payment,2)) ?>;

function updatePayBtn(radio) {
  var btn = document.getElementById('payBtnAmount');
  if (!btn) return;
  btn.innerHTML = '&#8377;' + (radio.value === 'full' ? fullAmt : depositAmt);
}

document.addEventListener('DOMContentLoaded', function() {
    var pLat = <?= !empty($booking['pickup_lat']) ? (float)$booking['pickup_lat'] : 'null' ?>;
    var pLng = <?= !empty($booking['pickup_lng']) ? (float)$booking['pickup_lng'] : 'null' ?>;
    var dLat = <?= !empty($booking['drop_lat']) ? (float)$booking['drop_lat'] : 'null' ?>;
    var dLng = <?= !empty($booking['drop_lng']) ? (float)$booking['drop_lng'] : 'null' ?>;

    if(pLat && pLng && dLat && dLng) {
        var map = L.map('tripMap').setView([pLat, pLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        var pickupIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        var dropIcon   = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        var pMark = L.marker([pLat, pLng], {icon: pickupIcon}).addTo(map).bindPopup("<b>Pickup Location</b>").openPopup();
        var dMark = L.marker([dLat, dLng], {icon: dropIcon}).addTo(map).bindPopup("<b>Drop Location</b>");
        var group = new L.featureGroup([pMark, dMark]);
        map.fitBounds(group.getBounds().pad(0.3));
        setTimeout(function() { map.invalidateSize(); }, 800);
    } else {
        document.getElementById('tripMap').innerHTML = "<div style='display:flex; align-items:center; justify-content:center; height:100%; color:var(--muted); font-size:14px;'><i class='fas fa-map-marker-alt' style='margin-right:8px;'></i>Map coordinates not found for this booking.</div>";
    }
});

(function(){
  var nav = document.getElementById('ftco-navbar');
  if(!nav) return;
  function onScroll(){ nav.classList.toggle('scrolled', window.scrollY > 60); }
  onScroll();
  window.addEventListener('scroll', onScroll, {passive:true});
})();

function openModal()  { document.getElementById('cancelModal').classList.add('open'); }
function closeModal() { document.getElementById('cancelModal').classList.remove('open'); }
document.getElementById('cancelModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });

function copyRef(){
  var val = document.getElementById('refVal').textContent;
  navigator.clipboard.writeText(val).then(function(){
    var t = document.getElementById('copyToast');
    t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 2400);
  });
}

<?php if($toast): ?>
(function(){
  var t = document.getElementById('serverToast');
  if(!t) return;
  setTimeout(function(){ t.classList.add('show'); }, 100);
  setTimeout(function(){ t.classList.remove('show'); }, 4000);
})();
<?php endif; ?>

<?php if($is_pending): ?>
(function(){
  var sel  = document.getElementById('changeDriverSelect');
  var note = document.getElementById('cdpNote');
  if(!sel) return;
  sel.addEventListener('change', function(){
    var chosen = sel.options[sel.selectedIndex];
    if(chosen && chosen.disabled){
      sel.value = '0';
      note.className = 'cdp-avail-note warn';
      note.innerHTML = '<i class="fas fa-exclamation-triangle"></i> That driver is unavailable on your dates. Switched to Self Drive.';
      setTimeout(function(){
        note.className = 'cdp-avail-note info';
        note.innerHTML = '<i class="fas fa-calendar-check"></i> Showing drivers available for your booking dates (<?= date("d M",$booking_pickup_ts) ?> – <?= date("d M",$booking_return_ts) ?>).';
      }, 3500);
    }
  });
})();
<?php endif; ?>
</script>

</body>
</html>