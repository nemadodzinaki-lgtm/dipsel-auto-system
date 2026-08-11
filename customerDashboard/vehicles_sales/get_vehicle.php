<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

$vehicle_id = isset($_GET['vehicle_id']) ? (int) $_GET['vehicle_id'] : 0;
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

try {
    $stmt = $pdo->prepare("
        SELECT v.*,
               CASE WHEN sv.saved_vehicle_id IS NOT NULL THEN 1 ELSE 0 END AS saved
        FROM vehicles v
        LEFT JOIN saved_vehicles sv ON sv.vehicle_id = v.vehicle_id AND sv.customer_id = :customer_id
        WHERE v.vehicle_id = :vehicle_id
    ");
    $stmt->execute([':customer_id' => $customerId ?: 0, ':vehicle_id' => $vehicle_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$vehicle) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }

    // Images
    $stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY display_order, is_primary DESC");
    $stmt->execute([$vehicle_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['vehicle' => $vehicle, 'images' => $images]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}