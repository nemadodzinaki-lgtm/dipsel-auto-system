<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Employee') {
    http_response_code(403);
    exit('Unauthorized');
}

$vehicleId = (int) ($_GET['id'] ?? 0);
if (!$vehicleId) {
    echo json_encode(['error' => 'Invalid vehicle ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT v.*,
           (SELECT vi.image_path FROM vehicle_images vi
            WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes'
            LIMIT 1) AS primary_image
    FROM vehicles v
    WHERE v.vehicle_id = ?
");
$stmt->execute([$vehicleId]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    echo json_encode(['error' => 'Vehicle not found']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($vehicle);