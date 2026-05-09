<!-- <html>
<body>
    <link rel="stylesheet" type="text/css"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css"
        href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
    <style type="text/css">
    body {
        background: #f2f2f2;
    }

    .payment {
        border: 1px solid #f2f2f2;
        height: 280px;
        border-radius: 20px;
        background: #fff;
    }

    .payment_header {
        background: #7AB3E1;
        padding: 20px;
        border-radius: 20px 20px 0px 0px;

    }

    .check {
        margin: 0px auto;
        width: 50px;
        height: 50px;
        border-radius: 100%;
        background: #fff;
        text-align: center;
    }

    .check i {
        vertical-align: middle;
        line-height: 50px;
        font-size: 30px;
    }

    .content {
        text-align: center;
    }

    .content h1 {
        font-size: 25px;
        padding-top: 25px;
    }

    .content a {
        width: 200px;
        height: 35px;
        color: #fff;
        border-radius: 10px;
        padding: 5px 10px;
        background: #007bff;
        transition: all ease-in-out 0.3s;
    }

    .content a:hover {
        text-decoration: none;
        background: #7AB3E1;
    }
    </style>
    <?php
    session_name('user_session');
    session_start();
      include('connect.php');
      $uid=$_SESSION['user_id'];
      $q=mysqli_query($con,"select * from order_master where user_id=$uid");
      $row=mysqli_fetch_array($q);
      $oid=$row[0];
      $_SESSION['oid']=$oid;
      // $uname=$_SESSION['uname'];
      // $uname=$_GET['uname'];
      if(isset($_POST['b1']))
      {
          $oid=$row[0];
          $uid=$row[1];
          $uname=$row[2];
          $address=$row[3];
          $zipcode=$row[4];
          $phone=$row[5];
          $email=$row[6];
          $subtotal=$row[7];
          $total=$row[8];
          $odate=$row[9];
          $q1=mysqli_query($con,"insert into order_history values($oid,$uid,'$uname','$address','$zipcode','$phone','$email',$subtotal,$total,'$odate',1)");
       mysqli_query($con,"update addtocart set status=1 where uid=$uid");
       mysqli_query($con,"update addtocart1 set status=1 where uid=$uid");
       header('location:index.php');
      }
    ?>
    <div class="container">
        <div class="row">
            <div class="col-md-6 mx-auto mt-5">
                <div class="payment">
                    <div class="payment_header">
                        <div class="check"><i class="fa fa-check" aria-hidden="true"></i></div>
                    </div>
                    <form method="POST">
                    <div class="content">
                        <h1>Payment Success !</h1>
                        <p>Thank you by,</p>
                        <div>
                            <p>Team InfinitexAgro</p>
                        <form method=post>
                        <h6>
                        <input type=submit value="Go to Home" name="b1" >    
</form>    
                    </div> 
                        </a>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> -->
<?php
if (session_status() === PHP_SESSION_NONE) { 
session_name('user_session');  
session_start(); }
require("connect.php");

if (!isset($_SESSION['user_id'])) { header("location: register.php"); exit; }

$success = $_SESSION['payment_success'] ?? null;
if (!$success) { header("location: booking_details.php"); exit; }

// Clear after reading
unset($_SESSION['payment_success']);

$payment_id  = $success['payment_id'];
$amount      = floatval($success['amount']);
$order_id    = intval($success['order_id']);
$booking_id  = intval($success['booking_id']);
$booking_ref = $success['booking_ref'];
$uname       = $success['uname'];
$pay_id      = intval($success['pay_id']);
$paid_date   = date('d M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Successful — CarBook</title>
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
    html,body{background:#0d0f14!important;font-family:var(--font);color:var(--text);min-height:100vh;overflow-x:hidden}
    #ftco-navbar{background:transparent!important;box-shadow:none!important;transition:background .35s,box-shadow .35s}
    #ftco-navbar.scrolled{background:rgba(7,9,12,.98)!important;box-shadow:0 4px 28px rgba(0,0,0,.7)!important}

    /* Background */
    .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}
    .bg-canvas::before{content:'';position:absolute;width:900px;height:900px;background:radial-gradient(circle,rgba(46,204,113,.12) 0%,transparent 65%);top:-200px;right:-100px;animation:drift 18s ease-in-out infinite alternate}
    .bg-canvas::after{content:'';position:absolute;width:700px;height:700px;background:radial-gradient(circle,rgba(26,140,255,.07) 0%,transparent 65%);bottom:-100px;left:-100px;animation:drift 24s ease-in-out infinite alternate-reverse}
    @keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(50px,40px) scale(1.08)}}
    .grid-lines{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:64px 64px}
    .deco-bottom{position:fixed;bottom:0;left:0;right:0;height:3px;z-index:100;background:linear-gradient(90deg,var(--green),var(--blue),var(--green));background-size:200%;animation:shimmer 3s linear infinite}
    @keyframes shimmer{from{background-position:0%}to{background-position:200%}}

    /* Confetti particles */
    .confetti-wrap{position:fixed;inset:0;z-index:2;pointer-events:none;overflow:hidden}
    .cp{position:absolute;width:8px;height:8px;border-radius:2px;opacity:0;animation:confettiFall linear forwards}
    @keyframes confettiFall{
      0%{opacity:1;transform:translateY(-20px) rotate(0deg)}
      100%{opacity:0;transform:translateY(100vh) rotate(720deg)}
    }

    /* Animations */
    @keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    @keyframes scaleIn{from{opacity:0;transform:scale(0.3)}to{opacity:1;transform:scale(1)}}
    @keyframes pulseGreen{0%,100%{box-shadow:0 0 0 0 rgba(46,204,113,.4)}50%{box-shadow:0 0 0 24px rgba(46,204,113,0)}}
    @keyframes checkDraw{from{stroke-dashoffset:100}to{stroke-dashoffset:0}}
    @keyframes glow{0%,100%{box-shadow:0 0 20px rgba(46,204,113,.3),0 0 40px rgba(46,204,113,.1)}50%{box-shadow:0 0 40px rgba(46,204,113,.5),0 0 80px rgba(46,204,113,.2)}}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
    @keyframes shimmerText{from{background-position:0% 50%}to{background-position:200% 50%}}

    /* Page */
    .page-wrap{position:relative;z-index:3;max-width:680px;margin:0 auto;padding:100px 24px 100px}

    /* SUCCESS HERO */
    .success-hero{text-align:center;margin-bottom:40px;animation:fadeUp .6s ease both .1s}
    .check-orbit{position:relative;width:120px;height:120px;margin:0 auto 24px;animation:float 3s ease-in-out infinite}
    .check-orbit-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(46,204,113,.25);animation:pulseGreen 2.5s ease infinite}
    .check-ring{position:absolute;inset:8px;border-radius:50%;background:linear-gradient(135deg,rgba(46,204,113,.2),rgba(46,204,113,.08));border:2px solid rgba(46,204,113,.5);display:flex;align-items:center;justify-content:center;animation:glow 3s ease-in-out infinite}
    .check-svg{width:40px;height:40px}
    .check-svg circle{stroke:var(--green);stroke-width:2;fill:none;stroke-dasharray:200;animation:checkDraw 1s ease forwards .3s}
    .check-svg path{stroke:var(--green);stroke-width:3;fill:none;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:100;stroke-dashoffset:100;animation:checkDraw .6s ease forwards .8s}

    .success-title{font-size:clamp(26px,4vw,40px);font-weight:900;letter-spacing:-.8px;margin-bottom:10px;background:linear-gradient(135deg,#fff 0%,#2ecc71 50%,#fff 100%);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmerText 3s linear infinite 1s}
    .success-sub{font-size:14px;color:var(--muted);max-width:380px;margin:0 auto 20px;line-height:1.7}

    /* RECEIPT CARD */
    .receipt-card{background:var(--dark2);border:1px solid rgba(46,204,113,.2);border-radius:20px;overflow:hidden;box-shadow:0 0 60px rgba(46,204,113,.08);animation:fadeUp .6s ease both .3s}
    .receipt-header{background:linear-gradient(135deg,rgba(46,204,113,.12),rgba(26,140,255,.06));border-bottom:1px solid rgba(46,204,113,.15);padding:24px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .rh-left .rh-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(46,204,113,.15);border:1px solid rgba(46,204,113,.3);color:var(--green);font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:4px 12px;border-radius:100px;margin-bottom:8px}
    .rh-left .rh-badge .dot{width:6px;height:6px;border-radius:50%;background:var(--green)}
    .rh-title{font-size:16px;font-weight:800;color:#fff}
    .rh-date{font-size:11px;color:var(--muted);margin-top:2px}
    .rh-amount{text-align:right}
    .rh-amount-label{font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
    .rh-amount-value{font-family:var(--mono);font-size:28px;font-weight:800;color:var(--green)}

    .receipt-body{padding:24px 28px}

    /* Info Grid */
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
    .ig-item{background:var(--dark3);border:1px solid var(--border);border-radius:10px;padding:13px 15px}
    .ig-item.full{grid-column:1/-1}
    .ig-label{font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;display:flex;align-items:center;gap:5px}
    .ig-label i{color:var(--blue);font-size:9px}
    .ig-value{font-size:13px;font-weight:600;color:#fff}
    .ig-value.green{color:var(--green)}
    .ig-value.mono{font-family:var(--mono);font-size:12px;word-break:break-all}

    /* Divider */
    .receipt-divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
    .receipt-divider::before,.receipt-divider::after{content:'';flex:1;height:1px;background:var(--border)}
    .dashed-line{border:none;border-top:1px dashed rgba(255,255,255,.08);margin:16px 0}

    /* Amount breakdown */
    .breakdown-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px}
    .breakdown-row:last-child{border-bottom:none;padding-bottom:0}
    .breakdown-row .bl{color:var(--muted);display:flex;align-items:center;gap:7px}
    .breakdown-row .bl i{font-size:10px;color:var(--blue)}
    .breakdown-row .bv{font-weight:600;color:#fff}
    .breakdown-row .bv.gold{color:var(--gold)}
    .breakdown-total{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:14px;border-top:1.5px solid rgba(46,204,113,.2)}
    .breakdown-total .btl{font-size:14px;font-weight:800;color:#fff}
    .breakdown-total .btv{font-family:var(--mono);font-size:22px;font-weight:800;color:var(--green)}

    /* Note box */
    .note-box{background:rgba(26,140,255,.06);border:1px solid rgba(26,140,255,.15);border-radius:10px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;font-size:12px;line-height:1.65;color:rgba(255,255,255,.65);margin-top:20px}
    .note-box i{color:var(--blue);flex-shrink:0;margin-top:1px}
    .note-box strong{color:#fff}

    /* Action buttons */
    .action-btns{display:flex;flex-direction:column;gap:12px;margin-top:24px;padding:0 28px 28px}
    .btn-cta{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px 18px;color:#fff;font-family:var(--font);font-size:14px;font-weight:700;border:none;border-radius:10px;cursor:pointer;text-decoration:none;transition:filter .2s,transform .15s,box-shadow .2s;letter-spacing:.02em}
    .btn-cta:hover{filter:brightness(1.1);transform:translateY(-2px);color:#fff;text-decoration:none}
    .btn-green{background:linear-gradient(135deg,#0a7a3e,#2ecc71);box-shadow:0 6px 22px rgba(46,204,113,.28)}
    .btn-blue{background:linear-gradient(135deg,#1a4a8a,#1a6ed4);box-shadow:0 6px 22px rgba(26,140,255,.22)}
    .btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--muted)}
    .btn-outline:hover{border-color:rgba(255,255,255,.25);color:#fff;filter:none;transform:none}

    /* Ticket strip */
    .ticket-strip{display:flex;align-items:center;justify-content:center;gap:20px;padding:16px 28px;background:var(--dark3);border-top:1px dashed rgba(255,255,255,.08);flex-wrap:wrap}
    .ts-item{display:flex;align-items:center;gap:7px;font-size:11px;color:var(--muted)}
    .ts-item i{color:var(--green);font-size:12px}
    .ts-item strong{color:#fff}

    @media(max-width:600px){.info-grid{grid-template-columns:1fr}.receipt-header{flex-direction:column}.rh-amount{text-align:left}}
  </style>
</head>
<body>

<div class="bg-canvas"></div>
<div class="grid-lines"></div>
<div class="deco-bottom"></div>
<div class="confetti-wrap" id="confettiWrap"></div>

<?php include("header.php"); ?>

<div class="page-wrap">

  <!-- SUCCESS HERO -->
  <div class="success-hero">
    <div class="check-orbit">
      <div class="check-orbit-ring"></div>
      <div class="check-ring">
        <svg class="check-svg" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
          <circle cx="25" cy="25" r="23" stroke-dasharray="200" stroke-dashoffset="200"/>
          <path d="M14 25 L22 33 L36 17" stroke-dasharray="100" stroke-dashoffset="100"/>
        </svg>
      </div>
    </div>
    <div class="success-title">Payment Successful!</div>
    <div class="success-sub">Your booking is confirmed. We've received your payment and your ride is all set.</div>
  </div>

  <!-- RECEIPT CARD -->
  <div class="receipt-card">

    <div class="receipt-header">
      <div class="rh-left">
        <div class="rh-badge"><div class="dot"></div> Payment Confirmed</div>
        <div class="rh-title">Official Payment Receipt</div>
        <div class="rh-date"><?= $paid_date ?></div>
      </div>
      <div class="rh-amount">
        <div class="rh-amount-label">Amount Paid</div>
        <div class="rh-amount-value">&#8377;<?= number_format($amount, 2) ?></div>
      </div>
    </div>

    <div class="receipt-body">

      <div class="info-grid">
        <div class="ig-item">
          <div class="ig-label"><i class="fas fa-hashtag"></i>Booking Ref</div>
          <div class="ig-value mono green"><?= htmlspecialchars($booking_ref) ?></div>
        </div>
        <div class="ig-item">
          <div class="ig-label"><i class="fas fa-receipt"></i>Order ID</div>
          <div class="ig-value mono">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></div>
        </div>
        <div class="ig-item">
          <div class="ig-label"><i class="fas fa-user"></i>Customer Name</div>
          <div class="ig-value"><?= htmlspecialchars($uname) ?></div>
        </div>
        <div class="ig-item">
          <div class="ig-label"><i class="fas fa-calendar-check"></i>Payment Date</div>
          <div class="ig-value"><?= date('d M Y') ?></div>
        </div>
        <div class="ig-item full">
          <div class="ig-label"><i class="fas fa-fingerprint"></i>Razorpay Transaction ID</div>
          <div class="ig-value mono green"><?= htmlspecialchars($payment_id) ?></div>
        </div>
      </div>

      <hr class="dashed-line">
      <div class="receipt-divider">Payment Breakdown</div>

      <?php
        $pay = [];
        // Re-derive from the amount stored — use session data already unpacked above
        // We display the now_payable split
        $advance  = round($amount * (15000 / (15000 + 4500)), 2); // approximate fallback
        // Just show total paid cleanly
      ?>
      <div class="breakdown-row">
        <span class="bl"><i class="fas fa-credit-card"></i>Total Amount Paid</span>
        <span class="bv">&#8377;<?= number_format($amount, 2) ?></span>
      </div>
      <div class="breakdown-row">
        <span class="bl"><i class="fas fa-shield-alt"></i>Payment Method</span>
        <span class="bv" style="color:var(--blue)">Razorpay</span>
      </div>
      <div class="breakdown-row">
        <span class="bl"><i class="fas fa-check-circle"></i>Payment Status</span>
        <span class="bv green"><i class="fas fa-circle" style="font-size:7px;vertical-align:middle"></i> Paid Successfully</span>
      </div>
      <div class="breakdown-total">
        <span class="btl">Total Paid</span>
        <span class="btv">&#8377;<?= number_format($amount, 2) ?></span>
      </div>

    </div><!-- receipt-body -->

    <!-- Ticket strip -->
    <div class="ticket-strip">
      <div class="ts-item"><i class="fas fa-calendar-check"></i><span>Paid: <strong><?= date('d M Y') ?></strong></span></div>
      <div class="ts-item"><i class="fas fa-lock"></i><span>Secured by <strong>Razorpay</strong></span></div>
    </div>

    <!-- Action buttons -->
    <div class="action-btns">
      <a href="booking_details.php?bid=<?= $booking_id ?>" class="btn-cta btn-green">
        <i class="fas fa-clipboard-check"></i>
        View Booking Details
      </a>
      <a href="profile.php" class="btn-cta btn-blue">
        <i class="fas fa-user-circle"></i>
        Go to My Profile
      </a>
      <a href="car.php" class="btn-cta btn-outline">
        <i class="fas fa-car-side"></i>
        Book Another Car
      </a>
    </div>

  </div><!-- receipt-card -->

</div><!-- page-wrap -->

<script src="../js/jquery.min.js"></script>
<script src="../js/jquery-migrate-3.0.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/main.js"></script>
<script>
// Navbar
(function(){
  var nav = document.getElementById('ftco-navbar');
  if(!nav) return;
  function onScroll(){ nav.classList.toggle('scrolled', window.scrollY > 60); }
  onScroll();
  window.addEventListener('scroll', onScroll, {passive:true});
})();

// Confetti burst
(function(){
  var wrap  = document.getElementById('confettiWrap');
  var colors = ['#2ecc71','#1a8cff','#f5a623','#ff6b6b','#b464ff','#fff'];
  var count = 80;

  for(var i = 0; i < count; i++){
    (function(i){
      setTimeout(function(){
        var p = document.createElement('div');
        p.className = 'cp';
        var color = colors[Math.floor(Math.random() * colors.length)];
        var size  = Math.random() * 8 + 4;
        var left  = Math.random() * 100;
        var dur   = Math.random() * 2 + 2;
        var delay = Math.random() * 2;
        p.style.cssText =
          'left:' + left + 'vw;' +
          'top:-20px;' +
          'width:' + size + 'px;' +
          'height:' + size + 'px;' +
          'background:' + color + ';' +
          'border-radius:' + (Math.random() > 0.5 ? '50%' : '2px') + ';' +
          'animation-duration:' + dur + 's;' +
          'animation-delay:' + delay + 's;';
        wrap.appendChild(p);
        setTimeout(function(){ p.remove(); }, (dur + delay) * 1000 + 500);
      }, i * 30);
    })(i);
  }
})();
</script>
</body>
</html>