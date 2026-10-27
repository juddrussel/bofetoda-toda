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
:root {
  --section-spacing: 28px;
  --card-padding: 24px;
}

.dashboard-container { max-width: 1400px; margin: 0 auto; }

.kpi-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:var(--section-spacing); }
.kpi-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:22px 24px; box-shadow:var(--shadow-card); transition: transform 0.2s, box-shadow 0.2s; }
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.kpi-card__label{ font-size:12px; text-transform:uppercase; letter-spacing:.6px; font-weight:700; color:var(--text-muted); margin-bottom:12px; }
.kpi-card__value{ font-size:36px; font-weight:800; color:var(--navy); line-height:1; margin-bottom:10px; }
.kpi-card__trend{ font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; }
.kpi-card__trend.is-up{ color:var(--green); }
.kpi-card__trend.is-down{ color:var(--red); }
.kpi-card__trend.is-neutral{ color:var(--text-muted); }

.section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.section-header__icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; background: var(--navy); color: white; border-radius: 6px; font-size: 14px; font-weight: 700; }
.section-header__title { font-size: 18px; font-weight: 700; color: var(--navy); margin: 0; }

.action-section{ margin-bottom:var(--section-spacing); }
.action-list{ display:grid; gap:12px; }
.action-item{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:18px 20px; box-shadow:var(--shadow-card); display:flex; align-items:center; justify-content:space-between; gap:20px; transition: all 0.2s; }
.action-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.action-item.is-warning{ border-left:4px solid var(--orange); background: #fffbf5; }
.action-item.is-alert{ border-left:4px solid var(--red); background: #fff5f5; }
.action-item.is-info{ border-left:4px solid #2563eb; background: #f5f8ff; }
.action-item__content{ flex:1; }
.action-item__title{ font-size:14px; font-weight:600; color:var(--text); line-height: 1.5; }
.action-item__link{ font-size:13px; font-weight:600; color:var(--navy); text-decoration:none; display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:6px; background:white; border: 1px solid var(--border); transition:all .2s; white-space: nowrap; }
.action-item__link:hover{ background:var(--navy); color:#fff; border-color: var(--navy); }

.progress-section{ margin-bottom:var(--section-spacing); }
.progress-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:var(--card-padding); box-shadow:var(--shadow-card); }
.progress-card__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.progress-card__title{ font-size:16px; font-weight:700; color:var(--navy); margin: 0; }
.progress-items-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:28px; }
.progress-item{ margin-bottom:0; }
.progress-item__head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.progress-item__label{ font-size:13px; font-weight:600; color:var(--text); }
.progress-item__value{ font-size:14px; font-weight:700; color:var(--navy); }
.progress-item__bar{ height:10px; background:var(--gray-bg); border-radius:999px; overflow:hidden; }
.progress-item__fill{ height:100%; background:var(--navy); border-radius:999px; transition:width .4s cubic-bezier(0.4, 0, 0.2, 1); }
.progress-item__fill.is-success{ background:var(--green); }
.progress-item__fill.is-warning{ background:var(--orange); }
.progress-item__fill.is-danger{ background:var(--red); }

.insights-section{ margin-bottom:var(--section-spacing); }
.insights-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:var(--card-padding); box-shadow:var(--shadow-card); }
.insights-card__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.insights-card__title{ font-size:16px; font-weight:700; color:var(--navy); margin: 0; }
.insight-item{ padding:16px 18px; border-radius:8px; margin-bottom:14px; border-left:4px solid; background: white; transition: all 0.2s; }
.insight-item:hover { transform: translateX(4px); }
.insight-item:last-child{ margin-bottom:0; }
.insight-item.is-high{ background:#fef2f2; border-color:var(--red); }
.insight-item.is-medium{ background:#fff7ed; border-color:var(--orange); }
.insight-item.is-low{ background:#f0fdf4; border-color:var(--green); }
.insight-item__message{ font-size:13px; font-weight:600; color:var(--text); margin-bottom:8px; line-height: 1.5; }
.insight-item__recommendation{ font-size:12px; color:var(--text-muted); line-height: 1.5; padding-left: 20px; position: relative; }
.insight-item__recommendation::before{ content:"→"; position: absolute; left: 0; font-weight: 700; color: var(--navy); }

.chart-section { margin-bottom:var(--section-spacing); }
.chart-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:var(--card-padding); box-shadow:var(--shadow-card); }
.chart-card__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.chart-card__title{ font-size:16px; font-weight:700; color:var(--navy); margin: 0; }
.sub-chart{ display:flex; align-items:flex-end; gap:12px; overflow-x:auto; padding:20px 0 10px; }
.sub-col{ flex:1; min-width:75px; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:8px; }
.sub-col__bar{ width:24px; background:var(--navy); border-radius:4px 4px 0 0; transition:all .2s; }
.sub-col__bar:hover{ background:var(--navy-light); transform: scaleY(1.05); }
.sub-col__val{ font-size:13px; font-weight:700; color:var(--navy); }
.sub-col__label{ font-size:11px; color:var(--text-muted); text-align:center; word-break: break-word; max-width: 80px; }

.empty-state{ text-align:center; padding:50px 20px; color:var(--text-muted); }
.empty-state__icon{ font-size:52px; margin-bottom:16px; opacity:0.2; }
.empty-state__text{ font-size:14px; line-height: 1.6; }

@media (max-width:1200px){ 
    .kpi-grid{ grid-template-columns:repeat(2,1fr); }
    .progress-items-grid{ grid-template-columns:repeat(2, 1fr); gap:20px; }
}
@media (max-width:768px){ 
    .kpi-grid{ grid-template-columns:1fr; }
    .progress-items-grid{ grid-template-columns:1fr; gap:16px; }
    .action-item { flex-direction: column; align-items: stretch; }
    .action-item__link { text-align: center; justify-content: center; }
}
</style>
</head>
<body>
<?php render_sidebar('dashboard'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Dashboard', $page_sub); ?>

  <div class="page">
    <div class="dashboard-container">

    <div style="margin-bottom:32px;">
      <h2 style="font-size:28px; font-weight:800; color:var(--navy); margin: 0;"><?= e($page_heading) ?></h2>
      <p style="font-size:14px; color:var(--text-muted); margin-top:6px;">Real-time insights and actionable intelligence for decision-making</p>
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
    </div>

    <!-- Action Items -->
    <?php if (count($actionItems) > 0): ?>
    <div class="action-section">
      <div class="section-header">
        <div class="section-header__icon">!</div>
        <h3 class="section-header__title">Action Required</h3>
      </div>
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
      <div class="progress-card">
        <div class="progress-card__header">
          <h3 class="progress-card__title">Renewal Progress</h3>
        </div>
        
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
    <div class="insights-section">
      <div class="insights-card">
        <div class="insights-card__header">
          <h3 class="insights-card__title">Insights & Recommendations</h3>
        </div>
        <?php foreach ($risks as $risk): ?>
        <div class="insight-item is-<?= $risk['level'] ?>">
          <div class="insight-item__message"><?= e($risk['message']) ?></div>
          <div class="insight-item__recommendation"><?= e($risk['recommendation']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="insights-section">
      <div class="insights-card">
        <div class="insights-card__header">
          <h3 class="insights-card__title">Insights & Recommendations</h3>
        </div>
        <div class="empty-state">
          <div class="empty-state__text">All systems operating smoothly. No issues detected.</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Barangay Submissions Chart -->
    <div class="chart-section">
      <div class="chart-card">
        <div class="chart-card__header">
          <h3 class="chart-card__title">Submissions by Barangay</h3>
        </div>
        <?php if (!$total): ?>
          <div class="empty-state">
            <div class="empty-state__text">No applications submitted yet this cycle.</div>
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
</div>

<?php render_shared_script(); ?>
</body>
</html>
