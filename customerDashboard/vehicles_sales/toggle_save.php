<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$vehicle_id = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
if (!$vehicle_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Vehicle ID required']);
    exit;
}

// Get customer_id
$customerId = null;
if (isset($currentUser['person_id'])) {
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
    $stmt->execute([$currentUser['person_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $customerId = (int) $customer['customer_id'];
    }
}
if (!$customerId) {
    http_response_code(403);
    echo json_encode(['error' => 'Customer profile not found']);
    exit;
}

// Check if already saved
$stmt = $pdo->prepare("SELECT saved_vehicle_id FROM saved_vehicles WHERE customer_id = ? AND vehicle_id = ?");
$stmt->execute([$customerId, $vehicle_id]);
$exists = $stmt->fetch();

if ($exists) {
    // Unsave
    $stmt = $pdo->prepare("DELETE FROM saved_vehicles WHERE customer_id = ? AND vehicle_id = ?");
    $stmt->execute([$customerId, $vehicle_id]);
    $action = 'unsaved';
} else {
    // Save
    $stmt = $pdo->prepare("INSERT INTO saved_vehicles (customer_id, vehicle_id) VALUES (?, ?)");
    $stmt->execute([$customerId, $vehicle_id]);
    $action = 'saved';
}

echo json_encode(['success' => true, 'action' => $action]);