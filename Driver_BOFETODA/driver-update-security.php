<?php

require 'driver-database.php';

require_login();

$driver = current_driver();
$driverId = $driver['driver_id'] ?? '';

// Get return URL from POST or default to profile
function safe_return_to($url) {
    if (!$url) {
        return 'driver-profile.php';
    }
    // Only allow relative URLs to prevent open redirect
    if (preg_match('/^https?:\/\//i', $url)) {
        return 'driver-profile.php';
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = (int) ($_POST['question_id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    $returnTo = safe_return_to($_POST['return_to'] ?? '');
    
    $separator = (strpos($returnTo, '?') !== false) ? '&' : '?';
    
    // Validation
    if ($questionId < 1 || $questionId > 5) {
        header('Location: ' . $returnTo . $separator . 'sqerror=question');
        exit;
    }
    
    if ($answer === '') {
        header('Location: ' . $returnTo . $separator . 'sqerror=answer');
        exit;
    }
    
    if (mb_strlen($answer) < 2) {
        header('Location: ' . $returnTo . $separator . 'sqerror=short');
        exit;
    }
    
    // Save security question
    if (driver_save_security_question($driverId, $questionId, $answer)) {
        header('Location: ' . $returnTo . $separator . 'sqok=1');
    } else {
        header('Location: ' . $returnTo . $separator . 'sqerror=save');
    }
    exit;
}

// If accessed directly via GET, redirect to profile
header('Location: driver-profile.php');
exit;
