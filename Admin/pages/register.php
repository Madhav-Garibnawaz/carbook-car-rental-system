<?php
session_name('admin_session');
session_start();
require('connect.php');
require "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Auto-add first_login column if it doesn't exist ─────────
$colCheck = mysqli_query($con, "SHOW COLUMNS FROM admin_master LIKE 'first_login'");
if(mysqli_num_rows($colCheck) == 0) {
    // Add column — existing admins default to 0 (already logged in, no key needed)
    mysqli_query($con, "ALTER TABLE admin_master ADD COLUMN first_login TINYINT(1) NOT NULL DEFAULT 0");
}

// ── Admin Login ──────────────────────────────────────────────
if(isset($_POST['login'])) {
    $loginEmail    = $_POST['loginEmail'];
    $loginPassword = $_POST['loginPassword'];

    // First, find the admin by email + password
    $q = mysqli_query($con, "SELECT * FROM admin_master 
                              WHERE email='$loginEmail' 
                              AND pass='$loginPassword'
                              AND status=1");

    if(mysqli_num_rows($q) > 0) {
        $admin = mysqli_fetch_assoc($q);

        // ── Determine if this is their first login ────────────
        // first_login = 1 means they have NEVER logged in before (secret key required)
        // first_login = 0 means they've already done first login (no secret key needed)
        if($admin['first_login'] == 1) {
            // Secret key is required on first login
            $loginSecretKey = isset($_POST['loginSecretKey']) ? $_POST['loginSecretKey'] : '';

            if($loginSecretKey !== $admin['secret_key']) {
                echo "<script>alert('Invalid Secret Key. Please check the key sent to your email.');</script>";
            } else {
                // Mark first_login as done so secret key is never asked again
                mysqli_query($con, "UPDATE admin_master SET first_login=0 WHERE ai='{$admin['ai']}'");

                $_SESSION['admin_id']    = $admin['ai'];
                $_SESSION['admin_name']  = $admin['aname'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_photo'] = $admin['photo'];

                echo "<script>
                alert('Login Successful! Welcome, {$admin['aname']}');
                window.location='index.php';
                </script>";
            }
        } else {
            // Normal login — no secret key needed
            $_SESSION['admin_id']    = $admin['ai'];
            $_SESSION['admin_name']  = $admin['aname'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_photo'] = $admin['photo'];

            echo "<script>
            alert('Login Successful! Welcome, {$admin['aname']}');
            window.location='index.php';
            </script>";
        }
    } else {
        echo "<script>alert('Invalid email or password. Please try again.');</script>";
    }
}

// ── Admin Register ───────────────────────────────────────────
if(isset($_POST['register'])) {
    $aname = $_POST['aname'];
    $email = $_POST['email'];
    $mobno = $_POST['mobno'];
    $pass  = $_POST['pass'];

    // Photo upload
    $photo = $_FILES['photo']['name'];
    $tmp   = $_FILES['photo']['tmp_name'];
    if (!empty($photo)) {
        $dst = "./images/admin_profile/" . $photo;
        move_uploaded_file($tmp, $dst);
    } else {
        $photo = '';
    }

    // Check duplicate email
    // Check duplicate email
$check_email = mysqli_query($con, "SELECT * FROM admin_master WHERE email='$email'");
// Check duplicate mobile
$check_mobile = mysqli_query($con, "SELECT * FROM admin_master WHERE mobno='$mobno'");
// Validate Indian mobile number (starts with 6, 7, 8, or 9)
$valid_mobile = preg_match('/^[6-9][0-9]{9}$/', $mobno);

if(!$valid_mobile) {
    echo "<script>alert('Invalid mobile number. Indian numbers must start with 6, 7, 8, or 9.');</script>";
} elseif(mysqli_num_rows($check_email) > 0) {
    echo "<script>alert('Email already registered as admin.');</script>";
} elseif(mysqli_num_rows($check_mobile) > 0) {
    echo "<script>alert('Mobile number already registered as admin.');</script>";
} else {

        // ── Generate 8-char alphanumeric Secret Key ──────────────
        $chars      = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $secret_key = '';
        for ($i = 0; $i < 8; $i++) {
            $secret_key .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // first_login=1 means secret key will be required on their very first sign-in
        $q = mysqli_query($con, "INSERT INTO admin_master 
            (aname, email, mobno, pass, photo, secret_key, first_login, status)
            VALUES
            ('$aname','$email','$mobno','$pass','$photo','$secret_key',1,1)");

        if($q) {
            // ── Send Secret Key via Email ─────────────────────────
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = "smtp.gmail.com";
                $mail->SMTPAuth   = true;
                $mail->Username   = "your-email@gmail.com";
                $mail->Password   = "YOUR_APP_PASSWORD";
                $mail->SMTPSecure = "tls";
                $mail->Port       = 587;

                $mail->setFrom("your-email@gmail.com", "CarBook Admin");
                $mail->addAddress($email, $aname);

                $mail->isHTML(true);
                $mail->Subject = "Your Admin Secret Key — CarBook";
                $mail->Body    = "
                    <div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f9f6ff;border-radius:12px;'>
                        <h2 style='color:#5b21b6;margin-bottom:4px;'>Welcome, $aname!</h2>
                        <p style='color:#6c7a8d;font-size:14px;margin-bottom:24px;'>Your admin account has been created on <strong>CarBook</strong>.</p>
                        <p style='color:#0f1923;font-size:14px;margin-bottom:8px;'>Your <strong>Secret Key</strong> for your <u>first</u> admin login:</p>
                        <div style='background:#7c3aed;color:#fff;font-size:28px;font-weight:800;letter-spacing:6px;text-align:center;padding:20px 32px;border-radius:10px;margin-bottom:20px;font-family:monospace;'>
                            $secret_key
                        </div>
                        <p style='color:#e74c3c;font-size:12px;'>⚠️ This key is required <strong>only once</strong> — on your very first sign-in. After that, you can log in with just your email and password.</p>
                        <hr style='border:none;border-top:1px solid #dde6f0;margin:20px 0;'>
                        <p style='color:#aab5c4;font-size:11px;'>This is an automated message. Do not reply.</p>
                    </div>";

                $mail->send();
                echo "<script>alert('Admin Account Created! Your one-time Secret Key has been sent to $email. You will only need it for your first sign-in.');</script>";
            } catch(Exception $e) {
                echo "<script>alert('Account created but email failed: " . $mail->ErrorInfo . "');</script>";
            }

        } else {
            echo "<script>alert('Registration Failed. Please try again.');</script>";
        }
    }
}

// ── Check if visiting admin needs secret key (for JS) ────────
// We do a lightweight AJAX-friendly check when the email is typed
if(isset($_POST['check_first_login'])) {
    $email = $_POST['email'];
    $q = mysqli_query($con, "SELECT first_login FROM admin_master WHERE email='$email' AND status=1");
    if(mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        echo json_encode(['found' => true, 'first_login' => (int)$row['first_login']]);
    } else {
        echo json_encode(['found' => false, 'first_login' => 0]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AutoDrive — Admin Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #1a4b8c;
      --blue-dark: #123570;
      --black: #0f1923;
      --white: #ffffff;
      --off: #f2f6fb;
      --gray: #6c7a8d;
      --light: #dde6f0;
      --border: #c8d6e8;
      --admin: #7c3aed;
      --admin-dark: #5b21b6;
      --admin-light: #ede9fe;
      --error: #e74c3c;
      --error-light: #fdf0ef;
      --success: #2a9d8f;
      --success-light: #e0f5f3;
      --warn: #f39c12;
    }

    body {
      background-color: var(--off);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px 16px;
      position: relative;
      overflow-x: hidden;
    }
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image:
        radial-gradient(circle at 12% 12%, rgba(124,58,237,0.09) 0%, transparent 50%),
        radial-gradient(circle at 88% 88%, rgba(42,157,143,0.09) 0%, transparent 50%);
      pointer-events: none; z-index: 0;
    }
    body::after {
      content: 'ADMIN';
      position: fixed; bottom: -30px; right: -30px;
      font-family: 'Syne', sans-serif;
      font-size: clamp(80px, 18vw, 200px); font-weight: 800;
      color: rgba(124,58,237,0.04);
      pointer-events: none; letter-spacing: -0.02em; line-height: 1; z-index: 0;
    }

    /* ─── PAGE WRAPPER ─── */
    .page-wrapper { width: 100%; position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; }

    .card-wrapper { width: 100%; max-width: 460px; transition: max-width 0.4s cubic-bezier(.4,0,.2,1); }
    .card-wrapper.wide { max-width: 640px; }

    /* ─── BRAND ─── */
    .brand-header { text-align: center; margin-bottom: 22px; }
    .logo-pill {
      display: inline-flex; align-items: center; gap: 12px;
      background: var(--white); border: 1px solid var(--border); border-radius: 50px;
      padding: 8px 22px 8px 10px; box-shadow: 0 2px 16px rgba(124,58,237,0.1);
    }
    .logo-icon { width: 36px; height: 36px; background: var(--admin); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .logo-icon svg { fill: white; width: 20px; height: 20px; }
    .logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--black); letter-spacing: -0.01em; }
    .logo-text span { color: var(--admin); }
    .brand-sub { margin-top: 9px; font-size: 12.5px; color: var(--gray); }
    .admin-badge-header {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--admin-light); border: 1px solid rgba(124,58,237,0.25); border-radius: 50px;
      padding: 4px 14px; font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase; color: var(--admin); margin-top: 8px;
    }

    /* ─── CARD ─── */
    .form-card { background: var(--white); border-radius: 18px; border: 1px solid var(--border); box-shadow: 0 6px 36px rgba(124,58,237,0.1); overflow: hidden; }

    /* ─── TABS ─── */
    .tab-switcher { display: flex; background: var(--off); border-bottom: 1px solid var(--border); padding: 6px; gap: 4px; }
    .tab-btn { flex: 1; padding: 10px 20px; background: none; border: none; border-radius: 10px; font-family: 'Syne', sans-serif; font-size: 12.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--gray); cursor: pointer; transition: all 0.22s ease; }
    .tab-btn.active { background: var(--white); color: var(--admin); box-shadow: 0 2px 10px rgba(124,58,237,0.12); }

    /* ─── PANELS ─── */
    .form-panel { display: none; }
    .form-panel.active { display: block; }
    #loginPanel  { padding: 36px 38px; max-width: 420px; margin: 0 auto; }
    #registerPanel { padding: 36px 38px; }

    /* ─── SECTION LABEL ─── */
    .section-label {
      font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.14em;
      text-transform: uppercase; color: var(--admin); margin-bottom: 16px; padding-bottom: 8px;
      border-bottom: 2px solid var(--admin-light); display: flex; align-items: center; gap: 8px;
    }
    .section-label::before { content: ''; width: 4px; height: 14px; background: var(--admin); border-radius: 2px; display: inline-block; flex-shrink: 0; }

    /* ─── FIELDS ─── */
    .field-group { margin-bottom: 16px; }
    .form-label { font-size: 11px; font-weight: 600; letter-spacing: 0.07em; color: var(--gray); margin-bottom: 6px; text-transform: uppercase; display: block; }
    .form-control {
      border: 1.5px solid var(--border); border-radius: 9px; font-size: 14px;
      font-family: 'DM Sans', sans-serif; background: var(--off); color: var(--black);
      padding: 10px 14px;
      transition: border-color 0.18s, background 0.18s, box-shadow 0.18s; width: 100%;
    }
    .form-control:focus { border-color: var(--admin); background: var(--white); box-shadow: 0 0 0 3px rgba(124,58,237,0.13); outline: none; }
    .form-control::placeholder { color: #aab5c4; }

    /* ─── VALIDATION STATES ─── */
    .form-control.is-valid  { border-color: var(--success) !important; background: var(--white); }
    .form-control.is-invalid { border-color: var(--error) !important; background: var(--error-light); }
    .form-control.is-invalid:focus { box-shadow: 0 0 0 3px rgba(231,76,60,0.13); border-color: var(--error) !important; }

    /* ─── INPUT WRAP — plain fields status icon ─── */
    .input-wrap { position: relative; }
    .input-wrap .form-control { padding-right: 36px; }
    .input-status {
      position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
      width: 18px; height: 18px; border-radius: 50%;
      display: none; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 700; pointer-events: none; flex-shrink: 0;
    }
    .input-wrap .form-control.is-valid  ~ .input-status { display: flex; background: var(--success-light); color: var(--success); }
    .input-wrap .form-control.is-valid  ~ .input-status::after { content: '✓'; }
    .input-wrap .form-control.is-invalid ~ .input-status { display: flex; background: #fde8e6; color: var(--error); }
    .input-wrap .form-control.is-invalid ~ .input-status::after { content: '✕'; }

    /* ─── ERROR MESSAGES ─── */
    .field-error {
      font-size: 11px; color: var(--error); margin-top: 5px;
      display: none; align-items: center; gap: 4px; font-weight: 500;
    }
    .field-error.show { display: flex; }
    .field-error::before { content: '⚠'; font-size: 10px; flex-shrink: 0; }

    /* ─── UPLOAD ─── */
    .img-upload-area {
      border: 1.5px dashed var(--border); border-radius: 9px; background: var(--off);
      padding: 14px; text-align: center; cursor: pointer; transition: all 0.18s; position: relative;
    }
    .img-upload-area:hover { border-color: var(--admin); background: var(--admin-light); }
    .img-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .img-upload-area .up-icon { font-size: 18px; display: block; margin-bottom: 3px; }
    .img-upload-area p { font-size: 12px; color: var(--gray); margin: 0; }
    .file-name { font-size: 12px; color: var(--admin); margin-top: 4px; font-weight: 500; }

    /* ─── PASSWORD WRAPPER ─── */
    .pass-wrapper { position: relative; display: flex; align-items: center; }
    .pass-wrapper .form-control { padding-right: 72px; }
    .pass-icons {
      position: absolute; right: 0; top: 0; bottom: 0;
      display: flex; align-items: center; padding-right: 10px; pointer-events: none;
    }
    .pass-status {
      width: 20px; height: 20px; border-radius: 50%;
      display: none; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 700; flex-shrink: 0;
      pointer-events: none; margin-right: 6px;
    }
    .pass-status.show-valid   { display: flex; background: var(--success-light); color: var(--success); }
    .pass-status.show-valid::after   { content: '✓'; }
    .pass-status.show-invalid { display: flex; background: #fde8e6; color: var(--error); }
    .pass-status.show-invalid::after { content: '✕'; }
    .pass-divider { width: 1px; height: 16px; background: var(--border); margin-right: 8px; flex-shrink: 0; }
    .pass-toggle {
      background: none; border: none; cursor: pointer; padding: 0;
      color: var(--gray); display: flex; align-items: center;
      pointer-events: all; transition: color 0.15s; flex-shrink: 0;
    }
    .pass-toggle:hover { color: var(--admin); }
    .pass-toggle svg { width: 17px; height: 17px; display: block; }

    /* ─── PASSWORD STRENGTH METER ─── */
    .pass-strength-bar { display: flex; gap: 4px; margin-top: 8px; }
    .pass-strength-bar span {
      flex: 1; height: 4px; border-radius: 10px;
      background: var(--light); transition: background 0.3s ease;
    }
    .pass-strength-bar span.active-weak   { background: #e74c3c; }
    .pass-strength-bar span.active-fair   { background: var(--warn); }
    .pass-strength-bar span.active-good   { background: #3498db; }
    .pass-strength-bar span.active-strong { background: var(--success); }

    .pass-strength-label {
      font-size: 11px; font-weight: 600; margin-top: 5px;
      letter-spacing: 0.06em; text-transform: uppercase; display: none;
    }
    .pass-strength-label.show { display: block; }
    .pass-strength-label.weak   { color: #e74c3c; }
    .pass-strength-label.fair   { color: var(--warn); }
    .pass-strength-label.good   { color: #3498db; }
    .pass-strength-label.strong { color: var(--success); }

    /* ─── PASSWORD INSTRUCTIONS ─── */
    .pass-instructions {
      background: var(--off); border: 1px solid var(--border);
      border-radius: 9px; padding: 12px 14px; margin-top: 8px; display: none;
    }
    .pass-instructions.show { display: block; }
    .pass-instructions p {
      font-size: 11px; font-weight: 700; letter-spacing: 0.07em;
      text-transform: uppercase; color: var(--gray); margin-bottom: 8px;
    }
    .pass-rule {
      display: flex; align-items: center; gap: 8px;
      font-size: 12.5px; color: var(--gray); margin-bottom: 5px; transition: color 0.2s;
    }
    .pass-rule:last-child { margin-bottom: 0; }
    .pass-rule .rule-icon {
      width: 16px; height: 16px; border-radius: 50%; background: var(--light);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 9px; transition: background 0.2s;
    }
    .pass-rule.met { color: var(--success); }
    .pass-rule.met .rule-icon  { background: var(--success-light); color: var(--success); }
    .pass-rule.unmet .rule-icon { background: #fde8e6; color: var(--error); }

    /* ─── SECRET KEY FIELD (login — shown only for first-timers) ─── */
    #secretKeyGroup {
      overflow: hidden;
      max-height: 0;
      opacity: 0;
      transition: max-height 0.4s cubic-bezier(.4,0,.2,1), opacity 0.3s ease, margin 0.3s ease;
      margin-bottom: 0;
    }
    #secretKeyGroup.visible {
      max-height: 120px;
      opacity: 1;
      margin-bottom: 16px;
    }
    /* One-time badge shown next to secret key label */
    .one-time-badge {
      display: inline-flex; align-items: center; gap: 4px;
      background: #fff8e1; border: 1px solid #f39c12;
      border-radius: 30px; padding: 2px 9px;
      font-size: 10px; font-weight: 700; color: #b7770d;
      letter-spacing: 0.06em; text-transform: uppercase; vertical-align: middle; margin-left: 6px;
    }

    /* ─── CHECKBOX ─── */
    .form-check-input { border-radius: 4px !important; border-color: var(--border) !important; width: 15px; height: 15px; cursor: pointer; }
    .form-check-input:checked { background-color: var(--admin) !important; border-color: var(--admin) !important; }
    .form-check-input:focus { box-shadow: 0 0 0 3px rgba(124,58,237,0.15) !important; }
    .form-check-label { font-size: 13px; color: var(--gray); cursor: pointer; }
    .form-check-input.is-invalid { border-color: var(--error) !important; }

    /* ─── LINKS ─── */
    .link-accent { color: var(--admin); text-decoration: none; font-weight: 500; }
    .link-accent:hover { text-decoration: underline; color: var(--admin-dark); }

    /* ─── BUTTONS ─── */
    .btn-submit { width: 100%; padding: 13px; background: var(--blue); border: none; border-radius: 10px; color: #fff; font-family: 'Syne', sans-serif; font-size: 13.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; cursor: pointer; transition: background 0.18s, transform 0.12s, box-shadow 0.18s; box-shadow: 0 4px 16px rgba(26,75,140,0.22); }
    .btn-submit:hover { background: var(--blue-dark); box-shadow: 0 6px 22px rgba(26,75,140,0.3); transform: translateY(-1px); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit.admin-btn { background: var(--admin); box-shadow: 0 4px 16px rgba(124,58,237,0.26); }
    .btn-submit.admin-btn:hover { background: var(--admin-dark); box-shadow: 0 6px 22px rgba(124,58,237,0.33); transform: translateY(-1px); }

    /* ─── TOAST ─── */
    .toast-msg { display: none; padding: 11px 22px; background: var(--black); color: #fff; font-size: 13px; font-weight: 500; border-left: 4px solid var(--admin); }
    .toast-msg.show { display: block; }

    /* ─── MISC ─── */
    .center-text { text-align: center; font-size: 13px; color: var(--gray); margin-top: 14px; }
    .login-badge { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 26px; }
    .login-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--admin); }
    .login-badge span { font-size: 11px; color: var(--gray); letter-spacing: 0.1em; font-family: 'Syne', sans-serif; font-weight: 700; text-transform: uppercase; }
    .hint-box { background: var(--admin-light); border: 1px solid rgba(124,58,237,0.2); border-radius: 8px; padding: 10px 14px; font-size: 11.5px; color: var(--admin-dark); margin-bottom: 10px; }

    @media (max-width: 520px) {
      #loginPanel, #registerPanel { padding: 28px 22px; }
    }
  </style>
</head>
<body>
<div class="page-wrapper">

  <div class="brand-header">
    <div class="logo-pill">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
      </div>
      <div class="logo-text">Car<span>Book</span></div>
    </div>
    <div><div class="admin-badge-header">🛡️ Admin Portal</div></div>
    <div class="brand-sub" style="margin-top:6px;">Restricted access — authorized personnel only</div>
  </div>

  <div class="card-wrapper" id="cardWrapper">
    <div class="form-card">

      <div class="tab-switcher">
        <button class="tab-btn active" id="tabLogin"    onclick="switchTab('login')"    type="button">Admin Sign In</button>
        <button class="tab-btn"        id="tabRegister" onclick="switchTab('register')" type="button">Register Admin</button>
      </div>

      <div class="toast-msg" id="toastMsg"></div>

      <!-- ══ LOGIN ══ -->
      <div class="form-panel active" id="loginPanel">
        <form method="post" id="loginForm" novalidate>
          <div class="login-badge">
            <div class="dot"></div>
            <span>Secure Admin Login</span>
            <div class="dot"></div>
          </div>

          <div class="field-group">
            <label class="form-label">Admin Email Address</label>
            <div class="input-wrap">
              <input type="email" class="form-control" placeholder="admin@autodrive.com" id="loginEmail" name="loginEmail"/>
              <span class="input-status"></span>
            </div>
            <div class="field-error" id="err-loginEmail">Please enter a valid email address.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Password</label>
            <div class="pass-wrapper">
              <input type="password" class="form-control" placeholder="••••••••" id="loginPassword" name="loginPassword"/>
              <div class="pass-icons">
                <div class="pass-status" id="ps-loginPassword"></div>
                <div class="pass-divider" id="pd-loginPassword" style="display:none"></div>
                <button type="button" class="pass-toggle" onclick="togglePass('loginPassword')" aria-label="Toggle password">
                  <svg id="eye-loginPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            <div class="field-error" id="err-loginPassword">Password is required.</div>
          </div>

          <!-- ══ SECRET KEY — shown only for first-time logins ══ -->
          <div id="secretKeyGroup">
            <label class="form-label">
              Secret Key
              <span class="one-time-badge">⚡ One-time only</span>
            </label>
            <div class="pass-wrapper">
              <input type="password" class="form-control" placeholder="8-character key" id="loginSecretKey" name="loginSecretKey" maxlength="8" style="font-family:monospace;letter-spacing:3px;font-size:15px;"/>
              <div class="pass-icons">
                <div class="pass-status" id="ps-loginSecretKey"></div>
                <div class="pass-divider" id="pd-loginSecretKey" style="display:none"></div>
                <button type="button" class="pass-toggle" onclick="togglePass('loginSecretKey')" aria-label="Toggle secret key">
                  <svg id="eye-loginSecretKey" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            <div class="field-error" id="err-loginSecretKey">Secret key must be exactly 8 characters.</div>
            <div style="font-size:11px;color:var(--gray);margin-top:5px;">
              🔐 Check your registration email for this key. You'll never need it again after this.
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check m-0">
              <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="#" class="link-accent" style="font-size:12px;">Forgot password?</a>
          </div>

          <button class="btn-submit" type="submit" name="login" onclick="return handleLoginValidation(event)">Sign In to Admin</button>
          <div class="center-text">
            Need admin access? <a href="#" class="link-accent" onclick="switchTab('register')">Register here</a>
          </div>
        </form>
      </div>

      <!-- ══ REGISTER ══ -->
      <div class="form-panel" id="registerPanel">
        <form method="post" enctype="multipart/form-data" id="registerForm" novalidate>

          <div class="section-label">Admin Information</div>

          <div class="field-group">
            <label class="form-label">Full Name <span style="color:var(--error)">*</span></label>
            <div class="input-wrap">
              <input type="text" class="form-control" placeholder="Enter Your Full Name" id="regName" name="aname"/>
              <span class="input-status"></span>
            </div>
            <div class="field-error" id="err-regName">Full name must be at least 2 characters.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Email Address <span style="color:var(--error)">*</span></label>
            <div class="input-wrap">
              <input type="email" class="form-control" placeholder="admin@autodrive.com" id="regEmail" name="email"/>
              <span class="input-status"></span>
            </div>
            <div class="field-error" id="err-regEmail">Please enter a valid email address.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Mobile Number <span style="color:var(--error)">*</span></label>
            <div class="input-wrap">
              <input type="tel" class="form-control" placeholder="9876543210" id="regMobile" name="mobno" maxlength="10"/>
              <span class="input-status"></span>
            </div>
            <div class="field-error" id="err-regMobile">Mobile number must be exactly 10 digits.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Profile Photo <span style="color:var(--gray); font-weight:400; text-transform:none; letter-spacing:0">(optional)</span></label>
            <div class="img-upload-area" id="uploadArea">
              <input type="file" accept="image/*" name="photo" onchange="showAdminFileName(this)"/>
              <span class="up-icon">📷</span>
              <p>Click to upload profile photo</p>
              <div class="file-name" id="adminFileNameDisplay"></div>
            </div>
          </div>

          <div class="section-label" style="margin-top:4px;">Security</div>

          <div class="field-group">
            <label class="form-label">Password <span style="color:var(--error)">*</span></label>
            <div class="pass-wrapper">
              <input type="password" class="form-control" placeholder="Min. 8 characters" id="regPassword" name="pass"
                oninput="checkPasswordStrength(this.value)" onfocus="showPassInstructions()" onblur="hidePassInstructions()"/>
              <div class="pass-icons">
                <div class="pass-status" id="ps-regPassword"></div>
                <div class="pass-divider" id="pd-regPassword" style="display:none"></div>
                <button type="button" class="pass-toggle" onclick="togglePass('regPassword')" aria-label="Toggle password">
                  <svg id="eye-regPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            <!-- Strength bar -->
            <div class="pass-strength-bar" id="strengthBar">
              <span id="bar1"></span><span id="bar2"></span><span id="bar3"></span><span id="bar4"></span>
            </div>
            <div class="pass-strength-label" id="strengthLabel"></div>
            <!-- Instructions -->
            <div class="pass-instructions" id="passInstructions">
              <p>Password must include:</p>
              <div class="pass-rule" id="rule-length"><div class="rule-icon">✕</div> At least 8 characters</div>
              <div class="pass-rule" id="rule-upper"><div class="rule-icon">✕</div> At least one uppercase letter (A–Z)</div>
              <div class="pass-rule" id="rule-lower"><div class="rule-icon">✕</div> At least one lowercase letter (a–z)</div>
              <div class="pass-rule" id="rule-number"><div class="rule-icon">✕</div> At least one number (0–9)</div>
              <div class="pass-rule" id="rule-special"><div class="rule-icon">✕</div> At least one special character (!@#$%^&*)</div>
            </div>
            <div class="field-error" id="err-regPassword">Password must be 8+ chars with uppercase, lowercase, number & special character.</div>
          </div>

          <div class="field-group">
            <label class="form-label">Confirm Password <span style="color:var(--error)">*</span></label>
            <div class="pass-wrapper">
              <input type="password" class="form-control" placeholder="Repeat password" id="regConfirm" oninput="checkConfirmMatch()"/>
              <div class="pass-icons">
                <div class="pass-status" id="ps-regConfirm"></div>
                <div class="pass-divider" id="pd-regConfirm" style="display:none"></div>
                <button type="button" class="pass-toggle" onclick="togglePass('regConfirm')" aria-label="Toggle password">
                  <svg id="eye-regConfirm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
            <div class="field-error" id="err-regConfirm">Passwords do not match.</div>
          </div>

          <div class="hint-box" style="margin-bottom:16px;">
            🔐 <strong>Auto Secret Key:</strong> Upon successful registration, a unique 8-character Secret Key will be generated and sent to your email. You will need this key <strong>only once</strong> — on your very first sign-in. After that, just use your email and password.
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="agreeTerms">
              <label class="form-check-label" for="agreeTerms">
                I acknowledge this is a restricted admin portal and agree to the <a href="#" class="link-accent">Admin Terms</a>
              </label>
            </div>
            <div class="field-error" id="err-terms">You must agree to the Admin Terms to continue.</div>
          </div>

          <button class="btn-submit admin-btn" type="submit" name="register" onclick="return handleRegisterValidation(event)">Create Admin Account</button>
          <div class="center-text">
            Already an admin? <a href="#" class="link-accent" onclick="switchTab('login')">Sign in</a>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>

<script>
  /* ═══════════════════════════════════════════
     TAB SWITCHER
  ═══════════════════════════════════════════ */
  function switchTab(tab) {
    const isReg = tab === 'register';
    document.getElementById('tabLogin').classList.toggle('active', !isReg);
    document.getElementById('tabRegister').classList.toggle('active', isReg);
    document.getElementById('loginPanel').classList.toggle('active', !isReg);
    document.getElementById('registerPanel').classList.toggle('active', isReg);
    document.getElementById('cardWrapper').classList.toggle('wide', isReg);
    document.getElementById('toastMsg').classList.remove('show');
  }

  /* ═══════════════════════════════════════════
     TOAST
  ═══════════════════════════════════════════ */
  function showToast(msg, isError) {
    const t = document.getElementById('toastMsg');
    t.textContent = msg;
    t.style.borderLeftColor = isError ? '#e74c3c' : '#7c3aed';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 4000);
  }

  /* ═══════════════════════════════════════════
     FILE NAME DISPLAY
  ═══════════════════════════════════════════ */
  function showAdminFileName(input) {
    document.getElementById('adminFileNameDisplay').textContent =
      input.files[0] ? '✓ ' + input.files[0].name : '';
  }

  /* ═══════════════════════════════════════════
     PASSWORD VISIBILITY TOGGLE
  ═══════════════════════════════════════════ */
  function togglePass(fieldId) {
    const field = document.getElementById(fieldId);
    const eyeIcon = document.getElementById('eye-' + fieldId);
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    eyeIcon.innerHTML = isHidden
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
         <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
         <line x1="1" y1="1" x2="23" y2="23"></line>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
         <circle cx="12" cy="12" r="3"></circle>`;
  }

  /* ═══════════════════════════════════════════
     SECRET KEY FIELD — SHOW / HIDE via AJAX
     Checks first_login flag when email is entered
  ═══════════════════════════════════════════ */
  let secretKeyRequired = false;
  let emailCheckTimer   = null;

  function checkFirstLoginStatus(email) {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) return;

    fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'check_first_login=1&email=' + encodeURIComponent(email.trim())
    })
    .then(r => r.json())
    .then(data => {
      const group = document.getElementById('secretKeyGroup');
      const skInput = document.getElementById('loginSecretKey');

      if (data.found && data.first_login === 1) {
        // First-time login — show secret key field
        secretKeyRequired = true;
        group.classList.add('visible');
        skInput.required = true;
      } else {
        // Returning admin — hide secret key field
        secretKeyRequired = false;
        group.classList.remove('visible');
        skInput.required = false;
        skInput.value = '';
        clearState(skInput, document.getElementById('err-loginSecretKey'));
      }
    })
    .catch(() => {
      // On error, silently hide the field (fail safe)
      secretKeyRequired = false;
      document.getElementById('secretKeyGroup').classList.remove('visible');
    });
  }

  /* ═══════════════════════════════════════════
     PASSWORD INSTRUCTIONS SHOW / HIDE
  ═══════════════════════════════════════════ */
  function showPassInstructions() {
    document.getElementById('passInstructions').classList.add('show');
  }
  function hidePassInstructions() {
    const val = document.getElementById('regPassword').value;
    if (!val) document.getElementById('passInstructions').classList.remove('show');
  }

  /* ═══════════════════════════════════════════
     REAL-TIME PASSWORD STRENGTH CHECKER
  ═══════════════════════════════════════════ */
  function checkPasswordStrength(val) {
    const rules = {
      length:  val.length >= 8,
      upper:   /[A-Z]/.test(val),
      lower:   /[a-z]/.test(val),
      number:  /[0-9]/.test(val),
      special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val),
    };

    updateRule('rule-length',  rules.length);
    updateRule('rule-upper',   rules.upper);
    updateRule('rule-lower',   rules.lower);
    updateRule('rule-number',  rules.number);
    updateRule('rule-special', rules.special);

    if (val.length > 0) {
      document.getElementById('passInstructions').classList.add('show');
    } else {
      document.getElementById('passInstructions').classList.remove('show');
    }

    const score = Object.values(rules).filter(Boolean).length;
    const bars  = ['bar1','bar2','bar3','bar4'];
    const classMap = ['active-weak','active-fair','active-good','active-strong'];
    const labelEl = document.getElementById('strengthLabel');

    bars.forEach(b => { document.getElementById(b).className = ''; });
    labelEl.className = 'pass-strength-label';

    if (val.length === 0) { labelEl.classList.remove('show'); return; }

    labelEl.classList.add('show');

    let level, label, cls;
    if (score <= 1)                     { level = 1; label = 'Weak';   cls = 'weak'; }
    else if (score === 2)               { level = 2; label = 'Fair';   cls = 'fair'; }
    else if (score === 3 || score === 4){ level = 3; label = 'Good';   cls = 'good'; }
    else                                { level = 4; label = 'Strong'; cls = 'strong'; }

    for (let i = 0; i < level; i++) {
      document.getElementById(bars[i]).className = classMap[level - 1];
    }
    labelEl.textContent = label;
    labelEl.classList.add(cls);

    const field = document.getElementById('regPassword');
    if (score === 5) {
      field.classList.remove('is-invalid');
      field.classList.add('is-valid');
      updatePassStatus(field, 'valid');
    } else if (val.length > 0) {
      field.classList.remove('is-valid');
      updatePassStatus(field, 'none');
    } else {
      updatePassStatus(field, 'none');
    }
  }

  function updateRule(ruleId, met) {
    const el = document.getElementById(ruleId);
    const icon = el.querySelector('.rule-icon');
    el.className = 'pass-rule ' + (met ? 'met' : (el.classList.contains('was-checked') ? 'unmet' : ''));
    icon.textContent = met ? '✓' : '✕';
  }

  /* ═══════════════════════════════════════════
     REAL-TIME CONFIRM PASSWORD CHECK
  ═══════════════════════════════════════════ */
  function checkConfirmMatch() {
    const pass    = document.getElementById('regPassword').value;
    const confirm = document.getElementById('regConfirm').value;
    const field   = document.getElementById('regConfirm');
    const errEl   = document.getElementById('err-regConfirm');
    if (!confirm) { field.classList.remove('is-valid','is-invalid'); errEl.classList.remove('show'); updatePassStatus(field,'none'); return; }
    if (pass === confirm) { setValid(field, errEl); }
    else                  { setInvalid(field, errEl, 'Passwords do not match.'); }
  }

  /* ═══════════════════════════════════════════
     HELPERS
  ═══════════════════════════════════════════ */
  function setValid(field, errEl) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    errEl.classList.remove('show');
    updatePassStatus(field, 'valid');
  }
  function setInvalid(field, errEl, msg) {
    field.classList.remove('is-valid');
    field.classList.add('is-invalid');
    if (msg) errEl.textContent = msg;
    errEl.classList.add('show');
    updatePassStatus(field, 'invalid');
  }
  function clearState(field, errEl) {
    field.classList.remove('is-valid','is-invalid');
    errEl.classList.remove('show');
    updatePassStatus(field, 'none');
  }
  function updatePassStatus(field, state) {
    const ps = document.getElementById('ps-' + field.id);
    const pd = document.getElementById('pd-' + field.id);
    if (!ps) return;
    ps.className = 'pass-status';
    if (state === 'valid')        { ps.classList.add('show-valid');   if (pd) pd.style.display = 'block'; }
    else if (state === 'invalid') { ps.classList.add('show-invalid'); if (pd) pd.style.display = 'block'; }
    else                          { if (pd) pd.style.display = 'none'; }
  }

  /* ═══════════════════════════════════════════
     LIVE VALIDATION — ON DOMContentLoaded
  ═══════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {

    function liveValidate(id, errId, validator) {
      const el  = document.getElementById(id);
      const err = document.getElementById(errId);
      if (!el || !err) return;
      el.addEventListener('input', () => { if (el.value) validator(el, err); else clearState(el, err); });
      el.addEventListener('blur',  () => { if (el.value) validator(el, err); });
    }

    // Name
    liveValidate('regName', 'err-regName', (el, err) => {
      el.value.trim().length >= 2 ? setValid(el, err) : setInvalid(el, err, 'Full name must be at least 2 characters.');
    });

    // Email
    liveValidate('regEmail', 'err-regEmail', (el, err) => {
      /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'Please enter a valid email address.');
    });

    // Mobile — digits only
    liveValidate('regMobile', 'err-regMobile', (el, err) => {
  /^[6-9][0-9]{9}$/.test(el.value.trim()) ? setValid(el, err) : setInvalid(el, err, 'Invalid mobile number. Must be 10 digits starting with 6, 7, 8, or 9.');
});
    document.getElementById('regMobile').addEventListener('keypress', function (e) {
  if (!/[0-9]/.test(e.key)) e.preventDefault();
});

/* Name field — letters and spaces only */
document.getElementById('regName').addEventListener('keypress', function(e) {
  if (!/[a-zA-Z\s]/.test(e.key)) e.preventDefault();
});
document.getElementById('regName').addEventListener('input', function() {
  this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
});

    // Password
    liveValidate('regPassword', 'err-regPassword', (el, err) => {
      const v = el.value;
      const strong = v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /[0-9]/.test(v) && /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v);
      strong ? setValid(el, err) : setInvalid(el, err, 'Password must have 8+ chars, uppercase, lowercase, number & special character.');
    });

    // Login email — live validation + AJAX check for first_login
    const loginEmailEl = document.getElementById('loginEmail');
    const loginEmailErr = document.getElementById('err-loginEmail');

    loginEmailEl.addEventListener('input', () => {
      const val = loginEmailEl.value;
      if (!val) { clearState(loginEmailEl, loginEmailErr); return; }
      if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim())) {
        setValid(loginEmailEl, loginEmailErr);
        // Debounce the AJAX call by 500ms
        clearTimeout(emailCheckTimer);
        emailCheckTimer = setTimeout(() => checkFirstLoginStatus(val), 500);
      } else {
        setInvalid(loginEmailEl, loginEmailErr, 'Please enter a valid email address.');
        // Hide secret key field if email becomes invalid
        document.getElementById('secretKeyGroup').classList.remove('visible');
        secretKeyRequired = false;
      }
    });
    loginEmailEl.addEventListener('blur', () => {
      if (loginEmailEl.value)
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(loginEmailEl.value.trim())
          ? setValid(loginEmailEl, loginEmailErr)
          : setInvalid(loginEmailEl, loginEmailErr, 'Please enter a valid email address.');
    });

    liveValidate('loginPassword', 'err-loginPassword', (el, err) => {
      el.value.length > 0 ? setValid(el, err) : setInvalid(el, err, 'Password is required.');
    });
    liveValidate('loginSecretKey', 'err-loginSecretKey', (el, err) => {
      el.value.trim().length === 8 ? setValid(el, err) : setInvalid(el, err, 'Secret key must be exactly 8 characters.');
    });

    // Terms checkbox
    document.getElementById('agreeTerms').addEventListener('change', function () {
      document.getElementById('err-terms').classList.toggle('show', !this.checked);
      this.classList.toggle('is-invalid', !this.checked);
    });
  });

  /* ═══════════════════════════════════════════
     LOGIN SUBMIT VALIDATION
  ═══════════════════════════════════════════ */
  function handleLoginValidation(e) {
    let valid = true;

    const email    = document.getElementById('loginEmail');
    const pass     = document.getElementById('loginPassword');
    const errEmail = document.getElementById('err-loginEmail');
    const errPass  = document.getElementById('err-loginPassword');

    if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
      setInvalid(email, errEmail, 'Please enter a valid email address.'); valid = false;
    } else { setValid(email, errEmail); }

    if (!pass.value) {
      setInvalid(pass, errPass, 'Password is required.'); valid = false;
    } else { setValid(pass, errPass); }

    // Only validate secret key if the field is visible (first login)
    if (secretKeyRequired) {
      const secretKey = document.getElementById('loginSecretKey');
      const errSecret = document.getElementById('err-loginSecretKey');
      if (!secretKey.value || secretKey.value.trim().length !== 8) {
        setInvalid(secretKey, errSecret, 'Secret key must be exactly 8 characters.'); valid = false;
      } else { setValid(secretKey, errSecret); }
    }

    if (!valid) {
      e.preventDefault();
      showToast('Please fix the errors before signing in.', true);
      return false;
    }
    return true;
  }

  /* ═══════════════════════════════════════════
     REGISTER SUBMIT VALIDATION
  ═══════════════════════════════════════════ */
  function handleRegisterValidation(e) {
    let valid = true;

    function v(id, errId, testFn, msg) {
      const el  = document.getElementById(id);
      const err = document.getElementById(errId);
      if (!testFn(el.value)) { setInvalid(el, err, msg); valid = false; }
      else { setValid(el, err); }
    }

    // Name
    v('regName', 'err-regName',
      val => val.trim().length >= 2,
      'Full name must be at least 2 characters.');

    // Email
    v('regEmail', 'err-regEmail',
      val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()),
      'Please enter a valid email address.');

    // Mobile
    v('regMobile', 'err-regMobile',
  val => /^[6-9][0-9]{9}$/.test(val.trim()),
  'Invalid mobile number. Must be 10 digits starting with 6, 7, 8, or 9.');

    // Password — strong check
    const passVal = document.getElementById('regPassword').value;
    const passEl  = document.getElementById('regPassword');
    const passErr = document.getElementById('err-regPassword');
    const isStrong = passVal.length >= 8
      && /[A-Z]/.test(passVal)
      && /[a-z]/.test(passVal)
      && /[0-9]/.test(passVal)
      && /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passVal);

    if (!isStrong) {
      setInvalid(passEl, passErr, 'Password must be 8+ chars with uppercase, lowercase, number & special character.');
      document.getElementById('passInstructions').classList.add('show');
      ['rule-length','rule-upper','rule-lower','rule-number','rule-special'].forEach(r => {
        document.getElementById(r).classList.add('was-checked');
      });
      checkPasswordStrength(passVal);
      valid = false;
    } else {
      setValid(passEl, passErr);
    }

    // Confirm Password
    const confirmEl  = document.getElementById('regConfirm');
    const confirmErr = document.getElementById('err-regConfirm');
    if (!confirmEl.value || confirmEl.value !== passVal) {
      setInvalid(confirmEl, confirmErr, 'Passwords do not match.'); valid = false;
    } else { setValid(confirmEl, confirmErr); }

    // Terms
    const terms    = document.getElementById('agreeTerms');
    const termsErr = document.getElementById('err-terms');
    if (!terms.checked) {
      terms.classList.add('is-invalid');
      termsErr.classList.add('show');
      valid = false;
    } else {
      terms.classList.remove('is-invalid');
      termsErr.classList.remove('show');
    }

    if (!valid) {
      e.preventDefault();
      showToast('Please fix all errors before creating your account.', true);
      const firstErr = document.querySelector('#registerPanel .is-invalid');
      if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }
    return true;
  }
</script>
</body>
</html>