<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

include('connect.php');
session_name('user_session');
session_start();

$modalTitle    = '';
$modalMessage  = '';
$modalType     = '';

// Pre-fill from session if user came from login page
$pre_name  = isset($_SESSION['req_user_name'])  ? htmlspecialchars($_SESSION['req_user_name'])  : '';
$pre_email = isset($_SESSION['req_user_email']) ? htmlspecialchars($_SESSION['req_user_email']) : '';
$pre_id    = isset($_SESSION['req_user_id'])    ? (int)$_SESSION['req_user_id']                 : 0;

// ── FORM SUBMISSION ────────────────────────────────────────────────────────
if (isset($_POST['btnrequest'])) {
    $errors = [];

    $sender_name   = trim($_POST['sender_name']   ?? '');
    $sender_email  = trim($_POST['sender_email']  ?? '');
    $sender_mobile = trim($_POST['sender_mobile'] ?? '');
    $subject       = trim($_POST['subject']       ?? '');
    $message       = trim($_POST['message']       ?? '');
    $sender_id     = (int)($_POST['sender_id']    ?? 0);

    // Validations
    if (empty($sender_name))
        $errors[] = 'Your name is required.';

    if (empty($sender_email))
        $errors[] = 'Email address is required.';
    elseif (!filter_var($sender_email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Please enter a valid email address.';

    if (empty($sender_mobile))
        $errors[] = 'Mobile number is required.';

    if (empty($subject))
        $errors[] = 'Subject is required.';

    if (empty($message))
        $errors[] = 'Please write your message.';
    elseif (strlen($message) < 20)
        $errors[] = 'Message must be at least 20 characters.';

    // Optional file attachment
    $attachment_path = null;
    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] !== 4) {
        $allowed_ext  = ['jpg','jpeg','png','webp','pdf','doc','docx'];
        $file_ext     = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $max_size     = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_ext))
            $errors[] = 'Attachment must be JPG, PNG, WEBP, PDF, DOC, or DOCX.';
        elseif ($_FILES['attachment']['size'] > $max_size)
            $errors[] = 'Attachment must be smaller than 5 MB.';
        else {
            $new_name        = 'contact_' . time() . '_' . basename($_FILES['attachment']['name']);
            $attachment_path = 'images/contact_attachments/' . $new_name;
            move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment_path);
        }
    }

    if (!empty($errors)) {
        $list = '<ul style="margin:6px 0 0;padding-left:18px;">';
        foreach ($errors as $e) $list .= '<li style="margin-bottom:4px;">' . htmlspecialchars($e) . '</li>';
        $list .= '</ul>';
        $modalTitle   = 'Please Fix These Issues';
        $modalMessage = 'Your request could not be submitted:' . $list;
        $modalType    = 'error';
    } else {
        $safe_name   = mysqli_real_escape_string($con, $sender_name);
        $safe_email  = mysqli_real_escape_string($con, $sender_email);
        $safe_mobile = mysqli_real_escape_string($con, $sender_mobile);
        $safe_subj   = mysqli_real_escape_string($con, $subject);
        $safe_msg    = mysqli_real_escape_string($con, $message);
        $att_val     = $attachment_path ? "'" . mysqli_real_escape_string($con, $attachment_path) . "'" : 'NULL';
        $sid         = $sender_id > 0 ? $sender_id : 'NULL';

        $q = mysqli_query($con,
            "INSERT INTO contact_master
(sender_type, sender_id, sender_name, sender_email, sender_mobile,
 subject, message, attachment, status)
VALUES
('user', $sid, '$safe_name', '$safe_email', '$safe_mobile',
 '$safe_subj', '$safe_msg', $att_val, 0)"
        );

        if ($q) {
            // Send confirmation email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'carbook443@gmail.com';
                $mail->Password   = 'eqwl mvwx jxwf bfih';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('carbook443@gmail.com', 'CarBook');
                $mail->addAddress($sender_email, $sender_name);
                $mail->isHTML(true);
                $mail->Subject = 'Your Request Has Been Received — CarBook';
                $mail->Body    = "
                <div style='font-family:sans-serif;max-width:560px;margin:auto;padding:30px;background:#f8fafc;border-radius:12px;'>
                  <h2 style='color:#1a4b8c;'>Request Received</h2>
                  <p>Hello <strong>$sender_name</strong>,</p>
                  <p>We have received your request and our team will review it shortly.</p>
                  <div style='background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:20px 0;'>
                    <strong>Subject:</strong> " . htmlspecialchars($subject) . "
                  </div>
                  <p>You will be notified at <strong>$sender_email</strong> once our team responds.</p>
                  <p style='color:#64748b;font-size:13px;margin-top:30px;'>Thank you,<br>CarBook Team</p>
                </div>";
                $mail->send();
            } catch (Exception $e) { /* non-critical */ }

            // Clear pre-fill session vars
            unset($_SESSION['req_user_name'], $_SESSION['req_user_email'], $_SESSION['req_user_id']);

            $modalTitle   = 'Request Submitted!';
            $modalMessage = 'Your request has been received. Our team will review it and respond to <strong>' . htmlspecialchars($sender_email) . '</strong> shortly.';
            $modalType    = 'success';
        } else {
            $modalTitle   = 'Database Error';
            $modalMessage = 'Something went wrong while submitting your request. Please try again.';
            $modalType    = 'error';
        }
    }
}

$user_mobile = '';

if ($pre_id > 0) {
    $res = mysqli_query($con, "SELECT mobno FROM users_master WHERE ui = $pre_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $user_mobile = $row['mobno'];
        if (empty($user_mobile)) {
            $modalType    = 'error';
            $modalTitle   = 'Mobile Missing';
            $modalMessage = 'Your mobile number is not found in the database. Please contact admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Request — CarBook</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
/* ── Reset & Base ──────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --navy:       #1a2e4a;
  --navy-deep:  #0f1e30;
  --navy-mid:   #1e3a5f;
  --accent:     #2e7df7;
  --accent-2:   #00c9a7;
  --white:      #ffffff;
  --off-white:  #f4f7fb;
  --border:     #dde5f0;
  --text:       #1a2e4a;
  --muted:      #64748b;
  --error:      #ef4444;
  --success:    #10b981;
  --warn:       #f59e0b;
}

html { font-size: 16px; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--off-white);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* ── Top navbar ────────────────────────────────────────── */
.navbar {
  background: var(--navy-deep);
  padding: 0 40px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 20px rgba(0,0,0,.3);
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
}

.logo-icon {
  width: 36px; height: 36px;
  background: var(--accent);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}

.logo-icon svg { width: 18px; height: 18px; fill: #fff; }

.logo-text {
  font-family: 'Syne', sans-serif;
  font-size: 20px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.02em;
}
.logo-text span { color: var(--accent-2); }

.nav-back {
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(255,255,255,.6);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: color .2s;
}
.nav-back:hover { color: #fff; }
.nav-back svg { width: 16px; height: 16px; }

/* ── Page layout ───────────────────────────────────────── */
.page {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 20px 64px;
}

/* ── Hero badge ────────────────────────────────────────── */
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(46,125,247,.1);
  border: 1px solid rgba(46,125,247,.25);
  border-radius: 999px;
  padding: 6px 16px;
  font-size: 12px;
  font-weight: 600;
  color: var(--accent);
  letter-spacing: .06em;
  text-transform: uppercase;
  margin-bottom: 16px;
}
.hero-badge span { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); display: inline-block; }

.page-title {
  font-family: 'Syne', sans-serif;
  font-size: 32px;
  font-weight: 800;
  color: var(--navy);
  text-align: center;
  margin-bottom: 8px;
  letter-spacing: -.02em;
}

.page-sub {
  font-size: 15px;
  color: var(--muted);
  text-align: center;
  max-width: 480px;
  line-height: 1.6;
  margin-bottom: 40px;
}

/* ── Card ──────────────────────────────────────────────── */
.card {
  width: 100%;
  max-width: 640px;
  background: var(--white);
  border-radius: 20px;
  border: 1px solid var(--border);
  box-shadow: 0 8px 40px rgba(26,46,74,.08), 0 2px 8px rgba(26,46,74,.04);
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy-deep) 100%);
  padding: 28px 36px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.card-header-icon {
  width: 48px; height: 48px;
  background: rgba(255,255,255,.12);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.card-header-icon svg { width: 24px; height: 24px; stroke: #fff; fill: none; }

.card-header h2 {
  font-family: 'Syne', sans-serif;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 3px;
}
.card-header p { font-size: 13px; color: rgba(255,255,255,.6); }

.card-body { padding: 36px; }

/* ── Form elements ─────────────────────────────────────── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
}
.form-row .form-group { margin-bottom: 0; }

.form-label {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: var(--navy);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.req { color: var(--error); }

.form-input,
.form-textarea,
.form-select {
  padding: 12px 16px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--text);
  background: #fafcff;
  transition: border-color .2s, box-shadow .2s, background .2s;
  outline: none;
  width: 100%;
}
.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: var(--accent);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(46,125,247,.1);
}
.form-input.is-error,
.form-textarea.is-error,
.form-select.is-error {
  border-color: var(--error);
  background: #fff5f5;
}
.form-input.is-error:focus,
.form-textarea.is-error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,.1); }

.form-input[readonly] {
  background: #f1f5f9;
  color: var(--muted);
  cursor: not-allowed;
  border-color: var(--border);
}

.form-textarea {
  resize: vertical;
  min-height: 130px;
  line-height: 1.6;
}

/* File upload */
.file-upload-wrap {
  position: relative;
}
.file-upload-wrap input[type="file"] {
  width: 100%;
  padding: 10px 16px;
  border: 1.5px dashed var(--border);
  border-radius: 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--muted);
  background: #fafcff;
  cursor: pointer;
  transition: border-color .2s;
}
.file-upload-wrap input[type="file"]:hover { border-color: var(--accent); }
.file-hint { font-size: 11px; color: var(--muted); margin-top: 5px; }

/* Field error */
.field-error {
  display: none;
  color: var(--error);
  font-size: 11.5px;
  font-weight: 500;
  margin-top: 5px;
  align-items: center;
  gap: 4px;
}
.field-error.show { display: flex; }
.field-error::before {
  content: "⚠";
  font-size: 10px;
}

/* ── Notice box (info) ─────────────────────────────────── */
.notice {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 24px;
}
.notice svg { width: 18px; height: 18px; stroke: var(--accent); fill: none; flex-shrink: 0; margin-top: 1px; }
.notice p { font-size: 13px; color: #1e40af; line-height: 1.5; }

/* ── Divider ───────────────────────────────────────────── */
.divider {
  display: flex; align-items: center; gap: 12px;
  margin: 4px 0 20px;
}
.divider hr { flex: 1; border: none; border-top: 1px solid var(--border); }
.divider span { font-size: 11px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }

/* ── Submit button ─────────────────────────────────────── */
.btn-submit {
  width: 100%;
  padding: 15px 24px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--accent) 0%, #1a5fcc 100%);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: .02em;
  cursor: pointer;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: transform .2s, box-shadow .2s, opacity .2s;
  box-shadow: 0 4px 16px rgba(46,125,247,.35);
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(46,125,247,.45); }
.btn-submit:active { transform: translateY(0); }
.btn-submit svg { width: 18px; height: 18px; stroke: #fff; fill: none; }

/* ── Footer note ───────────────────────────────────────── */
.card-footer {
  padding: 18px 36px;
  border-top: 1px solid var(--border);
  background: var(--off-white);
  text-align: center;
  font-size: 12.5px;
  color: var(--muted);
}
.card-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
.card-footer a:hover { text-decoration: underline; }

/* ── Steps indicator ───────────────────────────────────── */
.steps {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
  margin-bottom: 36px;
}
.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}
.step-num {
  width: 32px; height: 32px;
  border-radius: 50%;
  background: var(--white);
  border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: var(--muted);
}
.step.active .step-num { background: var(--accent); border-color: var(--accent); color: #fff; }
.step.done .step-num   { background: var(--accent-2); border-color: var(--accent-2); color: #fff; }
.step-label { font-size: 11px; font-weight: 600; color: var(--muted); text-align: center; }
.step.active .step-label { color: var(--accent); }
.step-line { width: 60px; height: 2px; background: var(--border); margin-bottom: 18px; }

/* ══════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════ */
.cb-modal-overlay {
  position: fixed; inset: 0;
  background: rgba(15,23,42,0.55);
  backdrop-filter: blur(5px);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
  opacity: 0; pointer-events: none;
  transition: opacity .22s ease;
}
.cb-modal-overlay.open { opacity: 1; pointer-events: all; }
.cb-modal {
  background: #fff; border-radius: 18px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 30px 70px rgba(15,23,42,.2);
  width: 100%; max-width: 440px;
  overflow: hidden;
  transform: translateY(16px) scale(0.96);
  transition: transform .25s cubic-bezier(.4,0,.2,1);
  font-family: 'DM Sans', sans-serif;
}
.cb-modal-overlay.open .cb-modal { transform: translateY(0) scale(1); }
.cb-modal-accent { height: 4px; width: 100%; }
.cb-modal-accent.success { background: linear-gradient(90deg, var(--accent), var(--accent-2)); }
.cb-modal-accent.error   { background: var(--error); }
.cb-modal-accent.warn    { background: var(--warn); }
.cb-modal-header { padding: 24px 26px 16px; display: flex; align-items: flex-start; gap: 14px; border-bottom: 1px solid #f1f5f9; }
.cb-modal-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex-shrink: 0; }
.cb-modal-icon.success { background: #eff6ff; color: var(--accent); }
.cb-modal-icon.error   { background: #fef2f2; color: var(--error); }
.cb-modal-icon.warn    { background: #fffbeb; color: var(--warn); }
.cb-modal-title    { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 3px; }
.cb-modal-subtitle { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
.cb-modal-subtitle.success { color: var(--accent); }
.cb-modal-subtitle.error   { color: var(--error); }
.cb-modal-subtitle.warn    { color: var(--warn); }
.cb-modal-body   { padding: 18px 26px; font-size: 14px; color: #64748b; line-height: 1.65; max-height: 55vh; overflow-y: auto; }
.cb-modal-body strong { color: #0f172a; font-weight: 600; }
.cb-modal-body ul { margin: 6px 0 0; padding-left: 18px; }
.cb-modal-body li { margin-bottom: 5px; }
.cb-modal-footer { padding: 0 26px 24px; display: flex; gap: 10px; }
.cb-modal-btn { flex: 1; padding: 12px 16px; border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .16s; }
.cb-modal-btn.primary   { background: var(--accent); color: #fff; box-shadow: 0 2px 10px rgba(46,125,247,.3); }
.cb-modal-btn.primary:hover { background: #1a5fcc; transform: translateY(-1px); }
.cb-modal-btn.secondary { background: #f8fafc; color: #64748b; border: 1px solid #e5e7eb; }
.cb-modal-btn.secondary:hover { background: #f1f5f9; color: #0f172a; }

/* ── Responsive ────────────────────────────────────────── */
@media (max-width: 600px) {
  .navbar { padding: 0 20px; }
  .card-body { padding: 24px 20px; }
  .card-header { padding: 22px 20px; }
  .card-footer { padding: 16px 20px; }
  .form-row { grid-template-columns: 1fr; }
  .page-title { font-size: 24px; }
}
</style>
</head>
<body>

<!-- ── MODAL ── -->
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

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <a href="#" class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.01 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
      </svg>
    </div>
    <span class="logo-text">Car<span>Book</span></span>
  </a>
  <a href="register.php" class="nav-back">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to Login
  </a>
</nav>

<!-- ── PAGE ── -->
<main class="page">

  <div class="hero-badge">
    <span></span>
    User Support
  </div>

  <h1 class="page-title">Submit a Request</h1>
  <p class="page-sub">
    Fill in the form below and our team will review your request within 2–3 business days. You'll receive a response at your registered email.
  </p>

  <!-- Steps -->
  <div class="steps">
    <div class="step done">
      <div class="step-num">✓</div>
      <div class="step-label">Registered</div>
    </div>
    <div class="step-line"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <div class="step-label">Request</div>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-num">3</div>
      <div class="step-label">Approval</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-header-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.77 9.77 0 01-4-.832L3 20l1.09-3.27C3.4 15.368 3 13.72 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
      </div>
      <div>
        <h2>Account Appeal / Request</h2>
        <p>Account review · Re-enable request · General query</p>
      </div>
    </div>

    <div class="card-body">

      <div class="notice">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p>Your account may be <strong>blocked or deactivated</strong>. Use this form to contact our team. Provide as much detail as possible to speed up the process.</p>
      </div>

      <form method="post" enctype="multipart/form-data" id="requestForm" novalidate>
        <!-- Hidden: sender_id from session -->
        <input type="hidden" name="sender_id" value="<?= $pre_id ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sender_name">Full Name <span class="req">*</span></label>
            <input type="text" id="sender_name" name="sender_name" class="form-input"
                   placeholder="Your name"
                   value="<?= $pre_name ?>">
            <span class="field-error" id="err-name">Full name is required.</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="sender_email">Email Address <span class="req">*</span></label>
            <input type="email" id="sender_email" name="sender_email" class="form-input"
                   placeholder="you@example.com"
                   value="<?= $pre_email ?>">
            <span class="field-error" id="err-email">Enter a valid email address.</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="sender_mobile">Mobile Number <span class="req">*</span></label>
          <input type="text" id="sender_mobile" name="sender_mobile" class="form-input"
                 value="<?= $user_mobile ?>" readonly>
        </div>

        <div class="divider">
          <hr><span>Request Details</span><hr>
        </div>

        <div class="form-group">
          <label class="form-label" for="subject">Subject</label>
          <input type="text" id="subject" name="subject" class="form-input"
                 value="Account blocked / deactivated — appeal for re-activation" readonly>
        </div>

        <div class="form-group">
          <label class="form-label" for="message">Message <span class="req">*</span></label>
          <textarea id="message" name="message" class="form-textarea"
                    placeholder="Describe your situation in detail. Include any relevant information that may help us process your request faster…"></textarea>
          <span class="field-error" id="err-message">Message must be at least 20 characters.</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="attachment">Attachment <span style="color:var(--muted);font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <div class="file-upload-wrap">
            <input type="file" id="attachment" name="attachment"
                   accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
          </div>
          <div class="file-hint">Accepted: JPG, PNG, WEBP, PDF, DOC, DOCX · Max 5 MB</div>
          <span class="field-error" id="err-attachment">Invalid file type or file too large.</span>
        </div>

        <button type="submit" name="btnrequest" class="btn-submit">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
          </svg>
          Submit Request
        </button>
      </form>
    </div>

    <div class="card-footer">
      Remember your password? <a href="register.php">Back to Sign In</a>
      &nbsp;·&nbsp;
      Need help? Email us at <a href="mailto:carbook443@gmail.com">carbook443@gmail.com</a>
    </div>
  </div>

</main>

<script>
/* ═══════════════════════════════════════════
   MODAL ENGINE
═══════════════════════════════════════════ */
const MODAL_CFG = {
  success: { icon: '✓', subtitle: 'Success' },
  error:   { icon: '✕', subtitle: 'Error'   },
  warn:    { icon: '!', subtitle: 'Notice'  },
};

function showModal(type, title, bodyHtml, redirectUrl) {
  const cfg     = MODAL_CFG[type] || MODAL_CFG.error;
  const overlay = document.getElementById('cbModalOverlay');
  document.getElementById('cbModalAccent').className     = 'cb-modal-accent ' + type;
  document.getElementById('cbModalIcon').className       = 'cb-modal-icon '   + type;
  document.getElementById('cbModalIcon').textContent     = cfg.icon;
  document.getElementById('cbModalSubtitle').className   = 'cb-modal-subtitle ' + type;
  document.getElementById('cbModalSubtitle').textContent = cfg.subtitle;
  document.getElementById('cbModalTitle').textContent    = title;
  document.getElementById('cbModalBody').innerHTML       = bodyHtml;
  const footer = document.getElementById('cbModalFooter');
  footer.innerHTML = '';
  if (redirectUrl) {
    const btn = document.createElement('button');
    btn.className   = 'cb-modal-btn primary';
    btn.textContent = 'OK';
    btn.onclick     = () => window.location.href = redirectUrl;
    footer.appendChild(btn);
  } else {
    if (type !== 'success') {
      const sec = document.createElement('button');
      sec.className   = 'cb-modal-btn secondary';
      sec.textContent = 'Dismiss';
      sec.onclick     = closeModal;
      footer.appendChild(sec);
    }
    const btn = document.createElement('button');
    btn.className   = 'cb-modal-btn primary';
    btn.textContent = 'OK, Got It';
    btn.onclick     = closeModal;
    footer.appendChild(btn);
  }
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('cbModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('cbModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

<?php if ($modalType): ?>
window.addEventListener('DOMContentLoaded', function() {
  showModal(
    '<?= $modalType ?>',
    '<?= addslashes($modalTitle) ?>',
    '<?= addslashes($modalMessage) ?>',
    null
  );
});
<?php endif; ?>

/* ═══════════════════════════════════════════
   FORM VALIDATION
═══════════════════════════════════════════ */
function setErr(inputId, errId, show) {
  const inp = document.getElementById(inputId);
  const err = document.getElementById(errId);
  if (!inp || !err) return show;
  inp.classList.toggle('is-error', show);
  err.classList.toggle('show', show);
  return show;
}

function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }

const form = document.getElementById('requestForm');
if (form) {
  form.addEventListener('submit', function(e) {
    let hasErr = false;

    const name  = document.getElementById('sender_name').value.trim();
    const email = document.getElementById('sender_email').value.trim();
    const msg   = document.getElementById('message').value.trim();

    if (setErr('sender_name',  'err-name',    !name))                  hasErr = true;
    if (setErr('sender_email', 'err-email',   !validateEmail(email)))  hasErr = true;
    if (setErr('message',      'err-message', msg.length < 20))        hasErr = true;

    if (hasErr) {
      e.preventDefault();
      showModal('error', 'Please Fix These Issues',
        'Some fields are missing or incorrect. Please review the highlighted fields and try again.');
      form.querySelector('.is-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  // Live clear on input
  form.querySelectorAll('.form-input, .form-textarea').forEach(el => {
    el.addEventListener('input', function() {
      if (this.readOnly) return;
      this.classList.remove('is-error');
      const errEl = this.closest('.form-group')?.querySelector('.field-error');
      if (errEl) errEl.classList.remove('show');
    });
  });
}
</script>
</body>
</html>