<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$role    = $officer['role'] ?? 'secretary';

$apps    = d('applications_current_cycle');
$drivers = d('drivers');

$total    = count($apps);
$pending  = array_filter($apps, fn($a) => in_array($a['status'], ['Pending', 'For Approval'], true));
$approved = array_filter($apps, fn($a) => $a['status'] === 'Approved');
$rejected = array_filter($apps, fn($a) => $a['status'] === 'Rejected');

$totalDrivers     = count($drivers);
$unrenewedDrivers = count(array_filter($drivers, fn($d) => $d['status'] !== 'Approved Renewal'));

$approvalRate = $totalDrivers > 0
    ? round((count(array_filter($drivers, fn($d) => $d['status'] === 'Approved Renewal')) / $totalDrivers) * 100)
    : 0;

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

function most_least(array $counts): array {
    $nonZero = array_filter($counts);
    if (!$nonZero) {
        return [null, null];
    }
    arsort($nonZero);
    return [array_key_first($nonZero), array_key_last($nonZero)];
}

$palette = [
    '#111827', '#9ca3af', '#ef4444', '#991b1b', '#16a34a', '#1d4ed8',
    '#0d9488', '#facc15', '#f97316', '#e11d48', '#7c3aed', '#c026d3',
    '#0891b2', '#65a30d', '#b45309', '#fbbf24', '#22c55e', '#60a5fa',
    '#fcd34d',
];
$colorFor = [];
foreach (array_values($barangayNames) as $i => $bname) {
    $colorFor[$bname] = $palette[$i % count($palette)];
}

function donut(array $counts, array $colorFor): string {
    $total = array_sum($counts);
    $c     = 2 * M_PI * 60;

    if ($total <= 0) {
        return '<div class="donut-wrap">'
             . '<svg class="donut" viewBox="0 0 160 160" role="img" aria-label="No data yet">'
             . '<circle cx="80" cy="80" r="60" fill="none" stroke="#e5e7eb" stroke-width="34"/></svg>'
             . '<div class="donut-tooltip" aria-hidden="true"></div></div>';
    }

    $svg    = '<svg class="donut" viewBox="0 0 160 160" role="img">';
    $offset = 0;
    foreach ($counts as $name => $v) {
        if ($v <= 0) {
            continue;
        }
        $len  = $c * ($v / $total);
        $pct  = round(($v / $total) * 100);
        $svg .= '<circle cx="80" cy="80" r="60" fill="none" class="donut-seg"'
              . ' stroke="' . htmlspecialchars($colorFor[$name] ?? '#9ca3af') . '"'
              . ' stroke-width="34"'
              . ' stroke-dasharray="' . round($len, 2) . ' ' . round($c - $len, 2) . '"'
              . ' stroke-dashoffset="' . round(-$offset, 2) . '"'
              . ' data-name="' . htmlspecialchars($name) . '" data-value="' . $v . '" data-pct="' . $pct . '"'
              . ' transform="rotate(-90 80 80)">'
              . '<title>' . htmlspecialchars($name) . ': ' . $v . ' (' . $pct . '%)</title></circle>';
        $offset += $len;
    }
    $svg .= '</svg>';
    return '<div class="donut-wrap">' . $svg . '<div class="donut-tooltip" aria-hidden="true"></div></div>';
}

$cards = [
    'pending'  => ['label' => 'Pending Application',  'rows' => $pending,  'class' => 'is-pending'],
    'approved' => ['label' => 'Approved Application', 'rows' => $approved, 'class' => 'is-ok'],
    'rejected' => ['label' => 'Rejected Application', 'rows' => $rejected, 'class' => 'is-warn'],
];

$submissionCounts = by_barangay($apps, $barangayNames);
$maxSubmission    = $submissionCounts ? max($submissionCounts) : 0;
[$mostAll, $leastAll] = most_least($submissionCounts);

$page_heading = $role === 'president' ? "President's Dashboard" : "Secretary's Dashboard";
$page_sub     = "Bocaue Bulacan Tricycle Operators and Drivers' Association Transport Group Inc.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.dash-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:18px; align-items:stretch; }
.dash-col{ display:flex; flex-direction:column; gap:16px; }

.dash-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:18px 20px; box-shadow:var(--shadow-card); display:flex; flex-direction:column; }
.dash-card__head{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.dash-card__title{ font-size:15px; font-weight:700; color:var(--navy); }
.dash-card__export{ color:var(--text-muted); display:flex; text-decoration:none; }
.dash-card__export:hover{ color:var(--navy); }

.dash-total{ display:flex; align-items:flex-start; gap:18px; margin-top:8px; }
.dash-total__num{ font-size:38px; font-weight:800; color:var(--navy); line-height:1; }
.dash-meta{ font-size:12px; color:var(--text-muted); }
.dash-card > .dash-meta{ margin-top:auto; padding-top:10px; }
.dash-meta strong{ color:var(--navy); font-size:12.5px; }

.donut{ width:100%; max-width:190px; height:auto; display:block; margin:6px auto 10px; }
.donut-wrap{ position:relative; }
.donut-seg{ cursor:pointer; transition:opacity .15s ease; }
.donut-seg:hover{ opacity:.82; }
.donut-tooltip{
    position:absolute; top:0; left:0; z-index:5;
    transform:translate(-50%,-100%);
    background:var(--navy); color:#fff; font-size:11.5px; font-weight:600;
    padding:7px 11px; border-radius:6px; white-space:nowrap; text-align:center;
    line-height:1.5;
    pointer-events:none; opacity:0; transition:opacity .1s ease;
}
.donut-tooltip.is-visible{ opacity:1; }
.donut-num{ font-size:26px; font-weight:800; line-height:1; margin-top:6px; }
.is-pending .donut-num{ color:var(--orange); }
.is-ok .donut-num{ color:var(--green); }
.is-warn .donut-num{ color:var(--red); }

.legend{ display:flex; flex-wrap:wrap; gap:8px 18px; background:#fff; border:1px solid var(--border);
         border-radius:var(--radius); padding:14px 18px; margin-bottom:18px; box-shadow:var(--shadow-card); }
.legend span{ display:flex; align-items:center; gap:7px; font-size:12px; color:var(--text); }
.legend i{ width:11px; height:11px; border-radius:50%; flex-shrink:0; }

.rate-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius);
            padding:16px 20px; box-shadow:var(--shadow-card); margin-bottom:18px; }
.rate-head{ display:flex; justify-content:space-between; font-size:12.5px; color:var(--text-muted); margin-bottom:8px; }
.rate-track{ height:12px; background:var(--gray-bg); border-radius:999px; overflow:hidden; }
.rate-fill{ height:100%; background:var(--green); border-radius:999px; transition:width .3s; }

.sub-chart{ display:flex; align-items:flex-end; gap:10px; overflow-x:auto; padding-top:10px; }
.sub-col{ flex:1; min-width:70px; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:6px; }
.sub-col__bar{ width:14px; background:var(--navy); border-radius:3px 3px 0 0; }
.sub-col__val{ font-size:11.5px; font-weight:700; color:var(--navy); }
.sub-col__label{ font-size:10.5px; color:var(--text-muted); text-align:center; white-space:nowrap; }

@media (max-width:1100px){ .dash-grid{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:640px){ .dash-grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<?php render_sidebar('dashboard'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Dashboard', $page_sub); ?>

  <div class="page">

    <div style="margin-bottom:20px;">
      <h2 style="font-size:26px; font-weight:800; color:var(--navy);"><?= e($page_heading) ?></h2>
      <p style="font-size:13px; color:var(--text-muted); margin-top:4px;"><?= e($page_sub) ?></p>
    </div>

    <div class="dash-grid">

      <div class="dash-col">
        <div class="dash-card">
          <div class="dash-card__title">Total Application</div>
          <div class="dash-total">
            <div class="dash-total__num"><?= $total ?></div>
            <div class="dash-meta">
              <div>Most Barangay:</div>
              <div style="font-weight:700;color:var(--navy);margin-bottom:8px;"><?= $mostAll ? e($mostAll) : '&mdash;' ?></div>
              <div>Least Barangay:</div>
              <div style="font-weight:700;color:var(--navy);"><?= $leastAll ? e($leastAll) : '&mdash;' ?></div>
            </div>
          </div>
        </div>

        <div class="dash-card">
          <div class="dash-card__title">Total Drivers</div>
          <div class="dash-total__num" style="margin-top:10px;"><?= $totalDrivers ?></div>
        </div>

        <div class="dash-card">
          <div class="dash-card__title">Unrenewed Drivers</div>
          <div class="dash-total__num" style="margin-top:10px;"><?= $unrenewedDrivers ?></div>
        </div>
      </div>

      <?php foreach ($cards as $card):
            $counts = by_barangay($card['rows'], $barangayNames);
            [$most, $least] = most_least($counts); ?>
      <div class="dash-card <?= $card['class'] ?>">
        <div class="dash-card__head">
          <div class="dash-card__title"><?= e($card['label']) ?></div>
          <a href="<?= BASE_URL ?>/reports.php" class="dash-card__export" title="Open in Reports">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v7H3V3h7"/>
            </svg>
          </a>
        </div>
        <div class="donut-num"><?= count($card['rows']) ?></div>
        <?= donut($counts, $colorFor) ?>
        <div class="dash-meta">
          <div style="margin-bottom:4px;">Most Barangay: <strong><?= $most ? e($most) : '&mdash;' ?></strong></div>
          <div>Least Barangay: <strong><?= $least ? e($least) : '&mdash;' ?></strong></div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>

    <div class="legend">
      <?php foreach ($barangayNames as $bname): ?>
        <span><i style="background:<?= e($colorFor[$bname]) ?>"></i><?= e($bname) ?></span>
      <?php endforeach; ?>
    </div>

    <div class="rate-card">
      <div class="rate-head">
        <span>Current Renewal Cycle Approval Rate</span>
        <span><?= $approvalRate ?>%</span>
      </div>
      <div class="rate-track"><div class="rate-fill" style="width:<?= $approvalRate ?>%"></div></div>
    </div>

    <div class="card">
      <div class="card__head">
        <h3>Member Submission by Barangay</h3>
        <?php if ($role === 'president'): ?>
        <a href="<?= BASE_URL ?>/reports.php?print=1" target="_blank" class="report-tile__download" title="Download PDF">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
        </a>
        <?php endif; ?>
      </div>
      <div class="card__body">
        <?php if (!$total): ?>
          <div class="empty-state">No applications submitted yet this cycle.</div>
        <?php else: ?>
        <div class="sub-chart">
          <?php foreach ($submissionCounts as $bname => $count):
                $h = $maxSubmission ? max(4, round(($count / $maxSubmission) * 150)) : 4; ?>
            <div class="sub-col">
              <div class="sub-col__val"><?= $count ?></div>
              <div class="sub-col__bar" style="height:<?= $h ?>px;"></div>
              <div class="sub-col__label"><?= e($bname) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
document.querySelectorAll('.donut-wrap').forEach(function (wrap) {
  var tooltip = wrap.querySelector('.donut-tooltip');
  if (!tooltip) return;

  wrap.querySelectorAll('.donut-seg').forEach(function (seg) {
    seg.addEventListener('mousemove', function (e) {
      var rect = wrap.getBoundingClientRect();
      tooltip.style.left = (e.clientX - rect.left) + 'px';
      tooltip.style.top  = (e.clientY - rect.top - 10) + 'px';
      tooltip.innerHTML = '<strong>' + seg.getAttribute('data-name') + '</strong><br>' +
        seg.getAttribute('data-value') + ' (' + seg.getAttribute('data-pct') + '%)';
      tooltip.classList.add('is-visible');
    });
    seg.addEventListener('mouseleave', function () {
      tooltip.classList.remove('is-visible');
    });
  });
});
</script>

<?php render_shared_script(); ?>
</body>
</html>