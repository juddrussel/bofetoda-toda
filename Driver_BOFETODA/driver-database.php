<?php

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('MAX_UPLOAD_BYTES')) {
    define('MAX_UPLOAD_BYTES', 4 * 1024 * 1024);
}


function driver_initials(?string $name): string {
    $name = trim((string) $name);
    if ($name === '') {
        return '??';
    }
    $parts = preg_split('/\s+/', $name);
    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
    if (count($parts) >= 2) {
        $initials = mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1);
    } else {
        $initials = mb_substr($parts[0], 0, 2);
    }
    return mb_strtoupper($initials);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function require_login() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['driver_id'])) {
        header('Location: driver-login.php');
        exit;
    }
}

function require_driver() {
    require_login();
    $driver = current_driver();
    if (!$driver) {
       
        do_logout();
        header('Location: driver-login.php?err=account');
        exit;
    }
    return $driver;
}

function current_driver() {
    $id = $_SESSION['driver_id'] ?? null;
    if (!$id) {
        return null;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT d.driver_id, d.name, d.age, d.birthday, d.contact_no, d.address,
                d.body_no, b.name AS barangay, c.name AS color_code
         FROM drivers d
         LEFT JOIN barangays b ON b.barangay_id = d.barangay_id
         LEFT JOIN colors    c ON c.color_id    = d.color_id
         WHERE d.driver_id = :id AND d.is_archived = 0
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $driver = $stmt->fetch();

    if (!$driver) {
        return null;
    }


    if (!empty($driver['birthday'])) {
        $driver['birthday'] = date('m/d/Y', strtotime($driver['birthday']));
    }
    $driver['barangay']   = $driver['barangay']   ?? '—';
    $driver['color_code'] = $driver['color_code'] ?? '—';

    $driver['role'] = 'Driver';
    return $driver;
}

function attempt_login($driver_id, $password) {
    $driver_id = trim((string) $driver_id);
    if ($driver_id === '' || $password === '') {
        return false;
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT driver_id, password, is_archived FROM drivers WHERE driver_id = :id LIMIT 1');
    $stmt->execute(['id' => $driver_id]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    // Use password_verify for hashed passwords (backward compatible with plain text during migration)
    $passwordMatch = false;
    if (strpos($row['password'], '$2y$') === 0) {
        // Password is hashed
        $passwordMatch = password_verify($password, $row['password']);
    } else {
        // Legacy plain text comparison
        $passwordMatch = hash_equals($row['password'], (string) $password);
        
        // Auto-upgrade to hashed password on successful login
        if ($passwordMatch) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE drivers SET password = :pwd WHERE driver_id = :id')
                ->execute(['pwd' => $hashedPassword, 'id' => $row['driver_id']]);
        }
    }

    if (!$passwordMatch) {
        return false;
    }

    if ((int) $row['is_archived'] === 1) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['driver_id'] = $row['driver_id'];
    return true;
}

function do_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Update a driver's own password. Called from the profile page's
 * Change Password modal.
 */
function driver_change_password(string $driverId, string $newPassword): bool {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE drivers SET password = :p WHERE driver_id = :id')
        ->execute(['p' => $newPassword, 'id' => $driverId]);
    return true;
}

/**
 * Absolute filesystem path to a driver's uploaded profile photo, or
 * null if they haven't uploaded one (in which case the initials
 * avatar is shown instead).
 */
function driver_avatar_path(string $driverId): ?string {
    $dir = __DIR__ . '/uploads/avatars/';
    foreach (['jpg', 'jpeg', 'png'] as $ext) {
        $path = $dir . $driverId . '.' . $ext;
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/** Public, cache-busted URL for a driver's profile photo, or null. */
function driver_avatar_url(string $driverId): ?string {
    $path = driver_avatar_path($driverId);
    if (!$path) {
        return null;
    }
    return 'uploads/avatars/' . basename($path) . '?v=' . filemtime($path);
}

/**
 * Save an uploaded profile photo for a driver, replacing any existing
 * one. Only JPG/PNG up to MAX_UPLOAD_BYTES are accepted.
 */
function save_driver_avatar(string $driverId, array $file): bool {
    $allowedExt = ['jpg', 'jpeg', 'png'];

    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return false;
    }

    $dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Only one photo per driver at a time — clear any previous file
    // even if it was saved with a different extension.
    foreach ($allowedExt as $e) {
        $old = $dir . $driverId . '.' . $e;
        if (is_file($old)) {
            unlink($old);
        }
    }

    return move_uploaded_file($file['tmp_name'], $dir . $driverId . '.' . $ext);
}

function get_renewal_history($driver_id = null) {
    $driver_id = $driver_id ?? ($_SESSION['driver_id'] ?? null);
    if (!$driver_id) {
        return [];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT renewal_year AS year, app_no, decided_at AS date, status
         FROM renewal_history
         WHERE driver_id = :id
         ORDER BY renewal_year DESC'
    );
    $stmt->execute(['id' => $driver_id]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['date'] = date('M j, Y', strtotime($r['date']));
    }
    return $rows;
}

function get_notifications($driver_id = null) {
    $driver_id = $driver_id ?? ($_SESSION['driver_id'] ?? null);
    if (!$driver_id) {
        return [];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT title, message AS text, created_at
         FROM notifications
         WHERE driver_id = :id
         ORDER BY created_at DESC'
    );
    $stmt->execute(['id' => $driver_id]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['date'] = $r['created_at'] ? date('M j, Y', strtotime($r['created_at'])) : '';
    }
    return $rows;
}


function get_latest_cycle_start() {
    $pdo = get_pdo();
    return $pdo->query(
        "SELECT created_at FROM announcements
         WHERE opened_date <= CURDATE() AND deadline_date >= CURDATE()
         ORDER BY announcement_id DESC LIMIT 1"
    )->fetchColumn() ?: null;
}

function application_is_current_cycle(?array $app): bool {
    if (!$app || empty($app['submitted_at'])) {
        return false;
    }
    $cycleStart = get_latest_cycle_start();
    if (!$cycleStart) {
        
        return false;
    }

    return strtotime($app['submitted_at']) > strtotime($cycleStart);
}

function get_current_application($driver_id = null) {
    $driver_id = $driver_id ?? ($_SESSION['driver_id'] ?? null);
    if (!$driver_id) {
        return null;
    }

    $pdo = get_pdo();
    $cycleStart = get_latest_cycle_start();

    if ($cycleStart) {
        $stmt = $pdo->prepare(
            'SELECT app_no, status FROM applications
             WHERE driver_id = :id AND submitted_at > :cycle_start
             ORDER BY submitted_at DESC
             LIMIT 1'
        );
        $stmt->execute(['id' => $driver_id, 'cycle_start' => $cycleStart]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT app_no, status FROM applications
             WHERE driver_id = :id
             ORDER BY submitted_at DESC
             LIMIT 1'
        );
        $stmt->execute(['id' => $driver_id]);
    }

    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

   
    return $row['app_no'];
}


function close_expired_announcements() {
    $pdo = get_pdo();
    $pdo->exec("UPDATE announcements SET status = 'CLOSED' WHERE status = 'OPEN' AND deadline_date < CURDATE()");
}


function close_superseded_announcements() {
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


function get_open_announcement() {
    close_expired_announcements();
    close_superseded_announcements();

    $pdo = get_pdo();
    $stmt = $pdo->query(
        "SELECT title, message, opened_date, deadline_date
         FROM announcements
         WHERE status = 'OPEN' AND opened_date <= CURDATE() AND deadline_date >= CURDATE()
         ORDER BY announcement_id DESC
         LIMIT 1"
    );
    $row = $stmt->fetch();
    if (!$row) {
        return [];
    }
    return [[
        'title'    => $row['title'],
        'message'  => $row['message'],
        'opened'   => date('F j, Y', strtotime($row['opened_date'])),
        'deadline' => date('F j, Y', strtotime($row['deadline_date'])),
    ]];
}


function get_upcoming_announcement() {
    close_expired_announcements();
    close_superseded_announcements();

    $pdo = get_pdo();
    $stmt = $pdo->query(
        "SELECT title, message, opened_date, deadline_date
         FROM announcements
         WHERE status = 'OPEN' AND opened_date > CURDATE()
         ORDER BY announcement_id DESC
         LIMIT 1"
    );
    $row = $stmt->fetch();
    if (!$row) {
        return [];
    }
    return [[
        'title'    => $row['title'],
        'message'  => $row['message'],
        'opened'   => date('F j, Y', strtotime($row['opened_date'])),
        'deadline' => date('F j, Y', strtotime($row['deadline_date'])),
    ]];
}

function get_application_detail($app_no) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT a.*, rr.reason AS reject_reason
         FROM applications a
         LEFT JOIN reject_reasons rr ON rr.reject_reason_id = a.reject_reason_id
         WHERE a.app_no = :app_no
         LIMIT 1'
    );
    $stmt->execute(['app_no' => $app_no]);
    return $stmt->fetch() ?: null;
}

/**
 * Application number in the form APP-<year><4-digit sequence>, e.g.
 * APP-20260003. The sequence restarts each year, same pattern as the
 * admin side's officer IDs.
 */
function next_application_no($pdo, $year = null) {
    $year = $year ?: date('Y');
    $prefix = 'APP-' . $year;

    $last = $pdo->prepare(
        'SELECT app_no FROM applications WHERE app_no LIKE :p ORDER BY app_no DESC LIMIT 1'
    );
    $last->execute(['p' => $prefix . '%']);
    $last = $last->fetchColumn();

    $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

function submit_renewal_application(array $driver, array $documentPaths): string {
    $pdo = get_pdo();

    $pdo->beginTransaction();
    try {
        $appNo = next_application_no($pdo);

        $barangayId = $pdo->prepare('SELECT barangay_id FROM barangays WHERE name = :n');
        $barangayId->execute(['n' => $driver['barangay']]);
        $barangayId = $barangayId->fetchColumn();

        $colorId = $pdo->prepare('SELECT color_id FROM colors WHERE name = :n');
        $colorId->execute(['n' => $driver['color_code']]);
        $colorId = $colorId->fetchColumn();

        $insertApp = $pdo->prepare(
            'INSERT INTO applications
                (app_no, driver_id, name, age, birthday, contact_no, address,
                 barangay_id, body_no, color_id, status, submitted_at)
             VALUES
                (:app_no, :driver_id, :name, :age, :birthday, :contact_no, :address,
                 :barangay_id, :body_no, :color_id, "Pending", NOW())'
        );
        $insertApp->execute([
            'app_no'      => $appNo,
            'driver_id'   => $driver['driver_id'],
            'name'        => $driver['name'],
            'age'         => $driver['age'],
            'birthday'    => date('Y-m-d', strtotime($driver['birthday'])),
            'contact_no'  => $driver['contact_no'],
            'address'     => $driver['address'],
            'barangay_id' => $barangayId,
            'body_no'     => $driver['body_no'],
            'color_id'    => $colorId,
        ]);

        $insertDoc = $pdo->prepare(
            'INSERT INTO application_documents (app_no, doc_type, file_path, is_verified)
             VALUES (:app_no, :doc_type, :file_path, 0)'
        );
        $docTypeMap = [
            'drivers_license' => 'license',
            'or_cr'           => 'or_cr',
            'payment_receipt' => 'receipt',
            'picture_1x1'     => 'photo',
        ];
        foreach ($documentPaths as $field => $path) {
            $docType = $docTypeMap[$field] ?? null;
            if (!$docType || !$path) {
                continue;
            }
            $insertDoc->execute([
                'app_no'    => $appNo,
                'doc_type'  => $docType,
                'file_path' => $path,
            ]);
        }

        $pdo->prepare('UPDATE drivers SET renewal_status = "Pending Renewal" WHERE driver_id = :id')
            ->execute(['id' => $driver['driver_id']]);

        $pdo->prepare(
            'INSERT INTO notifications (driver_id, app_no, title, message)
             VALUES (:driver_id, :app_no, "Application Submitted",
                     :msg)'
        )->execute([
            'driver_id' => $driver['driver_id'],
            'app_no'    => $appNo,
            'msg'       => "{$appNo} was submitted successfully and is currently in the validation queue for processing.",
        ]);

        $pdo->commit();
        return $appNo;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('submit_renewal_application failed: ' . $e->getMessage());
        throw $e;
    }
}


// ============================================================================
// SECURITY QUESTION FUNCTIONS - For Password Recovery
// ============================================================================

/**
 * Available security questions for password recovery
 */
define('SECURITY_QUESTIONS', [
    1 => "What is your mother's maiden name?",
    2 => "What city were you born in?",
    3 => "What is the name of your first pet?",
    4 => "What was your childhood nickname?",
    5 => "What is your favorite color?",
]);

/**
 * Normalize security answer for case-insensitive matching
 * Trims whitespace and converts to lowercase
 */
function driver_normalize_security_answer($answer) {
    return mb_strtolower(trim((string) $answer), 'UTF-8');
}

/**
 * Hash a security answer (after normalizing)
 */
function driver_hash_security_answer($answer) {
    $normalized = driver_normalize_security_answer($answer);
    return password_hash($normalized, PASSWORD_DEFAULT);
}

/**
 * Verify a provided security answer against the stored hash
 * Uses case-insensitive comparison
 */
function driver_verify_security_answer_hash($providedAnswer, $hashedAnswer) {
    $normalized = driver_normalize_security_answer($providedAnswer);
    return password_verify($normalized, $hashedAnswer);
}

/**
 * Check if a driver has set up a security question
 */
function driver_has_security_question($driverId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT security_question_id, security_answer_hash 
         FROM drivers 
         WHERE driver_id = :id'
    );
    $stmt->execute(['id' => $driverId]);
    $row = $stmt->fetch();
    
    return $row && !empty($row['security_question_id']) && !empty($row['security_answer_hash']);
}

/**
 * Get driver's security question ID and text
 * Returns array with 'id' and 'question', or null if not set
 */
function driver_get_security_question($driverId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT security_question_id 
         FROM drivers 
         WHERE driver_id = :id'
    );
    $stmt->execute(['id' => $driverId]);
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
 * Save security question and answer for a driver
 */
function driver_save_security_question($driverId, $questionId, $answer) {
    $pdo = get_pdo();
    $hashedAnswer = driver_hash_security_answer($answer);
    
    $stmt = $pdo->prepare(
        'UPDATE drivers 
         SET security_question_id = :qid, 
             security_answer_hash = :answer 
         WHERE driver_id = :id'
    );
    
    return $stmt->execute([
        'qid' => $questionId,
        'answer' => $hashedAnswer,
        'id' => $driverId
    ]);
}

/**
 * Check if driver is in recovery cooldown period
 * Returns cooldown end time if active, null otherwise
 */
function driver_check_recovery_cooldown($driverId) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT recovery_cooldown_until 
         FROM drivers 
         WHERE driver_id = :id'
    );
    $stmt->execute(['id' => $driverId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['recovery_cooldown_until'])) {
        return null;
    }
    
    $cooldownUntil = strtotime($row['recovery_cooldown_until']);
    $now = time();
    
    // If cooldown has expired, clear it
    if ($cooldownUntil <= $now) {
        $pdo->prepare('UPDATE drivers SET recovery_cooldown_until = NULL WHERE driver_id = :id')
            ->execute(['id' => $driverId]);
        return null;
    }
    
    return $cooldownUntil;
}

/**
 * Increment failed recovery attempts for a driver
 * Automatically sets cooldown if threshold is reached
 */
function driver_increment_recovery_attempts($driverId) {
    $pdo = get_pdo();
    
    // Increment attempts
    $pdo->prepare(
        'UPDATE drivers 
         SET failed_recovery_attempts = failed_recovery_attempts + 1 
         WHERE driver_id = :id'
    )->execute(['id' => $driverId]);
    
    // Get current attempt count
    $stmt = $pdo->prepare(
        'SELECT failed_recovery_attempts 
         FROM drivers 
         WHERE driver_id = :id'
    );
    $stmt->execute(['id' => $driverId]);
    $attempts = (int) $stmt->fetchColumn();
    
    // Set cooldown based on attempt count
    if ($attempts >= 5) {
        driver_set_recovery_cooldown($driverId, 60); // 1 hour
    } elseif ($attempts >= 3) {
        driver_set_recovery_cooldown($driverId, 15); // 15 minutes
    }
    
    return $attempts;
}

/**
 * Reset failed recovery attempts to 0 (called on successful answer)
 */
function driver_reset_recovery_attempts($driverId) {
    $pdo = get_pdo();
    $pdo->prepare(
        'UPDATE drivers 
         SET failed_recovery_attempts = 0, 
             recovery_cooldown_until = NULL 
         WHERE driver_id = :id'
    )->execute(['id' => $driverId]);
}

/**
 * Set recovery cooldown period
 */
function driver_set_recovery_cooldown($driverId, $minutes) {
    $pdo = get_pdo();
    $cooldownUntil = date('Y-m-d H:i:s', time() + ($minutes * 60));
    
    $pdo->prepare(
        'UPDATE drivers 
         SET recovery_cooldown_until = :until 
         WHERE driver_id = :id'
    )->execute([
        'until' => $cooldownUntil,
        'id' => $driverId
    ]);
}

/**
 * Get a human-readable cooldown message
 */
function driver_get_cooldown_message($cooldownTimestamp) {
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
 * Verify driver's security answer and handle attempts/cooldown
 * Returns true if answer is correct, false otherwise
 */
function driver_verify_security_answer($driverId, $providedAnswer) {
    $pdo = get_pdo();
    
    // Check cooldown first
    $cooldown = driver_check_recovery_cooldown($driverId);
    if ($cooldown !== null) {
        return false;
    }
    
    // Get stored answer hash
    $stmt = $pdo->prepare(
        'SELECT security_answer_hash 
         FROM drivers 
         WHERE driver_id = :id'
    );
    $stmt->execute(['id' => $driverId]);
    $row = $stmt->fetch();
    
    if (!$row || empty($row['security_answer_hash'])) {
        return false;
    }
    
    // Verify answer
    $isCorrect = driver_verify_security_answer_hash($providedAnswer, $row['security_answer_hash']);
    
    if ($isCorrect) {
        driver_reset_recovery_attempts($driverId);
    } else {
        driver_increment_recovery_attempts($driverId);
    }
    
    return $isCorrect;
}

/**
 * Reset driver password (used in forgot password flow)
 */
function driver_reset_password($driverId, $newPassword) {
    $pdo = get_pdo();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $pdo->prepare(
        'UPDATE drivers 
         SET password = :pwd 
         WHERE driver_id = :id'
    )->execute([
        'pwd' => $hashedPassword,
        'id' => $driverId
    ]);
    
    return true;
}
