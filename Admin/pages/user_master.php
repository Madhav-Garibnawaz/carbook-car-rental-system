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
    $where .= " AND (uname LIKE '%$search%' OR email LIKE '%$search%' OR mobno LIKE '%$search%')";
}

$order = match($sort) {
    'oldest'   => "created_at ASC",
    'name_az'  => "uname ASC",
    'name_za'  => "uname DESC",
    'active'   => "status DESC, created_at DESC",
    'inactive' => "status ASC, created_at DESC",
    default    => "created_at DESC",
};

$q    = mysqli_query($con, "SELECT * FROM users_master WHERE $where ORDER BY $order");
$users = [];
while ($row = mysqli_fetch_assoc($q)) $users[] = $row;

$count_q   = mysqli_query($con, "SELECT COUNT(*) as total FROM users_master");
$total     = mysqli_fetch_assoc($count_q)['total'];

$toast = $_SESSION['admin_toast'] ?? null;
unset($_SESSION['admin_toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users Management — Admin</title>
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
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
.stat-card {
    border-radius: 12px; border: none; padding: 18px 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    position: relative; overflow: hidden;
}
.stat-card.s-all      { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.s-active   { background: linear-gradient(135deg,#22c55e,#16a34a); }
.stat-card.s-inactive { background: linear-gradient(135deg,#6b7280,#374151); }
.stat-icon  { font-size: 20px; color: rgba(255,255,255,0.8); margin-bottom: 6px; }
.stat-val   { font-size: 30px; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 3px; }
.stat-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,0.8); }

/* ── FILTER BAR ── */
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-bar .sw  { position: relative; flex: 1; max-width: 380px; }
.filter-bar .sw i{ position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 12px; }
.filter-bar .si  {
    width: 100%; padding: 9px 12px 9px 34px;
    border: 1px solid #dee2e6; border-radius: 8px;
    font-size: 13px; outline: none; background: #fff; transition: border-color .2s;
    font-family: 'Public Sans', sans-serif;
}
.filter-bar .si:focus { border-color: #667eea; }
.sort-sel {
    padding: 9px 14px; border: 1px solid #dee2e6; border-radius: 8px;
    font-size: 13px; outline: none; background: #fff; color: #495057;
    font-family: 'Public Sans', sans-serif; cursor: pointer;
}
.sort-sel:focus { border-color: #667eea; }
.btn-go  { padding: 9px 18px; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; font-family: 'Public Sans', sans-serif; }
.btn-go:hover { opacity: .88; }
.btn-clr { padding: 9px 14px; background: #f1f3f5; color: #555; border: 1px solid #dee2e6; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; white-space: nowrap; }
.btn-clr:hover { background: #e9ecef; color: #333; }
.rc { margin-left: auto; font-size: 12px; color: #888; white-space: nowrap; }

/* ── USER CARDS ── */
.users-grid { display: flex; flex-direction: column; gap: 16px; }
.user-card {
    border-radius: 12px; box-shadow: 0 3px 12px rgba(0,0,0,0.07);
    border: 1px solid #eee; overflow: hidden; background: #fff;
    transition: box-shadow .2s, transform .15s;
}
.user-card:hover { box-shadow: 0 6px 22px rgba(0,0,0,0.11); transform: translateY(-1px); }

/* Card Header */
.uch {
    padding: 14px 20px; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 8px;
    background: linear-gradient(135deg,#667eea,#764ba2);
}
.uch.inactive-hdr { background: linear-gradient(135deg,#6b7280,#374151); }
.uch-name { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: .01em; }
.uch-id   { font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 2px; font-family: 'Courier New', monospace; }
.ubadge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 100px;
    font-size: 10px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
    background: rgba(255,255,255,0.22); color: #fff; border: 1px solid rgba(255,255,255,0.35);
}
.udot { width: 6px; height: 6px; border-radius: 50%; background: #fff; flex-shrink: 0; }
.udot.blink { animation: blink 1.5s ease infinite; }
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

/* Card Body */
.ucb { padding: 18px 20px; }
.user-sections {
    display: grid;
    grid-template-columns: 160px 1fr 1fr auto;
    gap: 20px;
    align-items: start;
}
.usec-title {
    font-size: 9px; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: #999; margin-bottom: 8px;
    display: flex; align-items: center; gap: 5px;
}
.usec-title i { font-size: 9px; color: #667eea; }

/* Avatar */
.u-avatar-wrap { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.u-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    object-fit: cover; border: 3px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(102,126,234,0.2);
}
.u-avatar-ph {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg,#e0e7ff,#c7d2fe);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; color: #667eea;
    border: 3px solid #e9ecef;
    box-shadow: 0 4px 12px rgba(102,126,234,0.2);
}
.uid-pill {
    font-family: 'Courier New', monospace; font-size: 10px; font-weight: 700;
    background: #f0f4ff; border: 1px solid #c7d2fe; color: #667eea;
    padding: 3px 10px; border-radius: 100px;
}

/* Info rows */
.irow { display: flex; align-items: center; gap: 7px; margin-bottom: 7px; font-size: 12px; color: #495057; }
.irow i { font-size: 10px; color: #667eea; width: 14px; text-align: center; flex-shrink: 0; }
.irow strong { color: #1a1a2e; font-weight: 600; }

/* Gender badge */
.gen-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 10px; border-radius: 100px; font-size: 10px; font-weight: 700; }
.gen-m { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.gen-f { background: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8; }
.gen-o { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }

/* Address block */
.addr-block { background: #f8f9ff; border-radius: 8px; padding: 10px 12px; font-size: 12px; color: #6c757d; line-height: 1.6; }
.addr-block strong { color: #1a1a2e; font-size: 11px; display: block; margin-bottom: 3px; }
.pin-pill { display: inline-flex; align-items: center; gap: 4px; background: #f0f4ff; border: 1px solid #c7d2fe; color: #667eea; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px; margin-top: 5px; font-family: 'Courier New', monospace; }

/* Action buttons */
.action-col { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
.btn-deactivate {
    padding: 7px 14px; background: transparent; border: 1.5px solid #fcd34d; color: #d97706;
    border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
    transition: background .2s, border-color .2s; white-space: nowrap;
}
.btn-deactivate:hover { background: #fffbeb; border-color: #f59e0b; color: #b45309; }
.btn-activate {
    padding: 7px 14px; background: transparent; border: 1.5px solid #86efac; color: #16a34a;
    border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
    transition: background .2s, border-color .2s; white-space: nowrap;
}
.btn-activate:hover { background: #f0fdf4; border-color: #22c55e; color: #15803d; }
.btn-del {
    padding: 7px 14px; background: transparent; border: 1.5px solid #fca5a5; color: #ef4444;
    border-radius: 7px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
    transition: background .2s, border-color .2s; white-space: nowrap;
}
.btn-del:hover { background: #fef2f2; border-color: #ef4444; }

/* Card Footer */
.ucf {
    background: #f8f9fa; border-top: 1px solid #eee;
    padding: 10px 20px; display: flex; align-items: center;
    justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.fm { font-size: 11px; color: #888; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.fm span { display: flex; align-items: center; gap: 4px; }
.fm i { font-size: 9px; color: #667eea; }

/* Empty state */
.empty-state { text-align: center; padding: 64px 32px; background: #fff; border-radius: 12px; border: 1px solid #eee; }
.empty-state i { font-size: 48px; color: #ccc; margin-bottom: 14px; display: block; }
.empty-state h4 { font-size: 18px; font-weight: 700; color: #555; margin-bottom: 6px; }
.empty-state p  { font-size: 13px; color: #888; }

/* Toast */
.toast-fixed { position: fixed; top: 20px; right: 24px; z-index: 9999; padding: 12px 20px; border-radius: 10px; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; transform: translateX(130%); transition: transform .4s cubic-bezier(.34,1.56,.64,1); max-width: 340px; box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
.toast-fixed.show { transform: translateX(0); }
.toast-fixed.success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
.toast-fixed.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

@media(max-width:1000px){ .user-sections { grid-template-columns: 1fr 1fr; } .action-col { flex-direction: row; align-items: center; } }
@media(max-width:640px) { .user-sections { grid-template-columns: 1fr; } .stats-row { grid-template-columns: 1fr 1fr; } }
</style>
</head>
<body>

<div class="page-inner"><br><br><br>

  <!-- PAGE TITLE -->
  <div class="d-flex align-items-center justify-content-between mb-4 pt-2">
    <div>
      <h4 class="mb-1" style="font-weight:800;color:#1a1a2e">
        <i class="fas fa-users me-2" style="color:#667eea"></i>Users Management
      </h4>
      <p class="text-muted mb-0" style="font-size:13px">View, manage, activate and deactivate customer accounts</p>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="filter-bar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap">
      <div class="sw">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="si"
               placeholder="Search by name, email, or mobile…"
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="sort" class="sort-sel">
        <option value="name_az"  <?= $sort==='name_az' ?'selected':'' ?>>Name A → Z</option>
        <option value="name_za"  <?= $sort==='name_za' ?'selected':'' ?>>Name Z → A</option>
        <option value="active"   <?= $sort==='active'  ?'selected':'' ?>>Active First</option>
        <option value="inactive" <?= $sort==='inactive'?'selected':'' ?>>Inactive First</option>
      </select>
      <button type="submit" class="btn-go"><i class="fas fa-search me-1"></i>Search</button>
      <?php if(!empty($search) || $sort !== 'newest'): ?>
      <a href="user_master.php" class="btn-clr"><i class="fas fa-times me-1"></i>Clear</a>
      <?php endif; ?>
    </form>
    <div class="rc"><?= count($users) ?> user<?= count($users)!=1?'s':'' ?></div>
  </div>
  <!-- USER CARDS -->
  <div class="users-grid">

  <?php if(empty($users)): ?>
    <div class="empty-state">
      <i class="fas fa-users-slash"></i>
      <h4>No users found</h4>
      <p>No users match your current search or filter.</p>
    </div>

  <?php else: ?>
  <?php foreach($users as $row):
    $is_active = ($row['status'] == 2);
    $hdr_cls   = $is_active ? '' : 'inactive-hdr';

    $gen       = $row['gen'] ?? 'N/A';
    $gen_cls   = match($gen){ 'Male'=>'gen-m','Female'=>'gen-f',default=>'gen-o' };
    $gen_icon  = match($gen){ 'Male'=>'fa-mars','Female'=>'fa-venus',default=>'fa-genderless' };

    $age_str = '';
    if(!empty($row['dob'])){
      $age = (new DateTime())->diff(new DateTime($row['dob']))->y;
      $age_str = $age.' yrs';
    }

    $user_img = !empty($row['photo']) ? '../../User/user_profile/'.htmlspecialchars($row['photo']) : '';
  ?>

  <div class="user-card">

    <!-- Header -->
    <div class="uch <?= $hdr_cls ?>">
      <div>
        <div class="uch-name"><i class="fas fa-user me-2" style="opacity:.8"></i><?= htmlspecialchars($row['uname']) ?></div>
        <div class="uch-id">UID #<?= str_pad($row['ui'],6,'0',STR_PAD_LEFT) ?> &nbsp;·&nbsp; Joined <?= date('d M Y', strtotime($row['created_at'])) ?></div>
      </div>
      <span class="ubadge">
        <span class="udot <?= $is_active ? 'blink' : '' ?>"></span>
        <?= $is_active ? 'Active' : 'Inactive' ?>
      </span>
    </div>

    <!-- Body -->
    <div class="ucb">
      <div class="user-sections">

        <!-- Avatar -->
        <div class="u-avatar-wrap">
          <div class="usec-title"><i class="fas fa-id-badge"></i> Profile</div>
          <?php if($user_img): ?>
            <img src="<?= $user_img ?>" class="u-avatar"
                 onerror="this.outerHTML='<div class=\'u-avatar-ph\'><i class=\'fas fa-user\'></i></div>'" alt="">
          <?php else: ?>
            <div class="u-avatar-ph"><i class="fas fa-user"></i></div>
          <?php endif; ?>
          <span class="uid-pill">#<?= str_pad($row['ui'],6,'0',STR_PAD_LEFT) ?></span>
        </div>

        <!-- Contact Info -->
        <div>
          <div class="usec-title"><i class="fas fa-address-card"></i> Contact Info</div>
          <div class="irow"><i class="fas fa-envelope"></i><span><?= htmlspecialchars($row['email']) ?></span></div>
          <div class="irow"><i class="fas fa-phone"></i><span><?= htmlspecialchars($row['mobno']) ?></span></div>
          <div class="irow">
            <i class="fas fa-<?= $gen_icon ?>"></i>
            <span class="gen-badge <?= $gen_cls ?>"><i class="fas fa-<?= $gen_icon ?>"></i><?= htmlspecialchars($gen) ?></span>
          </div>
          <?php if(!empty($row['dob'])): ?>
          <div class="irow"><i class="fas fa-birthday-cake"></i><span><?= date('d M Y', strtotime($row['dob'])) ?> &nbsp;<strong>(<?= $age_str ?>)</strong></span></div>
          <?php endif; ?>
        </div>

        <!-- Address -->
        <div>
          <div class="usec-title"><i class="fas fa-map-marker-alt"></i> Address</div>
          <div class="addr-block">
            <?php if(!empty($row['address'])): ?>
              <?= htmlspecialchars($row['address']) ?>
              <?php if(!empty($row['pin'])): ?>
              <div><span class="pin-pill"><i class="fas fa-thumbtack"></i>PIN <?= htmlspecialchars($row['pin']) ?></span></div>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:#aaa;font-size:12px">No address provided</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="action-col">
          <div class="usec-title" style="justify-content:flex-end"><i class="fas fa-cog"></i> Actions</div>
          <?php if($is_active): ?>
            <a href="toggle_user.php?id=<?= $row['ui'] ?>&status=1"
               class="btn-deactivate"
               onclick="return confirm('Deactivate this user?')">
              <i class="fas fa-ban"></i> Deactivate
            </a>
          <?php else: ?>
            <a href="toggle_user.php?id=<?= $row['ui'] ?>&status=2"
               class="btn-activate"
               onclick="return confirm('Activate this user?')">
              <i class="fas fa-check"></i> Activate
            </a>
          <?php endif; ?>
          <a href="delete_user.php?id=<?= $row['ui'] ?>"
             class="btn-del"
             onclick="return confirm('Permanently delete this user? This cannot be undone.')">
            <i class="fas fa-trash"></i> Delete
          </a>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <div class="ucf">
      <div class="fm">
        <span><i class="fas fa-hashtag"></i>UID-<?= str_pad($row['ui'],6,'0',STR_PAD_LEFT) ?></span>
        <span><i class="fas fa-clock"></i>Joined <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></span>
        <?php if($is_active): ?>
        <span style="color:#16a34a;font-weight:700"><i class="fas fa-circle" style="font-size:7px;color:#22c55e"></i> Active Account</span>
        <?php else: ?>
        <span style="color:#6b7280;font-weight:700"><i class="fas fa-circle" style="font-size:7px;color:#9ca3af"></i> Inactive Account</span>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- end user-card -->
  <?php endforeach; ?>
  <?php endif; ?>

  </div><!-- end users-grid -->

</div><!-- end page-inner -->

<!-- TOAST -->
<?php if($toast): ?>
<div class="toast-fixed <?= $toast['type'] ?>" id="adminToast">
  <i class="fas <?= $toast['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/kaiadmin.min.js"></script>
<script>
// Dynamic search — debounced, submits on each keystroke
(function(){
  const form   = document.getElementById('filterForm');
  const search = document.getElementById('searchInput');
  const sort   = document.getElementById('sortSelect');
  let timer;

  function submitForm(){
    form.submit();
  }

  // Debounce search input: wait 350ms after last keystroke
  search.addEventListener('input', function(){
    clearTimeout(timer);
    timer = setTimeout(submitForm, 350);
  });

  // Sort submits instantly on change
  sort.addEventListener('change', submitForm);
})();

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