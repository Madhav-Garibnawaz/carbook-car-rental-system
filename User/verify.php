<?php
        require('connect.php');
        $mail = $_GET['email'];

        $modalTitle   = '';
        $modalMessage = '';
        $modalType    = '';
        $modalRedirect = '';

        if(isset($_POST['verify'])){
          $otp = $_POST['otp'];
          // FIX: wrap $mail in single quotes so SQL treats it as a string
          $q = mysqli_query($con, "SELECT * FROM users_master WHERE email='$mail'");
          $r = mysqli_fetch_array($q);

          if($r['otp'] == $otp) {
            // Mark account as fully verified (status = 2)
            mysqli_query($con, "UPDATE users_master SET status=2 WHERE email='$mail'");
            $modalTitle    = 'Email Verified!';
            $modalMessage  = 'Your account has been successfully verified. You can now sign in.';
            $modalType     = 'success';
            $modalRedirect = 'register.php';
          } else {
            $modalTitle   = 'Invalid OTP';
            $modalMessage = 'The code you entered is incorrect. Please check your email and try again.';
            $modalType    = 'error';
          }
        }
      ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AutoDrive — OTP Verification</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #1a4b8c;
      --blue-dark: #123570;
      --blue-light: #e8f0fc;
      --sea: #2a9d8f;
      --sea-dark: #1f7a6e;
      --sea-light: #e0f5f3;
      --black: #0f1923;
      --white: #ffffff;
      --off: #f2f6fb;
      --gray: #6c7a8d;
      --light: #dde6f0;
      --border: #c8d6e8;
      --error: #e74c3c;
      --error-light: #fdf0ef;
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
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image:
        radial-gradient(circle at 12% 12%, rgba(26,75,140,0.09) 0%, transparent 50%),
        radial-gradient(circle at 88% 88%, rgba(42,157,143,0.09) 0%, transparent 50%);
      pointer-events: none; z-index: 0;
    }

    body::after {
      content: 'AUTODRIVE';
      position: fixed; bottom: -30px; right: -30px;
      font-family: 'Syne', sans-serif;
      font-size: clamp(80px, 18vw, 200px);
      font-weight: 800;
      color: rgba(26,75,140,0.04);
      pointer-events: none;
      letter-spacing: -0.02em;
      line-height: 1; z-index: 0;
    }

    .page-wrapper {
      width: 100%; max-width: 440px;
      position: relative; z-index: 1;
      display: flex; flex-direction: column; align-items: center;
    }

    /* ─── BRAND ─── */
    .brand-header { text-align: center; margin-bottom: 22px; }
    .logo-pill {
      display: inline-flex; align-items: center; gap: 12px;
      background: var(--white); border: 1px solid var(--border);
      border-radius: 50px; padding: 8px 22px 8px 10px;
      box-shadow: 0 2px 16px rgba(26,75,140,0.1);
    }
    .logo-icon {
      width: 36px; height: 36px; background: var(--blue);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
    }
    .logo-icon svg { fill: white; width: 20px; height: 20px; }
    .logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--black); letter-spacing: -0.01em; }
    .logo-text span { color: var(--sea); }
    .brand-sub { margin-top: 9px; font-size: 12.5px; color: var(--gray); }

    /* ─── CARD ─── */
    .otp-card {
      width: 100%;
      background: var(--white);
      border-radius: 18px;
      border: 1px solid var(--border);
      box-shadow: 0 6px 36px rgba(26,75,140,0.1);
      overflow: hidden;
    }
    .card-accent { height: 4px; background: var(--sea); }
    .card-body-inner { padding: 36px 38px 32px; }

    /* ─── ICON BADGE ─── */
    .icon-badge {
      width: 64px; height: 64px;
      background: var(--sea-light); border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px; position: relative;
    }
    .icon-badge::after {
      content: ''; position: absolute; inset: -5px;
      border-radius: 50%; border: 2px dashed var(--border);
      animation: spin 18s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .icon-badge svg { width: 28px; height: 28px; }

    /* ─── TEXT ─── */
    .otp-title {
      font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800;
      color: var(--black); text-align: center; margin-bottom: 8px; letter-spacing: -0.01em;
    }
    .otp-sub { font-size: 13.5px; color: var(--gray); text-align: center; line-height: 1.6; margin-bottom: 6px; }
    .otp-email { font-weight: 600; color: var(--blue); }

    /* ─── SECTION LABEL ─── */
    .section-label {
      font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
      letter-spacing: 0.14em; text-transform: uppercase; color: var(--sea);
      margin: 22px 0 14px; padding-bottom: 8px; border-bottom: 2px solid var(--sea-light);
      display: flex; align-items: center; gap: 8px;
    }
    .section-label::before {
      content: ''; width: 4px; height: 14px; background: var(--sea);
      border-radius: 2px; display: inline-block; flex-shrink: 0;
    }

    /* ─── SINGLE OTP INPUT ─── */
    .otp-input-wrap { position: relative; margin-bottom: 8px; }

    #otpInput {
      width: 100%;
      padding: 16px 20px;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      background: var(--off);
      font-family: 'Syne', sans-serif;
      font-size: 28px;
      font-weight: 800;
      color: var(--blue);
      letter-spacing: 0.45em;
      text-align: center;
      outline: none;
      caret-color: var(--sea);
      transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
    }
    #otpInput:focus {
      border-color: var(--sea);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(42,157,143,0.15);
    }
    #otpInput.filled {
      border-color: var(--sea);
      background: var(--sea-light);
      color: var(--sea-dark);
    }
    #otpInput.error {
      border-color: var(--error);
      background: var(--error-light);
      color: var(--error);
      animation: shake 0.35s ease;
    }

    .char-counter {
      position: absolute; right: 14px; top: 50%;
      transform: translateY(-50%);
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 700;
      color: var(--border);
      pointer-events: none;
      transition: color 0.18s;
    }
    #otpInput:focus ~ .char-counter { color: var(--sea); }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%      { transform: translateX(-6px); }
      40%      { transform: translateX(6px); }
      60%      { transform: translateX(-4px); }
      80%      { transform: translateX(4px); }
    }

    /* ─── RESEND ROW ─── */
    .resend-row {
      display: flex; justify-content: center;
      margin-top: 14px;
    }
    .resend-btn {
      font-size: 12.5px; color: var(--sea);
      background: none; border: none; cursor: pointer;
      font-family: 'DM Sans', sans-serif; font-weight: 500; padding: 0;
    }
    .resend-btn:hover { text-decoration: underline; color: var(--sea-dark); }

    /* ─── STATUS ─── */
    .status-msg {
      min-height: 20px; font-size: 12.5px; text-align: center;
      color: transparent; margin: 10px 0 14px; font-weight: 500;
      transition: color 0.2s;
    }
    .status-msg.error   { color: var(--error); }
    .status-msg.success { color: var(--sea-dark); }

    /* ─── BUTTON ─── */
    .btn-submit {
      width: 100%; padding: 13px; background: var(--sea);
      border: none; border-radius: 10px; color: #fff;
      font-family: 'Syne', sans-serif; font-size: 13.5px; font-weight: 700;
      letter-spacing: 0.07em; text-transform: uppercase; cursor: pointer;
      transition: background 0.18s, transform 0.12s, box-shadow 0.18s;
      box-shadow: 0 4px 16px rgba(42,157,143,0.26);
    }
    .btn-submit:hover:not(:disabled) {
      background: var(--sea-dark); box-shadow: 0 6px 22px rgba(42,157,143,0.33); transform: translateY(-1px);
    }
    .btn-submit:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; }

    /* ─── SUCCESS OVERLAY ─── */
    .success-overlay {
      display: none; flex-direction: column;
      align-items: center; padding: 38px 38px 32px; text-align: center;
    }
    .success-overlay.show { display: flex; }
    .success-check {
      width: 70px; height: 70px; border-radius: 50%;
      background: var(--sea-light);
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 20px;
      animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275) forwards;
    }
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    .success-check svg { width: 32px; height: 32px; }
    .success-title { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--black); margin-bottom: 8px; }
    .success-msg { font-size: 13.5px; color: var(--gray); line-height: 1.6; margin-bottom: 24px; }
    .btn-continue {
      padding: 12px 36px; background: var(--blue); border: none; border-radius: 10px; color: #fff;
      font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
      letter-spacing: 0.07em; text-transform: uppercase; cursor: pointer;
      box-shadow: 0 4px 16px rgba(26,75,140,0.22); transition: background 0.18s, transform 0.12s;
    }
    .btn-continue:hover { background: var(--blue-dark); transform: translateY(-1px); }

    /* ─── TOAST ─── */
    .toast-msg { display: none; padding: 11px 22px; background: var(--black); color: #fff; font-size: 13px; font-weight: 500; border-left: 4px solid var(--sea); }
    .toast-msg.show { display: block; }

    /* ─── BACK LINK ─── */
    .back-link { margin-top: 18px; font-size: 12.5px; color: var(--gray); text-align: center; }
    .back-link a { color: var(--sea); text-decoration: none; font-weight: 500; }
    .back-link a:hover { text-decoration: underline; color: var(--sea-dark); }

    /* ═══════════════════════════════════════════
       THEMED MODAL
    ═══════════════════════════════════════════ */
    .cb-modal-overlay {
      position: fixed; inset: 0;
      background: rgba(15,25,35,0.55);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: 9999;
      display: flex; align-items: center; justify-content: center;
      padding: 20px;
      opacity: 0; pointer-events: none;
      transition: opacity 0.22s ease;
    }
    .cb-modal-overlay.open { opacity: 1; pointer-events: all; }
    .cb-modal {
      background: var(--white); border-radius: 18px;
      border: 1px solid var(--border);
      box-shadow: 0 16px 60px rgba(26,75,140,0.18);
      width: 100%; max-width: 400px; overflow: hidden;
      transform: translateY(16px) scale(0.97);
      transition: transform 0.26s cubic-bezier(.4,0,.2,1);
    }
    .cb-modal-overlay.open .cb-modal { transform: translateY(0) scale(1); }
    .cb-modal-accent { height: 4px; width: 100%; }
    .cb-modal-accent.success { background: var(--sea); }
    .cb-modal-accent.error   { background: var(--error); }
    .cb-modal-accent.warn    { background: #f39c12; }
    .cb-modal-header {
      padding: 22px 24px 16px;
      display: flex; align-items: flex-start; gap: 14px;
      border-bottom: 1px solid var(--border);
    }
    .cb-modal-icon {
      width: 42px; height: 42px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; flex-shrink: 0;
    }
    .cb-modal-icon.success { background: var(--sea-light); }
    .cb-modal-icon.error   { background: var(--error-light); }
    .cb-modal-icon.warn    { background: #fef8ec; }
    .cb-modal-title { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 800; color: var(--black); margin: 0 0 3px; letter-spacing: -0.01em; }
    .cb-modal-subtitle { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
    .cb-modal-subtitle.success { color: var(--sea); }
    .cb-modal-subtitle.error   { color: var(--error); }
    .cb-modal-subtitle.warn    { color: #f39c12; }
    .cb-modal-body { padding: 18px 24px; font-size: 13.5px; color: var(--gray); line-height: 1.6; }
    .cb-modal-body strong { color: var(--black); font-weight: 600; }
    .cb-modal-footer { padding: 0 24px 22px; display: flex; gap: 10px; }
    .cb-modal-btn {
      flex: 1; padding: 11px 16px; border: none; border-radius: 10px;
      font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
      letter-spacing: 0.07em; text-transform: uppercase; cursor: pointer; transition: all 0.16s;
    }
    .cb-modal-btn.primary { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(26,75,140,0.22); }
    .cb-modal-btn.primary:hover { background: var(--blue-dark); }
    .cb-modal-btn.primary.sea { background: var(--sea); box-shadow: 0 4px 14px rgba(42,157,143,0.24); }
    .cb-modal-btn.primary.sea:hover { background: var(--sea-dark); }
    .cb-modal-btn.secondary { background: var(--off); color: var(--gray); border: 1.5px solid var(--border); }
    .cb-modal-btn.secondary:hover { background: var(--light); color: var(--black); }
  </style>
</head>
<body>

<!-- ══ THEMED MODAL ══ -->
<div class="cb-modal-overlay" id="cbModalOverlay" role="dialog" aria-modal="true">
  <div class="cb-modal">
    <div class="cb-modal-accent" id="cbModalAccent"></div>
    <div class="cb-modal-header">
      <div class="cb-modal-icon" id="cbModalIcon"></div>
      <div>
        <div class="cb-modal-subtitle" id="cbModalSubtitle"></div>
        <div class="cb-modal-title" id="cbModalTitle"></div>
      </div>
    </div>
    <div class="cb-modal-body" id="cbModalBody"></div>
    <div class="cb-modal-footer" id="cbModalFooter"></div>
  </div>
</div>

<div class="page-wrapper">

  <!-- Brand -->
  <div class="brand-header">
    <div class="logo-pill">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
      </div>
      <div class="logo-text">Car<span>Book</span></div>
    </div>
    <div class="brand-sub">Your trusted car marketplace</div>
  </div>

  <!-- Card -->
  <div class="otp-card">
    <div class="card-accent"></div>
    <div class="toast-msg" id="toastMsg"></div>

    <!-- OTP Form -->
    <div class="card-body-inner" id="otpFormSection">

      <div class="icon-badge">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4Z" stroke="#2a9d8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M22 6L12 13L2 6" stroke="#2a9d8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <?php
        // FIX: wrap $mail in single quotes
        $qry = mysqli_query($con, "SELECT * FROM users_master WHERE email='$mail'");
        $row = mysqli_fetch_array($qry);
      ?>

      <div class="otp-title">Verify Your Email</div>
      <div class="otp-sub">
        We've sent a 6-digit code to<br/>
        <span class="otp-email"><?php echo htmlspecialchars($row['email']); ?></span>
      </div>

      <div class="section-label">Enter OTP Code</div>
      <form id="otpFormTag" method="post" novalidate>
        <div class="otp-input-wrap">
          <input
            id="otpInput"
            name="otp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="••••••"
            autocomplete="one-time-code"
            oninput="onOtpInput(this)"
            required
          />
          <span class="char-counter" id="charCounter">0 / 6</span>
        </div>

        <div class="status-msg" id="statusMsg"></div>

        <button name="verify" class="btn-submit" type="submit" id="verifyBtn" disabled>Verify &amp; Continue</button>
      </form>

    </div>

    <!-- Success -->
    <div class="success-overlay" id="successOverlay">
      <div class="success-check">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M20 6L9 17L4 12" stroke="#2a9d8f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="success-title">Verified!</div>
      <div class="success-msg">
        Your email has been verified.<br/>
        Welcome to <strong style="color:var(--blue);">CarBook</strong> — let's get you on the road.
      </div>
      <button class="btn-continue" onclick="window.location.href='register.php'">Sign In Now</button>
    </div>

  </div>

  <div class="back-link">
    <a href="register.php">← Back to Sign In</a>
  </div>

</div>

<script>
  /* ═══════════════════════════════════════════
     THEMED MODAL ENGINE
  ═══════════════════════════════════════════ */
  const MODAL_CONFIG = {
    success: { icon: '✓', subtitle: 'Success',   btnClass: 'sea' },
    error:   { icon: '✕', subtitle: 'Error',      btnClass: '' },
    warn:    { icon: '⚠', subtitle: 'Attention', btnClass: '' },
  };

  function showModal(type, title, bodyHtml, redirectUrl) {
    const cfg        = MODAL_CONFIG[type] || MODAL_CONFIG.error;
    const overlay    = document.getElementById('cbModalOverlay');
    const accent     = document.getElementById('cbModalAccent');
    const iconEl     = document.getElementById('cbModalIcon');
    const subtitleEl = document.getElementById('cbModalSubtitle');
    const titleEl    = document.getElementById('cbModalTitle');
    const bodyEl     = document.getElementById('cbModalBody');
    const footerEl   = document.getElementById('cbModalFooter');

    accent.className      = 'cb-modal-accent ' + type;
    iconEl.className      = 'cb-modal-icon ' + type;
    iconEl.textContent    = cfg.icon;
    subtitleEl.className  = 'cb-modal-subtitle ' + type;
    subtitleEl.textContent = cfg.subtitle;
    titleEl.textContent   = title;
    bodyEl.innerHTML      = bodyHtml;
    footerEl.innerHTML    = '';

    if (redirectUrl) {
      const btn = document.createElement('button');
      btn.className   = 'cb-modal-btn primary ' + cfg.btnClass;
      btn.textContent = type === 'success' ? 'Sign In Now' : 'Try Again';
      btn.onclick     = () => {
        if (type === 'success') {
          window.location.href = redirectUrl;
        } else {
          closeModal();
        }
      };
      footerEl.appendChild(btn);
    } else {
      const sec = document.createElement('button');
      sec.className   = 'cb-modal-btn secondary';
      sec.textContent = 'Dismiss';
      sec.onclick     = closeModal;
      const btn = document.createElement('button');
      btn.className   = 'cb-modal-btn primary ' + cfg.btnClass;
      btn.textContent = 'OK, Got It';
      btn.onclick     = closeModal;
      footerEl.appendChild(sec);
      footerEl.appendChild(btn);
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
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
  });

  /* ── Fire PHP modal on page load ── */
  <?php if($modalType): ?>
  window.addEventListener('DOMContentLoaded', function() {
    <?php if($modalType === 'success'): ?>
    // Hide form, show success overlay
    document.getElementById('otpFormSection').style.display = 'none';
    document.getElementById('successOverlay').classList.add('show');
    <?php else: ?>
    showModal(
      '<?= $modalType ?>',
      '<?= addslashes($modalTitle) ?>',
      '<?= addslashes($modalMessage) ?>',
      <?= $modalRedirect ? "'".addslashes($modalRedirect)."'" : 'null' ?>
    );
    // Shake and clear the input on error
    const inp = document.getElementById('otpInput');
    inp.value = '';
    inp.classList.add('error');
    document.getElementById('charCounter').textContent = '0 / 6';
    document.getElementById('verifyBtn').disabled = true;
    setTimeout(() => inp.classList.remove('error'), 500);
    <?php endif; ?>
  });
  <?php endif; ?>

  /* ═══════════════════════════════════════════
     OTP INPUT LOGIC
  ═══════════════════════════════════════════ */
  const inp = document.getElementById('otpInput');
  const msg = document.getElementById('statusMsg');

  function onOtpInput(el) {
    el.value = el.value.replace(/\D/g, '');
    document.getElementById('charCounter').textContent = el.value.length + ' / 6';
    el.classList.toggle('filled', el.value.length === 6);
    el.classList.remove('error');
    msg.className = 'status-msg';
    document.getElementById('verifyBtn').disabled = el.value.length < 6;
  }

  function setMsg(text, type) {
    msg.textContent = text;
    msg.className = 'status-msg ' + type;
  }

  inp.focus();
</script>
</body>
</html>