<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Cars</title>

    <?php
    include('connect.php');
    session_name('admin_session');
    session_start();
    ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <?php include('../components/navbar.php'); ?>

    <style>
        :root {
            --accent:        #2563eb;
            --accent-hover:  #1d4ed8;
            --accent-glow:   rgba(37,99,235,.15);
            --gold:          #f59e0b;
            --gold-lt:       #fffbeb;
            --bg:            #f0f2f7;
            --surface:       #ffffff;
            --surface2:      #f7f8fc;
            --border:        #e4e7f0;
            --border-strong: #d0d5e8;
            --text:          #0f1623;
            --text2:         #4a5278;
            --muted:         #8b93b8;
            --radius:        16px;
            --radius-sm:     10px;
            --shadow-sm:     0 1px 4px rgba(15,22,35,.06);
            --shadow:        0 4px 16px rgba(15,22,35,.08);
            --shadow-lg:     0 16px 48px rgba(15,22,35,.14);
            --card-dark:     #131a2e;
            --card-dark2:    #1a2440;
            --transition:    all .28s cubic-bezier(.4,0,.2,1);
        }

        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: var(--bg); min-height: 100vh; }

        /* ═══════════════════════════════════════
           TOPBAR
        ═══════════════════════════════════════ */
        .topbar {
            position: sticky; top: 64px; z-index: 200;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 12px 28px;
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
        }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap i {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: var(--muted); font-size: 13px; pointer-events: none;
        }
        .search-input {
            width: 100%; padding: 9px 14px 9px 36px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; color: var(--text);
            background: var(--surface2);
            transition: var(--transition);
            font-family: 'DM Sans', sans-serif;
        }
        .search-input:focus {
            outline: none; border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 4px var(--accent-glow);
        }
        .search-input::placeholder { color: var(--muted); font-weight: 400; }

        .ctrl-select {
            padding: 9px 12px; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); font-size: 12.5px; font-weight: 600;
            color: var(--text); background: var(--surface2); cursor: pointer;
            font-family: 'DM Sans', sans-serif; transition: var(--transition);
        }
        .ctrl-select:focus { outline: none; border-color: var(--accent); }

        .filter-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 16px; border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); background: var(--surface2);
            font-size: 12.5px; font-weight: 700; color: var(--text2);
            cursor: pointer; transition: var(--transition); white-space: nowrap;
            position: relative;
        }
        .filter-btn:hover, .filter-btn.active {
            border-color: var(--accent); color: var(--accent);
            background: var(--accent-glow);
        }
        .filter-badge {
            display: none; position: absolute; top: -7px; right: -7px;
            background: #ef4444; color: #fff; border-radius: 50%;
            width: 18px; height: 18px; font-size: 9px; font-weight: 800;
            align-items: center; justify-content: center;
        }
        .filter-badge.show { display: flex; }

        .perpage-wrap { display: flex; align-items: center; gap: 7px; }
        .perpage-wrap label { font-size: 12px; font-weight: 600; color: var(--muted); white-space: nowrap; }

        /* ═══════════════════════════════════════
           FILTER PANEL
        ═══════════════════════════════════════ */
        .filter-panel {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 22px 28px; margin: 0 28px;
            display: none; animation: slideDown .22s ease;
        }
        .filter-panel.open { display: block; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px) }
            to   { opacity: 1; transform: translateY(0) }
        }

        .fg-label {
            font-family: 'Syne', sans-serif;
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .1em; color: var(--muted); margin-bottom: 9px;
        }
        .chips { display: flex; flex-wrap: wrap; gap: 7px; }
        .chip {
            padding: 5px 13px; border-radius: 20px; border: 1.5px solid var(--border);
            font-size: 12px; font-weight: 600; cursor: pointer;
            color: var(--text2); transition: var(--transition); user-select: none;
        }
        .chip:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-glow); }
        .chip.sel { background: var(--accent); border-color: var(--accent); color: #fff; }

        .filter-foot {
            display: flex; gap: 8px; justify-content: flex-end;
            margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);
        }
        .btn-fc {
            padding: 8px 20px; border-radius: 9px; font-size: 12.5px; font-weight: 700;
            cursor: pointer; border: 1.5px solid var(--border); background: #fff;
            color: var(--text); transition: var(--transition);
            font-family: 'DM Sans', sans-serif;
        }
        .btn-fa {
            padding: 8px 20px; border-radius: 9px; font-size: 12.5px; font-weight: 700;
            cursor: pointer; border: none; background: var(--accent); color: #fff;
            transition: var(--transition); font-family: 'DM Sans', sans-serif;
        }
        .btn-fa:hover { background: var(--accent-hover); transform: translateY(-1px); }

        /* ═══════════════════════════════════════
           SECTION HEADER
        ═══════════════════════════════════════ */
        .main-wrap { padding: 26px 28px; }
        .sec-hdr {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .sec-title {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 800; color: var(--text); letter-spacing: -.02em;
        }
        .sec-sub { font-size: 13px; color: var(--muted); margin-top: 2px; }
        .count-pill {
            background: var(--card-dark); color: rgba(255,255,255,.8);
            font-weight: 700; font-size: 12px; padding: 5px 14px; border-radius: 20px;
            font-family: 'Syne', sans-serif; letter-spacing: .02em;
        }

        /* ═══════════════════════════════════════
           CAR CARD — Premium Redesign
        ═══════════════════════════════════════ */
        .car-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 14px;
            overflow: hidden;
            transition: box-shadow .32s cubic-bezier(.4,0,.2,1),
                        border-color .32s, transform .22s cubic-bezier(.4,0,.2,1);
            animation: fadeUp .32s ease both;
        }
        .car-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--border-strong);
            transform: translateY(-3px);
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px) }
            to   { opacity: 1; transform: translateY(0) }
        }

        .card-row {
            display: flex; align-items: stretch;
            cursor: pointer; min-height: 116px;
        }

        /* ── LEFT: image panel ── */
        .card-left {
            position: relative;
            width: 210px; min-width: 210px; flex-shrink: 0;
            background: var(--card-dark);
            overflow: hidden;
        }

        /* Gradient vignette overlay */
        .card-left::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                to right,
                rgba(19,26,46,.55) 0%,
                transparent 55%
            );
            pointer-events: none; z-index: 1;
            transition: opacity .3s;
        }

        /* Subtle noise texture */
        .card-left::before {
            content: '';
            position: absolute; inset: 0; z-index: 2;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.04'/%3E%3C/svg%3E");
            pointer-events: none; opacity: .5;
        }

        .card-thumb {
            width: 100%; height: 100%; object-fit: cover; display: block;
            transition: transform .5s cubic-bezier(.4,0,.2,1), filter .32s;
            filter: brightness(.82) saturate(1.1);
        }
        .car-card:hover .card-thumb {
            transform: scale(1.07);
            filter: brightness(.95) saturate(1.15);
        }

        /* Status badge */
        .st-badge {
            position: absolute; top: 10px; left: 10px; z-index: 3;
            font-size: 9px; font-weight: 800; letter-spacing: .06em;
            padding: 3px 10px; border-radius: 20px;
            backdrop-filter: blur(4px);
        }
        .st-active  { background: rgba(22,163,74,.15); color: #4ade80; border: 1px solid rgba(74,222,128,.3); }
        .st-off     { background: rgba(255,255,255,.1); color: rgba(255,255,255,.55); border: 1px solid rgba(255,255,255,.15); }

        /* Car ID tag bottom-left */
        .card-id-tag {
            position: absolute; bottom: 10px; left: 10px; z-index: 3;
            font-size: 9px; font-weight: 700; letter-spacing: .04em;
            color: rgba(255,255,255,.45);
        }

        /* ── CENTER: info ── */
        .card-info {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column; justify-content: stretch;
            padding: 0;
        }

        /* Top dark header */
        .info-top {
            background: var(--card-dark);
            padding: 12px 18px 11px;
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            position: relative; overflow: hidden; flex: 1;
        }
        /* diagonal accent line */
        .info-top::before {
            content: '';
            position: absolute; top: 0; right: 0;
            width: 120px; height: 100%;
            background: linear-gradient(105deg, transparent 40%, rgba(37,99,235,.08) 100%);
            pointer-events: none;
        }

        .info-name-block { min-width: 0; }
        .car-name {
            font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 800; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            letter-spacing: -.01em;
        }
        .car-sub {
            font-size: 11.5px; font-weight: 500;
            color: rgba(255,255,255,.42); margin-top: 2px;
        }

        /* Price badges */
        .price-badges { display: flex; gap: 8px; flex-shrink: 0; }
        .pbadge {
            display: inline-flex; flex-direction: column; align-items: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px; padding: 5px 13px; min-width: 68px;
            transition: background .2s, border-color .2s;
        }
        .car-card:hover .pbadge {
            background: rgba(255,255,255,.1);
            border-color: rgba(255,255,255,.2);
        }
        .pbadge-lbl {
            font-size: 8.5px; font-weight: 700; color: rgba(255,255,255,.38);
            text-transform: uppercase; letter-spacing: .07em;
        }
        .pbadge-val {
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700; color: var(--gold);
            line-height: 1.25; margin-top: 2px;
        }

        /* Bottom pills strip */
        .info-bot {
            padding: 10px 18px;
            display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
            border-top: 1px solid var(--border);
        }

        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 11px; border-radius: 7px;
            font-size: 11px; font-weight: 600; flex-shrink: 0;
            transition: transform .18s, box-shadow .18s;
        }
        .pill:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); }
        .pill i { font-size: 10px; }

        .pill-plate    { background: #fefce8; color: #854d0e; border: 1px solid #fde68a; }
        .pill-petrol   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .pill-diesel   { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .pill-electric { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .pill-cng      { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .pill-hybrid   { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .pill-auto     { background: #fdf2f8; color: #9d174d; border: 1px solid #fbcfe8; }
        .pill-manual   { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
        .pill-seat     { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }

        /* ── BRAND + CATEGORY logos column ── */
        .card-logos {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 0;
            border-left: 1px solid var(--border);
            min-width: 80px; flex-shrink: 0;
            background: var(--surface2);
        }
        .logo-half {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; padding: 10px 12px; width: 100%; flex: 1;
            transition: background .2s;
        }
        .logo-half:first-child { border-bottom: 1px solid var(--border); }
        .logo-half:hover { background: #fff; }
        .logo-img {
            width: 34px; height: 34px; object-fit: contain;
            border-radius: 8px; border: 1px solid var(--border);
            padding: 3px; background: #fff; display: block;
            transition: transform .25s cubic-bezier(.4,0,.2,1), box-shadow .25s;
        }
        .logo-half:hover .logo-img {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        .logo-lbl {
            font-size: 8.5px; font-weight: 700; color: var(--muted);
            text-align: center; max-width: 68px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            text-transform: uppercase; letter-spacing: .05em;
        }

        /* ── ACTIONS column ── */
        .card-actions {
            display: flex; flex-direction: column; align-items: stretch; justify-content: center;
            gap: 6px; padding: 12px 12px;
            border-left: 1px solid var(--border);
            flex-shrink: 0; min-width: 96px;
            background: var(--surface);
        }
        .btn-act {
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 700;
            text-decoration: none; cursor: pointer; width: 100%;
            border: 1px solid transparent;
            transition: all .2s cubic-bezier(.4,0,.2,1);
            font-family: 'DM Sans', sans-serif; letter-spacing: .01em;
        }
        .btn-act i { font-size: 10px; }

        .btn-edit {
            background: #f1f5f9; color: #1e293b;
            border-color: #e2e8f0;
        }
        .btn-edit:hover {
            background: var(--card-dark); color: #fff;
            border-color: var(--card-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(19,26,46,.2);
        }
        .btn-del {
            background: #fff1f2; color: #be123c;
            border-color: #fecdd3;
        }
        .btn-del:hover {
            background: #be123c; color: #fff;
            border-color: #be123c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(190,18,60,.25);
        }
        .btn-enable {
            background: #f0fdf4; color: #166534;
            border-color: #bbf7d0;
        }
        .btn-enable:hover {
            background: #166534; color: #fff;
            border-color: #166534;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22,101,52,.25);
        }
        .btn-disable {
            background: #fffbeb; color: #92400e;
            border-color: #fde68a;
        }
        .btn-disable:hover {
            background: #92400e; color: #fff;
            border-color: #92400e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(146,64,14,.25);
        }

        /* ── EXPAND arrow ── */
        .expand-col {
            display: flex; align-items: center; justify-content: center;
            width: 34px; min-width: 34px;
            border-left: 1px solid var(--border);
            flex-shrink: 0; color: var(--muted);
            background: var(--surface);
            transition: background .18s, color .18s;
        }
        .expand-col:hover { background: var(--surface2); color: var(--text); }
        .arrow {
            font-size: 11px;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            display: block;
        }
        .expand-col.open .arrow { transform: rotate(180deg); }

        /* ═══════════════════════════════════════
           EXPANDED PANEL
        ═══════════════════════════════════════ */
        .card-exp {
            display: none;
            border-top: 1px solid var(--border);
            background: var(--surface2);
            padding: 18px 22px;
        }
        .card-exp.open {
            display: block;
            animation: expandIn .24s cubic-bezier(.4,0,.2,1);
        }
        @keyframes expandIn {
            from { opacity: 0; transform: translateY(-6px) }
            to   { opacity: 1; transform: translateY(0) }
        }

        .exp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 14px;
        }
        .exp-item {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            transition: border-color .2s;
        }
        .exp-item:hover { border-color: var(--border-strong); }
        .exp-lbl {
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: var(--muted); margin-bottom: 4px;
        }
        .exp-val {
            font-family: 'Syne', sans-serif;
            font-size: 13.5px; font-weight: 700; color: var(--text);
        }
        .exp-val.money { color: #166534; }
        .exp-desc {
            margin-top: 14px; padding: 12px 14px;
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 12.5px; color: var(--text2); line-height: 1.7;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .exp-desc i { color: var(--accent); margin-top: 2px; flex-shrink: 0; }

        /* ═══════════════════════════════════════
           PAGINATION
        ═══════════════════════════════════════ */
        .pag-wrap {
            display: flex; justify-content: center; align-items: center;
            gap: 5px; flex-wrap: wrap; margin-top: 26px;
        }
        .pg-btn {
            padding: 7px 14px; border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            background: var(--surface); font-size: 12.5px; font-weight: 700;
            color: var(--accent); cursor: pointer;
            transition: var(--transition);
            font-family: 'DM Sans', sans-serif;
        }
        .pg-btn:hover { border-color: var(--accent); background: var(--accent-glow); }
        .pg-btn.pg-active {
            background: var(--accent); border-color: var(--accent);
            color: #fff; pointer-events: none;
            box-shadow: 0 4px 12px var(--accent-glow);
        }
        .pg-btn.pg-disabled { opacity: .3; pointer-events: none; }
        .pg-dots { font-size: 12.5px; font-weight: 600; color: var(--muted); padding: 0 4px; }

        /* Toast */
        .toast-custom {
            position: fixed; bottom: 28px; right: 28px; z-index: 9999;
            background: var(--card-dark); color: #fff;
            padding: 12px 20px; border-radius: 12px;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 12px 32px rgba(0,0,0,.25);
            transform: translateY(80px) scale(.94); opacity: 0;
            transition: all .3s cubic-bezier(.4,0,.2,1);
            pointer-events: none; max-width: 320px;
        }
        .toast-custom.show { transform: translateY(0) scale(1); opacity: 1; }
        .toast-icon { font-size: 16px; }

        /* Empty state */
        .empty-state { text-align: center; padding: 70px 20px; display: none; }
        .empty-state i { font-size: 48px; color: #d1d9e6; margin-bottom: 16px; display: block; }
        .empty-state p { font-weight: 700; font-size: 14px; color: var(--muted); }

        @media (max-width: 768px) {
            .card-left { width: 110px; min-width: 110px; }
            .card-logos { display: none; }
            .card-actions { min-width: 80px; padding: 10px 8px; }
            .price-badges { display: none; }
            .topbar { padding: 10px 16px; }
            .main-wrap { padding: 16px; }
            .filter-panel { margin: 0 16px; }
        }
    </style>
</head>
<body>

<?php
$allQ = mysqli_query($con, "
    SELECT
        c.car_id,
        b.brand_name, b.brand_logo,
        m.model_name,
        cat.category_id, cat.category_name, cat.category_image,
        c.car_display_name, c.car_number_plate,
        c.gear_type, c.fuel_type, c.seating_capacity,
        c.car_description, c.primary_image, c.is_enabled,
        p.price_per_hour, p.price_per_day,
        p.late_fee_per_hour, p.security_deposit, p.effective_from
    FROM car_master c
    INNER JOIN brand_master b      ON c.brand_id   = b.brand_id
    INNER JOIN model_master m      ON c.model_id   = m.model_id
    LEFT  JOIN category_master cat ON m.category_id = cat.category_id
    INNER JOIN car_pricing p       ON c.car_id     = p.car_id
    ORDER BY c.car_id ASC
");
$allCars = [];
while ($r = mysqli_fetch_assoc($allQ)) $allCars[] = $r;

$brandRows = [];
$bq = mysqli_query($con, "SELECT brand_name FROM brand_master WHERE is_active=1 ORDER BY brand_name");
while ($r = mysqli_fetch_assoc($bq)) $brandRows[] = $r['brand_name'];

$catRows = [];
$cq = mysqli_query($con, "SELECT category_id, category_name FROM category_master WHERE is_active=1 ORDER BY category_name");
while ($r = mysqli_fetch_assoc($cq)) $catRows[] = $r;

$catTypes = [];
foreach ($catRows as $cr) {
    $parts = preg_split('/\s+with\s+/i', $cr['category_name']);
    $type  = trim($parts[0]);
    if ($type && !in_array($type, $catTypes)) $catTypes[] = $type;
}
sort($catTypes);

$fuelList = array_values(array_unique(array_map(fn($r) => $r['fuel_type'], $allCars)));
sort($fuelList);
$gearList = array_values(array_unique(array_map(fn($r) => $r['gear_type'], $allCars)));
sort($gearList);
$seatList = array_values(array_unique(array_map(fn($r) => (int)$r['seating_capacity'], $allCars)));
sort($seatList);
?>

<!-- ══ TOPBAR ══ -->
<div class="topbar" style="margin-top:64px;">
    <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" class="search-input"
               placeholder="Search name, brand, model, plate…">
    </div>

    <select id="sortSelect" class="ctrl-select">
        <option value="">Sort By</option>
        <optgroup label="Name">
            <option value="name-asc">Name A → Z</option>
            <option value="name-desc">Name Z → A</option>
        </optgroup>
        <optgroup label="Price / Hour">
            <option value="hour-asc">Price/Hr ↑ Low → High</option>
            <option value="hour-desc">Price/Hr ↓ High → Low</option>
        </optgroup>
        <optgroup label="Price / Day">
            <option value="day-asc">Price/Day ↑ Low → High</option>
            <option value="day-desc">Price/Day ↓ High → Low</option>
        </optgroup>
        <optgroup label="Seats">
            <option value="seat-asc">Seats ↑ Fewest → Most</option>
            <option value="seat-desc">Seats ↓ Most → Fewest</option>
        </optgroup>
    </select>

    <button class="filter-btn" id="filterBtn">
        <i class="fas fa-sliders-h"></i> Filters
        <span class="filter-badge" id="filterBadge"></span>
    </button>

    <div class="perpage-wrap">
        <label for="perPageSelect">Show:</label>
        <select id="perPageSelect" class="ctrl-select">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
</div>

<!-- ══ FILTER PANEL ══ -->
<div class="filter-panel" id="filterPanel">
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <div class="fg-label"><i class="fas fa-tag" style="margin-right:5px;opacity:.6;"></i>Brand</div>
            <div class="chips">
                <?php foreach ($brandRows as $b): ?>
                <span class="chip" data-key="brand"
                      data-val="<?= htmlspecialchars(strtolower($b)) ?>"
                      onclick="this.classList.toggle('sel')">
                    <?= htmlspecialchars($b) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="fg-label"><i class="fas fa-car" style="margin-right:5px;opacity:.6;"></i>Category Type</div>
            <div class="chips">
                <?php foreach ($catTypes as $ct): ?>
                <span class="chip" data-key="cattype"
                      data-val="<?= htmlspecialchars(strtolower($ct)) ?>"
                      onclick="this.classList.toggle('sel')">
                    <?= htmlspecialchars($ct) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="fg-label"><i class="fas fa-gas-pump" style="margin-right:5px;opacity:.6;"></i>Fuel Type</div>
            <div class="chips">
                <?php foreach ($fuelList as $f): ?>
                <span class="chip" data-key="fuel"
                      data-val="<?= htmlspecialchars(strtolower($f)) ?>"
                      onclick="this.classList.toggle('sel')">
                    <?= htmlspecialchars(ucfirst($f)) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="fg-label"><i class="fas fa-cog" style="margin-right:5px;opacity:.6;"></i>Gear Type</div>
            <div class="chips">
                <?php foreach ($gearList as $g): ?>
                <span class="chip" data-key="gear"
                      data-val="<?= htmlspecialchars(strtolower($g)) ?>"
                      onclick="this.classList.toggle('sel')">
                    <?= htmlspecialchars(ucfirst($g)) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="fg-label"><i class="fas fa-user-friends" style="margin-right:5px;opacity:.6;"></i>Seater</div>
            <div class="chips">
                <?php foreach ($seatList as $s): ?>
                <span class="chip" data-key="seater"
                      data-val="<?= $s ?>"
                      onclick="this.classList.toggle('sel')">
                    <?= $s ?> Seats
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="filter-foot">
        <button class="btn-fc" id="clearFilters">Clear All</button>
        <button class="btn-fa" id="applyFilters">Apply Filters</button>
    </div>
</div>

<!-- ══ MAIN ══ -->
<div class="main-wrap">
    <div class="sec-hdr">
        <div>
            <div class="sec-title">Fleet Management</div>
            <div class="sec-sub">Manage your rental vehicles</div>
        </div>
        <span class="count-pill" id="countPill">—</span>
    </div>

    <div id="carsList"></div>

    <div class="empty-state" id="emptyState">
        <i class="fas fa-car-side"></i>
        <p>No cars match your search or filters.</p>
    </div>

    <div class="pag-wrap" id="pagWrap"></div>
</div>

<!-- Toast notification -->
<div class="toast-custom" id="toastEl">
    <span class="toast-icon" id="toastIcon"></span>
    <span id="toastMsg"></span>
</div>

<?php include('../components/footer.php'); ?>

<!-- ══ ALL DATA IN JS ══ -->
<script>
const ALL_CARS = <?php
    $out = [];
    foreach ($allCars as $r) {
        $out[] = [
            'id'       => (int)$r['car_id'],
            'name'     => $r['car_display_name'],
            'brand'    => $r['brand_name'],
            'blogo'    => $r['brand_logo'] ?? '',
            'model'    => $r['model_name'],
            'category' => $r['category_name'] ?? '',
            'catimg'   => $r['category_image'] ?? '',
            'plate'    => $r['car_number_plate'],
            'fuel'     => $r['fuel_type'],
            'gear'     => $r['gear_type'],
            'seats'    => (int)$r['seating_capacity'],
            'img'      => $r['primary_image'] ?? '',
            'on'       => (int)$r['is_enabled'],
            'hour'     => (float)($r['price_per_hour'] ?? 0),
            'day'      => (float)($r['price_per_day'] ?? 0),
            'late'     => (float)($r['late_fee_per_hour'] ?? 0),
            'dep'      => (float)($r['security_deposit'] ?? 0),
            'eff'      => $r['effective_from'] ?? '',
            'desc'     => $r['car_description'] ?? '',
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>;

/* ── State ── */
let filtered      = [...ALL_CARS];
let curPage       = 1;
let perPage       = 10;
let sortBy        = '';
let query         = '';
let activeFilters = {};

/* ── Helpers ── */
const INR = n => '₹' + Number(n).toLocaleString('en-IN');
const ESC = s => {
    const d = document.createElement('div');
    d.textContent = String(s ?? '');
    return d.innerHTML;
};

function fuelMeta(f) {
    const fl = f.toLowerCase();
    const map = {
        petrol:   ['pill-petrol',   'fa-fire'],
        diesel:   ['pill-diesel',   'fa-leaf'],
        electric: ['pill-electric', 'fa-bolt'],
        cng:      ['pill-cng',      'fa-wind'],
        hybrid:   ['pill-hybrid',   'fa-seedling'],
    };
    return map[fl] || ['pill-plate', 'fa-gas-pump'];
}
function gearMeta(g) {
    return g.toLowerCase().includes('auto')
        ? ['pill-auto',   'fa-robot']
        : ['pill-manual', 'fa-cog'];
}

/* ── Toast ── */
function showToast(msg, icon) {
    const el = document.getElementById('toastEl');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').textContent = icon;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3000);
}

/* ── Toggle car status (AJAX) ── */
function toggleCarStatus(id, btn) {
    const card   = document.getElementById('card-' + id);
    const badge  = card.querySelector('.st-badge');
    const isOn   = badge.classList.contains('st-active');
    const newVal = isOn ? 0 : 1;

    btn.style.opacity = '.6';
    btn.style.pointerEvents = 'none';

    fetch(`toggle_car.php?id=${id}&status=${newVal}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (isOn) {
                    badge.className = 'st-badge st-off';
                    badge.textContent = 'Disabled';
                    btn.className = 'btn-act btn-enable';
                    btn.innerHTML = '<i class="fas fa-toggle-off"></i> Enable';
                    showToast('Car disabled successfully', '🔴');
                } else {
                    badge.className = 'st-badge st-active';
                    badge.textContent = 'Active';
                    btn.className = 'btn-act btn-disable';
                    btn.innerHTML = '<i class="fas fa-toggle-on"></i> Disable';
                    showToast('Car enabled successfully', '🟢');
                }
            } else {
                showToast('Update failed. Please try again.', '⚠️');
            }
        })
        .catch(() => showToast('Network error.', '⚠️'))
        .finally(() => {
            btn.style.opacity = '';
            btn.style.pointerEvents = '';
        });
}

/* ── CORE: filter + sort ── */
function applyAll() {
    const q = query.toLowerCase();

    filtered = ALL_CARS.filter(c => {
        if (q) {
            const hay = (c.name + ' ' + c.brand + ' ' + c.model + ' ' + c.category + ' ' + c.plate).toLowerCase();
            if (!hay.includes(q)) return false;
        }
        for (const [key, vals] of Object.entries(activeFilters)) {
            if (!vals.length) continue;
            let cv;
            switch (key) {
                case 'brand':   cv = c.brand.toLowerCase(); break;
                case 'fuel':    cv = c.fuel.toLowerCase();  break;
                case 'gear':    cv = c.gear.toLowerCase();  break;
                case 'seater':  cv = String(c.seats);       break;
                case 'cattype':
                    const catLower = c.category.toLowerCase();
                    if (!vals.some(v => catLower.startsWith(v))) return false;
                    continue;
                default: continue;
            }
            if (!vals.includes(cv)) return false;
        }
        return true;
    });

    if (sortBy) {
        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'name-asc':  return a.name.localeCompare(b.name);
                case 'name-desc': return b.name.localeCompare(a.name);
                case 'hour-asc':  return a.hour - b.hour;
                case 'hour-desc': return b.hour - a.hour;
                case 'day-asc':   return a.day  - b.day;
                case 'day-desc':  return b.day  - a.day;
                case 'seat-asc':  return a.seats - b.seats;
                case 'seat-desc': return b.seats - a.seats;
            }
            return 0;
        });
    }

    curPage = 1;
    renderPage();
}

/* ── Render page slice ── */
function renderPage() {
    const total = filtered.length;
    const start = (curPage - 1) * perPage;
    const slice = filtered.slice(start, start + perPage);

    document.getElementById('countPill').textContent = total + ' Car' + (total !== 1 ? 's' : '');
    document.getElementById('emptyState').style.display = total === 0 ? 'block' : 'none';

    const list = document.getElementById('carsList');
    list.innerHTML = '';

    slice.forEach((c, idx) => {
        const [fClass, fIcon] = fuelMeta(c.fuel);
        const [gClass, gIcon] = gearMeta(c.gear);

        const card = document.createElement('div');
        card.className = 'car-card';
        card.id = 'card-' + c.id;
        card.style.animationDelay = (idx * 0.04) + 's';

        card.innerHTML = `
<div class="card-row" onclick="toggleExp(${c.id})">

    <!-- LEFT: image -->
    <div class="card-left">
        <img class="card-thumb"
             src="./images/car_images/${ESC(c.img)}"
             alt="${ESC(c.name)}"
             onerror="this.src='./images/car_images/default.png'">
        <span class="st-badge ${c.on ? 'st-active' : 'st-off'}">${c.on ? 'Active' : 'Disabled'}</span>
        <span class="card-id-tag">#${c.id}</span>
    </div>

    <!-- CENTER: info -->
    <div class="card-info">
        <div class="info-top">
            <div class="info-name-block">
                <div class="car-name">${ESC(c.name)}</div>
                <div class="car-sub">${ESC(c.model)}&ensp;·&ensp;${ESC(c.brand)}</div>
            </div>
            <div class="price-badges">
                <div class="pbadge">
                    <span class="pbadge-lbl">Per Hour</span>
                    <span class="pbadge-val">${INR(c.hour)}</span>
                </div>
                <div class="pbadge">
                    <span class="pbadge-lbl">Per Day</span>
                    <span class="pbadge-val">${INR(c.day)}</span>
                </div>
            </div>
        </div>
        <div class="info-bot">
            <span class="pill pill-plate"><i class="fas fa-id-card"></i>${ESC(c.plate)}</span>
            <span class="pill ${fClass}"><i class="fas ${fIcon}"></i>${ESC(c.fuel)}</span>
            <span class="pill ${gClass}"><i class="fas ${gIcon}"></i>${ESC(c.gear)}</span>
            <span class="pill pill-seat"><i class="fas fa-user-friends"></i>${c.seats} Seats</span>
        </div>
    </div>

    <!-- Brand + Category logo column -->
    <div class="card-logos" onclick="event.stopPropagation()">
        <div class="logo-half">
            ${c.blogo
                ? `<img class="logo-img"
                        src="./images/brand_logos/${ESC(c.blogo)}"
                        alt="${ESC(c.brand)}"
                        onerror="this.style.display='none'">`
                : `<div style="width:34px;height:34px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#94a3b8;">${ESC(c.brand[0])}</div>`
            }
            <div class="logo-lbl">${ESC(c.brand)}</div>
        </div>
        ${c.catimg ? `
        <div class="logo-half">
            <img class="logo-img"
                 src="./images/category_images/${ESC(c.catimg)}"
                 alt="${ESC(c.category)}"
                 onerror="this.parentElement.style.display='none'">
            <div class="logo-lbl">${ESC(c.category)}</div>
        </div>` : ''}
    </div>

    <!-- Actions -->
    <div class="card-actions" onclick="event.stopPropagation()">
        <a href="edit_car.php?id=${c.id}" class="btn-act btn-edit">
            <i class="fas fa-edit"></i> Edit
        </a>
        <button class="btn-act ${c.on ? 'btn-disable' : 'btn-enable'}"
                onclick="toggleCarStatus(${c.id}, this)">
            <i class="fas ${c.on ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
            ${c.on ? 'Disable' : 'Enable'}
        </button>
        <a href="delete_car.php?id=${c.id}" class="btn-act btn-del"
           onclick="return confirm('Delete this car?')">
            <i class="fas fa-trash"></i> Delete
        </a>
    </div>

    <!-- Expand arrow -->
    <div class="expand-col" id="ebtn-${c.id}">
        <i class="fas fa-chevron-down arrow"></i>
    </div>
</div>

<!-- Expanded details -->
<div class="card-exp" id="eexp-${c.id}">
    <div class="exp-grid">
        <div class="exp-item">
            <div class="exp-lbl">Category</div>
            <div class="exp-val">${ESC(c.category) || '—'}</div>
        </div>
        <div class="exp-item">
            <div class="exp-lbl">Brand</div>
            <div class="exp-val">${ESC(c.brand)}</div>
        </div>
        <div class="exp-item">
            <div class="exp-lbl">Late Fee / Hr</div>
            <div class="exp-val money">${INR(c.late)}</div>
        </div>
        <div class="exp-item">
            <div class="exp-lbl">Security Deposit</div>
            <div class="exp-val money">${INR(c.dep)}</div>
        </div>
        <div class="exp-item">
            <div class="exp-lbl">Effective From</div>
            <div class="exp-val">${ESC(c.eff) || '—'}</div>
        </div>
        <div class="exp-item">
            <div class="exp-lbl">Status</div>
            <div class="exp-val" style="color:${c.on ? '#166534' : '#64748b'}">${c.on ? 'Active' : 'Disabled'}</div>
        </div>
    </div>
    ${c.desc ? `
    <div class="exp-desc">
        <i class="fas fa-info-circle"></i>
        <span>${ESC(c.desc)}</span>
    </div>` : ''}
</div>`;

        list.appendChild(card);
    });

    buildPagination(total);
}

/* ── Expand / collapse ── */
function toggleExp(id) {
    const exp = document.getElementById('eexp-' + id);
    const btn = document.getElementById('ebtn-' + id);
    if (!exp) return;
    const wasOpen = exp.classList.contains('open');
    document.querySelectorAll('.card-exp.open').forEach(e => e.classList.remove('open'));
    document.querySelectorAll('.expand-col.open').forEach(b => b.classList.remove('open'));
    if (!wasOpen) { exp.classList.add('open'); btn.classList.add('open'); }
}

/* ── Pagination ── */
function buildPagination(total) {
    const totalPages = Math.ceil(total / perPage);
    const wrap = document.getElementById('pagWrap');
    wrap.innerHTML = '';
    if (totalPages <= 1) return;

    const go = p => {
        curPage = p;
        renderPage();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const mkBtn = (html, page, extra = '') => {
        const b = document.createElement('button');
        b.className = 'pg-btn ' + extra;
        b.innerHTML = html;
        b.onclick = () => go(page);
        return b;
    };

    wrap.appendChild(mkBtn('<i class="fas fa-chevron-left"></i>', curPage - 1,
        curPage === 1 ? 'pg-disabled' : ''));

    let pages = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (curPage > 3) pages.push('…');
        for (let i = Math.max(2, curPage - 1); i <= Math.min(totalPages - 1, curPage + 1); i++) pages.push(i);
        if (curPage < totalPages - 2) pages.push('…');
        pages.push(totalPages);
    }

    pages.forEach(p => {
        if (p === '…') {
            const sp = document.createElement('span');
            sp.className = 'pg-dots'; sp.textContent = '…';
            wrap.appendChild(sp);
        } else {
            wrap.appendChild(mkBtn(p, p, p === curPage ? 'pg-active' : ''));
        }
    });

    wrap.appendChild(mkBtn('<i class="fas fa-chevron-right"></i>', curPage + 1,
        curPage === totalPages ? 'pg-disabled' : ''));
}

/* ── Events ── */
document.getElementById('searchInput').addEventListener('input', function () {
    query = this.value.trim();
    applyAll();
});
document.getElementById('sortSelect').addEventListener('change', function () {
    sortBy = this.value;
    applyAll();
});
document.getElementById('perPageSelect').addEventListener('change', function () {
    perPage = parseInt(this.value, 10);
    applyAll();
});
document.getElementById('filterBtn').addEventListener('click', () => {
    document.getElementById('filterPanel').classList.toggle('open');
});
document.getElementById('applyFilters').addEventListener('click', () => {
    activeFilters = {};
    document.querySelectorAll('.chip.sel').forEach(chip => {
        const k = chip.dataset.key;
        const v = chip.dataset.val;
        if (!activeFilters[k]) activeFilters[k] = [];
        if (!activeFilters[k].includes(v)) activeFilters[k].push(v);
    });
    document.getElementById('filterPanel').classList.remove('open');
    updateBadge();
    applyAll();
});
document.getElementById('clearFilters').addEventListener('click', () => {
    document.querySelectorAll('.chip.sel').forEach(c => c.classList.remove('sel'));
    activeFilters = {};
    updateBadge();
    applyAll();
});

function updateBadge() {
    const n = Object.values(activeFilters).reduce((s, a) => s + a.length, 0);
    const badge = document.getElementById('filterBadge');
    const btn   = document.getElementById('filterBtn');
    badge.textContent = n;
    badge.classList.toggle('show', n > 0);
    btn.classList.toggle('active', n > 0);
}

/* ── Boot ── */
applyAll();
</script>
</body>
</html>