<!DOCTYPE html>
<html lang="en">
  <?php include 'header.php'; ?>
  <body>

  <?php
    // ── Session & DB already handled in header.php ────────────────────────────
    // $con, $user, $_SESSION['user_id'] are available

    $success_msg = '';
    $error_msg   = '';

    // ── Subject options ────────────────────────────────────────────────────────
    $subjects = [
      'General Inquiry',
      'Booking Issue',
      'Payment Problem',
      'Car / Vehicle Complaint',
      'Driver Complaint',
      'Account & Profile Help',
      'Refund Request',
      'Technical Support',
      'Feedback / Suggestion',
      'Other',
    ];

    // ── Handle form submission ─────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {

      // Determine sender type & id
      if (isset($_SESSION['user_id'])) {
        $sender_type = 'user';
        $sender_id   = intval($_SESSION['user_id']);
        $sender_name  = mysqli_real_escape_string($con, $user['uname']);
        $sender_email = mysqli_real_escape_string($con, $user['email']);
        $sender_mobile= mysqli_real_escape_string($con, $user['mobno'] ?? '');
      } else {
        $sender_type  = 'guest';
        $sender_id_raw = null;
        $sender_name  = mysqli_real_escape_string($con, trim($_POST['sender_name'] ?? ''));
        $sender_email = mysqli_real_escape_string($con, trim($_POST['sender_email'] ?? ''));
        $sender_mobile= mysqli_real_escape_string($con, trim($_POST['sender_mobile'] ?? ''));

        if (empty($sender_name) || empty($sender_email)) {
          $error_msg = 'Please fill in your name and email.';
        }
      }

      $subject = mysqli_real_escape_string($con, trim($_POST['subject'] ?? ''));
      $message = mysqli_real_escape_string($con, trim($_POST['message'] ?? ''));

      if (strlen(trim($_POST['message'] ?? '')) < 20) {
        $error_msg = 'Message must be at least 20 characters.';
      }
      if (empty($subject)) {
        $error_msg = 'Please select a subject.';
      }

      // ── Handle attachment upload ────────────────────────────────────────────
      $attachment_val = 'NULL';
      if (!empty($_FILES['attachment']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','txt'];
        $ext     = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
          $error_msg = 'Invalid file type. Allowed: jpg, png, gif, pdf, doc, docx, txt';
        } elseif ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
          $error_msg = 'File size must be under 5 MB.';
        } else {
          $upload_dir = 'contact_attachments/';
          if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
          $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['attachment']['name']);
          if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $fname)) {
            $attachment_val = "'" . mysqli_real_escape_string($con, $upload_dir . $fname) . "'";
          } else {
            $error_msg = 'Failed to upload attachment. Please try again.';
          }
        }
      }

      // ── Insert into contact_master ──────────────────────────────────────────
      if (empty($error_msg)) {
        if (isset($_SESSION['user_id'])) {
          $sql = "INSERT INTO contact_master
                    (sender_type, sender_id, sender_name, sender_email, sender_mobile, subject, message, attachment, status)
                  VALUES
                    ('$sender_type', $sender_id, '$sender_name', '$sender_email', '$sender_mobile',
                     '$subject', '$message', $attachment_val, 0)";
        } else {
          $sql = "INSERT INTO contact_master
                    (sender_type, sender_id, sender_name, sender_email, sender_mobile, subject, message, attachment, status)
                  VALUES
                    ('guest', NULL, '$sender_name', '$sender_email', '$sender_mobile',
                     '$subject', '$message', $attachment_val, 0)";
        }

        if (mysqli_query($con, $sql)) {
          $success_msg = 'Your message has been sent! We will get back to you soon.';
        } else {
          $error_msg = 'Something went wrong. Please try again later.';
        }
      }
    }
  ?>

    <!-- Hero Section (unchanged) -->
    <section class="hero-wrap hero-wrap-2 js-fullheight" style="background-image: url('images/bg_3.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text js-fullheight align-items-end justify-content-start">
          <div class="col-md-9 ftco-animate pb-5">
            <p class="breadcrumbs">
              <span class="mr-2"><a href="index.php">Home <i class="ion-ios-arrow-forward"></i></a></span>
              <span>Contact <i class="ion-ios-arrow-forward"></i></span>
            </p>
            <h1 class="mb-3 bread">Contact Us</h1>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section class="ftco-section contact-section">
      <div class="container">
        <div class="row d-flex mb-5 contact-info">

          <!-- Left: Info cards (unchanged) -->
          <div class="col-md-4">
            <div class="row mb-5">
              <div class="col-md-12">
                <div class="border w-100 p-4 rounded mb-2 d-flex">
                  <div class="icon mr-3"><span class="icon-map-o"></span></div>
                  <p><span>Address:</span> 214, Ranchhod Nagar, opposite Swami Narayan Temple, Udhna, Surat, Gujarat - 394210</p>
                </div><br>
              </div>
              <div class="col-md-12">
                <div class="border w-100 p-4 rounded mb-2 d-flex">
                  <div class="icon mr-3"><span class="icon-mobile-phone"></span></div>
                  <p><span>Phone:</span> <a href="tel://1234567920">9104390576</a></p>
                </div><br>
              </div>
              <div class="col-md-12">
                <div class="border w-100 p-4 rounded mb-2 d-flex">
                  <div class="icon mr-3"><span class="icon-envelope-o"></span></div>
                  <p><span>Email:</span> <a href="mailto:info@yoursite.com">carbook443@gmail.com</a></p>
                </div><br>
              </div>
            </div>
          </div>

          <!-- Right: Contact Form -->
          <div class="col-md-8 block-9 mb-md-5">

            <?php if($success_msg): ?>
            <div class="cb-success-card">
              <div class="cb-success-icon">
                <svg width="38" height="38" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
                </svg>
              </div>
              <div>
                <div class="cb-success-title">Message Sent!</div>
                <div class="cb-success-sub"><?= htmlspecialchars($success_msg) ?></div>
              </div>
              <a href="contact.php" class="cb-send-again">Send Another</a>
            </div>
            <?php else: ?>

            <form action="contact.php" method="POST" enctype="multipart/form-data" class="cb-form" id="contactForm" novalidate>

              <?php if($error_msg): ?>
              <div class="cb-alert">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error_msg) ?>
              </div>
              <?php endif; ?>

              <!-- ── Logged-in user: show prefilled read-only info ── -->
              <?php if(isset($_SESSION['user_id']) && $user): ?>
              <div class="cb-user-banner">
                <img src="user_profile/<?= htmlspecialchars($user['photo'] ?? '') ?>"
                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'"
                     class="cb-user-avatar" alt="">
                <div class="cb-user-info">
                  <div class="cb-user-name"><?= htmlspecialchars($user['uname']) ?></div>
                  <div class="cb-user-meta"><?= htmlspecialchars($user['email']) ?>
                    <?php if(!empty($user['mobno'])): ?>
                      &nbsp;·&nbsp; <?= htmlspecialchars($user['mobno']) ?>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="cb-logged-badge">Logged In</span>
              </div>
              <?php else: ?>
              <!-- ── Guest: manual name/email/mobile ── -->
              <div class="cb-field-group">
                <div class="cb-field">
                  <label class="cb-label">Your Name <span class="cb-req">*</span></label>
                  <input type="text" name="sender_name" class="cb-input"
                         placeholder="Full name"
                         value="<?= htmlspecialchars($_POST['sender_name'] ?? '') ?>" required>
                </div>
                <div class="cb-field">
                  <label class="cb-label">Email Address <span class="cb-req">*</span></label>
                  <input type="email" name="sender_email" class="cb-input"
                         placeholder="you@example.com"
                         value="<?= htmlspecialchars($_POST['sender_email'] ?? '') ?>" required>
                </div>
              </div>
              <div class="cb-field">
                <label class="cb-label">Mobile Number <span class="cb-opt">(optional)</span></label>
                <input type="text" name="sender_mobile" class="cb-input"
                       placeholder="+91 XXXXX XXXXX"
                       value="<?= htmlspecialchars($_POST['sender_mobile'] ?? '') ?>">
              </div>
              <?php endif; ?>

              <!-- ── Subject dropdown ── -->
              <div class="cb-field">
                <label class="cb-label">Subject <span class="cb-req">*</span></label>
                <div class="cb-select-wrap">
                  <select name="subject" class="cb-select" required>
                    <option value="" disabled <?= empty($_POST['subject']) ? 'selected' : '' ?>>
                      — Select a subject —
                    </option>
                    <?php foreach($subjects as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"
                      <?= (isset($_POST['subject']) && $_POST['subject'] === $s) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($s) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="cb-select-arrow">▾</span>
                </div>
              </div>

              <!-- ── Message ── -->
              <div class="cb-field">
                <label class="cb-label">
                  Message <span class="cb-req">*</span>
                  <span class="cb-char-hint" id="charHint">0 / min 20</span>
                </label>
                <textarea name="message" class="cb-textarea" id="msgArea"
                          placeholder="Describe your issue or question in detail…"
                          rows="6" minlength="20" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                <div class="cb-char-bar"><div class="cb-char-fill" id="charFill"></div></div>
              </div>

              <!-- ── Attachment (optional) ── -->
              <div class="cb-field">
                <label class="cb-label">
                  Attachment <span class="cb-opt">(optional · max 5 MB · jpg, png, pdf, doc, txt)</span>
                </label>
                <label class="cb-file-label" id="fileLabel" for="attachFile">
                  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                  </svg>
                  <span id="fileName">Click to choose file…</span>
                </label>
                <input type="file" name="attachment" id="attachFile" class="cb-file-input"
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
              </div>

              <input type="hidden" name="send_contact" value="1">

              <button type="submit" class="cb-submit-btn">
                <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Send Message
              </button>

            </form>
            <?php endif; ?>

          </div><!-- /col-md-8 -->
        </div>
      </div>
    </section>

    <?php include 'footer.php'; ?>

  </body>
</html>

<!-- ── Scoped styles for the new form only ──────────────────────────────────── -->
<style>
/* Form card */
.cb-form {
  background: #ffffff;
  border-radius: 18px;
  padding: 36px 38px;
  box-shadow: 0 8px 40px rgba(0,0,0,.10);
  border: 1.5px solid #e9ecef;
}

/* Alert */
.cb-alert {
  display: flex; align-items: center; gap: 9px;
  background: #fff5f5; border: 1.5px solid #fca5a5;
  color: #b91c1c; border-radius: 10px;
  padding: 11px 16px; font-size: .88rem; margin-bottom: 20px;
}

/* Logged-in user banner */
.cb-user-banner {
  display: flex; align-items: center; gap: 14px;
  background: linear-gradient(135deg, #f0f4ff 0%, #e8f5e9 100%);
  border: 1.5px solid #c7d9ff;
  border-radius: 14px; padding: 14px 18px;
  margin-bottom: 22px;
}
.cb-user-avatar {
  width: 48px; height: 48px; border-radius: 50%; object-fit: cover;
  border: 2px solid #4f7cff;
  flex-shrink: 0;
}
.cb-user-info { flex: 1; min-width: 0; }
.cb-user-name { font-weight: 700; font-size: .97rem; color: #1a1a2e; }
.cb-user-meta { font-size: .78rem; color: #6b7280; margin-top: 2px; }
.cb-logged-badge {
  background: #4f7cff; color: #fff;
  font-size: .65rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; padding: 3px 10px; border-radius: 20px;
  flex-shrink: 0;
}

/* Field groups */
.cb-field { margin-bottom: 20px; }
.cb-field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
@media(max-width: 576px) { .cb-field-group { grid-template-columns: 1fr; } }

.cb-label {
  display: flex; align-items: center; justify-content: space-between;
  font-size: .82rem; font-weight: 700; color: #374151;
  text-transform: uppercase; letter-spacing: .05em;
  margin-bottom: 7px;
}
.cb-req { color: #ef4444; margin-left: 2px; }
.cb-opt { font-weight: 400; text-transform: none; letter-spacing: 0; color: #9ca3af; font-size: .75rem; }

/* Inputs */
.cb-input, .cb-textarea, .cb-select {
  width: 100%; border: 1.5px solid #d1d5db;
  border-radius: 10px; padding: 11px 14px;
  font-size: .92rem; color: #111827;
  background: #f9fafb;
  transition: border-color .2s, box-shadow .2s, background .2s;
  outline: none; font-family: inherit;
}
.cb-input:focus, .cb-textarea:focus, .cb-select:focus {
  border-color: #4f7cff;
  box-shadow: 0 0 0 3px rgba(79,124,255,.13);
  background: #fff;
}
.cb-textarea { resize: vertical; min-height: 120px; }

/* Select wrapper */
.cb-select-wrap { position: relative; }
.cb-select { appearance: none; padding-right: 36px; cursor: pointer; }
.cb-select-arrow {
  position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
  color: #9ca3af; pointer-events: none; font-size: .8rem;
}

/* Char counter */
.cb-char-hint { font-weight: 400; font-size: .75rem; color: #9ca3af; letter-spacing: 0; text-transform: none; }
.cb-char-bar {
  height: 3px; background: #f3f4f6; border-radius: 10px;
  margin-top: 6px; overflow: hidden;
}
.cb-char-fill {
  height: 100%; width: 0; border-radius: 10px;
  background: #ef4444;
  transition: width .2s, background .2s;
}

/* File input */
.cb-file-input { display: none; }
.cb-file-label {
  display: flex; align-items: center; gap: 10px;
  background: #f9fafb; border: 1.5px dashed #d1d5db;
  border-radius: 10px; padding: 12px 16px;
  cursor: pointer; color: #6b7280; font-size: .88rem;
  transition: border-color .2s, background .2s;
}
.cb-file-label:hover { border-color: #4f7cff; background: #f0f4ff; color: #4f7cff; }
.cb-file-label.has-file { border-color: #10b981; background: #ecfdf5; color: #065f46; border-style: solid; }

/* Submit */
.cb-submit-btn {
  display: inline-flex; align-items: center; gap: 9px;
  background: linear-gradient(135deg, #4f7cff 0%, #2563eb 100%);
  color: #fff; border: none; border-radius: 10px;
  padding: 13px 32px; font-size: .95rem; font-weight: 700;
  cursor: pointer; width: 100%; justify-content: center;
  transition: opacity .2s, transform .2s, box-shadow .2s;
  box-shadow: 0 4px 18px rgba(79,124,255,.3);
  margin-top: 6px;
}
.cb-submit-btn:hover {
  opacity: .92; transform: translateY(-1px);
  box-shadow: 0 6px 24px rgba(79,124,255,.4);
}
.cb-submit-btn:active { transform: none; }

/* Success card */
.cb-success-card {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 18px; padding: 32px 30px;
  display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
  box-shadow: 0 8px 30px rgba(16,185,129,.25);
}
.cb-success-icon {
  width: 60px; height: 60px; border-radius: 50%;
  background: rgba(255,255,255,.2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cb-success-title { font-size: 1.2rem; font-weight: 800; color: #fff; }
.cb-success-sub { font-size: .88rem; color: rgba(255,255,255,.85); margin-top: 3px; }
.cb-send-again {
  margin-left: auto; background: rgba(255,255,255,.2);
  color: #fff; border: 1.5px solid rgba(255,255,255,.4);
  border-radius: 8px; padding: 8px 20px;
  font-size: .85rem; font-weight: 700; text-decoration: none;
  transition: background .2s;
}
.cb-send-again:hover { background: rgba(255,255,255,.3); color: #fff; }
</style>

<script>
// ── Char counter + fill bar ───────────────────────────────────────────────────
const msgArea  = document.getElementById('msgArea');
const charHint = document.getElementById('charHint');
const charFill = document.getElementById('charFill');
const MIN = 20;

function updateChar() {
  const len = msgArea.value.length;
  charHint.textContent = len + ' / min ' + MIN;
  const pct = Math.min(len / MIN, 1) * 100;
  charFill.style.width = pct + '%';
  if (len >= MIN) {
    charFill.style.background = '#10b981';
    charHint.style.color = '#059669';
  } else {
    charFill.style.background = pct > 60 ? '#f59e0b' : '#ef4444';
    charHint.style.color = '#9ca3af';
  }
}
if (msgArea) { msgArea.addEventListener('input', updateChar); updateChar(); }

// ── File label update ─────────────────────────────────────────────────────────
const attachFile = document.getElementById('attachFile');
const fileLabel  = document.getElementById('fileLabel');
const fileName   = document.getElementById('fileName');

if (attachFile) {
  attachFile.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      fileName.textContent = this.files[0].name;
      fileLabel.classList.add('has-file');
    } else {
      fileName.textContent = 'Click to choose file…';
      fileLabel.classList.remove('has-file');
    }
  });
}

// ── Client-side validation ────────────────────────────────────────────────────
const form = document.getElementById('contactForm');
if (form) {
  form.addEventListener('submit', function(e) {
    if (msgArea && msgArea.value.trim().length < 20) {
      e.preventDefault();
      msgArea.focus();
      charHint.style.color = '#ef4444';
      msgArea.style.borderColor = '#ef4444';
      msgArea.style.boxShadow = '0 0 0 3px rgba(239,68,68,.13)';
    }
  });
}
</script>