<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Employee') {
    http_response_code(403);
    exit('Unauthorized');
}

$testDriveId = (int) ($_GET['id'] ?? 0);
if (!$testDriveId) {
    echo json_encode(['error' => 'Invalid test drive ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        td.*,
        v.make, v.model, v.manufacture_year, v.stock_number, v.registration_number,
        (SELECT vi.image_path FROM vehicle_images vi WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes' LIMIT 1) AS vehicle_image,
        c.customer_number,
        p.first_name AS customer_first_name,
        p.last_name AS customer_last_name,
        p.phone AS customer_phone,
        p.email AS customer_email,
        p.address AS customer_address,
        p.city AS customer_city,
        p.province AS customer_province,
        c.driver_license
    FROM vehicle_test_drives td
    JOIN vehicles v ON td.vehicle_id = v.vehicle_id
    JOIN customers c ON td.customer_id = c.customer_id
    JOIN persons p ON c.person_id = p.person_id
    WHERE td.test_drive_id = ?
");
$stmt->execute([$testDriveId]);
$data = $stmt->fetch();

if (!$data) {
    echo json_encode(['error' => 'Test drive not found']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($data);