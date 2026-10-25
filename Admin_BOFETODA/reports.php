<?php
require_once __DIR__ . '/config.php';
require_role('president');

$allBarangayNames = array_keys(d('barangays'));

$allApps = d('applications');

$dataYears = [];
foreach ($allApps as $a) {
    $ts = strtotime($a['submitted'] ?? '');
    if ($ts) {
        $dataYears[] = (int) date('Y', $ts);
    }
}

$currentYear  = (int) date('Y');
$earliestYear = $dataYears ? min($currentYear - 3, min($dataYears)) : $currentYear - 3;
$availableYears = range($currentYear, $earliestYear, -1);

$filterCycle    = $_GET['cycle'] ?? '';
$filterBarangay = $_GET['barangay'] ?? '';

if ($filterCycle !== '' && in_array((int) $filterCycle, $availableYears, true)) {
    $apps = array_filter($allApps, function ($a) use ($filterCycle) {
        $ts = strtotime($a['submitted'] ?? '');
        return $ts && date('Y', $ts) === $filterCycle;
    });
} else {
    $apps = d('applications_current_cycle');
}

if ($filterBarangay !== '') {
    $apps = array_filter($apps, fn($a) => $a['barangay'] === $filterBarangay);
}
$apps = array_values($apps);

$uniqueApplicants = [];
foreach ($apps as $a) {
    if (!isset($uniqueApplicants[$a['driver_id']])) {
        $uniqueApplicants[$a['driver_id']] = $a;
    }
}
$totalApplicants = count($uniqueApplicants);

$approvedCount   = count(array_filter($apps, fn($a) => $a['status'] === 'Approved'));
$rejectedCount   = count(array_filter($apps, fn($a) => $a['status'] === 'Rejected'));

// Pagination for audit trail tables
$activityPage = max(1, (int) ($_GET['activity_page'] ?? 1));
$accessPage = max(1, (int) ($_GET['access_page'] ?? 1));
$perPage = 10;

$allActivityLogs = d('logs');
$allAccessLogs = d('access_logs');

$paginatedActivity = paginate_array($allActivityLogs, $activityPage, $perPage);
$paginatedAccess = paginate_array($allAccessLogs, $accessPage, $perPage);

$activeDrivers = d('drivers');
if ($filterBarangay !== '') {
    $activeDrivers = array_values(array_filter($activeDrivers, fn($d) => $d['barangay'] === $filterBarangay));
}

$unrenewedCount = count(array_filter($activeDrivers, fn($d) => $d['status'] !== 'Approved Renewal'));

$barangays = array_fill_keys($allBarangayNames, 0);
foreach ($uniqueApplicants as $a) {
    if (isset($barangays[$a['barangay']])) {
        $barangays[$a['barangay']]++;
    }
}
arsort($barangays);
$maxCount = $barangays ? max($barangays) : 0;

function filter_qs(): string {
    $qs = array_filter([
        'cycle'    => $_GET['cycle'] ?? '',
        'barangay' => $_GET['barangay'] ?? '',
    ], fn($v) => $v !== '');
    return $qs ? '&amp;' . http_build_query($qs) : '';
}

function report_date_cell($value): string {
    if (!is_string($value) || $value === '') {
        return (string) $value;
    }
    $ts = strtotime($value);
    return $ts !== false ? date('F j, Y', $ts) : $value;
}

function csv_cell($value): string {
    if (!is_array($value)) {
        return (string) $value;
    }
    $parts = [];
    foreach ($value as $k => $v) {
        if (is_array($v)) {
            $v = csv_cell($v);
        } elseif (is_bool($v)) {
            $v = $v ? 'yes' : 'no';
        } elseif ($v === null) {
            $v = '';
        }
        $parts[] = is_int($k) ? $v : "$k: $v";
    }
    return implode('; ', $parts);
}


function report_tile_rows(string $key, array $uniqueApplicants, array $apps, array $activeDrivers): ?array {
    switch ($key) {
        case 'total':
            return array_values($uniqueApplicants);
        case 'approved':
            return array_values(array_filter($apps, fn($a) => $a['status'] === 'Approved'));
        case 'rejected':
            return array_values(array_filter($apps, fn($a) => $a['status'] === 'Rejected'));
        case 'unrenewed':
            return array_values(array_filter($activeDrivers, fn($d) => $d['status'] !== 'Approved Renewal'));
        case 'activity':
            return array_values(d('logs'));
        case 'access':
            return array_values(d('access_logs'));
        default:
            return null;
    }
}

function report_columns(string $key): array {
    switch ($key) {
        case 'total':
            return [
                'app_no'    => 'Applicant No.',
                'driver_id' => 'Driver ID',
                'name'      => 'Driver Name',
                'barangay'  => 'Barangay',
                'body_no'   => 'Body No.',
                'submitted' => 'Date Submitted',
            ];
        case 'approved':
            return [
                'app_no'    => 'Applicant No.',
                'driver_id' => 'Driver ID',
                'name'      => 'Driver Name',
                'barangay'  => 'Barangay',
                'body_no'   => 'Body No.',
                'submitted' => 'Date Submitted',
                'approved'  => 'Date Approved',
            ];
        case 'rejected':
            return [
                'app_no'    => 'Applicant No.',
                'driver_id' => 'Driver ID',
                'name'      => 'Driver Name',
                'barangay'  => 'Barangay',
                'body_no'   => 'Body No.',
                'submitted' => 'Date Submitted',
                'rejected'  => 'Date Rejected',
            ];
        case 'unrenewed':
            return [
                'driver_id' => 'Driver ID',
                'name'      => 'Driver Name',
                'barangay'  => 'Barangay',
                'body_no'   => 'Body No.',
                'status'    => 'Status',
            ];
        case 'activity':
            return [
                'action'    => 'Action',
                'driver_id' => 'Driver ID',
                'role'      => 'Role',
                'date'      => 'Date',
                'time'      => 'Time',
            ];
        case 'access':
            return [
                'action' => 'Action',
                'role'   => 'Role',
                'date'   => 'Date',
                'time'   => 'Time',
            ];
        default:
            return [];
    }
}

$reportTitles = [
    'total'     => 'TOTAL NUMBER OF APPLICANTS REPORT',
    'approved'  => 'APPROVED APPLICATIONS REPORT',
    'rejected'  => 'REJECTED APPLICATIONS REPORT',
    'unrenewed' => 'UNRENEWED DRIVER RECORDS REPORT',
    'activity'  => 'AUDIT TRAIL — ACTIVITY LOG REPORT',
    'access'    => 'AUDIT TRAIL — ACCESS LOG REPORT',
];

if (isset($_GET['print']) && $_GET['print'] === '1') {
    $tileKey = $_GET['tile'] ?? null;
    $tileRows = $tileKey !== null ? report_tile_rows($tileKey, $uniqueApplicants, $apps, $activeDrivers) : null;
    if ($tileKey !== null && $tileRows === null) {
        http_response_code(400);
        exit('Unknown tile.');
    }
    $columns   = $tileKey !== null ? report_columns($tileKey) : ['barangay' => 'Barangay', 'count' => 'Applicants This Cycle'];
    $title     = $tileKey !== null ? $reportTitles[$tileKey] : 'MEMBER DISTRIBUTION BY BARANGAY REPORT';
    $president = db_get_president_name();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
<style>
  :root{ --border:#000; }
  *{box-sizing:border-box;}
  body{ font-family:"Times New Roman",Times,serif; color:#000; margin:0; }

  table.rp-doc{ width:100%; border-collapse:collapse; font-size:10.5pt; }
  table.rp-doc th, table.rp-doc td{ text-align:center; padding:7px 8px; border:1px solid #000; }
  table.rp-doc th{ font-weight:700; background:#f0f0f0; }
  table.rp-doc td.rp-left{ text-align:left; }
  table.rp-doc tr{ break-inside:avoid; }

  .rp-letterhead{ display:flex; align-items:center; justify-content:center; gap:18px; padding:8px 0 6px; }
  .rp-letterhead img{ width:70px; height:70px; object-fit:contain; flex-shrink:0; }
  .rp-letterhead .rp-org{ text-align:center; }
  .rp-letterhead .rp-org p{ font-weight:700; font-size:10pt; line-height:1.25; margin:0; }
  .rp-letterhead .rp-org h1{ font-weight:700; font-size:16pt; margin:3px 0; letter-spacing:.3px; }
  .rp-letterhead .rp-org .rp-secreg{ font-weight:700; font-size:10pt; margin:0; }
  .rp-rule{ border:0; border-top:2px solid #000; width:100%; margin:4px 0 6px; }

  .rp-title{ text-align:center; font-weight:700; font-size:13pt; text-transform:uppercase; margin:0 0 4px; }

  .rp-signature{ text-align:center; padding:4px 0 2px; }
  .rp-signature .rp-sig-name{ display:inline-block; border-top:1px solid #000; padding-top:4px; min-width:200px; }
  .rp-signature p{ font-weight:700; font-size:10.5pt; margin:2px 0; }

  .rp-generated{ font-size:9pt; color:#555; text-align:right; margin-bottom:6px; font-family:Arial,sans-serif; }

  .rp-page-header{ position:running(rpPageHeader); }
  .rp-page-footer{ position:running(rpPageFooter); }

  @page {
    size: A4;
    margin: 52mm 15mm 28mm 15mm;

    @top-center{ content: element(rpPageHeader); }
    @bottom-center{ content: element(rpPageFooter); }
  }

  @media screen{
    body{ margin: 24px; }
    .no-print{ margin-top:24px; font-size:11px; color:#6b7280; font-family:Arial,sans-serif; }
  }
  @media print{
    .no-print{ display:none; }
  }
</style>
</head>
<body>

  <div class="rp-page-header">
    <div class="rp-letterhead">
      <img src="<?= BASE_URL ?>/LOGO_BOCAUE.png" alt="Bayan ng Bocaue Seal">
      <div class="rp-org">
        <p>Bocaue Bulacan Tricycle Operators and<br>Drivers' Association Transport Group Inc.</p>
        <h1>BOCAUE TODA INC.</h1>
        <p class="rp-secreg">Sec. Reg. No. 2025110225683-12</p>
      </div>
      <img src="<?= BASE_URL ?>/LOGO_TODA.png" alt="Bocaue Federation of Tricycle Operators and Drivers Association Seal">
    </div>
    <hr class="rp-rule">
    <div class="rp-title"><?= e($title) ?></div>
    <?php if ($filterCycle !== '' || $filterBarangay !== ''): ?>
      <div style="text-align:center; font-size:9.5pt; margin:2px 0 4px;">
        <?php
          $scopeParts = [];
          if ($filterCycle !== '' && in_array((int) $filterCycle, $availableYears, true)) {
              $scopeParts[] = 'Renewal Year ' . $filterCycle . '-' . ($filterCycle + 1);
          }
          if ($filterBarangay !== '') {
              $scopeParts[] = 'Barangay ' . $filterBarangay;
          }
          echo e(implode(' · ', $scopeParts));
        ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="rp-page-footer">
    <hr class="rp-rule" style="margin:0 0 8px;">
    <div class="rp-signature">
      <p><?= $president !== '' ? e(mb_strtoupper($president)) : '&nbsp;' ?></p>
      <p><?= e(APP_NAME) ?> President</p>
    </div>
  </div>

  <div class="rp-generated">Generated <?= e(date('F j, Y g:i A')) ?></div>

  <table class="rp-doc">
    <thead>
      <tr>
        <th style="width:34px;">No.</th>
        <?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($tileKey !== null): ?>
        <?php if (!$tileRows): ?>
          <tr><td colspan="<?= count($columns) + 1 ?>">No records found.</td></tr>
        <?php else: foreach ($tileRows as $i => $r): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <?php foreach (array_keys($columns) as $c): ?>
              <td class="rp-left"><?= e(in_array($c, ['submitted', 'approved', 'rejected'], true) ? report_date_cell($r[$c] ?? '') : csv_cell($r[$c] ?? '')) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; endif; ?>
      <?php else: ?>
        <?php if (!$barangays): ?>
          <tr><td colspan="3">No applications yet this cycle.</td></tr>
        <?php else: $i = 0; foreach ($barangays as $name => $count): $i++; ?>
          <tr><td><?= $i ?></td><td class="rp-left"><?= e($name) ?></td><td><?= $count ?></td></tr>
        <?php endforeach; endif; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <p class="no-print">
    Your browser's print dialog should have opened automatically — choose "Save as PDF" as the destination.
    If it didn't open, use your browser's Print command (Ctrl/Cmd+P).
  </p>

<script>
  window.PagedConfig = {
    auto: true,
    after: () => { window.print(); }
  };
</script>
<script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
</body>
</html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.report-tile__download { text-decoration:none; }

.rp-page-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:22px; flex-wrap:wrap; }
.rp-page-title{ font-size:30px; font-weight:800; color:var(--navy); line-height:1.1; }
.rp-page-sub{ font-size:13.5px; color:var(--text-muted); margin-top:5px; }

.rp-filters{ display:flex; gap:12px; flex-wrap:wrap; }
.rp-filters select{
  appearance:none; -webkit-appearance:none;
  min-width:190px; padding:11px 38px 11px 15px;
  border:1px solid var(--border); border-radius:8px; background:#fff;
  font-family:inherit; font-size:13.5px; color:var(--text); cursor:pointer;
  background-image:url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 12px center;
}
.rp-filters select:focus{ outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }

.rp-audit-export{
  display:inline-flex; align-items:center; justify-content:center;
  width:30px; height:30px; border:1px solid var(--border); border-radius:50%;
  color:var(--text-muted); text-decoration:none; flex-shrink:0; transition:all .15s;
}
.rp-audit-export:hover{ color:var(--navy); border-color:var(--navy); background:var(--gray-bg); }
</style>
</head>
<body>
<?php render_sidebar('reports'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Reports', 'Generate and export compliance reports'); ?>

  <div class="page">

    <div class="rp-page-head">
      <div>
        <h2 class="rp-page-title">Reports</h2>
        <p class="rp-page-sub">Generate and export compliance reports</p>
      </div>
      <form method="get" class="rp-filters" id="rpFilters">
        <select name="cycle" onchange="document.getElementById('rpFilters').submit()">
          <option value="" <?= $filterCycle === '' ? 'selected' : '' ?>>Current Year</option>
          <?php foreach ($availableYears as $y): ?>
            <option value="<?= e($y) ?>" <?= (int) $filterCycle === $y ? 'selected' : '' ?>><?= e($y) ?>-<?= e($y + 1) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="barangay" onchange="document.getElementById('rpFilters').submit()">
          <option value="">All Barangay</option>
          <?php foreach ($allBarangayNames as $bn): ?>
            <option value="<?= e($bn) ?>" <?= $filterBarangay === $bn ? 'selected' : '' ?>><?= e($bn) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="report-grid">
      <div class="report-tile">
        <div class="report-tile__head">
          <h4>Total Number of Applicants</h4>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=total<?= filter_qs() ?>" target="_blank" class="report-tile__download" title="Download PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--navy);margin:2px 0 6px;"><?= $totalApplicants ?></div>
        <p>Shows the total number of people who applied in the current cycle.</p>
      </div>
      <div class="report-tile">
        <div class="report-tile__head">
          <h4>Approved Applications</h4>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=approved<?= filter_qs() ?>" target="_blank" class="report-tile__download" title="Download PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--green);margin:2px 0 6px;"><?= $approvedCount ?></div>
        <p>Displays all applications that were accepted in the current cycle.</p>
      </div>
      <div class="report-tile">
        <div class="report-tile__head">
          <h4>Rejected Applications</h4>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=rejected<?= filter_qs() ?>" target="_blank" class="report-tile__download" title="Download PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--red);margin:2px 0 6px;"><?= $rejectedCount ?></div>
        <p>Shows applications that were denied in the current cycle.</p>
      </div>
      <div class="report-tile">
        <div class="report-tile__head">
          <h4>Unrenewed Driver Record</h4>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=unrenewed<?= filter_qs() ?>" target="_blank" class="report-tile__download" title="Download PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div style="font-size:28px;font-weight:800;color:var(--orange);margin:2px 0 6px;"><?= $unrenewedCount ?></div>
        <p>Provides a record of drivers who have not completed their renewal, serving as a reference for monitoring and follow-up.</p>
      </div>
    </div>

    <div style="margin:22px 0 14px;">
      <h3 style="font-size:20px; color:var(--navy); font-weight:800;">Audit Trail</h3>
      <p style="font-size:12.5px; color:var(--text-muted); margin-top:2px;">Officer action for accountability</p>
    </div>

    <div class="history-grid">
      <div class="card">
        <div class="card__head">
          <h3>Activity</h3>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=activity<?= filter_qs() ?>" target="_blank" class="rp-audit-export" title="Download PDF">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div class="card__body">
          <div class="table-wrap">
            <table class="registry">
              <thead><tr><th>Action</th><th>Driver ID</th><th>Role</th><th>Date</th><th>Time</th></tr></thead>
              <tbody>
                <?php foreach ($paginatedActivity['items'] as $log): ?>
                  <tr>
                    <td><?= e($log['action']) ?></td>
                    <td class="id-cell"><?= e($log['driver_id']) ?></td>
                    <td><?= e($log['role']) ?></td>
                    <td><?= e($log['date']) ?></td>
                    <td><?= e($log['time']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php 
          $activityUrl = BASE_URL . '/reports.php?activity_page=';
          if ($filterCycle !== '') $activityUrl .= '&cycle=' . urlencode($filterCycle);
          if ($filterBarangay !== '') $activityUrl .= '&barangay=' . urlencode($filterBarangay);
          // Remove the trailing =
          $activityUrl = rtrim($activityUrl, '=');
          render_pagination($paginatedActivity['page'], $paginatedActivity['total_pages'], $activityUrl); 
          ?>
        </div>
      </div>

      <div class="card">
        <div class="card__head">
          <h3>Access</h3>
          <a href="<?= BASE_URL ?>/reports.php?print=1&amp;tile=access<?= filter_qs() ?>" target="_blank" class="rp-audit-export" title="Download PDF">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
          </a>
        </div>
        <div class="card__body">
          <div class="table-wrap">
            <table class="registry">
              <thead><tr><th>Action</th><th>Role</th><th>Date</th><th>Time</th></tr></thead>
              <tbody>
                <?php foreach ($paginatedAccess['items'] as $log): ?>
                  <tr>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['role']) ?></td>
                    <td><?= e($log['date']) ?></td>
                    <td><?= e($log['time']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php 
          $accessUrl = BASE_URL . '/reports.php?access_page=';
          if ($filterCycle !== '') $accessUrl .= '&cycle=' . urlencode($filterCycle);
          if ($filterBarangay !== '') $accessUrl .= '&barangay=' . urlencode($filterBarangay);
          // Remove the trailing =
          $accessUrl = rtrim($accessUrl, '=');
          render_pagination($paginatedAccess['page'], $paginatedAccess['total_pages'], $accessUrl); 
          ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php render_shared_script(); ?>
</body>
</html>