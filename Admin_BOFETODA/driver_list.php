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

// AJAX endpoint for checking body number availability
if (isset($_GET['action']) && $_GET['action'] === 'check_body_number') {
    header('Content-Type: application/json');
    $bodyNo = trim($_GET['body_no'] ?? '');
    $currentDriverId = trim($_GET['current_driver_id'] ?? ''); // For edit modal
    
    if (empty($bodyNo)) {
        echo json_encode(['available' => true]);
        exit;
    }
    
    try {
        $pdo = get_pdo();
        $query = 'SELECT driver_id FROM drivers WHERE body_no = :body_no AND archived = 0';
        $params = ['body_no' => $bodyNo];
        
        // If editing, exclude the current driver
        if (!empty($currentDriverId)) {
            $query .= ' AND driver_id != :current_id';
            $params['current_id'] = $currentDriverId;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Body number is already in use by another driver' : 'Body number is available'
        ]);
    } catch (Exception $e) {
        echo json_encode(['available' => true, 'error' => 'Could not check availability']);
    }
    exit;
}

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
        
        } elseif ($action === 'delete_driver') {
            $driverId = $_POST['driver_id'] ?? '';
            if ($driverId) {
                db_delete_driver_permanently($driverId);
                $notice = 'Driver permanently deleted.';
            } else {
                $error = 'Invalid driver ID.';
            }
        }
    } catch (DriverValidationException $e) {
  
        $error = $e->getMessage();
    } catch (PDOException $e) {
        error_log('driver_list.php PDO error: ' . $e->getMessage());
        // Check for specific database errors
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            if (strpos($e->getMessage(), 'body_no') !== false) {
                $error = 'Body number already exists. Please use a different body number.';
            } elseif (strpos($e->getMessage(), 'contact_no') !== false) {
                $error = 'Contact number already exists. Please use a different contact number.';
            } elseif (strpos($e->getMessage(), 'driver_id') !== false) {
                $error = 'Driver ID already exists. This is a system error, please contact support.';
            } else {
                $error = 'Duplicate entry detected. Please check your input.';
            }
        } else {
            $error = 'Database error: ' . $e->getMessage();
        }
    } catch (Throwable $e) {
        error_log('driver_list.php action failed: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $error = 'Something went wrong while saving. Error: ' . $e->getMessage();
    }
}

// Get view mode and search params
$isArchived = (($_GET['view'] ?? 'active') === 'archived');
$searchQuery = trim($_GET['q'] ?? '');
$filterBarangay = trim($_GET['barangay'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Get drivers list
$allDrivers = d($isArchived ? 'archived_drivers' : 'drivers');

// Server-side search
if ($searchQuery !== '') {
    $searchLower = strtolower($searchQuery);
    $allDrivers = array_filter($allDrivers, function($driver) use ($searchLower) {
        return strpos(strtolower($driver['driver_id']), $searchLower) !== false
            || strpos(strtolower($driver['name']), $searchLower) !== false
            || strpos(strtolower($driver['barangay'] ?? ''), $searchLower) !== false
            || strpos(strtolower($driver['status'] ?? ''), $searchLower) !== false
            || strpos(strtolower($driver['body_no'] ?? ''), $searchLower) !== false
            || strpos(strtolower($driver['contact'] ?? ''), $searchLower) !== false;
    });
}

// Server-side filtering by Barangay
if ($filterBarangay !== '') {
    $allDrivers = array_filter($allDrivers, function($driver) use ($filterBarangay) {
        return ($driver['barangay'] ?? '') === $filterBarangay;
    });
}

// Server-side filtering by Status  
if ($filterStatus !== '') {
    $allDrivers = array_filter($allDrivers, function($driver) use ($filterStatus) {
        return ($driver['status'] ?? '') === $filterStatus;
    });
}

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalItems = count($allDrivers);
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$drivers = array_slice($allDrivers, $offset, $perPage);

$paginatedList = [
    'items' => $drivers,
    'page' => $page,
    'total_pages' => $totalPages,
    'total_items' => $totalItems
];

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
        <p class="page-subtitle">
          Organizes, updates, and maintains all driver information.
          <?php if ($searchQuery !== '' || $filterBarangay !== '' || $filterStatus !== ''): ?>
            <span style="color:var(--navy);font-weight:600;"> · Found <?= $paginatedList['total_items'] ?> result(s)</span>
            <?php if ($searchQuery !== ''): ?> for "<?= e($searchQuery) ?>"<?php endif; ?>
            <?php if ($filterBarangay !== ''): ?> in <?= e($filterBarangay) ?><?php endif; ?>
            <?php if ($filterStatus !== ''): ?> with status "<?= e($filterStatus) ?>"<?php endif; ?>
            <a href="<?= BASE_URL ?>/driver_list.php<?= $isArchived ? '?view=archived' : '' ?>" style="color:var(--accent);margin-left:8px;">Clear all</a>
          <?php endif; ?>
        </p>
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
        <form method="get" action="<?= BASE_URL ?>/driver_list.php" style="flex:1;margin:0;">
          <?php if ($isArchived): ?><input type="hidden" name="view" value="archived"><?php endif; ?>
          <input type="search" name="q" placeholder="Search name, Barangay, Renewal Status, or ID..." id="drvSearch" value="<?= e($searchQuery) ?>" style="width:100%;">
        </form>

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
                <option value="<?= e($b) ?>" <?= $b === $filterBarangay ? 'selected' : '' ?>><?= e($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-panel__field">
            <label for="filterStatus">Renewal Status</label>
            <select id="filterStatus">
              <option value="">All Status</option>
              <option value="-" <?= $filterStatus === '-' ? 'selected' : '' ?>>Not yet applied</option>
              <option value="Pending Renewal" <?= $filterStatus === 'Pending Renewal' ? 'selected' : '' ?>>Pending Renewal</option>
              <option value="Validated Renewal" <?= $filterStatus === 'Validated Renewal' ? 'selected' : '' ?>>Validated Renewal</option>
              <option value="Approved Renewal" <?= $filterStatus === 'Approved Renewal' ? 'selected' : '' ?>>Approved Renewal</option>
              <option value="Rejected Renewal" <?= $filterStatus === 'Rejected Renewal' ? 'selected' : '' ?>>Rejected Renewal</option>
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
              <?php if (empty($paginatedList['items'])): ?>
                <tr>
                  <td colspan="<?= $canManage ? '6' : '5' ?>" style="text-align:center; padding:40px 20px; color:var(--text-muted);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div style="font-weight:600; font-size:15px; margin-bottom:6px;">No drivers found</div>
                    <div style="font-size:13px;">Click "Add New" to create your first driver account.</div>
                  </td>
                </tr>
              <?php else: ?>
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
              <?php endif; ?>
            <?php else: ?>
              <?php if (empty($paginatedList['items'])): ?>
                <tr>
                  <td colspan="<?= $canManage ? '6' : '5' ?>" style="text-align:center; padding:40px 20px; color:var(--text-muted);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;opacity:0.5;"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                    <div style="font-weight:600; font-size:15px; margin-bottom:6px;">No archived drivers</div>
                    <div style="font-size:13px;">Archived drivers will appear here.</div>
                  </td>
                </tr>
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
                    <div class="action-btns" data-stop-row-click>
                      <button type="button" class="restore-link" data-open-modal="restore-<?= e($drv['driver_id']) ?>" title="Restore">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                        Restore
                      </button>
                      <button type="button" class="icon-btn delete" data-open-modal="delete-permanent-<?= e($drv['driver_id']) ?>" title="Delete Permanently">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      </button>
                    </div>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php 
      $baseUrl = BASE_URL . '/driver_list.php?view=' . ($isArchived ? 'archived' : 'active');
      if ($searchQuery !== '') {
          $baseUrl .= '&q=' . urlencode($searchQuery);
      }
      if ($filterBarangay !== '') {
          $baseUrl .= '&barangay=' . urlencode($filterBarangay);
      }
      if ($filterStatus !== '') {
          $baseUrl .= '&status=' . urlencode($filterStatus);
      }
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

<?php 
// Load full driver list only for modals (lazy load)
$allDrivers = null;
if (!empty($_GET['load_modals'])) {
    $allDrivers = d($isArchived ? 'archived_drivers' : 'drivers');
} else {
    // Use current page drivers for modals
    $allDrivers = $paginatedList['items'];
}

// For restore modals, we need archived drivers
$archivedForModals = [];
if ($isArchived) {
    $archivedForModals = $allDrivers;
}
?>

<?php foreach ($allDrivers as $drv): ?>
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
<?php foreach ($archivedForModals as $drv): ?>
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
<?php foreach ($archivedForModals as $drv): ?>
<div class="modal-backdrop" id="delete-permanent-<?= e($drv['driver_id']) ?>">
  <div class="modal" style="max-width:480px;">
    <div class="modal__head" style="background: #dc2626; color: white;"><h3>⚠️ Permanent Deletion Warning</h3></div>
    <div class="modal__body">
      <p style="margin-bottom: 16px;">You are about to <strong style="color: #dc2626;">permanently delete</strong> the following driver:</p>
      <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
        <p style="margin: 0; font-weight: 600;"><?= e($drv['name']) ?></p>
        <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">ID: <?= e($drv['driver_id']) ?></p>
      </div>
      <p style="margin-bottom: 16px;"><strong style="color: #dc2626;">This action CANNOT be undone!</strong></p>
      <p style="margin-bottom: 0; font-size: 14px; color: #6b7280;">All driver data, applications, and related records will be permanently removed from the system.</p>
    </div>
    <form method="post" action="<?= BASE_URL ?>/driver_list.php?view=archived">
      <input type="hidden" name="action" value="delete_driver">
      <input type="hidden" name="driver_id" value="<?= e($drv['driver_id']) ?>">
      <div class="modal__foot">
        <button type="button" class="btn btn-outline btn-pill" data-close-modal="delete-permanent-<?= e($drv['driver_id']) ?>">Cancel</button>
        <button type="submit" class="btn btn-pill" style="background: #dc2626; color: white;" onclick="return confirm('Are you absolutely sure? This driver will be PERMANENTLY deleted!');">Delete Permanently</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canManage): ?>
<div class="modal-backdrop" id="add-driver-modal">
  <div style="position:fixed; inset:0; z-50; display:flex; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,0.75); overflow-y:auto;">
    <div style="background:white; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid rgba(226,232,240,0.9); width:100%; max-width:700px; overflow:hidden; margin:auto;">
      
      <!-- Modal Header -->
      <div style="padding:20px 28px; background:linear-gradient(to right, #f8fafc, white, #f8fafc); border-bottom:1px solid #e2e8f0; display:flex; align-items:start; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:14px;">
          <!-- Icon Badge -->
          <div style="width:44px; height:44px; border-radius:12px; background:#eff6ff; border:1px solid rgba(191,219,254,0.8); display:flex; align-items:center; justify-content:center; color:#2563eb; box-shadow:0 1px 2px rgba(0,0,0,0.05); flex-shrink:0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <line x1="19" y1="8" x2="19" y2="14" stroke-linecap="round" stroke-linejoin="round"></line>
              <line x1="22" y1="11" x2="16" y2="11" stroke-linecap="round" stroke-linejoin="round"></line>
            </svg>
          </div>
          <div>
            <h2 style="font-size:18px; font-weight:700; color:#0f172a; letter-spacing:-0.01em; margin:0; line-height:1.3;">Add Driver Account</h2>
            <p style="font-size:12px; color:#64748b; margin:4px 0 0 0;">Register a new TODA member and create their driver portal credentials.</p>
          </div>
        </div>
        <!-- Close Button -->
        <button type="button" data-close-modal="add-driver-modal" style="color:#94a3b8; background:transparent; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background 0.15s, color 0.15s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#475569';" onmouseout="this.style.background='transparent'; this.style.color='#94a3b8';">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <form id="add-driver-form" method="post" action="<?= BASE_URL ?>/driver_list.php" style="padding:28px; max-height:calc(88vh - 130px); overflow-y:auto; -webkit-overflow-scrolling:touch;">
        <input type="hidden" name="action" value="add_driver">
        <input type="hidden" name="status" value="-">
        
        <!-- SECTION 1: Driver's Profile -->
        <div style="margin-bottom:24px;">
          <div style="display:flex; align-items:center; gap:8px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:16px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <h3 style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#334155; margin:0;">Driver's Profile</h3>
          </div>

          <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
            <!-- Driver ID -->
            <div>
              <label for="d_id" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">DRIVER ID</label>
              <div style="position:relative;">
                <input type="text" id="d_id" placeholder="Auto-Generated" disabled style="width:100%; background:rgba(241,245,249,0.9); color:#94a3b8; border:1px solid #e2e8f0; border-radius:8px; padding:10px 36px 10px 14px; font-size:12px; font-weight:500; cursor:not-allowed; user-select:none; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                <span style="position:absolute; inset-y:0; right:0; padding-right:10px; display:flex; align-items:center; color:#cbd5e1; pointer-events:none;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 11H5a2 2 0 00-2 2v10h18V13a2 2 0 00-2-2z"></path>
                    <path d="M7 11V7a5 5 0 0110 0v4"></path>
                  </svg>
                </span>
              </div>
              <p style="font-size:10px; color:#94a3b8; margin:4px 0 0 0;">System-assigned upon save</p>
            </div>

            <!-- Full Name -->
            <div>
              <label for="d_name" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                NAME <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <input type="text" id="d_name" name="name" placeholder="Full Name" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
            </div>

            <!-- Birthday -->
            <div>
              <label for="d_birthday" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                BIRTHDAY <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <input type="date" id="d_birthday" name="birthday" max="<?= e(date('Y-m-d')) ?>" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#475569; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 2fr; gap:16px; margin-top:16px;">
            <!-- Age -->
            <div>
              <label for="d_age" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">AGE</label>
              <input type="text" id="d_age" name="age" placeholder="Age" readonly style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
            </div>

            <!-- Contact No. -->
            <div>
              <label for="d_contact" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                CONTACT NO. <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <div style="display:flex; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                <span style="display:inline-flex; align-items:center; padding:0 12px; border-radius-left:8px; border:1px solid #e2e8f0; border-right:0; background:#f1f5f9; color:#64748b; font-size:12px; font-weight:600;">+63</span>
                <input type="text" id="d_contact" name="contact" placeholder="09XX XXX XXXX" required style="flex:1; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:0 8px 8px 0; padding:10px 14px; font-size:12px;" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6'; this.previousElementSibling.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0'; this.previousElementSibling.style.borderColor='#e2e8f0';">
              </div>
            </div>
          </div>
          <p class="field-error" id="err-d_birthday" style="display:none; margin:8px 0 0; font-size:12px; color:#dc2626;">Driver must be at least 18 years old.</p>
        </div>

        <!-- SECTION 2: Address -->
        <div style="margin-bottom:24px; padding-top:4px;">
          <div style="display:flex; align-items:center; gap:8px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:16px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <h3 style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#334155; margin:0;">Address</h3>
          </div>

          <div style="display:grid; grid-template-columns:5fr 3fr 4fr; gap:14px; align-items:end;">
            <!-- House No / Street -->
            <div>
              <label for="d_street" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                HOUSE NO / STREET <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <input type="text" id="d_street" placeholder="House No / Street" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
            </div>

            <!-- Barangay -->
            <div>
              <label for="d_addr_barangay" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                BARANGAY <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <input type="text" id="d_addr_barangay" placeholder="Barangay" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
            </div>

            <!-- Fixed Municipality & Province -->
            <div style="display:flex; gap:8px;">
              <div style="flex:1; text-align:center;">
                <span style="display:block; font-size:10px; color:#94a3b8; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Town</span>
                <div style="width:100%; background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; font-weight:500; border-radius:8px; padding:10px 8px; font-size:12px; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">Bocaue</div>
              </div>
              <div style="flex:1; text-align:center;">
                <span style="display:block; font-size:10px; color:#94a3b8; font-weight:600; text-transform:uppercase; margin-bottom:4px;">Province</span>
                <div style="width:100%; background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; font-weight:500; border-radius:8px; padding:10px 8px; font-size:12px; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">Bulacan</div>
              </div>
            </div>
          </div>

          <!-- Address Jurisdiction Footer -->
          <div style="font-size:11px; color:#64748b; font-weight:500; display:flex; align-items:center; gap:6px; margin-top:12px;">
            <span style="color:#94a3b8;">Address Jurisdiction:</span>
            <span style="font-weight:600; color:#475569; background:#f1f5f9; padding:4px 8px; border-radius:4px; border:1px solid rgba(226,232,240,0.7);">Bocaue, Bulacan</span>
          </div>
          <div class="address-preview" style="display:none;">Address: <span id="d_address_preview">&mdash;</span></div>
          <input type="hidden" id="d_address" name="address">
        </div>

        <!-- SECTION 3: Vehicle's Information -->
        <div style="margin-bottom:24px; padding-top:4px;">
          <div style="display:flex; align-items:center; gap:8px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:16px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
              <path d="M5 17h14v-2.76a1 1 0 00-.5-.87l-6.5-3.73a1 1 0 00-1 0l-6.5 3.73a1 1 0 00-.5.87V17z"></path>
              <circle cx="12" cy="9" r="3"></circle>
            </svg>
            <h3 style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#334155; margin:0;">Vehicle's Information</h3>
          </div>

          <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
            <!-- Body No. -->
            <div>
              <label for="d_body" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                BODY NO. <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <input type="text" id="d_body" name="body_no" placeholder="Body" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#334155; border-radius:8px; padding:10px 14px; font-size:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
            </div>

            <!-- Barangay -->
            <div>
              <label for="d_barangay" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                BARANGAY <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <div style="position:relative;">
                <select id="d_barangay" name="barangay" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#475569; border-radius:8px; padding:10px 32px 10px 14px; font-size:12px; appearance:none; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
                  <option value="" disabled selected>Barangay</option>
                  <?php foreach ($barangays as $b): ?><option value="<?= e($b) ?>"><?= e($b) ?></option><?php endforeach; ?>
                </select>
                <div style="pointer-events:none; position:absolute; inset-y:0; right:0; display:flex; align-items:center; padding:0 10px; color:#64748b; font-size:10px;">
                  <svg width="10" height="10" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Color Code -->
            <div>
              <label for="d_color" style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:6px;">
                COLOR CODE <span style="color:#dc2626; font-weight:900;">*</span>
              </label>
              <div style="position:relative;">
                <select id="d_color" name="color" required style="width:100%; background:rgba(248,250,252,0.7); border:1px solid #e2e8f0; color:#475569; border-radius:8px; padding:10px 32px 10px 14px; font-size:12px; appearance:none; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.02);" onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';" onblur="this.style.background='rgba(248,250,252,0.7)'; this.style.borderColor='#e2e8f0';">
                  <option value="" disabled selected>Color</option>
                  <?php foreach (d('colors') as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                </select>
                <div style="pointer-events:none; position:absolute; inset-y:0; right:0; display:flex; align-items:center; padding:0 10px; color:#64748b; font-size:10px;">
                  <svg width="10" height="10" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SECTION 4: Temporary Password Notice -->
        <div style="border-radius:12px; background:rgba(239,246,255,0.8); border:1px solid #bfdbfe; padding:14px; display:flex; align-items:start; gap:12px; color:#334155; margin-bottom:16px;">
          <div style="width:24px; height:24px; border-radius:6px; background:#2563eb; color:white; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; font-size:10px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <path d="M19 11H5a2 2 0 00-2 2v10h18V13a2 2 0 00-2-2z"></path>
              <path d="M7 11V7a5 5 0 0110 0v4"></path>
            </svg>
          </div>
          <p style="font-size:12px; line-height:1.6; color:#64748b; margin:0;">
            A default password will be generated automatically. The driver can change it after logging in for the first time.
          </p>
        </div>

        <!-- Modal Footer -->
        <div style="padding-top:16px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
          <!-- Cancel Button -->
          <button type="button" data-close-modal="add-driver-modal" style="padding:10px 24px; background:#dc2626; color:white; border:none; font-weight:600; font-size:12px; letter-spacing:0.02em; border-radius:8px; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.15s; display:flex; align-items:center; gap:6px;" onmouseover="this.style.background='#b91c1c'; this.style.transform='scale(0.98)';" onmouseout="this.style.background='#dc2626'; this.style.transform='scale(1)';">
            <span>Cancel</span>
          </button>

          <!-- Add Account Button -->
          <button type="button" data-open-modal="confirm-add-driver" style="padding:10px 24px; background:#0B132B; color:white; border:none; font-weight:600; font-size:12px; letter-spacing:0.02em; border-radius:8px; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.1); transition:all 0.15s; display:flex; align-items:center; gap:8px;" onmouseover="this.style.background='#1C2541'; this.style.boxShadow='0 6px 8px rgba(0,0,0,0.15)'; this.style.transform='scale(0.98)';" onmouseout="this.style.background='#0B132B'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'; this.style.transform='scale(1)';">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M22 12h-6m3-3v6" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <span>Add Account</span>
          </button>
        </div>
      </form>
    </div>
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

  // Server-side search with debounce
  var searchTimeout;
  if (search) {
    search.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(function() {
        search.form.submit();
      }, 500); // Wait 500ms after user stops typing
    });
  }

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

  // Server-side filtering - redirect with URL parameters
  if (filterApply) {
    filterApply.addEventListener('click', function () {
      var url = new URL(window.location.href);
      var barangayVal = filterBarangay ? filterBarangay.value : '';
      var statusVal = filterStatus ? filterStatus.value : '';
      
      if (barangayVal) {
        url.searchParams.set('barangay', barangayVal);
      } else {
        url.searchParams.delete('barangay');
      }
      
      if (statusVal) {
        url.searchParams.set('status', statusVal);
      } else {
        url.searchParams.delete('status');
      }
      
      // Reset to page 1 when filtering
      url.searchParams.delete('page');
      
      window.location.href = url.toString();
    });
  }

  if (filterClear) {
    filterClear.addEventListener('click', function () {
      var url = new URL(window.location.href);
      url.searchParams.delete('barangay');
      url.searchParams.delete('status');
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }
  
  // Update filter count badge on page load
  var activeCount = <?= ($filterBarangay !== '' ? 1 : 0) + ($filterStatus !== '' ? 1 : 0) ?>;
  if (filterCount) {
    filterCount.textContent = activeCount;
    filterCount.style.display = activeCount > 0 ? 'inline-flex' : 'none';
  }
  if (filterToggle && activeCount > 0) {
    filterToggle.classList.add('has-active');
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

  // Body number validation for Add Driver modal
  var addBodyNo = document.getElementById('d_body');
  var addBodyErr = null;
  var addBodyCheckTimeout = null;
  
  if (addBodyNo) {
    // Create error message element
    addBodyErr = document.createElement('p');
    addBodyErr.style.display = 'none';
    addBodyErr.style.margin = '4px 0 0';
    addBodyErr.style.fontSize = '12px';
    addBodyErr.style.color = '#dc2626';
    addBodyErr.style.fontWeight = '500';
    addBodyErr.textContent = 'Body number is already in use';
    addBodyNo.parentNode.appendChild(addBodyErr);
    
    addBodyNo.addEventListener('input', function() {
      clearTimeout(addBodyCheckTimeout);
      var bodyNo = this.value.trim();
      
      if (!bodyNo) {
        addBodyErr.style.display = 'none';
        addBodyNo.style.borderColor = '#e2e8f0';
        return;
      }
      
      addBodyCheckTimeout = setTimeout(function() {
        fetch('<?= BASE_URL ?>/driver_list.php?action=check_body_number&body_no=' + encodeURIComponent(bodyNo))
          .then(function(res) { return res.json(); })
          .then(function(data) {
            if (data.available) {
              addBodyErr.style.display = 'none';
              addBodyNo.style.borderColor = '#10b981';
              addBodyNo.dataset.available = 'true';
            } else {
              addBodyErr.style.display = 'block';
              addBodyErr.textContent = data.message || 'Body number is already in use';
              addBodyNo.style.borderColor = '#dc2626';
              addBodyNo.dataset.available = 'false';
            }
          })
          .catch(function(err) {
            console.error('Body number check error:', err);
          });
      }, 500);
    });
  }
  
  // Prevent form submission if body number is not available
  if (addAccountBtn && addBodyNo) {
    var originalAddClick = addAccountBtn.onclick;
    addAccountBtn.addEventListener('click', function(e) {
      if (addBodyNo.dataset.available === 'false') {
        e.preventDefault();
        e.stopImmediatePropagation();
        addBodyNo.focus();
        addBodyErr.style.display = 'block';
        return false;
      }
    });
  }

  // Body number validation for Edit Driver modals
  document.querySelectorAll('[id^="e_body_"]').forEach(function(editBodyInput) {
    var driverId = editBodyInput.id.replace('e_body_', '');
    var originalBodyNo = editBodyInput.value;
    var editBodyErr = null;
    var editBodyCheckTimeout = null;
    
    // Create error message element
    editBodyErr = document.createElement('p');
    editBodyErr.style.display = 'none';
    editBodyErr.style.margin = '4px 0 0';
    editBodyErr.style.fontSize = '11px';
    editBodyErr.style.color = '#dc2626';
    editBodyErr.style.fontWeight = '500';
    editBodyErr.textContent = 'Body number is already in use';
    editBodyInput.parentNode.appendChild(editBodyErr);
    
    editBodyInput.addEventListener('input', function() {
      clearTimeout(editBodyCheckTimeout);
      var bodyNo = this.value.trim();
      
      // If unchanged from original, it's valid
      if (bodyNo === originalBodyNo) {
        editBodyErr.style.display = 'none';
        editBodyInput.style.borderColor = '';
        editBodyInput.dataset.available = 'true';
        return;
      }
      
      if (!bodyNo) {
        editBodyErr.style.display = 'none';
        editBodyInput.style.borderColor = '';
        return;
      }
      
      editBodyCheckTimeout = setTimeout(function() {
        fetch('<?= BASE_URL ?>/driver_list.php?action=check_body_number&body_no=' + encodeURIComponent(bodyNo) + '&current_driver_id=' + encodeURIComponent(driverId))
          .then(function(res) { return res.json(); })
          .then(function(data) {
            if (data.available) {
              editBodyErr.style.display = 'none';
              editBodyInput.style.borderColor = '#10b981';
              editBodyInput.dataset.available = 'true';
            } else {
              editBodyErr.style.display = 'block';
              editBodyErr.textContent = data.message || 'Body number is already in use';
              editBodyInput.style.borderColor = '#dc2626';
              editBodyInput.dataset.available = 'false';
            }
          })
          .catch(function(err) {
            console.error('Body number check error:', err);
          });
      }, 500);
    });
    
    // Prevent opening confirmation modal if body number is not available
    var confirmBtn = document.querySelector('[data-open-modal="confirm-update-' + driverId + '"]');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function(e) {
        if (editBodyInput.dataset.available === 'false') {
          e.preventDefault();
          e.stopImmediatePropagation();
          editBodyInput.focus();
          editBodyErr.style.display = 'block';
          return false;
        }
      });
    }
  });

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