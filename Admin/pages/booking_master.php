<?php
require("connect.php");
session_name('admin_session');
session_start();

// ── Handle APPROVE action ─────────────────────────────────────────────────────
if(isset($_POST['approve_booking'])){
    $bid = intval($_POST['booking_id']);
    mysqli_query($con, "UPDATE booking_master  SET booking_status='Approved' WHERE booking_id=$bid");
    mysqli_query($con, "UPDATE booking_details SET booking_status='Approved' WHERE booking_id=$bid");
    $_SESSION['admin_toast'] = ['type'=>'success','msg'=>'Booking #'.str_pad($bid,6,'0',STR_PAD_LEFT).' approved successfully.'];
    header("location: booking_master.php"); exit;
}

// ── Handle REJECT action ──────────────────────────────────────────────────────
if(isset($_POST['reject_booking'])){
    $bid = intval($_POST['booking_id']);
    mysqli_query($con, "UPDATE booking_master  SET booking_status='Rejected' WHERE booking_id=$bid");
    mysqli_query($con, "UPDATE booking_details SET booking_status='Rejected' WHERE booking_id=$bid");
    $_SESSION['admin_toast'] = ['type'=>'reject','msg'=>'Booking #'.str_pad($bid,6,'0',STR_PAD_LEFT).' has been rejected.'];
    header("location: booking_master.php"); exit;
}

// ── Filters ───────────────────────────────────────────────────────────────────
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
// Normalize known filter keys that arrive lowercased from the URL
if($filter_status === 'completed')  $filter_status = 'Completed';
if($filter_status === 'approved')   $filter_status = 'Approved';
if($filter_status === 'cancelled')  $filter_status = 'Cancelled';
if($filter_status === 'rejected')   $filter_status = 'Rejected';
$search        = isset($_GET['search']) ? mysqli_real_escape_string($con, trim($_GET['search'])) : '';

// Normalize "not-approved" filter to DB value "Pending"
$db_filter_status = $filter_status;
if($filter_status === 'not-approved') $db_filter_status = 'Pending';

$where_parts = ["1=1"];
if($db_filter_status !== 'all'){
    $fs = mysqli_real_escape_string($con, $db_filter_status);

   if($db_filter_status === 'Completed'){
    $where_parts[] = "(bd.trip_status = 'Completed' OR bm.booking_status = 'Completed')";
    } elseif($db_filter_status === 'not-started'){
        // Not Started = Approved bookings where trip hasn't completed
        $where_parts[] = "bm.booking_status = 'Approved'";
        $where_parts[] = "(bd.trip_status IS NULL OR bd.trip_status != 'Completed')";
    } else {
        $where_parts[] = "bm.booking_status = '$fs'";
    }
}
if(!empty($search)){
    $where_parts[] = "(u.uname LIKE '%$search%'
                       OR u.email LIKE '%$search%'
                       OR u.mobno LIKE '%$search%'
                       OR c.car_display_name LIKE '%$search%'
                       OR c.car_number_plate LIKE '%$search%'
                       OR d.driver_name LIKE '%$search%'
                       OR d.driver_mobile LIKE '%$search%'
                       OR d.driver_email LIKE '%$search%'
                       OR bm.booking_id LIKE '%$search%')";
}
$where_sql = implode(' AND ', $where_parts);

// ── Main query ────────────────────────────────────────────────────────────────
$mainQ = mysqli_query($con,
    "SELECT
        bm.booking_id, bm.ui, bm.car_id, bm.driver_id,
        bm.pickup_datetime, bm.actual_return_datetime,
        bm.pan_aadhar_no, bm.booking_status, bm.created_at,
        bd.base_amount, bd.driver_amount,
        bd.security_deposit, bd.total_amount, bd.late_fee_per_hour,
        bd.trip_status,
        u.uname, u.email, u.mobno, u.address, u.photo AS user_photo,
        c.car_display_name, c.car_number_plate, c.gear_type,
        c.fuel_type, c.seating_capacity, c.primary_image,
        b.brand_name,
        d.driver_name, d.driver_mobile, d.experience_years,
        d.profile_image AS driver_photo
     FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     LEFT JOIN users_master    u  ON u.ui           = bm.ui
     LEFT JOIN car_master      c  ON c.car_id        = bm.car_id
     LEFT JOIN brand_master    b  ON b.brand_id      = c.brand_id
     LEFT JOIN driver_master   d  ON d.driver_id     = bm.driver_id
     WHERE $where_sql
     ORDER BY bm.created_at DESC"
);
$bookings = [];
while($row = mysqli_fetch_assoc($mainQ)) $bookings[] = $row;

// ── Counts (all statuses) ─────────────────────────────────────────────────────
$counts = ['all'=>0,'Pending'=>0,'Approved'=>0,'Cancelled'=>0,'Rejected'=>0,'Completed'=>0,'not-started'=>0];
$cQ = mysqli_query($con,"SELECT booking_status, COUNT(*) AS cnt FROM booking_master GROUP BY booking_status");
while($c = mysqli_fetch_assoc($cQ)){
    $counts[$c['booking_status']] = (int)$c['cnt'];
    $counts['all'] += (int)$c['cnt'];
}
// Count completed trips from booking_details
$compQ = mysqli_query($con,
    "SELECT COUNT(DISTINCT bm.booking_id) AS cnt
     FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     WHERE bd.trip_status = 'Completed' OR bm.booking_status = 'Completed'"
);
if($compRow = mysqli_fetch_assoc($compQ)) $counts['Completed'] = (int)$compRow['cnt'];

// Not Started = Approved bookings where trip_status != Completed
$nsQ = mysqli_query($con,"SELECT COUNT(*) AS cnt FROM booking_master bm LEFT JOIN booking_details bd ON bd.booking_id=bm.booking_id WHERE bm.booking_status='Approved' AND (bd.trip_status IS NULL OR bd.trip_status != 'Completed')");
if($nsRow = mysqli_fetch_assoc($nsQ)) $counts['not-started'] = (int)$nsRow['cnt'];

// ── Fetch all payments for displayed bookings (separate query, no duplicates) ─
$all_bid_list = implode(',', array_map(fn($b) => intval($b['booking_id']), $bookings) ?: [0]);
$paymentsQ = mysqli_query($con,
    "SELECT booking_id, payment_type, paid_amount, payment_status, added_on
 FROM payment_master
 WHERE booking_id IN ($all_bid_list)
 ORDER BY payid ASC"
);
$payments_by_booking = [];
while ($prow = mysqli_fetch_assoc($paymentsQ)) {
    $pbid = intval($prow['booking_id']);
    $payments_by_booking[$pbid][] = $prow;
}

$toast = $_SESSION['admin_toast'] ?? null;
unset($_SESSION['admin_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Management — Admin</title>
<link rel="icon" href="../assets/img/kaiadmin/favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/plugins.min.css">
<link rel="stylesheet" href="../assets/css/kaiadmin.min.css">

<style>
/* STAT CARDS */
.stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.stat-card-link { text-decoration:none; display:block; }
.stat-card-link:hover .stat-card { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,0.15); }
.stat-card {
    border-radius:12px; border:none; padding:18px 20px;
    transition:transform .2s,box-shadow .2s;
    position:relative; overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.stat-card.active-filter { outline:3px solid rgba(255,255,255,0.6); }
.stat-card.s-all          { background:linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.s-not-approved { background:linear-gradient(135deg,#f59e0b,#d97706); }
.stat-card.s-approved     { background:linear-gradient(135deg,#22c55e,#16a34a); }
.stat-card.s-cancelled    { background:linear-gradient(135deg,#ef4444,#b91c1c); }
.stat-card.s-rejected     { background:linear-gradient(135deg,#6b7280,#374151); }
.stat-card.s-completed    { background:linear-gradient(135deg,#0ea5e9,#0284c7); }
.stat-icon  { font-size:20px; color:rgba(255,255,255,0.8); margin-bottom:6px; }
.stat-val   { font-size:30px; font-weight:800; color:#fff; line-height:1; margin-bottom:3px; }
.stat-label { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,0.8); }

/* FILTER BAR */
.filter-bar { display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.filter-bar .sw  { position:relative; flex:1; max-width:360px; }
.filter-bar .sw i{ position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#aaa; font-size:12px; }
.filter-bar .si  {
    width:100%; padding:9px 12px 9px 34px;
    border:1px solid #dee2e6; border-radius:8px;
    font-size:13px; outline:none; background:#fff; transition:border-color .2s;
}
.filter-bar .si:focus { border-color:#667eea; }
.btn-go  { padding:9px 18px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
.btn-go:hover { opacity:.88; }
.btn-clr { padding:9px 14px; background:#f1f3f5; color:#555; border:1px solid #dee2e6; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; }
.btn-clr:hover { background:#e9ecef; color:#333; }
.rc { margin-left:auto; font-size:12px; color:#888; white-space:nowrap; }

/* BOOKING CARDS */
.bookings-grid { display:flex; flex-direction:column; gap:18px; }
.booking-card {
    border-radius:12px; box-shadow:0 3px 12px rgba(0,0,0,0.07);
    border:1px solid #eee; overflow:hidden; background:#fff;
    transition:box-shadow .2s,transform .15s;
}
.booking-card:hover { box-shadow:0 6px 22px rgba(0,0,0,0.11); transform:translateY(-1px); }

/* Card Header */
.bch {
    padding:14px 20px; display:flex; align-items:center;
    justify-content:space-between; flex-wrap:wrap; gap:8px;
}
.bch.status-not-approved { background:linear-gradient(135deg,#f59e0b,#d97706); }
.bch.status-approved     { background:linear-gradient(135deg,#22c55e,#16a34a); }
.bch.status-cancelled    { background:linear-gradient(135deg,#ef4444,#b91c1c); }
.bch.status-rejected     { background:linear-gradient(135deg,#6b7280,#374151); }
.bch.status-completed    { background:linear-gradient(135deg,#0ea5e9,#0284c7); }
.bch-ref  { font-family:'Courier New',monospace; font-size:14px; font-weight:700; color:#fff; letter-spacing:.05em; }
.bch-date { font-size:11px; color:rgba(255,255,255,0.75); margin-top:2px; }
.sbadge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:100px;
    font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    background:rgba(255,255,255,0.22); color:#fff; border:1px solid rgba(255,255,255,0.35);
}
.sdot { width:6px; height:6px; border-radius:50%; background:#fff; flex-shrink:0; }
.sdot.blink { animation:blink 1.5s ease infinite; }
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

/* Trip Status Badge */
.trip-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:100px;
    font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    border:1px solid rgba(255,255,255,0.35); color:#fff;
}
.trip-badge.not-started { background:rgba(107,114,128,0.35); }
.trip-badge.started     { background:rgba(59,130,246,0.45); }
.trip-badge.completed   { background:rgba(14,165,233,0.45); }

/* Card Body */
.bcb { padding:18px 20px; }
.booking-sections {
    display:grid;
    grid-template-columns: 2fr 1.4fr 1.2fr 1.1fr;
    gap:16px;
}
.bsec-title {
    font-size:9px; font-weight:800; letter-spacing:.12em;
    text-transform:uppercase; color:#999; margin-bottom:10px;
    display:flex; align-items:center; gap:5px;
}
.bsec-title i { font-size:9px; color:#667eea; }

/* Person */
.prow { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.pavatar {
    width:42px; height:42px; border-radius:50%;
    object-fit:cover; border:2px solid #e9ecef; flex-shrink:0;
}
.pavatar-ph {
    width:42px; height:42px; border-radius:50%;
    background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
    display:flex; align-items:center; justify-content:center;
    font-size:17px; color:#667eea; flex-shrink:0;
}
.pname { font-size:13px; font-weight:700; color:#1a1a2e; margin-bottom:2px; }
.pmeta { font-size:11px; color:#6c757d; display:flex; align-items:center; gap:4px; line-height:1.5; }
.pmeta i { font-size:9px; color:#667eea; }

/* Car */
.car-img  { width:80px; height:48px; object-fit:contain; display:block; margin-bottom:6px; filter:drop-shadow(0 2px 6px rgba(0,0,0,.12)); }
.car-name { font-size:13px; font-weight:700; color:#1a1a2e; margin-bottom:2px; }
.car-sub  { font-size:11px; color:#6c757d; }
.car-plate{ font-family:'Courier New',monospace; font-size:10px; font-weight:700; color:#495057; background:#f8f9fa; border:1px solid #dee2e6; padding:2px 7px; border-radius:3px; display:inline-block; margin-top:3px; }
.sdpill   { display:inline-flex; align-items:center; gap:5px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; font-size:11px; font-weight:700; padding:4px 11px; border-radius:100px; }

/* Dates */
.dt-block { margin-bottom:9px; }
.dt-label { font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#999; margin-bottom:1px; display:flex; align-items:center; gap:5px; }
.dt-label i { font-size:9px; }
.dt-val   { font-size:13px; font-weight:700; color:#1a1a2e; }
.dt-time  { font-size:11px; color:#6c757d; }
.dur-pill { display:inline-flex; align-items:center; gap:5px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; font-size:10px; font-weight:700; padding:3px 10px; border-radius:100px; font-family:'Courier New',monospace; margin-top:6px; }

/* Pricing */
.pi     { display:flex; justify-content:space-between; margin-bottom:5px; font-size:12px; }
.pi .pk { color:#6c757d; }
.pi .pv { font-weight:600; color:#1a1a2e; }
.pi.tot { border-top:1px solid #dee2e6; padding-top:7px; margin-top:5px; }
.pi.tot .pk { font-weight:700; color:#1a1a2e; font-size:13px; }
.pi.tot .pv { font-size:15px; font-weight:800; color:#22c55e; font-family:'Courier New',monospace; }

/* ── PAYMENT STATUS BLOCK ── */
.pay-status-block {
    margin-top: 10px;
    border-radius: 7px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    font-size: 11px;
}
.psb-header {
    padding: 5px 10px;
    font-size: 9px; font-weight: 800; letter-spacing: .1em;
    text-transform: uppercase; display: flex; align-items: center; gap: 5px;
}
.psb-header.full    { background: #f0fdf4; color: #166534; border-bottom: 1px solid #bbf7d0; }
.psb-header.deposit { background: #fffbeb; color: #92400e; border-bottom: 1px solid #fde68a; }
.psb-header.unpaid  { background: #fef9ec; color: #92400e; border-bottom: 1px solid #fde68a; }
.psb-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 5px 10px; border-bottom: 1px solid #f1f3f5;
    font-size: 11px;
}
.psb-row:last-child { border-bottom: none; }
.psb-row .pk { color: #6c757d; display: flex; align-items: center; gap: 4px; }
.psb-row .pv { font-weight: 700; }
.psb-row .pv.green  { color: #16a34a; }
.psb-row .pv.gold   { color: #d97706; }
.psb-row .pv.blue   { color: #2563eb; }
.psb-row .pv.red    { color: #dc2626; }
.psb-row .pv.gray   { color: #6b7280; }

/* Pan pill */
.pan-pill { display:inline-flex; align-items:center; gap:5px; background:#f8f9fa; border:1px solid #dee2e6; font-family:'Courier New',monospace; font-size:11px; color:#495057; padding:3px 9px; border-radius:5px; }

/* Trip Status inline pill (card body) */
.ts-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:100px;
    font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    margin-top:8px;
}
.ts-pill.not-started { background:#f1f5f9; border:1px solid #cbd5e1; color:#64748b; }
.ts-pill.started     { background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; }
.ts-pill.completed   { background:#e0f2fe; border:1px solid #7dd3fc; color:#0369a1; }

/* Card Footer */
.bcf {
    background:#f8f9fa; border-top:1px solid #eee;
    padding:12px 20px; display:flex; align-items:center;
    justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.fm   { font-size:11px; color:#888; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.fm span { display:flex; align-items:center; gap:4px; }
.fm i { font-size:9px; color:#667eea; }
.abtns { display:flex; align-items:center; gap:8px; }

/* ── PAYMENT PILL in footer ── */
.foot-pay-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 100px;
    font-size: 11px; font-weight: 700;
}
.foot-pay-pill.full    { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.foot-pay-pill.deposit { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.foot-pay-pill.unpaid  { background: #fef9ec; border: 1px solid #fde68a; color: #b45309; }
.foot-pay-pill.none    { background: #f1f3f5; border: 1px solid #dee2e6; color: #6b7280; }

.btn-approve    { padding:7px 18px; background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; border:none; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:filter .2s,transform .12s; }
.btn-approve:hover { filter:brightness(1.08); transform:translateY(-1px); }
.btn-reject-open{ padding:7px 18px; background:transparent; border:1.5px solid #fca5a5; color:#ef4444; border-radius:7px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:background .2s,border-color .2s; }
.btn-reject-open:hover { background:#fef2f2; border-color:#ef4444; }
.done-badge { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:#6c757d; background:#f1f3f5; padding:6px 13px; border-radius:7px; border:1px solid #dee2e6; }

/* Empty state */
.empty-state { text-align:center; padding:64px 32px; background:#fff; border-radius:12px; border:1px solid #eee; }
.empty-state i { font-size:48px; color:#ccc; margin-bottom:14px; display:block; }
.empty-state h4 { font-size:18px; font-weight:700; color:#555; margin-bottom:6px; }
.empty-state p  { font-size:13px; color:#888; }

/* Toast */
.toast-fixed { position:fixed; top:20px; right:24px; z-index:9999; padding:12px 20px; border-radius:10px; display:flex; align-items:center; gap:10px; font-size:13px; font-weight:600; transform:translateX(130%); transition:transform .4s cubic-bezier(.34,1.56,.64,1); max-width:340px; box-shadow:0 6px 20px rgba(0,0,0,0.12); }
.toast-fixed.show { transform:translateX(0); }
.toast-fixed.success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
.toast-fixed.reject  { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }

/* Reject Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:5000; display:none; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:16px; padding:36px 30px; max-width:420px; width:90%; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,0.2); }
.modal-icon-wrap { width:60px; height:60px; border-radius:50%; background:#fef2f2; border:2px solid #fca5a5; display:flex; align-items:center; justify-content:center; font-size:22px; color:#ef4444; margin:0 auto 16px; }
.modal-box h4 { font-size:19px; font-weight:800; color:#1a1a2e; margin-bottom:8px; }
.modal-box p  { font-size:13px; color:#6c757d; line-height:1.6; margin-bottom:6px; }
.modal-ref { font-family:'Courier New',monospace; font-size:13px; color:#667eea; background:#f0f4ff; border:1px solid #c7d2fe; padding:6px 14px; border-radius:6px; display:inline-block; margin-bottom:22px; }
.modal-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.mbtn { padding:11px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; border:none; transition:all .2s; }
.mbtn-reject { background:#ef4444; color:#fff; }
.mbtn-reject:hover { background:#b91c1c; }
.mbtn-keep { background:#f1f3f5; color:#555; border:1px solid #dee2e6; }
.mbtn-keep:hover { background:#e9ecef; }

@media(max-width:1100px){ .booking-sections{grid-template-columns:1fr 1fr} }
@media(max-width:768px) { .booking-sections{grid-template-columns:1fr} .stats-row{grid-template-columns:repeat(3,1fr)} }
@media(max-width:480px) { .stats-row{grid-template-columns:1fr 1fr} }
</style>
</head>

<body>

<?php include('../components/navbar.php'); ?>

  <div class="page-inner"><br><br><br>

    <!-- PAGE TITLE -->
    <div class="d-flex align-items-center justify-content-between mb-4 pt-2">
      <div>
        <h4 class="mb-1" style="font-weight:800;color:#1a1a2e">
          <i class="fas fa-calendar-check me-2" style="color:#667eea"></i>Booking Management
        </h4>
        <p class="text-muted mb-0" style="font-size:13px">View, approve, and manage all customer bookings</p>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
      <?php
      $stat_tabs = [
  ['key'=>'all',          'label'=>'All Bookings',  'icon'=>'fa-list',          'cls'=>'s-all'],
  ['key'=>'not-started',  'label'=>'Not Started',   'icon'=>'fa-check-circle',  'cls'=>'s-approved'],
  ['key'=>'Completed',    'label'=>'Completed',     'icon'=>'fa-flag-checkered','cls'=>'s-completed'],
  ['key'=>'not-approved', 'label'=>'Not Approved',  'icon'=>'fa-clock',         'cls'=>'s-not-approved'],
  ['key'=>'Cancelled',    'label'=>'Cancelled',     'icon'=>'fa-times-circle',  'cls'=>'s-cancelled'],
  ['key'=>'Rejected',     'label'=>'Rejected',      'icon'=>'fa-ban',           'cls'=>'s-rejected'],
];
      foreach($stat_tabs as $st):
        $is_active = (strtolower($filter_status) === strtolower($st['key']));
        // Map display key to count key
        $cnt_key = match($st['key']) {
    'not-approved' => 'Pending',
    'not-started'  => 'not-started',
    default        => $st['key'],
};
        $cnt = $counts[$cnt_key] ?? 0;
      ?>
      <a href="?status=<?= strtolower($st['key']) ?><?= !empty($search)?'&search='.urlencode($search):'' ?>"
         class="stat-card-link">
        <div class="stat-card <?= $st['cls'] ?> <?= $is_active ? 'active-filter' : '' ?>">
          <div class="stat-icon"><i class="fas <?= $st['icon'] ?>"></i></div>
          <div class="stat-val"><?= $cnt ?></div>
          <div class="stat-label"><?= $st['label'] ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap">
        <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
        <div class="sw">
          <i class="fas fa-search"></i>
          <input type="text" name="search" class="si"
                 placeholder="Search by name, email, mobile, car, plate, driver…"
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-go"><i class="fas fa-search me-1"></i>Search</button>
        <?php if(!empty($search)): ?>
        <a href="?status=<?= htmlspecialchars($filter_status) ?>" class="btn-clr">
          <i class="fas fa-times me-1"></i>Clear
        </a>
        <?php endif; ?>
      </form>
      <div class="rc"><?= count($bookings) ?> booking<?= count($bookings)!=1?'s':'' ?></div>
    </div>

    <!-- BOOKING CARDS -->
    <div class="bookings-grid">

    <?php if(empty($bookings)): ?>
      <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h4>No bookings found</h4>
        <p>No bookings match your current filter or search query.</p>
      </div>

    <?php else: ?>
    <?php foreach($bookings as $bk):
      $bid     = intval($bk['booking_id']);
      $bref    = 'CB-'.str_pad($bid, 6, '0', STR_PAD_LEFT);
      $bstatus = $bk['booking_status'];
      // Map Pending → Not Approved for display
     $bstatus_display = ($bstatus === 'Pending') ? 'Not Approved' : $bstatus;

// Trip status — must be defined BEFORE $hdr_cls
$trip_status = $bk['trip_status'] ?? 'Not Started';
if(empty($trip_status)) $trip_status = 'Not Started';
$trip_cls = strtolower(str_replace(' ', '-', $trip_status));
$trip_icon = match($trip_status){
    'Started'   => 'fa-play-circle',
    'Completed' => 'fa-flag-checkered',
    default     => 'fa-pause-circle'
};

// Override header color to blue if trip is completed
$hdr_cls = ($trip_status === 'Completed')
    ? 'status-completed'
    : 'status-'.strtolower(str_replace(' ', '-', $bstatus_display));

      $total   = floatval($bk['total_amount']    ?? 0);
      $base    = floatval($bk['base_amount']     ?? 0);
      $drv_amt = floatval($bk['driver_amount']   ?? 0);
      $sec_dep = floatval($bk['security_deposit']?? 0);
      $gst     = round(($base + $drv_amt) * 0.05, 2);

      $pickup_dt     = new DateTime($bk['pickup_datetime']);
      $return_dt_raw = (!empty($bk['actual_return_datetime']) && $bk['actual_return_datetime'] !== '0000-00-00 00:00:00')
                        ? new DateTime($bk['actual_return_datetime']) : null;
      $dur_str = '1d';
      if($return_dt_raw){
        $diff = $pickup_dt->diff($return_dt_raw);
        $dp = [];
        if($diff->days > 0) $dp[] = $diff->days.'d';
        if($diff->h > 0)    $dp[] = $diff->h.'h';
        $dur_str = $dp ? implode(' ',$dp) : '< 1h';
      }

      $user_img   = !empty($bk['user_photo'])   ? '../../User/user_profile/'.$bk['user_photo']  : '';
      $driver_img = !empty($bk['driver_photo'])  ? '../../Driver/images/driver_profile/'.$bk['driver_photo']   : '';
      $car_img    = !empty($bk['primary_image']) ? 'images/car_images/'.$bk['primary_image']     : '';

     // ── Payment data for this booking ─────────────────────────────────────────
$bk_payments      = $payments_by_booking[$bid] ?? [];
$bk_total_paid    = 0;
$bk_is_fully_paid = false;
$bk_is_dep_paid   = false;
$bk_dep_amount    = 0;
$bk_dep_date      = '';
$bk_full_date     = '';

foreach ($bk_payments as $p) {
    $bk_total_paid += floatval($p['paid_amount']);

    if (($p['payment_type'] ?? '') === 'deposit') {
        $bk_is_dep_paid = true;
        $bk_dep_amount  = floatval($p['paid_amount']);
        $bk_dep_date    = $p['added_on'] ?? '';
    }

    if (($p['payment_status'] ?? 0) == 2) {
        $bk_is_fully_paid = true;
        if (!$bk_full_date) $bk_full_date = $p['added_on'] ?? '';
    }

    if (($p['payment_type'] ?? '') === 'rental') {
        $bk_is_fully_paid = true;
        $bk_full_date     = $p['added_on'] ?? '';
    }
}

$bk_remaining = max(0, $total - $bk_total_paid);

if ($bk_remaining <= 0 && $bk_total_paid > 0) {
    $bk_is_fully_paid = true;
    if (!$bk_full_date && !empty($bk_payments)) {
        $bk_full_date = $bk_payments[count($bk_payments) - 1]['added_on'] ?? '';
    }
}

$bk_full_amount = $bk_total_paid;

    ?>

    <div class="booking-card">

      <!-- Header -->
      <div class="bch <?= $hdr_cls ?>">
        <div>
          <div class="bch-ref"><i class="fas fa-ticket-alt me-2" style="opacity:.8"></i><?= $bref ?></div>
          <div class="bch-date">Booked on <?= date('d M Y, h:i A', strtotime($bk['created_at'])) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <!-- Trip Status badge in header -->
          <span class="trip-badge <?= $trip_cls ?>">
            <i class="fas <?= $trip_icon ?>"></i>
            Trip: <?= htmlspecialchars($trip_status) ?>
          </span>
          <span class="sbadge">
            <span class="sdot <?= $bstatus==='Pending'?'blink':'' ?>"></span>
            <?= htmlspecialchars($bstatus_display) ?>
          </span>
        </div>
      </div>

      <!-- Body -->
      <div class="bcb">
        <div class="booking-sections">

          <!-- 1. Customer -->
          <div>
            <div class="bsec-title"><i class="fas fa-user"></i> Customer</div>
            <div class="prow">
              <?php if($user_img): ?>
                <img src="<?= htmlspecialchars($user_img) ?>" class="pavatar"
                     onerror="this.outerHTML='<div class=&quot;pavatar-ph&quot;><i class=&quot;fas fa-user&quot;></i></div>'" alt="">
              <?php else: ?>
                <div class="pavatar-ph"><i class="fas fa-user"></i></div>
              <?php endif; ?>
              <div>
                <div class="pname"><?= htmlspecialchars($bk['uname'] ?? '—') ?></div>
                <div class="pmeta"><i class="fas fa-envelope"></i><?= htmlspecialchars($bk['email'] ?? '') ?></div>
                <?php if(!empty($bk['mobno'])): ?>
                <div class="pmeta"><i class="fas fa-phone"></i><?= htmlspecialchars($bk['mobno']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php if(!empty($bk['address'])): ?>
            <div class="pmeta mb-1"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($bk['address']) ?></div>
            <?php endif; ?>
            <span class="pan-pill mt-1 d-inline-flex"><i class="fas fa-id-card" style="color:#667eea"></i><?= htmlspecialchars($bk['pan_aadhar_no'] ?? '—') ?></span>
          </div>

          <!-- 2. Vehicle + Driver -->
          <div>
            <div class="bsec-title"><i class="fas fa-car"></i> Vehicle</div>
            <?php if($car_img): ?>
            <img src="<?= htmlspecialchars($car_img) ?>" class="car-img"
                 onerror="this.style.display='none'" alt="">
            <?php endif; ?>
            <div class="car-name"><?= htmlspecialchars($bk['car_display_name'] ?? '—') ?></div>
            <div class="car-sub"><?= htmlspecialchars($bk['brand_name'] ?? '') ?></div>
            <?php if(!empty($bk['car_number_plate'])): ?>
            <span class="car-plate"><?= htmlspecialchars($bk['car_number_plate']) ?></span>
            <?php endif; ?>
            <div class="car-sub mt-1 d-flex gap-2 flex-wrap">
              <?php if(!empty($bk['fuel_type'])): ?><span><i class="fas fa-gas-pump" style="color:#667eea;font-size:9px"></i> <?= htmlspecialchars($bk['fuel_type']) ?></span><?php endif; ?>
              <?php if(!empty($bk['gear_type'])): ?><span><i class="fas fa-cogs" style="color:#667eea;font-size:9px"></i> <?= htmlspecialchars($bk['gear_type']) ?></span><?php endif; ?>
              <?php if(!empty($bk['seating_capacity'])): ?><span><i class="fas fa-users" style="color:#667eea;font-size:9px"></i> <?= $bk['seating_capacity'] ?>s</span><?php endif; ?>
            </div>

            <div class="bsec-title mt-3"><i class="fas fa-steering-wheel"></i> Driver</div>
            <?php if(!empty($bk['driver_name'])): ?>
            <div class="prow">
              <?php if($driver_img): ?>
                <img src="<?= htmlspecialchars($driver_img) ?>" class="pavatar"
                     onerror="this.outerHTML='<div class=&quot;pavatar-ph&quot;><i class=&quot;fas fa-user-tie&quot;></i></div>'" alt="">
              <?php else: ?>
                <div class="pavatar-ph"><i class="fas fa-user-tie"></i></div>
              <?php endif; ?>
              <div>
                <div class="pname"><?= htmlspecialchars($bk['driver_name']) ?></div>
                <?php if(!empty($bk['driver_mobile'])): ?>
                <div class="pmeta"><i class="fas fa-phone"></i><?= htmlspecialchars($bk['driver_mobile']) ?></div>
                <?php endif; ?>
                <?php if(!empty($bk['experience_years'])): ?>
                <div class="pmeta"><i class="fas fa-star"></i><?= $bk['experience_years'] ?> yr exp</div>
                <?php endif; ?>
              </div>
            </div>
            <?php else: ?>
            <span class="sdpill"><i class="fas fa-user-circle"></i> Self Drive</span>
            <?php endif; ?>

            <!-- Trip Status pill in body -->
            <div class="mt-2">
              <span class="ts-pill <?= $trip_cls ?>">
                <i class="fas <?= $trip_icon ?>"></i>
                <?= htmlspecialchars($trip_status) ?>
              </span>
            </div>
          </div>

          <!-- 3. Dates -->
          <div>
            <div class="bsec-title"><i class="fas fa-calendar-alt"></i> Rental Period</div>
            <div class="dt-block">
              <div class="dt-label"><i class="fas fa-arrow-right" style="color:#22c55e"></i> Pickup</div>
              <div class="dt-val"><?= date('d M Y', strtotime($bk['pickup_datetime'])) ?></div>
              <div class="dt-time"><?= date('h:i A', strtotime($bk['pickup_datetime'])) ?></div>
            </div>
            <div class="dt-block mt-2">
              <div class="dt-label"><i class="fas fa-arrow-left" style="color:#3b82f6"></i> Return</div>
              <?php if($return_dt_raw): ?>
              <div class="dt-val"><?= date('d M Y', strtotime($bk['actual_return_datetime'])) ?></div>
              <div class="dt-time"><?= date('h:i A', strtotime($bk['actual_return_datetime'])) ?></div>
              <?php else: ?>
              <div style="font-size:12px;color:#aaa;margin-top:2px">Not specified</div>
              <?php endif; ?>
            </div>
            <div class="dur-pill"><i class="fas fa-clock"></i><?= $dur_str ?></div>
          </div>

          <!-- 4. Pricing + Payment Status -->
          <div>
            <div class="bsec-title"><i class="fas fa-rupee-sign"></i> Fare Breakdown</div>
            <div class="pi"><span class="pk">Base Amount</span><span class="pv">&#8377;<?= number_format($base,2) ?></span></div>
            <?php if($drv_amt > 0): ?>
            <div class="pi"><span class="pk">Driver (10%)</span><span class="pv" style="color:#667eea">&#8377;<?= number_format($drv_amt,2) ?></span></div>
            <?php endif; ?>
            <div class="pi"><span class="pk">GST (5%)</span><span class="pv" style="color:#888">&#8377;<?= number_format($gst,2) ?></span></div>
            <?php if($sec_dep > 0): ?>
            <div class="pi"><span class="pk">Security Dep.</span><span class="pv" style="color:#f59e0b">&#8377;<?= number_format($sec_dep,2) ?></span></div>
            <?php endif; ?>
            <div class="pi tot"><span class="pk">Total</span><span class="pv">&#8377;<?= number_format($total,2) ?></span></div>
            <?php if(($bk['late_fee_per_hour']??0) > 0): ?>
            <div style="margin-top:6px;font-size:11px;color:#888">
              <i class="fas fa-exclamation-circle" style="color:#f59e0b"></i>
              Late fee: &#8377;<?= number_format($bk['late_fee_per_hour'],2) ?>/hr
            </div>
            <?php endif; ?>

            <!-- ── PAYMENT STATUS BLOCK ── -->
            <?php if ($bk_is_fully_paid): ?>
<div class="pay-status-block">
  <div class="psb-header full">
    <i class="fas fa-check-circle"></i> Full Payment Received
  </div>
  <?php if ($bk_is_dep_paid && $bk_full_amount > $bk_dep_amount): ?>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-shield-alt" style="font-size:9px;color:#d97706"></i> Security Deposit</span>
    <span class="pv gold">&#8377;<?= number_format($bk_dep_amount, 2) ?></span>
  </div>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-car" style="font-size:9px;color:#16a34a"></i> Rental Paid</span>
    <span class="pv green">&#8377;<?= number_format($bk_full_amount - $bk_dep_amount, 2) ?></span>
  </div>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-rupee-sign" style="font-size:9px;color:#16a34a"></i> Total Paid</span>
    <span class="pv green">&#8377;<?= number_format($bk_full_amount, 2) ?></span>
  </div>
  <?php else: ?>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-rupee-sign" style="font-size:9px;color:#16a34a"></i> Total Paid</span>
    <span class="pv green">&#8377;<?= number_format($bk_full_amount, 2) ?></span>
  </div>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-calendar" style="font-size:9px"></i> Date</span>
    <span class="pv gray"><?= $bk_full_date ? date('d M Y', strtotime($bk_full_date)) : '—' ?></span>
  </div>
  <?php endif; ?>
  <div class="psb-row">
    <span class="pk"><i class="fas fa-check" style="font-size:9px;color:#16a34a"></i> Due</span>
    <span class="pv green">&#8377;0.00</span>
  </div>
</div>

            <?php elseif ($bk_is_dep_paid): ?>
            <div class="pay-status-block">
              <div class="psb-header deposit">
                <i class="fas fa-shield-alt"></i> Security Deposit Paid
              </div>
              <div class="psb-row">
                <span class="pk"><i class="fas fa-shield-alt" style="font-size:9px;color:#d97706"></i> Deposit</span>
                <span class="pv gold">&#8377;<?= number_format($bk_dep_amount, 2) ?></span>
              </div>
              <div class="psb-row">
                <span class="pk"><i class="fas fa-calendar" style="font-size:9px"></i> Date</span>
                <span class="pv gray"><?= $bk_dep_date ? date('d M Y', strtotime($bk_dep_date)) : '—' ?></span>
              </div>
              <div class="psb-row">
                <span class="pk"><i class="fas fa-clock" style="font-size:9px;color:#2563eb"></i> Rental Due</span>
                <span class="pv blue">&#8377;<?= number_format($bk_remaining, 2) ?></span>
              </div>
            </div>

            <?php elseif ($bstatus === 'Approved'): ?>
            <div class="pay-status-block">
              <div class="psb-header unpaid">
                <i class="fas fa-clock"></i> Payment Pending
              </div>
              <div class="psb-row">
                <span class="pk"><i class="fas fa-rupee-sign" style="font-size:9px"></i> Paid</span>
                <span class="pv gray">&#8377;0.00</span>
              </div>
              <div class="psb-row">
                <span class="pk"><i class="fas fa-exclamation-circle" style="font-size:9px;color:#dc2626"></i> Due</span>
                <span class="pv red">&#8377;<?= number_format($total, 2) ?></span>
              </div>
            </div>
            <?php endif; ?>
            <!-- ── END PAYMENT STATUS BLOCK ── -->

          </div>

        </div><!-- end booking-sections -->
      </div><!-- end bcb -->

      <!-- Footer -->
      <div class="bcf">
        <div class="fm">
          <span><i class="fas fa-hashtag"></i><?= $bref ?></span>
          <span><i class="fas fa-clock"></i><?= date('d M Y', strtotime($bk['created_at'])) ?></span>
          <!-- ── PAYMENT PILL in footer ── -->
          <?php if ($bk_is_fully_paid): ?>
          <span class="foot-pay-pill full">
            <i class="fas fa-check-circle"></i>
            Fully Paid &middot; &#8377;<?= number_format($bk_full_amount, 2) ?>
          </span>
          <?php elseif ($bk_is_dep_paid): ?>
          <span class="foot-pay-pill deposit">
            <i class="fas fa-shield-alt"></i>
            Deposit Paid &middot; &#8377;<?= number_format($bk_dep_amount, 2) ?>
            &nbsp;&bull;&nbsp;
            <i class="fas fa-clock" style="color:#2563eb"></i>
            <span style="color:#2563eb">&#8377;<?= number_format($bk_remaining, 2) ?> due</span>
          </span>
          <?php elseif ($bstatus === 'Approved'): ?>
          <span class="foot-pay-pill unpaid">
            <i class="fas fa-clock"></i> Payment Pending
          </span>
          <?php else: ?>
          <span class="foot-pay-pill none">
            <i class="fas fa-minus-circle"></i> No Payment
          </span>
          <?php endif; ?>
          <!-- ── END PAYMENT PILL ── -->
        </div>
        <div class="abtns">
          <?php if($bstatus === 'Pending'): ?>
            <form method="POST" action="" style="margin:0">
              <input type="hidden" name="booking_id" value="<?= $bid ?>">
              <button type="submit" name="approve_booking" class="btn-approve">
                <i class="fas fa-check"></i> Approve
              </button>
            </form>
            <button class="btn-reject-open" onclick="openRejectModal(<?= $bid ?>, '<?= $bref ?>')">
              <i class="fas fa-ban"></i> Reject
            </button>
          <?php elseif($bstatus === 'Approved'): ?>
            <span class="done-badge"><i class="fas fa-check-circle" style="color:#22c55e"></i> Approved</span>
          <?php elseif($bstatus === 'Cancelled'): ?>
            <span class="done-badge"><i class="fas fa-times-circle" style="color:#ef4444"></i> Cancelled</span>
          <?php elseif($bstatus === 'Rejected'): ?>
            <span class="done-badge"><i class="fas fa-ban" style="color:#6b7280"></i> Rejected</span>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- end booking-card -->
    <?php endforeach; ?>
    <?php endif; ?>

    </div><!-- end bookings-grid -->

  </div><!-- end page-inner -->

<!-- REJECT MODAL -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal-box">
    <div class="modal-icon-wrap"><i class="fas fa-ban"></i></div>
    <h4>Reject This Booking?</h4>
    <p>The user will be notified that their booking has been rejected. This does not delete the record.</p>
    <div class="modal-ref" id="modalRef">—</div>
    <div class="modal-actions">
      <button class="mbtn mbtn-keep" onclick="closeRejectModal()">
        <i class="fas fa-arrow-left me-1"></i> Go Back
      </button>
      <form method="POST" action="" style="margin:0">
        <input type="hidden" name="booking_id" id="rejectBid" value="">
        <button type="submit" name="reject_booking" class="mbtn mbtn-reject" style="width:100%">
          <i class="fas fa-times me-1"></i> Confirm Reject
        </button>
      </form>
    </div>
  </div>
</div>

<!-- TOAST -->
<?php if($toast): ?>
<div class="toast-fixed <?= $toast['type'] ?>" id="adminToast">
  <i class="fas <?= $toast['type']==='success'?'fa-check-circle':'fa-ban' ?>"></i>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/kaiadmin.min.js"></script>
<script>
function openRejectModal(bid, bref){
  document.getElementById('rejectBid').value = bid;
  document.getElementById('modalRef').textContent = bref;
  document.getElementById('rejectModal').classList.add('open');
}
function closeRejectModal(){
  document.getElementById('rejectModal').classList.remove('open');
}
document.getElementById('rejectModal').addEventListener('click', function(e){
  if(e.target===this) closeRejectModal();
});
<?php if($toast): ?>
(function(){
  const t = document.getElementById('adminToast');
  if(!t) return;
  setTimeout(()=>t.classList.add('show'), 100);
  setTimeout(()=>t.classList.remove('show'), 4500);
})();
<?php endif; ?>
</script>
</body>
</html>