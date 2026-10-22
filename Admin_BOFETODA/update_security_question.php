<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$officerId = $officer['id'] ?? '';

// Get return URL from POST or default to dashboard
function safe_return_to($url) {
    if (!$url) {
        return BASE_URL . '/dashboard.php';
    }
    $parts = parse_url($url);
    if (isset($parts['host']) && $parts['host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
        return BASE_URL . '/dashboard.php';
    }
    $path  = $parts['path'] ?? (BASE_URL . '/dashboard.php');
    $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
    return $path . $query;
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
    if (save_security_question($officerId, $questionId, $answer)) {
        header('Location: ' . $returnTo . $separator . 'sqok=1');
    } else {
        header('Location: ' . $returnTo . $separator . 'sqerror=save');
    }
    exit;
}

// If accessed directly via GET, redirect to dashboard
header('Location: ' . BASE_URL . '/dashboard.php');
exit;
