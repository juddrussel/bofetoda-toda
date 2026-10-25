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

if (!function_exists('require_login')) {
    function require_login() {
        if (empty($_SESSION['logged_in']) || empty($_SESSION['driver_id'])) {
            header('Location: driver-login.php');
            exit;
        }
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
        'SELECT d.driver_id AS id, d.driver_id, d.name, d.age, d.birthday, d.contact_no, d.address,
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
    // Use R2 storage with fallback to local
    // Note: r2-storage.php must be included before calling this function
    
    $uploadResult = upload_driver_avatar_r2($driverId, $file);
    
    if ($uploadResult) {
        // Avatar uploaded successfully (either to R2 or local storage)
        return true;
    }
    
    return false;
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
        "SELECT opened_date FROM announcements
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
        error_log("get_current_application: No driver_id");
        return null;
    }

    $pdo = get_pdo();
    $cycleStart = get_latest_cycle_start();
    
    error_log("get_current_application: driver_id=$driver_id, cycleStart=$cycleStart");

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
    error_log("get_current_application: found=" . ($row ? "app_no={$row['app_no']}, status={$row['status']}" : "NULL"));
    
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


// ============================================================================
// GENERAL ANNOUNCEMENTS FUNCTIONS (DRIVER SIDE)
// ============================================================================

/**
 * Get active general announcements for drivers
 * 
 * @param int|null $limit Optional limit for number of announcements
 * @return array List of active announcements sorted by priority and date
 */
function get_active_general_announcements($limit = null) {
    $pdo = get_pdo();
    
    $sql = "SELECT ga.*, 
                   COUNT(ai.image_id) AS image_count
            FROM general_announcements ga
            LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
            WHERE ga.is_active = 1
            GROUP BY ga.id
            ORDER BY 
                CASE WHEN ga.priority = 'urgent' THEN 0 ELSE 1 END,
                ga.created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int) $limit;
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get latest announcements for homepage display
 * 
 * @param int $limit Number of announcements to fetch (default 3)
 * @return array Latest announcements
 */
function get_latest_announcements($limit = 3) {
    return get_active_general_announcements($limit);
}

/**
 * Get a single announcement with full details including images
 * 
 * @param int $id Announcement ID
 * @return array|null Announcement data with images, or null if not found
 */
function get_announcement_details($id) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare(
        "SELECT ga.*
         FROM general_announcements ga
         WHERE ga.id = :id AND ga.is_active = 1"
    );
    $stmt->execute(['id' => $id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$announcement) {
        return null;
    }
    
    // Get images for this announcement
    $announcement['images'] = get_announcement_images($id);
    
    return $announcement;
}

/**
 * Get all images for an announcement (ordered by display_order)
 * 
 * @param int $announcementId Announcement ID
 * @return array List of images
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
 * Search announcements by query string
 * 
 * @param string $query Search query
 * @return array Matching announcements
 */
function search_announcements($query) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare(
        "SELECT ga.*, 
                COUNT(ai.image_id) AS image_count
         FROM general_announcements ga
         LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
         WHERE ga.is_active = 1
           AND (ga.title LIKE :query OR ga.message LIKE :query)
         GROUP BY ga.id
         ORDER BY 
             CASE WHEN ga.priority = 'urgent' THEN 0 ELSE 1 END,
             ga.created_at DESC"
    );
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute(['query' => $searchTerm]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get announcements filtered by category
 * 
 * @param string $category Category name (Event, Reminder, Update, News, Other)
 * @return array Announcements in that category
 */
function get_announcements_by_category($category) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare(
        "SELECT ga.*, 
                COUNT(ai.image_id) AS image_count
         FROM general_announcements ga
         LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
         WHERE ga.is_active = 1 AND ga.category = :category
         GROUP BY ga.id
         ORDER BY 
             CASE WHEN ga.priority = 'urgent' THEN 0 ELSE 1 END,
             ga.created_at DESC"
    );
    
    $stmt->execute(['category' => $category]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get paginated announcements
 * 
 * @param int $page Page number (1-based)
 * @param int $perPage Items per page
 * @param array $filters Optional filters (category, search)
 * @return array Array with 'items' and 'total' keys
 */
function get_paginated_announcements($page = 1, $perPage = 10, $filters = []) {
    $pdo = get_pdo();
    
    $where = ['ga.is_active = 1'];
    $params = [];
    
    if (!empty($filters['category'])) {
        $where[] = 'ga.category = :category';
        $params['category'] = $filters['category'];
    }
    
    if (!empty($filters['search'])) {
        $where[] = '(ga.title LIKE :search OR ga.message LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT ga.id)
                 FROM general_announcements ga
                 WHERE {$whereClause}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    
    // Get paginated items
    $offset = ($page - 1) * $perPage;
    $itemsSql = "SELECT ga.*, 
                        COUNT(ai.image_id) AS image_count
                 FROM general_announcements ga
                 LEFT JOIN announcement_images ai ON ga.id = ai.announcement_id
                 WHERE {$whereClause}
                 GROUP BY ga.id
                 ORDER BY 
                     CASE WHEN ga.priority = 'urgent' THEN 0 ELSE 1 END,
                     ga.created_at DESC
                 LIMIT {$perPage} OFFSET {$offset}";
    
    $stmt = $pdo->prepare($itemsSql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ];
}

/**
 * Get category color for badges
 * 
 * @param string $category Category name
 * @return string Hex color code
 */
function get_category_color($category) {
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
 * Truncate text for display
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return array ['text' => truncated text, 'is_truncated' => boolean]
 */
function truncate_text($text, $length = 150) {
    $text = strip_tags($text);
    $isTruncated = mb_strlen($text) > $length;
    
    if ($isTruncated) {
        $truncated = mb_substr($text, 0, $length);
        // Try to truncate at last space to avoid cutting words
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $length * 0.8) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        return [
            'text' => $truncated . '...',
            'is_truncated' => true
        ];
    }
    
    return [
        'text' => $text,
        'is_truncated' => false
    ];
}

/**
 * Format event date for display
 * 
 * @param string|null $dateTime Event date/time string
 * @return string Formatted date or empty string
 */
function format_event_date($dateTime) {
    if (empty($dateTime)) {
        return '';
    }
    
    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return '';
    }
    
    $now = time();
    $diff = $timestamp - $now;
    
    // If event is in the past
    if ($diff < 0) {
        return date('M j, Y g:i A', $timestamp);
    }
    
    // If event is today
    if (date('Y-m-d', $timestamp) === date('Y-m-d', $now)) {
        return 'Today at ' . date('g:i A', $timestamp);
    }
    
    // If event is tomorrow
    if (date('Y-m-d', $timestamp) === date('Y-m-d', $now + 86400)) {
        return 'Tomorrow at ' . date('g:i A', $timestamp);
    }
    
    // If event is within a week
    if ($diff < 7 * 86400) {
        return date('l \a\t g:i A', $timestamp); // e.g., "Monday at 3:00 PM"
    }
    
    // Otherwise, full date
    return date('M j, Y g:i A', $timestamp);
}


/**
 * Get the full URL for an announcement image
 * Handles both R2 URLs and local paths
 * 
 * @param string $imagePath Image path from database
 * @return string Full URL to the image
 */
function get_announcement_image_url($imagePath) {
    // Note: r2-storage.php must be included before calling this function
    return get_file_url($imagePath);
}

/**
 * Get the full URL for a driver document or avatar
 * Handles both R2 URLs and local paths
 * 
 * @param string $filePath File path from database
 * @return string Full URL to the file
 */
function get_driver_file_url($filePath) {
    // Note: r2-storage.php must be included before calling this function
    return get_file_url($filePath);
}


// ============================================================================
// DRIVER-TO-OFFICER MESSAGING FUNCTIONS
// ============================================================================

/**
 * Get all messages for the current driver
 * Returns the conversation with officers
 * 
 * @param string $driverId Driver ID
 * @return array Array of messages with sender info
 */
function get_my_messages($driverId) {
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    return get_driver_messages($driverId);
}

/**
 * Send a message from driver to officers
 * 
 * @param string $driverId Driver ID
 * @param string $messageText Message content
 * @param array|null $files Files from $_FILES (optional)
 * @return int|false Message ID if successful, false on failure
 */
function send_message_to_officers($driverId, $messageText, $files = null) {
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';
    
    // Get driver's numeric ID for sender_id
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT driver_id FROM drivers WHERE driver_id = :driver_id");
    $stmt->execute(['driver_id' => $driverId]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        return ['success' => false, 'error' => 'Driver not found'];
    }
    
    // For driver messages, sender_id can be a hash of driver_id or just use driver_id
    // Since driver_id is VARCHAR, we'll use crc32 to get an integer
    $senderId = crc32($driverId);
    
    $result = send_driver_message($driverId, 'driver', $senderId, $messageText, $files);
    
    if ($result === false) {
        return ['success' => false, 'error' => 'Failed to send message'];
    }
    
    return ['success' => true, 'message_id' => $result];
}

/**
 * Get count of driver's messages that haven't been replied to by officers
 * For badge counter
 * 
 * @param string $driverId Driver ID
 * @return int Number of unreplied messages
 */
function get_my_unread_reply_count($driverId) {
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    return get_driver_unreplied_count($driverId);
}

/**
 * Check if driver has any messages in the conversation
 * 
 * @param string $driverId Driver ID
 * @return bool True if driver has messages
 */
function has_messages($driverId) {
    require_once __DIR__ . '/../Admin_BOFETODA/config.php';
    return driver_has_messages($driverId);
}

/**
 * Format message timestamp for display
 * Shows "Just now", "5 minutes ago", "Today at 2:30 PM", etc.
 * 
 * @param string $timestamp Message timestamp
 * @return string Formatted time string
 */
function format_message_time($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    // Less than 1 minute
    if ($diff < 60) {
        return 'Just now';
    }
    
    // Less than 1 hour
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    }
    
    // Less than 24 hours (same day)
    if ($diff < 86400 && date('Y-m-d', $time) === date('Y-m-d', $now)) {
        return 'Today at ' . date('g:i A', $time);
    }
    
    // Yesterday
    if ($diff < 172800 && date('Y-m-d', $time) === date('Y-m-d', $now - 86400)) {
        return 'Yesterday at ' . date('g:i A', $time);
    }
    
    // Less than 7 days
    if ($diff < 604800) {
        return date('l \a\t g:i A', $time); // "Monday at 2:30 PM"
    }
    
    // More than 7 days
    return date('M j, Y \a\t g:i A', $time); // "Oct 23, 2026 at 2:30 PM"
}

/**
 * Check if a file is an image based on extension
 * 
 * @param string $filename Filename to check
 * @return bool True if image file
 */
function is_image_file($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
}

/**
 * Get file icon based on file type
 * Returns emoji or icon for file display
 * 
 * @param string $filename Filename
 * @return string Icon/emoji
 */
function get_file_icon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return '📄';
        case 'doc':
        case 'docx':
            return '📝';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return '🖼️';
        default:
            return '📎';
    }
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted size (e.g., "1.5 MB")
 */
function format_file_size($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return round($bytes / 1048576, 1) . ' MB';
    }
}

/**
 * Mark all officer messages as read for a driver
 */
function mark_messages_as_read($driverId) {
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE driver_messages 
            SET is_read = 1 
            WHERE driver_id = ? 
            AND sender_type = 'officer' 
            AND is_read = 0
        ");
        
        return $stmt->execute([$driverId]);
    } catch (PDOException $e) {
        error_log("Error marking messages as read: " . $e->getMessage());
        return false;
    }
}
