<?php
/**
 * Fetch single record (service or workshop) as JSON
 * Usage:
 *   get_service.php?id=123           -> returns service data
 *   get_service.php?type=workshop&id=123 -> returns workshop data
 */

require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'service';

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

try {
    if ($type === 'workshop') {
        $stmt = $pdo->prepare("SELECT * FROM workshops WHERE workshop_id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Workshop not found']);
            exit;
        }
        echo json_encode($data);
    } else {
        // Default: fetch service with workshop name
        $stmt = $pdo->prepare("
            SELECT ws.*, w.workshop_name
            FROM workshop_services ws
            INNER JOIN workshops w ON ws.workshop_id = w.workshop_id
            WHERE ws.service_id = ?
        ");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
            exit;
        }
        echo json_encode($data);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}