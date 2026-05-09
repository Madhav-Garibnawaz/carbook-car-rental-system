<!-- ********************** -->
<!--***** WORK PANDING *****-->
<!-- ********************** -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AutoDrive — Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #1a4b8c;
      --blue-dark: #123570;
      --sea: #2a9d8f;
      --sea-dark: #1f7a6e;
      --sea-light: #e0f5f3;
      --black: #0f1923;
      --white: #ffffff;
      --off: #f2f6fb;
      --gray: #6c7a8d;
      --light: #dde6f0;
      --border: #c8d6e8;
    }

    body {
      background-color: var(--off);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 30px 16px;
      position: relative; overflow: hidden;
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
      font-size: clamp(80px, 18vw, 200px); font-weight: 800;
      color: rgba(26,75,140,0.04);
      pointer-events: none; letter-spacing: -0.02em; line-height: 1; z-index: 0;
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
    .fp-card {
      width: 100%; background: var(--white);
      border-radius: 18px; border: 1px solid var(--border);
      box-shadow: 0 6px 36px rgba(26,75,140,0.1); overflow: hidden;
    }
    .card-accent { height: 4px; background: var(--sea); }

    /* ─── PANELS ─── */
    .panel { display: none; padding: 36px 38px 32px; }
    .panel.active { display: block; }

    /* ─── ICON BADGE ─── */
    .icon-badge {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--sea-light);
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
    .panel-title {
      font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800;
      color: var(--black); text-align: center; margin-bottom: 8px; letter-spacing: -0.01em;
    }
    .panel-sub {
      font-size: 13.5px; color: var(--gray);
      text-align: center; line-height: 1.6; margin-bottom: 4px;
    }

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

    /* ─── FIELDS ─── */
    .field-group { margin-bottom: 18px; }
    .form-label {
      font-size: 11px; font-weight: 600; letter-spacing: 0.07em;
      color: var(--gray); margin-bottom: 6px; text-transform: uppercase; display: block;
    }
    .input-wrap { position: relative; }
    .form-control {
      width: 100%; padding: 11px 44px 11px 14px;
      border: 1.5px solid var(--border); border-radius: 9px;
      background: var(--off); color: var(--black);
      font-family: 'DM Sans', sans-serif; font-size: 14px;
      outline: none; transition: border-color 0.18s, background 0.18s, box-shadow 0.18s;
    }
    .form-control:focus { border-color: var(--sea); background: var(--white); box-shadow: 0 0 0 3px rgba(42,157,143,0.13); }
    .form-control.invalid { border-color: #e74c3c; }
    .form-control::placeholder { color: #aab5c4; }

    /* Eye toggle */
    .toggle-eye {
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; padding: 0;
      color: var(--gray); display: flex; align-items: center; transition: color 0.15s;
    }
    .toggle-eye:hover { color: var(--sea); }
    .toggle-eye svg { width: 18px; height: 18px; }

    /* ─── STRENGTH BAR ─── */
    .strength-bar { display: flex; gap: 4px; margin-top: 8px; }
    .strength-seg { flex: 1; height: 3px; border-radius: 2px; background: var(--light); transition: background 0.25s; }
    .strength-seg.weak   { background: #e74c3c; }
    .strength-seg.medium { background: #f39c12; }
    .strength-seg.strong { background: var(--sea); }
    .strength-text { font-size: 11px; color: var(--gray); margin-top: 5px; font-weight: 500; min-height: 16px; transition: color 0.2s; }
    .strength-text.weak   { color: #e74c3c; }
    .strength-text.medium { color: #f39c12; }
    .strength-text.strong { color: var(--sea-dark); }

    /* ─── STATUS ─── */
    .status-msg {
      font-size: 12.5px; text-align: center; font-weight: 500;
      min-height: 18px; margin: 10px 0 14px; color: transparent; transition: color 0.2s;
    }
    .status-msg.error   { color: #e74c3c; }
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
    .btn-submit:hover { background: var(--sea-dark); transform: translateY(-1px); box-shadow: 0 6px 22px rgba(42,157,143,0.33); }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit.blue-btn { background: var(--blue); box-shadow: 0 4px 16px rgba(26,75,140,0.22); }
    .btn-submit.blue-btn:hover { background: var(--blue-dark); box-shadow: 0 6px 22px rgba(26,75,140,0.3); }

    /* ─── LINKS ─── */
    .link-accent { color: var(--sea); text-decoration: none; font-weight: 500; }
    .link-accent:hover { text-decoration: underline; color: var(--sea-dark); }
    .center-text { text-align: center; font-size: 13px; color: var(--gray); margin-top: 16px; }

    /* ─── SUCCESS ─── */
    .success-check {
      width: 70px; height: 70px; border-radius: 50%;
      background: var(--sea-light);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      animation: popIn 0.4s cubic-bezier(.175,.885,.32,1.275) forwards;
    }
    @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    .success-check svg { width: 32px; height: 32px; }

    /* ─── BACK LINK ─── */
    .back-link { margin-top: 18px; font-size: 12.5px; color: var(--gray); text-align: center; }
    .back-link a { color: var(--sea); text-decoration: none; font-weight: 500; }
    .back-link a:hover { text-decoration: underline; color: var(--sea-dark); }
  </style>
</head>
<body>

<div class="page-wrapper">

  <!-- Brand -->
  <div class="brand-header">
    <div class="logo-pill">
      <div class="logo-icon">
        <svg viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
      </div>
      <div class="logo-text">Auto<span>Drive</span></div>
    </div>
    <div class="brand-sub">Your trusted car marketplace</div>
  </div>

  <!-- Card -->
  <div class="fp-card">
    <div class="card-accent"></div>

    <!-- ══ FORM PANEL ══ -->
    <div class="panel active" id="formPanel">

      <div class="icon-badge">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M19 11H5C3.89543 11 3 11.8954 3 13V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V13C21 11.8954 20.1046 11 19 11Z" stroke="#2a9d8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#2a9d8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="16" r="1.5" fill="#2a9d8f"/>
        </svg>
      </div>

      <div class="panel-title">Reset Password</div>
      <div class="panel-sub">Enter your email and choose a new password for your account.</div>

      <div class="section-label">Account Details</div>

      <form onsubmit="resetPassword(event)" novalidate>

        <!-- Email -->
        <div class="field-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <input type="email" class="form-control" id="emailInput" placeholder="you@example.com" autocomplete="email"/>
          </div>
        </div>

        <!-- New Password -->
        <div class="field-group">
          <label class="form-label">New Password</label>
          <div class="input-wrap">
            <input type="password" class="form-control" id="newPass" placeholder="Min. 8 characters" oninput="checkStrength(this.value)"/>
            <button type="button" class="toggle-eye" onclick="toggleEye('newPass', this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <div class="strength-bar">
            <div class="strength-seg" id="s1"></div>
            <div class="strength-seg" id="s2"></div>
            <div class="strength-seg" id="s3"></div>
            <div class="strength-seg" id="s4"></div>
          </div>
          <div class="strength-text" id="strengthText"></div>
        </div>

        <!-- Confirm Password -->
        <div class="field-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" class="form-control" id="confirmPass" placeholder="Repeat password"/>
            <button type="button" class="toggle-eye" onclick="toggleEye('confirmPass', this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="status-msg" id="statusMsg"></div>
        <button type="submit" class="btn-submit">Reset Password</button>
      </form>

      <div class="center-text">
        Remembered it? <a href="car-auth-form.html" class="link-accent">Back to Sign In</a>
      </div>
    </div>

    <!-- ══ SUCCESS PANEL ══ -->
    <div class="panel" id="successPanel">
      <div class="success-check">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M20 6L9 17L4 12" stroke="#2a9d8f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="panel-title">Password Reset!</div>
      <div class="panel-sub" style="margin-bottom:24px;">
        Your password has been updated successfully.<br/>
        You can now sign in with your new password.
      </div>
      <a href="car-auth-form.html">
        <button class="btn-submit blue-btn">Go to Sign In</button>
      </a>
    </div>

  </div>

  <div class="back-link">
    <a href="car-auth-form.html">← Back to Sign In</a>
  </div>

</div>

<script>
  function checkStrength(val) {
    const segs = ['s1','s2','s3','s4'].map(id => document.getElementById(id));
    const txt = document.getElementById('strengthText');
    segs.forEach(s => s.className = 'strength-seg');
    if (!val) { txt.textContent = ''; txt.className = 'strength-text'; return; }
    let score = [val.length >= 8, /[A-Z]/.test(val), /[0-9]/.test(val), /[^A-Za-z0-9]/.test(val)].filter(Boolean).length;
    const cls = ['','weak','medium','medium','strong'][score];
    const lbl = ['','Weak','Fair','Good','Strong'][score];
    segs.slice(0, score).forEach(s => s.classList.add(cls));
    txt.textContent = lbl; txt.className = 'strength-text ' + cls;
  }

  function toggleEye(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.querySelector('svg').innerHTML = f.type === 'text'
      ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
      : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }

  function resetPassword(e) {
    e.preventDefault();
    const email = document.getElementById('emailInput').value.trim();
    const np    = document.getElementById('newPass').value;
    const cp    = document.getElementById('confirmPass').value;
    const msg   = document.getElementById('statusMsg');

    if (!email || !/\S+@\S+\.\S+/.test(email)) { setMsg(msg, 'Please enter a valid email address.', 'error'); return; }
    if (np.length < 8)  { setMsg(msg, 'Password must be at least 8 characters.', 'error'); return; }
    if (np !== cp)      { setMsg(msg, 'Passwords do not match.', 'error'); return; }

    setMsg(msg, '✓ Password updated!', 'success');
    setTimeout(() => {
      document.getElementById('formPanel').classList.remove('active');
      document.getElementById('successPanel').classList.add('active');
    }, 600);
  }

  function setMsg(el, text, type) { el.textContent = text; el.className = 'status-msg ' + type; }
</script>
</body>
</html>