<?php

require_once __DIR__ . '/db.php';
// Note: r2-storage.php is included by individual pages that need it

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'Bocaue TODA Inc.');
define('APP_SYSTEM', "Tricycle Membership Permit Management System");
define('APP_YEAR', date('Y'));
define('BASE_URL', rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

class DriverValidationException extends RuntimeException {}

function d($key) {
    switch ($key) {
        case 'officers':          return db_get_officers();
        case 'drivers':           return db_get_drivers(false);
        case 'archived_drivers':  return db_get_drivers(true);
        case 'applications':      return db_get_applications();
        case 'applications_current_cycle': return db_get_applications_current_cycle();
        case 'announcements':     return db_get_announcements();
        case 'logs':              return db_get_activity_logs();
        case 'access_logs':       return db_get_access_logs();
        case 'barangays':         return db_get_barangay_counts();
        case 'colors':            return db_get_colors();
        case 'reject_reasons':    return db_get_reject_reasons();
        default:                  return null;
    }
}

function db_get_officers() {
    $pdo = get_pdo();
    $rows = $pdo->query('SELECT officer_id, name, initials, role FROM officers WHERE is_active = 1')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['officer_id']] = [
            'name'     => $r['name'],
            'role'     => $r['role'],
            'initials' => $r['initials'],
        ];
    }
    return $out;
}


function db_get_president_name(): string {
    $pdo = get_pdo();
    return (string) ($pdo->query(
        "SELECT name FROM officers WHERE role = 'president' AND is_active = 1 ORDER BY officer_id LIMIT 1"
    )->fetchColumn() ?: '');
}

/**
 * Start of the current renewal cycle = the most recent announcement that has
 * already opened. Deliberately NOT limited to announcements whose deadline
 * hasn't passed: once a renewal ends, its data must stay visible on the
 * dashboard until the admin opens a new renewal.
 */
function db_get_cycle_start() {
    $pdo = get_pdo();
    return $pdo->query(
        "SELECT created_at FROM announcements WHERE opened_date <= CURDATE() ORDER BY announcement_id DESC LIMIT 1"
    )->fetchColumn();
}

function db_get_drivers($archived) {
    $pdo = get_pdo();
    $cycleStart = db_get_cycle_start();
    if ($cycleStart) {
        $statusExpr = "CASE
                WHEN d.renewal_status IN ('Approved Renewal', 'Rejected Renewal')
                     AND NOT EXISTS (
                         SELECT 1 FROM applications a2
                         WHERE a2.driver_id = d.driver_id AND a2.submitted_at > :cycle_start
                     )
                THEN '-'
                ELSE d.renewal_status
            END AS status";
    } else {
        $statusExpr = 'd.renewal_status AS status';
    }

    $stmt = $pdo->prepare(
        "SELECT d.driver_id, d.name, d.age, d.birthday, d.contact_no AS contact, d.address,
                b.name AS barangay, d.body_no, c.name AS color, $statusExpr,
                d.password
         FROM drivers d
         JOIN barangays b ON b.barangay_id = d.barangay_id
         JOIN colors    c ON c.color_id    = d.color_id
         WHERE d.is_archived = :archived
         ORDER BY d.name"
    );
    $params = ['archived' => $archived ? 1 : 0];
    if ($cycleStart) {
        $params['cycle_start'] = $cycleStart;
    }
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        if (!empty($r['birthday'])) {
            $r['birthday'] = date('m/d/Y', strtotime($r['birthday']));
        }
    }
    return $rows;
}

function db_get_applications() {
    $pdo = get_pdo();
    $rows = $pdo->query(
        'SELECT a.app_no, a.driver_id, a.name, b.name AS barangay, a.body_no,
                a.submitted_at, a.age, a.birthday, a.contact_no AS contact, a.address,
                c.name AS color, a.status
         FROM applications a
         JOIN barangays b ON b.barangay_id = a.barangay_id
         JOIN colors    c ON c.color_id    = a.color_id
         ORDER BY a.submitted_at DESC'
    )->fetchAll();

    $docStmt = $pdo->prepare('SELECT doc_type, is_verified, file_path FROM application_documents WHERE app_no = :app_no');

    $out = [];
    foreach ($rows as $r) {
        $docStmt->execute(['app_no' => $r['app_no']]);
        $docs = ['license' => false, 'or_cr' => false, 'receipt' => false, 'photo' => false];
        $docPaths = ['license' => null, 'or_cr' => null, 'receipt' => null, 'photo' => null];
        foreach ($docStmt->fetchAll() as $d) {
            $docs[$d['doc_type']] = (bool) $d['is_verified'];
            $docPaths[$d['doc_type']] = $d['file_path'];
        }

        $out[$r['app_no']] = [
            'app_no'    => $r['app_no'],
            'driver_id' => $r['driver_id'],
            'name'      => $r['name'],
            'barangay'  => $r['barangay'],
            'body_no'   => $r['body_no'],
            'submitted' => date('m/d/Y', strtotime($r['submitted_at'])),
            'age'       => $r['age'],
            'birthday'  => !empty($r['birthday']) ? date('m/d/Y', strtotime($r['birthday'])) : '',
            'contact'   => $r['contact'],
            'address'   => $r['address'],
            'color'     => $r['color'],
            'status'    => $r['status'],
            'docs'      => $docs,
            'doc_paths' => $docPaths,
        ];
    }
    return $out;
}

function db_get_applications_current_cycle() {
    $pdo = get_pdo();

    $cycleStart = db_get_cycle_start();

    $sql = "SELECT a.app_no, a.driver_id, a.name, b.name AS barangay, a.body_no,
                a.submitted_at, a.approved_at, a.rejected_at, a.age, a.birthday,
                a.contact_no AS contact, a.address,
                c.name AS color, a.status
         FROM applications a
         JOIN barangays b ON b.barangay_id = a.barangay_id
         JOIN colors    c ON c.color_id    = a.color_id
         INNER JOIN (
             SELECT driver_id, MAX(submitted_at) AS latest
             FROM applications
             GROUP BY driver_id
         ) m ON m.driver_id = a.driver_id AND m.latest = a.submitted_at
         WHERE a.status IN ('Pending', 'For Approval')";
    $params = [];
    if ($cycleStart) {
        $sql .= " OR (a.status IN ('Approved', 'Rejected') AND a.submitted_at > :cycle_start)";
        $params['cycle_start'] = $cycleStart;
    }
    $sql .= ' ORDER BY a.submitted_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return [];
    }

    $docStmt = $pdo->prepare('SELECT doc_type, is_verified, file_path FROM application_documents WHERE app_no = :app_no');

    $out = [];
    foreach ($rows as $r) {
        $docStmt->execute(['app_no' => $r['app_no']]);
        $docs = ['license' => false, 'or_cr' => false, 'receipt' => false, 'photo' => false];
        $docPaths = ['license' => null, 'or_cr' => null, 'receipt' => null, 'photo' => null];
        foreach ($docStmt->fetchAll() as $d) {
            $docs[$d['doc_type']] = (bool) $d['is_verified'];
            $docPaths[$d['doc_type']] = $d['file_path'];
        }

        $out[$r['app_no']] = [
            'app_no'    => $r['app_no'],
            'driver_id' => $r['driver_id'],
            'name'      => $r['name'],
            'barangay'  => $r['barangay'],
            'body_no'   => $r['body_no'],
            'submitted' => date('m/d/Y', strtotime($r['submitted_at'])),
            'approved'  => !empty($r['approved_at']) ? date('m/d/Y', strtotime($r['approved_at'])) : '',
            'rejected'  => !empty($r['rejected_at']) ? date('m/d/Y', strtotime($r['rejected_at'])) : '',
            'age'       => $r['age'],
            'birthday'  => !empty($r['birthday']) ? date('m/d/Y', strtotime($r['birthday'])) : '',
            'contact'   => $r['contact'],
            'address'   => $r['address'],
            'color'     => $r['color'],
            'status'    => $r['status'],
            'docs'      => $docs,
            'doc_paths' => $docPaths,
        ];
    }
    return $out;
}

function db_get_announcements() {
    db_close_expired_announcements();
    db_close_superseded_announcements();

    $pdo = get_pdo();
    $rows = $pdo->query(
        'SELECT announcement_id, title, status, message, opened_date, deadline_date
         FROM announcements ORDER BY announcement_id DESC'
    )->fetchAll();
    $today = date('Y-m-d');
    foreach ($rows as &$r) {
      
        if ($r['status'] === 'OPEN' && $r['opened_date'] > $today) {
            $r['display_status'] = 'UPCOMING';
        } else {
            $r['display_status'] = $r['status'];
        }
    
        $r['opened_raw']   = $r['opened_date'];
        $r['deadline_raw'] = $r['deadline_date'];
        $r['opened']   = date('F j, Y', strtotime($r['opened_date']));
        $r['deadline'] = date('F j, Y', strtotime($r['deadline_date']));
        unset($r['opened_date'], $r['deadline_date']);
    }
    return $rows;
}


function db_close_expired_announcements() {
    $pdo = get_pdo();
    $pdo->exec("UPDATE announcements SET status = 'CLOSED' WHERE status = 'OPEN' AND deadline_date < CURDATE()");
}


function db_is_today_or_earlier(string $date): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT :date <= CURDATE()');
    $stmt->execute(['date' => $date]);
    return (bool) $stmt->fetchColumn();
}

function db_close_superseded_announcements() {
    $pdo = get_pdo();

    $current = $pdo->query(
        "SELECT announcement_id FROM announcements
         WHERE opened_date <= CURDATE()
         ORDER BY announcement_id DESC LIMIT 1"
    )->fetchColumn();

    if (!$current) {
        return;
    }

    $pdo->exec(
        "UPDATE announcements SET status = 'CLOSED'
         WHERE status = 'OPEN' AND opened_date <= CURDATE() AND announcement_id != " . (int) $current
    );
}

function db_get_activity_logs() {
    $pdo = get_pdo();
    $rows = $pdo->query(
        'SELECT action, driver_id, role, logged_at
         FROM activity_logs ORDER BY logged_at DESC LIMIT 50'
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['role'] = ucfirst($r['role']);
        $r['date'] = date('m/d/Y', strtotime($r['logged_at']));
        $r['time'] = date('g:i A', strtotime($r['logged_at']));
        unset($r['logged_at']);
    }
    return $rows;
}

function db_get_access_logs() {
    $pdo = get_pdo();
    $rows = $pdo->query(
        'SELECT action, role, logged_at
         FROM access_logs ORDER BY logged_at DESC LIMIT 50'
    )->fetchAll();
    foreach ($rows as &$r) {
        $r['role'] = ucfirst($r['role']);
        $r['date'] = date('m/d/Y', strtotime($r['logged_at']));
        $r['time'] = date('g:i A', strtotime($r['logged_at']));
        unset($r['logged_at']);
    }
    return $rows;
}

function db_get_barangay_counts() {
    $pdo = get_pdo();
    $rows = $pdo->query(
        'SELECT b.name, COUNT(d.driver_id) AS cnt
         FROM barangays b
         LEFT JOIN drivers d ON d.barangay_id = b.barangay_id AND d.is_archived = 0
         GROUP BY b.name
         ORDER BY b.name'
    )->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['name']] = (int) $r['cnt'];
    }
    return $out;
}

function db_get_colors() {
    $pdo = get_pdo();
    return $pdo->query('SELECT name FROM colors ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
}

function db_get_reject_reasons() {
    $pdo = get_pdo();
    return $pdo->query('SELECT reason FROM reject_reasons ORDER BY reason')->fetchAll(PDO::FETCH_COLUMN);
}


function log_activity($action, $driver_id, $officer_id, $role) {
    $pdo = get_pdo();
    $pdo->prepare('INSERT INTO activity_logs (action, driver_id, officer_id, role) VALUES (:action, :driver_id, :officer_id, :role)')
        ->execute([
            'action'     => $action,
            'driver_id'  => $driver_id,
            'officer_id' => $officer_id,
            'role'       => $role,
        ]);
}


function barangay_code($barangay) {
    $name = preg_replace('/\s*\d.*$/u', '', trim((string) $barangay));
    $name = str_replace(['ñ', 'Ñ'], 'N', $name);
    $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
    if ($letters === '') {
        return 'XX';
    }
    $consonants = preg_replace('/[AEIOU]/', '', $letters);
    return substr($consonants . 'XX', 0, 2);
}

function next_driver_id($pdo, $barangay, $bodyNo) {
    $digits = preg_replace('/\D/', '', (string) $bodyNo);
    $suffix = $digits !== ''
        ? str_pad($digits, 4, '0', STR_PAD_LEFT)
        : strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $bodyNo));

    $base = 'DRV-' . barangay_code($barangay) . $suffix;

    $check = $pdo->prepare('SELECT 1 FROM drivers WHERE driver_id = :id');
    $check->execute(['id' => $base]);
    if (!$check->fetchColumn()) {
        return $base;
    }
    for ($i = 2; $i < 100; $i++) {
        $check->execute(['id' => $base . '-' . $i]);
        if (!$check->fetchColumn()) {
            return $base . '-' . $i;
        }
    }
    return $base . '-' . time();
}

function next_officer_id($pdo, $year = null) {
    $year = $year ?: date('Y');
    $prefix = 'OFF-' . $year;

    $last = $pdo->prepare(
        'SELECT officer_id FROM officers WHERE officer_id LIKE :p ORDER BY officer_id DESC LIMIT 1'
    );
    $last->execute(['p' => $prefix . '%']);
    $last = $last->fetchColumn();

    $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

function generate_driver_password(int $length = 11): string {
    $chars = 'abcdefghijklmnopqrstuvwxyz';
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

function assert_driver_is_adult(string $birthday): void {
    $dob = date_create($birthday);
    if (!$dob) {
        throw new DriverValidationException('Please enter a valid birthday.');
    }

    $today = date_create('today');
    if ($dob > $today) {
        throw new DriverValidationException('Please enter a valid birthday.');
    }

    $age = $today->diff($dob)->y;
    if ($age < 18) {
        throw new DriverValidationException('Driver must be at least 18 years old.');
    }
}

function db_add_driver(array $in) {
    $pdo = get_pdo();

    assert_driver_is_adult($in['birthday']);

    $barangayId = $pdo->prepare('SELECT barangay_id FROM barangays WHERE name = :n');
    $barangayId->execute(['n' => $in['barangay']]);
    $barangayId = $barangayId->fetchColumn();

    $colorId = $pdo->prepare('SELECT color_id FROM colors WHERE name = :n');
    $colorId->execute(['n' => $in['color']]);
    $colorId = $colorId->fetchColumn();

    if (!$barangayId || !$colorId) {
        return false;
    }


    $dupe = $pdo->prepare(
        'SELECT body_no, contact_no FROM drivers
         WHERE is_archived = 0 AND (body_no = :body_no OR contact_no = :contact_no)
         LIMIT 1'
    );
    $dupe->execute(['body_no' => $in['body_no'], 'contact_no' => $in['contact']]);
    $dupe = $dupe->fetch();
    if ($dupe) {
        if ($dupe['body_no'] === $in['body_no']) {
            throw new DriverValidationException('Body number "' . $in['body_no'] . '" is already assigned to another driver.');
        }
        throw new DriverValidationException('Contact number "' . $in['contact'] . '" is already assigned to another driver.');
    }

    $driverId = next_driver_id($pdo, $in['barangay'], $in['body_no']);

    $pdo->prepare(
        'INSERT INTO drivers (driver_id, name, age, birthday, contact_no, address,
                               barangay_id, body_no, color_id, renewal_status, password)
         VALUES (:driver_id, :name, :age, :birthday, :contact_no, :address,
                 :barangay_id, :body_no, :color_id, "-", :password)'
    )->execute([
        'driver_id'   => $driverId,
        'name'        => $in['name'],
        'age'         => $in['age'],
        'birthday'    => date('Y-m-d', strtotime($in['birthday'])),
        'contact_no'  => $in['contact'],
        'address'     => $in['address'],
        'barangay_id' => $barangayId,
        'body_no'     => $in['body_no'],
        'color_id'    => $colorId,
        'password'    => $in['password'],
    ]);

    $officer = current_officer();
    log_activity('Add', $driverId, $officer['id'] ?? null, $officer['role'] ?? 'secretary');

    return $driverId;
}


function sync_application_status_from_driver_edit($driverId, $renewalStatus) {
    $map = [
        'Pending Renewal'   => 'Pending',
        'Validated Renewal' => 'For Approval',
        'Approved Renewal'  => 'Approved',
        'Rejected Renewal'  => 'Rejected',
    ];
    if (!isset($map[$renewalStatus])) {
        return; 
    }
    $targetStatus = $map[$renewalStatus];

    $pdo = get_pdo();
    $app = $pdo->prepare(
        'SELECT app_no, status, submitted_at FROM applications
         WHERE driver_id = :id ORDER BY submitted_at DESC LIMIT 1'
    );
    $app->execute(['id' => $driverId]);
    $app = $app->fetch();
    if (!$app || $app['status'] === $targetStatus) {
        return; 
    }

    $officer = current_officer();
    $appNo   = $app['app_no'];

    $pdo->beginTransaction();
    try {
        if ($targetStatus === 'Pending') {
       
            $pdo->prepare(
                'UPDATE applications SET status = "Pending",
                     validated_by = NULL, validated_at = NULL,
                     approved_by = NULL, approved_at = NULL,
                     rejected_by = NULL, rejected_at = NULL,
                     reject_reason_id = NULL, reject_note = NULL
                 WHERE app_no = :app'
            )->execute(['app' => $appNo]);

        } elseif ($targetStatus === 'For Approval') {
            $pdo->prepare(
                'UPDATE applications SET status = "For Approval", validated_by = :oid, validated_at = NOW() WHERE app_no = :app'
            )->execute(['oid' => $officer['id'] ?? null, 'app' => $appNo]);

        } elseif ($targetStatus === 'Approved') {
            $pdo->prepare(
                'UPDATE applications SET status = "Approved", approved_by = :oid, approved_at = NOW() WHERE app_no = :app'
            )->execute(['oid' => $officer['id'] ?? null, 'app' => $appNo]);

            $pdo->prepare(
                'INSERT INTO renewal_history (driver_id, app_no, renewal_year, decided_at, status)
                 VALUES (:driver_id, :app_no, :year, CURDATE(), "APPROVED")
                 ON DUPLICATE KEY UPDATE app_no = VALUES(app_no), decided_at = VALUES(decided_at), status = VALUES(status)'
            )->execute([
                'driver_id' => $driverId,
                'app_no'    => $appNo,
                'year'      => date('Y', strtotime($app['submitted_at'])),
            ]);

            $pdo->prepare(
                'INSERT INTO notifications (driver_id, app_no, title, message)
                 VALUES (:driver_id, :app_no, "Application Approved", :msg)'
            )->execute([
                'driver_id' => $driverId,
                'app_no'    => $appNo,
                'msg'       => "{$appNo} has been approved. Your renewed permit is now issued.",
            ]);

        } elseif ($targetStatus === 'Rejected') {
            $pdo->prepare(
                'UPDATE applications SET status = "Rejected", rejected_by = :oid, rejected_at = NOW() WHERE app_no = :app'
            )->execute(['oid' => $officer['id'] ?? null, 'app' => $appNo]);

            $pdo->prepare(
                'INSERT INTO renewal_history (driver_id, app_no, renewal_year, decided_at, status)
                 VALUES (:driver_id, :app_no, :year, CURDATE(), "REJECTED")
                 ON DUPLICATE KEY UPDATE app_no = VALUES(app_no), decided_at = VALUES(decided_at), status = VALUES(status)'
            )->execute([
                'driver_id' => $driverId,
                'app_no'    => $appNo,
                'year'      => date('Y', strtotime($app['submitted_at'])),
            ]);

            $pdo->prepare(
                'INSERT INTO notifications (driver_id, app_no, title, message)
                 VALUES (:driver_id, :app_no, "Application Rejected", :msg)'
            )->execute([
                'driver_id' => $driverId,
                'app_no'    => $appNo,
                'msg'       => "{$appNo} was rejected. Please review and resubmit.",
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('sync_application_status_from_driver_edit failed: ' . $e->getMessage());
        throw $e;
    }
}

function db_update_driver($driverId, array $in) {
    $pdo = get_pdo();

    assert_driver_is_adult($in['birthday']);

    $barangayId = $pdo->prepare('SELECT barangay_id FROM barangays WHERE name = :n');
    $barangayId->execute(['n' => $in['barangay']]);
    $barangayId = $barangayId->fetchColumn();

    $colorId = $pdo->prepare('SELECT color_id FROM colors WHERE name = :n');
    $colorId->execute(['n' => $in['color']]);
    $colorId = $colorId->fetchColumn();

    if (!$barangayId || !$colorId) {
        return false;
    }


    $dupe = $pdo->prepare(
        'SELECT body_no, contact_no FROM drivers
         WHERE is_archived = 0 AND driver_id != :self
           AND (body_no = :body_no OR contact_no = :contact_no)
         LIMIT 1'
    );
    $dupe->execute(['self' => $driverId, 'body_no' => $in['body_no'], 'contact_no' => $in['contact']]);
    $dupe = $dupe->fetch();
    if ($dupe) {
        if ($dupe['body_no'] === $in['body_no']) {
            throw new DriverValidationException('Body number "' . $in['body_no'] . '" is already assigned to another driver.');
        }
        throw new DriverValidationException('Contact number "' . $in['contact'] . '" is already assigned to another driver.');
    }

    $before = $pdo->prepare(
        'SELECT d.name, d.age, d.birthday, d.contact_no, d.address, d.body_no,
                b.name AS barangay, c.name AS color
         FROM drivers d
         LEFT JOIN barangays b ON b.barangay_id = d.barangay_id
         LEFT JOIN colors    c ON c.color_id    = d.color_id
         WHERE d.driver_id = :id'
    );
    $before->execute(['id' => $driverId]);
    $before = $before->fetch();
    if ($before && !empty($before['birthday'])) {
        $before['birthday'] = date('Y-m-d', strtotime($before['birthday']));
    }
    $newBirthday = date('Y-m-d', strtotime($in['birthday']));

    $pdo->prepare(
        'UPDATE drivers SET
            name = :name, age = :age, birthday = :birthday, contact_no = :contact_no,
            address = :address, barangay_id = :barangay_id, body_no = :body_no,
            color_id = :color_id
         WHERE driver_id = :driver_id'
    )->execute([
        'name'        => $in['name'],
        'age'         => $in['age'],
        'birthday'    => $newBirthday,
        'contact_no'  => $in['contact'],
        'address'     => $in['address'],
        'barangay_id' => $barangayId,
        'body_no'     => $in['body_no'],
        'color_id'    => $colorId,
        'driver_id'   => $driverId,
    ]);


    $officer = current_officer();
    log_activity('Update', $driverId, $officer['id'] ?? null, $officer['role'] ?? 'secretary');

    $fieldLabels = [
        'name'       => 'Name',
        'age'        => 'Age',
        'birthday'   => 'Birthday',
        'contact_no' => 'Contact No.',
        'address'    => 'Address',
        'body_no'    => 'Body No.',
        'barangay'   => 'Barangay',
        'color'      => 'Color Code',
    ];
    $after = [
        'name'       => $in['name'],
        'age'        => (string) $in['age'],
        'birthday'   => $newBirthday,
        'contact_no' => $in['contact'],
        'address'    => $in['address'],
        'body_no'    => $in['body_no'],
        'barangay'   => $in['barangay'],
        'color'      => $in['color'],
    ];

    $changes = [];
    if ($before) {
        foreach ($fieldLabels as $key => $label) {
            $oldVal = (string) ($before[$key] ?? '');
            $newVal = (string) ($after[$key] ?? '');
            if ($oldVal !== $newVal) {
                if ($key === 'birthday') {
                    $oldVal = $oldVal !== '' ? date('M j, Y', strtotime($oldVal)) : '(none)';
                    $newVal = $newVal !== '' ? date('M j, Y', strtotime($newVal)) : '(none)';
                }
                $changes[] = "{$label}: \"{$oldVal}\" \u{2192} \"{$newVal}\"";
            }
        }
    }

    if ($changes) {
        $pdo->prepare(
            'INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, NULL, :title, :message)'
        )->execute([
            'driver_id' => $driverId,
            'title'     => 'Profile Information Updated',
            'message'   => 'The administration updated your profile. Changes made: ' . implode('; ', $changes) . '.',
        ]);
    }

    return true;
}

function db_set_driver_password($driverId, $newPassword) {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE drivers SET password = :p WHERE driver_id = :id')
        ->execute(['p' => $newPassword, 'id' => $driverId]);

    $officer = current_officer();
    log_activity('Update', $driverId, $officer['id'] ?? null, $officer['role'] ?? 'secretary');

    $pdo->prepare(
        'INSERT INTO notifications (driver_id, app_no, title, message)
         VALUES (:driver_id, NULL, :title, :message)'
    )->execute([
        'driver_id' => $driverId,
        'title'     => 'Account Password Changed',
        'message'   => 'Your account password has been changed by the administration. If you did not request this, please contact the office immediately.',
    ]);

    return true;
}

function db_set_driver_archived($driverId, $archived) {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE drivers SET is_archived = :a WHERE driver_id = :id')
        ->execute(['a' => $archived ? 1 : 0, 'id' => $driverId]);

    $officer = current_officer();
    log_activity($archived ? 'Archive' : 'Restore', $driverId, $officer['id'] ?? null, $officer['role'] ?? 'secretary');


    $pdo->prepare(
        'INSERT INTO notifications (driver_id, app_no, title, message)
         VALUES (:driver_id, NULL, :title, :message)'
    )->execute([
        'driver_id' => $driverId,
        'title'     => $archived ? 'Account Archived' : 'Account Restored',
        'message'   => $archived
            ? 'Your account was archived by the administration. Contact the office if you believe this is a mistake.'
            : 'Your account has been restored by the administration and is active again.',
    ]);

    return true;
}


function db_add_announcement(array $in) {
    $pdo = get_pdo();
    $officer = current_officer();

    $startsNow = db_is_today_or_earlier($in['opened']);

    if ($startsNow) {
        $pdo->exec("UPDATE announcements SET status = 'CLOSED' WHERE status = 'OPEN'");
        $pdo->exec("UPDATE drivers SET renewal_status = '-' WHERE renewal_status IN ('Approved Renewal', 'Rejected Renewal')");
    }

    $pdo->prepare(
        "INSERT INTO announcements (title, message, status, opened_date, deadline_date, created_by)
         VALUES (:title, :message, 'OPEN', :opened, :deadline, :created_by)"
    )->execute([
        'title'      => $in['title'],
        'message'    => $in['message'],
        'opened'     => $in['opened'],
        'deadline'   => $in['deadline'],
        'created_by' => $officer['id'] ?? null,
    ]);

    return true;
}


function db_check_announcement_overlap(string $opened, string $deadline): ?array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        "SELECT title, opened_date, deadline_date
         FROM announcements
         WHERE deadline_date >= CURDATE()
           AND :opened <= deadline_date AND :deadline >= opened_date
         ORDER BY announcement_id DESC
         LIMIT 1"
    );
    $stmt->execute(['opened' => $opened, 'deadline' => $deadline]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['opened']   = date('F j, Y', strtotime($row['opened_date']));
    $row['deadline'] = date('F j, Y', strtotime($row['deadline_date']));
    return $row;
}


function db_update_announcement($announcementId, array $in): bool {
    $pdo = get_pdo();

    $current = $pdo->query(
        'SELECT announcement_id FROM announcements ORDER BY announcement_id DESC LIMIT 1'
    )->fetchColumn();
    if (!$current || (string) $current !== (string) $announcementId) {
        return false;
    }

    $today     = date('Y-m-d');
    $newStatus = ($in['deadline'] >= $today) ? 'OPEN' : 'CLOSED';

    $pdo->prepare(
        'UPDATE announcements
         SET title = :title, message = :message,
             opened_date = :opened, deadline_date = :deadline,
             status = :status
         WHERE announcement_id = :id'
    )->execute([
        'title'    => $in['title'],
        'message'  => $in['message'],
        'opened'   => $in['opened'],
        'deadline' => $in['deadline'],
        'status'   => $newStatus,
        'id'       => $current,
    ]);

    return true;
}


function db_set_document_verified($appNo, $docType, $verified) {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE application_documents SET is_verified = :v WHERE app_no = :app AND doc_type = :t')
        ->execute(['v' => $verified ? 1 : 0, 'app' => $appNo, 't' => $docType]);
}


function db_forward_application($appNo) {
    $pdo = get_pdo();

    $unverified = $pdo->prepare('SELECT COUNT(*) FROM application_documents WHERE app_no = :app AND is_verified = 0');
    $unverified->execute(['app' => $appNo]);
    if ((int) $unverified->fetchColumn() > 0) {
        return false;
    }

    $officer = current_officer();
    $pdo->prepare(
        'UPDATE applications SET status = "For Approval", validated_by = :oid, validated_at = NOW() WHERE app_no = :app'
    )->execute(['oid' => $officer['id'] ?? null, 'app' => $appNo]);

    $driverId = $pdo->prepare('SELECT driver_id FROM applications WHERE app_no = :app');
    $driverId->execute(['app' => $appNo]);
    $driverId = $driverId->fetchColumn();
    if ($driverId) {
        $pdo->prepare('UPDATE drivers SET renewal_status = "Validated Renewal" WHERE driver_id = :id')
            ->execute(['id' => $driverId]);
        log_activity('Validate', $driverId, $officer['id'] ?? null, $officer['role'] ?? 'secretary');
    }
    return true;
}


function db_approve_application($appNo) {
    $pdo = get_pdo();
    $officer = current_officer();

    $app = $pdo->prepare('SELECT driver_id, submitted_at FROM applications WHERE app_no = :app');
    $app->execute(['app' => $appNo]);
    $app = $app->fetch();
    if (!$app) return false;

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'UPDATE applications SET status = "Approved", approved_by = :oid, approved_at = NOW() WHERE app_no = :app'
        )->execute(['oid' => $officer['id'] ?? null, 'app' => $appNo]);

        $pdo->prepare('UPDATE drivers SET renewal_status = "Approved Renewal" WHERE driver_id = :id')
            ->execute(['id' => $app['driver_id']]);

        $pdo->prepare(
            'INSERT INTO renewal_history (driver_id, app_no, renewal_year, decided_at, status)
             VALUES (:driver_id, :app_no, :year, CURDATE(), "APPROVED")
             ON DUPLICATE KEY UPDATE app_no = VALUES(app_no), decided_at = VALUES(decided_at), status = VALUES(status)'
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $appNo,
            'year'      => date('Y', strtotime($app['submitted_at'])),
        ]);

        $pdo->prepare(
            'INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, :app_no, "Application Approved", :msg)'
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $appNo,
            'msg'       => "{$appNo} has been approved. Your renewed permit is now issued.",
        ]);

        log_activity('Approved', $app['driver_id'], $officer['id'] ?? null, $officer['role'] ?? 'president');

        $pdo->commit();
        
        // Send push notification after successful approval
        try {
            require_once __DIR__ . '/notification-manager.php';
            
            trigger_notification(
                $app['driver_id'],
                'driver',
                'status_change',
                [
                    'title' => 'Application Approved',
                    'message' => "Your application {$appNo} has been approved! Your renewed permit is now issued.",
                    'url' => '/Driver_BOFETODA/driver-home.php',
                    'appNo' => $appNo,
                    'metadata' => [
                        'app_no' => $appNo,
                        'status' => 'Approved',
                        'approved_by' => $officer['id'] ?? null
                    ]
                ]
            );
            
            error_log("Approval notification sent for application {$appNo} to driver {$app['driver_id']}");
            
        } catch (Exception $e) {
            error_log('Failed to send approval notification: ' . $e->getMessage());
            // Don't fail the approval if notification fails
        }
        
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('db_approve_application failed: ' . $e->getMessage());
        throw $e;
    }
}

function db_reject_application($appNo, $reasonText, $note) {
    $pdo = get_pdo();
    $officer = current_officer();

    $app = $pdo->prepare('SELECT driver_id, submitted_at FROM applications WHERE app_no = :app');
    $app->execute(['app' => $appNo]);
    $app = $app->fetch();
    if (!$app) return false;

    $reasonId = null;
    if ($reasonText) {
        $stmt = $pdo->prepare('SELECT reject_reason_id FROM reject_reasons WHERE reason = :r');
        $stmt->execute(['r' => $reasonText]);
        $reasonId = $stmt->fetchColumn() ?: null;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'UPDATE applications SET status = "Rejected", rejected_by = :oid, rejected_at = NOW(),
                                      reject_reason_id = :rid, reject_note = :note
             WHERE app_no = :app'
        )->execute([
            'oid'  => $officer['id'] ?? null,
            'rid'  => $reasonId,
            'note' => $note,
            'app'  => $appNo,
        ]);

        $pdo->prepare('UPDATE drivers SET renewal_status = "Rejected Renewal" WHERE driver_id = :id')
            ->execute(['id' => $app['driver_id']]);

        $pdo->prepare(
            'INSERT INTO renewal_history (driver_id, app_no, renewal_year, decided_at, status)
             VALUES (:driver_id, :app_no, :year, CURDATE(), "REJECTED")
             ON DUPLICATE KEY UPDATE app_no = VALUES(app_no), decided_at = VALUES(decided_at), status = VALUES(status)'
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $appNo,
            'year'      => date('Y', strtotime($app['submitted_at'])),
        ]);

        $pdo->prepare(
            'INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, :app_no, "Application Rejected", :msg)'
        )->execute([
            'driver_id' => $app['driver_id'],
            'app_no'    => $appNo,
            'msg'       => $note ?: "{$appNo} was rejected. Please review and resubmit.",
        ]);

        log_activity('Reject', $app['driver_id'], $officer['id'] ?? null, $officer['role'] ?? 'secretary');

        $pdo->commit();
        
        // Send push notification after successful rejection
        try {
            require_once __DIR__ . '/notification-manager.php';
            
            $rejectionMessage = $note 
                ? "Your application {$appNo} was rejected. Reason: {$note}" 
                : "Your application {$appNo} was rejected. Please review and resubmit.";
            
            trigger_notification(
                $app['driver_id'],
                'driver',
                'status_change',
                [
                    'title' => 'Application Update',
                    'message' => $rejectionMessage,
                    'url' => '/Driver_BOFETODA/driver-view-application.php?app_no=' . urlencode($appNo),
                    'appNo' => $appNo,
                    'metadata' => [
                        'app_no' => $appNo,
                        'status' => 'Rejected',
                        'rejected_by' => $officer['id'] ?? null,
                        'reason' => $reasonText,
                        'note' => $note
                    ]
                ]
            );
            
            error_log("Rejection notification sent for application {$appNo} to driver {$app['driver_id']}");
            
        } catch (Exception $e) {
            error_log('Failed to send rejection notification: ' . $e->getMessage());
            // Don't fail the rejection if notification fails
        }
        
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('db_reject_application failed: ' . $e->getMessage());
        throw $e;
    }
}

function db_change_officer_password($officerId, $currentPassword, $newPassword) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT password FROM officers WHERE officer_id = :id');
    $stmt->execute(['id' => $officerId]);
    $row = $stmt->fetch();
    if (!$row || !hash_equals($row['password'], (string) $currentPassword)) {
        return false;
    }
    $pdo->prepare('UPDATE officers SET password = :p WHERE officer_id = :id')
        ->execute(['p' => $newPassword, 'id' => $officerId]);
    return true;
}

if (!function_exists('require_login')) {
    function require_login() {
        if (empty($_SESSION['officer_id'])) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
}

function current_officer() {
    if (empty($_SESSION['officer_id'])) return null;
    $id = $_SESSION['officer_id'];

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT officer_id, name, initials, role, password FROM officers WHERE officer_id = :id AND is_active = 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) return null;

    return [
        'id'       => $row['officer_id'],
        'name'     => $row['name'],
        'role'     => $row['role'],
        'initials' => $row['initials'],
        'password' => $row['password'],
    ];
}


function attempt_officer_login($officerId, $password) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT officer_id, password, name, role FROM officers WHERE officer_id = :id AND is_active = 1');
    $stmt->execute(['id' => $officerId]);
    $officer = $stmt->fetch();

    // Use password_verify for hashed passwords (backward compatible with plain text during migration)
    $ok = false;
    if ($officer) {
        // Check if password is hashed (starts with $2y$ for bcrypt)
        if (strpos($officer['password'], '$2y$') === 0) {
            $ok = password_verify($password, $officer['password']);
        } else {
            // Legacy plain text comparison (for migration period)
            $ok = hash_equals($officer['password'], (string) $password);
            
            // Auto-upgrade to hashed password on successful login
            if ($ok) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE officers SET password = :pwd WHERE officer_id = :id')
                    ->execute(['pwd' => $hashedPassword, 'id' => $officer['officer_id']]);
            }
        }
    }

    $pdo->prepare('INSERT INTO access_logs (officer_id, action, role) VALUES (:oid, :action, :role)')
        ->execute([
            'oid'    => $officer['officer_id'] ?? null,
            'action' => $ok ? 'Login' : 'Invalid',
            'role'   => $officer['role'] ?? 'unknown',
        ]);

    if (!$ok) return false;

    session_regenerate_id(true);
    $_SESSION['officer_id'] = $officer['officer_id'];
    $_SESSION['name']       = $officer['name'];
    $_SESSION['role']       = $officer['role'];
    return true;
}

function is_role($role) {
    $officer = current_officer();
    return $officer && $officer['role'] === $role;
}

function require_role($role) {
    require_login();
    if (!is_role($role)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}


function renewal_statuses() {
    return [
        '-'                => '- (Unrenewed / Hindi pa nagre-renew)',
        'Pending Renewal'  => 'Pending Renewal',
        'Validated Renewal'=> 'Validated Renewal',
        'Approved Renewal' => 'Approved Renewal',
        'Rejected Renewal' => 'Rejected Renewal',
    ];
}

function badge_class_for_status($status) {
    $status = strtolower(trim($status));
    if ($status === '-' || str_contains($status, 'unrenewed')) return 'is-neutral';
    if (str_contains($status, 'validated')) return 'is-validated';
    if (str_contains($status, 'approve') || $status === 'open' || $status === 'active') return 'is-approved';
    if (str_contains($status, 'reject') || $status === 'closed') return 'is-rejected';
    return 'is-pending';
}

function row_class_for_status($status) {
    $status = strtolower(trim($status));
    if (str_contains($status, 'approve'))   return 'row-approved';
    if (str_contains($status, 'reject'))    return 'row-rejected';
    if (str_contains($status, 'validated')) return 'row-validated';
    if (str_contains($status, 'pending'))   return 'row-pending';
    return '';
}

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function render_shared_styles() {
    // Get VAPID public key for push notifications
    $vapidConfig = get_vapid_config();
    $vapidPublicKey = $vapidConfig['publicKey'] ?? '';
    
    if ($vapidPublicKey) {
        echo '<meta name="vapid-public-key" content="' . htmlspecialchars($vapidPublicKey) . '">' . "\n";
    }
    
    echo '<link rel="stylesheet" href="/assets/css/toast-notifications.css">' . "\n";
    
    // Run reminder scheduler (executes once per day)
    try {
        require_once __DIR__ . '/reminder-scheduler.php';
        check_and_send_reminders();
    } catch (Exception $e) {
        error_log('Reminder scheduler error in admin template: ' . $e->getMessage());
    }
?>
<style>
:root{
    --navy:#16233d;
    --navy-light:#20325a;
    --navy-hover:#0f1a30;
    --gold:#f0a020;
    --gold-dark:#c9840f;
    --bg:#f5f6f8;
    --white:#ffffff;
    --border:#e2e5ea;
    --text:#1f2937;
    --text-muted:#6b7280;
    --green:#2e7d32;
    --green-bg:#e8f5e9;
    --orange:#b8860b;
    --orange-bg:#fff4e5;
    --red:#d32f2f;
    --red-bg:#fdecea;
    --gray-bg:#f3f4f6;
    --radius:10px;
    --sidebar-w:260px;
    --shadow-card:0 1px 2px rgba(22,35,61,.05), 0 4px 12px rgba(22,35,61,.05);
}
*{box-sizing:border-box;margin:0;padding:0;}
html{min-height:100%;}
body{
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    display:flex;
    min-height:100vh;
}
a{color:inherit;}
img{max-width:100%;}
button{font-family:inherit;cursor:pointer;}
@media (prefers-reduced-motion: reduce){ *{animation:none !important; transition:none !important;} }

.sidebar{
    width:var(--sidebar-w); flex-shrink:0; background:var(--navy); color:#fff;
    align-self:stretch;
}
.sidebar__inner{
    display:flex; flex-direction:column;
    position:sticky; top:0;
    height:100vh; height:100dvh;
}
.sidebar__seal{ width:34px; height:34px; border-radius:8px; background:var(--gold); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--navy); font-size:13px; flex-shrink:0; }
.sidebar__officer{ display:flex; align-items:center; gap:10px; width:100%; padding:16px 20px; border:none; border-bottom:1px solid rgba(255,255,255,.1); background:transparent; text-align:left; color:inherit; font-family:inherit; cursor:pointer; transition:background .15s ease; }
.sidebar__officer:hover{ background:rgba(255,255,255,.06); }
.sidebar__avatar{ width:36px; height:36px; border-radius:50%; background:var(--gold); color:var(--navy); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0; }
.sidebar__officer-name{ font-size:13.5px; font-weight:600; }
.sidebar__officer-role{ font-size:11.5px; color:rgba(255,255,255,.55); text-transform:capitalize; }
.sidebar__nav{ flex:1; padding:16px 14px; display:flex; flex-direction:column; gap:4px; overflow-y:auto; }
.nav-link{ display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; color:rgba(255,255,255,.7); text-decoration:none; font-size:13.8px; font-weight:500; transition:background .15s ease, color .15s ease; position:relative; }
.nav-link:hover{ background:rgba(255,255,255,.08); color:#fff; }
.nav-link.is-active{ background:rgba(255,255,255,.14); color:#fff; font-weight:700; }
.nav-link__icon{ width:18px; height:18px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; }
.nav-link__icon svg{ width:100%; height:100%; }
.nav-link__badge{ position:absolute; top:6px; right:8px; min-width:18px; height:18px; background:#dc2626; color:#fff; font-size:10px; font-weight:800; border-radius:999px; display:flex; align-items:center; justify-content:center; padding:0 4px; pointer-events:none; line-height:1; }
.nav-link--logout{ margin-top:auto; border-top:1px solid rgba(255,255,255,.1); padding-top:11px; }

.profile-modal__head{ display:flex; align-items:center; justify-content:space-between; }
.profile-modal__back{ background:none; border:none; color:var(--text-muted); font-size:12.5px; font-weight:600; cursor:pointer; padding:4px; }
.profile-modal__back:hover{ color:var(--navy); }
.profile-modal__body{ text-align:center; padding-top:6px; padding-bottom:20px; }
.profile-modal__avatar{ width:64px; height:64px; border-radius:16px; background:var(--gold); color:var(--navy); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:22px; margin:6px auto 14px; }
.profile-modal__name{ font-size:15.5px; font-weight:700; color:var(--navy); }
.profile-modal__role{ font-size:12px; color:var(--text-muted); text-transform:capitalize; margin-bottom:18px; }
.profile-modal__meta{ text-align:left; font-size:13px; color:var(--text); border-top:1px solid var(--border); padding-top:16px; display:flex; flex-direction:column; gap:8px; }
.profile-modal__meta span{ color:var(--text-muted); display:inline-block; min-width:80px; }
.profile-modal__foot{ justify-content:center !important; padding-top:4px !important; display:flex; gap:12px; flex-wrap:wrap; }
.link-btn{ background:none; border:none; color:var(--navy); font-size:13px; font-weight:600; text-decoration:underline; cursor:pointer; padding:4px; }
.link-btn:hover{ color:var(--navy-light); }

.main-col{ flex:1; min-width:0; display:flex; flex-direction:column; }
.topbar{ display:flex; align-items:center; justify-content:space-between; padding:18px 32px; background:#fff; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:12px; }
.topbar__crumb{ font-size:12px; color:#9aa1b4; text-transform:uppercase; letter-spacing:.5px; font-weight:600; display:flex; align-items:center; gap:6px; }
.topbar__crumb-link{ color:#9aa1b4; text-decoration:none; }
.topbar__crumb-sep{ color:#9aa1b4; }
.topbar__crumb-page{ color:var(--text-muted); }
.topbar__title{ font-size:26px; font-weight:800; color:var(--navy); margin-top:6px; }
.topbar__sub{ font-size:13px; color:var(--text-muted); margin-top:2px; }
.topbar__meta{ text-align:right; font-size:12px; color:#9aa1b4; }
.page{ padding:28px 32px 60px; }

.stat-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.stat-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:18px 20px; box-shadow:var(--shadow-card); position:relative; overflow:hidden; }
.stat-card::before{ content:""; position:absolute; top:0; left:0; width:4px; height:100%; background:var(--gold); }
.stat-card.is-warn::before{ background:var(--red); }
.stat-card.is-ok::before{ background:var(--green); }
.stat-card.is-pending::before{ background:var(--orange); }
.stat-card__num{ font-size:32px; font-weight:800; color:var(--navy); text-align:right; margin-top:10px; }
.stat-card.is-pending .stat-card__num{ color:var(--orange); }
.stat-card.is-ok .stat-card__num{ color:var(--green); }
.stat-card.is-warn .stat-card__num{ color:var(--red); }
.stat-card__label{ font-size:11.5px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); line-height:1.4; }

.card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-card); margin-bottom:20px; }
.card__head{ padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.card__head h3{ font-size:16px; color:var(--navy); font-weight:700; }
.card__head p{ font-size:12.5px; color:var(--text-muted); margin-top:2px; }
.card__body{ padding:20px 22px; }

.history-grid{ display:grid; grid-template-columns:1.3fr 1fr; gap:20px; margin-bottom:20px; }
.history-grid .card{ margin-bottom:0; }
.history-grid .card__head{ justify-content:space-between; gap:8px; }
.history-grid .card__head h3{ flex:1; text-align:center; }

.table-wrap{ overflow-x:auto; }
table.registry{ width:100%; border-collapse:collapse; font-size:13.6px; }
table.registry thead th{ text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; color:var(--text-muted); padding:10px 14px; border-bottom:1px solid var(--border); background:var(--gray-bg); white-space:nowrap; }
table.registry tbody td{ padding:12px 14px; border-bottom:1px solid var(--border); }
table.registry tbody tr:hover{ background:var(--bg); }
table.registry td.id-cell{ color:var(--navy-light); font-weight:600; }

.badge{ display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:100px; font-size:11.5px; font-weight:700; letter-spacing:.2px; white-space:nowrap; }
.badge.is-pending{ background:var(--orange-bg); color:var(--orange); }
.badge.is-approved{ background:var(--green-bg); color:var(--green); }
.badge.is-rejected{ background:var(--red-bg); color:var(--red); }
.badge.is-neutral{ background:var(--gray-bg); color:var(--text-muted); }
.badge.is-validated{ background:#e6eefc; color:#2a5cbd; }

.btn{ display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; border-radius:8px; border:1px solid transparent; font-size:13.5px; font-weight:700; text-decoration:none; line-height:1; transition:background .15s ease, border-color .15s ease, color .15s ease, transform .1s ease; }
.btn:active{ transform:scale(.98); }
.btn-primary{ background:var(--navy); color:#fff; }
.btn-primary:hover{ background:var(--navy-light); }
.btn-gold{ background:var(--gold); color:var(--navy); }
.btn-gold:hover{ background:var(--gold-dark); color:#fff; }
.btn-outline{ background:transparent; border-color:var(--border); color:var(--text); }
.btn-outline:hover{ border-color:var(--navy); }
.btn-danger{ background:var(--red); color:#fff; }
.btn-danger:hover{ background:#b32a2a; }
.btn-success{ background:var(--green); color:#fff; }
.btn-success:hover{ background:#245e27; }
.btn-ghost{ background:transparent; color:var(--text-muted); }
.btn-ghost:hover{ color:var(--navy); }
.btn-block{ width:100%; }
.btn-pill{ border-radius:100px; padding:13px 18px; font-size:14.5px; }
.btn-sm{ padding:6px 12px; font-size:12px; }
.btn:disabled{ opacity:.5; cursor:not-allowed; }
.btn-row{ display:flex; gap:10px; flex-wrap:wrap; }

.form-grid{ display:grid; grid-template-columns:1fr 1fr; gap:16px 20px; }
.form-grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px 20px; }
.field{ display:flex; flex-direction:column; gap:6px; }
.field.span-2{ grid-column:1/-1; }
.field label{ font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); }
.field input, .field select, .field textarea{ border:1px solid var(--border); border-radius:8px; padding:10px 12px; font-size:14px; font-family:inherit; background:#f4f5f7; color:var(--text); transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; }
.field input:focus, .field select:focus, .field textarea:focus{ outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); background:#fff; }
.field textarea{ resize:vertical; min-height:90px; }
.field .hint{ font-size:11.5px; color:#9aa1b4; font-weight:400; text-transform:none; }

.search-bar{ display:flex; gap:10px; margin-bottom:16px; align-items:center; flex-wrap:wrap; }
.search-bar input[type="search"]{ flex:1; min-width:220px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:#fff; font-size:14px; }
.search-bar input[type="search"]:focus{ outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(22,35,61,.08); }
.search-bar select{ padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:#fff; font-size:13.5px; }

.doc-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:10px; }
.doc-tile{ border:1px solid var(--border); border-radius:8px; background:var(--gray-bg); padding:14px 10px; text-align:center; font-size:11.5px; color:var(--text-muted); }
.seal-check{ display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--green); font-weight:700; justify-content:center; margin-top:8px; }
.seal-check.is-missing{ color:var(--red); }
.seal-check__ring{ width:18px; height:18px; border-radius:50%; border:2px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:10px; }

.doc-link-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; margin-top:12px; }
.doc-link{ display:flex; align-items:center; justify-content:space-between; width:100%; background:#f4f5f7; border:1px solid var(--border); border-radius:10px; padding:14px 16px; font-size:13.8px; font-weight:700; color:var(--text); cursor:pointer; transition:background .15s ease, border-color .15s ease; font-family:inherit; text-align:left; }
.doc-link:hover{ background:#eceef1; border-color:#d7dbe3; }
.doc-link svg{ width:15px; height:15px; color:var(--text-muted); flex-shrink:0; }

.modal-backdrop{ position:fixed; inset:0; background:rgba(22,35,61,.55); display:none; align-items:center; justify-content:center; z-index:50; padding:20px; }
.modal-backdrop.is-open{ display:flex; }
.modal{ background:#fff; border-radius:var(--radius); width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.3); overflow:hidden; }
.modal__head{ padding:20px 24px 4px; }
.modal__head h3{ font-size:17px; color:var(--navy); }
.modal__body{ padding:8px 24px 4px; font-size:13.8px; color:var(--text-muted); }
.modal__body .field{ margin:12px 0 4px; }
.modal__foot{ padding:18px 24px 22px; display:flex; justify-content:flex-end; gap:10px; }
.modal__foot-split{ padding:18px 24px 22px; display:flex; justify-content:space-between; gap:10px; }
.modal.is-danger .modal__head h3{ color:var(--red); }

.bar-chart{ display:flex; flex-direction:column; gap:9px; }
.bar-row{ display:grid; grid-template-columns:110px 1fr 40px; align-items:center; gap:10px; font-size:12.5px; }
.bar-row__label{ color:var(--text-muted); text-align:right; }
.bar-track{ background:var(--gray-bg); border-radius:4px; height:12px; overflow:hidden; }
.bar-fill{ height:100%; background:var(--navy); border-radius:4px; }
.bar-row__val{ color:var(--text-muted); font-weight:700; }

.report-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:26px; }
.report-tile{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-card); }
.report-tile__icon{ width:38px; height:38px; border-radius:8px; background:var(--gray-bg); display:flex; align-items:center; justify-content:center; color:var(--gold-dark); font-size:16px; margin-bottom:12px; }
.report-tile h4{ font-size:14.5px; margin-bottom:4px; color:var(--navy); }
.report-tile p{ font-size:12.5px; color:var(--text-muted); margin:0 0 12px; }
.report-tile__head{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px; }
.report-tile__download{ flex-shrink:0; width:30px; height:30px; border-radius:50%; border:1px solid var(--border); background:#fff; display:flex; align-items:center; justify-content:center; color:var(--text-muted); cursor:pointer; transition:border-color .15s ease, color .15s ease; }
.report-tile__download:hover{ border-color:var(--navy); color:var(--navy); }
.report-tile__download svg{ width:14px; height:14px; }

.empty-state{ text-align:center; padding:40px 20px; color:#9aa1b4; }
.text-muted{ color:var(--text-muted); }
.flex-between{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }

@media (max-width:980px){
    .stat-grid, .report-grid{ grid-template-columns:repeat(2,1fr); }
    .form-grid{ grid-template-columns:1fr; }
    .doc-grid{ grid-template-columns:repeat(2,1fr); }
    .history-grid{ grid-template-columns:1fr; }
}
@media (max-width:720px){
    body{ flex-direction:column; }
    .sidebar{ width:100%; align-self:auto; }
    .sidebar__inner{ height:auto; position:relative; }
    .sidebar__nav{ flex-direction:row; flex-wrap:wrap; }
}
</style>
<?php
}

function nav_icon($name) {
    $icons = [
        'dashboard'   => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'megaphone'   => '<path d="M3 11l18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'validate'    => '<path d="M21 12a9 9 0 1 1-3.5-7.1"/><path d="M21 3v6h-6"/><path d="M9 12l2 2 4-4"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>',
        'check'       => '<path d="M20 6L9 17l-5-5"/>',
        'chart'       => '<path d="M4 20V10"/><path d="M12 20V4"/><path d="M20 20v-7"/>',
        'message'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function render_sidebar($active) {
    $officer = current_officer();
    $role = $officer['role'] ?? 'secretary';
    ?>
<aside class="sidebar">
 <div class="sidebar__inner">
  <button type="button" class="sidebar__officer" data-open-modal="profile-modal">
    <div class="sidebar__avatar"><?= e($officer['initials'] ?? '??') ?></div>
    <div>
      <div class="sidebar__officer-name"><?= e($officer['name'] ?? 'Officer') ?></div>
      <div class="sidebar__officer-role"><?= e($role) ?></div>
    </div>
  </button>
  <nav class="sidebar__nav">
    <a class="nav-link <?= $active === 'dashboard' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php"><span class="nav-link__icon"><?= nav_icon('dashboard') ?></span> Dashboard</a>
    <a class="nav-link <?= $active === 'general_announcements' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/manage_announcements.php"><span class="nav-link__icon"><?= nav_icon('megaphone') ?></span> Announcements</a>
    <a class="nav-link <?= $active === 'announcements' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/announcements.php"><span class="nav-link__icon"><?= nav_icon('mail') ?></span> Renewals</a>
    <?php if ($role === 'secretary'): ?>
      <a class="nav-link <?= $active === 'validation' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/validation_tools.php"><span class="nav-link__icon"><?= nav_icon('validate') ?></span> Validation Tools</a>
    <?php endif; ?>
    <?php if ($role === 'president'): ?>
      <a class="nav-link <?= $active === 'approval' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/approval_workflow.php"><span class="nav-link__icon"><?= nav_icon('check') ?></span> Approval Workflow</a>
    <?php endif; ?>
    <a class="nav-link <?= $active === 'driverlist' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/driver_list.php"><span class="nav-link__icon"><?= nav_icon('user') ?></span> Driver List</a>
    <a class="nav-link <?= $active === 'driver_inbox' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/driver_inbox.php">
      <span class="nav-link__icon"><?= nav_icon('message') ?></span> Driver Inbox
      <?php $unread_count = get_unread_message_count(); ?>
      <?php if ($unread_count > 0): ?>
        <span class="nav-link__badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
      <?php endif; ?>
    </a>
    <?php if ($role === 'president'): ?>
      <a class="nav-link <?= $active === 'reports' ? 'is-active' : '' ?>" href="<?= BASE_URL ?>/reports.php"><span class="nav-link__icon"><?= nav_icon('chart') ?></span> Reports</a>
    <?php endif; ?>
    <a class="nav-link nav-link--logout" href="#" data-open-modal="logout-modal"><span class="nav-link__icon"><?= nav_icon('logout') ?></span> Logout</a>
  </nav>
 </div>
</aside>

<div class="modal-backdrop" id="logout-modal">
  <div class="modal">
    <div class="modal__head"><h3>Notice</h3></div>
    <div class="modal__body">
      <p>Are you sure you want to log out?</p>
      <p>Any unsaved changes will be lost, and you will need to sign in again to continue.</p>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn-outline" data-close-modal="logout-modal">Cancel</button>
      <a class="btn btn-primary" href="<?= BASE_URL ?>/login.php?logout=1">Log out</a>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="profile-modal">
  <div class="modal" style="max-width:380px;">
    <div class="modal__head profile-modal__head">
      <h3>Profile</h3>
      <button type="button" class="profile-modal__back" data-close-modal="profile-modal">&larr; Back</button>
    </div>
    <div class="modal__body profile-modal__body">
      <div class="profile-modal__avatar"><?= e($officer['initials'] ?? '??') ?></div>
      <div class="profile-modal__name"><?= e($officer['name'] ?? 'Officer') ?></div>
      <div class="profile-modal__role"><?= e($role) ?></div>
      <div class="profile-modal__meta">
        <div><span>Officer ID:</span> <?= e($officer['id'] ?? '') ?></div>
        <div><span>Password:</span> <?= e($officer['password'] ?? '') ?></div>
      </div>
    </div>
    <div class="modal__foot profile-modal__foot">
      <button type="button" class="link-btn" data-close-modal="profile-modal" data-open-modal="change-password-modal">Change Password</button>
      <button type="button" class="link-btn" data-close-modal="profile-modal" data-open-modal="security-question-modal">Change Security Question</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="change-password-modal">
  <div class="modal" style="max-width:420px;">
    <div class="modal__head"><h3>Change Password</h3></div>
    <form method="post" action="<?= BASE_URL ?>/change_password.php" data-validate-match>
      <input type="hidden" name="return_to" class="js-return-to" value="">
      <div class="modal__body">
        <div class="field" style="margin:14px 0;">
          <label for="cp_current">Current Password <span style="color:var(--red)">*</span></label>
          <input type="password" id="cp_current" name="current_password" required>
        </div>
        <div class="field" style="margin:14px 0;">
          <label for="cp_new">New Password <span style="color:var(--red)">*</span></label>
          <input type="password" id="cp_new" name="new_password" required minlength="8">
        </div>
        <div class="field" style="margin:14px 0;">
          <label for="cp_confirm">Confirm Password <span style="color:var(--red)">*</span></label>
          <input type="password" id="cp_confirm" name="confirm_password" required minlength="8">
        </div>
      </div>
      <div class="modal__foot">
        <button type="button" class="btn btn-outline" data-close-modal="change-password-modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="security-question-modal">
  <div class="modal" style="max-width:480px;">
    <div class="modal__head"><h3>Change Security Question</h3></div>
    <form method="post" action="<?= BASE_URL ?>/update_security_question.php">
      <input type="hidden" name="return_to" class="js-return-to" value="">
      <div class="modal__body">
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">
          Update your security question and answer. This will be used to verify your identity if you forget your password.
        </p>
        <div class="field" style="margin:14px 0;">
          <label for="sq_question">Security Question <span style="color:var(--red)">*</span></label>
          <select id="sq_question" name="question_id" required style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-size:14px; background:#f4f5f7;">
            <option value="">— Select a question —</option>
            <?php foreach (SECURITY_QUESTIONS as $qid => $qtext): ?>
              <option value="<?= $qid ?>"><?= e($qtext) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field" style="margin:14px 0;">
          <label for="sq_answer">Your Answer <span style="color:var(--red)">*</span></label>
          <input type="text" id="sq_answer" name="answer" required placeholder="Enter your answer" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-size:14px; background:#f4f5f7;" autocomplete="off">
          <span style="font-size:11.5px; color:var(--text-muted); display:block; margin-top:6px;">Answer is case-insensitive</span>
        </div>
      </div>
      <div class="modal__foot">
        <button type="button" class="btn btn-outline" data-close-modal="security-question-modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php
}

function render_topbar($crumb, $title, $sub = '') {
?>
  <div class="topbar">
    <div>
      <div class="topbar__crumb">
        <span class="topbar__crumb-link"><?= e($crumb) ?></span>
        <span class="topbar__crumb-sep">&rsaquo;</span>
        <span class="topbar__crumb-page"><?= e($title) ?></span>
      </div>
    </div>
    <div class="topbar__meta">&copy; <?= APP_YEAR ?> <?= e(APP_NAME) ?></div>
  </div>
  <?php if (isset($_GET['pwok'])): ?>
    <div class="card" style="border-color:var(--green); background:var(--green-bg); margin:20px 32px 0;">
      <div class="card__body" style="color:var(--green); font-weight:600;">Password updated successfully.</div>
    </div>
  <?php elseif (isset($_GET['pwerror'])): ?>
    <div class="card" style="border-color:var(--red); background:var(--red-bg); margin:20px 32px 0;">
      <div class="card__body" style="color:var(--red); font-weight:600;">
        <?= $_GET['pwerror'] === 'mismatch' ? 'New password and confirmation did not match.' : 'Current password was incorrect.' ?>
      </div>
    </div>
  <?php elseif (isset($_GET['sqok'])): ?>
    <div class="card" style="border-color:var(--green); background:var(--green-bg); margin:20px 32px 0;">
      <div class="card__body" style="color:var(--green); font-weight:600;">Security question updated successfully.</div>
    </div>
  <?php elseif (isset($_GET['sqerror'])): ?>
    <div class="card" style="border-color:var(--red); background:var(--red-bg); margin:20px 32px 0;">
      <div class="card__body" style="color:var(--red); font-weight:600;">
        <?php
          $sqerror = $_GET['sqerror'];
          if ($sqerror === 'question') echo 'Please select a valid security question.';
          elseif ($sqerror === 'answer') echo 'Please provide an answer.';
          elseif ($sqerror === 'short') echo 'Answer must be at least 2 characters long.';
          else echo 'Failed to update security question. Please try again.';
        ?>
      </div>
    </div>
  <?php endif; ?>
<?php
}

function render_shared_script() {
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var modal = document.getElementById(trigger.getAttribute('data-open-modal'));
      if (modal) modal.classList.add('is-open');
    });
  });
  document.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var modal = document.getElementById(trigger.getAttribute('data-close-modal'));
      if (modal) modal.classList.remove('is-open');
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) backdrop.classList.remove('is-open'); });
  });

  document.querySelectorAll('[data-table-search]').forEach(function (input) {
    var table = document.querySelector(input.getAttribute('data-table-search'));
    if (!table) return;
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('[data-table-filter-col]').forEach(function (select) {
    var table = document.querySelector(select.getAttribute('data-table-target'));
    var col = parseInt(select.getAttribute('data-table-filter-col'), 10);
    if (!table) return;
    select.addEventListener('change', function () {
      var val = select.value;
      table.querySelectorAll('tbody tr').forEach(function (row) {
        if (val === 'All' || val === '') { row.style.display = ''; return; }
        var cell = row.children[col];
        row.style.display = (cell && cell.textContent.trim() === val) ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
    var input = document.querySelector(btn.getAttribute('data-toggle-pass'));
    if (!input) return;
    btn.addEventListener('click', function () {
      input.type = input.type === 'password' ? 'text' : 'password';
      btn.textContent = input.type === 'password' ? 'Show' : 'Hide';
    });
  });

  document.querySelectorAll('[data-toggle-pass-eye]').forEach(function (btn) {
    var input = document.getElementById(btn.getAttribute('data-toggle-pass-eye'));
    if (!input) return;
    var eyeIcon    = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeOffIcon = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.6 18.6 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    btn.innerHTML = eyeIcon;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? eyeOffIcon : eyeIcon;
    });
  });

  document.querySelectorAll('.js-return-to').forEach(function (input) {
    input.value = window.location.href;
  });

  document.querySelectorAll('form[data-validate-match]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var newPass = form.querySelector('[name="new_password"]');
      var confirmPass = form.querySelector('[name="confirm_password"]');
      if (newPass && confirmPass && newPass.value !== confirmPass.value) {
        confirmPass.setCustomValidity('Passwords do not match.');
        confirmPass.reportValidity();
        e.preventDefault();
        return;
      }
      if (confirmPass) confirmPass.setCustomValidity('');
    });
  });

  // Badge counter polling for driver messages
  function updateInboxBadge() {
    fetch('<?= BASE_URL ?>/api-message-count.php')
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          var badge = document.querySelector('.nav-link[href*="driver_inbox.php"] .nav-link__badge');
          if (data.count > 0) {
            var displayCount = data.count > 9 ? '9+' : data.count;
            if (badge) {
              badge.textContent = displayCount;
            } else {
              // Create badge if it doesn't exist
              var inboxLink = document.querySelector('.nav-link[href*="driver_inbox.php"]');
              if (inboxLink) {
                var newBadge = document.createElement('span');
                newBadge.className = 'nav-link__badge';
                newBadge.textContent = displayCount;
                inboxLink.appendChild(newBadge);
              }
            }
          } else if (badge) {
            // Remove badge if count is 0
            badge.remove();
          }
        }
      })
      .catch(function(error) {
        console.error('Error updating inbox badge:', error);
      });
  }

  // Update badge every 30 seconds
  setInterval(updateInboxBadge, 30000);
});
</script>
<script src="/assets/js/toast-notifications.js"></script>
<script src="/assets/js/notification-settings.js"></script>
<?php if (is_push_enabled()): ?>
<script src="/register-sw.js"></script>
<?php endif; ?>
<?php
}


// ============================================================================
// SECURITY QUESTION FUNCTIONS - For Password Recovery
// ============================================================================

/**
 * Available security questions for password recovery
 */
if (!defined('SECURITY_QUESTIONS')) {
    define('SECURITY_QUESTIONS', [
        1 => "What is your mother's maiden name?",
        2 => "What city were you born in?",
        3 => "What is the name of your first pet?",
        4 => "What was your childhood nickname?",
        5 => "What is your favorite color?",
    ]);
}

/**
 * Normalize security answer for case-insensitive matching
 * Trims whitespace and converts to lowercase
 */
function normalize_security_answer($answer) {
    return mb_strtolower(trim((string) $answer), 'UTF-8');
}

/**
 * Hash a security answer (after normalizing)
 */
function hash_security_answer($answer) {
    $normalized = normalize_security_answer($answer);
    return password_hash($normalized, PASSWORD_DEFAULT);
}

/**
 * Verify a provided security answer against the stored hash
 * Uses case-insensitive comparison
 */
function verify_security_answer($providedAnswer, $hashedAnswer) {
    $normalized = normalize_security_answer($providedAnswer);
    return password_verify($normalized, $hashedAnswer);
}

/**
 * Check if an officer has set up a security question
 */
function has_security_question($officerId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT security_question_id, security_answer_hash 
         FROM officers 
         WHERE officer_id = :id'
    );
    $stmt->execute(['id' => $officerId]);
    $row = $stmt->fetch();
    
    return $row && !empty($row['security_question_id']) && !empty($row['security_answer_hash']);
}

/**
 * Get officer's security question ID and text
 * Returns array with 'id' and 'question', or null if not set
 */
function get_security_question($officerId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT security_question_id 
         FROM officers 
         WHERE officer_id = :id'
    );
    $stmt->execute(['id' => $officerId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['security_question_id'])) {
        return null;
    }
    
    $questionId = (int) $row['security_question_id'];
    return [
        'id' => $questionId,
        'question' => SECURITY_QUESTIONS[$questionId] ?? 'Unknown question'
    ];
}

/**
 * Save security question and answer for an officer
 */
function save_security_question($officerId, $questionId, $answer) {
    $pdo = get_pdo();
    $hashedAnswer = hash_security_answer($answer);
    
    $stmt = $pdo->prepare(
        'UPDATE officers 
         SET security_question_id = :qid, 
             security_answer_hash = :answer 
         WHERE officer_id = :id'
    );
    
    return $stmt->execute([
        'qid' => $questionId,
        'answer' => $hashedAnswer,
        'id' => $officerId
    ]);
}

/**
 * Check if officer is in recovery cooldown period
 * Returns cooldown end time if active, null otherwise
 */
function check_recovery_cooldown($officerId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT recovery_cooldown_until 
         FROM officers 
         WHERE officer_id = :id'
    );
    $stmt->execute(['id' => $officerId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['recovery_cooldown_until'])) {
        return null;
    }
    
    $cooldownUntil = strtotime($row['recovery_cooldown_until']);
    $now = time();
    
    // If cooldown has expired, clear it
    if ($cooldownUntil <= $now) {
        $pdo->prepare('UPDATE officers SET recovery_cooldown_until = NULL WHERE officer_id = :id')
            ->execute(['id' => $officerId]);
        return null;
    }
    
    return $cooldownUntil;
}

/**
 * Increment failed recovery attempts for an officer
 * Automatically sets cooldown if threshold is reached
 */
function increment_recovery_attempts($officerId) {
    $pdo = get_pdo();
    
    // Increment attempts
    $pdo->prepare(
        'UPDATE officers 
         SET failed_recovery_attempts = failed_recovery_attempts + 1 
         WHERE officer_id = :id'
    )->execute(['id' => $officerId]);
    
    // Get current attempt count
    $stmt = $pdo->prepare(
        'SELECT failed_recovery_attempts 
         FROM officers 
         WHERE officer_id = :id'
    );
    $stmt->execute(['id' => $officerId]);
    $attempts = (int) $stmt->fetchColumn();
    
    // Set cooldown based on attempt count
    if ($attempts >= 5) {
        set_recovery_cooldown($officerId, 60); // 1 hour
    } elseif ($attempts >= 3) {
        set_recovery_cooldown($officerId, 15); // 15 minutes
    }
    
    return $attempts;
}

/**
 * Reset failed recovery attempts to 0 (called on successful answer)
 */
function reset_recovery_attempts($officerId) {
    $pdo = get_pdo();
    $pdo->prepare(
        'UPDATE officers 
         SET failed_recovery_attempts = 0, 
             recovery_cooldown_until = NULL 
         WHERE officer_id = :id'
    )->execute(['id' => $officerId]);
}

/**
 * Set recovery cooldown period
 */
function set_recovery_cooldown($officerId, $minutes) {
    $pdo = get_pdo();
    $cooldownUntil = date('Y-m-d H:i:s', time() + ($minutes * 60));
    
    $pdo->prepare(
        'UPDATE officers 
         SET recovery_cooldown_until = :until 
         WHERE officer_id = :id'
    )->execute([
        'until' => $cooldownUntil,
        'id' => $officerId
    ]);
}

/**
 * Get a human-readable cooldown message
 */
function get_cooldown_message($cooldownTimestamp) {
    $remaining = $cooldownTimestamp - time();
    
    if ($remaining <= 0) {
        return '';
    }
    
    $minutes = ceil($remaining / 60);
    
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        if ($mins > 0) {
            return "Please try again in {$hours} hour" . ($hours > 1 ? 's' : '') . " and {$mins} minute" . ($mins > 1 ? 's' : '') . ".";
        }
        return "Please try again in {$hours} hour" . ($hours > 1 ? 's' : '') . ".";
    }
    
    return "Please try again in {$minutes} minute" . ($minutes > 1 ? 's' : '') . ".";
}

/**
 * Verify officer's security answer and handle attempts/cooldown
 * Returns true if answer is correct, false otherwise
 */
function verify_officer_security_answer($officerId, $providedAnswer) {
    $pdo = get_pdo();
    
    // Check cooldown first
    $cooldown = check_recovery_cooldown($officerId);
    if ($cooldown !== null) {
        return false;
    }
    
    // Get stored answer hash
    $stmt = $pdo->prepare(
        'SELECT security_answer_hash 
         FROM officers 
         WHERE officer_id = :id'
    );
    $stmt->execute(['id' => $officerId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['security_answer_hash'])) {
        return false;
    }
    
    // Verify answer
    $isCorrect = verify_security_answer($providedAnswer, $row['security_answer_hash']);
    
    if ($isCorrect) {
        reset_recovery_attempts($officerId);
    } else {
        increment_recovery_attempts($officerId);
    }
    
    return $isCorrect;
}

/**
 * Reset officer password (used in forgot password flow)
 */
function reset_officer_password($officerId, $newPassword) {
    $pdo = get_pdo();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $pdo->prepare(
        'UPDATE officers 
         SET password = :pwd 
         WHERE officer_id = :id'
    )->execute([
        'pwd' => $hashedPassword,
        'id' => $officerId
    ]);
    
    return true;
}


// ============================================================================
// GENERAL ANNOUNCEMENTS FUNCTIONS
// ============================================================================

/**
 * Get all general announcements with optional filters
 * 
 * @param array $filters Optional filters: category, priority, is_active, search
 * @return array List of announcements
 */
function get_general_announcements($filters = []) {
    $pdo = get_pdo();
    
    $where = [];
    $params = [];
    
    if (isset($filters['category']) && $filters['category'] !== '') {
        $where[] = 'ga.category = :category';
        $params['category'] = $filters['category'];
    }
    
    if (isset($filters['priority']) && $filters['priority'] !== '') {
        $where[] = 'ga.priority = :priority';
        $params['priority'] = $filters['priority'];
    }
    
    if (isset($filters['is_active'])) {
        $where[] = 'ga.is_active = :is_active';
        $params['is_active'] = $filters['is_active'] ? 1 : 0;
    }
    
    if (isset($filters['search']) && trim($filters['search']) !== '') {
        $where[] = '(ga.title LIKE :search OR ga.message LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT ga.*, o.name AS created_by_name, o.role AS created_by_role,
                   COUNT(ai.image_id) AS image_count
            FROM general_announcements ga
            LEFT JOIN officers o ON ga.created_by = o.officer_id
            LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
            {$whereClause}
            GROUP BY ga.id
            ORDER BY 
                CASE WHEN ga.priority = 'urgent' THEN 0 ELSE 1 END,
                ga.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a single announcement by ID with all images
 * 
 * @param int $id Announcement ID
 * @return array|null Announcement data with images array, or null if not found
 */
function get_announcement_by_id($id) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare(
        "SELECT ga.*, o.name AS created_by_name, o.role AS created_by_role
         FROM general_announcements ga
         LEFT JOIN officers o ON ga.created_by = o.officer_id
         WHERE ga.id = :id"
    );
    $stmt->execute(['id' => $id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        return null;
    }
    
    // Get images
    $announcement['images'] = get_announcement_images($id);
    
    return $announcement;
}

/**
 * Get all images for an announcement
 * 
 * @param int $announcementId Announcement ID
 * @return array List of images ordered by display_order
 */
if (!function_exists('get_announcement_images')) {
    function get_announcement_images($announcementId) {
        $pdo = get_pdo();
        
        $stmt = $pdo->prepare(
            "SELECT image_id, image_path, display_order, uploaded_at
             FROM announcement_images
             WHERE announcement_id = :announcement_id
             ORDER BY display_order ASC, image_id ASC"
        );
        $stmt->execute(['announcement_id' => $announcementId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Create a new general announcement
 * 
 * @param array $data Announcement data (title, message, category, event_date, event_location, priority)
 * @param array $imageFiles Uploaded image files from $_FILES
 * @return int|false New announcement ID or false on failure
 */
function create_general_announcement($data, $imageFiles = []) {
    $pdo = get_pdo();
    $officer = current_officer();
    
    try {
        $pdo->beginTransaction();
        
        // Insert announcement
        $stmt = $pdo->prepare(
            "INSERT INTO general_announcements 
             (title, message, category, event_date, event_location, priority, is_active, created_by)
             VALUES (:title, :message, :category, :event_date, :event_location, :priority, 1, :created_by)"
        );
        
        $stmt->execute([
            'title' => $data['title'],
            'message' => $data['message'],
            'category' => $data['category'] ?? 'Other',
            'event_date' => !empty($data['event_date']) ? $data['event_date'] : null,
            'event_location' => !empty($data['event_location']) ? $data['event_location'] : null,
            'priority' => $data['priority'] ?? 'normal',
            'created_by' => $officer['officer_id'] ?? null
        ]);
        
        $announcementId = $pdo->lastInsertId();
        
        // Upload images if provided
        if (!empty($imageFiles)) {
            upload_announcement_images($announcementId, $imageFiles);
        }
        
        $pdo->commit();
        return (int) $announcementId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('create_general_announcement failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing announcement
 * 
 * @param int $id Announcement ID
 * @param array $data Updated data
 * @param array $imageFiles New uploaded image files (optional)
 * @return bool Success status
 */
function update_general_announcement($id, $data, $imageFiles = []) {
    $pdo = get_pdo();
    
    try {
        $pdo->beginTransaction();
        
        // Update announcement
        $stmt = $pdo->prepare(
            "UPDATE general_announcements 
             SET title = :title,
                 message = :message,
                 category = :category,
                 event_date = :event_date,
                 event_location = :event_location,
                 priority = :priority,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'category' => $data['category'] ?? 'Other',
            'event_date' => !empty($data['event_date']) ? $data['event_date'] : null,
            'event_location' => !empty($data['event_location']) ? $data['event_location'] : null,
            'priority' => $data['priority'] ?? 'normal'
        ]);
        
        // Upload new images if provided
        if (!empty($imageFiles)) {
            upload_announcement_images($id, $imageFiles);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('update_general_announcement failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Soft delete an announcement (set is_active = 0)
 * 
 * @param int $id Announcement ID
 * @return bool Success status
 */
function delete_general_announcement($id) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare(
            "UPDATE general_announcements SET is_active = 0 WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    } catch (Exception $e) {
        error_log('delete_general_announcement failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Toggle announcement active status
 * 
 * @param int $id Announcement ID
 * @return bool Success status
 */
function toggle_announcement_status($id) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare(
            "UPDATE general_announcements 
             SET is_active = NOT is_active 
             WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    } catch (Exception $e) {
        error_log('toggle_announcement_status failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Upload and save announcement images
 * 
 * @param int $announcementId Announcement ID
 * @param array $files Uploaded files from $_FILES
 * @return int Number of images successfully uploaded
 */
function upload_announcement_images($announcementId, $files) {
    if (empty($files) || !isset($files['tmp_name'])) {
        return 0;
    }
    
    $pdo = get_pdo();
    $uploaded = 0;
    $maxImages = 5;
    
    // Get current image count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcement_images WHERE announcement_id = :id");
    $stmt->execute(['id' => $announcementId]);
    $currentCount = (int) $stmt->fetchColumn();
    
    // Handle single or multiple files
    $fileArray = [];
    if (is_array($files['tmp_name'])) {
        for ($i = 0; $i < count($files['tmp_name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileArray[] = [
                    'tmp_name' => $files['tmp_name'][$i],
                    'name' => $files['name'][$i],
                    'size' => $files['size'][$i],
                    'type' => $files['type'][$i]
                ];
            }
        }
    } else {
        if ($files['error'] === UPLOAD_ERR_OK) {
            $fileArray[] = [
                'tmp_name' => $files['tmp_name'],
                'name' => $files['name'],
                'size' => $files['size'],
                'type' => $files['type']
            ];
        }
    }
    
    foreach ($fileArray as $file) {
        // Check limit
        if ($currentCount + $uploaded >= $maxImages) {
            break;
        }
        
        // Upload to R2 or local storage (R2 function handles fallback)
        $uploadResult = upload_announcement_image_r2($announcementId, $file);
        
        if ($uploadResult) {
            // Save to database
            $stmt = $pdo->prepare(
                "INSERT INTO announcement_images (announcement_id, image_path, display_order)
                 VALUES (:announcement_id, :image_path, :display_order)"
            );
            
            $stmt->execute([
                'announcement_id' => $announcementId,
                'image_path' => $uploadResult['path'], // Store R2 key or local path
                'display_order' => $currentCount + $uploaded
            ]);
            
            $uploaded++;
        }
    }
    
    return $uploaded;
}

/**
 * Validate an uploaded image file
 * 
 * @param array $file File data from $_FILES
 * @return bool True if valid, false otherwise
 */
function validate_announcement_image($file) {
    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Check MIME type
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedMimes)) {
        return false;
    }
    
    // Verify actual image using getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return false;
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    
    return true;
}

/**
 * Delete a specific announcement image
 * 
 * @param int $imageId Image ID
 * @return bool Success status
 */
function delete_announcement_image($imageId) {
    $pdo = get_pdo();
    
    try {
        // Get image path before deleting
        $stmt = $pdo->prepare("SELECT image_path FROM announcement_images WHERE image_id = :id");
        $stmt->execute(['id' => $imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM announcement_images WHERE image_id = :id");
            $stmt->execute(['id' => $imageId]);
            
            // Delete from R2 or local storage
            delete_announcement_image_r2($image['image_path']);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log('delete_announcement_image failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get announcement category color for badges
 * 
 * @param string $category Category name
 * @return string Hex color code
 */
function get_announcement_category_color($category) {
    $colors = [
        'Event' => '#1d4ed8',      // Blue
        'Reminder' => '#f97316',   // Orange
        'Update' => '#16a34a',     // Green
        'News' => '#7c3aed',       // Purple
        'Other' => '#6b7280'       // Gray
    ];
    
    return $colors[$category] ?? '#6b7280';
}

/**
 * Truncate text to specified length and add "Read more" link
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncate_announcement_text($text, $length = 150) {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . '...';
}


// ============================================================================
// DRIVER-TO-OFFICER MESSAGING FUNCTIONS
// ============================================================================

/**
 * Get all messages for a specific driver (conversation thread)
 * Returns messages in chronological order (oldest first)
 * 
 * @param string $driverId Driver ID
 * @return array Array of messages with sender info
 */
function get_driver_messages($driverId) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            CASE 
                WHEN m.sender_type = 'officer' THEN o.name
                WHEN m.sender_type = 'driver' THEN d.name
            END as sender_name,
            o.name as officer_name,
            CASE 
                WHEN m.sender_type = 'officer' THEN o.role
                ELSE NULL
            END as sender_role
        FROM driver_messages m
        LEFT JOIN officers o ON m.sender_type = 'officer' AND m.sender_id = o.officer_id
        LEFT JOIN drivers d ON m.sender_type = 'driver' AND m.sender_id = d.driver_id
        WHERE m.driver_id = :driver_id
        ORDER BY m.sent_at ASC
    ");
    
    $stmt->execute(['driver_id' => $driverId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("get_driver_messages for $driverId returned " . count($messages) . " messages");
    
    // Get attachments for each message
    foreach ($messages as &$message) {
        error_log("Message {$message['id']}: has_attachments={$message['has_attachments']}");
        if ($message['has_attachments']) {
            $message['attachments'] = get_message_attachments($message['id']);
            error_log("Message {$message['id']} loaded " . count($message['attachments']) . " attachments");
        } else {
            $message['attachments'] = [];
        }
    }
    
    return $messages;
}

/**
 * Get attachments for a specific message
 * 
 * @param int $messageId Message ID
 * @return array Array of attachments
 */
function get_message_attachments($messageId) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT * FROM message_attachments 
        WHERE message_id = :message_id 
        ORDER BY uploaded_at ASC
    ");
    
    $stmt->execute(['message_id' => $messageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Send a new message in a driver-officer conversation
 * 
 * @param string $driverId Driver ID
 * @param string $senderType 'driver' or 'officer'
 * @param int $senderId Driver ID (as int) or Officer ID
 * @param string $messageText Message content
 * @param array|null $files Files from $_FILES (optional)
 * @return int|false Message ID if successful, false on failure
 */
function send_driver_message($driverId, $senderType, $senderId, $messageText, $files = null) {
    $pdo = get_pdo();
    
    try {
        $pdo->beginTransaction();
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO driver_messages 
            (driver_id, sender_type, sender_id, message_text, has_attachments, is_read, has_reply)
            VALUES (:driver_id, :sender_type, :sender_id, :message_text, :has_attachments, :is_read, :has_reply)
        ");
        
        // Check if files were uploaded
        $hasAttachments = false;
        if (!empty($files)) {
            error_log("Files array structure: " . print_r($files, true));
            
            // Check if this is a pre-processed array (from driver-messages.php)
            // Structure: [0 => ['name' => '...', 'tmp_name' => '...', ...], ...]
            if (isset($files[0]) && is_array($files[0]) && isset($files[0]['tmp_name'])) {
                error_log("Pre-processed files array detected. Count: " . count($files));
                $hasAttachments = !empty($files[0]['tmp_name']) && $files[0]['error'] === UPLOAD_ERR_OK;
            }
            // Check if this is raw $_FILES structure
            elseif (isset($files['tmp_name'])) {
                if (is_array($files['tmp_name'])) {
                    // Multiple files
                    error_log("Multiple files structure detected. Count: " . count($files['tmp_name']));
                    $hasAttachments = !empty($files['tmp_name'][0]) && $files['error'][0] === UPLOAD_ERR_OK;
                } else {
                    // Single file
                    error_log("Single file structure detected");
                    $hasAttachments = !empty($files['tmp_name']) && $files['error'] === UPLOAD_ERR_OK;
                }
            } else {
                error_log("Unknown files array structure");
            }
        }
        
        error_log("send_driver_message: hasAttachments=" . (int)$hasAttachments . ", files is " . (empty($files) ? "empty" : "not empty"));
        
        // Set is_read based on sender (officer messages are pre-read)
        $isRead = ($senderType === 'officer') ? 1 : 0;
        
        // has_reply is only relevant for driver messages, set to false initially
        $hasReply = 0;
        
        $stmt->execute([
            'driver_id' => $driverId,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message_text' => $messageText,
            'has_attachments' => $hasAttachments ? 1 : 0,
            'is_read' => $isRead,
            'has_reply' => $hasReply
        ]);
        
        $messageId = (int) $pdo->lastInsertId();
        
        // If this is an officer reply, update has_reply on all unread driver messages
        if ($senderType === 'officer') {
            update_message_reply_status($driverId);
        }
        
        // Handle file attachments if present
        if ($hasAttachments && $messageId > 0) {
            require_once __DIR__ . '/r2-storage.php';
            
            error_log("Processing attachments for message $messageId. hasAttachments=$hasAttachments");
            
            // Handle both pre-processed array and raw $_FILES structure
            $fileArray = [];
            
            // Check if pre-processed (from driver-messages.php)
            if (isset($files[0]) && is_array($files[0]) && isset($files[0]['tmp_name'])) {
                error_log("Pre-processed files array: " . count($files) . " files");
                foreach ($files as $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $fileArray[] = $file;
                    } else {
                        error_log("Pre-processed file upload error: " . $file['error']);
                    }
                }
            }
            // Handle raw $_FILES structure
            elseif (is_array($files['tmp_name'])) {
                error_log("Multiple files detected: " . count($files['tmp_name']) . " files");
                for ($i = 0; $i < count($files['tmp_name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $fileArray[] = [
                            'tmp_name' => $files['tmp_name'][$i],
                            'name' => $files['name'][$i],
                            'size' => $files['size'][$i],
                            'type' => $files['type'][$i]
                        ];
                    } else {
                        error_log("File $i upload error: " . $files['error'][$i]);
                    }
                }
            } else {
                error_log("Single file detected");
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $fileArray[] = [
                        'tmp_name' => $files['tmp_name'],
                        'name' => $files['name'],
                        'size' => $files['size'],
                        'type' => $files['type']
                    ];
                } else {
                    error_log("Single file upload error: " . $files['error']);
                }
            }
            
            error_log("fileArray has " . count($fileArray) . " files to process");
            
            // Upload each file (max 3)
            $uploadedCount = 0;
            foreach (array_slice($fileArray, 0, 3) as $file) {
                error_log("Uploading file: " . $file['name']);
                if (function_exists('upload_message_attachment_r2')) {
                    $result = upload_message_attachment_r2($driverId, $messageId, $file);
                    
                    if ($result) {
                        error_log("Upload successful. Storage: " . $result['storage'] . ", Path: " . $result['path']);
                        
                        // Determine which fields to set based on storage type
                        $r2Key = ($result['storage'] === 'r2') ? $result['path'] : null;
                        $filePath = ($result['storage'] === 'local') ? $result['path'] : null;
                        
                        // Save attachment info to database
                        $stmt = $pdo->prepare("
                            INSERT INTO message_attachments 
                            (message_id, file_name, file_path, file_size, file_type, r2_key)
                            VALUES (:message_id, :file_name, :file_path, :file_size, :file_type, :r2_key)
                        ");
                        
                        $stmt->execute([
                            'message_id' => $messageId,
                            'file_name' => $file['name'],
                            'file_path' => $filePath,
                            'file_size' => $file['size'],
                            'file_type' => $file['type'],
                            'r2_key' => $r2Key
                        ]);
                        
                        $uploadedCount++;
                        error_log("Attachment saved to DB. Total uploaded: $uploadedCount");
                    } else {
                        error_log("Upload failed for file: " . $file['name']);
                    }
                } else {
                    error_log("upload_message_attachment_r2 function not found!");
                }
            }
            
            error_log("Final uploaded count: $uploadedCount");
            
            // If no files uploaded successfully, set has_attachments to false
            if ($uploadedCount === 0) {
                error_log("No files uploaded successfully, setting has_attachments=0");
                $stmt = $pdo->prepare("UPDATE driver_messages SET has_attachments = 0 WHERE id = :id");
                $stmt->execute(['id' => $messageId]);
            }
        }
        
        $pdo->commit();
        
        // Send notification after successful message insert
        try {
            require_once __DIR__ . '/notification-manager.php';
            
            if ($senderType === 'officer') {
                // Officer replied to driver - notify the driver
                $officerName = get_officer_name($senderId);
                $messagePreview = substr($messageText, 0, 100) . (strlen($messageText) > 100 ? '...' : '');
                
                trigger_notification(
                    $driverId,
                    'driver',
                    'message_reply',
                    [
                        'title' => 'New message from ' . $officerName,
                        'message' => $messagePreview,
                        'url' => '/Driver_BOFETODA/driver-messages.php',
                        'metadata' => [
                            'message_id' => $messageId,
                            'sender_type' => 'officer',
                            'sender_id' => $senderId
                        ]
                    ]
                );
                
                error_log("Notification sent to driver $driverId for officer reply");
                
            } elseif ($senderType === 'driver') {
                // Driver sent message - notify all officers
                $driverName = get_driver_name($driverId);
                $messagePreview = substr($messageText, 0, 100) . (strlen($messageText) > 100 ? '...' : '');
                
                $notifyResult = notify_all_officers(
                    'New message from ' . $driverName,
                    $messagePreview,
                    '/Admin_BOFETODA/driver_conversation.php?driver_id=' . urlencode($driverId),
                    'message_reply'
                );
                
                error_log("Notification sent to " . $notifyResult['notifications_sent'] . " officers for driver message");
            }
            
        } catch (Exception $e) {
            error_log('Failed to send message notification: ' . $e->getMessage());
            // Don't fail the message send if notification fails
        }
        
        return $messageId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('send_driver_message failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mark all messages from a driver as read (when officer opens conversation)
 * 
 * @param string $driverId Driver ID
 * @return bool Success status
 */
function mark_driver_messages_as_read($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE driver_messages 
            SET is_read = 1 
            WHERE driver_id = :driver_id 
            AND sender_type = 'driver'
            AND is_read = 0
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        return true;
        
    } catch (Exception $e) {
        error_log('mark_driver_messages_as_read failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update has_reply status on driver messages when officer responds
 * 
 * @param string $driverId Driver ID
 * @return bool Success status
 */
function update_message_reply_status($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE driver_messages 
            SET has_reply = 1 
            WHERE driver_id = :driver_id 
            AND sender_type = 'driver'
            AND has_reply = 0
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        return true;
        
    } catch (Exception $e) {
        error_log('update_message_reply_status failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get total count of unread messages from all drivers (for officer badge)
 * 
 * @return int Number of unread messages
 */
function get_unread_message_count() {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM driver_messages 
            WHERE sender_type = 'driver' 
            AND is_read = 0
        ");
        
        return (int) $stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log('get_unread_message_count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get count of messages from a driver that have no officer reply (for driver badge)
 * 
 * @param string $driverId Driver ID
 * @return int Number of unreplied messages
 */
function get_driver_unreplied_count($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM driver_messages 
            WHERE driver_id = :driver_id 
            AND sender_type = 'driver'
            AND has_reply = 0
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        return (int) $stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log('get_driver_unreplied_count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get list of drivers who have sent messages (for admin inbox)
 * Returns drivers with their message stats
 * 
 * @param string $sortBy Sort order: 'newest' or 'name' (default: 'newest')
 * @return array Array of drivers with message info
 */
function get_drivers_with_messages($sortBy = 'newest') {
    $pdo = get_pdo();
    
    try {
        $orderClause = ($sortBy === 'name') 
            ? 'ORDER BY d.name ASC' 
            : 'ORDER BY last_message_time DESC';
        
        $sql = "
            SELECT 
                d.driver_id,
                d.name,
                COUNT(DISTINCT m.id) as total_messages,
                SUM(CASE WHEN m.sender_type = 'driver' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                MAX(m.sent_at) as last_message_time
            FROM drivers d
            INNER JOIN driver_messages m ON d.driver_id = m.driver_id
            WHERE d.is_archived = 0
            GROUP BY d.driver_id
            $orderClause
        ";
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('get_drivers_with_messages returned ' . count($results) . ' drivers');
        
        return $results;
        
    } catch (Exception $e) {
        error_log('get_drivers_with_messages failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get last message preview for a driver (for inbox list)
 * 
 * @param string $driverId Driver ID
 * @param int $maxLength Maximum length of preview (default: 50)
 * @return string Message preview
 */
function get_last_message_preview($driverId, $maxLength = 50) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            SELECT message_text, sender_type
            FROM driver_messages 
            WHERE driver_id = :driver_id 
            ORDER BY sent_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $prefix = ($result['sender_type'] === 'driver') ? 'Driver: ' : 'Officer: ';
            $text = $result['message_text'];
            
            if (mb_strlen($text) > $maxLength) {
                $text = mb_substr($text, 0, $maxLength) . '...';
            }
            
            return $prefix . $text;
        }
        
        return 'No messages';
        
    } catch (Exception $e) {
        error_log('get_last_message_preview failed: ' . $e->getMessage());
        return '';
    }
}

/**
 * Get driver info by ID (for conversation header)
 * 
 * @param string $driverId Driver ID
 * @return array|false Driver info or false if not found
 */
function get_driver_info($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            SELECT driver_id, name, contact_no, address
            FROM drivers 
            WHERE driver_id = :driver_id AND is_archived = 0
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log('get_driver_info failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a driver has any messages
 * 
 * @param string $driverId Driver ID
 * @return bool True if driver has messages
 */
function driver_has_messages($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM driver_messages 
            WHERE driver_id = :driver_id
        ");
        
        $stmt->execute(['driver_id' => $driverId]);
        return ((int) $stmt->fetchColumn()) > 0;
        
    } catch (Exception $e) {
        error_log('driver_has_messages failed: ' . $e->getMessage());
        return false;
    }
}


/**
 * Format timestamp as relative time (e.g., "2 hours ago", "Just now")
 */
function format_relative_time($timestamp) {
    $now = time();
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days !== 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks !== 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}


/**
 * Check if a file is an image based on extension (admin version)
 * 
 * @param string $filename Filename to check
 * @return bool True if image file
 */
function is_image_file_admin($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

/**
 * Get file icon based on file extension (admin version)
 */
function get_file_icon_admin($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '🖼️';
        default:
            return '📎';
    }
}

/**
 * Format file size in human-readable format (admin version)
 */
function format_file_size_admin($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return round($bytes / 1048576, 1) . ' MB';
    }
}

/**
 * Get VAPID configuration for web push notifications
 * 
 * @return array|null Array with publicKey, privateKey, and subject, or null if not configured
 */
function get_vapid_config() {
    $publicKey = getenv('VAPID_PUBLIC_KEY');
    $privateKey = getenv('VAPID_PRIVATE_KEY');
    $subject = getenv('VAPID_SUBJECT') ?: 'mailto:admin@bofetoda.com';
    
    if (!$publicKey || !$privateKey) {
        error_log('VAPID keys not configured. Push notifications will not work.');
        return null;
    }
    
    return [
        'publicKey' => $publicKey,
        'privateKey' => $privateKey,
        'subject' => $subject
    ];
}

/**
 * Check if web push notifications are enabled
 * 
 * @return bool True if VAPID keys are configured
 */
function is_push_enabled() {
    return get_vapid_config() !== null;
}

/**
 * Get officer name by ID
 * 
 * @param int $officerId Officer ID
 * @return string Officer name or 'Officer'
 */
function get_officer_name($officerId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM officers WHERE officer_id = :id");
        $stmt->execute(['id' => $officerId]);
        $name = $stmt->fetchColumn();
        
        return $name ?: 'Officer';
        
    } catch (Exception $e) {
        error_log('get_officer_name error: ' . $e->getMessage());
        return 'Officer';
    }
}

/**
 * Get driver name by ID
 * 
 * @param string $driverId Driver ID
 * @return string Driver name or driver ID
 */
function get_driver_name($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM drivers WHERE driver_id = :id");
        $stmt->execute(['id' => $driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($driver) {
            return trim($driver['first_name'] . ' ' . $driver['last_name']);
        }
        
        return $driverId;
        
    } catch (Exception $e) {
        error_log('get_driver_name error: ' . $e->getMessage());
        return $driverId;
    }
}
