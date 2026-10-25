<?php

require_once __DIR__ . '/db.php';


if (!defined('DRIVER_UPLOADS_DIR')) {

    $candidates = [
        __DIR__ . '/../Driver',
        __DIR__ . '/../Driver_BOFETODA',
        __DIR__ . '/../driver',
        $_SERVER['DOCUMENT_ROOT'] ?? '' ? $_SERVER['DOCUMENT_ROOT'] . '/Driver' : null,
    ];

    $resolved = null;
    foreach ($candidates as $candidate) {
        if (!$candidate) {
            continue;
        }
        $real = realpath($candidate);
        if ($real !== false) {
            $resolved = $real;
            break;
        }
    }

    define('DRIVER_UPLOADS_DIR', $resolved ? $resolved . DIRECTORY_SEPARATOR : '');

    if (!$resolved) {
        error_log(
            'admin-documents.php: could not resolve the Driver uploads directory. Tried: '
            . implode(', ', array_filter($candidates))
        );
    }
}


function get_pending_applications(): array {
    $pdo = get_pdo();
    $stmt = $pdo->query(
        "SELECT a.app_no, a.driver_id, a.name, a.body_no, a.status, a.submitted_at,
                b.name AS barangay
         FROM applications a
         LEFT JOIN barangays b ON b.barangay_id = a.barangay_id
         WHERE a.status = 'Pending'
         ORDER BY a.submitted_at ASC"
    );
    return format_app_rows($stmt->fetchAll());
}

function count_applications(): int {
    $pdo = get_pdo();
    return (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
}


function get_for_approval_applications(): array {
    $pdo = get_pdo();
    $stmt = $pdo->query(
        "SELECT a.app_no, a.driver_id, a.name, a.body_no, a.status, a.submitted_at, a.validated_at,
                b.name AS barangay
         FROM applications a
         LEFT JOIN barangays b ON b.barangay_id = a.barangay_id
         WHERE a.status = 'For Approval'
         ORDER BY a.validated_at ASC, a.submitted_at ASC"
    );
    return format_app_rows($stmt->fetchAll());
}

function format_app_rows(array $rows): array {
    foreach ($rows as &$r) {
        $r['submitted'] = $r['submitted_at'] ? date('m/d/Y', strtotime($r['submitted_at'])) : '';
    }
    return $rows;
}

function get_application_with_docs(string $app_no): ?array {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        "SELECT a.*, b.name AS barangay, c.name AS color, rr.reason AS reject_reason
         FROM applications a
         LEFT JOIN barangays b ON b.barangay_id = a.barangay_id
         LEFT JOIN colors    c ON c.color_id    = a.color_id
         LEFT JOIN reject_reasons rr ON rr.reject_reason_id = a.reject_reason_id
         WHERE a.app_no = :app_no
         LIMIT 1"
    );
    $stmt->execute(['app_no' => $app_no]);
    $app = $stmt->fetch();
    if (!$app) {
        return null;
    }

    $app['submitted'] = $app['submitted_at'] ? date('m/d/Y', strtotime($app['submitted_at'])) : '';
    if (!empty($app['birthday'])) {
        $app['birthday'] = date('m/d/Y', strtotime($app['birthday']));
    }

    $app['validation_date'] = !empty($app['validated_at']) ? date('m/d/Y', strtotime($app['validated_at'])) : '';
    $app['contact'] = $app['contact_no'] ?? '';

    $docStmt = $pdo->prepare(
        'SELECT doc_type, file_path, is_verified FROM application_documents WHERE app_no = :app_no'
    );
    $docStmt->execute(['app_no' => $app_no]);

    $app['docs'] = ['license' => null, 'or_cr' => null, 'photo' => null, 'receipt' => null];
    foreach ($docStmt->fetchAll() as $d) {
        if (array_key_exists($d['doc_type'], $app['docs'])) {
            $app['docs'][$d['doc_type']] = [
                'file_path'   => $d['file_path'],
                'is_verified' => (bool) $d['is_verified'],
            ];
        }
    }

    return $app;
}

function mark_document_verified(string $app_no, string $doc_type): void {
    $pdo = get_pdo();
    $pdo->prepare(
        'UPDATE application_documents SET is_verified = 1 WHERE app_no = :app_no AND doc_type = :doc_type'
    )->execute(['app_no' => $app_no, 'doc_type' => $doc_type]);
}


function forward_to_approval(string $app_no): bool {
    $pdo = get_pdo();
    $officer = current_officer();

    $docs = $pdo->prepare('SELECT is_verified FROM application_documents WHERE app_no = :app_no');
    $docs->execute(['app_no' => $app_no]);
    $rows = $docs->fetchAll();
    if (!$rows) {
        return false; 
    }
    foreach ($rows as $r) {
        if (!$r['is_verified']) {
            return false;
        }
    }

    $app = $pdo->prepare("SELECT driver_id FROM applications WHERE app_no = :app_no AND status = 'Pending'");
    $app->execute(['app_no' => $app_no]);
    $app = $app->fetch();
    if (!$app) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE applications SET status = 'For Approval', validated_by = :oid, validated_at = NOW()
             WHERE app_no = :app_no AND status = 'Pending'"
        );
        $stmt->execute(['oid' => $officer['id'] ?? null, 'app_no' => $app_no]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare("UPDATE drivers SET renewal_status = 'Validated Renewal' WHERE driver_id = :id")
            ->execute(['id' => $app['driver_id']]);

        $pdo->prepare(
            'INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, :app_no, "Application Validated", :msg)'
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $app_no,
            'msg'       => "{$app_no}'s documents have been verified and forwarded for the president's approval.",
        ]);

        log_activity('Validate', $app['driver_id'], $officer['id'] ?? null, $officer['role'] ?? 'secretary');

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('forward_to_approval failed: ' . $e->getMessage());
        throw $e;
    }
}


function reject_application(string $app_no, string $reason, string $note = ''): bool {
    if ($reason === '') {
        return false;
    }

    $pdo = get_pdo();
    $officer = current_officer();

    $app = $pdo->prepare('SELECT driver_id, submitted_at FROM applications WHERE app_no = :app_no AND status = \'Pending\'');
    $app->execute(['app_no' => $app_no]);
    $app = $app->fetch();
    if (!$app) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE applications
             SET status = 'Rejected',
                 rejected_by = :oid,
                 rejected_at = NOW(),
                 reject_note = :note,
                 reject_reason_id = (SELECT reject_reason_id FROM reject_reasons WHERE reason = :reason LIMIT 1)
             WHERE app_no = :app_no AND status = 'Pending'"
        );
        $stmt->execute([
            'oid'     => $officer['id'] ?? null,
            'note'    => $note,
            'reason'  => $reason,
            'app_no'  => $app_no,
        ]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare('UPDATE drivers SET renewal_status = \'Rejected Renewal\' WHERE driver_id = :id')
            ->execute(['id' => $app['driver_id']]);

        $pdo->prepare(
            "INSERT INTO renewal_history (driver_id, app_no, renewal_year, decided_at, status)
             VALUES (:driver_id, :app_no, :year, CURDATE(), 'REJECTED')
             ON DUPLICATE KEY UPDATE app_no = VALUES(app_no), decided_at = VALUES(decided_at), status = VALUES(status)"
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $app_no,
            'year'      => date('Y', strtotime($app['submitted_at'])),
        ]);

        $pdo->prepare(
            "INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, :app_no, 'Application Rejected', :msg)"
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $app_no,
            'msg'       => $note !== '' ? $note : "{$app_no} was rejected. Please review and resubmit.",
        ]);

        log_activity('Reject', $app['driver_id'], $officer['id'] ?? null, $officer['role'] ?? 'secretary');

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('reject_application failed: ' . $e->getMessage());
        throw $e;
    }
}

function get_driver_documents_for_display(string $driverId): ?array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT app_no, status FROM applications
         WHERE driver_id = :id
         ORDER BY submitted_at DESC
         LIMIT 1'
    );
    $stmt->execute(['id' => $driverId]);
    $latest = $stmt->fetch();

    if (!$latest || $latest['status'] !== 'Approved') {
        return null;
    }

    return get_application_with_docs($latest['app_no']);
}

function resolve_document_path(string $relativePath): ?string {
    $base = DRIVER_UPLOADS_DIR;
    if ($base === '') {
        return null;
    }
    $relativePath = str_replace('\\', '/', ltrim($relativePath, '/\\'));
    $full = realpath($base . $relativePath);
    if ($full === false) {
        return null;
    }
    $baseReal = rtrim(realpath($base), DIRECTORY_SEPARATOR);
    if (strpos($full, $baseReal) !== 0) {
        return null; 
    }
    return $full;
}