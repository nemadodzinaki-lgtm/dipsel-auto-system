<?php
require_once '../../config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $sql = "SELECT * FROM service_bookings WHERE 1=1";
    $params = [];

    if ($id > 0) {
        $sql .= " AND booking_id = :id";
        $params['id'] = $id;
    } else {
        if (!empty($search)) {
            $sql .= " AND (booking_reference LIKE :search OR vehicle_name LIKE :search OR registration_number LIKE :search)";
            $params['search'] = "%$search%";
        }
        if (!empty($status)) {
            $sql .= " AND booking_status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY booking_id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}