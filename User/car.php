<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── PREMIUM CAR CARDS ── */
        .car-wrap {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.32s cubic-bezier(.34,1.56,.64,1), box-shadow 0.32s ease;
            position: relative;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
        }
        .car-wrap:hover {
            transform: translateY(-8px) scale(1.013);
            box-shadow: 0 20px 48px rgba(46,204,113,0.13), 0 6px 18px rgba(0,0,0,0.09);
        }
        .car-wrap::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: #2ecc71;
            z-index: 3;
        }
        .car-wrap::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 3px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.7), transparent);
            z-index: 4;
            transition: left 0.55s ease;
        }
        .car-wrap:hover::after { left: 100%; }
        .car-wrap .img-wrap img,
        .car-wrap .img.rounded {
            width: 100% !important;
            max-width: 260px !important;
            height: 130px !important;
            object-fit: contain !important;
            filter: drop-shadow(0 10px 18px rgba(0,0,0,0.14));
            transition: transform 0.35s cubic-bezier(.34,1.56,.64,1);
            position: relative;
            z-index: 1;
            border-radius: 0 !important;
            background: none !important;
        }
        .car-wrap:hover .img.rounded { transform: translateX(6px) scale(1.06); }
        .brand-badge {
            position: absolute;
            top: 14px; left: 14px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(46,204,113,0.25);
            border-radius: 6px;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #27ae60;
            padding: 3px 9px;
            z-index: 2;
            backdrop-filter: blur(4px);
        }
        .price-badge {
            position: absolute;
            top: 14px; right: 14px;
            background: #2ecc71;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            color: #fff;
            padding: 4px 10px;
            z-index: 2;
            box-shadow: 0 3px 10px rgba(46,204,113,0.35);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        .car-wrap:hover .price-badge { transform: scale(1.07); }
        .car-wrap .text {
            padding: 16px 18px 18px !important;
            flex: 1;
            display: flex;
            flex-direction: column;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .car-wrap .text h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .car-specs-strip {
            display: flex;
            border: 1px solid rgba(0,0,0,0.07);
            border-radius: 9px;
            overflow: hidden;
            margin-bottom: 14px;
            background: #f8fffe;
        }
        .car-spec-item {
            flex: 1;
            text-align: center;
            padding: 7px 4px;
            border-right: 1px solid rgba(0,0,0,0.07);
            transition: background 0.18s;
        }
        .car-spec-item:last-child { border-right: none; }
        .car-wrap:hover .car-spec-item { background: #f0faf4; }
        .car-spec-val { display: block; font-size: 0.78rem; font-weight: 700; color: #1a1a2e; }
        .car-spec-lbl { display: block; font-size: 0.58rem; color: #999; text-transform: uppercase; letter-spacing: 0.06em; }
        .car-wrap .btn-primary {
            background: #2ecc71 !important;
            border: 2px solid #2ecc71 !important;
            border-radius: 10px !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            padding: 11px 0 !important;
            transition: all 0.25s !important;
        }
        .car-wrap .btn-primary:hover {
            background: #27ae60 !important;
            border-color: #27ae60 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 22px rgba(46,204,113,0.35) !important;
        }

        /* ═══════════════════════════════════════════════
           FILTER PANEL
        ═══════════════════════════════════════════════ */
        .filter-panel {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(46,204,113,0.12);
            padding: 24px 28px 20px;
            margin-bottom: 40px;
            position: relative;
            overflow: visible;
        }
        .filter-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            border-radius: 20px 20px 0 0;
            background: linear-gradient(90deg, #2ecc71, #27ae60, #1abc9c);
        }

        /* Search */
        .filter-search-wrap {
            position: relative;
            margin-bottom: 16px;
        }
        .filter-search-wrap .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #2ecc71;
            font-size: 1rem;
            z-index: 2;
            pointer-events: none;
        }
        .filter-search-wrap input {
            width: 100%;
            padding: 14px 50px 14px 48px;
            border: 2px solid rgba(46,204,113,0.2);
            border-radius: 50px;
            font-size: 0.92rem;
            font-family: 'DM Sans', sans-serif;
            color: #1a1a2e;
            background: #f8fffe;
            transition: border-color 0.25s, box-shadow 0.25s;
            outline: none;
        }
        .filter-search-wrap input:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 4px rgba(46,204,113,0.1);
            background: #fff;
        }
        .filter-search-wrap input::placeholder { color: #aaa; }
        .filter-search-wrap .clear-search {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            cursor: pointer;
            font-size: 0.85rem;
            display: none;
            transition: color 0.2s;
            z-index: 2;
        }
        .filter-search-wrap .clear-search:hover { color: #e74c3c; }

        /* Dropdown row */
        .filter-dropdowns-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        /* Each dropdown wrapper */
        .filter-dropdown-wrap {
            position: relative;
        }

        /* Dropdown trigger button */
        .filter-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border: 2px solid rgba(46,204,113,0.2);
            border-radius: 50px;
            background: #f8fffe;
            font-size: 0.8rem;
            font-weight: 600;
            color: #444;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.22s;
            white-space: nowrap;
            user-select: none;
        }
        .filter-dropdown-btn i.fa-chevron-down {
            font-size: 0.65rem;
            color: #2ecc71;
            transition: transform 0.22s;
        }
        .filter-dropdown-btn i:first-child { color: #2ecc71; }
        .filter-dropdown-btn:hover,
        .filter-dropdown-btn.open {
            border-color: #2ecc71;
            background: #f0faf4;
        }
        .filter-dropdown-btn.has-active {
            border-color: #2ecc71;
            background: #2ecc71;
            color: #fff;
        }
        .filter-dropdown-btn.has-active i { color: #fff !important; }
        .filter-dropdown-btn.open i.fa-chevron-down { transform: rotate(180deg); }

        /* Dropdown panel */
        .filter-dropdown-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            background: #fff;
            border: 1.5px solid rgba(46,204,113,0.2);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            padding: 16px;
            z-index: 999;
            min-width: 220px;
            max-width: 340px;
            display: none;
            animation: dropIn 0.18s ease;
        }
        .filter-dropdown-panel.open { display: block; }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Brand chips inside dropdown */
        .brand-chip-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            max-height: 240px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #2ecc71 #f0faf4;
        }
        .brand-chip-grid::-webkit-scrollbar { width: 4px; }
        .brand-chip-grid::-webkit-scrollbar-thumb { background: #2ecc71; border-radius: 4px; }

        .brand-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 8px 6px;
            border-radius: 10px;
            border: 1.5px solid rgba(46,204,113,0.15);
            background: #f8fffe;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .brand-chip:hover { border-color: #2ecc71; background: #f0faf4; }
        .brand-chip.active { border-color: #2ecc71; background: linear-gradient(135deg,#f0faf4,#e8f8ef); }
        .brand-chip img {
            width: 38px; height: 26px;
            object-fit: contain;
            filter: grayscale(20%);
            transition: filter 0.2s;
        }
        .brand-chip:hover img, .brand-chip.active img { filter: none; }
        .brand-chip span {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #555;
        }
        .brand-chip.active span { color: #27ae60; }

        /* Category chips inside dropdown */
        .cat-chip-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 240px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #2ecc71 #f0faf4;
        }
        .cat-chip-list::-webkit-scrollbar { width: 4px; }
        .cat-chip-list::-webkit-scrollbar-thumb { background: #2ecc71; border-radius: 4px; }

        .cat-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1.5px solid rgba(46,204,113,0.15);
            background: #f8fffe;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
            font-weight: 600;
            color: #444;
        }
        .cat-chip img {
            width: 32px; height: 22px;
            object-fit: contain;
            border-radius: 4px;
        }
        .cat-chip .cat-ico {
            width: 32px; height: 32px;
            background: rgba(46,204,113,0.1);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #2ecc71; font-size: 0.85rem;
            flex-shrink: 0;
        }
        .cat-chip:hover { border-color: #2ecc71; background: #f0faf4; }
        .cat-chip.active { border-color: #2ecc71; background: #2ecc71; color: #fff; }
        .cat-chip.active .cat-ico { background: rgba(255,255,255,0.25); color: #fff; }

        /* Icon pills inside dropdowns */
        .icon-pill-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .icon-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1.5px solid rgba(46,204,113,0.15);
            background: #f8fffe;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
            font-weight: 600;
            color: #444;
        }
        .icon-pill i { color: #2ecc71; font-size: 0.9rem; width: 16px; text-align: center; transition: color 0.2s; }
        .icon-pill:hover { border-color: #2ecc71; background: #f0faf4; }
        .icon-pill.active { border-color: #2ecc71; background: #2ecc71; color: #fff; }
        .icon-pill.active i { color: #fff; }

        /* Filter footer */
        .filter-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid rgba(46,204,113,0.1);
            flex-wrap: wrap;
            gap: 10px;
        }
        .filter-results-count {
            font-size: 0.82rem;
            color: #888;
            font-family: 'DM Sans', sans-serif;
        }
        .filter-results-count strong { color: #1a1a2e; }

        .btn-clear-filters {
            font-size: 0.78rem;
            font-weight: 600;
            color: #e74c3c;
            background: none;
            border: 1.5px solid rgba(231,76,60,0.25);
            border-radius: 50px;
            padding: 6px 16px;
            cursor: pointer;
            transition: all 0.22s;
            display: none;
        }
        .btn-clear-filters:hover { background: #e74c3c; color: #fff; border-color: #e74c3c; }

        /* Show per page selector */
        .show-per-page-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: #888;
            font-family: 'DM Sans', sans-serif;
        }
        .per-page-btn {
            padding: 4px 12px;
            border-radius: 50px;
            border: 1.5px solid rgba(46,204,113,0.2);
            background: #f8fffe;
            font-size: 0.78rem;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            transition: all 0.2s;
        }
        .per-page-btn:hover { border-color: #2ecc71; background: #f0faf4; }
        .per-page-btn.active { background: #2ecc71; border-color: #2ecc71; color: #fff; }

        /* Active filter tags row */
        .active-tags-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 12px;
        }
        .active-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px 3px 8px;
            background: rgba(46,204,113,0.1);
            border: 1px solid rgba(46,204,113,0.3);
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #27ae60;
        }
        .active-tag i { cursor: pointer; font-size: 0.65rem; opacity: 0.7; }
        .active-tag i:hover { opacity: 1; }

        /* Spinner / no results */
        .spinner-car {
            width: 48px; height: 48px;
            border: 4px solid #f0faf4;
            border-top-color: #2ecc71;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .no-results-box {
            text-align: center;
            padding: 60px 20px;
            width: 100%;
        }
        .no-results-box i { font-size: 3rem; color: #d5ecd5; margin-bottom: 16px; display: block; }
        .no-results-box h5 { color: #555; font-weight: 600; margin-bottom: 6px; }
        .no-results-box p { color: #aaa; font-size: 0.85rem; }

        @media (max-width: 768px) {
            .filter-panel { padding: 18px 14px 16px; }
            .filter-dropdown-panel { min-width: 180px; }
            .filter-footer { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <section class="hero-wrap hero-wrap-2 js-fullheight" style="background-image: url('images/bg_3.jpg');" data-stellar-background-ratio="0.5">
        <div class="overlay"></div>
        <div class="container">
            <div class="row no-gutters slider-text js-fullheight align-items-end justify-content-start">
                <div class="col-md-9 ftco-animate pb-5">
                    <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Home <i class="ion-ios-arrow-forward"></i></a></span> <span>Cars <i class="ion-ios-arrow-forward"></i></span></p>
                    <h1 class="mb-3 bread">Choose Your Car</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section bg-light">
        <div class="container">

            <!-- ════════════════════════════════
                 FILTER PANEL
            ════════════════════════════════ -->
            <div class="filter-panel">

                <!-- Search -->
                <div class="filter-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="carSearch" placeholder="Search by car name, brand, model or category..." autocomplete="off">
                    <i class="fas fa-times clear-search" id="clearSearch"></i>
                </div>

                <!-- Dropdown Filters Row -->
                <div class="filter-dropdowns-row">

                    <!-- Brand Dropdown -->
                    <div class="filter-dropdown-wrap">
                        <div class="filter-dropdown-btn" id="btnBrand" onclick="toggleDropdown('ddBrand','btnBrand')">
                            <i class="fas fa-tag"></i> Brand <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="filter-dropdown-panel" id="ddBrand">
                            <div class="brand-chip-grid">
                                <?php
                                include("connect.php");
                                $bq = mysqli_query($con, "SELECT * FROM brand_master WHERE is_active=1 ORDER BY brand_name ASC");
                                while ($b = mysqli_fetch_assoc($bq)): ?>
                                    <div class="brand-chip"
                                         data-brand="<?= $b['brand_id'] ?>"
                                         data-label="<?= htmlspecialchars($b['brand_name']) ?>"
                                         onclick="toggleChip(this,'brand')">
                                        <?php if (!empty($b['brand_logo'])): ?>
                                            <img src="../Admin/pages/images/brand_images/<?= htmlspecialchars($b['brand_logo']) ?>"
                                                 alt="<?= htmlspecialchars($b['brand_name']) ?>"
                                                 onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fas fa-car',style:'font-size:1.2rem;color:#2ecc71'}))">
                                        <?php else: ?>
                                            <i class="fas fa-car" style="font-size:1.2rem;color:#2ecc71"></i>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($b['brand_name']) ?></span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Category Dropdown — deduplicated by category_name -->
                    <div class="filter-dropdown-wrap">
                        <div class="filter-dropdown-btn" id="btnCategory" onclick="toggleDropdown('ddCategory','btnCategory')">
                            <i class="fas fa-th-large"></i> Category <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="filter-dropdown-panel" id="ddCategory">
                            <div class="cat-chip-list">
                                <?php
                                /* GROUP BY category_name → one filter per unique name.
                                   Pick first image found for that name.                */
                                $cq = mysqli_query($con, "
                                    SELECT category_name,
                                           MIN(category_image) AS category_image
                                    FROM category_master
                                    WHERE is_active = 1
                                    GROUP BY category_name
                                    ORDER BY category_name ASC
                                ");
                                while ($c = mysqli_fetch_assoc($cq)): ?>
                                    <div class="cat-chip"
                                         data-category="<?= htmlspecialchars($c['category_name']) ?>"
                                         data-label="<?= htmlspecialchars($c['category_name']) ?>"
                                         onclick="toggleChip(this,'category')">
                                        <?php if (!empty($c['category_image'])): ?>
                                            <img src="../Admin/pages/images/category_images/<?= htmlspecialchars($c['category_image']) ?>"
                                                 alt="<?= htmlspecialchars($c['category_name']) ?>"
                                                 onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'cat-ico',innerHTML:'<i class=\'fas fa-car-side\'></i>'}))">
                                        <?php else: ?>
                                            <span class="cat-ico"><i class="fas fa-car-side"></i></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($c['category_name']) ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Seating Dropdown -->
                    <div class="filter-dropdown-wrap">
                        <div class="filter-dropdown-btn" id="btnSeater" onclick="toggleDropdown('ddSeater','btnSeater')">
                            <i class="fas fa-users"></i> Seating <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="filter-dropdown-panel" id="ddSeater">
                            <div class="icon-pill-list">
                                <?php
                                $sq = mysqli_query($con, "SELECT DISTINCT seating_capacity FROM car_master WHERE is_enabled=1 AND seating_capacity IS NOT NULL ORDER BY seating_capacity ASC");
                                while ($s = mysqli_fetch_assoc($sq)):
                                    $seat = $s['seating_capacity'];
                                    $ico  = $seat <= 2 ? 'fa-user' : ($seat <= 5 ? 'fa-user-friends' : 'fa-users');
                                ?>
                                    <div class="icon-pill"
                                         data-seater="<?= $seat ?>"
                                         data-label="<?= $seat ?> Seater"
                                         onclick="toggleChip(this,'seater')">
                                        <i class="fas <?= $ico ?>"></i> <?= $seat ?> Seater
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Dropdown -->
                    <div class="filter-dropdown-wrap">
                        <div class="filter-dropdown-btn" id="btnFuel" onclick="toggleDropdown('ddFuel','btnFuel')">
                            <i class="fas fa-gas-pump"></i> Fuel Type <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="filter-dropdown-panel" id="ddFuel">
                            <div class="icon-pill-list">
                                <?php
                                $fq = mysqli_query($con, "SELECT DISTINCT fuel_type FROM car_master WHERE is_enabled=1 AND fuel_type IS NOT NULL ORDER BY fuel_type ASC");
                                $ficons = ['Petrol'=>'fa-gas-pump','Diesel'=>'fa-oil-can','Electric'=>'fa-bolt','Hybrid'=>'fa-leaf','CNG'=>'fa-wind'];
                                while ($f = mysqli_fetch_assoc($fq)):
                                    $fuel = $f['fuel_type'];
                                    $fi   = $ficons[$fuel] ?? 'fa-gas-pump';
                                ?>
                                    <div class="icon-pill"
                                         data-fuel="<?= htmlspecialchars($fuel) ?>"
                                         data-label="<?= htmlspecialchars($fuel) ?>"
                                         onclick="toggleChip(this,'fuel')">
                                        <i class="fas <?= $fi ?>"></i> <?= htmlspecialchars($fuel) ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Gear Dropdown -->
                    <div class="filter-dropdown-wrap">
                        <div class="filter-dropdown-btn" id="btnGear" onclick="toggleDropdown('ddGear','btnGear')">
                            <i class="fas fa-cog"></i> Transmission <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="filter-dropdown-panel" id="ddGear">
                            <div class="icon-pill-list">
                                <?php
                                $gq = mysqli_query($con, "SELECT DISTINCT gear_type FROM car_master WHERE is_enabled=1 AND gear_type IS NOT NULL ORDER BY gear_type ASC");
                                $gicons = ['Manual'=>'fa-sliders-h','Automatic'=>'fa-cogs','AMT'=>'fa-cog','CVT'=>'fa-sync-alt','DCT'=>'fa-random'];
                                while ($g = mysqli_fetch_assoc($gq)):
                                    $gear = $g['gear_type'];
                                    $gi   = $gicons[$gear] ?? 'fa-cog';
                                ?>
                                    <div class="icon-pill"
                                         data-gear="<?= htmlspecialchars($gear) ?>"
                                         data-label="<?= htmlspecialchars($gear) ?>"
                                         onclick="toggleChip(this,'gear')">
                                        <i class="fas <?= $gi ?>"></i> <?= htmlspecialchars($gear) ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /.filter-dropdowns-row -->

                <!-- Active filter tags -->
                <div class="active-tags-row" id="activeTagsRow"></div>

                <!-- Footer: results + per-page + clear -->
                <div class="filter-footer">
                    <div class="filter-results-count">
                        Showing <strong id="resultCount">9</strong> of <strong id="totalCount">—</strong> cars
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div class="show-per-page-wrap">
                            <span>Show:</span>
                            <button class="per-page-btn active" data-limit="9"  onclick="setLimit(this)">9</button>
                            <button class="per-page-btn"        data-limit="15" onclick="setLimit(this)">15</button>
                            <button class="per-page-btn"        data-limit="30" onclick="setLimit(this)">30</button>
                            <button class="per-page-btn"        data-limit="45" onclick="setLimit(this)">45</button>
                            <button class="per-page-btn"        data-limit="60" onclick="setLimit(this)">60</button>
                        </div>
                        <button class="btn-clear-filters" id="btnClearAll" onclick="clearAllFilters()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                    </div>
                </div>

            </div><!-- /.filter-panel -->

            <!-- Car Grid -->
            <div class="row" id="carGrid">
                <?php
                $limit  = 9;
                $offset = 0;
                $q = mysqli_query($con, "
                    SELECT c.*, b.brand_name, p.price_per_day
                    FROM car_master c
                    INNER JOIN brand_master b ON c.brand_id = b.brand_id
                    LEFT JOIN  car_pricing p  ON p.car_id   = c.car_id
                    WHERE c.is_enabled = 1 AND b.is_active = 1
                    ORDER BY c.car_id DESC
                    LIMIT $offset, $limit
                ");
                while ($row = mysqli_fetch_assoc($q)):
                    $fuel  = !empty($row['fuel_type'])        ? $row['fuel_type']        : '—';
                    $seats = !empty($row['seating_capacity']) ? $row['seating_capacity'] : '—';
                    $gear  = !empty($row['gear_type'])        ? $row['gear_type']        : '—';
                    $price = !empty($row['price_per_day'])
                             ? '&#8377;'.number_format($row['price_per_day'],0).'/day'
                             : 'N/A';
                ?>
                <div class="col-md-4 mb-4">
                    <div class="car-wrap rounded" style="opacity:1;transform:none;">
                        <div style="position:relative;background:linear-gradient(160deg,#f0faf4,#e8f8ef);padding:28px 20px 18px;display:flex;align-items:center;justify-content:center;overflow:hidden;height:200px;">
                            <span class="brand-badge"><?= htmlspecialchars($row['brand_name']) ?></span>
                            <span class="price-badge"><?= $price ?></span>
                            <img src="../Admin/pages/images/car_images/<?= htmlspecialchars($row['primary_image']) ?>"
                                 class="img rounded" alt="<?= htmlspecialchars($row['car_display_name']) ?>"
                                 style="max-width:260px;height:130px;object-fit:contain;position:relative;z-index:1;"
                                 onerror="this.style.opacity='.15'">
                            <div style="position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(to top,#fff,transparent);pointer-events:none"></div>
                        </div>
                        <div class="text">
                            <h2 class="mb-0"><?= htmlspecialchars($row['car_display_name']) ?></h2>
                            <div class="car-specs-strip">
                                <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($seats) ?></span><span class="car-spec-lbl">Seats</span></div>
                                <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($fuel) ?></span><span class="car-spec-lbl">Fuel</span></div>
                                <div class="car-spec-item"><span class="car-spec-val"><?= htmlspecialchars($gear) ?></span><span class="car-spec-lbl">Gear</span></div>
                            </div>
                            <p class="card-footer p-0 mt-auto">
                                <a href="booking.php?car_id=<?= $row['car_id'] ?>&brand_id=<?= $row['brand_id'] ?>"
                                   class="btn btn-primary w-100 py-3 rounded">Book Now</a>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div><!-- /#carGrid -->

            <!-- Pagination -->
            <div class="row mt-5" id="paginationWrap">
                <div class="col text-center">
                    <div class="block-27">
                        <ul id="paginationList"></ul>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include('footer.php'); ?>

    <script>
    /* ═══════════════════════════════════════════
       STATE
    ═══════════════════════════════════════════ */
    const state = {
        brand:    [],
        category: [],   /* stores category_name strings now */
        seater:   [],
        fuel:     [],
        gear:     [],
        search:   '',
        limit:    9,
        page:     1
    };

    /* ── Dropdown toggle ── */
    function toggleDropdown(panelId, btnId) {
        const panel = document.getElementById(panelId);
        const btn   = document.getElementById(btnId);
        const isOpen = panel.classList.contains('open');

        // Close all
        document.querySelectorAll('.filter-dropdown-panel').forEach(p => p.classList.remove('open'));
        document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('open'));

        if (!isOpen) {
            panel.classList.add('open');
            btn.classList.add('open');
        }
    }

    /* Close dropdowns when clicking outside */
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-dropdown-wrap')) {
            document.querySelectorAll('.filter-dropdown-panel').forEach(p => p.classList.remove('open'));
            document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('open'));
        }
    });

    /* ── Chip toggle ── */
    function toggleChip(el, type) {
        el.classList.toggle('active');
        const val = el.dataset[type] || el.dataset.seater || el.dataset.fuel || el.dataset.gear || el.dataset.brand || el.dataset.category;

        if (el.classList.contains('active')) {
            if (!state[type].includes(val)) state[type].push(val);
        } else {
            state[type] = state[type].filter(v => v !== val);
        }

        // Update dropdown button appearance
        const map = { brand:'btnBrand', category:'btnCategory', seater:'btnSeater', fuel:'btnFuel', gear:'btnGear' };
        const btn = document.getElementById(map[type]);
        if (btn) btn.classList.toggle('has-active', state[type].length > 0);

        state.page = 1;
        updateActiveTags();
        updateClearBtn();
        applyFilters();
    }

    /* ── Active tags ── */
    function updateActiveTags() {
        const row = document.getElementById('activeTagsRow');
        row.innerHTML = '';
        const allTags = [
            ...state.brand.map(v    => ({ type:'brand',    val:v, label: document.querySelector(`.brand-chip[data-brand="${v}"]`)?.dataset.label || v })),
            ...state.category.map(v => ({ type:'category', val:v, label: v })),
            ...state.seater.map(v   => ({ type:'seater',   val:v, label: v+' Seater' })),
            ...state.fuel.map(v     => ({ type:'fuel',     val:v, label: v })),
            ...state.gear.map(v     => ({ type:'gear',     val:v, label: v })),
        ];
        allTags.forEach(t => {
            const tag = document.createElement('span');
            tag.className = 'active-tag';
            tag.innerHTML = `${t.label} <i class="fas fa-times" onclick="removeTag('${t.type}','${t.val}')"></i>`;
            row.appendChild(tag);
        });
    }

    function removeTag(type, val) {
        state[type] = state[type].filter(v => v !== val);
        // De-activate chip
        const attr = type === 'brand' ? `[data-brand="${val}"]`
                   : type === 'category' ? `[data-category="${val}"]`
                   : type === 'seater'   ? `[data-seater="${val}"]`
                   : type === 'fuel'     ? `[data-fuel="${val}"]`
                   :                       `[data-gear="${val}"]`;
        document.querySelectorAll(attr).forEach(el => el.classList.remove('active'));

        const map = { brand:'btnBrand', category:'btnCategory', seater:'btnSeater', fuel:'btnFuel', gear:'btnGear' };
        const btn = document.getElementById(map[type]);
        if (btn) btn.classList.toggle('has-active', state[type].length > 0);

        state.page = 1;
        updateActiveTags();
        updateClearBtn();
        applyFilters();
    }

    /* ── Clear all ── */
    function clearAllFilters() {
        state.brand=[]; state.category=[]; state.seater=[]; state.fuel=[]; state.gear=[]; state.search='';
        state.page=1;
        document.querySelectorAll('.brand-chip,.cat-chip,.icon-pill').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.filter-dropdown-btn').forEach(b => b.classList.remove('has-active'));
        document.getElementById('carSearch').value = '';
        document.getElementById('clearSearch').style.display = 'none';
        document.getElementById('activeTagsRow').innerHTML = '';
        updateClearBtn();
        applyFilters();
    }

    function updateClearBtn() {
        const has = state.brand.length || state.category.length || state.seater.length ||
                    state.fuel.length  || state.gear.length     || state.search;
        document.getElementById('btnClearAll').style.display = has ? 'inline-flex' : 'none';
    }

    /* ── Per-page selector ── */
    function setLimit(btn) {
        document.querySelectorAll('.per-page-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        state.limit = parseInt(btn.dataset.limit);
        state.page  = 1;
        applyFilters();
    }

    /* ── Search ── */
    const searchInput    = document.getElementById('carSearch');
    const clearSearchBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', function() {
        state.search = this.value.trim();
        clearSearchBtn.style.display = state.search ? 'block' : 'none';
        updateClearBtn();
        state.page = 1;
        clearTimeout(window._st);
        window._st = setTimeout(applyFilters, 350);
    });
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = ''; state.search = '';
        this.style.display = 'none';
        updateClearBtn();
        state.page = 1;
        applyFilters();
    });

    /* ── Pagination ── */
    function goToPage(p) {
        state.page = p;
        applyFilters();
        window.scrollTo({ top: document.getElementById('carGrid').offsetTop - 100, behavior: 'smooth' });
    }

    function renderPagination(total, limit, current) {
        const totalPages = Math.ceil(total / limit);
        const ul = document.getElementById('paginationList');
        ul.innerHTML = '';
        if (totalPages <= 1) return;

        if (current > 1) ul.innerHTML += `<li><a href="#" onclick="goToPage(${current-1});return false;">&lt;</a></li>`;
        for (let i = 1; i <= totalPages; i++) {
            ul.innerHTML += `<li class="${i===current?'active':''}"><a href="#" onclick="goToPage(${i});return false;">${i}</a></li>`;
        }
        if (current < totalPages) ul.innerHTML += `<li><a href="#" onclick="goToPage(${current+1});return false;">&gt;</a></li>`;
    }

    /* ── MAIN: Apply Filters via AJAX ── */
    function applyFilters() {
        const grid = document.getElementById('carGrid');

        grid.innerHTML = `<div class="col-12 text-center py-5">
            <div class="spinner-car"></div>
            <p style="color:#aaa;font-size:0.85rem;">Finding your perfect car...</p>
        </div>`;

        const params = new URLSearchParams();
        if (state.search)          params.append('search',   state.search);
        if (state.brand.length)    params.append('brand',    state.brand.join(','));
        if (state.category.length) params.append('category', state.category.join(','));
        if (state.seater.length)   params.append('seater',   state.seater.join(','));
        if (state.fuel.length)     params.append('fuel',     state.fuel.join(','));
        if (state.gear.length)     params.append('gear',     state.gear.join(','));
        params.append('limit', state.limit);
        params.append('page',  state.page);

        fetch('getFilteredCars.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                document.getElementById('resultCount').textContent = data.showing;
                document.getElementById('totalCount').textContent  = data.total;

                if (data.total === 0) {
                    grid.innerHTML = `<div class="col-12"><div class="no-results-box">
                        <i class="fas fa-car-crash"></i>
                        <h5>No cars found</h5>
                        <p>Try adjusting your filters or search term.</p>
                    </div></div>`;
                    document.getElementById('paginationList').innerHTML = '';
                } else {
                    grid.innerHTML = data.html;
                    grid.querySelectorAll('.car-wrap').forEach((card, i) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'none';
                        }, i * 55);
                    });
                    renderPagination(data.total, state.limit, state.page);
                }
            })
            .catch(() => {
                grid.innerHTML = `<div class="col-12 text-center py-5"><p class="text-danger">Something went wrong. Please try again.</p></div>`;
            });
    }

    /* Init total count */
    document.addEventListener('DOMContentLoaded', function() {
        fetch('getFilteredCars.php?limit=9&page=1')
            .then(r => r.json())
            .then(data => {
                document.getElementById('totalCount').textContent = data.total;
                document.getElementById('resultCount').textContent = data.showing;
                renderPagination(data.total, 9, 1);
            });
    });
    </script>

</body>
</html>