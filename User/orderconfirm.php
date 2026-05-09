<?php
if (session_status() === PHP_SESSION_NONE) { 
session_name('user_session');  
session_start(); }
require("connect.php");

$uid = $_SESSION['user_id'] ?? null;
if (!isset($uid)) { header("location: register.php"); exit; }
$uid_safe = intval($uid);

// ── Load payment data from session (set by booking_details.php) ───────────────
$pay = $_SESSION['booking_payment'] ?? null;
if (!$pay) {
    $bQ = mysqli_query($con, "SELECT bm.*, c.car_display_name, c.primary_image, b.brand_name FROM booking_master bm LEFT JOIN car_master c ON c.car_id=bm.car_id LEFT JOIN brand_master b ON b.brand_id=c.brand_id WHERE bm.ui=$uid_safe AND bm.booking_status='Approved' ORDER BY bm.booking_id DESC LIMIT 1");
    if (!$bQ || mysqli_num_rows($bQ) == 0) {
        header("location: booking_details.php"); exit;
    }
    $br = mysqli_fetch_assoc($bQ);
    header("location: booking_details.php?bid=".$br['booking_id']); exit;
}

// ── Unpack session ────────────────────────────────────────────────────────────
$booking_id    = $pay['booking_id'];
$booking_ref   = $pay['booking_ref'];
$grand_total   = $pay['grand_total'];
$rent_only     = $pay['rent_only'];
$full_payment  = $pay['full_payment'];
$deposit_only  = $pay['deposit_only'];
$security_dep  = $pay['security_dep'];
$base_amount   = $pay['base_amount'];
$driver_charge = $pay['driver_charge'];
$gst           = $pay['gst'];
$car_name      = $pay['car_name'];
$car_image     = $pay['car_image'];
$brand_name    = $pay['brand_name'];
$pickup_date   = $pay['pickup_date'];
$return_date   = $pay['return_date'];
$driver_name   = $pay['driver_name'];
$user_name     = $pay['user_name'];
$user_email    = $pay['user_email'];
$user_phone    = $pay['user_phone'];
$user_address  = $pay['user_address'];
$days          = $pay['days'];
$ppd           = $pay['ppd'];
// ── FETCH PAYMENT HISTORY ─────────────────────────
$total_paid = 0;
$is_deposit_paid = false;
$is_full_paid = false;

$pmt_q = mysqli_query($con,
    "SELECT * FROM payment_master 
     WHERE booking_id = $booking_id"
);

if ($pmt_q) {
    while ($row = mysqli_fetch_assoc($pmt_q)) {
        $total_paid += floatval($row['paid_amount']);

        if ($row['payment_type'] == 'deposit') {
            $is_deposit_paid = true;
        }

        if (($row['payment_status'] ?? 0) == 2) {
            $is_full_paid = true;
        }
    }
}

// ✅ calculate AFTER loop
$remaining_due = max(0, $grand_total - $total_paid);

// ── Determine what is payable now based on choice ─────────────────────────────
// ── DETERMINE WHAT TO PAY NOW ─────────────────────

$choice = $_SESSION['payment_choice'] ?? 'deposit';

$choice = $_SESSION['payment_choice'] ?? 'deposit';

// ✅ Case 1: Already fully paid (REAL full payment in DB)
if ($is_full_paid) {
    $now_payable = 0;
    $choice_label = "Fully Paid";

// ✅ Case 2: Deposit already paid → pay remaining
} elseif ($is_deposit_paid) {
    $now_payable = $remaining_due;
    $choice_label = "Remaining Rental Payment";

// ✅ Case 3: User choosing payment for first time
} else {
    if ($choice === 'full') {
        $now_payable = $grand_total; // 🔥 IMPORTANT CHANGE
        $choice_label = "Full Payment";
    } else {
        $now_payable = $deposit_only;
        $choice_label = "Security Deposit";
    }
}

// ── Fetch user from DB (fresher data) ─────────────────────────────────────────
$userQ   = mysqli_query($con, "SELECT * FROM users_master WHERE ui=$uid_safe");
$userRow = mysqli_fetch_assoc($userQ) ?: [];

// ── Handle order place ────────────────────────────────────────────────────────
$place_msg = null;
if (isset($_POST['place_order'])) {
    $uname   = mysqli_real_escape_string($con, $_POST['uname']   ?? $user_name);
    $address = mysqli_real_escape_string($con, $_POST['address'] ?? $user_address);
    $zipcode = mysqli_real_escape_string($con, $_POST['zipcode'] ?? $userRow['pin'] ?? '');
    $phone   = mysqli_real_escape_string($con, $_POST['phone']   ?? $user_phone);
    $email   = mysqli_real_escape_string($con, $_POST['email']   ?? $user_email);
    $odate   = date('Y-m-d');
    $q = mysqli_query($con, "INSERT INTO order_master VALUES('',$uid_safe,'$uname','$address','$zipcode','$phone','$email',$now_payable,'$odate',0)");
    if ($q) {
        $_SESSION['uname']    = $uname;
        $_SESSION['total']    = $now_payable;
        $_SESSION['order_id'] = mysqli_insert_id($con);
        header("location: paymentmode.php");
        exit;
    } else {
        $place_msg = ['type'=>'error','msg'=>'Order could not be placed. Please try again.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirm — CarBook</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/open-iconic-bootstrap.min.css">
  <link rel="stylesheet" href="../css/animate.css">
  <link rel="stylesheet" href="../css/owl.carousel.min.css">
  <link rel="stylesheet" href="../css/owl.theme.default.min.css">
  <link rel="stylesheet" href="../css/aos.css">
  <link rel="stylesheet" href="../css/ionicons.min.css">
  <link rel="stylesheet" href="../css/flaticon.css">
  <link rel="stylesheet" href="../css/icomoon.css">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --green:#2ecc71;--blue:#1a8cff;--gold:#f5a623;--red:#ff6b6b;
      --dark:#0d0f14;--dark2:#141720;--dark3:#1c2030;--dark4:#232840;
      --border:rgba(255,255,255,0.07);--text:rgba(255,255,255,0.88);--muted:rgba(255,255,255,0.42);
      --font:'Poppins',sans-serif;--mono:'Space Mono',monospace;
    }
    html,body{background:#0d0f14 !important;font-family:var(--font);color:var(--text);min-height:100vh}
    html{scroll-behavior:smooth}

    #ftco-navbar{background:transparent !important;box-shadow:none !important;transition:background .35s ease,box-shadow .35s ease;position:relative;z-index:1000;}
#ftco-navbar.scrolled{background:rgba(7,9,12,0.98) !important;box-shadow:0 4px 28px rgba(0,0,0,0.7) !important;}

    .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}
    .bg-canvas::before{content:'';position:absolute;width:900px;height:900px;background:radial-gradient(circle,rgba(46,204,113,0.08) 0%,transparent 65%);top:-200px;right:-100px;animation:drift 20s ease-in-out infinite alternate}
    .bg-canvas::after{content:'';position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(26,140,255,0.07) 0%,transparent 65%);bottom:-100px;left:-100px;animation:drift 28s ease-in-out infinite alternate-reverse}
    @keyframes drift{from{transform:translate(0,0)}to{transform:translate(50px,40px) scale(1.07)}}
    .grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.02) 1px,transparent 1px);background-size:64px 64px}
    .deco-bottom{position:fixed;bottom:0;left:0;right:0;height:3px;z-index:100;background:linear-gradient(90deg,var(--green),var(--blue),var(--green));background-size:200%;animation:shimmer 3s linear infinite}
    @keyframes shimmer{from{background-position:0%}to{background-position:200%}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
    @keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(46,204,113,0.25)}50%{box-shadow:0 0 0 18px rgba(46,204,113,0)}}

    .page-wrap{position:relative;z-index:1;max-width:1060px;margin:0 auto;padding:100px 24px 90px}

    /* BANNER */
    .order-banner{text-align:center;margin-bottom:42px;animation:fadeUp .5s ease both}
    .order-icon-ring{width:88px;height:88px;border-radius:50%;background:rgba(46,204,113,0.12);border:2px solid rgba(46,204,113,0.35);display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--green);margin:0 auto 18px;animation:pulse-green 2.5s ease infinite}
    .order-banner h1{font-size:clamp(22px,3vw,32px);font-weight:800;color:#fff;letter-spacing:-.5px;margin-bottom:8px}
    .order-banner p{font-size:13px;color:var(--muted);max-width:440px;margin:0 auto}
    .order-ref-pill{display:inline-flex;align-items:center;gap:10px;margin-top:14px;padding:8px 20px;background:var(--dark3);border:1px solid var(--border);border-radius:100px}
    .order-ref-pill .rl{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
    .order-ref-pill .rv{font-family:var(--mono);font-size:13px;color:var(--green);font-weight:700}

    /* CHOICE PILL */
    .choice-pill{display:inline-flex;align-items:center;gap:8px;margin-top:10px;padding:7px 18px;border-radius:100px;font-size:12px;font-weight:700}
    .choice-pill.deposit{background:rgba(245,166,35,0.12);border:1px solid rgba(245,166,35,0.3);color:var(--gold)}
    .choice-pill.full{background:rgba(46,204,113,0.12);border:1px solid rgba(46,204,113,0.3);color:var(--green)}

    /* LAYOUT */
    .order-grid{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}

    /* CARDS */
    .ocard{background:var(--dark2);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px;animation:fadeUp .5s ease both}
    .ocard:last-child{margin-bottom:0}
    .ocard-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
    .ocard-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
    .oi-g{background:rgba(46,204,113,0.15);color:var(--green)}
    .oi-b{background:rgba(26,140,255,0.15);color:var(--blue)}
    .oi-o{background:rgba(245,166,35,0.15);color:var(--gold)}
    .oi-p{background:rgba(180,100,255,0.15);color:#b464ff}
    .ocard-head h3{font-size:14px;font-weight:700;color:#fff}
    .ocard-head .hbadge{margin-left:auto;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:100px}
    .hbadge-gold{background:rgba(245,166,35,0.12);color:var(--gold);border:1px solid rgba(245,166,35,0.28)}
    .hbadge-green{background:rgba(46,204,113,0.12);color:var(--green);border:1px solid rgba(46,204,113,0.28)}
    .ocard-body{padding:20px 22px}

    /* FARE TABLE */
    .fare-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px}
    .fare-row:last-child{border-bottom:none}
    .fare-row .fl{color:var(--muted);display:flex;align-items:center;gap:7px}
    .fare-row .fl i{font-size:10px;color:var(--blue)}
    .fare-row .fv{font-weight:600;color:#fff}
    .fare-row.sub .fv{color:rgba(255,255,255,0.6)}
    .fare-row.drv .fv{color:var(--blue)}
    .fare-row.dep .fv{color:var(--gold)}
    .fare-row.total{padding-top:14px;margin-top:4px;border-top:1px solid rgba(255,255,255,0.09);border-bottom:none}
    .fare-row.total .fl{color:#fff;font-weight:700;font-size:14px}
    .fare-row.total .fv{font-family:var(--mono);font-size:20px;font-weight:800;color:rgba(255,255,255,0.65)}

    /* FORM */
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-group.full{grid-column:1/-1}
    .form-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);display:flex;align-items:center;gap:5px}
    .form-label i{color:var(--blue);font-size:9px}
    .form-input{background:var(--dark3);border:1.5px solid var(--border);color:var(--text);font-family:var(--font);font-size:13px;padding:11px 14px;border-radius:9px;outline:none;transition:border-color .2s,box-shadow .2s;width:100%}
    .form-input:focus{border-color:rgba(26,140,255,0.45);box-shadow:0 0 0 3px rgba(26,140,255,0.08)}
    .form-input::placeholder{color:rgba(255,255,255,0.22)}

    /* SIDEBAR */
    .pay-sidebar{position:sticky;top:90px;display:flex;flex-direction:column;gap:14px}

    /* PAY NOW CARD */
    .pay-now-card{background:linear-gradient(135deg,rgba(46,204,113,0.1),rgba(26,140,255,0.06));border:1.5px solid rgba(46,204,113,0.28);border-radius:14px;overflow:hidden}
    .pay-now-card .pnc-head{background:rgba(46,204,113,0.1);border-bottom:1px solid rgba(46,204,113,0.2);padding:12px 18px;display:flex;align-items:center;gap:8px}
    .pay-now-card .pnc-head span{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--green)}
    .pay-now-card .pnc-body{padding:16px 18px}

    /* PAY ROWS */
    .pn-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px}
    .pn-row:last-of-type{border-bottom:none}
    .pn-row .pnl{color:var(--muted);display:flex;align-items:center;gap:6px}
    .pn-row .pnl i{font-size:10px;color:var(--blue)}
    .pn-row .pnv{font-weight:700;color:#fff}
    .pn-row .pnv.gold{color:var(--gold)}
    .pn-row .pnv.muted{color:rgba(255,255,255,0.4);font-size:11px;font-weight:600}

    /* DIVIDER */
    .pn-divider{height:1px;background:rgba(255,255,255,0.06);margin:4px 0}

    /* TOTAL */
    .pn-total{display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:14px;border-top:1px solid rgba(46,204,113,0.25)}
    .pn-total .pt-l{font-size:14px;font-weight:800;color:#fff;display:flex;align-items:center;gap:7px}
    .pn-total .pt-l i{color:var(--green)}
    .pn-total .pt-v{font-family:var(--mono);font-size:28px;font-weight:800;color:var(--green)}

    /* REMAINING NOTE */
    .remaining-note{background:rgba(26,140,255,0.06);border:1px solid rgba(26,140,255,0.15);border-radius:10px;padding:13px 16px;display:flex;align-items:flex-start;gap:10px;font-size:12px;line-height:1.65;color:var(--muted)}
    .remaining-note i{color:var(--blue);font-size:14px;flex-shrink:0;margin-top:1px}
    .remaining-note strong{color:#fff}

    /* BUTTONS */
    .btn-place{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;background:linear-gradient(135deg,#0a7a3e,#2ecc71);color:#fff;font-family:var(--font);font-size:15px;font-weight:800;border:none;border-radius:10px;cursor:pointer;text-decoration:none;transition:filter .2s,transform .15s,box-shadow .2s;box-shadow:0 8px 26px rgba(46,204,113,0.3);letter-spacing:.02em}
    .btn-place:hover{filter:brightness(1.1);transform:translateY(-2px);box-shadow:0 14px 36px rgba(46,204,113,0.42);color:#fff}
    .btn-back{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;background:transparent;border:1.5px solid var(--border);color:var(--muted);font-family:var(--font);font-size:13px;font-weight:700;border-radius:9px;cursor:pointer;text-decoration:none;transition:all .2s}
    .btn-back:hover{border-color:rgba(255,255,255,0.25);color:#fff;text-decoration:none}

    /* SECURITY BADGE */
    .security-badge{display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;background:rgba(46,204,113,0.06);border:1px solid rgba(46,204,113,0.15);border-radius:8px;font-size:11px;font-weight:600;color:var(--muted)}
    .security-badge i{color:var(--green)}

    /* TOAST */
    .toast-bar{position:fixed;top:90px;left:50%;transform:translateX(-50%) translateY(-80px);padding:12px 24px;border-radius:100px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;z-index:6000;transition:transform .4s cubic-bezier(.34,1.56,.64,1)}
    .toast-bar.show{transform:translateX(-50%) translateY(0)}
    .toast-bar.error{background:rgba(255,107,107,0.12);border:1px solid rgba(255,107,107,0.3);color:var(--red)}

    @media(max-width:900px){.order-grid{grid-template-columns:1fr}.pay-sidebar{position:static}}
    @media(max-width:600px){.form-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<div class="bg-canvas"></div>
<div class="grid-lines"></div>
<div class="deco-bottom"></div>

<?php include("header.php"); ?>

<?php if($place_msg): ?>
<div class="toast-bar <?= $place_msg['type'] ?>" id="placeToast">
  <i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($place_msg['msg']) ?>
</div>
<?php endif; ?>

<div class="page-wrap">

  <!-- BANNER -->
  <div class="order-banner">
    <div class="order-icon-ring"><i class="fas fa-credit-card"></i></div>
    <h1><i class="fas fa-shield-alt" style="font-size:.75em;margin-right:10px;opacity:.7"></i>Confirm Your Payment</h1>
    <p>Review your booking and billing information before proceeding to payment.</p>
    <div class="order-ref-pill">
      <span class="rl">Booking Ref</span>
      <span class="rv"><?= htmlspecialchars($booking_ref) ?></span>
    </div>
    <div class="choice-pill">
 <i class="fas fa-credit-card"></i>
      <?= $choice_label ?> Selected
    </div>
  </div>

  <div class="order-grid">

    <!-- LEFT: Form + Fare Summary -->
    <div>

      <!-- FARE BREAKDOWN CARD -->
      <div class="ocard" style="animation-delay:.05s">
        <div class="ocard-head">
          <div class="ocard-icon oi-o"><i class="fas fa-rupee-sign"></i></div>
          <h3>Fare Breakdown</h3>
          <span class="hbadge <?= ($now_payable == $grand_total ? 'hbadge-green' : 'hbadge-gold') ?>"><?= $choice_label ?></span>
        </div>
        <div class="ocard-body">
          <div class="fare-row sub">
            <span class="fl"><i class="fas fa-calculator"></i>Base Rent</span>
            <span class="fv">&#8377;<?= number_format($base_amount,2) ?></span>
          </div>
          <?php if($driver_charge > 0): ?>
          <div class="fare-row drv">
            <span class="fl"><i class="fas fa-user-tie"></i>Driver Charge (10%)</span>
            <span class="fv">&#8377;<?= number_format($driver_charge,2) ?></span>
          </div>
          <?php endif; ?>
          <div class="fare-row sub">
            <span class="fl"><i class="fas fa-percent"></i>GST (5%)</span>
            <span class="fv">&#8377;<?= number_format($gst,2) ?></span>
          </div>
          <?php if($security_dep > 0): ?>
          <div class="fare-row dep">
            <span class="fl"><i class="fas fa-shield-alt"></i>Security Deposit</span>
            <span class="fv">&#8377;<?= number_format($security_dep,2) ?></span>
          </div>
          <?php endif; ?>
          <div class="fare-row total">
            <span class="fl">Grand Total</span>
            <span class="fv">&#8377;<?= number_format($grand_total,2) ?></span>
          </div>
        </div>
      </div>

      <!-- BILLING FORM CARD -->
      <div class="ocard" style="animation-delay:.1s">
        <div class="ocard-head">
          <div class="ocard-icon oi-p"><i class="fas fa-user-edit"></i></div>
          <h3>Billing Information</h3>
        </div>
        <div class="ocard-body">
          <form method="POST" action="" id="orderForm">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label"><i class="fas fa-user"></i>Full Name *</label>
                <input type="text" name="uname" class="form-input" required
                       value="<?= htmlspecialchars($userRow['uname'] ?? $user_name) ?>" placeholder="Your full name">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-phone"></i>Phone Number *</label>
                <input type="tel" name="phone" class="form-input" required
                       value="<?= htmlspecialchars($userRow['mobno'] ?? $user_phone) ?>" placeholder="+91 00000 00000">
              </div>
              <div class="form-group full">
                <label class="form-label"><i class="fas fa-envelope"></i>Email Address *</label>
                <input type="email" name="email" class="form-input" required
                       value="<?= htmlspecialchars($userRow['email'] ?? $user_email) ?>" placeholder="your@email.com">
              </div>
              <div class="form-group full">
                <label class="form-label"><i class="fas fa-map-marker-alt"></i>Street Address *</label>
                <input type="text" name="address" class="form-input" required
                       value="<?= htmlspecialchars($userRow['address'] ?? $user_address) ?>" placeholder="Your full address">
              </div>
              <div class="form-group">
                <label class="form-label"><i class="fas fa-map-pin"></i>PIN / ZIP Code</label>
                <input type="text" name="zipcode" class="form-input"
                       value="<?= htmlspecialchars($userRow['pin'] ?? '') ?>" placeholder="PIN code">
              </div>
              <div class="form-group" style="display:flex;align-items:flex-end">
                <div style="background:var(--dark3);border:1px solid rgba(46,204,113,0.2);border-radius:9px;padding:11px 14px;width:100%;display:flex;align-items:center;gap:8px">
                  <i class="fas fa-hashtag" style="color:var(--green);font-size:12px"></i>
                  <div>
                    <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em">Booking Ref</div>
                    <div style="font-family:var(--mono);font-size:13px;color:var(--green);font-weight:700"><?= htmlspecialchars($booking_ref) ?></div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div><!-- end left -->

    <!-- RIGHT: Payment Summary Sidebar -->
    <div class="pay-sidebar">

      <!-- PAY NOW CARD -->
      <div class="pay-now-card">
        <div class="pnc-head">
          <i class="fas fa-bolt" style="color:var(--green);font-size:13px"></i>
          <span>Amount Due Now</span>
        </div>
        <div class="pnc-body">

            <?php if($remaining_due <= 0): ?>
    <!-- FULLY PAID -->
    <div class="pn-row">
        <span class="pnl"><i class="fas fa-check-circle"></i>Payment Status</span>
        <span class="pnv" style="color:var(--green)">Fully Paid</span>
    </div>

<?php elseif($is_deposit_paid): ?>
    <!-- REMAINING PAYMENT -->
    <div class="pn-row">
        <span class="pnl"><i class="fas fa-wallet"></i>Remaining Amount</span>
        <span class="pnv gold">&#8377;<?= number_format($remaining_due,2) ?></span>
    </div>

<?php else: ?>
    <!-- FIRST TIME -->
    <div class="pn-row">
        <span class="pnl"><i class="fas fa-shield-alt"></i>Security Deposit</span>
        <span class="pnv gold">&#8377;<?= number_format($deposit_only,2) ?></span>
    </div>
<?php endif; ?>

          <div class="pn-total">
            <span class="pt-l"><i class="fas fa-credit-card"></i>Pay Now</span>
            <span class="pt-v">&#8377;<?= number_format($now_payable,2) ?></span>
          </div>
        </div>
      </div>

      <!-- REMAINING NOTE -->
      <?php if($remaining_due > 0): ?>
      <div class="remaining-note">
        <i class="fas fa-info-circle"></i>
        <div>
          <strong>&#8377;<?= number_format($remaining_due,2) ?></strong> (rental + taxes) is payable on vehicle return.
          <?php if($security_dep > 0): ?>
          Security deposit of <strong>&#8377;<?= number_format($security_dep,2) ?></strong> is fully refundable upon safe return.
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="remaining-note" style="background:rgba(46,204,113,0.06);border-color:rgba(46,204,113,0.18)">
        <i class="fas fa-check-circle" style="color:var(--green)"></i>
        <div>
          Full payment selected — <strong>no dues remaining</strong> on vehicle return.
          <?php if($security_dep > 0): ?>
          Security deposit of <strong>&#8377;<?= number_format($security_dep,2) ?></strong> is fully refundable upon safe return.
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- PLACE ORDER BUTTON -->
      <button type="submit" form="orderForm" name="place_order" class="btn-place">
        <i class="fas fa-lock"></i>
        Confirm &amp; Proceed to Pay
        <span style="margin-left:auto;font-family:var(--mono);font-size:13px">&#8377;<?= number_format($now_payable,0) ?></span>
      </button>

      <a href="booking_details.php?bid=<?= $booking_id ?>" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Booking Details
      </a>

      <div class="security-badge">
        <i class="fas fa-lock"></i>
        <span>256-bit SSL encrypted · Secure payment</span>
      </div>

    </div><!-- end sidebar -->
  </div><!-- end order-grid -->

</div><!-- end page-wrap -->

<script src="../js/jquery.min.js"></script>
<script src="../js/jquery-migrate-3.0.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/main.js"></script>
<script>
(function(){
  var nav = document.getElementById('ftco-navbar');
  if(!nav) return;
  function onScroll(){ nav.classList.toggle('scrolled', window.scrollY > 60); }
  onScroll();
  window.addEventListener('scroll', onScroll, {passive:true});
})();
<?php if($place_msg): ?>
(function(){
  var t = document.getElementById('placeToast');
  if(!t) return;
  setTimeout(function(){ t.classList.add('show'); }, 100);
  setTimeout(function(){ t.classList.remove('show'); }, 4000);
})();
<?php endif; ?>
</script>
</body>
</html>