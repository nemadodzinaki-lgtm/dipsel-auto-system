<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Validate CSRF token (optional but recommended)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token.']);
    exit;
}

// Validate vehicle ID
$vehicle_id = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
if ($vehicle_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid Vehicle ID required.']);
    exit;
}

// Get customer_id from session
if (!isset($currentUser['person_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$currentUser['person_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    http_response_code(403);
    echo json_encode(['error' => 'Customer profile not found.']);
    exit;
}
$customerId = (int) $customer['customer_id'];

// Verify the vehicle exists and is available
$stmt = $pdo->prepare("
    SELECT vehicle_id FROM vehicles 
    WHERE vehicle_id = ? AND status = 'Available' AND available_for_sale = 'Yes'
");
$stmt->execute([$vehicle_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found or not available for viewing.']);
    exit;
}

// Optional: prevent duplicate viewing on the same day (optional – uncomment if desired)
/*
$stmt = $pdo->prepare("
    SELECT viewing_id FROM vehicle_viewings 
    WHERE vehicle_id = ? AND customer_id = ? AND DATE(viewing_date) = CURDATE()
");
$stmt->execute([$vehicle_id, $customerId]);
if ($stmt->fetch()) {
    // Already viewed today – still return success (or you could return an info message)
    echo json_encode(['success' => true, 'message' => 'Already viewed today.']);
    exit;
}
*/

// Insert viewing record
try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO vehicle_viewings (
                vehicle_id, customer_id, viewing_date, viewing_time, location, status
            ) VALUES (
                :vehicle_id, :customer_id, CURDATE(), CURTIME(), 'Online', 'Completed'
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':vehicle_id' => $vehicle_id,
        ':customer_id' => $customerId
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Viewing recorded.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}