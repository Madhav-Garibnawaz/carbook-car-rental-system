<?php
session_name('admin_session');
session_start();
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header("Location: register.php"); exit();
}
include('../components/navbar.php');
include('connect.php');

function safe_count($con, $table, $where = '') {
    $q = mysqli_query($con, "SELECT COUNT(*) AS c FROM `$table`" . ($where ? " WHERE $where" : ""));
    if (!$q) return 0;
    $r = mysqli_fetch_assoc($q);
    return $r ? (int)$r['c'] : 0;
}

$total_brands   = safe_count($con, 'brand_master');
$total_cats     = safe_count($con, 'category_master');
$total_models   = safe_count($con, 'model_master');
$total_cars     = safe_count($con, 'car_master');
$total_users    = safe_count($con, 'users_master');
$active_users   = safe_count($con, 'users_master', 'status = 2');
$total_drivers  = safe_count($con, 'driver_master');
$active_drivers = safe_count($con, 'driver_master', 'status = 1');
$inactive_users   = $total_users - $active_users;
$inactive_drivers = $total_drivers - $active_drivers;

$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_master");
$total_bookings = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

$q = mysqli_query($con, "SELECT COALESCE(SUM(total_amount),0) AS p FROM booking_details");
$total_profit = ($q && $r = mysqli_fetch_assoc($q)) ? (float)$r['p'] : 0;

$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_details WHERE booking_status='Completed'");
$completed = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_details WHERE booking_status='Pending'");
$pending = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// ── BOOKING STATUS BREAKDOWN for ratio card ───────────────────────────────────
// Trip Completed
$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_details WHERE trip_status='Completed'");
$trip_completed = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// Booked but trip NOT completed (Approved + trip_status != Completed)
$q = mysqli_query($con,
    "SELECT COUNT(*) AS c FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     WHERE bm.booking_status = 'Approved'
       AND (bd.trip_status IS NULL OR bd.trip_status != 'Completed')"
);
$trip_active = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// Not Approved (Pending in DB)
$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_master WHERE booking_status='Pending'");
$not_approved = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// Cancelled
$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_master WHERE booking_status='Cancelled'");
$cancelled = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// Rejected
$q = mysqli_query($con, "SELECT COUNT(*) AS c FROM booking_master WHERE booking_status='Rejected'");
$rejected = ($q && $r = mysqli_fetch_assoc($q)) ? (int)$r['c'] : 0;

// ── PAYMENT RECEIVED ──────────────────────────────────────────────────────────
$q = mysqli_query($con, "SELECT COALESCE(SUM(paid_amount),0) AS received FROM payment_master WHERE payment_status IN (1,2)");
$amount_received = ($q && $r = mysqli_fetch_assoc($q)) ? (float)$r['received'] : 0;

$q = mysqli_query($con,
    "SELECT COUNT(*) AS unpaid_count
     FROM booking_master bm
     INNER JOIN booking_details bd ON bd.booking_id = bm.booking_id
     LEFT JOIN (
         SELECT booking_id, SUM(paid_amount) AS total_paid
         FROM payment_master
         WHERE payment_status IN (1,2)
         GROUP BY booking_id
     ) pm ON pm.booking_id = bm.booking_id
     WHERE bm.booking_status = 'Approved'
       AND (pm.total_paid IS NULL OR pm.total_paid < bd.total_amount)"
);
$unpaid_approved_count = 0;
if ($q && $r = mysqli_fetch_assoc($q)) {
    $unpaid_approved_count = (int)$r['unpaid_count'];
}

$q = mysqli_query($con,
    "SELECT COALESCE(SUM(bd.total_amount), 0) AS total_billed
     FROM booking_master bm
     INNER JOIN booking_details bd ON bd.booking_id = bm.booking_id
     WHERE bm.booking_status = 'Approved'"
);
$total_billed_approved = ($q && $r = mysqli_fetch_assoc($q)) ? (float)$r['total_billed'] : 0;

$q = mysqli_query($con,
    "SELECT COALESCE(SUM(pm.paid_amount), 0) AS paid_on_approved
     FROM payment_master pm
     INNER JOIN booking_master bm ON bm.booking_id = pm.booking_id
     WHERE pm.payment_status IN (1,2)
       AND bm.booking_status = 'Approved'"
);
$paid_on_approved = ($q && $r = mysqli_fetch_assoc($q)) ? (float)$r['paid_on_approved'] : 0;
$amount_pending = max(0, $total_billed_approved - $paid_on_approved);

$collection_rate = ($amount_received + $amount_pending) > 0
    ? round($amount_received / ($amount_received + $amount_pending) * 100, 1)
    : 0;

$recent_users = [];
$q = mysqli_query($con, "SELECT uname,email,status,photo FROM users_master ORDER BY created_at DESC LIMIT 6");
if ($q) while ($r = mysqli_fetch_assoc($q)) $recent_users[] = $r;

$recent_drivers = [];
$q = mysqli_query($con, "SELECT driver_name,driver_mobile,status,profile_image FROM driver_master ORDER BY driver_id DESC LIMIT 6");
if ($q) while ($r = mysqli_fetch_assoc($q)) $recent_drivers[] = $r;

// ── PROFIT TREND: last 14 days ────────────────────────────────────────────────
$days_map = [];
for ($i = 13; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M',   strtotime("-$i days"));
    $days_map[$date] = ['label' => $label, 'profit' => 0, 'bookings' => 0];
}
$q = mysqli_query($con,
    "SELECT DATE(bm.created_at) AS day,
            COALESCE(SUM(bd.total_amount),0) AS daily_profit,
            COUNT(bm.booking_id) AS daily_bookings
     FROM booking_master bm
     LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
     WHERE bm.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(bm.created_at)");
if ($q) while ($r = mysqli_fetch_assoc($q)) {
    if (isset($days_map[$r['day']])) {
        $days_map[$r['day']]['profit']   = (float)$r['daily_profit'];
        $days_map[$r['day']]['bookings'] = (int)$r['daily_bookings'];
    }
}
$profit_labels = $profit_data = $bookings_data = [];
foreach ($days_map as $v) {
    $profit_labels[] = $v['label'];
    $profit_data[]   = $v['profit'];
    $bookings_data[] = $v['bookings'];
}
$week1 = array_sum(array_slice($profit_data, 0, 7));
$week2 = array_sum(array_slice($profit_data, 7, 7));
$profit_change = $week1 > 0 ? round(($week2 - $week1) / $week1 * 100, 1) : 0;
$peak_idx    = array_search(max($profit_data), $profit_data);
$peak_label  = $profit_labels[$peak_idx] ?? '—';
$peak_amount = max($profit_data);

// ── Brand data ────────────────────────────────────────────────────────────────
$brand_labels = []; $brand_data = [];
$q = mysqli_query($con, "SELECT b.brand_name, COUNT(c.car_id) AS cnt FROM brand_master b LEFT JOIN car_master c ON c.brand_id=b.brand_id GROUP BY b.brand_id ORDER BY cnt DESC LIMIT 10");
if ($q) while ($r = mysqli_fetch_assoc($q)) { $brand_labels[] = $r['brand_name']; $brand_data[] = (int)$r['cnt']; }
if (empty($brand_labels)) { $brand_labels = ['No data']; $brand_data = [0]; }

// ── Gender data ───────────────────────────────────────────────────────────────
$gen_labels = []; $gen_data = [];
$q = mysqli_query($con, "SELECT COALESCE(NULLIF(gen,''),'Unknown') AS g, COUNT(*) AS cnt FROM users_master GROUP BY g ORDER BY cnt DESC");
if ($q) while ($r = mysqli_fetch_assoc($q)) { $gen_labels[] = $r['g']; $gen_data[] = (int)$r['cnt']; }
if (empty($gen_labels)) { $gen_labels = ['Unknown']; $gen_data = [1]; }

// ═════════════════════════════════════════════════════════════════════════════
//  SVG CHART HELPERS
// ═════════════════════════════════════════════════════════════════════════════

function svg_profit_chart($labels, $profit, $w=680, $h=240) {
    $pad  = ['t'=>24,'r'=>24,'b'=>48,'l'=>72];
    $cw   = $w - $pad['l'] - $pad['r'];
    $ch   = $h - $pad['t'] - $pad['b'];
    $n    = count($labels) ?: 1;
    $xStep = $cw / max($n - 1, 1);

    $max_p = max(array_merge($profit, [1]));
    $max_p = ceil($max_p * 1.3 / 100) * 100 ?: 100;

    ob_start();
    echo "<svg viewBox=\"0 0 $w $h\" xmlns=\"http://www.w3.org/2000/svg\" style=\"width:100%;height:100%;display:block;\">";

    echo "<defs>";
    echo "<linearGradient id='gp' x1='0' y1='0' x2='0' y2='1'><stop offset='0%' stop-color='#16a34a' stop-opacity='0.25'/><stop offset='100%' stop-color='#16a34a' stop-opacity='0'/></linearGradient>";
    echo "<filter id='glow'><feGaussianBlur stdDeviation='2' result='blur'/><feMerge><feMergeNode in='blur'/><feMergeNode in='SourceGraphic'/></feMerge></filter>";
    echo "</defs>";

    for ($i = 0; $i <= 5; $i++) {
        $y   = $pad['t'] + $ch - ($i / 5) * $ch;
        $val = number_format(($i / 5) * $max_p);
        $col = $i === 0 ? '#e5e7eb' : '#f3f4f6';
        echo "<line x1='{$pad['l']}' y1='$y' x2='" . ($w - $pad['r']) . "' y2='$y' stroke='$col' stroke-width='1' stroke-dasharray='4 3'/>";
        echo "<text x='" . ($pad['l'] - 10) . "' y='" . ($y + 4) . "' text-anchor='end' font-size='10' fill='#9ca3af'>₹$val</text>";
    }

    for ($i = 6; $i < $n; $i += 7) {
        $x1 = $pad['l'] + ($i - 0.5) * $xStep;
        $x2 = $pad['l'] + ($i + 0.5) * $xStep;
        echo "<rect x='$x1' y='{$pad['t']}' width='" . ($x2 - $x1) . "' height='$ch' fill='#f9fafb'/>";
    }

    for ($i = 0; $i < $n; $i++) {
        $x = $pad['l'] + $i * $xStep;
        $y = $h - 10;
        $fw = ($i === 0 || $i === $n - 1) ? '600' : '400';
        echo "<text x='$x' y='$y' text-anchor='middle' font-size='10' font-weight='$fw' fill='#9ca3af'>{$labels[$i]}</text>";
    }

    $ppts = [];
    for ($i = 0; $i < $n; $i++) {
        $ppts[] = [
            $pad['l'] + $i * $xStep,
            $pad['t'] + $ch - ($max_p > 0 ? ($profit[$i] / $max_p) * $ch : 0)
        ];
    }

    $area  = "M {$ppts[0][0]} " . ($pad['t'] + $ch);
    foreach ($ppts as $p) $area .= " L {$p[0]} {$p[1]}";
    $area .= " L {$ppts[$n-1][0]} " . ($pad['t'] + $ch) . " Z";
    echo "<path d='$area' fill='url(#gp)'/>";

    $line = "M {$ppts[0][0]} {$ppts[0][1]}";
    for ($i = 1; $i < $n; $i++) {
        $cx = ($ppts[$i-1][0] + $ppts[$i][0]) / 2;
        $line .= " C $cx {$ppts[$i-1][1]}, $cx {$ppts[$i][1]}, {$ppts[$i][0]} {$ppts[$i][1]}";
    }
    echo "<path d='$line' fill='none' stroke='#16a34a' stroke-width='2.5' stroke-linecap='round' filter='url(#glow)'/>";

    foreach ($ppts as $i => $p) {
        $r    = $profit[$i] > 0 ? 5 : 3;
        $fill = $profit[$i] > 0 ? '#16a34a' : '#e5e7eb';
        echo "<circle cx='{$p[0]}' cy='{$p[1]}' r='$r' fill='$fill' stroke='#fff' stroke-width='2'/>";
        if ($profit[$i] === max($profit) && $profit[$i] > 0) {
            $lx = $p[0]; $ly = $p[1] - 12;
            echo "<rect x='" . ($lx-22) . "' y='" . ($ly-13) . "' width='44' height='16' rx='4' fill='#16a34a'/>";
            echo "<text x='$lx' y='" . ($ly-2) . "' text-anchor='middle' font-size='9' font-weight='700' fill='#fff'>₹" . number_format($profit[$i]) . "</text>";
        }
    }

    echo "</svg>";
    return ob_get_clean();
}

function svg_brand_chart($labels, $data, $w=340, $h=240) {
    $labels = array_slice($labels, 0, 7);
    $data   = array_slice($data,   0, 7);
    $pad = ['t'=>24,'r'=>24,'b'=>60,'l'=>44];
    $cw  = $w - $pad['l'] - $pad['r'];
    $ch  = $h - $pad['t'] - $pad['b'];
    $n   = count($labels) ?: 1;
    $max = max(array_merge($data, [1]));
    $max = ceil($max * 1.25);
    $gap  = $cw / $n;
    $barW = min(54, $gap - 12);

    $palette = [
        ['#4f8ef7','#3b7de8'],['#7c5df9','#6b4ee0'],['#0891b2','#0779a0'],
        ['#16a34a','#138a3f'],['#d97706','#bf6805'],['#dc2626','#c01f1f'],
        ['#db2777','#c01d68'],['#7c3aed','#6d2fd4'],['#059669','#04835a'],
        ['#ea580c','#d14e0a'],
    ];

    ob_start();
    echo "<svg viewBox='0 0 $w $h' xmlns='http://www.w3.org/2000/svg' style='width:100%;height:100%;display:block;'>";
    echo "<defs>";
    foreach ($palette as $bi => list($c1,$c2)) {
        echo "<linearGradient id='bg$bi' x1='0' y1='0' x2='0' y2='1'>";
        echo "<stop offset='0%' stop-color='$c1'/><stop offset='100%' stop-color='$c2' stop-opacity='0.7'/>";
        echo "</linearGradient>";
    }
    echo "</defs>";

    for ($i = 0; $i <= 4; $i++) {
        $y   = $pad['t'] + $ch - ($i / 4) * $ch;
        $val = round(($i / 4) * $max);
        echo "<line x1='{$pad['l']}' y1='$y' x2='" . ($w - $pad['r']) . "' y2='$y' stroke='#f1f5f9' stroke-width='1' stroke-dasharray='4 3'/>";
        echo "<text x='" . ($pad['l'] - 6) . "' y='" . ($y + 4) . "' text-anchor='end' font-size='10' fill='#9ca3af'>$val</text>";
    }

    foreach ($data as $i => $val) {
        $bh  = $max > 0 ? ($val / $max) * $ch : 2;
        $x   = $pad['l'] + $i * $gap + ($gap - $barW) / 2;
        $y   = $pad['t'] + $ch - $bh;
        $ci  = $i % count($palette);

        echo "<rect x='" . ($x+2) . "' y='" . ($y+3) . "' width='$barW' height='$bh' rx='7' fill='rgba(0,0,0,0.07)'/>";
        echo "<rect x='$x' y='$y' width='$barW' height='$bh' rx='7' fill='url(#bg$ci)'/>";
        if ($val > 0) {
            echo "<text x='" . ($x + $barW / 2) . "' y='" . ($y - 6) . "' text-anchor='middle' font-size='11' font-weight='700' fill='{$palette[$ci][0]}'>$val</text>";
        }

        $lx  = $pad['l'] + $i * $gap + $gap / 2;
        $ly  = $h - 20;
        $lbl = $labels[$i];
        $words = explode(' ', $lbl);
        if (count($words) > 1 && strlen($lbl) > 10) {
            echo "<text x='$lx' y='$ly' text-anchor='middle' font-size='10' fill='#6b7280'>{$words[0]}</text>";
            echo "<text x='$lx' y='" . ($ly + 13) . "' text-anchor='middle' font-size='10' fill='#6b7280'>" . implode(' ', array_slice($words, 1)) . "</text>";
        } else {
            echo "<text x='$lx' y='$ly' text-anchor='middle' font-size='10' fill='#6b7280'>$lbl</text>";
        }
    }
    echo "</svg>";
    return ob_get_clean();
}

function svg_donut_chart($labels, $data, $colors, $size=140) {
    $total = array_sum($data) ?: 1;
    $cx = $size/2; $cy = $size/2;
    $ro = $size/2 - 8; $ri = $ro * 0.55;
    $angle = -M_PI/2;
    ob_start();
    echo "<svg viewBox='0 0 $size $size' xmlns='http://www.w3.org/2000/svg' style='width:{$size}px;height:{$size}px;flex-shrink:0;'>";
    foreach ($data as $i => $val) {
        $slice = ($val / $total) * 2 * M_PI;
        $x1o = $cx + $ro * cos($angle); $y1o = $cy + $ro * sin($angle);
        $x2o = $cx + $ro * cos($angle + $slice); $y2o = $cy + $ro * sin($angle + $slice);
        $x1i = $cx + $ri * cos($angle + $slice); $y1i = $cy + $ri * sin($angle + $slice);
        $x2i = $cx + $ri * cos($angle); $y2i = $cy + $ri * sin($angle);
        $large = $slice > M_PI ? 1 : 0;
        $color = $colors[$i % count($colors)];
        echo "<path d='M $x1o $y1o A $ro $ro 0 $large 1 $x2o $y2o L $x1i $y1i A $ri $ri 0 $large 0 $x2i $y2i Z' fill='$color' stroke='#fff' stroke-width='2'/>";
        $angle += $slice;
    }
    echo "<text x='$cx' y='" . ($cy - 4) . "' text-anchor='middle' font-size='18' font-weight='700' fill='#111827'>$total</text>";
    echo "<text x='$cx' y='" . ($cy + 14) . "' text-anchor='middle' font-size='9' fill='#9ca3af'>users</text>";
    echo "</svg>";
    return ob_get_clean();
}

$profit_svg = svg_profit_chart($profit_labels, $profit_data);
$brand_svg  = svg_brand_chart($brand_labels, $brand_data, 340, 240);
$donut_svg  = svg_donut_chart($gen_labels, $gen_data, ['#4f8ef7','#db2777','#9ca3af','#d97706','#0891b2']);

// ── Booking ratio segments ────────────────────────────────────────────────────
$ratio_total = max($total_bookings, 1);
$ratio_segments = [
    ['label'=>'Trip Not Started',    'count'=>$trip_active,    'color'=>'#4f8ef7', 'bg'=>'#eff6ff', 'tc'=>'#1d4ed8'],
    ['label'=>'Trip Completed',  'count'=>$trip_completed, 'color'=>'#16a34a', 'bg'=>'#dcfce7', 'tc'=>'#15803d'],
    ['label'=>'Not Approved',    'count'=>$not_approved,   'color'=>'#f59e0b', 'bg'=>'#fef3c7', 'tc'=>'#92400e'],
    ['label'=>'Cancelled',       'count'=>$cancelled,      'color'=>'#ef4444', 'bg'=>'#fee2e2', 'tc'=>'#b91c1c'],
    ['label'=>'Rejected',        'count'=>$rejected,       'color'=>'#6b7280', 'bg'=>'#f3f4f6', 'tc'=>'#374151'],
];
?>
<style>
  
:root{
  --bg:#f0f2f8;--bg2:#e4e7f2;--surface:#fff;
  --border:rgba(0,0,0,0.07);--border2:rgba(0,0,0,0.13);
  --accent:#4f8ef7;--accent2:#7c5df9;
  --green:#16a34a;--green-bg:#dcfce7;
  --amber:#d97706;--amber-bg:#fef3c7;
  --red:#dc2626;--cyan:#0891b2;--cyan-bg:#e0f2fe;--pink:#db2777;
  --text:#111827;--text2:#6b7280;--text3:#9ca3af;
  --r2:18px;
  --shadow:0 1px 3px rgba(0,0,0,.05),0 4px 16px rgba(0,0,0,.05);
  --shadow-h:0 6px 24px rgba(79,142,247,.15);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--text)}

.dash{padding:2rem 2rem 4rem;max-width:1440px;margin:0 auto}
.dash-title{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2rem;padding-bottom:1.25rem;border-bottom:1px solid var(--border)}
.dash-title h1{font-size:1.9rem;font-weight:800;letter-spacing:-.03em}
.dash-title p{font-size:.83rem;color:var(--text2);margin-top:3px}
.live-badge{display:flex;align-items:center;gap:7px;background:var(--surface);border:1px solid var(--border2);padding:6px 16px;border-radius:999px;font-size:.78rem;font-weight:600;color:var(--text2);box-shadow:var(--shadow)}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--green);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.4)}}

/* ── KPI cards ── */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem}
.kpi2-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r2);padding:1.3rem 1.4rem;text-decoration:none;display:block;position:relative;overflow:hidden;box-shadow:var(--shadow);transition:transform .2s,box-shadow .2s}
.kpi-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--kc,#4f8ef7);border-radius:var(--r2) var(--r2) 0 0}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-h)}
.kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
.kpi-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.kpi-trend{font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:6px}
.t-up{background:var(--green-bg);color:var(--green)}
.t-down{background:#fee2e2;color:#dc2626}
.t-neu{background:var(--bg2);color:var(--text2)}
.kpi-value{font-size:2.1rem;font-weight:800;letter-spacing:-.04em;line-height:1}
.kpi-label{font-size:.78rem;color:var(--text2);margin-top:5px;font-weight:500}
.kpi-bar{height:4px;border-radius:999px;background:var(--bg2);margin-top:10px;overflow:hidden}
.kpi-fill{height:100%;border-radius:999px;opacity:.65}
.kpi-sub{margin-top:8px;font-size:.72rem;color:var(--text3)}
.kpi-sub .g{color:var(--green);font-weight:700}
.kpi-sub .a{color:var(--amber);font-weight:700}

/* ── BOOKING RATIO CARD ── */
.booking-ratio-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 1.3rem 1.4rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow);
    cursor: default;
}
.booking-ratio-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #4f8ef7, #16a34a, #f59e0b, #ef4444, #6b7280);
    border-radius: var(--r2) var(--r2) 0 0;
}
.brc-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .85rem;
}
.brc-total {
    font-size: 2.1rem;
    font-weight: 800;
    letter-spacing: -.04em;
    line-height: 1;
}
.brc-label {
    font-size: .78rem;
    color: var(--text2);
    margin-top: 4px;
    font-weight: 500;
}

/* Stacked ratio bar */
.ratio-bar {
    display: flex;
    height: 9px;
    border-radius: 999px;
    overflow: hidden;
    gap: 2px;
    margin: .85rem 0 .8rem;
}
.ratio-seg {
    height: 100%;
    border-radius: 999px;
    transition: opacity .2s;
    min-width: 3px;
}
.ratio-seg:hover { opacity: .75; }

/* Legend grid */
.ratio-legend {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5px 10px;
    margin-top: .3rem;
}
.rl-item {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 8px;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: background .15s;
}
.rl-item:hover {
    background: var(--bg);
}
.rl-dot {
    width: 9px;
    height: 9px;
    border-radius: 3px;
    flex-shrink: 0;
}
.rl-info { flex: 1; min-width: 0; }
.rl-name {
    font-size: .7rem;
    color: var(--text2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.rl-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1px;
    flex-shrink: 0;
}
.rl-count {
    font-size: .82rem;
    font-weight: 800;
    letter-spacing: -.02em;
}
.rl-pct {
    font-size: .64rem;
    color: var(--text3);
    font-weight: 600;
}

/* ── PROFIT PAYMENT SPLIT ── */
.profit-split{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:.85rem}
.psplit-item{border-radius:10px;padding:.65rem .85rem;position:relative;overflow:hidden}
.psplit-item.received{background:linear-gradient(135deg,#dcfce7,#bbf7d0);border:1px solid #86efac}
.psplit-item.pending{background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #fbbf24}
.psplit-label{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;display:flex;align-items:center;gap:4px}
.psplit-item.received .psplit-label{color:#15803d}
.psplit-item.pending .psplit-label{color:#92400e}
.psplit-amount{font-size:.95rem;font-weight:800;letter-spacing:-.02em}
.psplit-item.received .psplit-amount{color:#15803d}
.psplit-item.pending .psplit-amount{color:#b45309}
.psplit-sub{font-size:.62rem;margin-top:2px;opacity:.75}
.psplit-item.received .psplit-sub{color:#166534}
.psplit-item.pending .psplit-sub{color:#92400e}
.collection-bar-wrap{margin-top:.75rem}
.collection-bar-label{display:flex;justify-content:space-between;font-size:.68rem;color:var(--text3);margin-bottom:4px}
.collection-bar-label span:last-child{font-weight:700;color:var(--green)}
.collection-bar{height:6px;border-radius:999px;background:#e5e7eb;overflow:hidden}
.collection-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,#16a34a,#4ade80);transition:width .6s ease}

/* ── Panels ── */
.panel{background:var(--surface);border:1px solid var(--border);border-radius:var(--r2);overflow:hidden;box-shadow:var(--shadow)}
.ph{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--border)}
.ph-left h3{font-size:.92rem;font-weight:700}
.ph-left p{font-size:.73rem;color:var(--text2);margin-top:2px}
.panel-link{font-size:.73rem;font-weight:700;text-decoration:none;padding:4px 12px;border-radius:8px;background:var(--bg2);border:1px solid var(--border);color:var(--text2);transition:all .15s}
.panel-link:hover{background:var(--accent);color:#fff}
.pbadge{font-size:.72rem;font-weight:600;background:var(--bg2);color:var(--text2);padding:3px 10px;border-radius:6px;border:1px solid var(--border)}
.pb{padding:1rem 1.25rem}

/* ── Profit chart pills ── */
.profit-pills{display:flex;gap:.6rem;flex-wrap:wrap;padding:.75rem 1.25rem;border-bottom:1px solid var(--border);background:#fafafa}
.ppill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:8px;font-size:.73rem;font-weight:600;border:1px solid var(--border)}
.ppill-g{background:var(--green-bg);border-color:#bbf7d0;color:var(--green)}
.ppill-r{background:#fee2e2;border-color:#fecaca;color:#dc2626}
.ppill-b{background:#eff6ff;border-color:#bfdbfe;color:#2563eb}
.ppill-p{background:#f5f3ff;border-color:#ddd6fe;color:#7c5df9}
.ppill .pv{font-weight:800;margin-left:2px}
.chart-legend{display:flex;gap:1.25rem;align-items:center;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.cleg{display:flex;align-items:center;gap:6px;font-size:.74rem;color:var(--text2)}
.cleg-line{width:18px;height:3px;border-radius:2px}
.cleg-dash{width:18px;height:0;border-top:2.5px dashed;border-radius:2px}

.chart-section{margin-bottom:1.25rem}

/* ── Bottom grid ── */
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:1.25rem}

/* ── People list ── */
.p-item{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border)}
.p-item:last-child{border-bottom:none}
.p-av{width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--border2)}
.p-ini{width:34px;height:34px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;color:#fff}
.pu{background:linear-gradient(135deg,#7c5df9,#4f8ef7)}
.pd{background:linear-gradient(135deg,#0891b2,#38bdf8)}
.p-inf{flex:1;min-width:0}
.p-nm{font-size:.83rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.p-sb{font-size:.7rem;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.p-st{font-size:.67rem;font-weight:700;padding:2px 7px;border-radius:5px;flex-shrink:0}
.son{background:var(--green-bg);color:var(--green)}
.sof{background:var(--bg2);color:var(--text3)}

/* ── Demo + catalog ── */
.leg-row{display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:.78rem}
.leg-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}
.leg-lbl{flex:1;color:var(--text2)}
.leg-val{font-weight:700;color:var(--text)}
.qs-strip{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--border)}
.qs-item{padding:1rem;border-right:1px solid var(--border);text-align:center}
.qs-item:last-child{border-right:none}
.qs-val{font-size:1.5rem;font-weight:800;letter-spacing:-.03em}
.qs-lbl{font-size:.7rem;color:var(--text2);margin-top:2px}

@media(max-width:1100px){
  .kpi-grid{grid-template-columns:repeat(2,1fr)}
  .grid-3{grid-template-columns:1fr}
}
</style>

<div class="dash">

  <!-- ── PAGE TITLE ── -->
  <div class="dash-title mt-5">
    <div><h1>Dashboard</h1><p>Car Rental Admin &mdash; Overview &amp; Analytics</p></div>
    <div class="live-badge"><span class="live-dot"></span><?= date('d M Y') ?></div>
  </div>

  <!-- ── KPI ROW 1 ── -->
  <div class="kpi-grid">
    <a href="view_cars.php" class="kpi-card" style="--kc:#4f8ef7">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#eff6ff;color:#4f8ef7"><i class="fas fa-car"></i></div>
        <span class="kpi-trend t-neu">fleet</span>
      </div>
      <div class="kpi-value"><?= $total_cars ?></div>
      <div class="kpi-label">Total Cars</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:100%;background:#4f8ef7"></div></div>
      <div class="kpi-sub"><?= $total_cars ?> vehicles in fleet</div>
    </a>

    <a href="user_master.php" class="kpi-card" style="--kc:#7c5df9">
      <?php $upct = $total_users ? round($active_users/$total_users*100) : 0; ?>
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#f5f3ff;color:#7c5df9"><i class="fas fa-users"></i></div>
        <span class="kpi-trend t-up"><?= $upct ?>% active</span>
      </div>
      <div class="kpi-value"><?= $total_users ?></div>
      <div class="kpi-label">Registered Users</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:<?= $upct ?>%;background:#7c5df9"></div></div>
      <div class="kpi-sub"><span class="g"><?= $active_users ?></span> active &nbsp;&middot;&nbsp; <span class="a"><?= $inactive_users ?></span> inactive</div>
    </a>

    <a href="driver_master.php" class="kpi-card" style="--kc:#0891b2">
      <?php $dpct = $total_drivers ? round($active_drivers/$total_drivers*100) : 0; ?>
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#ecfeff;color:#0891b2"><i class="fas fa-id-badge"></i></div>
        <span class="kpi-trend t-up"><?= $dpct ?>% active</span>
      </div>
      <div class="kpi-value"><?= $total_drivers ?></div>
      <div class="kpi-label">Registered Drivers</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:<?= $dpct ?>%;background:#0891b2"></div></div>
      <div class="kpi-sub"><span class="g"><?= $active_drivers ?></span> active &nbsp;&middot;&nbsp; <span class="a"><?= $inactive_drivers ?></span> inactive</div>
    </a>

    <a href="view_brands.php" class="kpi-card" style="--kc:#d97706">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#fffbeb;color:#d97706"><i class="fas fa-building"></i></div>
        <span class="kpi-trend t-neu">catalog</span>
      </div>
      <div class="kpi-value"><?= $total_brands ?></div>
      <div class="kpi-label">Total Brands</div>
      <div class="kpi-bar"><div class="kpi-fill" style="width:100%;background:#d97706"></div></div>
      <div class="kpi-sub"><span class="g"><?= $total_cats ?></span> categories &nbsp;&middot;&nbsp; <?= $total_models ?> models</div>
    </a>

  </div>

  <!-- ── KPI ROW 2 ── -->
  <div class="kpi2-grid">

    <!-- ── BOOKING RATIO CARD (replaces old Total Cars Booked card) ── -->
    <div class="booking-ratio-card">
      <div class="brc-top">
        <div>
          <div class="brc-total"><?= $total_bookings ?></div>
          <div class="brc-label">Total Bookings &mdash; Status Breakdown</div>
        </div>
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a">
          <i class="fas fa-calendar-check"></i>
        </div>
      </div>

      <!-- Stacked ratio bar -->
      <div class="ratio-bar">
        <?php foreach ($ratio_segments as $seg):
          $pct = $ratio_total > 0 ? ($seg['count'] / $ratio_total * 100) : 0;
          if ($pct < 0.5) continue; // skip invisible slivers
        ?>
        <div class="ratio-seg"
             style="width:<?= $pct ?>%;background:<?= $seg['color'] ?>"
             title="<?= $seg['label'] ?>: <?= $seg['count'] ?> (<?= round($pct,1) ?>%)">
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Legend -->
      <div class="ratio-legend">
        <?php foreach ($ratio_segments as $seg):
          $pct = $ratio_total > 0 ? round($seg['count'] / $ratio_total * 100, 1) : 0;
        ?>
        <div class="rl-item">
          <span class="rl-dot" style="background:<?= $seg['color'] ?>"></span>
          <div class="rl-info">
            <div class="rl-name"><?= $seg['label'] ?></div>
          </div>
          <div class="rl-right">
            <span class="rl-count" style="color:<?= $seg['color'] ?>"><?= $seg['count'] ?></span>
            <span class="rl-pct"><?= $pct ?>%</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- ── END BOOKING RATIO CARD ── -->

    <!-- ── PROFIT CARD (unchanged) ── -->
    <div class="kpi-card" style="--kc:#db2777;cursor:default">
      <div class="kpi-top">
        <div class="kpi-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-indian-rupee-sign"></i></div>
        <span class="kpi-trend t-up">revenue</span>
      </div>
      <div class="kpi-value" style="font-size:1.7rem">&#8377;<?= number_format($total_profit,0,'.',',') ?></div>
      <div class="kpi-label">Total Revenue (All Bookings)</div>

      <div class="profit-split">
        <div class="psplit-item received">
          <div class="psplit-label">
            <i class="fas fa-check-circle" style="font-size:.65rem"></i>
            Received
          </div>
          <div class="psplit-amount">&#8377;<?= number_format($amount_received,0,'.',',') ?></div>
          <div class="psplit-sub">via payment gateway</div>
        </div>
        <div class="psplit-item pending">
          <div class="psplit-label">
            <i class="fas fa-clock" style="font-size:.65rem"></i>
            Pending
          </div>
          <div class="psplit-amount">&#8377;<?= number_format($amount_pending,0,'.',',') ?></div>
          <div class="psplit-sub"><?= $unpaid_approved_count ?> approved · unpaid</div>
        </div>
      </div>

      <div class="collection-bar-wrap">
        <div class="collection-bar-label">
          <span>Collection Rate</span>
          <span><?= $collection_rate ?>%</span>
        </div>
        <div class="collection-bar">
          <div class="collection-fill" style="width:<?= $collection_rate ?>%"></div>
        </div>
      </div>
    </div>

  </div>

  <!-- ── CHARTS ROW: profit (left, wide) + brand (right, narrow) ── -->
  <div style="display:grid;grid-template-columns:1fr 380px;gap:1.25rem;margin-bottom:1.25rem">

    <!-- Profit Trend -->
    <div class="panel">
      <div class="ph">
        <div class="ph-left">
          <h3>&#128200; Profit Trend &mdash; Last 14 Days</h3>
          <p>Daily revenue generated from bookings</p>
        </div>
        <span class="pbadge">14 days</span>
      </div>

      <div class="profit-pills">
        <span class="ppill ppill-b">
          <i class="fas fa-calendar-week" style="font-size:.65rem"></i>
          Week 1 &nbsp;<span class="pv">&#8377;<?= number_format($week1) ?></span>
        </span>
        <span class="ppill ppill-p">
          <i class="fas fa-calendar-week" style="font-size:.65rem"></i>
          Week 2 &nbsp;<span class="pv">&#8377;<?= number_format($week2) ?></span>
        </span>
        <?php if ($profit_change >= 0): ?>
        <span class="ppill ppill-g">
          <i class="fas fa-arrow-trend-up" style="font-size:.65rem"></i>
          WoW &nbsp;<span class="pv">+<?= $profit_change ?>%</span>
        </span>
        <?php else: ?>
        <span class="ppill ppill-r">
          <i class="fas fa-arrow-trend-down" style="font-size:.65rem"></i>
          WoW &nbsp;<span class="pv"><?= $profit_change ?>%</span>
        </span>
        <?php endif; ?>
        <?php if ($peak_amount > 0): ?>
        <span class="ppill" style="background:#fffbeb;border-color:#fde68a;color:#92400e">
          <i class="fas fa-crown" style="font-size:.65rem;color:#d97706"></i>
          Peak &nbsp;<span class="pv"><?= $peak_label ?> &mdash; &#8377;<?= number_format($peak_amount) ?></span>
        </span>
        <?php endif; ?>
      </div>

      <div style="padding:.75rem 1rem 1rem;width:100%;height:260px;">
        <?= $profit_svg ?>
      </div>
    </div>

    <!-- Fleet by Brand -->
    <div class="panel">
      <div class="ph">
        <div class="ph-left">
          <h3>&#127775; Fleet by Brand</h3>
          <p>Cars per brand (top <?= count($brand_labels) ?>)</p>
        </div>
        <a href="view_brands.php" class="panel-link">All &rarr;</a>
      </div>
      <div style="padding:.75rem 1rem 1rem;width:100%;height:260px;">
        <?= $brand_svg ?>
      </div>
    </div>

  </div>

  <!-- ── BOTTOM ROW ── -->
  <div class="grid-3">

    <!-- Recent Users -->
    <div class="panel">
      <div class="ph">
        <div class="ph-left"><h3>Recent Users</h3><p>Latest registrations</p></div>
        <a href="user_master.php" class="panel-link">View all &rarr;</a>
      </div>
      <div class="pb" style="padding-top:.3rem;padding-bottom:.3rem">
        <?php if (empty($recent_users)): ?>
          <p style="text-align:center;padding:2rem;color:var(--text3);font-size:.82rem">No users yet</p>
        <?php else: foreach ($recent_users as $u):
          $init = strtoupper(substr($u['uname'],0,1)); $on = (bool)$u['status']; ?>
          <div class="p-item">
            <?php if (!empty($u['photo'])): ?>
              <img src="../../User/user_profile/<?= htmlspecialchars($u['photo']) ?>" class="p-av" alt="">
            <?php else: ?>
              <div class="p-ini pu"><?= $init ?></div>
            <?php endif; ?>
            <div class="p-inf">
              <div class="p-nm"><?= htmlspecialchars($u['uname']) ?></div>
              <div class="p-sb"><?= htmlspecialchars($u['email']) ?></div>
            </div>
            <span class="p-st <?= $on?'son':'sof' ?>"><?= $on?'Active':'Off' ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Recent Drivers -->
    <div class="panel">
      <div class="ph">
        <div class="ph-left"><h3>Recent Drivers</h3><p>Latest signups</p></div>
        <a href="driver_master.php" class="panel-link">View all &rarr;</a>
      </div>
      <div class="pb" style="padding-top:.3rem;padding-bottom:.3rem">
        <?php if (empty($recent_drivers)): ?>
          <p style="text-align:center;padding:2rem;color:var(--text3);font-size:.82rem">No drivers yet</p>
        <?php else: foreach ($recent_drivers as $d):
          $di = strtoupper(substr($d['driver_name'],0,1)); $on = (bool)$d['status']; ?>
          <div class="p-item">
            <?php if (!empty($d['profile_image'])): ?>
              <img src="../../Driver/images/driver_profile/<?= htmlspecialchars($d['profile_image']) ?>" class="p-av" alt="">
            <?php else: ?>
              <div class="p-ini pd"><?= $di ?></div>
            <?php endif; ?>
            <div class="p-inf">
              <div class="p-nm"><?= htmlspecialchars($d['driver_name']) ?></div>
              <div class="p-sb"><?= htmlspecialchars($d['driver_mobile']) ?></div>
            </div>
            <span class="p-st <?= $on?'son':'sof' ?>"><?= $on?'Active':'Off' ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Demographics + Catalog -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">
      <div class="panel">
        <div class="ph">
          <div class="ph-left"><h3>User Demographics</h3><p>Gender breakdown</p></div>
        </div>
        <div class="pb">
          <div style="display:flex;align-items:center;gap:1rem">
            <?= $donut_svg ?>
            <div style="flex:1">
              <?php
              $gtotal  = array_sum($gen_data) ?: 1;
              $gcolors = ['#4f8ef7','#db2777','#9ca3af','#d97706','#0891b2'];
              foreach ($gen_labels as $gi => $gl):
                $pct = round($gen_data[$gi]/$gtotal*100);
                $gc  = $gcolors[$gi % count($gcolors)];
              ?>
              <div class="leg-row">
                <span class="leg-dot" style="background:<?= $gc ?>"></span>
                <span class="leg-lbl"><?= htmlspecialchars($gl) ?></span>
                <span class="leg-val"><?= $pct ?>%</span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="ph"><div class="ph-left"><h3>Catalog Snapshot</h3></div></div>
        <div class="qs-strip">
          <div class="qs-item"><div class="qs-val" style="color:var(--accent)"><?= $total_brands ?></div><div class="qs-lbl">Brands</div></div>
          <div class="qs-item"><div class="qs-val" style="color:var(--amber)"><?= $total_models ?></div><div class="qs-lbl">Models</div></div>
          <div class="qs-item"><div class="qs-val" style="color:var(--cyan)"><?= $total_cats ?></div><div class="qs-lbl">Categories</div></div>
        </div>
      </div>
    </div>

  </div><!-- /grid-3 -->

</div><!-- /dash -->

<?php include("../components/footer.php"); ?>