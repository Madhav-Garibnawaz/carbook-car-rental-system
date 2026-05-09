<?php
// contact_master.php - Admin Contact/Support Requests Management
$host   = 'localhost';
$dbname = 'car_rental';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => $e->getMessage()]));
}

// ── AJAX handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $id     = (int)$_POST['contact_id'];
        $status = (int)$_POST['status'];
        $stmt   = $pdo->prepare("UPDATE contact_master SET status=? WHERE contact_id=?");
        echo json_encode(['ok' => $stmt->execute([$status, $id])]);
        exit;
    }

    if ($_POST['action'] === 'save_reply') {
        $id    = (int)$_POST['contact_id'];
        $reply = trim($_POST['reply']);
        $stmt  = $pdo->prepare("UPDATE contact_master SET admin_reply=?, replied_at=NOW() WHERE contact_id=?");
        echo json_encode(['ok' => $stmt->execute([$reply, $id])]);
        exit;
    }
    exit;
}

// ── Fetch contacts ────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '1';

if ($search !== '') {
    $where  = "(c.sender_name LIKE ? OR c.sender_email LIKE ? OR c.sender_mobile LIKE ? OR c.subject LIKE ?)";
    $like   = "%$search%";
    $params = [$like, $like, $like, $like];
}

$sql = "
SELECT c.*,
       u.uname          AS user_name,  u.photo         AS user_photo,
       d.driver_name,                  d.profile_image AS driver_photo
FROM contact_master c
LEFT JOIN users_master  u ON c.sender_type='user'   AND c.sender_id = u.ui
LEFT JOIN driver_master d ON c.sender_type='driver' AND c.sender_id = d.driver_id
WHERE $where
ORDER BY c.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll();

$statusMeta = [
    0 => ['label' => 'Pending',     'color' => '#b45309', 'bg' => '#fef3c7', 'border' => '#fcd34d'],
    1 => ['label' => 'In-Progress', 'color' => '#1d4ed8', 'bg' => '#eff6ff', 'border' => '#93c5fd'],
    2 => ['label' => 'Resolved',    'color' => '#065f46', 'bg' => '#ecfdf5', 'border' => '#6ee7b7'],
];

// ── Include admin header (sidebar + navbar) ───────────────────────────────────
include '../components/navbar.php';
?>

<style>
:root {
  --cr-accent: #4f7cff;
  --cr-border: #e5e7eb;
  --cr-muted:  #6b7280;
  --cr-text:   #111827;
  --cr-bg:     #f3f4f6;
  --cr-white:  #ffffff;
  --cr-r:      10px;
  --cr-r-lg:   14px;
  --cr-trans:  .25s cubic-bezier(.4,0,.2,1);
}

.cm-wrap {
  padding: 28px 28px 80px;
  background: var(--cr-bg);
  min-height: 100vh;
  font-family: 'Segoe UI', sans-serif;
}

/* ── Page heading ── */
.cm-page-head {
  display: flex; align-items: flex-end; justify-content: space-between;
  margin-bottom: 22px;
}
.cm-page-head h1 {
  font-size: 1.5rem; font-weight: 800; color: var(--cr-text);
  display: flex; align-items: center; gap: 9px;
}
.cm-page-head h1 svg { color: var(--cr-accent); }
.cm-page-head p { font-size: .83rem; color: var(--cr-muted); margin-top: 3px; }
.cm-count-badge {
  background: var(--cr-accent); color: #fff;
  border-radius: 40px; padding: 6px 18px;
  font-size: .85rem; font-weight: 700;
}

/* ── Toolbar ── */
.cm-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
.cm-search-wrap { flex: 1; position: relative; }
.cm-search-wrap svg {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: var(--cr-muted); pointer-events: none;
}
.cm-search-wrap input {
  width: 100%; background: var(--cr-white);
  border: 1.5px solid var(--cr-border); border-radius: var(--cr-r);
  padding: 10px 16px 10px 38px;
  color: var(--cr-text); font-size: .9rem; outline: none;
  transition: border-color var(--cr-trans), box-shadow var(--cr-trans);
}
.cm-search-wrap input::placeholder { color: #9ca3af; }
.cm-search-wrap input:focus {
  border-color: var(--cr-accent);
  box-shadow: 0 0 0 3px rgba(79,124,255,.12);
}
.cm-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--cr-accent); color: #fff; border: none;
  border-radius: var(--cr-r); padding: 10px 20px;
  font-size: .88rem; font-weight: 600; cursor: pointer;
  white-space: nowrap; text-decoration: none;
  transition: background var(--cr-trans);
}
.cm-btn:hover { background: #3b6bff; }
.cm-btn-outline {
  background: var(--cr-white); color: var(--cr-muted);
  border: 1.5px solid var(--cr-border);
}
.cm-btn-outline:hover { background: #f9fafb; color: var(--cr-text); }

/* ── Cards ── */
.cm-cards { display: flex; flex-direction: column; gap: 13px; }

.cm-card {
  background: var(--cr-white);
  border: 1.5px solid var(--cr-border);
  border-radius: var(--cr-r-lg);
  overflow: hidden;
  transition: border-color var(--cr-trans), box-shadow var(--cr-trans);
  animation: cmFadeUp .4s both;
}
@keyframes cmFadeUp {
  from { opacity:0; transform:translateY(10px); }
  to   { opacity:1; transform:translateY(0); }
}
.cm-card:hover {
  border-color: rgba(79,124,255,.3);
  box-shadow: 0 3px 18px rgba(0,0,0,.07);
}

/* Summary row */
.cm-summary {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  cursor: pointer; user-select: none;
}

/* Avatar col — avatar + chip stacked, chip never clips outside card */
.cm-avatar-col {
  display: flex; flex-direction: column;
  align-items: center; gap: 5px;
  flex-shrink: 0;
}

/* Avatar circle */
.cm-avatar-block {
  width: 52px; height: 52px;
  border-radius: 50%; overflow: hidden; flex-shrink: 0;
  border: 2px solid var(--cr-border);
}
.cm-avatar {
  width: 100%; height: 100%;
  object-fit: cover; display: block;
}
.cm-avatar-fallback {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 1.1rem; color: #fff;
}

/* Type chip — sits BELOW avatar, fully inside card */
.cm-type-chip {
  font-size: .58rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; padding: 2px 8px; border-radius: 20px;
  white-space: nowrap;
}
.cm-type-chip.user   { background: #dbeafe; color: #1d4ed8; }
.cm-type-chip.driver { background: #dcfce7; color: #15803d; }
.cm-type-chip.guest  { background: #fee2e2; color: #b91c1c; }

/* Info */
.cm-info { min-width: 0; }
.cm-info-name {
  font-size: .97rem; font-weight: 700; color: var(--cr-text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 3px;
}
.cm-info-meta {
  display: flex; flex-wrap: wrap; gap: 10px;
  font-size: .77rem; color: var(--cr-muted);
}
.cm-info-meta span { display: flex; align-items: center; gap: 4px; }

/* Subject as a neat label */
.cm-subject-label {
  display: inline-block; margin-top: 7px;
  font-size: .74rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .05em; color: var(--cr-accent);
  background: rgba(79,124,255,.08);
  padding: 2px 10px; border-radius: 20px;
  max-width: 100%; white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis;
}

/* Right col */
.cm-right {
  display: flex; flex-direction: column;
  align-items: flex-end; gap: 7px; flex-shrink: 0;
}
.cm-status-pill {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 11px; border-radius: 20px;
  font-size: .7rem; font-weight: 700; letter-spacing: .04em;
  border: 1.5px solid currentColor;
}
.cm-status-pill::before {
  content:''; width:5px; height:5px; border-radius:50%;
  background:currentColor;
}
.cm-time { font-size: .7rem; color: var(--cr-muted); }
.cm-chevron {
  background: none; border: none; cursor: pointer;
  color: var(--cr-muted); padding: 2px;
  display: flex; align-items: center;
  transition: transform var(--cr-trans), color var(--cr-trans);
}
.cm-card.open .cm-chevron { transform: rotate(180deg); color: var(--cr-accent); }

/* Dropdown body */
.cm-body { max-height: 0; overflow: hidden; transition: max-height .4s cubic-bezier(.4,0,.2,1); }
.cm-card.open .cm-body { max-height: 480px; }

.cm-body-inner {
  border-top: 1.5px solid var(--cr-border);
  padding: 16px 20px; background: #f8faff;
  display: grid; gap: 13px;
}

.cm-sec-label {
  font-size: .67rem; text-transform: uppercase; letter-spacing: .1em;
  color: var(--cr-muted); margin-bottom: 5px; font-weight: 700;
}
.cm-msg-box {
  background: var(--cr-white); border: 1.5px solid var(--cr-border);
  border-radius: var(--cr-r); padding: 11px 13px;
  font-size: .88rem; line-height: 1.65; color: var(--cr-text);
}
.cm-attach-link {
  display: inline-flex; align-items: center; gap: 7px;
  background: #eff6ff; border: 1.5px solid #bfdbfe;
  border-radius: var(--cr-r); padding: 7px 13px;
  text-decoration: none; color: #1d4ed8;
  font-size: .82rem; font-weight: 600;
  transition: background var(--cr-trans);
}
.cm-attach-link:hover { background: #dbeafe; }

/* Status-tinted card backgrounds */
.cm-card[data-status="0"] { border-color: #fcd34d; background: #fef3c7; }
.cm-card[data-status="0"] .cm-summary { background: #fef3c7; }
.cm-card[data-status="1"] { border-color: #93c5fd; background: #eff6ff; }
.cm-card[data-status="1"] .cm-summary { background: #eff6ff; }
.cm-card[data-status="2"] { border-color: #6ee7b7; background: #ecfdf5; }
.cm-card[data-status="2"] .cm-summary { background: #ecfdf5; }

/* Hover still works on top */
.cm-card[data-status="0"]:hover { border-color: #f59e0b; box-shadow: 0 3px 18px rgba(245,158,11,.15); }
.cm-card[data-status="1"]:hover { border-color: #3b82f6; box-shadow: 0 3px 18px rgba(59,130,246,.15); }
.cm-card[data-status="2"]:hover { border-color: #10b981; box-shadow: 0 3px 18px rgba(16,185,129,.15); }

/* Status select — extra compact */
.cm-status-wrap { position: relative; display: inline-block; }
.cm-status-sel {
  appearance: none; background: var(--cr-white);
  border: 1.5px solid; border-radius: 6px;
  padding: 4px 24px 4px 8px;
  font-size: .72rem; font-weight: 700; cursor: pointer; outline: none;
  transition: box-shadow var(--cr-trans);
  line-height: 1.4;
}
.cm-status-sel:focus { box-shadow: 0 0 0 3px rgba(79,124,255,.15); }
.cm-status-wrap::after {
  content:'▾'; position: absolute; right:7px; top:50%; transform:translateY(-50%);
  pointer-events: none; font-size: .6rem;
}

/* Reply */
.cm-reply-area {
  width: 100%; background: var(--cr-white);
  border: 1.5px solid var(--cr-border); border-radius: var(--cr-r);
  padding: 9px 12px; color: var(--cr-text);
  font-size: .88rem; line-height: 1.6;
  resize: vertical; min-height: 78px; outline: none;
  transition: border-color var(--cr-trans), box-shadow var(--cr-trans);
}
.cm-reply-area:focus {
  border-color: var(--cr-accent);
  box-shadow: 0 0 0 3px rgba(79,124,255,.12);
}
.cm-reply-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--cr-accent); color: #fff; border: none;
  border-radius: var(--cr-r); padding: 8px 18px;
  font-size: .84rem; font-weight: 700; cursor: pointer; margin-top: 7px;
  transition: background var(--cr-trans), transform var(--cr-trans);
}
.cm-reply-btn:hover { background: #3b6bff; transform: translateY(-1px); }
.cm-reply-btn:active { transform: none; }
.cm-reply-btn.saved { background: #059669; }

/* Empty */
.cm-empty { text-align:center; padding:70px 0; color:var(--cr-muted); font-size:.92rem; }
.cm-empty svg { opacity:.18; margin-bottom:14px; }

/* Toast */
.cm-toast {
  position: fixed; bottom:22px; right:22px; z-index:9999;
  background: var(--cr-white); border: 1.5px solid var(--cr-border);
  border-left: 4px solid var(--cr-accent); border-radius: 10px;
  padding: 11px 20px; font-size:.85rem; color: var(--cr-text);
  box-shadow: 0 6px 28px rgba(0,0,0,.1);
  transform: translateY(60px); opacity:0;
  transition: transform .3s cubic-bezier(.4,0,.2,1), opacity .3s;
  pointer-events: none;
}
.cm-toast.show { transform: translateY(0); opacity:1; }
</style>

<div class="cm-wrap">

  <!-- Heading -->
   <br><br><br>
  <div class="cm-page-head">
    <div>
      <h1>
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        Support Requests
      </h1>
      <p>Manage contact &amp; help messages from users, drivers and guests</p>
    </div>
    <div class="cm-count-badge"><?= count($contacts) ?> Requests</div>
  </div>

  <!-- Toolbar -->
  <form class="cm-toolbar" method="GET" action="">
    <div class="cm-search-wrap">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="Search name, email, mobile, subject…" autocomplete="off">
    </div>
    <button type="submit" class="cm-btn">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      Search
    </button>
    <?php if($search): ?>
      <a href="contact_master.php" class="cm-btn cm-btn-outline">✕ Clear</a>
    <?php endif; ?>
  </form>

  <!-- Cards -->
  <div class="cm-cards">

  <?php if(empty($contacts)): ?>
    <div class="cm-empty">
      <svg width="58" height="58" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <p>No support requests found.</p>
    </div>
  <?php endif; ?>

  <?php
  $avatarColors = ['#4f7cff','#f59e0b','#10b981','#ef4444','#8b5cf6','#ec4899','#0ea5e9','#f97316'];

  foreach($contacts as $i => $c):
    $sid    = $c['contact_id'];
    $stype  = $c['sender_type'];
    $status = (int)$c['status'];
    $sm     = $statusMeta[$status];

    if ($stype === 'user') {
        $dname = $c['user_name']    ?: $c['sender_name'];
        $photo = $c['user_photo']   ?: null;
    } elseif ($stype === 'driver') {
        $dname = $c['driver_name']  ?: $c['sender_name'];
        $photo = $c['driver_photo'] ?: null;
    } else {
        $dname = $c['sender_name'];
        $photo = null;
    }

    $initials  = strtoupper(substr($dname, 0, 1));
    $created   = date('d M Y, h:i A', strtotime($c['created_at']));
    $delay     = ($i < 15) ? round($i * 0.04, 2) . 's' : '0s';
    $avatarBg  = $avatarColors[$i % count($avatarColors)];
    // Build photo src — onerror fallback handles wrong path gracefully
    if ($photo && $stype === 'driver') {
        $photoSrc = '../../Driver/images/driver_profile/' . htmlspecialchars($photo);
    } elseif ($photo && $stype === 'user') {
        $photoSrc = '../../User/user_profile/' . htmlspecialchars($photo);
    } elseif ($photo) {
        $photoSrc = '../uploads/' . htmlspecialchars($photo);
    } else {
        $photoSrc = null;
    }
    // Fallback: also try root uploads if subfolder fails (handled via onerror in JS)
    $photoSrcFallback = $photo ? '../uploads/' . htmlspecialchars($photo) : null;

    // Use status from statusMeta; if status not in array (e.g. old "closed"), default to pending
    if (!isset($statusMeta[$status])) { $status = 0; $sm = $statusMeta[0]; }
  ?>

  <div class="cm-card" id="card-<?= $sid ?>" data-status="<?= $status ?>" style="animation-delay:<?= $delay ?>">

    <div class="cm-summary" onclick="toggleCard(<?= $sid ?>)">

      <!-- Avatar + type chip stacked -->
      <div class="cm-avatar-col">
        <div class="cm-avatar-block">
          <?php if($photoSrc): ?>
            <img class="cm-avatar" src="<?= $photoSrc ?>"
                 onerror="if(this.dataset.tried!='1'){this.dataset.tried='1';this.src='<?= $photoSrcFallback ?>';}else{this.style.display='none';document.getElementById('fb-<?= $sid ?>').style.display='flex';}"
                 alt="<?= htmlspecialchars($dname) ?>">
            <div class="cm-avatar-fallback" id="fb-<?= $sid ?>"
                 style="display:none; background:<?= $avatarBg ?>;"><?= $initials ?></div>
          <?php else: ?>
            <div class="cm-avatar-fallback" style="background:<?= $avatarBg ?>;"><?= $initials ?></div>
          <?php endif; ?>
        </div>
        <div class="cm-type-chip <?= $stype ?>"><?= ucfirst($stype) ?></div>
      </div>

      <!-- Info -->
      <div class="cm-info">
        <div class="cm-info-name"><?= htmlspecialchars($dname) ?></div>
        <div class="cm-info-meta">
          <?php if($c['sender_email']): ?>
          <span>
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>
            <?= htmlspecialchars($c['sender_email']) ?>
          </span>
          <?php endif; ?>
          <?php if($c['sender_mobile']): ?>
          <span>
            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
            <?= htmlspecialchars($c['sender_mobile']) ?>
          </span>
          <?php endif; ?>
        </div>
        <div class="cm-subject-label">📌 <?= htmlspecialchars($c['subject']) ?></div>
      </div>

      <!-- Right -->
      <div class="cm-right">
        <span class="cm-status-pill" id="pill-<?= $sid ?>"
              style="color:<?= $sm['color'] ?>"><?= $sm['label'] ?></span>
        <span class="cm-time"><?= $created ?></span>
        <button class="cm-chevron" aria-label="expand"
                onclick="event.stopPropagation(); toggleCard(<?= $sid ?>)">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Dropdown -->
    <div class="cm-body">
      <div class="cm-body-inner">

        <!-- Message -->
        <div>
          <div class="cm-sec-label">Message</div>
          <div class="cm-msg-box"><?= nl2br(htmlspecialchars($c['message'])) ?></div>
        </div>

        <?php if(!empty($c['attachment'])): ?>
        <div>
          <div class="cm-sec-label">Attachment</div>
          <a class="cm-attach-link" href="../uploads/<?= htmlspecialchars($c['attachment']) ?>" target="_blank">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
            </svg>
            View Attachment
          </a>
        </div>
        <?php endif; ?>

        <!-- Status change (compact) -->
        <div>
          <div class="cm-sec-label">Update Status</div>
          <div class="cm-status-wrap">
            <select class="cm-status-sel" id="status-sel-<?= $sid ?>"
                    style="color:<?= $sm['color'] ?>; border-color:<?= $sm['color'] ?>"
                    onchange="changeStatus(<?= $sid ?>, this)">
              <?php foreach($statusMeta as $val => $meta): ?>
              <option value="<?= $val ?>" <?= $val===$status?'selected':'' ?>><?= $meta['label'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Admin reply -->
        <div>
          <div class="cm-sec-label">Admin Reply</div>
          <textarea class="cm-reply-area" id="reply-<?= $sid ?>"
                    placeholder="Type your reply to this request…"><?= htmlspecialchars($c['admin_reply'] ?? '') ?></textarea>
          <button class="cm-reply-btn" id="reply-btn-<?= $sid ?>"
                  onclick="saveReply(<?= $sid ?>)">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Send Reply
          </button>
        </div>

      </div>
    </div>

  </div>
  <?php endforeach; ?>

  </div><!-- /cm-cards -->
</div><!-- /cm-wrap -->

<div class="cm-toast" id="cm-toast"></div>

<script>
const cmStatusColors = { 0:'#b45309', 1:'#1d4ed8', 2:'#065f46' };
const cmStatusLabels = { 0:'Pending', 1:'In-Progress', 2:'Resolved' };

function toggleCard(id) {
  document.getElementById('card-' + id).classList.toggle('open');
}

function cmToast(msg, color) {
  const t = document.getElementById('cm-toast');
  t.textContent = msg;
  t.style.borderLeftColor = color || '#4f7cff';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

function changeStatus(id, sel) {
  const val   = parseInt(sel.value);
  const color = cmStatusColors[val];
  sel.style.color       = color;
  sel.style.borderColor = color;
  const pill = document.getElementById('pill-' + id);
  if (pill) { pill.textContent = cmStatusLabels[val]; pill.style.color = color; }
  // Update card tint
  const card = document.getElementById('card-' + id);
  if (card) card.dataset.status = val;
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=update_status&contact_id=${id}&status=${val}`
  }).then(r => r.json()).then(d => {
    if (d.ok) cmToast('✓ Status updated to ' + cmStatusLabels[val], color);
    else      cmToast('⚠ Failed to update status', '#ef4444');
  });
}

function saveReply(id) {
  const reply = document.getElementById('reply-' + id).value.trim();
  const btn   = document.getElementById('reply-btn-' + id);
  btn.disabled = true; btn.textContent = 'Saving…';
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=save_reply&contact_id=${id}&reply=${encodeURIComponent(reply)}`
  }).then(r => r.json()).then(d => {
    if (d.ok) {
      btn.textContent = '✓ Saved!'; btn.classList.add('saved');
      cmToast('✓ Reply saved successfully', '#059669');
      setTimeout(() => {
        btn.disabled = false; btn.classList.remove('saved');
        btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Reply`;
      }, 2500);
    } else {
      btn.textContent = '⚠ Error'; btn.disabled = false;
      cmToast('⚠ Failed to save reply', '#ef4444');
    }
  });
}
</script>