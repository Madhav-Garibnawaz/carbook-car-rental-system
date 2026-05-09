<?php
if (session_status() === PHP_SESSION_NONE) { 
session_name('user_session');  
session_start(); }
require("connect.php");

if (!isset($_SESSION['user_id'])) { header("location: register.php"); exit; }
$uid = intval($_SESSION['user_id']);

// ── Get order_id from session (set by orderconfirm.php) ────────────────────
$order_id = intval($_SESSION['order_id'] ?? 0);
if (!$order_id) { header("location: booking_details.php"); exit; }

// ── Fetch THIS specific order ──────────────────────────────────────────────
$oQ  = mysqli_query($con, "SELECT * FROM order_master WHERE order_id=$order_id AND user_id=$uid LIMIT 1");
$row = mysqli_fetch_assoc($oQ);
if (!$row) { header("location: booking_details.php"); exit; }

$now_payable = floatval($row['total']);   // correct amount
$uname       = $row['user_name'];

// ── Also get booking_id from session so we can link payment ───────────────
$booking_id  = intval($_SESSION['booking_payment']['booking_id'] ?? 0);
$booking_ref = $_SESSION['booking_payment']['booking_ref'] ?? '';

// ── Handle Razorpay success callback (AJAX post with payment_id) ──────────
if (isset($_POST['razorpay_payment_id']) && !empty($_POST['razorpay_payment_id'])) {
    $payment_id  = mysqli_real_escape_string($con, $_POST['razorpay_payment_id']);
    $paid_amount = intval($now_payable); // amount in rupees (already decimal)
    $today       = date('Y-m-d');

    // 1. Save to payment_master

$choice = $_SESSION['payment_choice'] ?? 'deposit';
$total_amount = $_SESSION['booking_payment']['grand_total'];

// 🔥 calculate total paid BEFORE insert
$paidQ = mysqli_query($con, "SELECT SUM(paid_amount) as total_paid FROM payment_master WHERE booking_id=$booking_id");
$paidData = mysqli_fetch_assoc($paidQ);
$total_paid = $paidData['total_paid'] ?? 0;

// add current payment
$total_paid += $now_payable;

// calculate remaining
$remaining = $total_amount - $total_paid;

// decide status
if ($remaining <= 0) {
    $remaining = 0;
    $payment_status = 2; // FULL
} else {
    $payment_status = 1; // PARTIAL
}

// ✅ NOW insert
mysqli_query($con,
    "INSERT INTO payment_master 
    (booking_id, payname, paid_amount, payment_type, total_amount, remaining_amount, payment_status, added_on)
    VALUES 
    ($booking_id, '$uname', $now_payable, '$choice', $total_amount, $remaining, $payment_status, '$today')"
);

    // 3. Store success info in session for thank_you page
    $_SESSION['payment_success'] = [
        'payment_id'  => $payment_id,
        'amount'      => $now_payable,
        'order_id'    => $order_id,
        'booking_id'  => $booking_id,
        'booking_ref' => $booking_ref,
        'uname'       => $uname,
        'pay_id'      => $payment_id,
    ];

    // Clear booking payment session
    unset($_SESSION['booking_payment']);

    echo json_encode(['status' => 'ok', 'redirect' => 'thank_you.php']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment — CarBook</title>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
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
    html,body{background:#0d0f14!important;font-family:var(--font);color:var(--text);min-height:100vh}
    #ftco-navbar{background:transparent!important;box-shadow:none!important;transition:background .35s,box-shadow .35s}
    #ftco-navbar.scrolled{background:rgba(7,9,12,.98)!important;box-shadow:0 4px 28px rgba(0,0,0,.7)!important}
    .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}
    .bg-canvas::before{content:'';position:absolute;width:800px;height:800px;background:radial-gradient(circle,rgba(46,204,113,.1) 0%,transparent 65%);top:-200px;right:-100px;animation:drift 20s ease-in-out infinite alternate}
    .bg-canvas::after{content:'';position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(26,140,255,.08) 0%,transparent 65%);bottom:-100px;left:-100px;animation:drift 26s ease-in-out infinite alternate-reverse}
    @keyframes drift{from{transform:translate(0,0)}to{transform:translate(50px,40px) scale(1.07)}}
    .grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:64px 64px}
    .deco-bottom{position:fixed;bottom:0;left:0;right:0;height:3px;z-index:100;background:linear-gradient(90deg,var(--green),var(--blue),var(--green));background-size:200%;animation:shimmer 3s linear infinite}
    @keyframes shimmer{from{background-position:0%}to{background-position:200%}}
    @keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
    @keyframes pulse-green{0%,100%{box-shadow:0 0 0 0 rgba(46,204,113,.25)}50%{box-shadow:0 0 0 20px rgba(46,204,113,0)}}
    @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

    .page-wrap{position:relative;z-index:1;max-width:560px;margin:0 auto;padding:110px 24px 90px;animation:fadeUp .5s ease both}

    .pay-card{background:var(--dark2);border:1px solid var(--border);border-radius:20px;overflow:hidden}
    .pay-card-header{background:linear-gradient(135deg,rgba(46,204,113,.12),rgba(26,140,255,.08));border-bottom:1px solid var(--border);padding:28px 32px;text-align:center;position:relative}
    .pay-card-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,rgba(46,204,113,.4),transparent)}
    .pay-icon-ring{width:80px;height:80px;border-radius:50%;background:rgba(46,204,113,.12);border:2px solid rgba(46,204,113,.35);display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--green);margin:0 auto 16px;animation:pulse-green 2.5s ease infinite}
    .pay-card-header h2{font-size:22px;font-weight:800;color:#fff;letter-spacing:-.3px;margin-bottom:4px}
    .pay-card-header p{font-size:13px;color:var(--muted)}

    .pay-card-body{padding:28px 32px}
    .order-ref-row{display:flex;align-items:center;justify-content:space-between;background:var(--dark3);border:1px solid var(--border);border-radius:10px;padding:12px 16px;margin-bottom:20px}
    .order-ref-row .orl{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
    .order-ref-row .orv{font-family:var(--mono);font-size:13px;color:var(--green);font-weight:700}

    .amount-display{text-align:center;padding:24px;background:linear-gradient(135deg,rgba(46,204,113,.09),rgba(26,140,255,.05));border:1.5px solid rgba(46,204,113,.25);border-radius:14px;margin-bottom:24px}
    .amount-label{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
    .amount-value{font-family:var(--mono);font-size:42px;font-weight:800;color:var(--green);letter-spacing:-1px}
    .amount-sub{font-size:12px;color:var(--muted);margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px}
    .amount-sub i{color:var(--gold);font-size:10px}

    .pay-breakdown{margin-bottom:24px}
    .pb-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
    .pb-row:last-child{border-bottom:none}
    .pb-row .pbl{color:var(--muted);display:flex;align-items:center;gap:6px}
    .pb-row .pbl i{font-size:10px;color:var(--blue)}
    .pb-row .pbv{font-weight:600;color:#fff}
    .pb-row .pbv.gold{color:var(--gold)}

    .btn-pay{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;background:linear-gradient(135deg,#0a7a3e,#2ecc71);color:#fff;font-family:var(--font);font-size:15px;font-weight:800;border:none;border-radius:10px;cursor:pointer;transition:filter .2s,transform .15s,box-shadow .2s;box-shadow:0 8px 26px rgba(46,204,113,.3);letter-spacing:.02em}
    .btn-pay:hover{filter:brightness(1.1);transform:translateY(-2px);box-shadow:0 14px 36px rgba(46,204,113,.42)}
    .btn-pay:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .btn-pay .spinner{width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin 0.8s linear infinite;display:none}

    .security-row{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;font-size:11px;color:var(--muted)}
    .security-row i{color:var(--green);font-size:12px}

    .razorpay-badge{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;font-size:10px;color:rgba(255,255,255,.3);font-weight:600;letter-spacing:.05em}
    .razorpay-badge i{font-size:9px}

    /* Loading overlay */
    .loading-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;display:none;align-items:center;justify-content:center;flex-direction:column;gap:16px;backdrop-filter:blur(6px)}
    .loading-overlay.show{display:flex}
    .loading-spinner{width:60px;height:60px;border:3px solid rgba(46,204,113,.2);border-top-color:var(--green);border-radius:50%;animation:spin .9s linear infinite}
    .loading-overlay p{font-size:14px;font-weight:600;color:var(--green)}
  </style>
</head>
<body>

<div class="bg-canvas"></div>
<div class="grid-lines"></div>
<div class="deco-bottom"></div>

<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-spinner"></div>
  <p>Processing your payment…</p>
</div>

<div class="page-wrap">
  <div class="pay-card">
    <div class="pay-card-header">
      <div class="pay-icon-ring"><i class="fas fa-credit-card"></i></div>
      <h2>Secure Payment</h2>
      <p>Complete your booking payment via Razorpay</p>
    </div>
    <div class="pay-card-body">
      <div class="order-ref-row">
        <div>
          <div class="orl">Booking Reference</div>
          <div class="orv"><?= htmlspecialchars($booking_ref ?: 'CB-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT)) ?></div>
        </div>
        <div style="text-align:right">
          <div class="orl">Customer</div>
          <div style="font-size:13px;font-weight:600;color:#fff"><?= htmlspecialchars($uname) ?></div>
        </div>
      </div>

      <div class="amount-display">
        <div class="amount-label">Total Amount Due Now</div>
        <div class="amount-value">&#8377;<?= number_format($now_payable, 2) ?></div>
      </div>

      <?php $pay = $_SESSION['booking_payment'] ?? []; ?>
      <?php if (!empty($pay)): ?>
      <div class="pay-breakdown">
        <?php if (($pay['security_dep'] ?? 0) > 0): ?>
        <div class="pb-row">
          <span class="pbl"><i class="fas fa-shield-alt"></i>Security Deposit (refundable)</span>
          <span class="pbv gold">&#8377;<?= number_format($pay['security_dep'], 2) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <button class="btn-pay" id="payBtn" onclick="initiatePayment()">
        <div class="spinner" id="paySpinner"></div>
        <i class="fas fa-lock" id="payIcon"></i>
        <span id="payText">Pay &#8377;<?= number_format($now_payable, 2) ?> Securely</span>
      </button>

      <div class="security-row">
        <i class="fas fa-lock"></i>
        <span>256-bit SSL encrypted · Your data is protected</span>
      </div>
      <div class="razorpay-badge">
        <i class="fas fa-shield-alt"></i>
        POWERED BY RAZORPAY
      </div>
    </div>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script>
(function(){
  var nav = document.getElementById('ftco-navbar');
  if(!nav) return;
  function onScroll(){ nav.classList.toggle('scrolled', window.scrollY > 60); }
  onScroll();
  window.addEventListener('scroll', onScroll, {passive:true});
})();

function initiatePayment() {
    var btn     = document.getElementById('payBtn');
    var spinner = document.getElementById('paySpinner');
    var icon    = document.getElementById('payIcon');
    var text    = document.getElementById('payText');

    btn.disabled   = true;
    spinner.style.display = 'block';
    icon.style.display    = 'none';
    text.textContent      = 'Opening Payment Gateway…';

    // Amount must be in PAISE (multiply by 100)
    var amountInPaise = <?= intval(round($now_payable * 100)) ?>;

    var options = {
        "key": "rzp_test_YourTestKeyHere", // Replace with your Razorpay Test Key
        "amount": amountInPaise,
        "currency": "INR",
        "name": "CarBook",
        "description": "Booking <?= htmlspecialchars($booking_ref ?: 'CB-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT)) ?>",
        "image": "images/logo.png",
        "prefill": {
    "name": "<?= htmlspecialchars($uname) ?>",
    "email": "<?= htmlspecialchars($row['email'] ?? '') ?>",
    "contact": "<?= htmlspecialchars(
        preg_match('/^\+91/', $row['phone'] ?? '') 
            ? $row['phone'] 
            : '+91' . ltrim($row['phone'] ?? '9000000000', '0')
    ) ?>"
},
        "theme": { "color": "#2ecc71" },
        "handler": function(response) {
            // Payment succeeded — save to DB via AJAX then redirect
            document.getElementById('loadingOverlay').classList.add('show');

            fetch('paymentmode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'razorpay_payment_id=' + encodeURIComponent(response.razorpay_payment_id)
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ok') {
                    window.location.href = data.redirect;
                }
            })
            .catch(() => {
                window.location.href = 'thank_you.php';
            });
        },
        "modal": {
            "ondismiss": function() {
                btn.disabled          = false;
                spinner.style.display = 'none';
                icon.style.display    = 'inline';
                text.textContent      = 'Pay ₹<?= number_format($now_payable, 2) ?> Securely';
            }
        }
    };

    var rzp = new Razorpay(options);
    rzp.on('payment.failed', function(response) {
        btn.disabled          = false;
        spinner.style.display = 'none';
        icon.style.display    = 'inline';
        text.textContent      = 'Retry Payment';
        alert('Payment failed: ' + (response.error.description || 'Please try again.'));
    });
    rzp.open();
}
</script>
</body>
</html>