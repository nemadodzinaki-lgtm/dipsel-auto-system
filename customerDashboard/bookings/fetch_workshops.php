<?php

require_once '../../config/database.php';

if (!isset($_GET['service_name']) || !isset($_GET['category'])) {
    http_response_code(400);
    exit('Missing parameters');
}

$serviceName = $_GET['service_name'];
$category    = $_GET['category'];

$stmt = $pdo->prepare("
    SELECT 
        w.workshop_id,
        w.workshop_name,
        ws.service_id
    FROM workshops_service ws
    JOIN workshops w ON ws.workshop_id = w.workshop_id
    WHERE ws.service_name = ?
      AND ws.category = ?
      AND ws.status = 'Available'
      AND w.status = 'Open'
    ORDER BY w.workshop_name
");
$stmt->execute([$serviceName, $category]);
$workshops = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($workshops);
exit;