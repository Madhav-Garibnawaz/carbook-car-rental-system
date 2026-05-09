<?php
include("connect.php");

if (session_status() === PHP_SESSION_NONE) {
  session_name('user_session');
    session_start();
}

if(!isset($_SESSION['user_id'])){
    header("location: register.php"); exit;
}
$uid = intval($_SESSION['user_id']);

function getDistance($lat1, $lon1, $lat2, $lon2) {
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return round($earth_radius * $c, 2);
}

if(isset($_POST['delete_booking'])){
    $bid = intval($_POST['booking_id']);
    $chkQ = mysqli_query($con,
        "SELECT booking_id FROM booking_master
         WHERE booking_id=$bid AND ui=$uid
           AND booking_status IN ('Cancelled','Rejected')"
    );
    if($chkQ && mysqli_num_rows($chkQ) > 0){
        mysqli_query($con, "DELETE FROM booking_details WHERE booking_id=$bid");
        mysqli_query($con, "DELETE FROM booking_master  WHERE booking_id=$bid AND ui=$uid");
        $_SESSION['profile_toast'] = ['type'=>'success','msg'=>'Booking removed successfully.'];
    } else {
        $_SESSION['profile_toast'] = ['type'=>'error','msg'=>'Could not delete. Only cancelled or rejected bookings can be removed.'];
    }
    header("location: profile.php"); exit;
}

$userQ = mysqli_query($con, "SELECT * FROM users_master WHERE ui=$uid");
$user  = mysqli_fetch_assoc($userQ) ?: [];

$bookingsQ = mysqli_query($con,
    "SELECT bm.*,
            bd.base_amount, bd.driver_amount, bd.security_deposit,
            bd.total_amount, bd.late_fee_per_hour, bd.trip_status,
            c.car_display_name, c.primary_image, c.car_number_plate,
            b.brand_name,
            d.driver_name
     FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     LEFT JOIN car_master      c  ON c.car_id      = bm.car_id
     LEFT JOIN brand_master    b  ON b.brand_id    = c.brand_id
     LEFT JOIN driver_master   d  ON d.driver_id   = bm.driver_id
     WHERE bm.ui = $uid
     ORDER BY bm.created_at DESC"
);
$bookings = [];
while($row = mysqli_fetch_assoc($bookingsQ)) $bookings[] = $row;

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

$counts = ['all'=>count($bookings),'Pending'=>0,'Approved'=>0,'Cancelled'=>0,'Rejected'=>0,'Completed'=>0];
foreach($bookings as $b){
    $bs = $b['booking_status'];
    if(isset($counts[$bs])) $counts[$bs]++;
    if($bs === 'Approved' && ($b['trip_status'] ?? '') === 'Completed') {
        $counts['Completed']++;
    }
}

// ── Fetch user's contact/support issues ──────────────────────────────────────
$issuesQ = mysqli_query($con,
    "SELECT contact_id, subject, message, status, admin_reply, replied_at, updated_at, created_at
     FROM contact_master
     WHERE sender_type = 'user' AND sender_id = $uid
     ORDER BY created_at DESC"
);
$user_issues = [];
while($irow = mysqli_fetch_assoc($issuesQ)) $user_issues[] = $irow;

$toast = $_SESSION['profile_toast'] ?? null;
unset($_SESSION['profile_toast']);

$member_since = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A';
$total_spent  = 0;
foreach($bookings as $b) $total_spent += floatval($b['total_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — CarBook</title>

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
:root{
  --green:#2ecc71;--blue:#1a8cff;--gold:#f5a623;--red:#ff6b6b;
  --dark:#0d0f14;--dark2:#141720;--dark3:#1c2030;--dark4:#232840;
  --border:rgba(255,255,255,0.07);--text:rgba(255,255,255,0.88);--muted:rgba(255,255,255,0.42);
  --font:'Poppins',sans-serif;--mono:'Space Mono',monospace;
}

html,body{background:#0d0f14 !important}
#ftco-navbar {
  background: rgba(10,12,16,0.98) !important;
  box-shadow: 0 1px 0 rgba(255,255,255,0.05) !important;
  transition: background .3s ease, box-shadow .3s ease;
}
#ftco-navbar.scrolled {
  background: rgba(7,9,12,1) !important;
  box-shadow: 0 4px 24px rgba(0,0,0,0.7) !important;
}

.profile-page *, .profile-page *::before, .profile-page *::after { box-sizing: border-box; }
.profile-page { font-family: var(--font); background: var(--dark); color: var(--text); min-height: 100vh; }

.bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
.bg-canvas::before {
  content: ''; position: absolute;
  width: 900px; height: 900px;
  background: radial-gradient(circle, rgba(26,140,255,0.09) 0%, transparent 65%);
  top: -300px; right: -200px;
  animation: drift 18s ease-in-out infinite alternate;
}
.bg-canvas::after {
  content: ''; position: absolute;
  width: 700px; height: 700px;
  background: radial-gradient(circle, rgba(46,204,113,0.06) 0%, transparent 65%);
  bottom: -150px; left: -150px;
  animation: drift 24s ease-in-out infinite alternate-reverse;
}
@keyframes drift { from{transform:translate(0,0) scale(1)} to{transform:translate(50px,35px) scale(1.08)} }
.grid-lines {
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 64px 64px;
}
.deco-bottom {
  position: fixed; bottom: 0; left: 0; right: 0; height: 3px; z-index: 100;
  background: linear-gradient(90deg, var(--green), var(--blue), var(--green));
  background-size: 200% 100%;
  animation: shimmer 3s linear infinite;
}
@keyframes shimmer { from{background-position:0%} to{background-position:200%} }
@keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.25} }
@keyframes pulse-ring { 0%{box-shadow:0 0 0 0 rgba(46,204,113,0.3)} 70%{box-shadow:0 0 0 16px rgba(46,204,113,0)} 100%{box-shadow:0 0 0 0 rgba(46,204,113,0)} }
@keyframes tripGlow { 0%,100%{box-shadow:0 0 0 0 rgba(46,204,113,0.4)} 60%{box-shadow:0 0 0 12px rgba(46,204,113,0)} }

.page-wrap { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 32px 24px 100px; }

.profile-hero {
  background: linear-gradient(135deg, var(--dark2) 0%, var(--dark4) 100%);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 36px 40px;
  display: flex;
  align-items: center;
  gap: 36px;
  margin-bottom: 28px;
  position: relative;
  overflow: hidden;
  animation: fadeUp .5s ease both;
}
.profile-hero::before {
  content: '';
  position: absolute;
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(26,140,255,0.1) 0%, transparent 65%);
  top: -200px; right: -100px;
  pointer-events: none;
}
.profile-hero::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--green), var(--blue));
  border-radius: 20px 20px 0 0;
}

.avatar-wrap { position: relative; flex-shrink: 0; }
.avatar-img {
  width: 110px; height: 110px; border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--green);
  box-shadow: 0 0 0 6px rgba(46,204,113,0.12), 0 16px 40px rgba(0,0,0,0.5);
  display: block;
  animation: pulse-ring 3s ease infinite;
}
.avatar-badge {
  position: absolute; bottom: 4px; right: 4px;
  width: 22px; height: 22px; border-radius: 50%;
  background: var(--green);
  border: 2px solid var(--dark);
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; color: #fff;
}
.avatar-completed-ring {
  position: absolute;
  top: -6px; left: -6px; right: -6px; bottom: -6px;
  border-radius: 50%;
  border: 2px dashed rgba(46,204,113,0.45);
  pointer-events: none;
  animation: spinSlow 12s linear infinite;
}
@keyframes spinSlow { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.avatar-completed-pill {
  position: absolute;
  top: -12px; left: 50%; transform: translateX(-50%);
  background: linear-gradient(135deg,#0a7a3e,#2ecc71);
  color: #fff; font-size: 9px; font-weight: 800;
  padding: 3px 9px; border-radius: 100px;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(46,204,113,0.4);
  display: flex; align-items: center; gap: 4px;
  border: 1.5px solid rgba(255,255,255,0.2);
  animation: tripGlow 2.5s ease infinite;
  letter-spacing: .04em;
}

.hero-info { flex: 1; }
.hero-name { font-size: 26px; font-weight: 800; color: #fff; letter-spacing: -.5px; margin-bottom: 4px; }
.hero-email { font-size: 13px; color: var(--muted); margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }
.hero-email i { color: var(--blue); font-size: 11px; }
.hero-meta { display: flex; gap: 24px; flex-wrap: wrap; }
.meta-item { display: flex; flex-direction: column; gap: 2px; }
.meta-label { font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
.meta-value { font-size: 14px; font-weight: 700; color: #fff; }
.meta-value.green { color: var(--green); }

.hero-stats { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; }
.stat-pill {
  display: flex; align-items: center; gap: 8px;
  background: var(--dark3); border: 1px solid var(--border);
  padding: 9px 16px; border-radius: 10px;
  white-space: nowrap;
}
.stat-pill i { font-size: 13px; }
.stat-pill .sp-val { font-size: 15px; font-weight: 800; color: #fff; }
.stat-pill .sp-lbl { font-size: 10px; color: var(--muted); font-weight: 600; }
.stat-pill.completed-pill {
  background: rgba(46,204,113,0.08);
  border-color: rgba(46,204,113,0.3);
}

/* ── YOUR ISSUES BUTTON ── */
.issues-open-btn {
  cursor: pointer;
  font-family: var(--font);
  text-align: left;
  background: rgba(245,166,35,0.10) !important;
  border: 1px solid rgba(245,166,35,0.30) !important;
  transition: all .2s;
}
.issues-open-btn:hover {
  background: rgba(245,166,35,0.18) !important;
  border-color: rgba(245,166,35,0.5) !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(245,166,35,0.15);
}
.issues-open-btn .sp-val { color: var(--gold) !important; }

/* ── Issues Modal Box ── */
.issues-modal-box {
  background: var(--dark2);
  border: 1px solid rgba(245,166,35,0.2);
  border-radius: 20px;
  width: 94%;
  max-width: 660px;
  max-height: 82vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 30px 80px rgba(0,0,0,0.6);
  overflow: hidden;
  animation: fadeUp .3s ease both;
  position: relative;
}
.issues-modal-box::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--gold), var(--blue), var(--gold));
  background-size: 200% 100%;
  animation: shimmer 3s linear infinite;
  border-radius: 20px 20px 0 0;
}
.im-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 22px 24px 18px;
  border-bottom: 1px solid var(--border);
  background: var(--dark3);
  flex-shrink: 0;
}
.im-title {
  font-size: 15px; font-weight: 800; color: #fff;
  display: flex; align-items: center; gap: 9px; letter-spacing: -.2px;
}
.im-count {
  background: rgba(245,166,35,0.15); border: 1px solid rgba(245,166,35,0.3);
  color: var(--gold); font-size: 10px; font-weight: 800;
  padding: 2px 9px; border-radius: 100px;
}
.im-close {
  width: 34px; height: 34px; border-radius: 50%;
  background: rgba(255,255,255,0.06); border: 1px solid var(--border);
  color: var(--muted); font-size: 13px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.im-close:hover { background: rgba(255,107,107,0.15); border-color: rgba(255,107,107,0.3); color: var(--red); }
.im-body {
  overflow-y: auto; padding: 16px 20px 20px;
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.im-body::-webkit-scrollbar { width: 5px; }
.im-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

.im-empty {
  text-align: center; padding: 52px 20px; color: var(--muted);
}
.im-empty i { font-size: 42px; color: rgba(46,204,113,0.25); display: block; margin-bottom: 14px; }
.im-empty-title { font-size: 16px; font-weight: 700; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
.im-empty-sub { font-size: 12px; }

.iss-card {
  background: var(--dark3); border: 1px solid var(--border);
  border-radius: 12px; margin-bottom: 10px; overflow: hidden;
  transition: border-color .2s;
}
.iss-card:hover { border-color: rgba(255,255,255,0.12); }
.iss-card-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; cursor: pointer; gap: 12px;
  user-select: none;
}
.iss-left { flex: 1; }
.iss-subject { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 4px; line-height: 1.4; }
.iss-meta { font-size: 10px; color: var(--muted); display: flex; gap: 10px; flex-wrap: wrap; }
.iss-meta i { font-size: 9px; }
.iss-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.iss-chevron { font-size: 11px; color: var(--muted); transition: transform .25s; }
.iss-chevron.open { transform: rotate(180deg); }

.iss-status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 100px;
  font-size: 9px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
  white-space: nowrap;
}
.iss-pending    { background: rgba(255,193,7,0.1);    color: #ffc107;      border: 1px solid rgba(255,193,7,0.25); }
.iss-inprogress { background: rgba(26,140,255,0.12);  color: var(--blue);  border: 1px solid rgba(26,140,255,0.3); }
.iss-resolved   { background: rgba(46,204,113,0.12);  color: var(--green); border: 1px solid rgba(46,204,113,0.3); }
.iss-closed     { background: rgba(107,114,128,0.12); color: #9ca3af;      border: 1px solid rgba(107,114,128,0.25); }

.iss-card-body { display: none; border-top: 1px solid var(--border); padding: 14px 16px; }
.iss-card-body.open { display: block; }
.iss-section { margin-bottom: 14px; }
.iss-section:last-child { margin-bottom: 0; }
.iss-section-label {
  font-size: 10px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase;
  color: var(--muted); margin-bottom: 7px;
  display: flex; align-items: center; gap: 6px;
}
.iss-section-text {
  font-size: 12px; color: rgba(255,255,255,0.8); line-height: 1.7;
  background: var(--dark4); border: 1px solid var(--border);
  border-radius: 8px; padding: 10px 13px;
}
.iss-reply-section .iss-section-text {
  background: rgba(46,204,113,0.05);
  border-color: rgba(46,204,113,0.18);
}
.iss-replied-time {
  font-size: 10px; color: var(--muted); margin-top: 6px;
  display: flex; align-items: center; gap: 5px;
}
.iss-no-reply {
  font-size: 11px; color: var(--muted);
  display: flex; align-items: center; gap: 7px;
  padding: 8px 12px; border-radius: 8px;
  background: rgba(255,255,255,0.03);
  border: 1px dashed var(--border);
}
.iss-updated {
  font-size: 10px; color: rgba(255,255,255,0.3);
  margin-top: 12px; display: flex; align-items: center; gap: 5px;
  border-top: 1px solid var(--border); padding-top: 10px;
}

.section-title {
  font-size: 12px; font-weight: 800; letter-spacing: .12em;
  text-transform: uppercase; color: var(--muted);
  margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.section-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
  margin-bottom: 32px;
  animation: fadeUp .5s ease both .1s;
}
.info-card {
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: 12px; padding: 16px 18px;
  transition: border-color .2s, transform .2s;
}
.info-card:hover { border-color: rgba(255,255,255,0.14); transform: translateY(-1px); }
.info-card.full { grid-column: 1/-1; }
.ic-label {
  font-size: 10px; font-weight: 700; letter-spacing: .09em;
  text-transform: uppercase; color: var(--muted);
  margin-bottom: 6px; display: flex; align-items: center; gap: 5px;
}
.ic-label i { color: var(--blue); font-size: 10px; }
.ic-value { font-size: 14px; font-weight: 600; color: #fff; line-height: 1.5; }
.ic-value.mono { font-family: var(--mono); font-size: 12px; }

.filter-tabs {
  display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;
  animation: fadeUp .5s ease both .15s;
}
.ftab {
  padding: 7px 16px; border-radius: 100px;
  font-size: 12px; font-weight: 700; letter-spacing: .05em;
  cursor: pointer; border: 1px solid var(--border);
  background: var(--dark2); color: var(--muted);
  transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px;
}
.ftab:hover { border-color: rgba(255,255,255,0.18); color: #fff; }
.ftab.active { background: var(--dark4); border-color: rgba(26,140,255,0.4); color: #fff; }
.ftab .fc { font-size: 10px; background: rgba(255,255,255,0.1); padding: 1px 7px; border-radius: 100px; }

.booking-card {
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden;
  transition: border-color .25s, transform .2s, box-shadow .25s;
  margin-bottom: 16px;
  position: relative;
  animation: fadeUp .45s ease both;
}
.booking-card:hover {
  border-color: rgba(255,255,255,0.12);
  transform: translateY(-2px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.35);
}
.card-strip { height: 3px; width: 100%; }
.strip-pending   { background: linear-gradient(90deg, #94a3b8, #64748b); }
.strip-approved  { background: linear-gradient(90deg, #2ecc71, #1a8cff); }
.strip-cancelled { background: linear-gradient(90deg, #ff6b6b, #c0392b); }
.strip-rejected  { background: linear-gradient(90deg, #6b7280, #374151); }

.card-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px 14px; border-bottom: 1px solid var(--border);
  flex-wrap: wrap; gap: 10px;
}
.card-ref {
  font-family: var(--mono); font-size: 13px; font-weight: 700;
  color: var(--green); letter-spacing: .04em;
  display: flex; align-items: center; gap: 8px;
}
.card-ref i { color: var(--muted); font-size: 11px; }
.card-date { font-size: 11px; color: var(--muted); margin-top: 2px; }
.card-head-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.status-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 13px; border-radius: 100px;
  font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
}
.sb-pending   { background: rgba(148,163,184,0.12);  color: #94a3b8;     border: 1px solid rgba(148,163,184,0.3); }
.sb-approved  { background: rgba(46,204,113,0.12);  color: var(--green); border: 1px solid rgba(46,204,113,0.3); }
.sb-cancelled { background: rgba(255,107,107,0.1);  color: var(--red);   border: 1px solid rgba(255,107,107,0.28); }
.sb-rejected  { background: rgba(107,114,128,0.15); color: #9ca3af;     border: 1px solid rgba(107,114,128,0.3); }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.status-dot.blink { animation: blink 1.5s ease infinite; }

.trip-status-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 11px; border-radius: 100px;
  font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
}
.tsp-notstarted { background: rgba(148,163,184,0.1);  color: #94a3b8;     border: 1px solid rgba(148,163,184,0.25); }
.tsp-started    { background: rgba(26,140,255,0.12);  color: var(--blue);  border: 1px solid rgba(26,140,255,0.3); }
.tsp-completed  { background: rgba(46,204,113,0.12);  color: var(--green); border: 1px solid rgba(46,204,113,0.3); }

.pay-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 11px; border-radius: 100px;
  font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
}
.pay-badge.full    { background: rgba(46,204,113,0.1); color: var(--green); border: 1px solid rgba(46,204,113,0.28); }
.pay-badge.deposit { background: rgba(245,166,35,0.1); color: var(--gold);  border: 1px solid rgba(245,166,35,0.28); }
.pay-badge.pending { background: rgba(255,193,7,0.08); color: #ffc107;      border: 1px solid rgba(255,193,7,0.22); }

.card-body-grid {
  display: grid; grid-template-columns: auto 1fr auto;
  gap: 20px; padding: 18px 20px; align-items: center;
}
.car-thumb-wrap { flex-shrink: 0; }
.car-thumb {
  width: 120px; height: 72px; object-fit: contain;
  filter: drop-shadow(0 6px 18px rgba(0,0,0,0.5));
  display: block;
}
.car-thumb-placeholder {
  width: 120px; height: 72px;
  background: var(--dark3); border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; color: var(--muted);
}
.card-info { display: flex; flex-direction: column; gap: 5px; }
.car-name { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -.2px; }
.car-brand { font-size: 12px; color: var(--muted); margin-bottom: 4px; }
.date-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
.date-chip {
  display: flex; align-items: center; gap: 6px;
  background: var(--dark3); border: 1px solid var(--border);
  padding: 5px 12px; border-radius: 7px; font-size: 11px;
}
.date-chip i { font-size: 10px; }
.date-chip .dc-label { color: var(--muted); font-size: 9px; font-weight: 700; text-transform: uppercase; margin-right: 2px; }
.date-chip .dc-val { color: #fff; font-weight: 600; }

.driver-pill {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(26,140,255,0.1); border: 1px solid rgba(26,140,255,0.22);
  color: var(--blue); font-size: 10px; font-weight: 700;
  padding: 3px 10px; border-radius: 100px; margin-top: 8px;
}
.self-drive-pill {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(46,204,113,0.08); border: 1px solid rgba(46,204,113,0.22);
  color: var(--green); font-size: 10px; font-weight: 700;
  padding: 3px 10px; border-radius: 100px; margin-top: 8px;
}

.fare-block { min-width: 190px; }
.fare-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 11px; color: var(--muted); margin-bottom: 5px; }
.fare-row .fv { font-weight: 600; color: rgba(255,255,255,0.75); text-align: right; min-width: 72px; }
.fare-row .fv.gold  { color: var(--gold); }
.fare-row .fv.blue  { color: var(--blue); }
.fare-total-wrap { border-top: 1px solid var(--border); margin-top: 10px; padding-top: 8px; }
.fare-label { font-size: 10px; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .08em; text-align: right; }
.fare-total { font-family: var(--mono); font-size: 20px; font-weight: 700; color: var(--green); text-align: right; }
.fare-mode  { font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; text-align: right; }
.fare-mode span { color: var(--blue); }

.fare-pay-status { margin-top: 10px; padding: 8px 10px; border-radius: 8px; font-size: 11px; }
.fare-pay-status.full-paid { background: rgba(46,204,113,0.08); border: 1px solid rgba(46,204,113,0.22); }
.fare-pay-status.deposit-paid { background: rgba(245,166,35,0.07); border: 1px solid rgba(245,166,35,0.22); }
.fps-row { display: flex; justify-content: space-between; align-items: center; font-size: 11px; margin-bottom: 4px; }
.fps-row:last-child { margin-bottom: 0; }
.fps-label { color: var(--muted); display: flex; align-items: center; gap: 5px; }
.fps-value { font-weight: 700; }
.fps-value.green { color: var(--green); }
.fps-value.gold  { color: var(--gold); }
.fps-value.blue  { color: var(--blue); }

.trip-mini-bar {
  display: flex; align-items: center; gap: 0;
  padding: 10px 20px;
  border-top: 1px solid var(--border);
  background: var(--dark3);
}
.tmb-step { display: flex; align-items: center; gap: 6px; flex: 1; }
.tmb-dot {
  width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 9px;
}
.tmb-dot.done     { background: rgba(46,204,113,0.2); border: 1.5px solid rgba(46,204,113,0.5); color: var(--green); }
.tmb-dot.active   { background: rgba(26,140,255,0.2); border: 1.5px solid rgba(26,140,255,0.5); color: var(--blue); animation: blink 1.6s ease infinite; }
.tmb-dot.inactive { background: rgba(255,255,255,0.04); border: 1.5px solid rgba(255,255,255,0.1); color: var(--muted); }
.tmb-label { font-size: 9px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.tmb-label.done   { color: var(--green); }
.tmb-label.active { color: var(--blue); }
.tmb-label.inactive { color: var(--muted); }
.tmb-line { flex: 1; height: 1px; background: rgba(255,255,255,0.08); margin: 0 6px; }
.tmb-line.done { background: rgba(46,204,113,0.4); }

.card-foot {
  padding: 12px 20px;
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  gap: 10px; flex-wrap: wrap;
}
.foot-link {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 18px; border-radius: 7px;
  font-size: 12px; font-weight: 700;
  background: rgba(26,140,255,0.1); border: 1px solid rgba(26,140,255,0.25);
  color: var(--blue); text-decoration: none;
  transition: all .2s;
}
.foot-link:hover { background: rgba(26,140,255,0.2); color: #fff; }

.foot-pay-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 7px;
  font-size: 12px; font-weight: 700;
  background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.28);
  color: var(--green); text-decoration: none;
  transition: all .2s;
}
.foot-pay-btn:hover { background: rgba(46,204,113,0.2); color: #fff; }

.delete-wrap { position: relative; display: flex; align-items: center; gap: 8px; }
.btn-delete {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 7px;
  font-size: 12px; font-weight: 700;
  background: rgba(255,107,107,0.08); border: 1px solid rgba(255,107,107,0.2);
  color: var(--red); cursor: pointer;
  transition: all .2s;
}
.btn-delete:hover { background: rgba(255,107,107,0.22); border-color: rgba(255,107,107,0.5); }
.del-tooltip {
  position: absolute; bottom: calc(100% + 8px); right: 0;
  background: var(--dark4); border: 1px solid rgba(255,107,107,0.3);
  color: var(--red); font-size: 11px; font-weight: 600;
  padding: 6px 12px; border-radius: 7px; white-space: nowrap;
  pointer-events: none; opacity: 0; transform: translateY(4px);
  transition: all .2s; z-index: 10;
}
.btn-delete:hover + .del-tooltip { opacity: 1; transform: translateY(0); }

.empty-state {
  text-align: center; padding: 72px 32px;
  background: var(--dark2); border: 1px solid var(--border);
  border-radius: 16px;
}
.empty-state i { font-size: 48px; color: rgba(255,255,255,0.12); margin-bottom: 16px; display: block; }
.empty-state h4 { font-size: 18px; font-weight: 700; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
.empty-state p { font-size: 13px; color: var(--muted); }
.empty-state a {
  display: inline-flex; align-items: center; gap: 6px;
  margin-top: 18px; padding: 10px 22px;
  background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3);
  color: var(--green); border-radius: 8px; font-size: 13px; font-weight: 700;
  text-decoration: none; transition: all .2s;
}
.empty-state a:hover { background: rgba(46,204,113,0.2); }

.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.65);
  z-index: 5000; display: none; align-items: center; justify-content: center;
  backdrop-filter: blur(6px);
}
.modal-overlay.open { display: flex; }
.modal-box {
  background: var(--dark2); border: 1px solid rgba(255,107,107,0.2);
  border-radius: 18px; padding: 38px 34px;
  max-width: 420px; width: 90%; text-align: center;
  box-shadow: 0 30px 70px rgba(0,0,0,0.5);
}
.modal-icon {
  width: 68px; height: 68px; border-radius: 50%;
  background: rgba(255,107,107,0.1); border: 2px solid rgba(255,107,107,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; color: var(--red); margin: 0 auto 20px;
}
.modal-box h4 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px; }
.modal-box p { font-size: 13px; color: var(--muted); line-height: 1.7; margin-bottom: 6px; }
.modal-ref {
  font-family: var(--mono); font-size: 13px; color: var(--green);
  background: var(--dark3); border: 1px solid var(--border);
  padding: 7px 16px; border-radius: 7px; display: inline-block; margin-bottom: 26px;
}
.modal-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.mbtn { padding: 12px; border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; transition: all .2s; font-family: var(--font); }
.mbtn-cancel { background: var(--dark3); color: var(--text); border: 1px solid var(--border); }
.mbtn-cancel:hover { background: var(--dark4); }
.mbtn-delete { background: var(--red); color: #fff; }
.mbtn-delete:hover { background: #e55555; }

.toast-fixed {
  position: fixed; top: 90px; right: 24px; z-index: 9999;
  padding: 13px 20px; border-radius: 10px;
  display: flex; align-items: center; gap: 10px;
  font-size: 13px; font-weight: 600; font-family: var(--font);
  transform: translateX(130%);
  transition: transform .4s cubic-bezier(.34,1.56,.64,1);
  max-width: 340px; box-shadow: 0 8px 30px rgba(0,0,0,0.35);
}
.toast-fixed.show { transform: translateX(0); }
.toast-fixed.success { background: rgba(46,204,113,0.12); border: 1px solid rgba(46,204,113,0.35); color: var(--green); }
.toast-fixed.error   { background: rgba(255,107,107,0.12); border: 1px solid rgba(255,107,107,0.3); color: var(--red); }

@media(max-width:900px){
  .profile-hero { flex-direction: column; align-items: flex-start; padding: 28px; }
  .hero-stats { flex-direction: row; align-items: flex-start; flex-wrap: wrap; }
  .info-grid { grid-template-columns: 1fr 1fr; }
  .card-body-grid { grid-template-columns: 1fr; }
  .car-thumb { width: 100%; max-width: 200px; }
  .fare-block { text-align: left; }
  .fare-total, .fare-label, .fare-mode { text-align: left; }
}
@media(max-width:600px){
  .info-grid { grid-template-columns: 1fr; }
  .profile-hero { padding: 22px 20px; gap: 20px; }
}
</style>
</head>

<body class="profile-page">

<div class="bg-canvas"></div>
<div class="grid-lines"></div>
<div class="deco-bottom"></div>

<?php include("header.php"); ?>

<?php if($toast): ?>
<div class="toast-fixed <?= $toast['type'] ?>" id="profileToast">
  <i class="fas <?= $toast['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<?php endif; ?>

<div class="page-wrap">

  <!-- ── PROFILE HERO ── -->
  <div class="profile-hero">
    <div class="avatar-wrap">
      <?php if ($counts['Completed'] > 0): ?>
      <div class="avatar-completed-ring"></div>
      <div class="avatar-completed-pill">
        <i class="fas fa-flag-checkered" style="font-size:8px"></i>
        <?= $counts['Completed'] ?> Completed
      </div>
      <?php endif; ?>
      <img src="user_profile/<?= htmlspecialchars($user['photo'] ?? '') ?>"
           class="avatar-img"
           onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'"
           alt="Profile">
      <div class="avatar-badge"><i class="fas fa-check"></i></div>
    </div>

    <div class="hero-info">
      <div class="hero-name"><?= htmlspecialchars($user['uname'] ?? 'User') ?></div>
      <div class="hero-email">
        <i class="fas fa-envelope"></i><?= htmlspecialchars($user['email'] ?? '') ?>
      </div>
      <div class="hero-meta">
        <?php if(!empty($user['mobno'])): ?>
        <div class="meta-item">
          <span class="meta-label">Phone</span>
          <span class="meta-value"><?= htmlspecialchars($user['mobno']) ?></span>
        </div>
        <?php endif; ?>
        <div class="meta-item">
          <span class="meta-label">Member Since</span>
          <span class="meta-value"><?= $member_since ?></span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Total Spent</span>
          <span class="meta-value green">&#8377;<?= number_format($total_spent, 0) ?></span>
        </div>
      </div>
    </div>

    <div class="hero-stats"><br><br>
      <div class="stat-pill">
        <i class="fas fa-calendar-check" style="color:var(--green)"></i>
        <div><div class="sp-val"><?= $counts['Approved'] ?></div><div class="sp-lbl">Approved</div></div>
      </div>
      <div class="stat-pill">
        <i class="fas fa-hourglass-half" style="color:#94a3b8"></i>
        <div><div class="sp-val"><?= $counts['Pending'] ?></div><div class="sp-lbl">Not Approved</div></div>
      </div>
      <?php if ($counts['Completed'] > 0): ?>
      <div class="stat-pill completed-pill">
        <i class="fas fa-flag-checkered" style="color:var(--green)"></i>
        <div><div class="sp-val"><?= $counts['Completed'] ?></div><div class="sp-lbl">Trips Done</div></div>
      </div>
      <?php else: ?>
      <div class="stat-pill">
        <i class="fas fa-list" style="color:var(--blue)"></i>
        <div><div class="sp-val"><?= $counts['all'] ?></div><div class="sp-lbl">Total Trips</div></div>
      </div>
      <?php endif; ?>

      <!-- ── YOUR ISSUES BUTTON ── -->
      <button class="stat-pill issues-open-btn" onclick="openIssuesModal()">
        <i class="fas fa-headset" style="color:var(--gold)"></i>
        <div><div class="sp-val"><?= count($user_issues) ?></div><div class="sp-lbl">Your Issues</div></div>
      </button>
    </div>
  </div>

  <!-- ── PERSONAL INFO ── -->
  <div class="section-title"><i class="fas fa-id-card" style="color:var(--blue)"></i> Personal Information</div>
  <div class="info-grid">
    <div class="info-card">
      <div class="ic-label"><i class="fas fa-user"></i> Full Name</div>
      <div class="ic-value"><?= htmlspecialchars($user['uname'] ?? '—') ?></div>
    </div>
    <div class="info-card">
      <div class="ic-label"><i class="fas fa-envelope"></i> Email</div>
      <div class="ic-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
    </div>
    <div class="info-card">
      <div class="ic-label"><i class="fas fa-phone"></i> Mobile</div>
      <div class="ic-value"><?= htmlspecialchars($user['mobno'] ?? '—') ?></div>
    </div>
    <?php if(!empty($user['pan_aadhar_no'])): ?>
    <div class="info-card">
      <div class="ic-label"><i class="fas fa-id-badge"></i> PAN / Aadhaar</div>
      <div class="ic-value mono"><?= htmlspecialchars($user['pan_aadhar_no']) ?></div>
    </div>
    <?php endif; ?>
    <?php if(!empty($user['address'])): ?>
    <div class="info-card full">
      <div class="ic-label"><i class="fas fa-map-marker-alt"></i> Address</div>
      <div class="ic-value"><?= htmlspecialchars($user['address']) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── BOOKINGS ── -->
  <div class="section-title"><i class="fas fa-calendar-alt" style="color:var(--green)"></i> My Bookings</div>

  <div class="filter-tabs">
    <a href="?filter=all" class="ftab <?= (!isset($_GET['filter']) || $_GET['filter']==='all') ? 'active':'' ?>">
      <i class="fas fa-list"></i> All <span class="fc"><?= $counts['all'] ?></span>
    </a>
    <a href="?filter=Pending" class="ftab <?= ($_GET['filter']??'') === 'Pending' ? 'active':'' ?>" style="<?= $counts['Pending'] > 0 ? 'color:#94a3b8;border-color:rgba(148,163,184,0.3)' : '' ?>">
      <i class="fas fa-hourglass-half"></i> Not Approved <span class="fc"><?= $counts['Pending'] ?></span>
    </a>
    <a href="?filter=Approved" class="ftab <?= ($_GET['filter']??'') === 'Approved' ? 'active':'' ?>" style="<?= $counts['Approved'] > 0 ? 'color:var(--green);border-color:rgba(46,204,113,0.3)' : '' ?>">
      <i class="fas fa-check-circle"></i> Approved <span class="fc"><?= $counts['Approved'] ?></span>
    </a>
    <a href="?filter=Completed" class="ftab <?= ($_GET['filter']??'') === 'Completed' ? 'active':'' ?>" style="<?= $counts['Completed'] > 0 ? 'color:var(--green);border-color:rgba(46,204,113,0.35)' : '' ?>">
      <i class="fas fa-flag-checkered"></i> Completed <span class="fc"><?= $counts['Completed'] ?></span>
    </a>
    <a href="?filter=Cancelled" class="ftab <?= ($_GET['filter']??'') === 'Cancelled' ? 'active':'' ?>" style="<?= $counts['Cancelled'] > 0 ? 'color:var(--red);border-color:rgba(255,107,107,0.3)' : '' ?>">
      <i class="fas fa-times-circle"></i> Cancelled <span class="fc"><?= $counts['Cancelled'] ?></span>
    </a>
    <a href="?filter=Rejected" class="ftab <?= ($_GET['filter']??'') === 'Rejected' ? 'active':'' ?>" style="<?= $counts['Rejected'] > 0 ? 'color:#9ca3af;border-color:rgba(107,114,128,0.3)' : '' ?>">
      <i class="fas fa-ban"></i> Rejected <span class="fc"><?= $counts['Rejected'] ?></span>
    </a>
  </div>

  <?php
  $filter = isset($_GET['filter']) && $_GET['filter'] !== 'all' ? $_GET['filter'] : null;
  if ($filter === 'Completed') {
      $shown = array_filter($bookings, fn($b) =>
          $b['booking_status'] === 'Approved' && ($b['trip_status'] ?? '') === 'Completed'
      );
  } elseif ($filter) {
      $shown = array_filter($bookings, fn($b) => $b['booking_status'] === $filter);
  } else {
      $shown = $bookings;
  }
  $shown = array_values($shown);
  ?>

  <?php if(empty($shown)): ?>
  <div class="empty-state">
    <i class="fas fa-calendar-times"></i>
    <h4>No bookings found</h4>
    <p><?= $filter === 'Pending' ? "You have no bookings awaiting approval." : ($filter ? "You have no $filter bookings." : "You haven't made any bookings yet.") ?></p>
    <a href="car.php"><i class="fas fa-car"></i> Browse Cars</a>
  </div>

  <?php else: ?>
  <?php foreach($shown as $idx => $bk):
    $bid     = intval($bk['booking_id']);
    $bref    = 'CB-'.str_pad($bid, 6, '0', STR_PAD_LEFT);
    $bstatus = $bk['booking_status'];
    $trip_st = $bk['trip_status'] ?? 'Not Started';
    $sclass  = strtolower($bstatus);

    $grand_total      = floatval($bk['total_amount']    ?? 0);
    $base_rent        = floatval($bk['base_amount']     ?? 0);
    $driver_charge    = floatval($bk['driver_amount']   ?? 0);
    $security_deposit = floatval($bk['security_deposit']?? 0);
    $gst = max(0, $grand_total - $base_rent - $driver_charge - $security_deposit);

    $trip_distance = getDistance($bk['pickup_lat'], $bk['pickup_lng'], $bk['drop_lat'], $bk['drop_lng']);
    $pricing_mode  = ($trip_distance < 50) ? 'Distance Based' : 'Rental Based';

    $pickup_dt = new DateTime($bk['pickup_datetime']);
    $return_dt = (!empty($bk['actual_return_datetime']) && $bk['actual_return_datetime'] !== '0000-00-00 00:00:00')
                 ? new DateTime($bk['actual_return_datetime']) : null;
    $dur = '1d';
    if($return_dt){
      $diff = $pickup_dt->diff($return_dt);
      $dp = [];
      if($diff->days > 0) $dp[] = $diff->days.'d';
      if($diff->h > 0)    $dp[] = $diff->h.'h';
      $dur = $dp ? implode(' ',$dp) : '< 1h';
    }

    $car_img = !empty($bk['primary_image'])
               ? '../Admin/pages/images/car_images/'.htmlspecialchars($bk['primary_image'])
               : '';

    $bk_payments      = $payments_by_booking[$bid] ?? [];
    $bk_total_paid    = 0;
    $bk_is_fully_paid = false;
    $bk_is_dep_paid   = false;
    $bk_dep_amount    = 0;
    $bk_full_amount   = 0;
    foreach ($bk_payments as $p) {
        $bk_total_paid += floatval($p['paid_amount']);
        if (($p['payment_status'] ?? 0) == 2) {
            $bk_is_fully_paid = true;
            $bk_full_amount = $bk_total_paid;
        }
        if (($p['payment_type'] ?? '') === 'deposit') {
            $bk_is_dep_paid = true;
            $bk_dep_amount = floatval($p['paid_amount']);
        }
    }
    if ($bk_total_paid > 0 && max(0, $grand_total - $bk_total_paid) <= 0) {
        $bk_is_fully_paid = true;
    }
    $bk_remaining = max(0, $grand_total - $bk_total_paid);

    $can_delete = ($bstatus === 'Cancelled' || $bstatus === 'Rejected');

    $status_icons = ['Pending'=>'fa-hourglass-half','Approved'=>'fa-check-circle','Cancelled'=>'fa-times-circle','Rejected'=>'fa-ban'];
    $sicon = $status_icons[$bstatus] ?? 'fa-circle';
    $badge_cls = 'sb-'.strtolower($bstatus);
    $status_label = ($bstatus === 'Pending') ? 'Not Approved' : $bstatus;

    if ($bk_is_fully_paid) {
        $pay_badge_cls = 'full'; $pay_badge_icon = 'fa-check-circle'; $pay_badge_text = 'Fully Paid';
    } elseif ($bk_is_dep_paid) {
        $pay_badge_cls = 'deposit'; $pay_badge_icon = 'fa-shield-alt'; $pay_badge_text = 'Deposit Paid';
    } elseif ($bstatus === 'Approved') {
        $pay_badge_cls = 'pending'; $pay_badge_icon = 'fa-clock'; $pay_badge_text = 'Pay Now';
    } else {
        $pay_badge_cls = ''; $pay_badge_icon = ''; $pay_badge_text = '';
    }

    $tsp_class = match($trip_st) {
        'Started'   => 'tsp-started',
        'Completed' => 'tsp-completed',
        default     => 'tsp-notstarted',
    };
    $tsp_icon = match($trip_st) {
        'Started'   => 'fa-play-circle',
        'Completed' => 'fa-flag-checkered',
        default     => 'fa-minus-circle',
    };
    $tsp_label = match($trip_st) {
        'Started'   => 'In Progress',
        'Completed' => 'Completed',
        default     => 'Not Started',
    };

    $tmb_step = match($trip_st) { 'Started' => 2, 'Completed' => 3, default => 1 };
  ?>

  <div class="booking-card" style="animation-delay:<?= ($idx * 0.05) ?>s">
    <div class="card-strip strip-<?= $sclass ?>"></div>

    <div class="card-head">
      <div>
        <div class="card-ref"><i class="fas fa-hashtag"></i><?= $bref ?></div>
        <div class="card-date"><i class="fas fa-clock" style="font-size:9px;color:var(--muted)"></i> Booked <?= date('d M Y, h:i A', strtotime($bk['created_at'])) ?></div>
      </div>
      <div class="card-head-right">
        <?php if ($bstatus === 'Approved'): ?>
        <span class="trip-status-pill <?= $tsp_class ?>">
          <i class="fas <?= $tsp_icon ?>" style="font-size:9px"></i>
          <?= $tsp_label ?>
        </span>
        <?php endif; ?>
        <?php if ($pay_badge_text): ?>
        <span class="pay-badge <?= $pay_badge_cls ?>">
          <i class="fas <?= $pay_badge_icon ?>"></i>
          <?= $pay_badge_text ?>
        </span>
        <?php endif; ?>
        <span class="status-badge <?= $badge_cls ?>">
          <span class="status-dot <?= $bstatus==='Pending'?'blink':'' ?>"></span>
          <i class="fas <?= $sicon ?>"></i>
          <?= $status_label ?>
        </span>
      </div>
    </div>

    <div class="card-body-grid">
      <div class="car-thumb-wrap">
        <?php if($car_img): ?>
        <img src="<?= $car_img ?>"
             class="car-thumb" loading="lazy"
             onerror="this.outerHTML='<div class=\'car-thumb-placeholder\'><i class=\'fas fa-car\'></i></div>'"
             alt="<?= htmlspecialchars($bk['car_display_name'] ?? '') ?>">
        <?php else: ?>
        <div class="car-thumb-placeholder"><i class="fas fa-car"></i></div>
        <?php endif; ?>
      </div>

      <div class="card-info">
        <div class="car-name"><?= htmlspecialchars($bk['car_display_name'] ?? '—') ?></div>
        <div class="car-brand">
          <?= htmlspecialchars($bk['brand_name'] ?? '') ?>
          <?= !empty($bk['car_number_plate']) ? ' &middot; <span style="font-family:var(--mono);font-size:10px">'.htmlspecialchars($bk['car_number_plate']).'</span>' : '' ?>
        </div>
        <div class="date-row">
          <div class="date-chip">
            <i class="fas fa-arrow-right" style="color:var(--green)"></i>
            <span class="dc-label">Pick</span>
            <span class="dc-val"><?= date('d M Y', strtotime($bk['pickup_datetime'])) ?></span>
          </div>
          <?php if($return_dt): ?>
          <div class="date-chip">
            <i class="fas fa-arrow-left" style="color:var(--blue)"></i>
            <span class="dc-label">Return</span>
            <span class="dc-val"><?= date('d M Y', strtotime($bk['actual_return_datetime'])) ?></span>
          </div>
          <?php endif; ?>
          <div class="date-chip" style="border-color:rgba(245,166,35,0.2)">
            <i class="fas fa-clock" style="color:var(--gold)"></i>
            <span class="dc-val" style="color:var(--gold)"><?= $dur ?></span>
          </div>
        </div>
        <?php if(!empty($bk['driver_name'])): ?>
        <span class="driver-pill"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($bk['driver_name']) ?></span>
        <?php else: ?>
        <span class="self-drive-pill"><i class="fas fa-user-circle"></i> Self Drive</span>
        <?php endif; ?>
      </div>

      <div class="fare-block">
        <div class="fare-mode"><?= $pricing_mode ?> &nbsp;<span><?= $trip_distance ?> KM</span></div>
        <div class="fare-row"><span>Base Rent</span><span class="fv">&#8377;<?= number_format($base_rent,2) ?></span></div>
        <?php if($driver_charge > 0): ?>
        <div class="fare-row"><span>Driver (10%)</span><span class="fv blue">&#8377;<?= number_format($driver_charge,2) ?></span></div>
        <?php endif; ?>
        <div class="fare-row"><span>GST (5%)</span><span class="fv">&#8377;<?= number_format($gst,2) ?></span></div>
        <?php if($security_deposit > 0): ?>
        <div class="fare-row"><span>Security Dep.</span><span class="fv gold">&#8377;<?= number_format($security_deposit,2) ?></span></div>
        <?php endif; ?>
        <div class="fare-total-wrap">
          <div class="fare-label">Grand Total</div>
          <div class="fare-total">&#8377;<?= number_format($grand_total,2) ?></div>
        </div>
        <?php if ($bk_is_fully_paid): ?>
        <div class="fare-pay-status full-paid">
          <div class="fps-row"><span class="fps-label"><i class="fas fa-check-circle" style="color:var(--green)"></i> Paid</span><span class="fps-value green">&#8377;<?= number_format($bk_full_amount,2) ?></span></div>
          <div class="fps-row"><span class="fps-label">Due</span><span class="fps-value green">&#8377;0.00</span></div>
        </div>
        <?php elseif ($bk_is_dep_paid): ?>
        <div class="fare-pay-status deposit-paid">
          <div class="fps-row"><span class="fps-label"><i class="fas fa-shield-alt" style="color:var(--gold)"></i> Deposit</span><span class="fps-value gold">&#8377;<?= number_format($bk_dep_amount,2) ?></span></div>
          <div class="fps-row"><span class="fps-label"><i class="fas fa-clock" style="color:var(--blue);font-size:9px"></i> Rental Due</span><span class="fps-value blue">&#8377;<?= number_format($bk_remaining,2) ?></span></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($bstatus === 'Approved'): ?>
    <div class="trip-mini-bar">
      <div class="tmb-step">
        <div class="tmb-dot done"><i class="fas fa-check" style="font-size:8px"></i></div>
        <div class="tmb-label done">Confirmed</div>
      </div>
      <div class="tmb-line <?= $tmb_step >= 2 ? 'done' : '' ?>"></div>
      <div class="tmb-step">
        <?php if ($tmb_step === 1): ?>
        <div class="tmb-dot inactive"><i class="fas fa-play" style="font-size:7px"></i></div>
        <div class="tmb-label inactive">Not Started</div>
        <?php elseif ($tmb_step === 2): ?>
        <div class="tmb-dot active"><i class="fas fa-play" style="font-size:7px"></i></div>
        <div class="tmb-label active">In Progress</div>
        <?php else: ?>
        <div class="tmb-dot done"><i class="fas fa-check" style="font-size:8px"></i></div>
        <div class="tmb-label done">Started</div>
        <?php endif; ?>
      </div>
      <div class="tmb-line <?= $tmb_step === 3 ? 'done' : '' ?>"></div>
      <div class="tmb-step">
        <?php if ($tmb_step < 3): ?>
        <div class="tmb-dot inactive"><i class="fas fa-flag-checkered" style="font-size:7px"></i></div>
        <div class="tmb-label inactive">Pending</div>
        <?php else: ?>
        <div class="tmb-dot done"><i class="fas fa-flag-checkered" style="font-size:7px"></i></div>
        <div class="tmb-label done">Completed</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card-foot">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <?php if ($bk_is_fully_paid): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.28);color:var(--green)">
            <i class="fas fa-check-circle"></i> Full Payment Done &middot; &#8377;<?= number_format($bk_full_amount,2) ?>
          </span>
        <?php elseif ($bk_is_dep_paid): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;background:rgba(245,166,35,.08);border:1px solid rgba(245,166,35,.28);color:var(--gold)">
            <i class="fas fa-shield-alt"></i> Deposit Paid &middot; &#8377;<?= number_format($bk_dep_amount,2) ?>
          </span>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;background:rgba(26,140,255,.08);border:1px solid rgba(26,140,255,.22);color:var(--blue)">
            <i class="fas fa-clock"></i> &#8377;<?= number_format($bk_remaining,2) ?> due on return
          </span>
        <?php elseif ($bstatus === 'Approved'): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;background:rgba(255,193,7,.08);border:1px solid rgba(255,193,7,.25);color:#ffc107">
            <i class="fas fa-clock"></i> Payment Pending
          </span>
        <?php elseif ($bstatus === 'Pending'): ?>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:7px;font-size:11px;font-weight:700;background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.22);color:#94a3b8">
            <i class="fas fa-hourglass-half"></i> Awaiting Admin Approval
          </span>
        <?php endif; ?>
      </div>

      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <?php if ($bstatus === 'Approved' && !$bk_is_fully_paid): ?>
        <a href="booking_details.php?bid=<?= $bid ?>" class="foot-pay-btn">
          <i class="fas fa-lock"></i> <?= $bk_is_dep_paid ? 'Pay Remaining' : 'Pay Now' ?>
        </a>
        <?php endif; ?>

        <a href="booking_details.php?bid=<?= $bid ?>" class="foot-link">
          <i class="fas fa-eye"></i> View Details
        </a>

        <?php if($can_delete): ?>
        <div class="delete-wrap">
          <button class="btn-delete" onclick="openDeleteModal(<?= $bid ?>, '<?= $bref ?>')">
            <i class="fas fa-trash-alt"></i> Clear
          </button>
          <div class="del-tooltip">Remove this booking from history</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<!-- ── Delete Modal ── -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
    <h4>Clear This Booking?</h4>
    <p>This will permanently remove the booking from your history. This action cannot be undone.</p>
    <div class="modal-ref" id="modalRef">—</div>
    <div class="modal-btns">
      <button class="mbtn mbtn-cancel" onclick="closeDeleteModal()">
        <i class="fas fa-arrow-left"></i> Go Back
      </button>
      <form method="POST" action="" style="margin:0">
        <input type="hidden" name="booking_id" id="deleteBid" value="">
        <button type="submit" name="delete_booking" class="mbtn mbtn-delete" style="width:100%">
          <i class="fas fa-trash-alt"></i> Yes, Remove
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── Issues Modal ── -->
<div class="modal-overlay" id="issuesModal">
  <div class="issues-modal-box">
    <div class="im-header">
      <div class="im-title">
        <i class="fas fa-headset" style="color:var(--gold)"></i>
        Your Support Issues
        <span class="im-count"><?= count($user_issues) ?></span>
      </div>
      <button class="im-close" onclick="closeIssuesModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="im-body">
      <?php if(empty($user_issues)): ?>
      <div class="im-empty">
        <i class="fas fa-check-circle"></i>
        <div class="im-empty-title">No Issues Found</div>
        <div class="im-empty-sub">You haven't submitted any support requests yet.</div>
      </div>
      <?php else: ?>
      <?php foreach($user_issues as $iss):
        $ist = intval($iss['status']);
        $status_map = [0=>'Pending', 1=>'In Progress', 2=>'Resolved', 3=>'Closed'];
        $status_cls = ['0'=>'iss-pending','1'=>'iss-inprogress','2'=>'iss-resolved','3'=>'iss-closed'];
        $status_icn = ['0'=>'fa-clock','1'=>'fa-spinner','2'=>'fa-check-circle','3'=>'fa-lock'];
        $st_label   = $status_map[$ist] ?? 'Unknown';
        $st_cls     = $status_cls[(string)$ist] ?? 'iss-pending';
        $st_icn     = $status_icn[(string)$ist] ?? 'fa-clock';
        $cid        = 'issue-'.$iss['contact_id'];
      ?>
      <div class="iss-card">
        <div class="iss-card-head" onclick="toggleIssue('<?= $cid ?>')">
          <div class="iss-left">
            <div class="iss-subject"><?= htmlspecialchars($iss['subject']) ?></div>
            <div class="iss-meta">
              <span><i class="fas fa-calendar-alt"></i> <?= date('d M Y, h:i A', strtotime($iss['created_at'])) ?></span>
            </div>
          </div>
          <div class="iss-right">
            <span class="iss-status-badge <?= $st_cls ?>">
              <i class="fas <?= $st_icn ?>"></i> <?= $st_label ?>
            </span>
            <i class="fas fa-chevron-down iss-chevron" id="chev-<?= $cid ?>"></i>
          </div>
        </div>
        <div class="iss-card-body" id="<?= $cid ?>">
          <div class="iss-section">
            <div class="iss-section-label"><i class="fas fa-comment-dots" style="color:var(--blue)"></i> Your Message</div>
            <div class="iss-section-text"><?= nl2br(htmlspecialchars($iss['message'])) ?></div>
          </div>
          <?php if(!empty($iss['admin_reply'])): ?>
          <div class="iss-section iss-reply-section">
            <div class="iss-section-label"><i class="fas fa-reply" style="color:var(--green)"></i> Admin Reply</div>
            <div class="iss-section-text"><?= nl2br(htmlspecialchars($iss['admin_reply'])) ?></div>
            <?php if(!empty($iss['replied_at']) && $iss['replied_at'] !== '0000-00-00 00:00:00'): ?>
            <div class="iss-replied-time"><i class="fas fa-clock"></i> Replied <?= date('d M Y, h:i A', strtotime($iss['replied_at'])) ?></div>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="iss-section iss-no-reply">
            <i class="fas fa-hourglass-half"></i> Awaiting admin response…
          </div>
          <?php endif; ?>
          <?php if(!empty($iss['updated_at'])): ?>
          <div class="iss-updated"><i class="fas fa-sync-alt"></i> Last updated <?= date('d M Y, h:i A', strtotime($iss['updated_at'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.easing.1.3.js"></script>
<script src="js/jquery.waypoints.min.js"></script>
<script src="js/jquery.stellar.min.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="js/aos.js"></script>
<script src="js/jquery.animateNumber.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="js/jquery.timepicker.min.js"></script>
<script src="js/main.js"></script>

<script>
(function(){
  var nav = document.getElementById('ftco-navbar');
  if(!nav) return;
  function onScroll(){ nav.classList.toggle('scrolled', window.scrollY > 50); }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

function openDeleteModal(bid, bref){
  document.getElementById('deleteBid').value = bid;
  document.getElementById('modalRef').textContent = bref;
  document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal(){
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e){
  if(e.target === this) closeDeleteModal();
});

function openIssuesModal(){
  document.getElementById('issuesModal').classList.add('open');
}
function closeIssuesModal(){
  document.getElementById('issuesModal').classList.remove('open');
}
document.getElementById('issuesModal').addEventListener('click', function(e){
  if(e.target === this) closeIssuesModal();
});

function toggleIssue(id){
  var body = document.getElementById(id);
  var chev = document.getElementById('chev-' + id);
  var isOpen = body.classList.contains('open');
  body.classList.toggle('open', !isOpen);
  chev.classList.toggle('open', !isOpen);
}

<?php if($toast): ?>
(function(){
  var t = document.getElementById('profileToast');
  if(!t) return;
  setTimeout(function(){ t.classList.add('show'); }, 100);
  setTimeout(function(){ t.classList.remove('show'); }, 4000);
})();
<?php endif; ?>
</script>
</body>
</html>