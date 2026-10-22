<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$role    = $officer['role'] ?? 'secretary';

$composed = false;
$updated  = false;
$error    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? 'set_renewal';
    $title    = trim($_POST['title'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $opened   = $_POST['opened'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    if ($action === 'update_renewal') {
        $announcementId = $_POST['announcement_id'] ?? '';
        if ($title !== '' && $message !== '' && $opened !== '' && $deadline !== '' && $announcementId !== '') {
            $updated = db_update_announcement($announcementId, [
                'title'    => $title,
                'message'  => $message,
                'opened'   => $opened,
                'deadline' => $deadline,
            ]);
            $error = $updated ? null : 'Could not update — this renewal cycle may have changed since the page loaded. Please refresh and try again.';
        } else {
            $error = 'Please fill in all fields before updating.';
        }
    } elseif ($title !== '' && $message !== '' && $opened !== '' && $deadline !== '') {

        $conflict = db_check_announcement_overlap($opened, $deadline);

        if ($conflict) {
            $error = "That start date overlaps with \"{$conflict['title']}\", which is still open until {$conflict['deadline']}. "
                   . "Pick a start date on or after that, or leave the current renewal's own deadline to end it first.";
        } else {
            db_add_announcement([
                'title'    => $title,
                'message'  => $message,
                'opened'   => $opened,
                'deadline' => $deadline,
            ]);
            $composed = true;
        }
    } else {
        $error = 'Please fill in all fields before publishing.';
    }
}

$announcementRows = d('announcements');
$hasActiveCycle = isset($announcementRows[0])
    && in_array($announcementRows[0]['display_status'], ['OPEN', 'UPCOMING'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Renewals · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.ann-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 16px;
}
.ann-page-title { font-size: 28px; font-weight: 800; color: var(--navy); line-height: 1.1; }
.ann-page-sub   { font-size: 13px; color: var(--text-muted); margin-top: 5px; font-weight: 400; }

.ann-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.ann-card__title { padding:18px 20px 14px; font-size:20px; font-weight:700; color:var(--navy); border-bottom:1px solid #e5e7eb; }

.ann-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.ann-table thead th { text-align:left; font-size:12.5px; font-weight:600; color:#374151; padding:12px 20px; border-bottom:1px solid #e5e7eb; background:#fff; }
.ann-table tbody td { padding:20px 20px; border-bottom:1px solid #f0f0f0; vertical-align:top; }
.ann-table tbody tr:last-child td { border-bottom:none; }
.ann-table tbody tr:hover { background:#fafafa; }

.ann-year-title { font-size:13.5px; font-weight:800; color:#111827; line-height:1.3; margin-bottom:7px; text-transform:uppercase; }
.ann-badge-open   { display:inline-block; background:#16a34a; color:#fff; font-size:10.5px; font-weight:700; padding:2px 10px; border-radius:999px; letter-spacing:.3px; }
.ann-badge-upcoming { display:inline-block; background:#fff; color:#c2670c; font-size:10.5px; font-weight:700; padding:2px 10px; border-radius:999px; border:1px solid #c2670c; letter-spacing:.3px; }
.ann-badge-closed { display:inline-block; background:#fff; color:#6b7280; font-size:10.5px; font-weight:600; padding:2px 10px; border-radius:999px; border:1px solid #d1d5db; letter-spacing:.3px; }

.ann-msg  { font-size:13px; color:#6b7280; line-height:1.6; }
.ann-date { font-size:13px; color:#374151; white-space:nowrap; }

.btn-set-renewal {
    display:inline-flex; align-items:center; gap:7px;
    background:var(--navy); color:#fff; border:none; border-radius:8px;
    padding:10px 18px; font-size:13.5px; font-weight:700; cursor:pointer;
    white-space:nowrap; flex-shrink:0; text-decoration:none; transition:background .15s;
}
.btn-set-renewal:hover { background:var(--navy-light); }
.btn-set-renewal:disabled { background:#c8ccd4; cursor:not-allowed; }
.ann-header-right { display:flex; flex-direction:column; align-items:flex-end; gap:6px; flex-shrink:0; }
.ann-active-note {
    font-size:12px; color:var(--text-muted); text-align:right; line-height:1.4; max-width:320px;
}

.ann-edit-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:6px; border:1px solid #e5e7eb;
    background:#fff; color:#6b7280; cursor:pointer; transition:border-color .15s, color .15s;
}
.ann-edit-btn:hover { border-color:var(--navy); color:var(--navy); }

.renewal-overlay {
    position:fixed; inset:0; background:rgba(22,35,61,.5);
    display:none; align-items:center; justify-content:center; z-index:100; padding:20px;
}
.renewal-overlay.is-open { display:flex; }
.renewal-modal {
    background:#fff; border-radius:12px; width:100%; max-width:500px;
    box-shadow:0 24px 64px rgba(0,0,0,.25); overflow:hidden; animation:modalIn .18s ease;
}
@keyframes modalIn {
    from { opacity:0; transform:scale(.96) translateY(8px); }
    to   { opacity:1; transform:scale(1)   translateY(0);   }
}
.renewal-modal__head {
    display:flex; align-items:flex-start; justify-content:space-between;
    padding:20px 24px 16px; border-bottom:1px solid #e5e7eb;
}
.renewal-modal__head-title { font-size:20px; font-weight:700; color:var(--navy); margin-bottom:3px; }
.renewal-modal__head-sub   { font-size:15px; color:#6b7280; font-weight:400; }
.renewal-modal__close {
    background:none; border:none; cursor:pointer; color:#9ca3af;
    display:flex; align-items:center; padding:4px; border-radius:6px;
    transition:color .15s, background .15s; flex-shrink:0; margin-top:2px;
}
.renewal-modal__close:hover { color:var(--navy); background:#f3f4f6; }

.renewal-modal__body { padding:20px 24px 4px; }

.rm-field { margin-bottom:18px; }
.rm-label {
    display:block; font-size:11px; font-weight:800;
    text-transform:uppercase; letter-spacing:.6px; color:#374151; margin-bottom:7px;
}
.rm-input {
    width:100%; padding:11px 14px; border:1px solid #d1d5db; border-radius:8px;
    background:#f9fafb; font-size:14px; color:#111827; font-family:inherit;
    transition:border-color .15s, box-shadow .15s, background .15s;
}
.rm-input:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); background:#fff; }
.rm-textarea {
    width:100%; padding:11px 14px; border:1px solid #d1d5db; border-radius:8px;
    background:#f9fafb; font-size:14px; color:#111827; font-family:inherit;
    min-height:96px; resize:vertical; transition:border-color .15s, box-shadow .15s, background .15s;
}
.rm-textarea:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); background:#fff; }

.rm-duration-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.rm-duration-col { display:flex; flex-direction:column; gap:6px; }
.rm-duration-sublabel { font-size:11px; font-weight:600; color:#9ca3af; letter-spacing:.3px; }

.rm-date-wrap { position:relative; display:flex; align-items:center; }
.rm-date-wrap input[type="date"] {
    width:100%;
    padding:11px 38px 11px 14px;   
    border:1px solid #d1d5db; border-radius:8px;
    background:#f9fafb; font-size:14px; color:#111827; font-family:inherit;
    transition:border-color .15s, box-shadow .15s, background .15s;
    appearance:none; -webkit-appearance:none;
    -webkit-calendar-picker-indicator-display:none;
}
.rm-date-wrap input[type="date"]::-webkit-calendar-picker-indicator {
    opacity:0; position:absolute; right:10px; width:20px; height:20px; cursor:pointer; z-index:2;
}
.rm-date-wrap input[type="date"]:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); background:#fff; }
.rm-date-wrap__icon {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    pointer-events:none; color:#9ca3af; display:flex; align-items:center; z-index:1;
}

.renewal-modal__foot { padding:16px 24px 24px; }
.btn-open-renewal {
    width:100%; background:var(--navy); color:#fff; border:none; border-radius:8px;
    padding:14px; font-size:15px; font-weight:700; cursor:pointer;
    font-family:inherit; transition:background .15s;
}
.btn-open-renewal:hover { background:var(--navy-light); }
</style>
</head>
<body>
<?php render_sidebar('announcements'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Renewals', 'Compose and broadcast notices'); ?>

  <div class="page">

    <?php if ($composed): ?>
      <div class="card" style="border-color:var(--green); background:var(--green-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--green); font-weight:600;">
          Renewal announcement published successfully.
        </div>
      </div>
    <?php elseif ($updated): ?>
      <div class="card" style="border-color:var(--green); background:var(--green-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--green); font-weight:600;">
          Renewal dates updated successfully.
        </div>
      </div>
    <?php elseif ($error): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;"><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="ann-page-header">
      <div>
        <div class="ann-page-title">Renewal</div>
        <div class="ann-page-sub">Compose and broadcast notices</div>
      </div>
      <div class="ann-header-right">
        <?php if ($hasActiveCycle): ?>
          <button type="button" class="btn-set-renewal" id="openRenewalBtn" disabled
                  title="A renewal cycle is already open — use the edit icon on it below instead.">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            Set Renewal
          </button>
          <p class="ann-active-note">
            "<?= e($announcementRows[0]['title']) ?>" is open until <?= e($announcementRows[0]['deadline']) ?> &mdash; edit it below.
          </p>
        <?php else: ?>
          <button type="button" class="btn-set-renewal" id="openRenewalBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <path d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            Set Renewal
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="ann-card">
      <div class="ann-card__title">Renewal History</div>
      <table class="ann-table">
        <thead>
          <tr>
            <th style="width:200px;">Year</th>
            <th>Message</th>
            <th style="width:155px;">Date Opened</th>
            <th style="width:155px;">Deadline</th>
            <th style="width:44px;"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($announcementRows as $i => $a): ?>
            <tr>
              <td>
                <div class="ann-year-title"><?= e($a['title']) ?></div>
                <?php if ($a['display_status'] === 'OPEN'): ?>
                  <span class="ann-badge-open">OPEN</span>
                <?php elseif ($a['display_status'] === 'UPCOMING'): ?>
                  <span class="ann-badge-upcoming">UPCOMING</span>
                <?php else: ?>
                  <span class="ann-badge-closed">CLOSED</span>
                <?php endif; ?>
              </td>
              <td><div class="ann-msg"><?= e($a['message']) ?></div></td>
              <td><div class="ann-date"><?= e($a['opened']) ?></div></td>
              <td>
                <div class="ann-date"><?= e($a['deadline']) ?></div>
              </td>
              <td style="width:44px;">
                <?php if ($i === 0): ?>
                  <button type="button" class="ann-edit-btn" title="Update renewal dates"
                    data-announcement-id="<?= e($a['announcement_id']) ?>"
                    data-title="<?= e($a['title']) ?>"
                    data-message="<?= e($a['message']) ?>"
                    data-opened="<?= e($a['opened_raw']) ?>"
                    data-deadline="<?= e($a['deadline_raw']) ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<div class="renewal-overlay" id="renewalOverlay">
  <div class="renewal-modal" role="dialog" aria-modal="true" aria-labelledby="renewalModalTitle">

    <div class="renewal-modal__head">
      <div>
        <div class="renewal-modal__head-title" id="renewalModalTitle">Set Renewal</div>
        <div class="renewal-modal__head-sub" id="renewalModalSub">Set permit renewal application for the drivers</div>
      </div>
      <button type="button" class="renewal-modal__close" id="closeRenewalBtn" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <form method="post" action="<?= BASE_URL ?>/announcements.php" id="renewalForm">
      <input type="hidden" name="action" id="rn_action" value="set_renewal">
      <input type="hidden" name="announcement_id" id="rn_announcement_id" value="">
      <div class="renewal-modal__body">

        <div class="rm-field">
          <label class="rm-label" for="rn_title">Title</label>
          <input class="rm-input" type="text" id="rn_title" name="title" placeholder="Enter Title" required>
        </div>

        <div class="rm-field">
          <label class="rm-label" for="rn_message">Message</label>
          <textarea class="rm-textarea" id="rn_message" name="message" placeholder="Write your announcement..." required></textarea>
        </div>

        <div class="rm-field" style="margin-bottom:0;">
          <label class="rm-label">Duration</label>
          <div class="rm-duration-row">

            <div class="rm-duration-col">
              <span class="rm-duration-sublabel">Start</span>
              <div class="rm-date-wrap">
                <input type="date" name="opened" id="rn_opened" required>
                <span class="rm-date-wrap__icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                  </svg>
                </span>
              </div>
            </div>

            <div class="rm-duration-col">
              <span class="rm-duration-sublabel">End</span>
              <div class="rm-date-wrap">
                <input type="date" name="deadline" id="rn_deadline" required>
                <span class="rm-date-wrap__icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                  </svg>
                </span>
              </div>
            </div>

          </div>
        </div>

        <p id="renewalUpdateNote" class="text-muted" style="font-size:12.5px; margin-top:14px; display:none;">
          Drivers who already submitted a renewal application this cycle won't be able to upload again — this only adjusts the window for those who haven't applied yet (or were rejected).
        </p>

      </div>

      <div class="renewal-modal__foot">
        <button type="submit" class="btn-open-renewal" id="renewalSubmitBtn">Open Renewal</button>
      </div>
    </form>

  </div>
</div>

<script>
(function () {
  var overlay      = document.getElementById('renewalOverlay');
  var openBtn      = document.getElementById('openRenewalBtn');
  var closeBtn     = document.getElementById('closeRenewalBtn');
  var editBtns     = document.querySelectorAll('.ann-edit-btn');
  var modalTitle   = document.getElementById('renewalModalTitle');
  var modalSub     = document.getElementById('renewalModalSub');
  var form         = document.getElementById('renewalForm');
  var actionField  = document.getElementById('rn_action');
  var idField      = document.getElementById('rn_announcement_id');
  var titleField   = document.getElementById('rn_title');
  var messageField = document.getElementById('rn_message');
  var openedField  = document.getElementById('rn_opened');
  var deadlineField= document.getElementById('rn_deadline');
  var submitBtn    = document.getElementById('renewalSubmitBtn');
  var updateNote   = document.getElementById('renewalUpdateNote');

  function openOverlay()  { overlay.classList.add('is-open');    document.body.style.overflow = 'hidden'; }
  function closeOverlay() { overlay.classList.remove('is-open'); document.body.style.overflow = '';       }

  function openForCreate() {
    form.reset();
    actionField.value = 'set_renewal';
    idField.value = '';
    modalTitle.textContent = 'Set Renewal';
    modalSub.textContent = 'Set permit renewal application for the drivers';
    submitBtn.textContent = 'Open Renewal';
    updateNote.style.display = 'none';
    openOverlay();
  }

  function openForUpdate(btn) {
    actionField.value = 'update_renewal';
    idField.value = btn.getAttribute('data-announcement-id') || '';
    titleField.value = btn.getAttribute('data-title') || '';
    messageField.value = btn.getAttribute('data-message') || '';
    openedField.value = btn.getAttribute('data-opened') || '';
    deadlineField.value = btn.getAttribute('data-deadline') || '';
    modalTitle.textContent = 'Update Renewal';
    modalSub.textContent = 'Adjust the dates for the current renewal cycle';
    submitBtn.textContent = 'Update Renewal';
    updateNote.style.display = 'block';
    openOverlay();
  }

  if (openBtn) openBtn.addEventListener('click', openForCreate);
  editBtns.forEach(function (btn) {
    btn.addEventListener('click', function () { openForUpdate(btn); });
  });
  if (closeBtn) closeBtn.addEventListener('click', closeOverlay);

  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeOverlay();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeOverlay();
  });
})();
</script>

<?php render_shared_script(); ?>
</body>
</html>