<?php
session_name('admin_session');
session_start();
require('connect.php');

if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$result   = mysqli_query($con, "SELECT * FROM admin_master WHERE ai='$admin_id'");
$admin    = mysqli_fetch_assoc($result);

$msg_success = '';
$msg_error   = '';

// ── Update Profile ────────────────────────────────────────────
if(isset($_POST['update_profile'])) {
    $aname = mysqli_real_escape_string($con, $_POST['aname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $mobno = mysqli_real_escape_string($con, $_POST['mobno']);

    $photo = $admin['photo'];
    if(!empty($_FILES['photo']['name'])) {
        $newPhoto = basename($_FILES['photo']['name']);
        $tmp      = $_FILES['photo']['tmp_name'];
        $dst      = "./images/admin_profile/" . $newPhoto;
        if(move_uploaded_file($tmp, $dst)) $photo = $newPhoto;
    }

    $q = mysqli_query($con, "UPDATE admin_master SET
        aname='$aname', email='$email', mobno='$mobno', photo='$photo'
        WHERE ai='$admin_id'");

    if($q) {
        $_SESSION['admin_name']  = $aname;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_photo'] = $photo;
        $msg_success = "Profile updated successfully.";
        $result = mysqli_query($con, "SELECT * FROM admin_master WHERE ai='$admin_id'");
        $admin  = mysqli_fetch_assoc($result);
    } else {
        $msg_error = "Update failed. Please try again.";
    }
}

// ── Change Password ───────────────────────────────────────────
if(isset($_POST['change_password'])) {
    $current  = $_POST['current_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm  = $_POST['confirm_pass'];

    if($admin['pass'] !== $current) {
        $msg_error = "Current password is incorrect.";
    } elseif($new_pass !== $confirm) {
        $msg_error = "New passwords do not match.";
    } elseif(strlen($new_pass) < 8) {
        $msg_error = "Password must be at least 8 characters.";
    } else {
        $q = mysqli_query($con, "UPDATE admin_master SET pass='$new_pass' WHERE ai='$admin_id'");
        $msg_success = $q ? "Password changed successfully." : "Failed. Please try again.";
    }
}

// ── Booking Stats from booking_details ───────────────────────
$r_total    = mysqli_query($con, "SELECT details_id FROM booking_details");
$r_pending  = mysqli_query($con, "SELECT details_id FROM booking_details WHERE booking_status='Pending'");
$r_approved = mysqli_query($con, "SELECT details_id FROM booking_details WHERE booking_status='Approved'");
$r_rejected = mysqli_query($con, "SELECT details_id FROM booking_details WHERE booking_status='Rejected'");

$total    = $r_total    ? mysqli_num_rows($r_total)    : 0;
$pending  = $r_pending  ? mysqli_num_rows($r_pending)  : 0;
$approved = $r_approved ? mysqli_num_rows($r_approved) : 0;
$rejected = $r_rejected ? mysqli_num_rows($r_rejected) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Profile — CarBook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --admin:       #7c3aed;
      --admin-dark:  #5b21b6;
      --admin-light: #ede9fe;
      --admin-pale:  #f5f3ff;
      --black:       #0f1923;
      --white:       #ffffff;
      --off:         #f4f6fb;
      --gray:        #6c7a8d;
      --border:      #e2e8f0;
      --error:       #e74c3c;
      --error-bg:    #fdf0ef;
      --success:     #2a9d8f;
      --success-bg:  #e0f5f3;
      --warn:        #f39c12;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--off);
      color: var(--black);
      min-height: 100vh;
      padding: 32px 16px 48px;
    }

    .page-wrap { max-width: 860px; margin: 0 auto; }

    /* ── Page heading ── */
    .page-head {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 22px;
    }
    .page-head h1 {
      font-family: 'Syne', sans-serif;
      font-size: 22px; font-weight: 800; color: var(--black); letter-spacing: -0.02em;
    }
    .page-head h1 span { color: var(--admin); }
    .back-link {
      font-size: 13px; color: var(--gray); text-decoration: none;
      display: flex; align-items: center; gap: 5px; transition: color 0.15s;
    }
    .back-link:hover { color: var(--admin); }

    /* ── Alert ── */
    .alert-bar {
      border-radius: 10px; padding: 11px 16px; font-size: 13px; font-weight: 500;
      display: flex; align-items: center; gap: 9px; margin-bottom: 20px;
      animation: fadeIn 0.25s ease;
    }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:translateY(0)} }
    .alert-ok  { background: var(--success-bg); color: #1f7a6e; border-left: 3px solid var(--success); }
    .alert-err { background: var(--error-bg);   color: #c0392b; border-left: 3px solid var(--error); }

    /* ── Stats strip ── */
    .stats-strip {
      display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 22px;
    }
    .stat-box {
      background: var(--white); border: 1px solid var(--border);
      border-radius: 12px; padding: 16px 16px;
      display: flex; align-items: center; gap: 12px;
    }
    .stat-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; flex-shrink: 0;
    }
    .si-v { background: var(--admin-light); color: var(--admin); }
    .si-p { background: #fef3e2;            color: var(--warn); }
    .si-a { background: var(--success-bg);  color: var(--success); }
    .si-r { background: var(--error-bg);    color: var(--error); }
    .stat-num { font-family:'Syne',sans-serif; font-size:22px; font-weight:800; line-height:1; letter-spacing:-0.02em; }
    .stat-lbl { font-size:11px; color:var(--gray); font-weight:500; margin-top:2px; }

    /* ── Two col ── */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }

    /* ── Card ── */
    .card { background: var(--white); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
    .card-head {
      padding: 15px 20px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 9px;
    }
    .ch-icon {
      width: 30px; height: 30px; border-radius: 8px;
      background: var(--admin-light); color: var(--admin);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0;
    }
    .ch-title { font-family:'Syne',sans-serif; font-size:13.5px; font-weight:700; color:var(--black); }
    .card-body { padding: 20px; }

    /* ── Avatar row ── */
    .avatar-row {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--border);
    }
    .av-circle {
      width: 64px; height: 64px; border-radius: 50%;
      border: 3px solid var(--admin-light);
      background: var(--admin-pale);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800;
      color: var(--admin); overflow: hidden; flex-shrink: 0;
      cursor: pointer; position: relative;
    }
    .av-circle img { width:100%;height:100%;object-fit:cover;border-radius:50%; }
    .av-circle .cam-ov {
      position:absolute;inset:0;border-radius:50%;
      background:rgba(124,58,237,0.5);
      display:flex;align-items:center;justify-content:center;
      opacity:0;transition:opacity 0.18s;color:white;font-size:16px;
    }
    .av-circle:hover .cam-ov { opacity:1; }
    .av-name { font-family:'Syne',sans-serif; font-size:15px; font-weight:800; color:var(--black); }
    .av-badge {
      display:inline-flex;align-items:center;gap:4px;
      background:var(--admin-light);color:var(--admin);
      font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;
      padding:2px 9px;border-radius:20px;margin-top:5px;
    }

    /* ── Fields ── */
    .field { margin-bottom: 14px; }
    .flabel {
      font-size:10.5px;font-weight:700;letter-spacing:0.08em;
      text-transform:uppercase;color:var(--gray);margin-bottom:6px;display:block;
    }
    .finput {
      width:100%;border:1.5px solid var(--border);border-radius:8px;
      padding:9px 13px;font-size:13.5px;font-family:'DM Sans',sans-serif;
      background:var(--off);color:var(--black);
      transition:border-color 0.16s,background 0.16s,box-shadow 0.16s;
    }
    .finput:focus { outline:none;border-color:var(--admin);background:var(--white);box-shadow:0 0 0 3px rgba(124,58,237,0.11); }
    .finput::placeholder { color:#aab5c4; }
    .finput.ok  { border-color:var(--success)!important;background:var(--white); }
    .finput.err { border-color:var(--error)!important;background:var(--error-bg); }
    .finput-file {
      width:100%;border:1.5px dashed var(--border);border-radius:8px;
      padding:8px 13px;font-size:13px;font-family:'DM Sans',sans-serif;
      background:var(--off);color:var(--gray);cursor:pointer;transition:border-color 0.16s;
    }
    .finput-file:hover { border-color:var(--admin); }
    .ferr { font-size:11px;color:var(--error);margin-top:4px;display:none;align-items:center;gap:4px;font-weight:500; }
    .ferr.show { display:flex; }
    .ferr::before { content:'⚠';font-size:10px; }

    /* ── Pass wrapper ── */
    .pw { position:relative; }
    .pw .finput { padding-right:40px; }
    .eye-btn {
      position:absolute;right:11px;top:50%;transform:translateY(-50%);
      background:none;border:none;cursor:pointer;color:var(--gray);
      display:flex;align-items:center;transition:color 0.14s;padding:0;
    }
    .eye-btn:hover { color:var(--admin); }
    .eye-btn svg { width:16px;height:16px;display:block; }

    /* Strength */
    .s-bar { display:flex;gap:3px;margin-top:6px; }
    .s-bar span { flex:1;height:3px;border-radius:4px;background:var(--border);transition:background 0.3s; }
    .s-bar span.w{background:#e74c3c;} .s-bar span.f{background:var(--warn);}
    .s-bar span.g{background:#3498db;} .s-bar span.s{background:var(--success);}
    .s-lbl { font-size:10.5px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;margin-top:4px;display:none; }
    .s-lbl.show{display:block;} .s-lbl.w{color:#e74c3c;} .s-lbl.f{color:var(--warn);} .s-lbl.g{color:#3498db;} .s-lbl.s{color:var(--success);}

    /* Rules */
    .p-rules { background:var(--off);border:1px solid var(--border);border-radius:8px;padding:10px 13px;margin-top:7px;display:none; }
    .p-rules.show { display:block; }
    .p-rules p { font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--gray);margin-bottom:7px; }
    .prule { display:flex;align-items:center;gap:7px;font-size:12px;color:var(--gray);margin-bottom:4px; }
    .prule:last-child { margin-bottom:0; }
    .prule .ri { width:14px;height:14px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;flex-shrink:0; }
    .prule.met { color:var(--success); }
    .prule.met .ri { background:var(--success-bg);color:var(--success); }

    /* ── Button ── */
    .btn-save {
      width:100%;padding:11px;background:var(--admin);color:white;
      border:none;border-radius:8px;font-family:'Syne',sans-serif;
      font-size:13px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;
      cursor:pointer;transition:background 0.16s,transform 0.1s,box-shadow 0.16s;
      box-shadow:0 4px 14px rgba(124,58,237,0.28);margin-top:18px;
      display:flex;align-items:center;justify-content:center;gap:7px;
    }
    .btn-save:hover { background:var(--admin-dark);transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,58,237,0.34); }
    .btn-save:active { transform:translateY(0); }

    @media(max-width:680px) {
      .two-col { grid-template-columns:1fr; }
      .stats-strip { grid-template-columns:1fr 1fr; }
    }
  </style>
</head>
<body>
<div class="page-wrap">

  <!-- Heading -->
  <div class="page-head">
    <h1>My <span>Profile</span></h1>
    <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>

  <!-- Alerts -->
  <?php if($msg_success): ?>
    <div class="alert-bar alert-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msg_success) ?></div>
  <?php endif; ?>
  <?php if($msg_error): ?>
    <div class="alert-bar alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($msg_error) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-strip">
    <div class="stat-box">
      <div class="stat-icon si-v"><i class="bi bi-calendar2-check"></i></div>
      <div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Total Bookings</div></div>
    </div>
    <div class="stat-box">
      <div class="stat-icon si-p"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="stat-num"><?= $pending ?></div><div class="stat-lbl">Pending</div></div>
    </div>
    <div class="stat-box">
      <div class="stat-icon si-a"><i class="bi bi-check-circle"></i></div>
      <div><div class="stat-num"><?= $approved ?></div><div class="stat-lbl">Approved</div></div>
    </div>
    <div class="stat-box">
      <div class="stat-icon si-r"><i class="bi bi-x-circle"></i></div>
      <div><div class="stat-num"><?= $rejected ?></div><div class="stat-lbl">Rejected</div></div>
    </div>
  </div>

  <!-- Two col -->
  <div class="two-col">

    <!-- Edit Profile -->
    <div class="card">
      <div class="card-head">
        <div class="ch-icon"><i class="bi bi-person-fill"></i></div>
        <div class="ch-title">Edit Profile</div>
      </div>
      <div class="card-body">
        <div class="avatar-row">
          <label for="quickPhoto" style="cursor:pointer">
            <div class="av-circle" id="avCircle">
              <?php if(!empty($admin['photo']) && file_exists("./images/admin_profile/".$admin['photo'])): ?>
                <img id="avImg" src="./images/admin_profile/<?= htmlspecialchars($admin['photo']) ?>" alt=""/>
              <?php else: ?>
                <span id="avInit"><?= strtoupper(substr($admin['aname'],0,1)) ?></span>
              <?php endif; ?>
              <div class="cam-ov"><i class="bi bi-camera-fill"></i></div>
            </div>
          </label>
          <div>
            <div class="av-name"><?= htmlspecialchars($admin['aname']) ?></div>
            <div class="av-badge"><i class="bi bi-shield-fill-check"></i> Super Admin</div>
          </div>
        </div>

        <form method="post" enctype="multipart/form-data" novalidate>
          <input type="file" id="quickPhoto" name="photo" accept="image/*" style="display:none" onchange="previewAv(this)"/>

          <div class="field">
            <label class="flabel">Full Name <span style="color:var(--error)">*</span></label>
            <input type="text" class="finput" name="aname" id="f-name" value="<?= htmlspecialchars($admin['aname']) ?>" placeholder="John Doe"/>
            <div class="ferr" id="err-name">Name is required.</div>
          </div>
          <div class="field">
            <label class="flabel">Email Address <span style="color:var(--error)">*</span></label>
            <input type="email" class="finput" name="email" id="f-email" value="<?= htmlspecialchars($admin['email']) ?>" placeholder="admin@example.com"/>
            <div class="ferr" id="err-email">Enter a valid email.</div>
          </div>
          <div class="field">
            <label class="flabel">Mobile Number <span style="color:var(--error)">*</span></label>
            <input type="tel" class="finput" name="mobno" id="f-mob" value="<?= htmlspecialchars($admin['mobno'] ?? '') ?>" placeholder="9876543210" maxlength="10"/>
            <div class="ferr" id="err-mob">Enter a 10-digit number.</div>
          </div>
          <div class="field">
            <label class="flabel">Change Photo</label>
            <input type="file" class="finput-file" name="photo" accept="image/*" onchange="previewAv(this)"/>
          </div>

          <button type="submit" name="update_profile" class="btn-save" onclick="return validateProfile(event)">
            <i class="bi bi-check-lg"></i> Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-head">
        <div class="ch-icon"><i class="bi bi-lock-fill"></i></div>
        <div class="ch-title">Change Password</div>
      </div>
      <div class="card-body">
        <form method="post" novalidate>

          <div class="field">
            <label class="flabel">Current Password <span style="color:var(--error)">*</span></label>
            <div class="pw">
              <input type="password" class="finput" name="current_pass" id="p-cur" placeholder="Current password"/>
              <button type="button" class="eye-btn" onclick="toggleEye('p-cur')">
                <svg id="eye-p-cur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="ferr" id="err-pcur">Current password is required.</div>
          </div>

          <div class="field">
            <label class="flabel">New Password <span style="color:var(--error)">*</span></label>
            <div class="pw">
              <input type="password" class="finput" name="new_pass" id="p-new" placeholder="Min. 8 characters"
                oninput="checkStrength(this.value)" onfocus="showRules()" onblur="hideRules()"/>
              <button type="button" class="eye-btn" onclick="toggleEye('p-new')">
                <svg id="eye-p-new" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="s-bar"><span id="sb1"></span><span id="sb2"></span><span id="sb3"></span><span id="sb4"></span></div>
            <div class="s-lbl" id="sLbl"></div>
            <div class="p-rules" id="pRules">
              <p>Must include:</p>
              <div class="prule" id="r-len"><div class="ri">✕</div> 8+ characters</div>
              <div class="prule" id="r-up"><div class="ri">✕</div> Uppercase letter</div>
              <div class="prule" id="r-lo"><div class="ri">✕</div> Lowercase letter</div>
              <div class="prule" id="r-num"><div class="ri">✕</div> Number (0–9)</div>
              <div class="prule" id="r-sp"><div class="ri">✕</div> Special character</div>
            </div>
            <div class="ferr" id="err-pnew">Weak password — see requirements above.</div>
          </div>

          <div class="field">
            <label class="flabel">Confirm Password <span style="color:var(--error)">*</span></label>
            <div class="pw">
              <input type="password" class="finput" name="confirm_pass" id="p-conf" placeholder="Repeat password" oninput="checkMatch()"/>
              <button type="button" class="eye-btn" onclick="toggleEye('p-conf')">
                <svg id="eye-p-conf" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="ferr" id="err-pconf">Passwords do not match.</div>
          </div>

          <button type="submit" name="change_password" class="btn-save" onclick="return validatePassword(event)">
            <i class="bi bi-shield-lock-fill"></i> Update Password
          </button>
        </form>
      </div>
    </div>

  </div><!-- end two-col -->
</div><!-- end page-wrap -->

<script>
  /* ── Avatar preview ── */
  function previewAv(input) {
    if(!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      const c = document.getElementById('avCircle');
      const init = document.getElementById('avInit');
      if(init) init.remove();
      let img = document.getElementById('avImg');
      if(!img) {
        img = document.createElement('img');
        img.id = 'avImg';
        c.insertBefore(img, c.querySelector('.cam-ov'));
      }
      img.src = e.target.result;
      img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
    };
    reader.readAsDataURL(input.files[0]);
    // Sync both file inputs
    const other = (input.id === 'quickPhoto')
      ? document.querySelector('input[type="file"].finput-file')
      : document.getElementById('quickPhoto');
    if(other) { try { const dt = new DataTransfer(); dt.items.add(input.files[0]); other.files = dt.files; } catch(e){} }
  }

  /* ── Eye toggle ── */
  function toggleEye(id) {
    const f   = document.getElementById(id);
    const eye = document.getElementById('eye-' + id);
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    eye.innerHTML = show
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  }

  /* ── Password strength ── */
  function checkStrength(v) {
    const r = { len:v.length>=8, up:/[A-Z]/.test(v), lo:/[a-z]/.test(v), num:/[0-9]/.test(v), sp:/[!@#$%^&*()\-_=+\[\]{};:'",.<>?\/\\|`~]/.test(v) };
    setRule('r-len',r.len); setRule('r-up',r.up); setRule('r-lo',r.lo); setRule('r-num',r.num); setRule('r-sp',r.sp);
    document.getElementById('pRules').classList.toggle('show', v.length > 0);

    const score = Object.values(r).filter(Boolean).length;
    ['sb1','sb2','sb3','sb4'].forEach(b => document.getElementById(b).className = '');
    const lbl = document.getElementById('sLbl');
    lbl.className = 's-lbl';
    if(!v.length) return;
    const lvl   = score <= 1 ? 1 : score === 2 ? 2 : score <= 4 ? 3 : 4;
    const cmap  = {1:'w', 2:'f', 3:'g', 4:'s'};
    const names = {1:'Weak', 2:'Fair', 3:'Good', 4:'Strong'};
    ['sb1','sb2','sb3','sb4'].slice(0, lvl).forEach(b => document.getElementById(b).className = cmap[lvl]);
    lbl.textContent = names[lvl];
    lbl.classList.add('show');
    lbl.classList.add(cmap[lvl]);
  }
  function setRule(id, met) {
    const el = document.getElementById(id);
    el.className = 'prule' + (met ? ' met' : '');
    el.querySelector('.ri').textContent = met ? '✓' : '✕';
  }
  function showRules() { if(document.getElementById('p-new').value) document.getElementById('pRules').classList.add('show'); }
  function hideRules() { if(!document.getElementById('p-new').value) document.getElementById('pRules').classList.remove('show'); }

  /* ── Confirm match ── */
  function checkMatch() {
    const nv = document.getElementById('p-new').value;
    const cv = document.getElementById('p-conf').value;
    const f  = document.getElementById('p-conf');
    const er = document.getElementById('err-pconf');
    if(!cv) { f.classList.remove('ok','err'); er.classList.remove('show'); return; }
    const match = nv === cv;
    f.classList.toggle('ok', match); f.classList.toggle('err', !match);
    er.classList.toggle('show', !match);
  }

  /* ── Profile validation ── */
  function validateProfile(e) {
    let ok = true;
    function chk(id, errId, fn) {
      const el = document.getElementById(id);
      const er = document.getElementById(errId);
      if(fn(el.value)) { el.classList.add('ok'); el.classList.remove('err'); er.classList.remove('show'); }
      else { el.classList.add('err'); el.classList.remove('ok'); er.classList.add('show'); ok = false; }
    }
    chk('f-name',  'err-name',  v => v.trim().length >= 2);
    chk('f-email', 'err-email', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()));
    chk('f-mob',   'err-mob',   v => /^[0-9]{10}$/.test(v.trim()));
    if(!ok) e.preventDefault();
    return ok;
  }

  /* ── Password validation ── */
  function validatePassword(e) {
    let ok = true;
    const cur  = document.getElementById('p-cur');
    const nw   = document.getElementById('p-new');
    const conf = document.getElementById('p-conf');

    if(!cur.value) { cur.classList.add('err'); document.getElementById('err-pcur').classList.add('show'); ok = false; }
    else { cur.classList.remove('err'); cur.classList.add('ok'); document.getElementById('err-pcur').classList.remove('show'); }

    const v = nw.value;
    const strong = v.length>=8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && /[!@#$%^&*()\-_=+\[\]{};:'",.<>?\/\\|`~]/.test(v);
    if(!strong) { nw.classList.add('err'); document.getElementById('err-pnew').classList.add('show'); document.getElementById('pRules').classList.add('show'); ok = false; }
    else { nw.classList.remove('err'); nw.classList.add('ok'); document.getElementById('err-pnew').classList.remove('show'); }

    if(!conf.value || conf.value !== nw.value) { conf.classList.add('err'); document.getElementById('err-pconf').classList.add('show'); ok = false; }
    else { conf.classList.remove('err'); conf.classList.add('ok'); document.getElementById('err-pconf').classList.remove('show'); }

    if(!ok) e.preventDefault();
    return ok;
  }

  /* ── Live validation ── */
  document.addEventListener('DOMContentLoaded', () => {
    const lv = (id, errId, fn) => {
      const el = document.getElementById(id);
      if(!el) return;
      el.addEventListener('input', () => {
        const er = document.getElementById(errId);
        if(!el.value) { el.classList.remove('ok','err'); er.classList.remove('show'); return; }
        const pass = fn(el.value);
        el.classList.toggle('ok', pass); el.classList.toggle('err', !pass);
        er.classList.toggle('show', !pass);
      });
    };
    lv('f-name',  'err-name',  v => v.trim().length >= 2);
    lv('f-email', 'err-email', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()));
    lv('f-mob',   'err-mob',   v => /^[0-9]{10}$/.test(v.trim()));
    document.getElementById('f-mob').addEventListener('keypress', e => { if(!/[0-9]/.test(e.key)) e.preventDefault(); });
  });
</script>
</body>
</html>