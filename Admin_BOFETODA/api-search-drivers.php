<?php
/**
 * API Endpoint: Search Drivers for Assisted Renewal
 * Returns list of drivers matching search query
 * Access: Secretary and President only
 */

require_once __DIR__ . '/config.php';
require_role(['secretary', 'president']);

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => [], 'message' => 'Please enter at least 2 characters']);
    exit;
}

try {
    $results = search_drivers_for_assisted_renewal($query, 10);
    
    // Add eligibility flag to each result
    foreach ($results as &$driver) {
        $driver['can_submit'] = ($driver['has_pending_app'] == 0);
        $driver['status_message'] = $driver['has_pending_app'] > 0 
            ? 'Already has pending application' 
            : 'Eligible for assisted renewal';
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log('Search drivers API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Search failed. Please try again.'
    ]);
}
