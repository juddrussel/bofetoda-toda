<?php

require 'driver-database.php';
require 'driver-template.php';

$driver  = require_driver();
$app_no  = get_current_application($driver['driver_id']);
$app     = $app_no ? get_application_detail($app_no) : null;

if ($app && in_array($app['status'], ['Approved', 'Rejected'], true) && !application_is_current_cycle($app)) {
    $app_no = null;
    $app = null;
}

$is_rejected = $app && $app['status'] === 'Rejected';

render_header([
    'title'          => 'Application Status',
    'topbar_mode'    => 'page',
    'topbar_heading' => 'Current Status',
    'topbar_sub'     => $app_no,
]);
?>

<div class="content">

    <?php if (!$app): ?>
        <div class="card">
            <p class="text-muted" style="font-size:13px;">You don&rsquo;t have a renewal application on file yet.</p>
            <a href="driver-renewal.php" class="btn btn-accent" style="margin-top:14px;">Start a Renewal</a>
        </div>

    <?php elseif ($is_rejected): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--rejected status-tag">REJECTED</span>
            <div class="card-title" style="margin-bottom:2px;">Admin Remarks</div>
            <p style="font-weight:700;font-size:14px;margin-top:16px;margin-bottom:8px;"><?php echo h($app['reject_reason'] ?? 'Application Rejected'); ?></p>
            <p class="text-muted" style="font-size:13px;margin-bottom:20px;"><?php echo h($app['reject_note'] ?: 'Please review and resubmit your application.'); ?></p>

            <div class="timeline" style="border-top:1px solid var(--color-border-light);padding-top:10px;">
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Submitted</strong></div>
                    <div class="timeline-right"><?php echo h(date('M j, Y', strtotime($app['submitted_at']))); ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Rejected</strong><span>By: <?php echo h(ucfirst($app['rejected_by'] ? 'Officer ' . $app['rejected_by'] : '')); ?></span></div>
                    <div class="timeline-right"><?php echo $app['rejected_at'] ? h(date('M j, Y', strtotime($app['rejected_at']))) : '&mdash;'; ?></div>
                </div>
            </div>
        </div>

        <a href="driver-renewal.php?start=1" class="btn btn-primary">Re-Apply</a>

    <?php else: ?>
        <div class="card">
            <div class="stepper">
                <div class="step done">
                    <div class="step-circle">&#10003;</div>
                    <div class="step-label" style="font-size:11px;">Submitted</div>
                </div>
                <div class="step <?php echo $app['status'] !== 'Pending' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] !== 'Pending' ? '&#10003;' : '2'; ?></div>
                    <div class="step-label" style="font-size:11px;">Validated</div>
                </div>
                <div class="step <?php echo $app['status'] === 'Approved' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] === 'Approved' ? '&#10003;' : '3'; ?></div>
                    <div class="step-label" style="font-size:11px;">Approved</div>
                </div>
                <div class="step <?php echo $app['status'] === 'Approved' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] === 'Approved' ? '&#10003;' : '4'; ?></div>
                    <div class="step-label" style="font-size:11px;">Done</div>
                </div>
            </div>

            <div class="timeline" style="border-top:1px solid var(--color-border-light);padding-top:10px;">
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Submitted</strong></div>
                    <div class="timeline-right"><?php echo h(date('M j, Y', strtotime($app['submitted_at']))); ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Under Review</strong><span>Validated by: Secretary</span></div>
                    <div class="timeline-right"><?php echo $app['validated_at'] ? h(date('M j, Y', strtotime($app['validated_at']))) : 'Pending'; ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>For Approval</strong><span>Approved by: President</span></div>
                    <div class="timeline-right"><?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Approved</strong></div>
                    <div class="timeline-right"><?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php render_footer('home'); ?>