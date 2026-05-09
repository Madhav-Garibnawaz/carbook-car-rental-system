<?php
include('connect.php');
session_name('admin_session');
session_start();
include('../components/navbar.php');

// ── Filters ───────────────────────────────────────────────────────────────────
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, trim($_GET['search'])) : '';
$sort   = isset($_GET['sort'])   ? $_GET['sort'] : 'newest';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (driver_name LIKE '%$search%'
                  OR driver_email LIKE '%$search%'
                  OR driver_mobile LIKE '%$search%'
                  OR license_number LIKE '%$search%'
                  OR aadhar_number LIKE '%$search%')";
}

$order = match($sort) {
    'oldest'    => "driver_id ASC",
    'name_az'   => "driver_name ASC",
    'name_za'   => "driver_name DESC",
    'exp_high'  => "experience_years DESC",
    'exp_low'   => "experience_years ASC",
    default     => "driver_id DESC",
};

$q       = mysqli_query($con, "SELECT * FROM driver_master WHERE $where ORDER BY $order");
$drivers = [];
while ($row = mysqli_fetch_assoc($q)) $drivers[] = $row;

$total_q    = mysqli_query($con, "SELECT COUNT(*) as c FROM driver_master");
$total      = mysqli_fetch_assoc($total_q)['c'];
$approved_q = mysqli_query($con, "SELECT COUNT(*) as c FROM driver_master WHERE status=1");
$approved_c = mysqli_fetch_assoc($approved_q)['c'];
$pending_q  = mysqli_query($con, "SELECT COUNT(*) as c FROM driver_master WHERE status=0");
$pending_c  = mysqli_fetch_assoc($pending_q)['c'];
$rejected_q = mysqli_query($con, "SELECT COUNT(*) as c FROM driver_master WHERE status=2");
$rejected_c = mysqli_fetch_assoc($rejected_q)['c'];

$toast = $_SESSION['admin_toast'] ?? null;
unset($_SESSION['admin_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Drivers Management — Admin</title>
<link rel="icon" href="../assets/img/kaiadmin/favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/plugins.min.css">
<link rel="stylesheet" href="../assets/css/kaiadmin.min.css">

<style>
/* ── STAT CARDS ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.stat-card {
    border-radius: 12px; border: none; padding: 18px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.stat-card.s-all      { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.s-approved { background: linear-gradient(135deg,#22c55e,#16a34a); }
.stat-card.s-pending  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-card.s-rejected { background: linear-gradient(135deg,#6b7280,#374151); }
.stat-icon  { font-size:20px; color:rgba(255,255,255,0.8); margin-bottom:6px; }
.stat-val   { font-size:30px; font-weight:800; color:#fff; line-height:1; margin-bottom:3px; }
.stat-label { font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,0.8); }

/* ── FILTER BAR ── */
.filter-bar { display:flex; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.filter-bar .sw  { position:relative; flex:1; max-width:380px; }
.filter-bar .sw i{ position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#aaa; font-size:12px; }
.filter-bar .si  {
    width:100%; padding:9px 12px 9px 34px;
    border:1px solid #dee2e6; border-radius:8px;
    font-size:13px; outline:none; background:#fff; transition:border-color .2s;
    font-family:'Public Sans',sans-serif;
}
.filter-bar .si:focus { border-color:#667eea; }
.sort-sel {
    padding:9px 14px; border:1px solid #dee2e6; border-radius:8px;
    font-size:13px; outline:none; background:#fff; color:#495057;
    font-family:'Public Sans',sans-serif; cursor:pointer;
}
.sort-sel:focus { border-color:#667eea; }
.btn-go  { padding:9px 18px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; font-family:'Public Sans',sans-serif; }
.btn-go:hover { opacity:.88; }
.btn-clr { padding:9px 14px; background:#f1f3f5; color:#555; border:1px solid #dee2e6; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; }
.btn-clr:hover { background:#e9ecef; color:#333; }
.rc { margin-left:auto; font-size:12px; color:#888; white-space:nowrap; }

/* ── DRIVER CARDS ── */
.drivers-grid { display:flex; flex-direction:column; gap:16px; }
.driver-card {
    border-radius:12px; box-shadow:0 3px 12px rgba(0,0,0,0.07);
    border:1px solid #eee; overflow:hidden; background:#fff;
    transition:box-shadow .2s,transform .15s;
}
.driver-card:hover { box-shadow:0 6px 22px rgba(0,0,0,0.11); transform:translateY(-1px); }

/* Header */
.dch {
    padding:14px 20px; display:flex; align-items:center;
    justify-content:space-between; flex-wrap:wrap; gap:8px;
}
.dch.s-approved { background:linear-gradient(135deg,#22c55e,#16a34a); }
.dch.s-pending  { background:linear-gradient(135deg,#f59e0b,#d97706); }
.dch.s-rejected { background:linear-gradient(135deg,#6b7280,#374151); }
.dch.s-active   { background:linear-gradient(135deg,#667eea,#764ba2); }

.dch-name { font-size:15px; font-weight:800; color:#fff; margin-bottom:2px; }
.dch-sub  { font-size:11px; color:rgba(255,255,255,0.75); font-family:'Courier New',monospace; }
.dbadge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:100px;
    font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    background:rgba(255,255,255,0.22); color:#fff; border:1px solid rgba(255,255,255,0.35);
}
.ddot { width:6px; height:6px; border-radius:50%; background:#fff; flex-shrink:0; }
.ddot.blink { animation:blink 1.5s ease infinite; }
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

/* Body */
.dcb { padding:18px 20px; }
.driver-sections {
    display:grid;
    grid-template-columns: 160px 1.4fr 1.2fr 1.2fr auto;
    gap:20px;
    align-items:start;
}
.dsec-title {
    font-size:9px; font-weight:800; letter-spacing:.12em;
    text-transform:uppercase; color:#999; margin-bottom:8px;
    display:flex; align-items:center; gap:5px;
}
.dsec-title i { font-size:9px; color:#667eea; }

/* Avatar */
.d-avatar-wrap { display:flex; flex-direction:column; align-items:center; gap:8px; }
.d-avatar {
    width:90px; height:90px; border-radius:50%;
    object-fit:cover; border:3px solid #e9ecef;
    box-shadow:0 4px 12px rgba(102,126,234,0.2);
}
.d-avatar-ph {
    width:90px; height:90px; border-radius:50%;
    background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
    display:flex; align-items:center; justify-content:center;
    font-size:30px; color:#667eea;
    border:3px solid #e9ecef;
    box-shadow:0 4px 12px rgba(102,126,234,0.2);
}
.did-pill {
    font-family:'Courier New',monospace; font-size:10px; font-weight:700;
    background:#f0f4ff; border:1px solid #c7d2fe; color:#667eea;
    padding:3px 10px; border-radius:100px;
}

/* Info rows */
.irow { display:flex; align-items:flex-start; gap:7px; margin-bottom:7px; font-size:12px; color:#495057; }
.irow i { font-size:10px; color:#667eea; width:14px; text-align:center; flex-shrink:0; margin-top:2px; }

/* Doc thumbnails */
.doc-thumb-wrap { margin-bottom:10px; }
.doc-thumb-label { font-size:9px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#aaa; margin-bottom:5px; display:flex; align-items:center; gap:4px; }
.doc-thumb { width:100%; max-width:130px; height:70px; object-fit:cover; border-radius:7px; border:1px solid #e9ecef; box-shadow:0 2px 8px rgba(0,0,0,0.08); cursor:pointer; transition:transform .2s,box-shadow .2s; display:block; }
.doc-thumb:hover { transform:scale(1.04); box-shadow:0 4px 14px rgba(0,0,0,0.14); }

/* Info tile */
.info-tile { background:#f8f9ff; border:1px solid #e8edff; border-radius:8px; padding:10px 12px; margin-bottom:8px; }
.it-label { font-size:9px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#999; margin-bottom:3px; display:flex; align-items:center; gap:4px; }
.it-label i { font-size:9px; color:#667eea; }
.it-val { font-size:12px; font-weight:700; color:#1a1a2e; font-family:'Courier New',monospace; }

/* Status pill */
.spill { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:100px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
.spill.s-approved { background:#f0fdf4; border:1px solid #86efac; color:#16a34a; }
.spill.s-pending  { background:#fffbeb; border:1px solid #fde68a; color:#d97706; }
.spill.s-rejected { background:#f3f4f6; border:1px solid #d1d5db; color:#6b7280; }

/* Experience badge */
.exp-badge { display:inline-flex; align-items:center; gap:5px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; font-size:11px; font-weight:700; padding:4px 11px; border-radius:100px; }

/* Action col */
.action-col { display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
.btn-view {
    padding:7px 14px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff;
    border:none; border-radius:7px; font-size:12px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px; text-decoration:none;
    transition:filter .2s,transform .12s; white-space:nowrap;
}
.btn-view:hover { filter:brightness(1.08); transform:translateY(-1px); color:#fff; }
.btn-deactivate {
    padding:7px 14px; background:transparent; border:1.5px solid #fcd34d; color:#d97706;
    border-radius:7px; font-size:12px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px; text-decoration:none;
    transition:background .2s; white-space:nowrap;
}
.btn-deactivate:hover { background:#fffbeb; color:#b45309; }
.btn-activate {
    padding:7px 14px; background:transparent; border:1.5px solid #86efac; color:#16a34a;
    border-radius:7px; font-size:12px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px; text-decoration:none;
    transition:background .2s; white-space:nowrap;
}
.btn-activate:hover { background:#f0fdf4; color:#15803d; }
.btn-del {
    padding:7px 14px; background:transparent; border:1.5px solid #fca5a5; color:#ef4444;
    border-radius:7px; font-size:12px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px; text-decoration:none;
    transition:background .2s; white-space:nowrap;
}
.btn-del:hover { background:#fef2f2; border-color:#ef4444; }

/* Footer */
.dcf {
    background:#f8f9fa; border-top:1px solid #eee;
    padding:10px 20px; display:flex; align-items:center;
    justify-content:space-between; gap:10px; flex-wrap:wrap;
}
.fm { font-size:11px; color:#888; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.fm span { display:flex; align-items:center; gap:4px; }
.fm i { font-size:9px; color:#667eea; }

/* Empty state */
.empty-state { text-align:center; padding:64px 32px; background:#fff; border-radius:12px; border:1px solid #eee; }
.empty-state i { font-size:48px; color:#ccc; margin-bottom:14px; display:block; }
.empty-state h4 { font-size:18px; font-weight:700; color:#555; margin-bottom:6px; }
.empty-state p  { font-size:13px; color:#888; }

/* Toast */
.toast-fixed { position:fixed; top:20px; right:24px; z-index:9999; padding:12px 20px; border-radius:10px; display:flex; align-items:center; gap:10px; font-size:13px; font-weight:600; transform:translateX(130%); transition:transform .4s cubic-bezier(.34,1.56,.64,1); max-width:340px; box-shadow:0 6px 20px rgba(0,0,0,0.12); }
.toast-fixed.show { transform:translateX(0); }
.toast-fixed.success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
.toast-fixed.warning { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }

/* Lightbox */
.lightbox { position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; display:none; align-items:center; justify-content:center; backdrop-filter:blur(6px); }
.lightbox.open { display:flex; }
.lightbox img { max-width:90vw; max-height:88vh; border-radius:10px; box-shadow:0 20px 60px rgba(0,0,0,0.5); }
.lb-close { position:absolute; top:20px; right:24px; color:#fff; font-size:28px; cursor:pointer; }

@media(max-width:1100px){ .driver-sections{grid-template-columns:1fr 1fr 1fr} .action-col{flex-direction:row;align-items:center} }
@media(max-width:768px) { .driver-sections{grid-template-columns:1fr 1fr} .stats-row{grid-template-columns:1fr 1fr} }
@media(max-width:480px) { .driver-sections{grid-template-columns:1fr} .stats-row{grid-template-columns:1fr 1fr} }
</style>
</head>
<body>

<div class="page-inner"><br><br><br>

  <!-- PAGE TITLE -->
  <div class="d-flex align-items-center justify-content-between mb-4 pt-2">
    <div>
      <h4 class="mb-1" style="font-weight:800;color:#1a1a2e">
        <i class="fas fa-steering-wheel me-2" style="color:#667eea"></i>Drivers Management
      </h4>
      <p class="text-muted mb-0" style="font-size:13px">View, manage and control all registered drivers</p>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap">
      <div class="sw">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="si"
               placeholder="Search by name, email, mobile, license, aadhar…"
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="sort" class="sort-sel">
        <option value="newest"   <?= $sort==='newest'  ?'selected':'' ?>>Newest First</option>
        <option value="oldest"   <?= $sort==='oldest'  ?'selected':'' ?>>Oldest First</option>
        <option value="name_az"  <?= $sort==='name_az' ?'selected':'' ?>>Name A → Z</option>
        <option value="name_za"  <?= $sort==='name_za' ?'selected':'' ?>>Name Z → A</option>
        <option value="exp_high" <?= $sort==='exp_high'?'selected':'' ?>>Experience High → Low</option>
        <option value="exp_low"  <?= $sort==='exp_low' ?'selected':'' ?>>Experience Low → High</option>
      </select>
      <button type="submit" class="btn-go"><i class="fas fa-search me-1"></i>Search</button>
      <?php if(!empty($search) || $sort !== 'newest'): ?>
      <a href="driver_master.php" class="btn-clr"><i class="fas fa-times me-1"></i>Clear</a>
      <?php endif; ?>
    </form>
    <div class="rc"><?= count($drivers) ?> driver<?= count($drivers)!=1?'s':'' ?></div>
  </div>

  <!-- DRIVER CARDS -->
  <div class="drivers-grid">

  <?php if(empty($drivers)): ?>
    <div class="empty-state">
      <i class="fas fa-user-slash"></i>
      <h4>No drivers found</h4>
      <p>No drivers match your current search or filter.</p>
    </div>

  <?php else: ?>
  <?php foreach($drivers as $row):
    $did      = intval($row['driver_id']);
    $status   = intval($row['status']);
    $status_lbl = match($status){ 1=>'Approved', 2=>'Rejected', default=>'Pending' };
    $hdr_cls    = match($status){ 1=>'s-approved', 2=>'s-rejected', default=>'s-pending' };
    $spill_cls  = match($status){ 1=>'s-approved', 2=>'s-rejected', default=>'s-pending' };
    $spill_icon = match($status){ 1=>'fa-check-circle', 2=>'fa-ban', default=>'fa-clock' };

    $profile_img = !empty($row['profile_image']) ? '../../Driver/images/driver_profile/'.htmlspecialchars($row['profile_image']) : '';
    $license_img = !empty($row['license_image'])  ? '../../Driver/images/driver_licence/'.htmlspecialchars($row['license_image'])  : '';
    $aadhar_img  = !empty($row['aadhar_image'])   ? '../../Driver/images/driver_aadhar/'.htmlspecialchars($row['aadhar_image'])    : '';
  ?>

  <div class="driver-card">

    <!-- Header -->
    <div class="dch <?= $hdr_cls ?>">
      <div>
        <div class="dch-name"><i class="fas fa-user-tie me-2" style="opacity:.8"></i><?= htmlspecialchars($row['driver_name']) ?></div>
        <div class="dch-sub">DRV-<?= str_pad($did,6,'0',STR_PAD_LEFT) ?> &nbsp;·&nbsp; <?= htmlspecialchars($row['driver_email']) ?></div>
      </div>
      <span class="dbadge">
        <span class="ddot <?= $status===0?'blink':'' ?>"></span>
        <?= $status_lbl ?>
      </span>
    </div>

    <!-- Body -->
    <div class="dcb">
      <div class="driver-sections">

        <!-- Avatar -->
        <div class="d-avatar-wrap">
          <div class="dsec-title"><i class="fas fa-id-badge"></i> Profile</div>
          <?php if($profile_img): ?>
            <img src="<?= $profile_img ?>" class="d-avatar"
                 onerror="this.outerHTML='<div class=\'d-avatar-ph\'><i class=\'fas fa-user-tie\'></i></div>'"
                 onclick="openLightbox(this.src)" style="cursor:pointer" alt="">
          <?php else: ?>
            <div class="d-avatar-ph"><i class="fas fa-user-tie"></i></div>
          <?php endif; ?>
          <span class="did-pill">DRV-<?= str_pad($did,6,'0',STR_PAD_LEFT) ?></span>
          <span class="spill <?= $spill_cls ?>">
            <i class="fas <?= $spill_icon ?>"></i><?= $status_lbl ?>
          </span>
        </div>

        <!-- Contact & Personal -->
        <div>
          <div class="dsec-title"><i class="fas fa-address-card"></i> Contact Info</div>
          <div class="irow"><i class="fas fa-envelope"></i><?= htmlspecialchars($row['driver_email']) ?></div>
          <div class="irow"><i class="fas fa-phone"></i>+91 <?= htmlspecialchars($row['driver_mobile']) ?></div>
          <div class="irow"><i class="fas fa-birthday-cake"></i><strong>DOB:</strong>&nbsp;<?= htmlspecialchars($row['dob']) ?></div>
          <div class="irow"><i class="fas fa-calendar-check"></i><strong>DOJ:</strong>&nbsp;<?= htmlspecialchars($row['doj']) ?></div>
          <div class="mt-2">
            <span class="exp-badge"><i class="fas fa-star"></i><?= htmlspecialchars($row['experience_years']) ?> Yrs Experience</span>
          </div>
        </div>

        <!-- License -->
        <div>
          <div class="dsec-title"><i class="fas fa-id-card-alt"></i> License Details</div>
          <div class="info-tile">
            <div class="it-label"><i class="fas fa-id-card-alt"></i> License No.</div>
            <div class="it-val"><?= htmlspecialchars($row['license_number']) ?></div>
          </div>
          <div class="info-tile">
            <div class="it-label"><i class="fas fa-calendar-times"></i> Expiry</div>
            <div class="it-val"><?= htmlspecialchars($row['license_expiry_date']) ?></div>
          </div>
          <?php if($license_img): ?>
          <div class="doc-thumb-wrap">
            <div class="doc-thumb-label"><i class="fas fa-image"></i> License Doc</div>
            <img src="<?= $license_img ?>" class="doc-thumb" onclick="openLightbox(this.src)" alt="License"
                 onerror="this.style.display='none'">
          </div>
          <?php endif; ?>
        </div>

        <!-- Aadhar -->
        <div>
          <div class="dsec-title"><i class="fas fa-fingerprint"></i> Aadhar Details</div>
          <div class="info-tile">
            <div class="it-label"><i class="fas fa-fingerprint"></i> Aadhar No.</div>
            <div class="it-val"><?= htmlspecialchars($row['aadhar_number']) ?></div>
          </div>
          <?php if($aadhar_img): ?>
          <div class="doc-thumb-wrap mt-2">
            <div class="doc-thumb-label"><i class="fas fa-image"></i> Aadhar Doc</div>
            <img src="<?= $aadhar_img ?>" class="doc-thumb" onclick="openLightbox(this.src)" alt="Aadhar"
                 onerror="this.style.display='none'">
          </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="action-col">
          <div class="dsec-title" style="justify-content:flex-end"><i class="fas fa-cog"></i> Actions</div>
          <?php if($status == 1): ?>
            <a href="toggle_driver.php?id=<?= $did ?>&status=2"
               class="btn-deactivate"
               onclick="return confirm('Deactivate this driver?')">
              <i class="fas fa-ban"></i> Deactivate
            </a>
          <?php else: ?>
            <a href="toggle_driver.php?id=<?= $did ?>&status=1"
               class="btn-activate"
               onclick="return confirm('Activate this driver?')">
              <i class="fas fa-check"></i> Activate
            </a>
          <?php endif; ?>
          <a href="delete_driver.php?id=<?= $did ?>"
             class="btn-del"
             onclick="return confirm('Permanently delete this driver?')">
            <i class="fas fa-trash"></i> Delete
          </a>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <div class="dcf">
      <div class="fm">
        <span><i class="fas fa-hashtag"></i>DRV-<?= str_pad($did,6,'0',STR_PAD_LEFT) ?></span>
        <span><i class="fas fa-envelope"></i><?= htmlspecialchars($row['driver_email']) ?></span>
        <span><i class="fas fa-phone"></i>+91 <?= htmlspecialchars($row['driver_mobile']) ?></span>
        <?php if($status===1): ?>
        <span style="color:#16a34a;font-weight:700"><i class="fas fa-circle" style="font-size:7px;color:#22c55e"></i> Approved</span>
        <?php elseif($status===2): ?>
        <span style="color:#6b7280;font-weight:700"><i class="fas fa-circle" style="font-size:7px;color:#9ca3af"></i> Rejected</span>
        <?php else: ?>
        <span style="color:#d97706;font-weight:700"><i class="fas fa-circle" style="font-size:7px;color:#f59e0b"></i> Pending Approval</span>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- end driver-card -->
  <?php endforeach; ?>
  <?php endif; ?>

  </div><!-- end drivers-grid -->

</div><!-- end page-inner -->

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <span class="lb-close" onclick="closeLightbox()">&times;</span>
  <img src="" id="lbImg" alt="">
</div>

<!-- TOAST -->
<?php if($toast): ?>
<div class="toast-fixed <?= $toast['type'] ?>" id="adminToast">
  <i class="fas <?= $toast['type']==='success'?'fa-check-circle':'fa-exclamation-circle' ?>"></i>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/kaiadmin.min.js"></script>
<script>
function openLightbox(src){
  document.getElementById('lbImg').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox(){
  document.getElementById('lightbox').classList.remove('open');
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLightbox(); });

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