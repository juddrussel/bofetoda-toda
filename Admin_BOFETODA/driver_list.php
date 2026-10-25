<?php

require_once __DIR__ . '/config.php';
require_login();
require_once __DIR__ . '/admin-documents.php';

$officer   = current_officer();
$role      = $officer['role'] ?? 'secretary';
$canManage = in_array($role, ['president', 'secretary'], true);

$notice = null;
$error  = null;
$newDriverCredentials = null; 

if ($canManage && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_driver') {
            $generatedPassword = generate_driver_password();
            $newId = db_add_driver([
                'name'     => trim($_POST['name'] ?? ''),
                'age'      => (int) ($_POST['age'] ?? 0),
                'birthday' => $_POST['birthday'] ?? '',
                'contact'  => trim($_POST['contact'] ?? ''),
                'address'  => trim($_POST['address'] ?? ''),
                'body_no'  => trim($_POST['body_no'] ?? ''),
                'barangay' => $_POST['barangay'] ?? '',
                'color'    => $_POST['color'] ?? '',
                'password' => $generatedPassword,
            ]);
            if ($newId) {
                $notice = 'Driver added successfully.';
                $newDriverCredentials = ['driver_id' => $newId, 'password' => $generatedPassword];
            } else {
                $error = 'Could not add driver — check the barangay/color values.';
            }

        } elseif ($action === 'edit_driver') {
            $ok = db_update_driver($_POST['driver_id'] ?? '', [
                'name'     => trim($_POST['name'] ?? ''),
                'age'      => (int) ($_POST['age'] ?? 0),
                'birthday' => $_POST['birthday'] ?? '',
                'contact'  => trim($_POST['contact'] ?? ''),
                'address'  => trim($_POST['address'] ?? ''),
                'body_no'  => trim($_POST['body_no'] ?? ''),
                'barangay' => $_POST['barangay'] ?? '',
                'color'    => $_POST['color'] ?? '',
            
            ]);
            $notice = $ok ? 'Driver record updated.' : null;
            $error  = $ok ? null : 'Could not update driver — check the barangay/color values.';

        } elseif ($action === 'change_password') {
            $newPass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if ($newPass !== '' && $newPass === $confirm) {
                db_set_driver_password($_POST['driver_id'] ?? '', $newPass);
                $notice = 'Driver password updated.';
            } else {
                $error = 'Passwords did not match.';
            }

        } elseif ($action === 'archive_driver') {
            db_set_driver_archived($_POST['driver_id'] ?? '', true);
            $notice = 'Driver archived.';

        } elseif ($action === 'restore_driver') {
            db_set_driver_archived($_POST['driver_id'] ?? '', false);
            $notice = 'Driver restored to the active list.';
        }
    } catch (DriverValidationException $e) {
  
        $error = $e->getMessage();
    } catch (Throwable $e) {
        error_log('driver_list.php action failed: ' . $e->getMessage());
        $error = 'Something went wrong while saving. Please try again.';
    }
}

$drivers   = d('drivers');
$archived  = d('archived_drivers');

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10; // Items per page
$isArchived = (($_GET['view'] ?? 'active') === 'archived');
$listToShow = $isArchived ? $archived : $drivers;
$paginatedList = paginate_array($listToShow, $page, $perPage);

$barangays = array_keys(d('barangays'));
sort($barangays);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver List · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.page-title { font-size:26px; font-weight:800; color:var(--navy); }
.page-title .is-archived-tag { color:var(--red); }
.page-subtitle { font-size:13px; color:var(--text-muted); margin-top:4px; }
.filter-bar { display:flex; align-items:center; gap:12px; padding:16px 20px; border-bottom:1px solid var(--border); position:relative; }
.filter-bar input[type="search"] { flex:1; padding:10px 14px 10px 36px; border:1px solid var(--border); border-radius:8px; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239aa1b4' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='M21 21l-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; font-size:14px; color:var(--text); }
.filter-bar input[type="search"]:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }
.btn-filter {
    display:inline-flex; align-items:center; gap:7px; flex-shrink:0;
    background:#fff; border:1px solid var(--border); border-radius:8px;
    padding:10px 14px; font-size:13.5px; font-weight:600; color:var(--text);
    cursor:pointer; transition:border-color .15s, background .15s; position:relative;
}
.btn-filter:hover { border-color:var(--navy); }
.btn-filter.has-active { border-color:var(--navy); background:#f4f6fb; color:var(--navy); }
.btn-filter__count {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:17px; height:17px; padding:0 4px; border-radius:999px;
    background:var(--navy); color:#fff; font-size:10.5px; font-weight:700;
}
.filter-panel {
    display:none; position:absolute; top:calc(100% + 6px); right:20px; z-index:20;
    background:#fff; border:1px solid var(--border); border-radius:10px;
    box-shadow:0 12px 32px rgba(0,0,0,.12); width:260px; padding:16px;
}
.filter-panel.is-open { display:block; }
.filter-panel__field { margin-bottom:14px; }
.filter-panel__field label {
    display:block; font-size:11px; font-weight:800; text-transform:uppercase;
    letter-spacing:.5px; color:#6b7280; margin-bottom:6px;
}
.filter-panel__field select {
    width:100%; padding:9px 10px; border:1px solid var(--border); border-radius:7px;
    background:#fff; font-size:13.5px; color:var(--text); font-family:inherit;
}
.filter-panel__field select:focus { outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }
.filter-panel__foot { display:flex; justify-content:space-between; align-items:center; margin-top:4px; }
.filter-panel__clear {
    background:none; border:none; color:var(--text-muted); font-size:12.5px;
    font-weight:600; cursor:pointer; padding:6px 4px;
}
.filter-panel__clear:hover { color:var(--navy); text-decoration:underline; }
.filter-panel__apply {
    background:var(--navy); color:#fff; border:none; border-radius:7px;
    padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer;
}
.filter-panel__apply:hover { background:var(--navy-light); }
.drv-table-wrap { overflow:hidden; border-radius:0 0 var(--radius) var(--radius); }
.drv-table { width:100%; border-collapse:collapse; font-size:13.6px; }
.drv-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); padding:12px 16px; border-bottom:1px solid var(--border); white-space:nowrap; }
.drv-table td { padding:14px 16px; border-bottom:1px solid var(--border); color:var(--text); }
.drv-table tbody tr:hover { background:var(--bg); }
.drv-table tbody tr.row-pending  { background:#fffbe6; }
.drv-table tbody tr.row-pending:hover  { background:#fff3bf; }
.drv-table tbody tr.row-rejected { background:#fdecea; }
.drv-table tbody tr.row-rejected:hover { background:#fbd5d2; }
.drv-table tbody tr.row-approved { background:#eafaf0; }
.drv-table tbody tr.row-approved:hover { background:#d4f4e0; }
.drv-table tbody tr.row-validated { background:#eaf2fd; }
.drv-table tbody tr.row-validated:hover { background:#d7e7fb; }
.drv-table td.id-col { color:var(--text-muted); font-size:12.5px; }
.action-btns { display:flex; gap:6px; align-items:center; }
.icon-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:none; cursor:pointer; transition:opacity .15s; }
.icon-btn:hover { opacity:.8; }
.icon-btn.edit { background:#22c55e; }
.icon-btn.delete { background:#ef4444; }
.icon-btn svg { width:14px; height:14px; }
.restore-link { display:inline-flex; align-items:center; gap:5px; background:none; border:none; color:var(--navy-light); font-weight:600; font-size:13px; cursor:pointer; padding:0; }
.restore-link:hover { text-decoration:underline; }
.restore-link svg { width:14px; height:14px; }
.header-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.header-btns { display:flex; gap:10px; }
.form-section-title { font-size:14.5px; font-weight:800; color:var(--navy); margin:18px 0 12px; }
.form-section-title:first-child { margin-top:4px; }
.drv-table tbody tr.is-clickable { cursor:pointer; }

.req { color:var(--red); }
input.has-error, select.has-error { border-color:var(--red) !important; background:#fdecea; }
.addr-fixed-group { display:flex; gap:8px; align-items:center; height:41px; }
.addr-pill { display:inline-flex; align-items:center; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:#f4f5f7; color:var(--text-muted); font-size:13.5px; font-weight:600; }
.address-preview { font-size:12.5px; color:var(--text-muted); margin:-6px 0 16px; }
.address-preview span { color:var(--navy); font-weight:600; }
.pw-auto-note { font-size:12px; color:var(--text-muted); margin-top:14px; }
.pw-field-wrap { position:relative; }
.pw-field-wrap input { padding-right:38px; width:100%; }
.pw-eye-btn { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:2px; color:var(--text-muted); display:flex; align-items:center; justify-content:center; }
.pw-eye-btn:hover { color:var(--navy); }

.doc-grid-view { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; margin-top:4px; }
.doc-tile-view { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid var(--border); border-radius:10px; padding:14px 16px; background:#fff; font-size:13.8px; font-weight:700; color:var(--text); }
.doc-tile-view__expand { width:26px; height:26px; border-radius:6px; border:1px solid var(--border); background:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--text-muted); cursor:pointer; transition:border-color .15s ease, color .15s ease; }
.doc-tile-view__expand:hover { border-color:var(--navy); color:var(--navy); }
.doc-tile-view__expand svg { width:13px; height:13px; }
.doc-preview-modal { z-index:60; }
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
<?php render_sidebar('driverlist'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Driver list Management', 'Organizes, updates, and maintains all driver information.'); ?>

  <div class="page">

    <?php if ($notice): ?>
      <div class="card" style="border-color:var(--green); background:var(--green-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--green); font-weight:600;"><?= e($notice) ?></div>
      </div>
    <?php elseif ($error): ?>
      <div class="card" style="border-color:var(--red); background:var(--red-bg); margin-bottom:20px;">
        <div class="card__body" style="color:var(--red); font-weight:600;"><?= e($error) ?></div>
      </div>
    <?php endif; ?>

    <div class="header-row">
      <div>
        <h2 class="page-title">
          Driver List Management
          <?php if ($isArchived): ?><span class="is-archived-tag">- Archived</span><?php endif; ?>
        </h2>
        <p class="page-subtitle">Organizes, updates, and maintains all driver information.</p>
      </div>
      <?php if ($canManage): ?>
      <div class="header-btns">
        <?php if ($isArchived): ?>
          <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/driver_list.php">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Back
          </a>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/driver_list.php?view=archived">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
            Show Archived
          </a>
        <?php endif; ?>
        <button type="button" class="btn btn-primary btn-sm" data-open-modal="add-driver-modal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          Add New
        </button>
      </div>
      <?php endif; ?>
    </div>

    <div class="card" style="overflow:visible;">
      <div class="filter-bar">
        <input type="search" placeholder="Search name, Barangay, Renewal Status, or ID..." id="drvSearch">

        <button type="button" class="btn-filter" id="filterToggleBtn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
          Filter
          <span class="btn-filter__count" id="filterCount" style="display:none;">0</span>
        </button>

        <div class="filter-panel" id="filterPanel">
          <div class="filter-panel__field">
            <label for="filterBarangay">Barangay</label>
            <select id="filterBarangay">
              <option value="">All Barangays</option>
              <?php foreach ($barangays as $b): ?>
                <option value="<?= e($b) ?>"><?= e($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-panel__field">
            <label for="filterStatus">Renewal Status</label>
            <select id="filterStatus">
              <option value="">All Status</option>
              <option value="-">Not yet applied</option>
              <option value="Pending Renewal">Pending Renewal</option>
              <option value="Validated Renewal">Validated Renewal</option>
              <option value="Approved Renewal">Approved Renewal</option>
              <option value="Rejected Renewal">Rejected Renewal</option>
            </select>
          </div>
          <div class="filter-panel__foot">
            <button type="button" class="filter-panel__clear" id="filterClearBtn">Clear filters</button>
            <button type="button" class="filter-panel__apply" id="filterApplyBtn">Apply</button>
          </div>
        </div>
      </div>
      <div class="table-wrap drv-table-wrap">
        <table class="drv-table" id="driversTable">
          <thead>
            <tr>
              <th>Driver ID</th>
              <th>Driver Name</th>
              <th>Barangay</th>
              <th>Body No.</th>
              <th>Renewal Status</th>
              <?php if ($canManage): ?><th>Action</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!$isArchived): ?>
              <?php foreach ($paginatedList['items'] as $drv): ?>
                <tr class="is-clickable <?= row_class_for_status($drv['status']) ?>" data-open-modal="view-<?= e($drv['driver_id']) ?>" title="View driver details" data-barangay="<?= e($drv['barangay']) ?>" data-status="<?= e($drv['status']) ?>">
                  <td class="id-col"><?= e($drv['driver_id']) ?></td>
                  <td><?= e($drv['name']) ?></td>
                  <td><?= e($drv['barangay']) ?></td>
                  <td><?= e($drv['body_no']) ?></td>
                  <td><span class="badge <?= badge_class_for_status($drv['status']) ?>"><?= e($drv['status']) ?></span></td>
                  <?php if ($canManage): ?>
                  <td>
                    <div class="action-btns" data-stop-row-click>
                      <button type="button" class="icon-btn edit" data-open-modal="edit-<?= e($drv['driver_id']) ?>" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button type="button" class="icon-btn delete" data-open-modal="delete-<?= e($drv['driver_id']) ?>" title="Archive">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                      </button>
                    </div>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <?php foreach ($paginatedList['items'] as $drv): ?>
                <tr data-barangay="<?= e($drv['barangay']) ?>" data-status="-">
                  <td class="id-col"><?= e($drv['driver_id']) ?></td>
                  <td><?= e($drv['name']) ?></td>
                  <td><?= e($drv['barangay']) ?></td>
                  <td><?= e($drv['body_no']) ?></td>
                  <td>-</td>
                  <?php if ($canManage): ?>
                  <td>
                    <button type="button" class="restore-link" data-open-modal="restore-<?= e($drv['driver_id']) ?>">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                      Restore
                    </button>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php 
      $baseUrl = BASE_URL . '/driver_list.php?view=' . ($isArchived ? 'archived' : 'active');
      render_pagination($paginatedList['page'], $paginatedList['total_pages'], $baseUrl); 
      ?>
    </div>

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

<?php foreach ($isArchived ? $archived : $drivers as $drv): ?>
<div class="modal-backdrop" id="view-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:680px;">
    <div class="modal__body" style="padding:20px 24px 4px;">
      <div class="form-section-title" style="margin-top:0;">Driver's Profile</div>
      <div class="form-grid-3">
        <div class="field"><label>Driver ID</label><input type="text" value="<?= e($drv['driver_id']) ?>" disabled></div>
        <div class="field"><label>Name</label><input type="text" value="<?= e($drv['name']) ?>" disabled></div>
        <div class="field"><label>Age</label><input type="text" value="<?= e($drv['age']) ?>" disabled></div>
        <div class="field"><label>Birthday</label><input type="text" value="<?= e($drv['birthday']) ?>" disabled></div>
        <div class="field"><label>Contact No.</label><input type="text" value="<?= e($drv['contact']) ?>" disabled></div>
        <div class="field"><label>Address</label><input type="text" value="<?= e($drv['address']) ?>" disabled></div>
      </div>

      <div class="form-section-title">Vehicle's Information</div>
      <div class="form-grid-3">
        <div class="field"><label>Body No.</label><input type="text" value="<?= e($drv['body_no']) ?>" disabled></div>
        <div class="field"><label>Barangay</label><input type="text" value="<?= e($drv['barangay']) ?>" disabled></div>
        <div class="field"><label>Color Code</label><input type="text" value="<?= e($drv['color']) ?>" disabled></div>
      </div>

      <div class="form-grid-3" style="margin-top:16px;">
        <div class="field">
          <label>Password</label>
          <div class="pw-field-wrap">
            <input type="password" id="pw_view_<?= e($drv['driver_id']) ?>" value="<?= e($drv['password'] ?? '') ?>" disabled>
            <button type="button" class="pw-eye-btn" data-toggle-pass-eye="pw_view_<?= e($drv['driver_id']) ?>" title="Show/hide password"></button>
          </div>
        </div>
      </div>

      <div class="form-section-title">Uploaded Documents</div>
      <?php
        $drvDocsApp = get_driver_documents_for_display($drv['driver_id']);
        $docLabels  = ['license' => "Driver's License", 'or_cr' => 'OR/CR', 'photo' => '1x1 Picture', 'receipt' => 'Proof of Payment'];
      ?>
      <?php if (!$drvDocsApp): ?>
        <div class="empty-state" style="margin-bottom:8px;">No approved documents on file yet. Documents only appear here once the president has approved this driver's latest renewal application.</div>
      <?php else: ?>
        <div class="doc-grid-view" style="margin-bottom:8px;">
          <?php foreach ($docLabels as $key => $label):
            $doc = $drvDocsApp['docs'][$key] ?? null;
            $hasFile = $doc && !empty($doc['file_path']);
            $previewUrl = $hasFile ? (BASE_URL . '/doc-file.php?app=' . urlencode($drvDocsApp['app_no']) . '&doc=' . urlencode($key)) : '';
          
            $docExt = $hasFile ? strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) : '';
          ?>
            <div class="doc-tile-view">
              <span><?= e($label) ?><?php if (!$hasFile): ?> <span style="color:var(--text-muted); font-weight:500; font-size:12px;">(not uploaded)</span><?php endif; ?></span>
              <span class="doc-tile-view__expand" data-open-doc-preview="<?= e($label) ?>" data-doc-src="<?= e($previewUrl) ?>" data-doc-type="<?= $docExt === 'pdf' ? 'pdf' : 'image' ?>" title="View document">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="modal__foot" style="margin-top:18px;">
      <button type="button" class="btn btn-outline" data-close-modal="view-<?= e($drv['driver_id']) ?>">Close</button>
    </div>
  </div>
</div>

<?php if ($canManage): ?>
<div class="modal-backdrop" id="edit-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:680px;">
    <form id="edit-driver-form-<?= e($drv['driver_id']) ?>" method="post" action="<?= BASE_URL ?>/driver_list.php">
      <input type="hidden" name="action" value="edit_driver">
      <input type="hidden" name="driver_id" value="<?= e($drv['driver_id']) ?>">
      <div class="modal__body" style="padding:20px 24px 4px;">
        <div class="form-section-title" style="margin-top:0;">Driver's Profile</div>
        <div class="form-grid-3">
          <div class="field"><label for="e_id_<?= e($drv['driver_id']) ?>">Driver ID</label><input type="text" id="e_id_<?= e($drv['driver_id']) ?>" value="<?= e($drv['driver_id']) ?>" disabled></div>
          <div class="field"><label for="e_name_<?= e($drv['driver_id']) ?>">Name</label><input type="text" id="e_name_<?= e($drv['driver_id']) ?>" name="name" value="<?= e($drv['name']) ?>" required></div>
          <div class="field"><label for="e_age_<?= e($drv['driver_id']) ?>">Age</label><input type="number" id="e_age_<?= e($drv['driver_id']) ?>" name="age" value="<?= e($drv['age']) ?>" min="18" required></div>
          <div class="field"><label for="e_bday_<?= e($drv['driver_id']) ?>">Birthday</label><input type="text" id="e_bday_<?= e($drv['driver_id']) ?>" name="birthday" value="<?= e($drv['birthday']) ?>" required></div>
          <div class="field"><label for="e_contact_<?= e($drv['driver_id']) ?>">Contact No.</label><input type="text" id="e_contact_<?= e($drv['driver_id']) ?>" name="contact" value="<?= e($drv['contact']) ?>" required></div>
          <div class="field"><label for="e_address_<?= e($drv['driver_id']) ?>">Address</label><input type="text" id="e_address_<?= e($drv['driver_id']) ?>" name="address" value="<?= e($drv['address']) ?>" required></div>
        </div>

        <div class="form-section-title">Vehicle's Information</div>
        <div class="form-grid-3">
          <div class="field"><label for="e_body_<?= e($drv['driver_id']) ?>">Body No.</label><input type="text" id="e_body_<?= e($drv['driver_id']) ?>" name="body_no" value="<?= e($drv['body_no']) ?>" required></div>
          <div class="field">
            <label for="e_barangay_<?= e($drv['driver_id']) ?>">Barangay</label>
            <select id="e_barangay_<?= e($drv['driver_id']) ?>" name="barangay" required>
              <?php foreach ($barangays as $b): ?><option value="<?= e($b) ?>" <?= $b === $drv['barangay'] ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="e_color_<?= e($drv['driver_id']) ?>">Color Code</label>
            <select id="e_color_<?= e($drv['driver_id']) ?>" name="color" required>
              <?php foreach (d('colors') as $c): ?><option value="<?= e($c) ?>" <?= $c === $drv['color'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-section-title">Renewal Status</div>
        <div class="form-grid-3">
          <div class="field">
            <label>Renewal Status</label>
            <input type="text" value="<?= e($drv['status']) ?>" disabled
              style="background:#f3f4f6; color:#6b7280; cursor:not-allowed;">
            <p style="font-size:11.5px; color:#9ca3af; margin-top:5px;">
              This is automatically managed by the system and cannot be edited manually.
            </p>
          </div>
        </div>
      </div>

      <div class="modal__foot-split" style="margin-top:12px; align-items:center;">
        <button type="button" class="link-btn" style="padding:0;" data-open-modal="change-password-<?= e($drv['driver_id']) ?>">Change Password</button>
        <div style="display:flex; gap:10px;">
          <button type="button" class="btn btn-danger" data-close-modal="edit-<?= e($drv['driver_id']) ?>">Cancel</button>
          <button type="button" class="btn btn-primary" data-open-modal="confirm-update-<?= e($drv['driver_id']) ?>">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="confirm-update-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:440px; text-align:center;">
    <div class="modal__head"><h3>Notice</h3></div>
    <div class="modal__body">
      <p>Are you sure you want to update and save these changes? Once saved, the information will overwrite the existing record in the Masterlist.</p>
    </div>
    <div class="modal__foot" style="justify-content:center;">
      <button type="button" class="btn btn-outline btn-pill" data-close-modal="confirm-update-<?= e($drv['driver_id']) ?>">Cancel</button>
      <button type="button" class="btn btn-primary btn-pill" data-submit-form="edit-driver-form-<?= e($drv['driver_id']) ?>">Save</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="change-password-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:420px;">
    <div class="modal__head"><h3>Change Password</h3></div>
    <form id="change-password-form-<?= e($drv['driver_id']) ?>" method="post" action="<?= BASE_URL ?>/driver_list.php">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="driver_id" value="<?= e($drv['driver_id']) ?>">
      <div class="modal__body">
        <div class="field" style="margin:14px 0;">
          <label for="np_<?= e($drv['driver_id']) ?>">New Password <span style="color:var(--red)">*</span></label>
          <input type="password" id="np_<?= e($drv['driver_id']) ?>" name="new_password" required minlength="8">
        </div>
        <div class="field" style="margin:14px 0;">
          <label for="cp_<?= e($drv['driver_id']) ?>">Confirm Password <span style="color:var(--red)">*</span></label>
          <input type="password" id="cp_<?= e($drv['driver_id']) ?>" name="confirm_password" required minlength="8">
        </div>
      </div>
      <div class="modal__foot">
        <button type="button" class="btn btn-outline" data-close-modal="change-password-<?= e($drv['driver_id']) ?>">Cancel</button>
        <button type="button" class="btn btn-primary" data-open-modal="confirm-password-<?= e($drv['driver_id']) ?>" data-validate-passwords="change-password-form-<?= e($drv['driver_id']) ?>">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="confirm-password-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:440px; text-align:center;">
    <div class="modal__head"><h3>Notice</h3></div>
    <div class="modal__body">
      <p>Are you sure you want to change the password for <strong><?= e($drv['name']) ?></strong>? This driver will need to use the new password the next time they log in.</p>
    </div>
    <div class="modal__foot" style="justify-content:center;">
      <button type="button" class="btn btn-outline btn-pill" data-close-modal="confirm-password-<?= e($drv['driver_id']) ?>">Cancel</button>
      <button type="button" class="btn btn-primary btn-pill" data-submit-form="change-password-form-<?= e($drv['driver_id']) ?>">Confirm</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="delete-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:440px; text-align:center;">
    <div class="modal__head"><h3>Notice</h3></div>
    <div class="modal__body">
      <p>Are you sure you want to archive <strong><?= e($drv['name']) ?></strong>? Once archived, this driver will be moved to the archived list and removed from the active Masterlist.</p>
    </div>
    <form method="post" action="<?= BASE_URL ?>/driver_list.php">
      <input type="hidden" name="action" value="archive_driver">
      <input type="hidden" name="driver_id" value="<?= e($drv['driver_id']) ?>">
      <div class="modal__foot" style="justify-content:center;">
        <button type="button" class="btn btn-outline btn-pill" data-close-modal="delete-<?= e($drv['driver_id']) ?>">Cancel</button>
        <button type="submit" class="btn btn-danger btn-pill">Archive</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php if ($canManage): ?>
<?php foreach ($archived as $drv): ?>
<div class="modal-backdrop" id="restore-<?= e($drv['driver_id']) ?>">
  <div class="modal">
    <div class="modal__head"><h3>Restore Driver</h3></div>
    <div class="modal__body">
      <p>Restore <strong><?= e($drv['name']) ?></strong> back to the active driver list?</p>
    </div>
    <form method="post" action="<?= BASE_URL ?>/driver_list.php">
      <input type="hidden" name="action" value="restore_driver">
      <input type="hidden" name="driver_id" value="<?= e($drv['driver_id']) ?>">
      <div class="modal__foot">
        <button type="button" class="btn btn-outline btn-pill" data-close-modal="restore-<?= e($drv['driver_id']) ?>">Cancel</button>
        <button type="submit" class="btn btn-primary btn-pill">Restore</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canManage): ?>
<div class="modal-backdrop" id="add-driver-modal">
  <div class="modal" style="max-width:680px;">
    <form id="add-driver-form" method="post" action="<?= BASE_URL ?>/driver_list.php">
      <input type="hidden" name="action" value="add_driver">
      <input type="hidden" name="status" value="-">
      <div class="modal__body" style="padding:20px 24px 4px;">
        <div class="form-section-title" style="margin-top:0;">Driver's Profile</div>
        <div class="form-grid-3">
          <div class="field"><label for="d_id">Driver ID</label><input type="text" id="d_id" placeholder="Auto-Generated" disabled></div>
          <div class="field"><label for="d_name">Name <span class="req">*</span></label><input type="text" id="d_name" name="name" placeholder="Full Name" required></div>
          <div class="field"><label for="d_birthday">Birthday <span class="req">*</span></label><input type="date" id="d_birthday" name="birthday" max="<?= e(date('Y-m-d')) ?>" required></div>
        </div>
        <div class="form-grid-3">
          <div class="field"><label for="d_age">Age</label><input type="text" id="d_age" name="age" placeholder="Age" readonly></div>
          <div class="field"><label for="d_contact">Contact No. <span class="req">*</span></label><input type="text" id="d_contact" name="contact" placeholder="09" required></div>
        </div>
        <p class="field-error" id="err-d_birthday" style="display:none;margin:-10px 0 14px;">Driver must be at least 18 years old.</p>

        <div class="form-section-title">Address</div>
        <div class="form-grid-3">
          <div class="field"><label for="d_street">House No / Street <span class="req">*</span></label><input type="text" id="d_street" placeholder="House No / Street" required></div>
          <div class="field"><label for="d_addr_barangay">Barangay <span class="req">*</span></label><input type="text" id="d_addr_barangay" placeholder="Barangay" required></div>
          <div class="field">
            <label>&nbsp;</label>
            <div class="addr-fixed-group">
              <span class="addr-pill">Bocaue</span>
              <span class="addr-pill">Bulacan</span>
            </div>
          </div>
        </div>
        <div class="address-preview">Address: <span id="d_address_preview">&mdash;</span></div>
        <input type="hidden" id="d_address" name="address">

        <div class="form-section-title">Vehicle's Information</div>
        <div class="form-grid-3">
          <div class="field"><label for="d_body">Body No. <span class="req">*</span></label><input type="text" id="d_body" name="body_no" placeholder="Body" required></div>
          <div class="field">
            <label for="d_barangay">Barangay <span class="req">*</span></label>
            <select id="d_barangay" name="barangay" required>
              <option value="" disabled selected>Barangay</option>
              <?php foreach ($barangays as $b): ?><option value="<?= e($b) ?>"><?= e($b) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="d_color">Color Code <span class="req">*</span></label>
            <select id="d_color" name="color" required>
              <option value="" disabled selected>Color</option>
              <?php foreach (d('colors') as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="pw-auto-note">A default password will be generated automatically. The driver can change it after logging in for the first time.</p>
      </div>

      <div class="modal__foot-split" style="margin-top:18px;">
        <button type="button" class="btn btn-danger" data-close-modal="add-driver-modal">Cancel</button>
        <button type="button" class="btn btn-primary" data-open-modal="confirm-add-driver">Add Account</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="confirm-add-driver">
  <div class="modal" style="max-width:440px; text-align:center;">
    <div class="modal__head"><h3>Notice</h3></div>
    <div class="modal__body">
      <p>Are you sure you want to create this new account? Once confirmed, the account will be added to the Masterlist and recorded as part of the official membership database.</p>
    </div>
    <div class="modal__foot" style="justify-content:center;">
      <button type="button" class="btn btn-outline btn-pill" data-close-modal="confirm-add-driver">Cancel</button>
      <button type="button" class="btn btn-primary btn-pill" data-submit-form="add-driver-form">Add</button>
    </div>
  </div>
</div>

<?php if ($newDriverCredentials): ?>
<div class="modal-backdrop is-open" id="new-driver-credentials-modal">
  <div class="modal" style="max-width:420px;">
    <div class="modal__head"><h3>Driver's Credentials</h3></div>
    <div class="modal__body">
      <div class="field" style="margin:14px 0;">
        <label>Driver ID</label>
        <input type="text" value="<?= e($newDriverCredentials['driver_id']) ?>" disabled>
      </div>
      <div class="field" style="margin:14px 0;">
        <label>Password (Default)</label>
        <input type="text" value="<?= e($newDriverCredentials['password']) ?>" disabled>
      </div>
      <p class="pw-auto-note" style="margin-top:0;">This is the default password the driver will use for their first login.</p>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn-primary" data-close-modal="new-driver-credentials-modal">Close</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var search        = document.getElementById('drvSearch');
  var table         = document.getElementById('driversTable');
  var filterToggle  = document.getElementById('filterToggleBtn');
  var filterPanel   = document.getElementById('filterPanel');
  var filterBarangay = document.getElementById('filterBarangay');
  var filterStatus   = document.getElementById('filterStatus');
  var filterApply    = document.getElementById('filterApplyBtn');
  var filterClear    = document.getElementById('filterClearBtn');
  var filterCount    = document.getElementById('filterCount');

  var activeBarangay = '';
  var activeStatus   = '';

  function applyFilters() {
    if (!table) return;
    var q = search ? search.value.trim().toLowerCase() : '';

    table.querySelectorAll('tbody tr').forEach(function(row) {
      var matchesSearch   = !q || row.textContent.toLowerCase().includes(q);
      var matchesBarangay = !activeBarangay || row.getAttribute('data-barangay') === activeBarangay;
      var matchesStatus   = !activeStatus   || row.getAttribute('data-status')   === activeStatus;
      row.style.display = (matchesSearch && matchesBarangay && matchesStatus) ? '' : 'none';
    });

    var activeCount = (activeBarangay ? 1 : 0) + (activeStatus ? 1 : 0);
    if (filterCount) {
      filterCount.textContent = activeCount;
      filterCount.style.display = activeCount > 0 ? 'inline-flex' : 'none';
    }
    if (filterToggle) filterToggle.classList.toggle('has-active', activeCount > 0);
  }

  if (search) search.addEventListener('input', applyFilters);

  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      filterPanel.classList.toggle('is-open');
    });
    filterPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () {
      filterPanel.classList.remove('is-open');
    });
  }

  if (filterApply) {
    filterApply.addEventListener('click', function () {
      activeBarangay = filterBarangay ? filterBarangay.value : '';
      activeStatus   = filterStatus ? filterStatus.value : '';
      applyFilters();
      filterPanel.classList.remove('is-open');
    });
  }

  if (filterClear) {
    filterClear.addEventListener('click', function () {
      if (filterBarangay) filterBarangay.value = '';
      if (filterStatus) filterStatus.value = '';
      activeBarangay = '';
      activeStatus   = '';
      applyFilters();
      filterPanel.classList.remove('is-open');
    });
  }

  document.querySelectorAll('[data-submit-form]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var form = document.getElementById(btn.getAttribute('data-submit-form'));
      if (form) form.submit();
    });
  });

  document.querySelectorAll('[data-stop-row-click]').forEach(function (el) {
    el.addEventListener('click', function (e) { e.stopPropagation(); });
  });

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


  if (previewFrame) {
    previewFrame.addEventListener('wheel', function (e) {
      if (!e.ctrlKey && !e.metaKey) return;
      e.preventDefault();
      setZoom(zoomLevel + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
    }, { passive: false });
  }


  if (previewContent) {
    previewContent.addEventListener('dblclick', function (e) {
      if (e.target.tagName !== 'IMG') return;
      setZoom(zoomLevel > 1 ? 1 : 2);
    });
  }

  document.querySelectorAll('[data-open-doc-preview]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
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

  var addBirthday = document.getElementById('d_birthday');
  var addAge      = document.getElementById('d_age');
  var addBdayErr  = document.getElementById('err-d_birthday');
  function computeAge(dateStr) {
    var bday = new Date(dateStr);
    if (isNaN(bday.getTime())) return null;
    var today = new Date();
    var age = today.getFullYear() - bday.getFullYear();
    var m = today.getMonth() - bday.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) age--;
    return age;
  }
  function isAddBirthdayValid() {
    if (!addBirthday || !addBirthday.value) return false;
    var age = computeAge(addBirthday.value);
    return age !== null && age >= 18;
  }
  if (addBirthday && addAge) {
    addBirthday.addEventListener('change', function () {
      var age = computeAge(addBirthday.value);
      addAge.value = (age !== null && age >= 0) ? age : '';
      var underage = age !== null && age < 18;
      if (addBdayErr) addBdayErr.style.display = underage ? 'block' : 'none';
      addBirthday.classList.toggle('has-error', underage);
    });
  }

  var addAccountBtn = document.querySelector('#add-driver-modal [data-open-modal="confirm-add-driver"]');
  if (addAccountBtn) {
    addAccountBtn.addEventListener('click', function (e) {
      if (!isAddBirthdayValid()) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (addBdayErr) addBdayErr.style.display = 'block';
        if (addBirthday) {
          addBirthday.classList.add('has-error');
          if (addBirthday.value === '' && addBirthday.reportValidity) {
            addBirthday.reportValidity();
          }
          addBirthday.focus();
        }
      }
    });
  }

  var addStreet   = document.getElementById('d_street');
  var addBarangay = document.getElementById('d_addr_barangay');
  var addAddress  = document.getElementById('d_address');
  var addPreview  = document.getElementById('d_address_preview');
  function updateAddressPreview() {
    if (!addAddress || !addPreview) return;
    var street   = addStreet ? addStreet.value.trim() : '';
    var barangay = addBarangay ? addBarangay.value.trim() : '';
    var parts = [street, barangay, 'Bocaue', 'Bulacan'].filter(function (p) { return p; });
    var full = parts.join(', ');
    addAddress.value = full;
    addPreview.textContent = full || '\u2014';
  }
  if (addStreet) addStreet.addEventListener('input', updateAddressPreview);
  if (addBarangay) addBarangay.addEventListener('input', updateAddressPreview);
  updateAddressPreview();

  document.querySelectorAll('[data-validate-passwords]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var form = document.getElementById(btn.getAttribute('data-validate-passwords'));
      if (!form) return;
      var newPass = form.querySelector('[name="new_password"]');
      var confirmPass = form.querySelector('[name="confirm_password"]');
      if (!form.reportValidity()) { e.stopImmediatePropagation(); return; }
      if (newPass && confirmPass && newPass.value !== confirmPass.value) {
        confirmPass.setCustomValidity('Passwords do not match.');
        confirmPass.reportValidity();
        e.stopImmediatePropagation();
        return;
      }
      if (confirmPass) confirmPass.setCustomValidity('');
    });
  });
});
</script>

<?php render_shared_script(); ?>
</body>
</html>