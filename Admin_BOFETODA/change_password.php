<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();

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
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $returnTo = safe_return_to($_POST['return_to'] ?? '');

    $separator = (strpos($returnTo, '?') !== false) ? '&' : '?';

    if ($new === '' || $new !== $confirm) {
        header('Location: ' . $returnTo . $separator . 'pwerror=mismatch');
        exit;
    }

    $ok = db_change_officer_password($officer['id'] ?? '', $current, $new);

    if ($ok) {
        header('Location: ' . $returnTo . $separator . 'pwok=1');
    } else {
        header('Location: ' . $returnTo . $separator . 'pwerror=invalid');
    }
    exit;
}

header('Location: ' . BASE_URL . '/dashboard.php');
exit;
