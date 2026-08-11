<?php


require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Get customer_id from logged-in user
$customerId = null;
if (isset($currentUser['person_id'])) {
    $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
    $stmt->execute([$currentUser['person_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $customerId = (int) $customer['customer_id'];
    }
}

$sql = "
    SELECT
        v.vehicle_id,
        v.stock_number,
        v.make,
        v.model,
        v.variant,
        v.manufacture_year,
        v.price,
        v.mileage,
        v.body_type,
        v.fuel_type,
        v.transmission,
        v.condition_type,
        v.status,
        vi.image_path AS primary_image,
        CASE WHEN sv.saved_vehicle_id IS NOT NULL THEN 1 ELSE 0 END AS saved
    FROM vehicles v
    LEFT JOIN vehicle_images vi ON vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'Yes'
    LEFT JOIN saved_vehicles sv ON sv.vehicle_id = v.vehicle_id AND sv.customer_id = :customer_id
    WHERE
        v.available_for_sale = 'Yes'
        AND v.status = 'Available'
";

$params = [':customer_id' => $customerId ?: 0];

// --- Filters ---
if (!empty($_GET['make'])) {
    $sql .= " AND v.make = :make";
    $params['make'] = $_GET['make'];
}

if (!empty($_GET['model'])) {
    $sql .= " AND v.model LIKE :model";
    $params['model'] = '%' . $_GET['model'] . '%';
}

if (!empty($_GET['body_type'])) {
    $sql .= " AND v.body_type = :body_type";
    $params['body_type'] = $_GET['body_type'];
}
if (!empty($_GET['fuel_type'])) {
    $sql .= " AND v.fuel_type = :fuel_type";
    $params['fuel_type'] = $_GET['fuel_type'];
}
if (!empty($_GET['transmission'])) {
    $sql .= " AND v.transmission = :transmission";
    $params['transmission'] = $_GET['transmission'];
}
if (!empty($_GET['condition_type'])) {
    $sql .= " AND v.condition_type = :condition_type";
    $params['condition_type'] = $_GET['condition_type'];
}
if (!empty($_GET['min_price'])) {
    $sql .= " AND v.price >= :min_price";
    $params['min_price'] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $sql .= " AND v.price <= :max_price";
    $params['max_price'] = $_GET['max_price'];
}
// Saved only filter
if (!empty($_GET['saved_only']) && $_GET['saved_only'] === '1') {
    $sql .= " AND sv.saved_vehicle_id IS NOT NULL";
}

$sql .= " ORDER BY v.featured DESC, v.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));