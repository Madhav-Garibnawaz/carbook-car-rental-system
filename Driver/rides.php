<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('driver_session');
    session_start();
}
include("connect.php");

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['driver_id'])) {
    header("location: register.php"); exit;
}
$driver_id = (int)$_SESSION['driver_id'];

// ── Driver info ───────────────────────────────────────────────────────────────
$dQ     = mysqli_query($con, "SELECT * FROM driver_master WHERE driver_id='$driver_id'");
$driver = mysqli_fetch_assoc($dQ) ?: [];

if (empty($driver)) {
    session_destroy();
    header("location: register.php"); exit;
}

// ── Fetch all rides (now includes trip_status) ────────────────────────────────
$ridesQ = mysqli_query($con,
    "SELECT
        bm.booking_id, bm.pickup_datetime, bm.actual_return_datetime,
        bm.created_at,
        bd.booking_status, bd.trip_status,
        bd.base_amount, bd.driver_amount, bd.total_amount,
        c.car_display_name, c.primary_image, c.car_number_plate,
        c.seating_capacity, c.gear_type, c.fuel_type,
        br.brand_name,
        u.uname, u.email AS cust_email, u.mobno, u.photo AS user_photo
     FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     LEFT JOIN car_master      c  ON c.car_id      = bm.car_id
     LEFT JOIN brand_master    br ON br.brand_id   = c.brand_id
     LEFT JOIN users_master    u  ON u.ui          = bm.ui
     WHERE bm.driver_id = $driver_id
       AND bd.booking_status IN ('Pending', 'Approved')
     ORDER BY bm.pickup_datetime DESC"
);
$all_rides = [];
if ($ridesQ) {
    while ($r = mysqli_fetch_assoc($ridesQ)) $all_rides[] = $r;
}

// ── Fetch payments for all rides ──────────────────────────────────────────────
$all_bid_list = implode(',', array_map(fn($r) => intval($r['booking_id']), $all_rides) ?: [0]);
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

// ── Helper: calculate payment state for a ride ────────────────────────────────
function calcPaymentState(array $payments, float $total): array {
    $total_paid    = 0;
    $is_fully_paid = false;
    $is_dep_paid   = false;
    $dep_amount    = 0;

    foreach ($payments as $p) {
        $total_paid += floatval($p['paid_amount']);
        if (($p['payment_type'] ?? '') === 'deposit') {
            $is_dep_paid = true;
            $dep_amount  = floatval($p['paid_amount']);
        }
        if (($p['payment_status'] ?? 0) == 2) $is_fully_paid = true;
        if (($p['payment_type'] ?? '') === 'rental') $is_fully_paid = true;
    }
    $remaining = max(0, $total - $total_paid);
    if ($remaining <= 0 && $total_paid > 0) $is_fully_paid = true;

    return [
        'is_fully_paid' => $is_fully_paid,
        'is_dep_paid'   => $is_dep_paid,
        'total_paid'    => $total_paid,
        'dep_amount'    => $dep_amount,
        'remaining'     => $remaining,
        // driver can start ride only if deposit or full payment is done
        'payment_ok'    => ($is_fully_paid || $is_dep_paid),
    ];
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$stats = ['total' => count($all_rides), 'Approved' => 0, 'Pending' => 0];
$total_earned = 0;
foreach ($all_rides as $r) {
    $s = $r['booking_status'];
    if (isset($stats[$s])) $stats[$s]++;

    // Only count earning when trip is fully completed AND full payment received
    if ($r['trip_status'] === 'Completed') {
        $bk_payments = $payments_by_booking[intval($r['booking_id'])] ?? [];
        $ps = calcPaymentState($bk_payments, floatval($r['total_amount'] ?? 0));
        if ($ps['is_fully_paid']) {
            $total_earned += floatval($r['driver_amount'] ?? 0);
        }
    }
}

// ── Filter & search ───────────────────────────────────────────────────────────
$tab    = $_GET['tab'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$filtered = $all_rides;
if ($tab !== 'all') {
    $filtered = array_values(array_filter($filtered, fn($r) => strtolower($r['booking_status']) === strtolower($tab)));
}
if ($search) {
    $sl = strtolower($search);
    $filtered = array_values(array_filter($filtered, fn($r) =>
        str_contains(strtolower($r['car_display_name'] ?? ''), $sl) ||
        str_contains(strtolower($r['uname'] ?? ''), $sl) ||
        str_contains(strtolower('CB-' . str_pad($r['booking_id'], 6, '0', STR_PAD_LEFT)), $sl)
    ));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function rideDur($p, $ret) {
    if (empty($ret) || $ret === '0000-00-00 00:00:00') return null;
    $diff = (new DateTime($p))->diff(new DateTime($ret));
    $out  = [];
    if ($diff->days) $out[] = $diff->days . 'd';
    if ($diff->h)    $out[] = $diff->h . 'h';
    return $out ? implode(' ', $out) : '<1h';
}
function statusCfg($s) {
    return match($s) {
        'Approved'  => ['bg' => 'bg-emerald-50',  'text' => 'text-emerald-700',  'dot' => 'bg-emerald-500',  'border' => 'border-emerald-200', 'bar' => '#10b981', 'icon' => 'fa-check-circle'],
        'Pending'   => ['bg' => 'bg-amber-50',    'text' => 'text-amber-700',    'dot' => 'bg-amber-400',    'border' => 'border-amber-200',   'bar' => '#f59e0b', 'icon' => 'fa-clock'],
        default     => ['bg' => 'bg-blue-50',     'text' => 'text-blue-700',     'dot' => 'bg-blue-400',     'border' => 'border-blue-200',    'bar' => '#3b82f6', 'icon' => 'fa-circle'],
    };
}

// ── Trip status config helper ─────────────────────────────────────────────────
function tripStatusCfg(string $ts): array {
    return match($ts) {
        'Started'   => [
            'bg'     => 'bg-blue-50',   'text' => 'text-blue-700',
            'border' => 'border-blue-200', 'icon' => 'fa-play-circle',
            'label'  => 'Trip Started',  'dot'  => 'bg-blue-500',
        ],
        'Completed' => [
            'bg'     => 'bg-emerald-50', 'text' => 'text-emerald-700',
            'border' => 'border-emerald-200', 'icon' => 'fa-flag-checkered',
            'label'  => 'Trip Completed', 'dot' => 'bg-emerald-500',
        ],
        default     => [
            'bg'     => 'bg-gray-50',   'text' => 'text-gray-500',
            'border' => 'border-gray-200', 'icon' => 'fa-minus-circle',
            'label'  => 'Trip Not Started', 'dot' => 'bg-gray-300',
        ],
    };
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Rides — DriverConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: { sans: ['Inter','sans-serif'], display: ['Manrope','sans-serif'], mono: ['JetBrains Mono','monospace'] },
            colors: { primary:'#3B82F6', success:'#10B981', danger:'#EF4444', dark:'#1F2937', surface:'#F3F4F6' }
        }
    }
}
</script>
<style>
   
.dark .surface-texture {
    background-color: #111827;
    background-image: none;
}

/* ── Custom premium layer on top of Tailwind ─────────────────────────────── */
body { font-family: 'Inter', sans-serif; }

.surface-texture {
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='4' height='4'%3E%3Crect width='4' height='4' fill='%23f8fafc'/%3E%3Crect width='1' height='1' fill='%23e2e8f0' opacity='.4'/%3E%3C/svg%3E");
}

/* ── Ride card ─────────────────────────────────────────────────────────────── */
.ride-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    transition: box-shadow .25s ease, transform .2s ease, border-color .2s;
    animation: cardIn .35s ease both;
    position: relative;
}
.ride-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,.09);
    transform: translateY(-2px);
    border-color: #d1d5db;
}
.dark .ride-card { background: #1f2937; border-color: #374151; }
.dark .ride-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,.35); border-color: #4b5563; }

.card-accent { width: 4px; border-radius: 4px 0 0 4px; flex-shrink: 0; }

.card-inner { display: grid; grid-template-columns: 1fr auto; }
.card-main  { padding: 18px 20px; }
.card-side  {
    padding: 18px 20px;
    border-left: 1px solid #f1f5f9;
    min-width: 140px;
    display: flex; flex-direction: column;
    align-items: flex-end; justify-content: space-between;
}
.dark .card-side { border-left-color: #374151; }

.sbadge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 100px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    border: 1px solid transparent;
}
.sdot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }
.blink { animation: blink 1.6s ease infinite; }

/* ── Trip Status Badge ───────────────────────────────────────────────────────── */
.trip-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 100px;
    font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    border: 1px solid transparent;
}

/* ── Payment Gate Banner ─────────────────────────────────────────────────────── */
.pay-gate-banner {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; border-radius: 8px; margin-top: 10px;
    font-size: 11px; font-weight: 600;
}
.pay-gate-banner.locked   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.pay-gate-banner.unlocked { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.pay-gate-banner.deposit  { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

/* ── Action Buttons ──────────────────────────────────────────────────────────── */
.btn-start-ride {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 9px; font-size: 12px; font-weight: 700;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff; border: none; cursor: pointer;
    box-shadow: 0 4px 12px rgba(37,99,235,.35);
    transition: filter .2s, transform .12s; text-decoration: none;
    white-space: nowrap;
}
.btn-start-ride:hover { filter: brightness(1.08); transform: translateY(-1px); color: #fff; }

.btn-view-map {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 9px; font-size: 12px; font-weight: 700;
    background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1;
    text-decoration: none; transition: background .2s; white-space: nowrap;
}
.btn-view-map:hover { background: #e0f2fe; color: #0369a1; }

.btn-not-today {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 9px; font-size: 11px; font-weight: 600;
    background: #f8fafc; border: 1px dashed #cbd5e1; color: #94a3b8;
    white-space: nowrap; cursor: default;
}

/* Car block */
.car-block {
    display: flex; align-items: center; gap: 12px;
    background: #f8fafc; border: 1px solid #f1f5f9;
    border-radius: 10px; padding: 10px 14px; margin-bottom: 12px;
}
.dark .car-block { background: #111827; border-color: #374151; }
.car-img { width: 90px; height: 52px; object-fit: contain; flex-shrink: 0; filter: drop-shadow(0 2px 6px rgba(0,0,0,.12)); }
.car-img-ph {
    width: 90px; height: 52px; flex-shrink: 0; border-radius: 8px;
    background: #f1f5f9; display: flex; align-items: center;
    justify-content: center; font-size: 22px; color: #94a3b8;
}
.plate-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fff; border: 1px solid #e2e8f0;
    padding: 2px 8px; border-radius: 4px;
    font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #475569;
}
.dark .plate-chip { background: #1f2937; border-color: #374151; color: #94a3b8; }

/* Date cells */
.date-strip { display: flex; align-items: stretch; margin-bottom: 12px; border-radius: 10px; overflow: hidden; border: 1px solid #f1f5f9; }
.dcell { flex: 1; padding: 9px 12px; background: #f8fafc; }
.dcell + .dcell { border-left: 1px solid #f1f5f9; }
.dark .dcell { background: #111827; }
.dark .date-strip, .dark .dcell + .dcell { border-color: #374151; }
.dlbl { font-size: 9px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; color: #94a3b8; margin-bottom: 3px; }
.dval { font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 600; color: #1e293b; }
.dark .dval { color: #f1f5f9; }
.dsub { font-size: 10px; color: #94a3b8; margin-top: 1px; }
.dur-badge {
    align-self: center; padding: 0 10px; flex-shrink: 0;
    font-family: 'JetBrains Mono', monospace; font-size: 10px; font-weight: 600;
    color: #3b82f6; background: #eff6ff; border-radius: 100px;
    line-height: 26px; white-space: nowrap; border: 1px solid #dbeafe;
}
.dark .dur-badge { background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.25); color: #60a5fa; }

/* Customer row */
.cust-row { display: flex; align-items: center; gap: 9px; }
.cust-av { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #e0e7ff; flex-shrink: 0; }
.cust-av-ph {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: #eff6ff; border: 2px solid #dbeafe;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: #3b82f6;
}
.call-btn {
    margin-left: auto;
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a;
    text-decoration: none; transition: all .2s; white-space: nowrap;
}
.call-btn:hover { background: #dcfce7; border-color: #86efac; color: #15803d; }
.dark .call-btn { background: rgba(16,185,129,.1); border-color: rgba(16,185,129,.25); color: #34d399; }

/* Earning side */
.earn-top { text-align: right; width: 100%; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.dark .earn-top { border-bottom-color: #374151; }
.earn-lbl { font-size: 9px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #94a3b8; margin-bottom: 3px; }
.earn-val { font-family: 'JetBrains Mono', monospace; font-size: 19px; font-weight: 700; color: #10b981; }
.earn-val.pending { color: #94a3b8; }
.earn-of  { font-size: 10px; color: #94a3b8; margin-top: 1px; }
.aside-rows { width: 100%; margin-top: 10px; }
.aside-row {
    display: flex; align-items: center; justify-content: flex-end; gap: 5px;
    font-size: 11px; color: #94a3b8; margin-bottom: 5px;
}
.aside-row span { color: #475569; font-weight: 500; }
.dark .aside-row span { color: #9ca3af; }

/* ── Stat cards ─────────────────────────────────────────────────────────────── */
.stat-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 14px; padding: 18px;
    position: relative; overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.stat-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dark .stat-card { background: #1f2937; border-color: #374151; }
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--accent, #3b82f6);
}
.stat-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; margin-bottom: 10px;
}
.stat-val { font-family: 'JetBrains Mono', monospace; font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }
.dark .stat-val { color: #f1f5f9; }
.stat-lbl { font-size: 11px; font-weight: 600; color: #94a3b8; margin-top: 4px; text-transform: uppercase; letter-spacing: .07em; }

/* ── Filter pills ───────────────────────────────────────────────────────────── */
.fpill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 100px;
    font-size: 12px; font-weight: 600;
    border: 1px solid #e5e7eb; background: #fff; color: #6b7280;
    text-decoration: none; transition: all .18s; white-space: nowrap;
}
.fpill:hover { border-color: #d1d5db; color: #374151; background: #f9fafb; }
.fpill.active { background: #3b82f6; border-color: #3b82f6; color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,.28); }
.fpill.fp-g.active { background: #10b981; border-color: #10b981; box-shadow: 0 4px 12px rgba(16,185,129,.25); }
.fpill.fp-o.active { background: #f59e0b; border-color: #f59e0b; box-shadow: 0 4px 12px rgba(245,158,11,.25); }
.dark .fpill { background: #1f2937; border-color: #374151; color: #9ca3af; }
.dark .fpill:hover { border-color: #4b5563; color: #d1d5db; background: #374151; }
.pill-cnt { font-size: 10px; background: rgba(0,0,0,.07); padding: 1px 6px; border-radius: 100px; }
.fpill.active .pill-cnt { background: rgba(255,255,255,.22); }

/* ── Search ─────────────────────────────────────────────────────────────────── */
.search-wrap { position: relative; flex: 1; min-width: 180px; }
.search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }
.search-inp {
    width: 100%; padding: 9px 14px 9px 36px;
    border: 1.5px solid #e5e7eb; border-radius: 10px;
    font-family: 'Inter', sans-serif; font-size: 13px;
    background: #fff; color: #1e293b;
    transition: border-color .2s, box-shadow .2s; outline: none;
}
.search-inp:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
.search-inp::placeholder { color: #94a3b8; }
.dark .search-inp { background: #1f2937; border-color: #374151; color: #f1f5f9; }
.dark .search-inp:focus { border-color: #3b82f6; }

/* ── Section label ──────────────────────────────────────────────────────────── */
.sec-sep {
    display: flex; align-items: center; gap: 8px;
    font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: #94a3b8; margin: 20px 0 10px;
}
.sec-sep::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }
.dark .sec-sep::after { background: #374151; }

/* ── Booking ref ────────────────────────────────────────────────────────────── */
.bref {
    font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600;
    color: #3b82f6; display: inline-flex; align-items: center; gap: 4px;
    cursor: pointer; transition: color .15s;
}
.bref:hover { color: #1d4ed8; }

/* ── Toast ──────────────────────────────────────────────────────────────────── */
.toast {
    position: fixed; top: 20px; right: 24px; z-index: 9999;
    padding: 10px 18px; border-radius: 10px;
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 600;
    background: #fff; border: 1px solid #e5e7eb;
    box-shadow: 0 8px 24px rgba(0,0,0,.12); color: #3b82f6;
    transform: translateX(140%);
    transition: transform .4s cubic-bezier(.34,1.56,.64,1);
    pointer-events: none;
}
.toast.show { transform: translateX(0); }

/* ── Empty state ────────────────────────────────────────────────────────────── */
.empty-state {
    text-align: center; padding: 60px 32px;
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 16px;
}
.dark .empty-state { background: #1f2937; border-color: #374151; }

/* ── Animations ─────────────────────────────────────────────────────────────── */
@keyframes cardIn { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
@keyframes countUp { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

/* ── Responsive ─────────────────────────────────────────────────────────────── */
@media(max-width:768px) {
    .card-inner { grid-template-columns: 1fr; }
    .card-side { border-left: none; border-top: 1px solid #f1f5f9; flex-direction: row; align-items: center; min-width: unset; }
    .earn-top { border-bottom: none; padding-bottom: 0; text-align: left; }
    .aside-rows { display: none; }
    .dur-badge { display: none; }
    .stats-row { grid-template-columns: repeat(2,1fr) !important; }
}
@media(max-width:480px) {
    .page-pad { padding: 14px !important; }
}
</style>
</head>

<body class="bg-surface text-gray-800 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-200 h-screen flex flex-col md:flex-row overflow-hidden surface-texture">

<!-- ── SIDEBAR ─────────────────────────────────────────────────────────────── -->
<nav class="md:w-20 lg:w-64 bg-white dark:bg-gray-800 shadow-lg z-50 flex flex-row md:flex-col justify-around md:justify-start py-2 md:py-6 shrink-0 order-2 md:order-1 border-t md:border-t-0 md:border-r border-gray-200 dark:border-gray-700">
    <div class="hidden md:flex items-center justify-center mb-8 px-4">
        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white font-bold text-xl">
            <i class="fas fa-car"></i>
        </div>
        <span class="ml-3 font-bold text-xl hidden lg:block">DriverConnect</span>
    </div>
    <a href="index.php" class="nav-item flex flex-col md:flex-row items-center md:px-6 py-3 text-gray-500 hover:text-primary hover:bg-blue-50 dark:hover:bg-gray-700 transition">
        <i class="fas fa-home text-xl md:w-8"></i>
        <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">Home</span>
    </a>
    <a href="rides.php" class="nav-item active flex flex-col md:flex-row items-center md:px-6 py-3 text-primary bg-blue-50 dark:bg-gray-700">
        <i class="fas fa-car text-xl md:w-8"></i>
        <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">My Rides</span>
    </a>
    <a href="profile.php" class="nav-item flex flex-col md:flex-row items-center md:px-6 py-3 text-gray-500 hover:text-primary hover:bg-blue-50 dark:hover:bg-gray-700 transition">
        <i class="fas fa-user text-xl md:w-8"></i>
        <span class="text-xs md:text-base mt-1 md:mt-0 md:ml-3 lg:inline hidden">Profile</span>
    </a>
</nav>

<!-- ── MAIN ────────────────────────────────────────────────────────────────── -->
<main class="flex-1 flex flex-col h-full overflow-hidden order-1 md:order-2">

    <!-- TOP HEADER -->
    <header class="h-16 bg-white dark:bg-gray-800 shadow-sm flex items-center justify-between px-4 md:px-6 shrink-0 z-40 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white" style="font-family:'Manrope',sans-serif">
                My Rides
            </h1>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold rounded-full border border-blue-100 dark:border-blue-800">
                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                <?= $stats['total'] ?> Total
            </span>
        </div>
        <div class="flex items-center space-x-3">
            <button id="darkModeToggle" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition">
                <i class="fas fa-moon dark:hidden"></i>
                <i class="fas fa-sun hidden dark:inline"></i>
            </button>
            <div class="hidden md:flex flex-col items-end">
                <span class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($driver['driver_name'] ?? '') ?></span>
                <span class="text-xs text-gray-500"><?= htmlspecialchars($driver['license_number'] ?? '') ?></span>
            </div>
            <?php if(!empty($driver['profile_image'])): ?>
            <img src="images/driver_profile/<?= htmlspecialchars($driver['profile_image']) ?>"
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'"
                 class="w-10 h-10 rounded-full border-2 border-primary object-cover" alt="">
            <?php else: ?>
            <div class="w-10 h-10 rounded-full border-2 border-primary bg-blue-50 flex items-center justify-center text-primary font-bold">
                <?= strtoupper(substr($driver['driver_name'] ?? 'D', 0, 1)) ?>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="flex-1 overflow-y-auto page-pad" style="padding:24px 24px 60px">
           <!-- STATS ROW -->
<?php
$trips_completed = 0;
$trips_not_done  = 0;
foreach ($all_rides as $r) {
    if ($r['booking_status'] === 'Approved') {
        if ($r['trip_status'] === 'Completed') $trips_completed++;
        else $trips_not_done++;
    }
}
$pct = $stats['Approved'] > 0 ? round(($trips_completed / $stats['Approved']) * 100) : 0;
?>
<div class="stats-row grid grid-cols-4 gap-3 mb-6">

    <!-- Total Rides -->
    <div class="stat-card" style="--accent:#3b82f6">
        <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="fas fa-route"></i></div>
        <div class="stat-val" id="cnt-total"><?= $stats['total'] ?></div>
        <div class="stat-lbl">Total Rides</div>
    </div>

    <!-- Approved + trip breakdown -->
    <div class="stat-card" style="--accent:#10b981">
        <div class="stat-icon" style="background:#f0fdf4;color:#10b981"><i class="fas fa-check-circle"></i></div>
        <div class="stat-val" id="cnt-approved"><?= $stats['Approved'] ?></div>
        <div class="stat-lbl">Approved</div>
        <div style="margin-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:10px;font-weight:600;margin-bottom:4px;">
                <span style="color:#10b981"><i class="fas fa-flag-checkered" style="font-size:9px;margin-right:2px"></i><?= $trips_completed ?> done</span>
                <span style="color:#f59e0b"><i class="fas fa-hourglass-half" style="font-size:9px;margin-right:2px"></i><?= $trips_not_done ?> left</span>
            </div>
            <div style="height:4px;border-radius:99px;background:#e5e7eb;overflow:hidden;">
                <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:99px;"></div>
            </div>
        </div>
    </div>

    <!-- Not Approved -->
    <div class="stat-card" style="--accent:#ef4444">
        <div class="stat-icon" style="background:#fef2f2;color:#ef4444"><i class="fas fa-times-circle"></i></div>
        <div class="stat-val"><?= $stats['Pending'] ?></div>
        <div class="stat-lbl">Not Approved</div>
    </div>

    <!-- Earned -->
    <div class="stat-card" style="--accent:#10b981">
        <div class="stat-icon" style="background:#f0fdf4;color:#10b981"><i class="fas fa-indian-rupee-sign"></i></div>
        <div class="stat-val">₹<?= number_format($total_earned, 0) ?></div>
        <div class="stat-lbl">Earned</div>
    </div>

</div>

        <!-- TOOLBAR -->
        <div class="flex items-center gap-3 mb-5 flex-wrap">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="srchInput" class="search-inp"
                       placeholder="Search car, customer, booking ID…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="flex gap-2 flex-wrap">
                <?php
                $tabDefs = [
                    'all'      => ['icon'=>'fa-list',        'lb'=>'All',      'cls'=>''],
                    'Approved' => ['icon'=>'fa-check-circle', 'lb'=>'Approved', 'cls'=>'fp-g'],
                    'Pending'  => ['icon'=>'fa-clock',        'lb'=>'Pending',  'cls'=>'fp-o'],
                ];
                foreach ($tabDefs as $key => $td):
                    $cnt  = $key === 'all' ? $stats['total'] : ($stats[$key] ?? 0);
                    $href = '?tab='.$key.($search ? '&q='.urlencode($search) : '');
                ?>
                <a href="<?= $href ?>" class="fpill <?= $td['cls'] ?> <?= $tab===$key?'active':'' ?>">
                    <i class="fas <?= $td['icon'] ?>"></i>
                    <?= $td['lb'] ?>
                    <span class="pill-cnt"><?= $cnt ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIDES ───────────────────────────────────────────────────────────── -->
        <?php if (empty($filtered)): ?>
        <div class="empty-state">
            <div class="w-16 h-16 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-200 text-3xl mx-auto mb-4">
                <i class="fas fa-car"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-300 dark:text-gray-600 mb-2">No rides found</h4>
            <p class="text-sm text-gray-400"><?= $tab !== 'all' ? "No $tab rides." : "You haven't been assigned any rides yet." ?></p>
        </div>

        <?php else:
            $show_groups = ($tab === 'all' && !$search);
            $upcoming    = array_values(array_filter($filtered, fn($r) => in_array($r['booking_status'], ['Approved','Pending'])));
            $today       = date('Y-m-d');

            // renderCard uses $payments_by_booking from outer scope via use()
            $renderCard = function($r, $idx) use ($payments_by_booking, $today) {
                $bid       = $r['booking_id'];
                $bref      = 'CB-' . str_pad($bid, 6, '0', STR_PAD_LEFT);
                $s         = $r['booking_status'];
                $sc        = statusCfg($s);
                $earn      = floatval($r['driver_amount'] ?? 0);
                $tot       = floatval($r['total_amount']  ?? 0);
                $dur       = rideDur($r['pickup_datetime'], $r['actual_return_datetime'] ?? '');
                $trip_st   = $r['trip_status'] ?? 'Not Started';
                $tsc       = tripStatusCfg($trip_st);
                $ret       = (!empty($r['actual_return_datetime']) && $r['actual_return_datetime'] !== '0000-00-00 00:00:00')
                             ? $r['actual_return_datetime'] : null;

                // ── Payment state ──
                $bk_payments = $payments_by_booking[intval($bid)] ?? [];
                $ps          = calcPaymentState($bk_payments, $tot);

                // ── Is today the pickup day? ──
                $pickup_date   = date('Y-m-d', strtotime($r['pickup_datetime']));
                $is_today      = ($pickup_date === $today);
                $is_approved   = ($s === 'Approved');

                // ── Earning display logic:
                //    Show real amount only if trip completed + fully paid, else show "Pending"
                $earn_confirmed = ($trip_st === 'Completed' && $ps['is_fully_paid']);
            ?>
            <div class="ride-card flex" style="animation-delay:<?= $idx*.04 ?>s">
                <!-- Accent bar -->
                <div class="card-accent" style="background:<?= $sc['bar'] ?>"></div>

                <div class="card-inner flex-1">
                    <!-- MAIN -->
                    <div class="card-main">

                        <!-- Ref + booking status + trip status -->
                        <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
                            <div>
                                <div class="bref" onclick="cpRef('<?= $bref ?>')">
                                    <i class="fas fa-hashtag text-gray-300 text-xs"></i>
                                    <?= $bref ?>
                                    <i class="fas fa-copy text-gray-300 text-xs"></i>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    Booked <?= date('d M Y', strtotime($r['created_at'])) ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                <!-- Booking status badge -->
                                <span class="sbadge <?= $sc['bg'] ?> <?= $sc['text'] ?> <?= $sc['border'] ?>">
                                    <span class="sdot <?= $s==='Pending'?'blink':'' ?>"></span>
                                    <i class="fas <?= $sc['icon'] ?>"></i>
                                    <?= $s ?>
                                </span>
                                <!-- Trip status badge -->
                                <span class="trip-badge <?= $tsc['bg'] ?> <?= $tsc['text'] ?> <?= $tsc['border'] ?>">
                                    <span class="sdot" style="background:currentColor"></span>
                                    <i class="fas <?= $tsc['icon'] ?>"></i>
                                    <?= $tsc['label'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Car -->
                        <div class="car-block">
                            <?php if(!empty($r['primary_image'])): ?>
                            <img src="../Admin/pages/images/car_images/<?= htmlspecialchars($r['primary_image']) ?>"
                                 class="car-img"
                                 onerror="this.outerHTML='<div class=\'car-img-ph\'><i class=\'fas fa-car\'></i></div>'"
                                 alt="">
                            <?php else: ?>
                            <div class="car-img-ph"><i class="fas fa-car"></i></div>
                            <?php endif; ?>

                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-800 dark:text-white text-sm truncate"><?= htmlspecialchars($r['car_display_name'] ?? '—') ?></div>
                                <div class="text-xs text-gray-400 mb-1.5"><?= htmlspecialchars($r['brand_name'] ?? '') ?></div>
                                <?php if(!empty($r['car_number_plate'])): ?>
                                <div class="plate-chip">
                                    <i class="fas fa-id-card text-amber-400" style="font-size:9px"></i>
                                    <?= htmlspecialchars($r['car_number_plate']) ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="text-right flex-shrink-0 flex flex-col gap-1">
                                <?php if(!empty($r['fuel_type'])): ?>
                                <div class="text-xs text-gray-400"><i class="fas fa-gas-pump text-emerald-400 mr-1"></i><?= htmlspecialchars($r['fuel_type']) ?></div>
                                <?php endif; ?>
                                <?php if(!empty($r['gear_type'])): ?>
                                <div class="text-xs text-gray-400"><i class="fas fa-cogs text-blue-400 mr-1"></i><?= htmlspecialchars($r['gear_type']) ?></div>
                                <?php endif; ?>
                                <?php if(!empty($r['seating_capacity'])): ?>
                                <div class="text-xs text-gray-400"><i class="fas fa-users text-violet-400 mr-1"></i><?= $r['seating_capacity'] ?> seats</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="date-strip">
                            <div class="dcell">
                                <div class="dlbl"><i class="fas fa-arrow-right text-emerald-400 mr-1"></i>Pickup</div>
                                <div class="dval"><?= date('d M Y', strtotime($r['pickup_datetime'])) ?></div>
                                <div class="dsub"><?= date('h:i A', strtotime($r['pickup_datetime'])) ?></div>
                            </div>
                            <?php if($dur): ?>
                            <div class="dur-badge"><?= $dur ?></div>
                            <?php endif; ?>
                            <div class="dcell">
                                <div class="dlbl"><i class="fas fa-flag-checkered text-blue-400 mr-1"></i>Return</div>
                                <?php if($ret): ?>
                                <div class="dval"><?= date('d M Y', strtotime($ret)) ?></div>
                                <div class="dsub"><?= date('h:i A', strtotime($ret)) ?></div>
                                <?php else: ?>
                                <div class="dval text-gray-300 text-xs">Not set</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Customer row + action buttons -->
                        <div class="cust-row">
                            <?php if(!empty($r['user_photo'])): ?>
                            <img src="user_profile/<?= htmlspecialchars($r['user_photo']) ?>"
                                 class="cust-av"
                                 onerror="this.outerHTML='<div class=\'cust-av-ph\'><i class=\'fas fa-user\'></i></div>'"
                                 alt="">
                            <?php else: ?>
                            <div class="cust-av-ph"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-gray-800 dark:text-white truncate"><?= htmlspecialchars($r['uname'] ?? '—') ?></div>
                                <div class="text-xs text-gray-400 truncate"><?= htmlspecialchars($r['cust_email'] ?? '') ?></div>
                            </div>

                            <!-- ── ACTION BUTTONS (core new logic) ── -->
<div class="ml-auto flex items-center gap-2 flex-wrap justify-end">

    <?php if ($is_approved && $trip_st === 'Not Started'): ?>
    <?php if ($is_today && $ps['payment_ok']): ?>
        <!-- Today + paid → redirect to map with start=1 flag -->
        <a href="index.php?booking_id=<?= $bid ?>&start=1" class="btn-start-ride">
            <i class="fas fa-play text-xs"></i> Start Ride
        </a>
    <?php elseif ($is_today && !$ps['payment_ok']): ?>
        <!-- Today but no payment → view map only, locked -->
        <a href="index.php?booking_id=<?= $bid ?>" class="btn-view-map">
            <i class="fas fa-map-marked-alt"></i> View Map
        </a>
    <?php else: ?>
        <!-- Future date → view map + show upcoming date chip -->
        <a href="index.php?booking_id=<?= $bid ?>" class="btn-view-map">
            <i class="fas fa-map-marked-alt"></i> View Map
        </a>
        <?php if ($pickup_date > $today): ?>
        <span class="btn-not-today">
            <i class="fas fa-calendar"></i>
            Pickup on <?= date('d M', strtotime($pickup_date)) ?>
        </span>
        <?php endif; ?>
    <?php endif; ?>

    <?php elseif ($trip_st === 'Started'): ?>
        <a href="index.php?booking_id=<?= $bid ?>" class="btn-view-map">
            <i class="fas fa-map-marked-alt"></i> View Map
        </a>

    <?php elseif ($trip_st === 'Completed'): ?>
        <a href="index.php?booking_id=<?= $bid ?>" class="btn-view-map">
            <i class="fas fa-map-marked-alt"></i> View Map
        </a>

    <?php else: ?>
        <a href="index.php?booking_id=<?= $bid ?>" class="btn-view-map">
            <i class="fas fa-map-marked-alt"></i> View Map
        </a>
    <?php endif; ?>

    <?php if(!empty($r['mobno']) && $s === 'Approved'): ?>
    <a href="tel:<?= htmlspecialchars($r['mobno']) ?>" class="call-btn" style="margin-left:0;">
        <i class="fas fa-phone text-xs"></i>
        <?= htmlspecialchars($r['mobno']) ?>
    </a>
    <?php endif; ?>

</div>
                        </div>

                        <!-- ── Payment gate banner (shown when today but no payment) ── -->
                        <?php if ($is_today && $is_approved && !$ps['payment_ok'] && $trip_st === 'Not Started'): ?>
                        <div class="pay-gate-banner locked">
                            <i class="fas fa-lock text-xs"></i>
                            Start Ride is locked — customer has not paid the deposit or full amount yet.
                        </div>
                        <?php elseif ($is_today && $is_approved && $ps['payment_ok'] && !$ps['is_fully_paid'] && $trip_st === 'Not Started'): ?>
                        <div class="pay-gate-banner deposit">
                            <i class="fas fa-shield-alt text-xs"></i>
                            Security deposit paid (₹<?= number_format($ps['dep_amount'], 0) ?>). You can start the ride. Rental due after trip.
                        </div>
                        <?php endif; ?>

                    </div><!-- /card-main -->

                    <!-- ASIDE -->
                    <div class="card-side">
                        <div class="earn-top">
    <div class="earn-lbl">Your Earning</div>
    <?php if ($ps['is_fully_paid']): ?>
        <div class="earn-val">₹<?= number_format($earn, 0) ?></div>
        <div class="earn-of">of ₹<?= number_format($tot, 0) ?> total</div>
    <?php else: ?>
        <div class="earn-val pending">₹<?= number_format($earn, 0) ?></div>
        <div class="earn-of" style="color:#f59e0b;font-size:9px;font-weight:700;letter-spacing:.04em;">
            <i class="fas fa-hourglass-half" style="font-size:8px"></i> Will earn on completion
        </div>
    <?php endif; ?>
</div>
                        <div class="aside-rows">
                            <div class="aside-row"><i class="fas fa-calendar-alt text-blue-400 w-3 text-center"></i><span><?= date('d M', strtotime($r['pickup_datetime'])) ?></span></div>
                            <?php if($dur): ?>
                            <div class="aside-row"><i class="fas fa-hourglass-half text-amber-400 w-3 text-center"></i><span><?= $dur ?></span></div>
                            <?php endif; ?>
                            <?php if(!empty($r['car_number_plate'])): ?>
                            <div class="aside-row"><i class="fas fa-car text-emerald-400 w-3 text-center"></i><span style="font-family:'JetBrains Mono',monospace;font-size:10px"><?= htmlspecialchars($r['car_number_plate']) ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /card-inner -->
            </div>
            <?php };// end renderCard closure

            if ($show_groups):
                if (!empty($upcoming)): ?>
                    <div class="sec-sep"><i class="fas fa-calendar-check text-emerald-400"></i> Upcoming & Active</div>
                    <?php foreach($upcoming as $i => $r) $renderCard($r, $i); ?>
                <?php endif;
            else:
                foreach($filtered as $i => $r) $renderCard($r, $i);
            endif;
        endif; ?>

    </div><!-- /page content -->
</main>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script src="script.js"></script>

<script>
    // ── Copy ref ───────────────────────────────────────────────────────────────────
    function cpRef(ref) {
        navigator.clipboard.writeText(ref).then(function() {
            var t = document.getElementById('toast');
            t.innerHTML = '<i class="fas fa-copy text-blue-500"></i>&nbsp;' + ref + ' copied';
            t.classList.add('show');
            setTimeout(function(){ t.classList.remove('show'); }, 2400);
        });
    }

    // ── Live client-side search ────────────────────────────────────────────────────
    (function(){
        var inp   = document.getElementById('srchInput');
        var cards = document.querySelectorAll('.ride-card');
        if (!inp) return;
        var timer;
        inp.addEventListener('input', function(){
            var q = this.value.toLowerCase().trim();
            cards.forEach(function(c){
                c.style.display = (!q || c.textContent.toLowerCase().includes(q)) ? '' : 'none';
            });
            clearTimeout(timer);
            timer = setTimeout(function(){
                if (inp.value.trim()) {
                    var u = new URL(window.location.href);
                    u.searchParams.set('q', inp.value.trim());
                    window.location.href = u.toString();
                }
            }, 900);
        });
    })();

    // ── Stat counter animation ─────────────────────────────────────────────────────
    ['cnt-total','cnt-approved'].forEach(function(id){
        var el = document.getElementById(id);
        if (!el) return;
        var target = parseInt(el.textContent) || 0;
        if (!target) return;
        var cur = 0, step = Math.max(1, Math.ceil(target/25));
        var t = setInterval(function(){
            cur = Math.min(cur + step, target);
            el.textContent = cur;
            if (cur >= target) clearInterval(t);
        }, 30);
    });
</script>

</body>
</html>