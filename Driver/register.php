<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

include('connect.php');
session_name('driver_session');
session_start();

$modalTitle    = '';
$modalMessage  = '';
$modalType     = '';
$modalRedirect = '';
$activeTab     = 'login';

// Special flag for the disabled/rejected/pending account modal
$showRequestModal = false;
$blockedName      = '';
$blockedEmail     = '';
$blockedId        = 0;
$blockedReason    = ''; // 'pending' | 'rejected' | 'disabled'

// ── LOGIN handler ──────────────────────────────────────────────────────────
if (isset($_POST['btnlogin'])) {
    $driver_email = trim($_POST['driver_email']);
    $password     = trim($_POST['password']);
    $activeTab    = 'login';

    if (empty($driver_email) || empty($password)) {
        $modalTitle   = 'Missing Fields';
        $modalMessage = 'Please enter both your email and password before signing in.';
        $modalType    = 'error';
    } elseif (!filter_var($driver_email, FILTER_VALIDATE_EMAIL)) {
        $modalTitle   = 'Invalid Email';
        $modalMessage = 'The email address you entered is not in a valid format (e.g. john@example.com).';
        $modalType    = 'error';
    } else {
        $safe_email = mysqli_real_escape_string($con, $driver_email);
        $safe_pass  = mysqli_real_escape_string($con, $password);
        $q   = mysqli_query($con, "SELECT * FROM driver_master WHERE driver_email='$safe_email' AND password='$safe_pass'");
        $num = mysqli_num_rows($q);

        if ($num > 0) {
            $row = mysqli_fetch_assoc($q);

            if ($row['status'] == 1) {
                // ── Approved: log in ────────────────────────────────────
                $_SESSION['driver_id']   = $row['driver_id'];
                $_SESSION['driver_mail'] = $driver_email;
                $_SESSION['driver_name'] = $row['driver_name'];
                $modalTitle    = 'Welcome Back!';
                $modalMessage  = 'You have signed in successfully. Redirecting to your dashboard…';
                $modalType     = 'success';
                $modalRedirect = 'index.php';

            } elseif ($row['status'] == 0) {
                // ── Pending ─────────────────────────────────────────────
                $showRequestModal = true;
                $blockedReason    = 'pending';
                $blockedName      = $row['driver_name'];
                $blockedEmail     = $driver_email;
                $blockedId        = $row['driver_id'];

            } elseif ($row['status'] == 2) {
                // ── Rejected ────────────────────────────────────────────
                $showRequestModal = true;
                $blockedReason    = 'rejected';
                $blockedName      = $row['driver_name'];
                $blockedEmail     = $driver_email;
                $blockedId        = $row['driver_id'];

            } else {
                // ── Any other non-1 status = disabled ───────────────────
                $showRequestModal = true;
                $blockedReason    = 'disabled';
                $blockedName      = $row['driver_name'];
                $blockedEmail     = $driver_email;
                $blockedId        = $row['driver_id'];
            }

            // Pre-fill session so driver_request.php can auto-fill the form
            if ($showRequestModal) {
                $_SESSION['req_driver_name']  = $blockedName;
                $_SESSION['req_driver_email'] = $blockedEmail;
                $_SESSION['req_driver_id']    = $blockedId;
            }

        } else {
            $modalTitle   = 'Login Failed';
            $modalMessage = 'The email or password you entered is incorrect. Please check your credentials and try again.';
            $modalType    = 'error';
        }
    }
}

// ── REGISTER handler ───────────────────────────────────────────────────────
if (isset($_POST['btnapply'])) {
    $activeTab = 'register';
    $errors    = [];

    $driver_name         = trim($_POST['driver_name']         ?? '');
    $driver_email        = trim($_POST['driver_email']        ?? '');
    $password            = trim($_POST['password']            ?? '');
    $confirm_password    = trim($_POST['confirm_password']    ?? '');
    $driver_mobile       = trim($_POST['driver_mobile']       ?? '');
    $dob                 = trim($_POST['dob']                 ?? '');
    $doj                 = trim($_POST['doj']                 ?? '');
    $experience_years    = trim($_POST['experience_years']    ?? '');
    $license_number      = trim($_POST['license_number']      ?? '');
    $license_expiry_date = trim($_POST['license_expiry_date'] ?? '');
    $aadhar_number       = trim($_POST['aadhar_number']       ?? '');

    if (empty($driver_name))
        $errors[] = 'Full name is required.';

    if (empty($driver_email))
        $errors[] = 'Email address is required.';
    elseif (!filter_var($driver_email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Email address is not valid (e.g. john@example.com).';

    if (empty($password))
        $errors[] = 'Password is required.';
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]).{8,}$/', $password))
        $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';

    if (empty($confirm_password))
        $errors[] = 'Please confirm your password.';
    elseif ($confirm_password !== $password)
        $errors[] = 'Passwords do not match.';

    if (empty($driver_mobile))
        $errors[] = 'Mobile number is required.';
    elseif (!preg_match('/^[6-9]\d{9}$/', $driver_mobile))
        $errors[] = 'Mobile number must be a valid 10-digit Indian number (starting with 6–9).';

    // DOB: must be at least 18 years old
    if (empty($dob)) {
        $errors[] = 'Date of birth is required.';
    } else {
        $dobDate    = new DateTime($dob);
        $minAgeDate = (new DateTime('today'))->modify('-18 years');
        if ($dobDate > $minAgeDate)
            $errors[] = 'You must be at least 18 years old to register as a driver.';
    }

    // DOJ: must be a past date
    if (empty($doj)) {
        $errors[] = 'Date of joining is required.';
    } else {
        $dojDate = new DateTime($doj);
        $today   = new DateTime('today');
        if ($dojDate >= $today)
            $errors[] = 'Date of joining must be a past date (before today).';
    }

    if ($experience_years === '')
        $errors[] = 'Experience (years) is required.';
    elseif (!is_numeric($experience_years) || $experience_years < 0 || $experience_years > 50)
        $errors[] = 'Experience must be a number between 0 and 50.';

    if (empty($license_number))
        $errors[] = 'License number is required.';
    elseif (!preg_match('/^[A-Z]{2}[0-9]{13}$/', strtoupper($license_number)))
        $errors[] = 'License number format is invalid. Expected format: XX9999999999999 (2 letters + 13 digits).';

    // License expiry: must be future
    if (empty($license_expiry_date)) {
        $errors[] = 'License expiry date is required.';
    } else {
        $licExpDate = new DateTime($license_expiry_date);
        $today      = new DateTime('today');
        if ($licExpDate <= $today)
            $errors[] = 'License expiry date must be a future date.';
    }

    $aadhar_clean = preg_replace('/\s+/', '', $aadhar_number);
    if (empty($aadhar_number))
        $errors[] = 'Aadhaar number is required.';
    elseif (!preg_match('/^\d{12}$/', $aadhar_clean))
        $errors[] = 'Aadhaar number must be exactly 12 digits.';

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if (empty($_FILES['profile_image']['name']) || $_FILES['profile_image']['error'] === 4)
        $errors[] = 'Profile image is required.';
    elseif (!in_array($_FILES['profile_image']['type'], $allowed_types))
        $errors[] = 'Profile image must be a JPG, PNG, or WEBP file.';

    if (empty($_FILES['license_image']['name']) || $_FILES['license_image']['error'] === 4)
        $errors[] = 'License image is required.';
    elseif (!in_array($_FILES['license_image']['type'], $allowed_types))
        $errors[] = 'License image must be a JPG, PNG, or WEBP file.';

    if (empty($_FILES['aadhar_image']['name']) || $_FILES['aadhar_image']['error'] === 4)
        $errors[] = 'Aadhaar image is required.';
    elseif (!in_array($_FILES['aadhar_image']['type'], $allowed_types))
        $errors[] = 'Aadhaar image must be a JPG, PNG, or WEBP file.';

    // Duplicate checks
    if (empty($errors)) {
        $safe_email  = mysqli_real_escape_string($con, $driver_email);
        $safe_mobile = mysqli_real_escape_string($con, $driver_mobile);
        $safe_aadhar = mysqli_real_escape_string($con, $aadhar_clean);
        $safe_lic    = mysqli_real_escape_string($con, strtoupper($license_number));

        $chk = mysqli_query($con, "SELECT driver_id FROM driver_master WHERE driver_email='$safe_email'");
        if (mysqli_num_rows($chk) > 0)
            $errors[] = 'This email address is already registered. Please use a different email or sign in.';

        $chk = mysqli_query($con, "SELECT driver_id FROM driver_master WHERE driver_mobile='$safe_mobile'");
        if (mysqli_num_rows($chk) > 0)
            $errors[] = 'This mobile number is already registered. Please use a different number.';

        $chk = mysqli_query($con, "SELECT driver_id FROM driver_master WHERE aadhar_number='$safe_aadhar'");
        if (mysqli_num_rows($chk) > 0)
            $errors[] = 'This Aadhaar number is already registered with another account.';

        $chk = mysqli_query($con, "SELECT driver_id FROM driver_master WHERE license_number='$safe_lic'");
        if (mysqli_num_rows($chk) > 0)
            $errors[] = 'This license number is already registered with another account.';
    }

    if (!empty($errors)) {
        $list = '<ul style="margin:6px 0 0;padding-left:18px;">';
        foreach ($errors as $e) $list .= '<li style="margin-bottom:4px;">' . htmlspecialchars($e) . '</li>';
        $list .= '</ul>';
        $modalTitle   = 'Please Fix These Issues';
        $modalMessage = 'Your application could not be submitted:' . $list;
        $modalType    = 'error';
    } else {
        $driver_image = basename($_FILES['profile_image']['name']);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], "images/driver_profile/" . $driver_image);
        $license_img = basename($_FILES['license_image']['name']);
        move_uploaded_file($_FILES['license_image']['tmp_name'], "images/driver_licence/" . $license_img);
        $aadhar_img = basename($_FILES['aadhar_image']['name']);
        move_uploaded_file($_FILES['aadhar_image']['tmp_name'], "images/driver_aadhar/" . $aadhar_img);

        $safe_name    = mysqli_real_escape_string($con, $driver_name);
        $safe_pass    = mysqli_real_escape_string($con, $password);
        $safe_dob     = mysqli_real_escape_string($con, $dob);
        $safe_doj     = mysqli_real_escape_string($con, $doj);
        $safe_exp     = (int)$experience_years;
        $safe_lic_exp = mysqli_real_escape_string($con, $license_expiry_date);

        $q = mysqli_query($con, "INSERT INTO driver_master VALUES('',
            '$safe_name','$safe_email','$safe_pass','$safe_mobile',
            '$safe_dob','$safe_doj','$driver_image',
            '$safe_lic','$license_img','$safe_lic_exp',
            '$safe_exp','$safe_aadhar','$aadhar_img', 0)");

        if ($q) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'your-email@gmail.com';
                $mail->Password   = 'YOUR_APP_PASSWORD';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('your-email@gmail.com', 'Driver Portal');
                $mail->addAddress($driver_email, $driver_name);
                $mail->isHTML(true);
                $mail->Subject = 'Driver Registration Submitted';
                $mail->Body    = "<p>Hello <b>$driver_name</b>,</p><p>Your driver registration has been received.</p><p>Status: <b>Pending Approval</b></p><br><p>Thank you,<br>Driver Portal</p>";
                $mail->send();
            } catch (Exception $e) { /* non-critical */ }

            $modalTitle   = 'Application Submitted!';
            $modalMessage = 'Your application has been received and is <strong>pending approval</strong>. We will notify you at <strong>' . htmlspecialchars($driver_email) . '</strong> once reviewed.';
            $modalType    = 'success';
        } else {
            $modalTitle   = 'Database Error';
            $modalMessage = 'Something went wrong while saving your application. Please try again shortly.';
            $modalType    = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Register</title>
<style>
.pw-rule{display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;margin-bottom:5px;transition:color .2s}
.pw-rule:last-child{margin-bottom:0}
.pw-icon{width:16px;height:16px;min-width:16px;border-radius:50%;border:1.5px solid #cbd5e1;display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;background:#fff;transition:all .2s}
.pw-rule[data-ok="1"]{color:#16a34a}
.pw-rule[data-ok="1"] .pw-icon{background:#16a34a;border-color:#16a34a;color:#fff}
.pw-rule[data-ok="1"] .pw-icon::after{content:"✓"}
.pw-rule[data-ok="0"] .pw-icon::after{content:"✕";color:#94a3b8}
:root{--primary:#2563eb;--text:#0f172a;--muted:#64748b;--bg:#f8fafc;--card:#fff;--error:#ef4444;--error-bg:#fef2f2}
*{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont}
body{margin:0;min-height:100vh;display:flex;justify-content:center;align-items:center;background:var(--bg);color:var(--text)}
.auth{width:1100px;background:var(--card);border-radius:20px;box-shadow:0 40px 80px rgba(15,23,42,.15);display:grid;grid-template-columns:420px 1fr;overflow:hidden}
.left{background:#1089FF;color:#fff;padding:50px;display:flex;flex-direction:column;justify-content:center}
.left h1{font-size:32px;margin:0 0 10px}
.left p{color:#cbd5f5;line-height:1.6}
.right{padding:45px}
.tabs{display:flex;gap:30px;margin-bottom:30px}
.tabs label{font-weight:600;cursor:pointer;color:var(--muted);position:relative;padding-bottom:8px}
.tabs label::after{content:"";position:absolute;left:0;bottom:0;width:0;height:3px;background:var(--primary);transition:.3s}
input[type="radio"]{display:none}
#login:checked~.tabs label[for="login"],#register:checked~.tabs label[for="register"]{color:var(--text)}
#login:checked~.tabs label[for="login"]::after,#register:checked~.tabs label[for="register"]::after{width:100%}
.panel{display:none}
#login:checked~.login,#register:checked~.register{display:block}
form{width:100%}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.form-group{display:flex;flex-direction:column;margin-bottom:18px;position:relative}
.form-row .form-group{margin-bottom:0}
label{font-size:13px;font-weight:600;margin-bottom:6px;color:var(--text);display:flex;align-items:center;gap:4px}
.req{color:var(--error);font-size:14px;line-height:1}
input:not([type="radio"]):not([type="submit"]){padding:12px 14px;border-radius:10px;border:1px solid #e5e7eb;font-size:14px;transition:border-color .2s,box-shadow .2s}
input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
input[type="file"]{padding:9px 14px;font-size:13px;cursor:pointer}
input.is-error{border-color:var(--error)!important;background:var(--error-bg)}
input.is-error:focus{box-shadow:0 0 0 3px rgba(239,68,68,.12)!important}
.field-error{display:none;color:var(--error);font-size:12px;margin-top:5px;font-weight:500;align-items:center;gap:4px}
.field-error.show{display:flex}
.field-error::before{content:"";display:inline-block;width:14px;height:14px;min-width:14px;background:var(--error);border-radius:50%;color:#fff;font-size:10px;font-weight:700;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z'/%3E%3C/svg%3E");background-size:cover}
button[type="submit"]{width:100%;margin-top:15px;padding:14px;border:none;border-radius:12px;background:var(--primary);color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s}
button[type="submit"]:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
button[type="submit"]:active{transform:translateY(0)}
@media(max-width:1000px){.auth{grid-template-columns:1fr;width:95%}.left{display:none}.form-row{grid-template-columns:1fr}}
/* MODAL */
.cb-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity 0.22s ease}
.cb-modal-overlay.open{opacity:1;pointer-events:all}
.cb-modal{background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 24px 60px rgba(15,23,42,.18);width:100%;max-width:460px;overflow:hidden;transform:translateY(14px) scale(0.97);transition:transform 0.24s cubic-bezier(.4,0,.2,1);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont}
.cb-modal-overlay.open .cb-modal{transform:translateY(0) scale(1)}
.cb-modal-accent{height:4px;width:100%}
.cb-modal-accent.success{background:#2563eb}
.cb-modal-accent.error{background:#ef4444}
.cb-modal-accent.warn{background:#f59e0b}
.cb-modal-header{padding:22px 24px 16px;display:flex;align-items:flex-start;gap:14px;border-bottom:1px solid #f1f5f9}
.cb-modal-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;font-weight:700;flex-shrink:0}
.cb-modal-icon.success{background:#eff6ff;color:#2563eb}
.cb-modal-icon.error{background:#fef2f2;color:#ef4444}
.cb-modal-icon.warn{background:#fffbeb;color:#f59e0b}
.cb-modal-title{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 3px;letter-spacing:-.01em}
.cb-modal-subtitle{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase}
.cb-modal-subtitle.success{color:#2563eb}
.cb-modal-subtitle.error{color:#ef4444}
.cb-modal-subtitle.warn{color:#f59e0b}
.cb-modal-body{padding:18px 24px;font-size:14px;color:#64748b;line-height:1.6;max-height:55vh;overflow-y:auto}
.cb-modal-body strong{color:#0f172a;font-weight:600}
.cb-modal-footer{padding:0 24px 22px;display:flex;gap:10px}
.cb-modal-btn{flex:1;padding:11px 16px;border:none;border-radius:10px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:all .16s}
.cb-modal-btn.primary{background:#2563eb;color:#fff;box-shadow:0 2px 10px rgba(37,99,235,.25)}
.cb-modal-btn.primary:hover{background:#1d4ed8;transform:translateY(-1px)}
.cb-modal-btn.secondary{background:#f8fafc;color:#64748b;border:1px solid #e5e7eb}
.cb-modal-btn.secondary:hover{background:#f1f5f9;color:#0f172a}
.cb-modal-btn.warn-btn{background:#f59e0b;color:#fff;box-shadow:0 2px 10px rgba(245,158,11,.25)}
.cb-modal-btn.warn-btn:hover{background:#d97706;transform:translateY(-1px)}
</style>
</head>
<body>

<div class="cb-modal-overlay" id="cbModalOverlay" role="dialog" aria-modal="true">
  <div class="cb-modal">
    <div class="cb-modal-accent" id="cbModalAccent"></div>
    <div class="cb-modal-header">
      <div class="cb-modal-icon" id="cbModalIcon"></div>
      <div>
        <div class="cb-modal-subtitle" id="cbModalSubtitle"></div>
        <div class="cb-modal-title"    id="cbModalTitle"></div>
      </div>
    </div>
    <div class="cb-modal-body"   id="cbModalBody"></div>
    <div class="cb-modal-footer" id="cbModalFooter"></div>
  </div>
</div>

<div class="auth">
  <div class="left">
    <h1>Drive with us</h1>
    <p>Join our car-rental platform as a verified driver. Earn more, drive safely, and grow with us.</p>
  </div>
  <div class="right">
    <input type="radio" name="tab" id="login"    <?= $activeTab==='login'    ? 'checked':'' ?>>
    <input type="radio" name="tab" id="register" <?= $activeTab==='register' ? 'checked':'' ?>>
    <div class="tabs">
      <label for="login">Login</label>
      <label for="register">Become a Driver</label>
    </div>

    <!-- LOGIN -->
    <div class="panel login">
      <form method="post" id="loginForm" novalidate>
        <div class="form-group">
          <label for="login-email">Email <span class="req">*</span></label>
          <input type="email" id="login-email" name="driver_email" placeholder="Enter email">
          <span class="field-error" id="err-login-email">Please enter a valid email address.</span>
        </div>
        <div class="form-group">
          <label for="login-password">Password <span class="req">*</span></label>
          <input type="password" id="login-password" name="password" placeholder="Enter password">
          <span class="field-error" id="err-login-password">Password is required.</span>
        </div>
        <button type="submit" name="btnlogin">Login</button>
      </form>
    </div>

    <!-- REGISTER -->
    <div class="panel register">
      <form method="post" enctype="multipart/form-data" id="registerForm" novalidate>
        <div class="form-row">
          <div class="form-group">
            <label for="driver-name">Full Name <span class="req">*</span></label>
            <input type="text" id="driver-name" name="driver_name" placeholder="Enter Your Full Name">
            <span class="field-error" id="err-driver-name">Full name is required.</span>
          </div>
          <div class="form-group">
            <label for="driver-email">Email <span class="req">*</span></label>
            <input type="email" id="driver-email" name="driver_email" placeholder="your@example.com">
            <span class="field-error" id="err-driver-email">Enter a valid email address.</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group" style="position:relative">
            <label for="reg-password">Password <span class="req">*</span></label>
            <div style="position:relative">
              <input type="password" id="reg-password" name="password" placeholder="Min. 8 characters" style="padding-right:42px;width:100%">
              <button type="button" id="togglePass" tabindex="-1" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#94a3b8;width:auto;margin:0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div id="pwChecklist" style="display:none;margin-top:8px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px">
              <div style="font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#64748b;margin-bottom:8px">Password must include:</div>
              <div class="pw-rule" id="rule-len"    data-ok="0"><span class="pw-icon"></span> At least 8 characters</div>
              <div class="pw-rule" id="rule-upper"  data-ok="0"><span class="pw-icon"></span> Uppercase letter (A–Z)</div>
              <div class="pw-rule" id="rule-lower"  data-ok="0"><span class="pw-icon"></span> Lowercase letter (a–z)</div>
              <div class="pw-rule" id="rule-num"    data-ok="0"><span class="pw-icon"></span> Number (0–9)</div>
              <div class="pw-rule" id="rule-special"data-ok="0"><span class="pw-icon"></span> Special character (!@#$%^&amp;*)</div>
            </div>
            <span class="field-error" id="err-reg-password">Password must meet all requirements above.</span>
          </div>
          <div class="form-group" style="position:relative">
            <label for="confirm-password">Confirm Password <span class="req">*</span></label>
            <div style="position:relative">
              <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat password" style="padding-right:42px;width:100%">
              <button type="button" id="toggleConfirm" tabindex="-1" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#94a3b8;width:auto;margin:0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <span class="field-error" id="err-confirm-password">Passwords do not match.</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="mobile">Mobile <span class="req">*</span></label>
            <input type="tel" id="mobile" name="driver_mobile" placeholder="10-digit number" maxlength="10">
            <span class="field-error" id="err-mobile">Valid 10-digit Indian number (starts 6–9).</span>
          </div>
          <div class="form-group">
            <label for="profile-image">Profile Image <span class="req">*</span></label>
            <input type="file" id="profile-image" name="profile_image" accept="image/*">
            <span class="field-error" id="err-profile-image">Profile image required (JPG/PNG/WEBP).</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="dob">Date of Birth <span class="req">*</span></label>
            <input type="date" id="dob" name="dob">
            <span class="field-error" id="err-dob">Must be at least 18 years old.</span>
          </div>
          <div class="form-group">
            <label for="doj">Date of Joining <span class="req">*</span></label>
            <input type="date" id="doj" name="doj">
            <span class="field-error" id="err-doj">Must be a past date (before today).</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="experience">Experience (Years) <span class="req">*</span></label>
            <input type="number" id="experience" name="experience_years" placeholder="0" min="0" max="50">
            <span class="field-error" id="err-experience">Enter a value between 0 and 50.</span>
          </div>
          <div class="form-group">
            <label for="license-expiry">License Expiry Date <span class="req">*</span></label>
            <input type="date" id="license-expiry" name="license_expiry_date">
            <span class="field-error" id="err-license-expiry">Must be a future date.</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="license-number">License Number <span class="req">*</span></label>
            <input type="text" id="license-number" name="license_number" placeholder="MH0120230001234" maxlength="15" style="text-transform:uppercase">
            <span class="field-error" id="err-license-number">2 letters + 13 digits (e.g. MH0120230001234).</span>
          </div>
          <div class="form-group">
            <label for="license-image">License Image <span class="req">*</span></label>
            <input type="file" id="license-image" name="license_image" accept="image/*">
            <span class="field-error" id="err-license-image">License image required (JPG/PNG/WEBP).</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="aadhar-number">Aadhaar Number <span class="req">*</span></label>
            <input type="text" id="aadhar-number" name="aadhar_number" placeholder="XXXX XXXX XXXX" maxlength="14">
            <span class="field-error" id="err-aadhar-number">Aadhaar must be exactly 12 digits.</span>
          </div>
          <div class="form-group">
            <label for="aadhar-image">Aadhaar Image <span class="req">*</span></label>
            <input type="file" id="aadhar-image" name="aadhar_image" accept="image/*">
            <span class="field-error" id="err-aadhar-image">Aadhaar image required (JPG/PNG/WEBP).</span>
          </div>
        </div>
        <button type="submit" name="btnapply">Apply as Driver</button>
      </form>
    </div>
  </div>
</div>

<script>
/* DATE BOUNDS */
(function(){
  function toYMD(d){return d.toISOString().split('T')[0];}
  const t=new Date();
  const maxDob=new Date(t); maxDob.setFullYear(maxDob.getFullYear()-18);
  const minDob=new Date(t); minDob.setFullYear(minDob.getFullYear()-80);
  const dob=document.getElementById('dob');
  if(dob){dob.max=toYMD(maxDob);dob.min=toYMD(minDob);}
  const maxDoj=new Date(t); maxDoj.setDate(maxDoj.getDate()-1);
  const minDoj=new Date(t); minDoj.setFullYear(minDoj.getFullYear()-50);
  const doj=document.getElementById('doj');
  if(doj){doj.max=toYMD(maxDoj);doj.min=toYMD(minDoj);}
  const minLic=new Date(t); minLic.setDate(minLic.getDate()+1);
  const lic=document.getElementById('license-expiry');
  if(lic)lic.min=toYMD(minLic);
})();

/* MODAL */
const MODAL_CFG={success:{icon:'✓',subtitle:'Success'},error:{icon:'✕',subtitle:'Error'},warn:{icon:'!',subtitle:'Notice'}};
function showModal(type,title,bodyHtml,buttons){
  const cfg=MODAL_CFG[type]||MODAL_CFG.error;
  const overlay=document.getElementById('cbModalOverlay');
  document.getElementById('cbModalAccent').className='cb-modal-accent '+type;
  document.getElementById('cbModalIcon').className='cb-modal-icon '+type;
  document.getElementById('cbModalIcon').textContent=cfg.icon;
  document.getElementById('cbModalSubtitle').className='cb-modal-subtitle '+type;
  document.getElementById('cbModalSubtitle').textContent=cfg.subtitle;
  document.getElementById('cbModalTitle').textContent=title;
  document.getElementById('cbModalBody').innerHTML=bodyHtml;
  const footer=document.getElementById('cbModalFooter');
  footer.innerHTML='';
  if(Array.isArray(buttons)){
    buttons.forEach(b=>{
      const btn=document.createElement('button');
      btn.className='cb-modal-btn '+(b.cls||'secondary');
      btn.textContent=b.label;
      btn.onclick=b.action;
      footer.appendChild(btn);
    });
  } else {
    const redirectUrl=buttons;
    if(redirectUrl){
      const btn=document.createElement('button');
      btn.className='cb-modal-btn primary';
      btn.textContent=type==='success'?'Continue':'OK';
      btn.onclick=()=>window.location.href=redirectUrl;
      footer.appendChild(btn);
    } else {
      if(type!=='success'){
        const sec=document.createElement('button');
        sec.className='cb-modal-btn secondary';
        sec.textContent='Dismiss';
        sec.onclick=closeModal;
        footer.appendChild(sec);
      }
      const btn=document.createElement('button');
      btn.className='cb-modal-btn primary';
      btn.textContent='OK, Got It';
      btn.onclick=closeModal;
      footer.appendChild(btn);
    }
  }
  overlay.classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(){
  document.getElementById('cbModalOverlay').classList.remove('open');
  document.body.style.overflow='';
}
document.getElementById('cbModalOverlay').addEventListener('click',function(e){if(e.target===this)closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

/* PHP modal triggers */
<?php if ($showRequestModal): ?>
window.addEventListener('DOMContentLoaded',function(){
  <?php
    if ($blockedReason==='pending'){
      $title='Application Still Pending';
      $body ='Your driver application is currently <strong>under review</strong>. You will be notified at <strong>'.htmlspecialchars($blockedEmail).'</strong> once a decision is made.<br><br>If you have additional information to share or wish to follow up, click <strong>Submit Request</strong>.';
    } elseif ($blockedReason==='rejected'){
      $title='Account Rejected';
      $body ='Your driver account has been <strong>rejected</strong>. This may be due to incomplete documents or failed verification.<br><br>If you believe this is a mistake or wish to appeal, click <strong>Submit Request</strong> to contact our support team.';
    } else {
      $title='Account Disabled';
      $body ='Your driver account has been <strong>disabled</strong>. You are unable to log in at this time.<br><br>If you believe this is an error, click <strong>Submit Request</strong> to contact our support team.';
    }
  ?>
  showModal('warn','<?= addslashes($title) ?>','<?= addslashes($body) ?>',[
    {label:'OK',cls:'secondary',action:closeModal},
    {label:'Submit Request →',cls:'warn-btn',action:function(){window.location.href='driver_request.php';}}
  ]);
});
<?php elseif ($modalType): ?>
window.addEventListener('DOMContentLoaded',function(){
  showModal('<?= $modalType ?>','<?= addslashes($modalTitle) ?>','<?= addslashes($modalMessage) ?>',<?= $modalRedirect?"'".addslashes($modalRedirect)."'":'null' ?>);
});
<?php endif; ?>

/* VALIDATION */
function setError(id,eid,show){
  const i=document.getElementById(id),e=document.getElementById(eid);
  if(!i||!e)return show;
  i.classList.toggle('is-error',show);e.classList.toggle('show',show);return show;
}
function toYMD(d){return d.toISOString().split('T')[0];}
function validateEmail(v){return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());}
function validateMobile(v){return /^[6-9]\d{9}$/.test(v.replace(/\s/g,''));}
function validateLicense(v){return /^[A-Za-z]{2}\d{13}$/.test(v.replace(/\s/g,''));}
function validateAadhar(v){return /^\d{12}$/.test(v.replace(/\s/g,''));}
function isImageFile(fi){return fi.files&&fi.files[0]&&/^image\/(jpeg|jpg|png|webp)$/i.test(fi.files[0].type);}
function validateDob(val){if(!val)return false;const m=new Date();m.setFullYear(m.getFullYear()-18);return val<=toYMD(m);}
function validateDoj(val){if(!val)return false;const y=new Date();y.setDate(y.getDate()-1);return val<=toYMD(y);}
function validateLicExp(val){return val&&val>toYMD(new Date());}

document.getElementById('aadhar-number')?.addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'').substring(0,12);
  this.value=v.replace(/(\d{4})(\d{0,4})(\d{0,4})/,(_,a,b,c)=>[a,b,c].filter(Boolean).join(' '));
});
document.getElementById('license-number')?.addEventListener('input',function(){
  const p=this.selectionStart;this.value=this.value.toUpperCase();this.setSelectionRange(p,p);
});

/* Login */
const loginForm=document.getElementById('loginForm');
if(loginForm){
  loginForm.addEventListener('submit',function(e){
    let h=false;
    if(setError('login-email','err-login-email',!validateEmail(document.getElementById('login-email').value)))h=true;
    if(setError('login-password','err-login-password',!document.getElementById('login-password').value.trim()))h=true;
    if(h){e.preventDefault();showModal('error','Missing Information','Please fill in all required fields correctly before signing in.',null);}
  });
  ['login-email','login-password'].forEach(id=>{
    document.getElementById(id)?.addEventListener('input',function(){
      this.classList.remove('is-error');document.getElementById('err-'+id)?.classList.remove('show');
    });
  });
}

/* Password checklist */
const pwInput=document.getElementById('reg-password'),pwChecklist=document.getElementById('pwChecklist');
const PW_RULES={'rule-len':v=>v.length>=8,'rule-upper':v=>/[A-Z]/.test(v),'rule-lower':v=>/[a-z]/.test(v),'rule-num':v=>/[0-9]/.test(v),'rule-special':v=>/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v)};
function checkPwStrength(val){let ok=true;for(const[id,fn]of Object.entries(PW_RULES)){const r=fn(val);document.getElementById(id)?.setAttribute('data-ok',r?'1':'0');if(!r)ok=false;}return ok;}
function isStrongPassword(v){return Object.values(PW_RULES).every(fn=>fn(v));}
if(pwInput){
  pwInput.addEventListener('focus',()=>{pwChecklist.style.display='block';});
  pwInput.addEventListener('blur',()=>{if(!pwInput.value)pwChecklist.style.display='none';});
  pwInput.addEventListener('input',()=>{checkPwStrength(pwInput.value);pwChecklist.style.display='block';});
}
document.getElementById('togglePass')?.addEventListener('click',function(){const i=document.getElementById('reg-password');i.type=i.type==='password'?'text':'password';this.style.color=i.type==='text'?'var(--primary)':'#94a3b8';});
document.getElementById('toggleConfirm')?.addEventListener('click',function(){const i=document.getElementById('confirm-password');i.type=i.type==='password'?'text':'password';this.style.color=i.type==='text'?'var(--primary)':'#94a3b8';});

/* Register */
const regForm=document.getElementById('registerForm');
if(regForm){
  regForm.addEventListener('submit',function(e){
    let h=false;
    const pass=document.getElementById('reg-password').value;
    const cp=document.getElementById('confirm-password').value;
    const exp=document.getElementById('experience').value;
    if(setError('driver-name','err-driver-name',!document.getElementById('driver-name').value.trim()))h=true;
    if(setError('driver-email','err-driver-email',!validateEmail(document.getElementById('driver-email').value)))h=true;
    if(!isStrongPassword(pass)){setError('reg-password','err-reg-password',true);pwChecklist.style.display='block';checkPwStrength(pass);h=true;}
    if(setError('confirm-password','err-confirm-password',!cp||cp!==pass))h=true;
    if(setError('mobile','err-mobile',!validateMobile(document.getElementById('mobile').value)))h=true;
    if(setError('dob','err-dob',!validateDob(document.getElementById('dob').value)))h=true;
    if(setError('doj','err-doj',!validateDoj(document.getElementById('doj').value)))h=true;
    if(setError('experience','err-experience',exp===''||isNaN(exp)||Number(exp)<0||Number(exp)>50))h=true;
    if(setError('license-number','err-license-number',!validateLicense(document.getElementById('license-number').value)))h=true;
    if(setError('license-expiry','err-license-expiry',!validateLicExp(document.getElementById('license-expiry').value)))h=true;
    if(setError('aadhar-number','err-aadhar-number',!validateAadhar(document.getElementById('aadhar-number').value)))h=true;
    if(setError('profile-image','err-profile-image',!isImageFile(document.getElementById('profile-image'))))h=true;
    if(setError('license-image','err-license-image',!isImageFile(document.getElementById('license-image'))))h=true;
    if(setError('aadhar-image','err-aadhar-image',!isImageFile(document.getElementById('aadhar-image'))))h=true;
    if(h){
      e.preventDefault();
      showModal('error','Please Fix These Issues','Some fields are missing or incorrect. Fields marked <strong style="color:#ef4444">*</strong> are required. Please review highlighted fields.',null);
      regForm.querySelector('.is-error')?.scrollIntoView({behavior:'smooth',block:'center'});
    }
  });
  document.getElementById('confirm-password')?.addEventListener('input',function(){const p=document.getElementById('reg-password').value;if(this.value)setError('confirm-password','err-confirm-password',this.value!==p);});
  document.getElementById('dob')?.addEventListener('change',function(){setError('dob','err-dob',!validateDob(this.value));});
  document.getElementById('doj')?.addEventListener('change',function(){setError('doj','err-doj',!validateDoj(this.value));});
  document.getElementById('license-expiry')?.addEventListener('change',function(){setError('license-expiry','err-license-expiry',!validateLicExp(this.value));});
  regForm.querySelectorAll('input').forEach(inp=>{
    inp.addEventListener('input',function(){if(['reg-password','dob','doj','license-expiry'].includes(this.id))return;this.classList.remove('is-error');document.getElementById('err-'+this.id)?.classList.remove('show');});
    inp.addEventListener('change',function(){if(['dob','doj','license-expiry'].includes(this.id))return;this.classList.remove('is-error');document.getElementById('err-'+this.id)?.classList.remove('show');});
  });
}
/* Name field — letters and spaces only */
document.getElementById('driver-name')?.addEventListener('keypress', function(e) {
  if (!/[a-zA-Z\s]/.test(e.key)) e.preventDefault();
});
document.getElementById('driver-name')?.addEventListener('input', function() {
  this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
});
</script>
</body>
</html>