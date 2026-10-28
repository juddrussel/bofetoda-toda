<?php

require_once __DIR__ . '/config.php';
require_role('president');
require_once __DIR__ . '/admin-documents.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appNo    = $_POST['app_no'] ?? '';
    $doAction = $_POST['do_action'] ?? '';

    try {
        if ($doAction === 'approve') {
            db_approve_application($appNo);
            header('Location: ' . BASE_URL . '/approval_workflow.php?decided=approved');
            exit;
        } elseif ($doAction === 'reject') {
            db_reject_application($appNo, $_POST['reason'] ?? '', trim($_POST['note'] ?? ''));
            header('Location: ' . BASE_URL . '/approval_workflow.php?decided=rejected');
            exit;
        }
    } catch (Throwable $e) {
        error_log('approval_workflow.php action failed: ' . $e->getMessage());
        $error = 'Something went wrong while saving. Please try again.';
    }
}

$queue = get_for_approval_applications(); // Applications validated and ready for president approval

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10; // Items per page
$paginatedQueue = paginate_array($queue, $page, $perPage);

$totalApplications = count_applications();
$barangays_list = array_keys(d('barangays'));
sort($barangays_list);

$viewId = $_GET['app'] ?? null;
$current = $viewId ? get_application_with_docs($viewId) : null;

$decided = $_GET['decided'] ?? null;

$permit_period = '';
foreach (d('announcements') as $a) {
  if ($a['status'] === 'OPEN') { $permit_period = $a['opened'] . ' - ' . $a['deadline']; break; }
}

if ($current) {
  $docs_total = count($current['docs']);
  $docs_verified = 0;
  foreach ($current['docs'] as $d) {
      if ($d && $d['is_verified']) {
          $docs_verified++;
      }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approval Workflow · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.page-title { font-size:26px; font-weight:800; color:var(--navy); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filter-bar { display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid var(--border); }
.filter-bar input[type="search"] { flex:1; padding:10px 14px 10px 36px; border:1px solid var(--border); border-radius:8px; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239aa1b4' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; font-size:14px; color:var(--text); }
.filter-bar input[type="search"]:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }
.filter-bar select { padding:10px 36px 10px 14px; border:1px solid var(--border); border-radius:8px; background:var(--navy); color:#fff; font-size:13.5px; font-weight:600; appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; background-color:var(--navy); cursor:pointer; }
.appr-table { width:100%; border-collapse:collapse; font-size:13.6px; }
.appr-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
.appr-table td { padding:14px 16px; border-bottom:1px solid var(--border); color:var(--text); }
.appr-table tbody tr:hover { background:var(--bg); }
.appr-table td.id-col { color:var(--text-muted); font-size:12.5px; }

.review-card__head { padding:22px 24px 18px; border-bottom:1px solid var(--border); display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.review-card__eyebrow { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); margin-bottom:4px; }
.review-card__appno { font-size:22px; font-weight:800; color:var(--navy); margin-bottom:8px; }
.review-card__subline { display:flex; align-items:center; gap:14px; font-size:14px; color:var(--text); font-weight:600; }
.review-card__subline span:not(:first-child) { color:var(--text-muted); font-weight:500; }

.review-meta { padding:18px 24px; display:flex; flex-direction:column; gap:12px; }
.review-meta__row { display:flex; align-items:center; justify-content:space-between; font-size:13.5px; }
.review-meta__label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
.review-meta__value { color:var(--text); font-weight:600; text-align:right; }

.review-docs { padding:18px 24px 22px; border-top:1px solid var(--border); }
.review-docs__label { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); font-weight:700; margin-bottom:10px; }
.review-doc-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; }
.review-doc-tile { display:flex; align-items:center; justify-content:space-between; border:1px solid var(--border); border-radius:10px; padding:14px 16px; font-size:13.8px; font-weight:700; color:var(--text); background:#fff; }
.review-doc-tile__icon { width:26px; height:26px; border-radius:6px; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--text-muted); flex-shrink:0; cursor:pointer; background:#fff; transition:border-color .15s ease, color .15s ease; }
.review-doc-tile__icon:hover { border-color:var(--navy); color:var(--navy); }
.review-doc-tile__icon svg { width:13px; height:13px; }

.attest-row { display:flex; align-items:flex-start; justify-content:center; gap:10px; padding:18px 24px 4px; border-top:1px solid var(--border); font-size:13.5px; color:var(--text); text-align:center; }
.attest-row input[type="checkbox"] { width:17px; height:17px; margin-top:1px; accent-color:var(--navy); cursor:pointer; flex-shrink:0; }
.attest-row label { cursor:pointer; line-height:1.4; }
.decision-btn-row { display:flex; gap:12px; }
.decision-btn-row .btn { flex:1 1 50%; min-width:0; padding-top:14px; padding-bottom:14px; font-size:15px; }

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
</style>
</head>
<body>
<?php render_sidebar('approval'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Approval Workflow', 'Final review of validated permit applications'); ?>

  <div class="page">

    <?php if ($decided === 'approved'): ?>
      <div class="card" style="border-color:var(--green); background:var(--green-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--green); font-weight:600;">Application approved. The member's permit has been issued.</div>
      </div>
    <?php elseif ($decided === 'rejected'): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;">Application rejected and sent back to the secretary.</div>
      </div>
    <?php elseif ($error): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;"><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($current): ?>

      <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>/approval_workflow.php" style="margin-bottom:16px; display:inline-flex;">&larr; Back to queue</a>

      <form id="reviewForm" method="post" action="<?= BASE_URL ?>/approval_workflow.php?app=<?= urlencode($current['app_no']) ?>">
      <input type="hidden" name="app_no" value="<?= e($current['app_no']) ?>">
      <div class="card">
        <div class="review-card__head">
          <div>
            <div class="review-card__eyebrow">Approving Application</div>
            <div class="review-card__appno"><?= e($current['app_no']) ?></div>
            <div class="review-card__subline">
              <span><?= e($current['name']) ?></span>
              <span><?= e($current['barangay']) ?></span>
              <span><?= e($current['body_no']) ?></span>
            </div>
          </div>
          <span class="badge <?= badge_class_for_status($current['status']) ?>"><?= e($current['status']) ?></span>
        </div>

        <div class="review-meta">
          <div class="review-meta__row"><span class="review-meta__label">Date Submitted</span><span class="review-meta__value"><?= e($current['submitted']) ?></span></div>
          <div class="review-meta__row"><span class="review-meta__label">Reviewed By</span><span class="review-meta__value">Secretary</span></div>
          <div class="review-meta__row"><span class="review-meta__label">Validation Date</span><span class="review-meta__value"><?= $current['validation_date'] ? e($current['validation_date']) : '&mdash;' ?></span></div>
          <div class="review-meta__row"><span class="review-meta__label">Documents</span><span class="review-meta__value"><?= $docs_verified ?> / <?= $docs_total ?> Verified</span></div>
          <?php if ($permit_period): ?>
          <div class="review-meta__row"><span class="review-meta__label">Permit Period</span><span class="review-meta__value"><?= e($permit_period) ?></span></div>
          <?php endif; ?>
        </div>

        <div class="review-docs">
          <div class="review-docs__label">Supporting Documents</div>
          <div class="review-doc-grid">
            <?php
            $docLabels = ['license' => "Driver's License", 'or_cr' => 'Updated OR/CR', 'receipt' => 'Payment Receipt', 'photo' => '1x1 Picture'];
            foreach ($docLabels as $key => $label):
              $doc = $current['docs'][$key] ?? null;
              $hasFile = $doc && !empty($doc['file_path']);
              $isVerified = $doc['is_verified'] ?? false;
              $docUrl = $hasFile ? (BASE_URL . '/doc-file.php?app=' . urlencode($current['app_no']) . '&doc=' . urlencode($key)) : '';
            
              $docExt = $hasFile ? strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) : '';
            ?>
              <div class="review-doc-tile">
                <span>
                  <?= e($label) ?>
                  <?php if (!$hasFile): ?>
                    <span style="color:var(--text-muted); font-weight:500; font-size:12px;">(not uploaded)</span>
                  <?php elseif (!$isVerified): ?>
                    <span style="color:var(--text-muted); font-weight:500; font-size:12px;">(not verified by secretary)</span>
                  <?php endif; ?>
                </span>
                <span class="review-doc-tile__icon" data-open-doc-preview="<?= e($label) ?>" data-doc-url="<?= e($docUrl) ?>" data-doc-type="<?= $docExt === 'pdf' ? 'pdf' : 'image' ?>" title="View document">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="attest-row">
          <input type="checkbox" id="attestCheck">
          <label for="attestCheck">I attest that this application has been checked and approved under Bocaue TODA Inc. standards. <span style="color:var(--red)">*</span></label>
        </div>

        <div class="card__body" style="padding-top:14px;">
          <div class="decision-btn-row">
            <button type="button" class="btn btn-danger" data-open-modal="reject-modal">Reject</button>
            <button type="button" class="btn btn-success" id="approveBtn" data-open-modal="approve-modal" disabled>Approve</button>
          </div>
        </div>
      </div>
      </form>

      <div class="modal-backdrop doc-preview-modal" id="doc-preview-modal">
        <div class="modal">
          <div class="modal__head"><h3 id="docPreviewTitle">Document</h3></div>
          <div class="modal__body">
            <div class="doc-preview__wrap" id="docPreviewWrap">
              <div class="doc-preview__frame is-empty" id="docPreviewFrame">
                <div class="doc-preview__content" id="docPreviewContent">
                  <div class="doc-preview__placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M14 10l7-7"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    <p>No file was uploaded for this document.</p>
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

      <div class="modal-backdrop" id="approve-modal">
        <div class="modal">
          <div class="modal__head"><h3>Approve Application</h3></div>
          <div class="modal__body">
            <p>Are you sure you want to approve <?= e($current['name']) ?>'s permit renewal?<br>This action issues the official permit and cannot be undone.</p>
          </div>
          <div class="modal__foot">
            <button type="button" class="btn btn-outline" data-close-modal="approve-modal">Cancel</button>
            <button type="submit" form="reviewForm" name="do_action" value="approve" class="btn btn-success">Confirm Approve</button>
          </div>
        </div>
      </div>

      <div class="modal-backdrop" id="reject-modal">
        <div class="modal is-danger" style="max-width:520px;">
          <div class="modal__head"><h3>Admin Remarks</h3></div>
          <div class="modal__body">
            <p>Are you sure you want to reject this application? Once rejected, the applicant will be notified and cannot proceed unless resubmitted.</p>
            <div class="form-grid" style="margin-top:14px;">
              <div class="field">
                <label>Note <span style="color:var(--red)">*</span></label>
                <textarea rows="4" placeholder="Note..." name="note" id="approval-reject-note" form="reviewForm" required></textarea>
              </div>
              <div class="field">
                <label>Reason <span style="color:var(--red)">*</span></label>
                <select name="reason" form="reviewForm" required>
                  <option value="">Select a reason</option>
                  <?php foreach (d('reject_reasons') as $r): ?><option><?= e($r) ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal__foot">
            <button type="button" class="btn btn-outline" data-close-modal="reject-modal">Cancel</button>
            <button type="submit" form="reviewForm" name="do_action" value="reject" class="btn btn-danger" onclick="return validateApprovalRejectForm(event)">Reject</button>
          </div>
        </div>
      </div>

    <?php else: ?>

      <div style="margin-bottom:20px;">
        <h2 class="page-title">Approval Workflow</h2>
        <p class="page-subtitle"><?= $paginatedQueue['total'] ?> applications (showing <?= count($paginatedQueue['items']) ?> on this page)</p>
      </div>

      <div class="card" style="overflow:hidden;">
        <div class="filter-bar">
          <input type="search" placeholder="Search name or ID..." id="apprSearch">
          <select id="apprBarangay">
            <option value="">All Barangay</option>
            <?php foreach ($barangays_list as $b): ?>
              <option value="<?= e($b) ?>"><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="table-wrap">
          <table class="appr-table" id="approvalTable">
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
              <?php if (empty($paginatedQueue['items'])): ?>
                <tr><td colspan="7"><div class="empty-state">No applications waiting for your approval.</div></td></tr>
              <?php else: foreach ($paginatedQueue['items'] as $app): ?>
                <tr>
                  <td class="id-col"><?= e($app['app_no']) ?></td>
                  <td class="id-col"><?= e($app['driver_id']) ?></td>
                  <td><?= e($app['name']) ?></td>
                  <td><?= e($app['barangay']) ?></td>
                  <td><?= e($app['body_no']) ?></td>
                  <td class="id-col"><?= e($app['submitted']) ?></td>
                  <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/approval_workflow.php?app=<?= urlencode($app['app_no']) ?>">Review</a></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
        <?php render_pagination($paginatedQueue['page'], $paginatedQueue['total_pages'], BASE_URL . '/approval_workflow.php'); ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var search = document.getElementById('apprSearch');
  var filter = document.getElementById('apprBarangay');
  var table  = document.getElementById('approvalTable');
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

  var attest = document.getElementById('attestCheck');
  var approveBtn = document.getElementById('approveBtn');
  if (attest && approveBtn) {
    attest.addEventListener('change', function () {
      approveBtn.disabled = !attest.checked;
    });
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
      var url = btn.getAttribute('data-doc-url');
      var isPdf = btn.getAttribute('data-doc-type') === 'pdf';
      baseSize = null;
      currentPdfBaseSrc = isPdf ? url : null;
      if (previewFrame && previewContent) {
        if (url) {
          previewFrame.classList.remove('is-empty');
       
          previewContent.innerHTML = isPdf
            ? '<iframe src="about:blank"></iframe>'
            : '<img src="' + url + '" alt="Uploaded document">';
          if (previewZoomBar) previewZoomBar.style.display = 'flex';
        } else {
          previewFrame.classList.add('is-empty');
          previewContent.innerHTML = previewPlaceholderHTML;
          if (previewZoomBar) previewZoomBar.style.display = 'none';
        }
      }
      setZoom(1); 
      if (previewModal) previewModal.classList.add('is-open');
      if (url && !isPdf) {
        var newEl = currentDocEl();
        if (newEl) {
          if (newEl.complete) captureBaseSize();
          else newEl.addEventListener('load', captureBaseSize, { once: true });
        }
      }
    });
  });

  // Validate approval reject form - note is required
  function validateApprovalRejectForm(event) {
    var noteField = document.getElementById('approval-reject-note');
    if (!noteField) return true;
    
    var noteValue = noteField.value.trim();
    if (!noteValue) {
      event.preventDefault();
      noteField.focus();
      noteField.style.borderColor = '#f56565';
      
      // Show error message
      var existingError = noteField.parentNode.querySelector('.error-message');
      if (!existingError) {
        var errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        errorMsg.style.color = '#f56565';
        errorMsg.style.fontSize = '12px';
        errorMsg.style.marginTop = '4px';
        errorMsg.textContent = 'Please provide a note explaining why this application is being rejected.';
        noteField.parentNode.appendChild(errorMsg);
      }
      
      // Remove error on input
      noteField.addEventListener('input', function() {
        noteField.style.borderColor = '';
        var errorMsg = noteField.parentNode.querySelector('.error-message');
        if (errorMsg) errorMsg.remove();
      }, { once: true });
      
      return false;
    }
    return true;
  }
  
  // Make validateApprovalRejectForm globally accessible
  window.validateApprovalRejectForm = validateApprovalRejectForm;
});
</script>

<?php render_shared_script(); ?>
</body>
</html>