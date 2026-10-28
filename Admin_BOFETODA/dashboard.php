<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$role    = $officer['role'] ?? 'secretary';

$apps    = d('applications_current_cycle');
$drivers = d('drivers');

$total    = count($apps);
$pending  = array_filter($apps, fn($a) => $a['status'] === 'Pending'); // Need validation
$forApproval = array_filter($apps, fn($a) => $a['status'] === 'For Approval'); // Ready for president
$approved = array_filter($apps, fn($a) => $a['status'] === 'Approved');
$rejected = array_filter($apps, fn($a) => $a['status'] === 'Rejected');
$validated = array_filter($apps, fn($a) => $a['status'] === 'Validated');

$totalDrivers     = count($drivers);
$renewedDrivers = count(array_filter($drivers, fn($d) => $d['status'] === 'Approved Renewal'));
$unrenewedDrivers = $totalDrivers - $renewedDrivers;

$approvalRate = $totalDrivers > 0 ? round(($renewedDrivers / $totalDrivers) * 100) : 0;
$completionRate = $totalDrivers > 0 ? round(($total / $totalDrivers) * 100) : 0;
$rejectionRate = $total > 0 ? round((count($rejected) / $total) * 100) : 0;

// All items needing review (pending + for approval)
$needsReview = count($pending) + count($forApproval);

// Calculate trends (mock data for now - in production, compare with previous cycle)
$trends = [
    'applications' => '+12%',
    'approval_rate' => '+5%',
    'completion' => '+8%',
    'avg_processing' => '-2 days'
];

// Action items - things requiring immediate attention
$actionItems = [];

// Secretary needs to validate these
if (count($pending) > 0) {
    $actionItems[] = [
        'type' => 'warning',
        'title' => count($pending) . ' applications need validation',
        'action' => 'Validate now',
        'link' => BASE_URL . '/validation_tools.php'
    ];
}

// President needs to approve these (after validation)
if (count($forApproval) > 0) {
    $actionItems[] = [
        'type' => 'info',
        'title' => count($forApproval) . ' applications ready for final approval',
        'action' => 'Review now',
        'link' => BASE_URL . '/approval_workflow.php'
    ];
}
if ($unrenewedDrivers > 0) {
    $actionItems[] = [
        'type' => 'alert',
        'title' => $unrenewedDrivers . ' drivers have not renewed yet',
        'action' => 'View drivers',
        'link' => BASE_URL . '/driver_list.php'
    ];
}

// Risk assessment
$risks = [];
if ($rejectionRate > 15) {
    $risks[] = [
        'level' => 'high',
        'message' => "High rejection rate ({$rejectionRate}%) - Review common rejection reasons",
        'recommendation' => 'Consider providing clearer submission guidelines to drivers'
    ];
}
if ($completionRate < 50 && $unrenewedDrivers > 5) {
    $risks[] = [
        'level' => 'medium',
        'message' => "Only {$completionRate}% of drivers have submitted applications",
        'recommendation' => 'Send bulk reminders to unrenewed drivers'
    ];
}
if (count($pending) > 10 || count($forApproval) > 10) {
    $risks[] = [
        'level' => 'medium',
        'message' => 'Processing backlog detected - ' . $needsReview . ' applications need review',
        'recommendation' => 'Allocate additional review time or delegate validation tasks'
    ];
}

$barangayNames = array_keys(d('barangays'));

function by_barangay(array $rows, array $names): array {
    $out = array_fill_keys($names, 0);
    foreach ($rows as $r) {
        if (isset($out[$r['barangay']])) {
            $out[$r['barangay']]++;
        }
    }
    return $out;
}

$submissionCounts = by_barangay($apps, $barangayNames);
$maxSubmission    = $submissionCounts ? max($submissionCounts) : 0;

$page_heading = $role === 'president' ? "President's Dashboard" : "Secretary's Dashboard";
$page_sub     = "Decision Support & Performance Overview";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.kpi-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px; }
.kpi-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:18px 20px; box-shadow:var(--shadow-card); }
.kpi-card__label{ font-size:11.5px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); margin-bottom:10px; }
.kpi-card__value{ font-size:32px; font-weight:800; color:var(--navy); line-height:1; margin-bottom:8px; }
.kpi-card__trend{ font-size:12px; font-weight:600; display:flex; align-items:center; gap:4px; }
.kpi-card__trend.is-up{ color:var(--green); }
.kpi-card__trend.is-down{ color:var(--red); }
.kpi-card__trend.is-neutral{ color:var(--text-muted); }

.action-section{ margin-bottom:20px; }
.action-header{ font-size:17px; font-weight:700; color:var(--navy); margin-bottom:12px; }
.action-list{ display:flex; flex-direction:column; gap:10px; }
.action-item{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:16px 18px; box-shadow:var(--shadow-card); display:flex; align-items:center; justify-content:space-between; gap:16px; }
.action-item.is-warning{ border-left:4px solid var(--orange); }
.action-item.is-alert{ border-left:4px solid var(--red); }
.action-item.is-info{ border-left:4px solid #2563eb; }
.action-item__content{ flex:1; }
.action-item__title{ font-size:14px; font-weight:600; color:var(--text); margin-bottom:2px; }
.action-item__link{ font-size:13px; font-weight:600; color:var(--navy); text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:6px; background:var(--gray-bg); transition:background .15s; }
.action-item__link:hover{ background:var(--navy); color:#fff; }

.progress-section{ margin-bottom:20px; }
.progress-grid{ display:grid; grid-template-columns:1fr; gap:16px; }
.progress-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-card); }
.progress-card--full{ grid-column:1 / -1; }
.progress-card__title{ font-size:15px; font-weight:700; color:var(--navy); margin-bottom:16px; }
.progress-items-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
.progress-item{ margin-bottom:0; }
.progress-item:last-child{ margin-bottom:0; }
.progress-item__head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.progress-item__label{ font-size:13px; font-weight:600; color:var(--text); }
.progress-item__value{ font-size:13px; font-weight:700; color:var(--navy); }
.progress-item__bar{ height:8px; background:var(--gray-bg); border-radius:999px; overflow:hidden; }
.progress-item__fill{ height:100%; background:var(--navy); border-radius:999px; transition:width .3s; }
.progress-item__fill.is-success{ background:var(--green); }
.progress-item__fill.is-warning{ background:var(--orange); }
.progress-item__fill.is-danger{ background:var(--red); }

.risk-section{ margin-bottom:20px; }
.risk-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-card); }
.risk-card__title{ font-size:15px; font-weight:700; color:var(--navy); margin-bottom:16px; }
.risk-item{ padding:14px 16px; border-radius:8px; margin-bottom:12px; border-left:4px solid; }
.risk-item:last-child{ margin-bottom:0; }
.risk-item.is-high{ background:#fef2f2; border-color:var(--red); }
.risk-item.is-medium{ background:#fff7ed; border-color:var(--orange); }
.risk-item.is-low{ background:#f0fdf4; border-color:var(--green); }
.risk-item__message{ font-size:13px; font-weight:600; color:var(--text); margin-bottom:6px; }
.risk-item__recommendation{ font-size:12px; color:var(--text-muted); display:flex; align-items:flex-start; gap:6px; }
.risk-item__recommendation::before{ content:"💡"; flex-shrink:0; }

.chart-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-card); margin-bottom:20px; }
.chart-card__title{ font-size:15px; font-weight:700; color:var(--navy); margin-bottom:16px; }
.sub-chart{ display:flex; align-items:flex-end; gap:10px; overflow-x:auto; padding-top:10px; }
.sub-col{ flex:1; min-width:70px; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:6px; }
.sub-col__bar{ width:20px; background:var(--navy); border-radius:3px 3px 0 0; transition:background .15s; }
.sub-col__bar:hover{ background:var(--navy-light); }
.sub-col__val{ font-size:12px; font-weight:700; color:var(--navy); }
.sub-col__label{ font-size:10.5px; color:var(--text-muted); text-align:center; white-space:nowrap; }

.empty-insights{ text-align:center; padding:40px 20px; color:var(--text-muted); }
.empty-insights__icon{ font-size:48px; margin-bottom:12px; opacity:0.3; }
.empty-insights__text{ font-size:14px; }

@media (max-width:1100px){ 
    .kpi-grid{ grid-template-columns:repeat(2,1fr); }
    .progress-grid{ grid-template-columns:1fr; }
    .progress-items-grid{ grid-template-columns:1fr; gap:16px; }
}
@media (max-width:640px){ 
    .kpi-grid{ grid-template-columns:1fr; }
}
</style>
</head>
<body>
<?php render_sidebar('dashboard'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Dashboard', $page_sub); ?>

  <div class="page">

    <div style="margin-bottom:24px;">
      <h2 style="font-size:26px; font-weight:800; color:var(--navy);"><?= e($page_heading) ?></h2>
      <p style="font-size:13px; color:var(--text-muted); margin-top:4px;">Real-time insights and actionable intelligence for decision-making</p>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-card__label">Total Applications</div>
        <div class="kpi-card__value"><?= $total ?></div>
        <div class="kpi-card__trend is-up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
          <?= $trends['applications'] ?> vs last cycle
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Approval Rate</div>
        <div class="kpi-card__value"><?= $approvalRate ?>%</div>
        <div class="kpi-card__trend is-up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
          <?= $trends['approval_rate'] ?> vs last cycle
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Completion Rate</div>
        <div class="kpi-card__value"><?= $completionRate ?>%</div>
        <div class="kpi-card__trend is-up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
          <?= $trends['completion'] ?> vs last cycle
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Avg. Processing Time</div>
        <div class="kpi-card__value">3.5d</div>
        <div class="kpi-card__trend is-up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"></polyline></svg>
          <?= $trends['avg_processing'] ?> vs last cycle
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Pending Applications</div>
        <div class="kpi-card__value"><?= count($forApproval) ?></div>
        <div class="kpi-card__trend is-neutral">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          For approval
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Validated Applications</div>
        <div class="kpi-card__value"><?= count($validated) + count($pending) ?></div>
        <div class="kpi-card__trend is-neutral">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
          Documents verified
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Approved Applications</div>
        <div class="kpi-card__value"><?= count($approved) ?></div>
        <div class="kpi-card__trend is-up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Ready for renewal
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-card__label">Rejected Applications</div>
        <div class="kpi-card__value"><?= count($rejected) ?></div>
        <div class="kpi-card__trend is-down">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
          <?= $total > 0 ? round((count($rejected) / $total) * 100) : 0 ?>% rejection rate
        </div>
      </div>
    </div>

    <!-- Action Items -->
    <?php if (count($actionItems) > 0): ?>
    <div class="action-section">
      <h3 class="action-header">⚡ Action Required</h3>
      <div class="action-list">
        <?php foreach ($actionItems as $item): ?>
        <div class="action-item is-<?= $item['type'] ?>">
          <div class="action-item__content">
            <div class="action-item__title"><?= e($item['title']) ?></div>
          </div>
          <a href="<?= $item['link'] ?>" class="action-item__link">
            <?= e($item['action']) ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Progress Tracking -->
    <div class="progress-section">
      <div class="progress-grid">
        <div class="progress-card progress-card--full">
          <h3 class="progress-card__title">Renewal Progress</h3>
          
          <div class="progress-items-grid">
            <div class="progress-item">
              <div class="progress-item__head">
                <span class="progress-item__label">Driver Participation</span>
                <span class="progress-item__value"><?= $total ?> / <?= $totalDrivers ?></span>
              </div>
              <div class="progress-item__bar">
                <div class="progress-item__fill <?= $completionRate > 75 ? 'is-success' : ($completionRate > 50 ? 'is-warning' : 'is-danger') ?>" style="width:<?= $completionRate ?>%"></div>
              </div>
            </div>

            <div class="progress-item">
              <div class="progress-item__head">
                <span class="progress-item__label">Approved Renewals</span>
                <span class="progress-item__value"><?= count($approved) ?> / <?= $total ?></span>
              </div>
              <div class="progress-item__bar">
                <div class="progress-item__fill is-success" style="width:<?= $total > 0 ? round((count($approved) / $total) * 100) : 0 ?>%"></div>
              </div>
            </div>

            <div class="progress-item">
              <div class="progress-item__head">
                <span class="progress-item__label">Pending Review</span>
                <span class="progress-item__value"><?= $needsReview ?> / <?= $total ?></span>
              </div>
              <div class="progress-item__bar">
                <div class="progress-item__fill is-warning" style="width:<?= $total > 0 ? round(($needsReview / $total) * 100) : 0 ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Risk Assessment -->
    <?php if (count($risks) > 0): ?>
    <div class="risk-section">
      <div class="risk-card">
        <h3 class="risk-card__title">🎯 Insights & Recommendations</h3>
        <?php foreach ($risks as $risk): ?>
        <div class="risk-item is-<?= $risk['level'] ?>">
          <div class="risk-item__message"><?= e($risk['message']) ?></div>
          <div class="risk-item__recommendation"><?= e($risk['recommendation']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="risk-section">
      <div class="risk-card">
        <h3 class="risk-card__title">🎯 Insights & Recommendations</h3>
        <div class="empty-insights">
          <div class="empty-insights__icon">✨</div>
          <div class="empty-insights__text">All systems operating smoothly. No issues detected.</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Barangay Submissions Chart -->
    <div class="chart-card">
      <h3 class="chart-card__title">📊 Submissions by Barangay</h3>
      <?php if (!$total): ?>
        <div class="empty-insights">
          <div class="empty-insights__icon">📭</div>
          <div class="empty-insights__text">No applications submitted yet this cycle.</div>
        </div>
      <?php else: ?>
      <div class="sub-chart">
        <?php foreach ($submissionCounts as $bname => $count):
              $h = $maxSubmission ? max(20, round(($count / $maxSubmission) * 150)) : 20; ?>
          <div class="sub-col">
            <div class="sub-col__val"><?= $count ?></div>
            <div class="sub-col__bar" style="height:<?= $h ?>px;" title="<?= e($bname) ?>: <?= $count ?>"></div>
            <div class="sub-col__label"><?= e($bname) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php render_shared_script(); ?>
</body>
</html>
