<?php

require_once __DIR__ . '/config.php';
require_role('secretary');
require_once __DIR__ . '/admin-documents.php';

$pending = get_pending_applications();

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10; // Items per page
$paginatedPending = paginate_array($pending, $page, $perPage);

$totalApplications = count_applications();
$barangays_list = array_keys(d('barangays'));
sort($barangays_list);

$viewId = $_GET['id'] ?? null;
$current = $viewId ? get_application_with_docs($viewId) : null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_doc'], $_POST['app_no'])) {
    mark_document_verified($_POST['app_no'], $_POST['verify_doc']);
    echo 'ok';
    exit;
}


if (isset($_GET['forward']) && $_GET['forward'] !== '') {
    $moved = forward_to_approval($_GET['forward']);
    header('Location: ' . BASE_URL . '/validation_tools.php?' . ($moved ? 'forwarded=1' : 'forward_failed=1'));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_app_no'])) {
    $rejected = reject_application($_POST['reject_app_no'], trim($_POST['reason'] ?? ''), trim($_POST['note'] ?? ''));
    header('Location: ' . BASE_URL . '/validation_tools.php?' . ($rejected ? 'rejected=1' : 'reject_failed=1'));
    exit;
}

$forwarded = isset($_GET['forwarded']);
$forwardFailed = isset($_GET['forward_failed']);
$rejected = isset($_GET['rejected']);
$rejectFailed = isset($_GET['reject_failed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validation Tools · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.page-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; }
.page-title { font-size:26px; font-weight:800; color:var(--navy); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filter-bar { display:flex; align-items:center; gap:12px; margin-bottom:0; padding:16px 20px; border-bottom:1px solid var(--border); }
.filter-bar input[type="search"] { flex:1; padding:10px 14px 10px 36px; border:1px solid var(--border); border-radius:8px; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239aa1b4' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; font-size:14px; color:var(--text); }
.filter-bar input[type="search"]:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }
.filter-bar select { padding:10px 36px 10px 14px; border:1px solid var(--border); border-radius:8px; background:var(--navy); color:#fff; font-size:13.5px; font-weight:600; appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-color:var(--navy); cursor:pointer; }
.val-table { width:100%; border-collapse:collapse; font-size:13.6px; }
.val-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
.val-table td { padding:14px 16px; border-bottom:1px solid var(--border); color:var(--text); }
.val-table tbody tr:hover { background:var(--bg); cursor:pointer; }
.val-table td.id-col { color:var(--text-muted); font-size:12.5px; }
.count-label { font-size:13px; color:var(--text-muted); }

.review-card__head { padding:22px 24px; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.review-card__eyebrow { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:4px; }
.review-card__appno { font-size:22px; font-weight:800; color:var(--navy); margin-bottom:6px; }
.review-card__date { font-size:13.5px; color:var(--text-muted); }

.review-section { padding:20px 24px; border-top:1px solid var(--border); }
.review-section h4 { font-size:16px; font-weight:800; color:var(--text); margin-bottom:16px; }
.read-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px 20px; }
.read-field label { display:block; font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:6px; }
.read-field__box { background:#f4f5f7; border:1px solid var(--border); border-radius:8px; padding:10px 12px; font-size:14px; color:var(--text); min-height:20px; }

.doc-grid-v2 { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; margin-top:4px; }
.doc-tile-v2 { display:flex; align-items:center; gap:12px; border:1px solid var(--border); border-radius:10px; padding:14px 16px; background:#fff; }
.doc-tile-v2__label { flex:1; font-size:13.8px; font-weight:700; color:var(--text); }
.doc-tile-v2__expand { width:26px; height:26px; border-radius:6px; border:1px solid var(--border); background:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--text-muted); cursor:pointer; transition:border-color .15s ease, color .15s ease; }
.doc-tile-v2__expand:hover { border-color:var(--navy); color:var(--navy); }
.doc-tile-v2__expand svg { width:13px; height:13px; }
.doc-tile-v2 input[type="checkbox"] { width:19px; height:19px; accent-color:var(--navy); cursor:pointer; flex-shrink:0; }

.doc-preview-modal .modal { max-width:620px; }
.doc-preview-modal .modal__body { padding:4px 24px 4px; }

.doc-preview__wrap { position:relative; }
.doc-preview__frame { background:var(--gray-bg); border:1px solid var(--border); border-radius:10px; height:68vh; overflow:auto; scrollbar-width:none; -ms-overflow-style:none; }
.doc-preview__frame::-webkit-scrollbar { display:none; }

.doc-preview__content { display:flex; align-items:safe center; justify-content:safe center; height:100%; width:100%; }
.doc-preview__frame.is-empty .doc-preview__content { align-items:center; }
.doc-preview__frame img { max-width:100%; max-height:100%; width:auto; height:auto; display:block; transition:width .12s ease, height .12s ease; }

.doc-preview__frame img.is-zoomed { max-width:none; max-height:none; }
.doc-preview__frame iframe { width:100%; height:100%; border:0; display:block; transition:width .12s ease, height .12s ease; }
.doc-preview__frame.is-zoomed-in { cursor:grab; }
.doc-preview__frame.is-dragging { cursor:grabbing; user-select:none; }
.doc-preview__placeholder { text-align:center; color:var(--text-muted); padding:30px; }
.doc-preview__placeholder svg { width:44px; height:44px; margin-bottom:10px; color:#c3c8d1; }
.doc-preview__placeholder p { font-size:13px; }


.doc-preview__zoom {
    position:absolute; top:12px; right:12px; z-index:5;
    display:flex; flex-direction:column; align-items:center; gap:8px;
}
.doc-preview__zoom-group {
    display:flex; flex-direction:column;
    background:#fff; border-radius:8px;
    box-shadow:0 2px 6px rgba(0,0,0,.25);
    overflow:hidden;
}
.doc-preview__zoom-group button {
    width:32px; height:32px; border:none; background:#fff;
    color:var(--navy); font-size:17px; font-weight:700; cursor:pointer; line-height:1;
    display:flex; align-items:center; justify-content:center; transition:background .12s;
}
.doc-preview__zoom-group button:first-child { border-bottom:1px solid var(--border); }
.doc-preview__zoom-group button:hover:not(:disabled) { background:var(--gray-bg); }
.doc-preview__zoom-group button:disabled { opacity:.35; cursor:default; }
.doc-preview__zoom__level {
    background:#fff; border-radius:999px; padding:5px 11px;
    box-shadow:0 2px 6px rgba(0,0,0,.25);
    color:var(--navy); font-size:11px; font-weight:700; white-space:nowrap;
}
.doc-preview__zoom-reset {
    background:#fff; border:none; border-radius:999px; padding:5px 11px;
    box-shadow:0 2px 6px rgba(0,0,0,.25);
    color:var(--navy); font-size:11px; font-weight:700; cursor:pointer;
}
.doc-preview__zoom-reset:hover { background:var(--gray-bg); }

.action-row-2col { display:grid; grid-template-columns:1fr 1fr; gap:14px; padding:22px 24px 24px; border-top:1px solid var(--border); }
.action-row-2col .btn { padding:14px 18px; font-size:14.5px; }
</style>
</head>
<body>
<?php render_sidebar('validation'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Validation Tool', 'Verify submitted documents before forwarding for approval'); ?>

  <div class="page">

    <?php if ($forwarded): ?>
      <div class="card" style="border-color:var(--green); background:var(--green-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--green); font-weight:600;">
          Application forwarded to the President for approval.
        </div>
      </div>
    <?php elseif ($forwardFailed): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;">
          Couldn't forward that application — make sure every uploaded document is ticked verified first.
        </div>
      </div>
    <?php elseif ($rejected): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;">
          Application rejected. The applicant will need to resubmit before it can be reviewed again.
        </div>
      </div>
    <?php elseif ($rejectFailed): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;">
          Couldn't reject that application — a reason is required.
        </div>
      </div>
    <?php endif; ?>

    <?php if ($current): ?>

      <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/validation_tools.php" style="margin-bottom:16px; display:inline-flex;">&larr; Back to queue</a>

      <div class="card">
        <div class="review-card__head">
          <div>
            <div class="review-card__eyebrow">Reviewing Application</div>
            <div class="review-card__appno"><?= e($current['app_no']) ?></div>
            <div class="review-card__date"><?= e($current['submitted']) ?></div>
          </div>
          <span class="badge <?= badge_class_for_status($current['status']) ?>"><?= e($current['status']) ?></span>
        </div>

        <div class="review-section">
          <h4>Driver's Profile</h4>
          <div class="read-grid">
            <div class="read-field"><label>Driver ID</label><div class="read-field__box"><?= e($current['driver_id']) ?></div></div>
            <div class="read-field"><label>Name</label><div class="read-field__box"><?= e($current['name']) ?></div></div>
            <div class="read-field"><label>Age</label><div class="read-field__box"><?= e($current['age']) ?></div></div>
            <div class="read-field"><label>Birthday</label><div class="read-field__box"><?= e($current['birthday']) ?></div></div>
            <div class="read-field"><label>Contact No.</label><div class="read-field__box"><?= e($current['contact']) ?></div></div>
            <div class="read-field"><label>Address</label><div class="read-field__box"><?= e($current['address']) ?></div></div>
          </div>
        </div>

        <div class="review-section">
          <h4>Vehicle's Information</h4>
          <div class="read-grid">
            <div class="read-field"><label>Body No.</label><div class="read-field__box"><?= e($current['body_no']) ?></div></div>
            <div class="read-field"><label>Barangay</label><div class="read-field__box"><?= e($current['barangay']) ?></div></div>
            <div class="read-field"><label>Color Code</label><div class="read-field__box"><?= e($current['color']) ?></div></div>
          </div>
        </div>

        <div class="review-section">
          <div class="doc-grid-v2">
            <?php
            $docLabels = ['license' => "Driver's License", 'or_cr' => 'OR/CR', 'photo' => '1x1 Picture', 'receipt' => 'Proof of Payment'];
            foreach ($docLabels as $key => $label):
              $doc = $current['docs'][$key] ?? null;
              $hasFile = $doc && !empty($doc['file_path']);
              $isVerified = $doc['is_verified'] ?? false;
              $previewUrl = $hasFile ? (BASE_URL . '/doc-file.php?app=' . urlencode($current['app_no']) . '&doc=' . urlencode($key)) : '';
           
              $docExt = $hasFile ? strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) : '';
            ?>
              <div class="doc-tile-v2">
                <span class="doc-tile-v2__label"><?= e($label) ?><?php if (!$hasFile): ?> <span style="color:var(--text-muted); font-weight:500; font-size:12px;">(not uploaded)</span><?php endif; ?></span>
                <span class="doc-tile-v2__expand" data-open-doc-preview="<?= e($label) ?>" data-doc-src="<?= e($previewUrl) ?>" data-doc-type="<?= $docExt === 'pdf' ? 'pdf' : 'image' ?>" title="View document">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                </span>
                <label style="display:flex; align-items:center;">
                  <input type="checkbox" class="doc-verify-check" data-app="<?= e($current['app_no']) ?>" data-doc="<?= e($key) ?>" <?= $isVerified ? 'checked' : '' ?> <?= $hasFile ? '' : 'disabled title="No file uploaded yet"' ?>>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="action-row-2col">
          <button type="button" class="btn btn-danger btn-block" data-open-modal="return-modal">Reject</button>
          <button type="button" class="btn btn-primary btn-block" id="forwardBtn" data-open-modal="forward-modal" disabled>Pass to Approval</button>
        </div>
      </div>

      <div class="modal-backdrop doc-preview-modal" id="doc-preview-modal">
        <div class="modal">
          <div class="modal__head"><h3 id="docPreviewTitle">Document</h3></div>
          <div class="modal__body">
            <div class="doc-preview__wrap" id="docPreviewWrap">
              <div class="doc-preview__frame is-empty" id="docPreviewFrame">
                <div class="doc-preview__content" id="docPreviewContent">
                  <div class="doc-preview__placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M14 10l7-7"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    <p>No document uploaded for this field yet.</p>
                  </div>
                </div>
              </div>
              <div class="doc-preview__zoom" id="docPreviewZoom" style="display:none;">
                <div class="doc-preview__zoom-group">
                  <button type="button" id="docZoomIn" title="Zoom in">&plus;</button>
                  <button type="button" id="docZoomOut" title="Zoom out">&minus;</button>
                </div>
                <span class="doc-preview__zoom__level" id="docZoomLevel">100%</span>
                <button type="button" id="docZoomReset" class="doc-preview__zoom-reset" title="Reset zoom">Reset</button>
              </div>
            </div>
          </div>
          <div class="modal__foot">
            <button type="button" class="btn btn-outline" data-close-modal="doc-preview-modal">Close</button>
          </div>
        </div>
      </div>

      <div class="modal-backdrop" id="forward-modal">
        <div class="modal">
          <div class="modal__head"><h3>Pass to Approval</h3></div>
          <div class="modal__body"><p>Send <?= e($current['name']) ?>'s application to the President for final approval? This confirms all documents have been verified.</p></div>
          <div class="modal__foot">
            <button type="button" class="btn btn-outline" data-close-modal="forward-modal">Cancel</button>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/validation_tools.php?forward=<?= urlencode($current['app_no']) ?>">Confirm</a>
          </div>
        </div>
      </div>

      <div class="modal-backdrop" id="return-modal">
        <div class="modal is-danger" style="max-width:520px;">
          <form method="post" action="<?= BASE_URL ?>/validation_tools.php">
            <input type="hidden" name="reject_app_no" value="<?= e($current['app_no']) ?>">
            <div class="modal__head"><h3>Reject Application</h3></div>
            <div class="modal__body">
              <p>Are you sure you want to reject this application? The applicant will be notified and must resubmit before it can be reviewed again.</p>
              <div class="form-grid" style="margin-top:14px;">
                <div class="field">
                  <label>Note</label>
                  <textarea name="note" rows="4" placeholder="Note..."></textarea>
                </div>
                <div class="field">
                  <label>Reason</label>
                  <select name="reason" required>
                    <option value="">Select a reason</option>
                    <?php foreach (d('reject_reasons') as $r): ?><option value="<?= e($r) ?>"><?= e($r) ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal__foot">
              <button type="button" class="btn btn-outline" data-close-modal="return-modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Reject</button>
            </div>
          </form>
        </div>
      </div>

    <?php else: ?>

      <div style="margin-bottom:20px;">
        <h2 class="page-title">Validation Tool</h2>
        <p class="page-subtitle"><?= $paginatedPending['total'] ?> applications (showing <?= count($paginatedPending['items']) ?> on this page)</p>
      </div>

      <div class="card" style="overflow:hidden;">
        <div class="filter-bar">
          <input type="search" placeholder="Search name or ID..." id="valSearch">
          <select id="valBarangay">
            <option value="">All Barangay</option>
            <?php foreach ($barangays_list as $b): ?>
              <option value="<?= e($b) ?>"><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="table-wrap">
          <table class="val-table" id="validationTable">
            <thead>
              <tr>
                <th>Applicant No.</th>
                <th>Driver ID</th>
                <th>Driver Name</th>
                <th>Barangay</th>
                <th>Body No.</th>
                <th>Submission</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($paginatedPending['items'])): ?>
                <tr><td colspan="7"><div class="empty-state">No applications waiting for validation.</div></td></tr>
              <?php else: foreach ($paginatedPending['items'] as $app): ?>
                <tr>
                  <td class="id-col"><?= e($app['app_no']) ?></td>
                  <td class="id-col"><?= e($app['driver_id']) ?></td>
                  <td><?= e($app['name']) ?></td>
                  <td><?= e($app['barangay']) ?></td>
                  <td><?= e($app['body_no']) ?></td>
                  <td class="id-col"><?= e($app['submitted']) ?></td>
                  <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/validation_tools.php?id=<?= urlencode($app['app_no']) ?>">Review</a></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php render_pagination($paginatedPending['page'], $paginatedPending['total_pages'], BASE_URL . '/validation_tools.php'); ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var search = document.getElementById('valSearch');
  var filter = document.getElementById('valBarangay');
  var table  = document.getElementById('validationTable');
  if (table) {
    function applyFilters() {
      var q = search ? search.value.trim().toLowerCase() : '';
      var b = filter ? filter.value : '';
      table.querySelectorAll('tbody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var barangay = row.children[3] ? row.children[3].textContent.trim() : '';
        var matchQ = !q || text.includes(q);
        var matchB = !b || barangay === b;
        row.style.display = (matchQ && matchB) ? '' : 'none';
      });
    }
    if (search) search.addEventListener('input', applyFilters);
    if (filter) filter.addEventListener('change', applyFilters);
  }

  var docChecks = document.querySelectorAll('.doc-verify-check');
  var forwardBtn = document.getElementById('forwardBtn');
  if (docChecks.length && forwardBtn) {
    function refreshForwardState() {
      var allChecked = Array.prototype.every.call(docChecks, function (c) { return c.checked; });
      forwardBtn.disabled = !allChecked;
    }
    docChecks.forEach(function (c) {
      c.addEventListener('change', function () {
        refreshForwardState();
        if (!c.checked) return; 
        fetch(window.location.pathname, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'verify_doc=' + encodeURIComponent(c.getAttribute('data-doc')) +
                '&app_no=' + encodeURIComponent(c.getAttribute('data-app'))
        }).catch(function () {});
      });
    });
    refreshForwardState();
  }

  var previewModal   = document.getElementById('doc-preview-modal');
  var previewTitle   = document.getElementById('docPreviewTitle');
  var previewFrame   = document.getElementById('docPreviewFrame');
  var previewContent = document.getElementById('docPreviewContent');
  var previewZoomBar = document.getElementById('docPreviewZoom');
  var zoomOutBtn      = document.getElementById('docZoomOut');
  var zoomInBtn       = document.getElementById('docZoomIn');
  var zoomResetBtn    = document.getElementById('docZoomReset');
  var zoomLevelLabel  = document.getElementById('docZoomLevel');
  var previewPlaceholderHTML = previewContent ? previewContent.innerHTML : '';

  var ZOOM_MIN = 1, ZOOM_MAX = 3, ZOOM_STEP = 0.25;
  var zoomLevel = 1;

  var baseSize = null; 
  var currentPdfBaseSrc = null; 

  function currentDocEl() {
    return previewContent ? previewContent.querySelector('img, iframe') : null;
  }

 
  function captureBaseSize() {
    var el = currentDocEl();
    if (!el) { baseSize = null; return; }
    el.style.width = '';
    el.style.height = '';
    var rect = el.getBoundingClientRect();
    baseSize = rect.width ? { width: rect.width, height: rect.height } : null;
  }

  function applyZoom() {
    var el = currentDocEl();
    if (el) {
      if (el.tagName === 'IMG') {
     
        if (zoomLevel === 1 || !baseSize) {
          el.style.width = '';
          el.style.height = '';
        } else {
          el.style.width = (baseSize.width * zoomLevel) + 'px';
          el.style.height = 'auto';
        }
        el.classList.toggle('is-zoomed', zoomLevel > 1);
      } else if (el.tagName === 'IFRAME' && currentPdfBaseSrc) {
      
        var pct = Math.round(zoomLevel * 100);
    
        var zoomParam = zoomLevel <= 1 ? 'page-fit' : pct;
        var newSrc = currentPdfBaseSrc + '#zoom=' + zoomParam + '&toolbar=0';
      
        var freshFrame = document.createElement('iframe');
        freshFrame.src = newSrc;
        if (el.parentNode) el.parentNode.replaceChild(freshFrame, el);
      }
    }
    if (previewFrame) previewFrame.classList.toggle('is-zoomed-in', zoomLevel > 1);
    if (zoomLevelLabel) zoomLevelLabel.textContent = Math.round(zoomLevel * 100) + '%';
    if (zoomOutBtn) zoomOutBtn.disabled = zoomLevel <= ZOOM_MIN;
    if (zoomInBtn) zoomInBtn.disabled = zoomLevel >= ZOOM_MAX;
  }

  function setZoom(level) {
    zoomLevel = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, level));
    applyZoom();
  }

  var isDragging = false, dragStartX = 0, dragStartY = 0, dragScrollLeft = 0, dragScrollTop = 0;

  function dragPoint(e) {
    return e.touches && e.touches.length ? e.touches[0] : e;
  }

  function startDrag(e) {
    if (zoomLevel <= 1 || !previewFrame) return; 
    isDragging = true;
    var p = dragPoint(e);
    dragStartX = p.clientX;
    dragStartY = p.clientY;
    dragScrollLeft = previewFrame.scrollLeft;
    dragScrollTop = previewFrame.scrollTop;
    var el = currentDocEl();
    if (el) el.style.pointerEvents = 'none'; 
    previewFrame.classList.add('is-dragging');
    e.preventDefault();
  }

  function duringDrag(e) {
    if (!isDragging || !previewFrame) return;
    var p = dragPoint(e);
    previewFrame.scrollLeft = dragScrollLeft - (p.clientX - dragStartX);
    previewFrame.scrollTop  = dragScrollTop  - (p.clientY - dragStartY);
    e.preventDefault();
  }

  function endDrag() {
    if (!isDragging) return;
    isDragging = false;
    var el = currentDocEl();
    if (el) el.style.pointerEvents = '';
    if (previewFrame) previewFrame.classList.remove('is-dragging');
  }

  if (previewFrame) {
    previewFrame.addEventListener('mousedown', startDrag);
    document.addEventListener('mousemove', duringDrag);
    document.addEventListener('mouseup', endDrag);
    previewFrame.addEventListener('touchstart', startDrag, { passive: false });
    document.addEventListener('touchmove', duringDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
  }

  if (zoomInBtn)    zoomInBtn.addEventListener('click', function () { setZoom(zoomLevel + ZOOM_STEP); });
  if (zoomOutBtn)   zoomOutBtn.addEventListener('click', function () { setZoom(zoomLevel - ZOOM_STEP); });
  if (zoomResetBtn) zoomResetBtn.addEventListener('click', function () { setZoom(1); });


  if (previewContent) {
    previewContent.addEventListener('dblclick', function (e) {
      if (e.target.tagName !== 'IMG') return;
      setZoom(zoomLevel > 1 ? 1 : 2);
    });
  }

  document.querySelectorAll('[data-open-doc-preview]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (previewTitle) previewTitle.textContent = btn.getAttribute('data-open-doc-preview');
      var src = btn.getAttribute('data-doc-src');
      var isPdf = btn.getAttribute('data-doc-type') === 'pdf';
      baseSize = null; 
      currentPdfBaseSrc = isPdf ? src : null;
      if (previewFrame && previewContent) {
        if (src) {
          previewFrame.classList.remove('is-empty');
       
          previewContent.innerHTML = isPdf
            ? '<iframe src="about:blank"></iframe>'
            : '<img src="' + src + '" alt="Document preview">';
          if (previewZoomBar) previewZoomBar.style.display = 'flex';
        } else {
          previewFrame.classList.add('is-empty');
          previewContent.innerHTML = previewPlaceholderHTML;
          if (previewZoomBar) previewZoomBar.style.display = 'none';
        }
      }
      setZoom(1); 
      if (previewModal) previewModal.classList.add('is-open');
      if (src && !isPdf) {
        var newEl = currentDocEl();
        if (newEl) {
          if (newEl.complete) captureBaseSize();
          else newEl.addEventListener('load', captureBaseSize, { once: true });
        }
      }
    });
  });
});
</script>

<?php render_shared_script(); ?>
</body>
</html>