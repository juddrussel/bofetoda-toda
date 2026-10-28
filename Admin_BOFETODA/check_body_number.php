<?php
// Separate endpoint for body number checking
// Use output buffering to catch any unexpected output
ob_start();

try {
    require_once __DIR__ . '/config.php';
    require_login();
    
    // Clear any output that might have been generated
    ob_clean();
    
    header('Content-Type: application/json');
    
    $bodyNo = trim($_GET['body_no'] ?? '');
    $currentDriverId = trim($_GET['current_driver_id'] ?? '');
    
    // Log for debugging
    error_log("Body number check: bodyNo='$bodyNo', currentDriverId='$currentDriverId'");
    
    if (empty($bodyNo)) {
        echo json_encode(['available' => true, 'message' => 'No body number provided']);
        exit;
    }
    
    $pdo = get_pdo();
    // Use TRIM to ensure exact matching regardless of spaces
    $query = 'SELECT driver_id, name FROM drivers WHERE TRIM(body_no) = :body_no AND is_archived = 0';
    $params = ['body_no' => $bodyNo];
    
    // If editing, exclude the current driver
    if (!empty($currentDriverId)) {
        $query .= ' AND driver_id != :current_id';
        $params['current_id'] = $currentDriverId;
    }
    
    error_log("Query: $query, Params: " . json_encode($params));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Result: " . json_encode($exists));
    
    if ($exists) {
        $message = 'Body number is already in use by ' . $exists['name'] . ' (' . $exists['driver_id'] . ')';
        echo json_encode([
            'available' => false,
            'message' => $message,
            'driver_id' => $exists['driver_id'],
            'driver_name' => $exists['name']
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Body number is available'
        ]);
    }
} catch (PDOException $e) {
    ob_clean();
    error_log('Body number check PDO error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'available' => false, 
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    ob_clean();
    error_log('Body number check error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'available' => false, 
        'error' => true,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    ob_end_flush();
}
