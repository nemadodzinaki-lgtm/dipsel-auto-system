<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

$sql = "SELECT v.*, 
               (SELECT vi.image_path FROM vehicle_images vi 
                WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes' 
                LIMIT 1) AS image_path
        FROM vehicles v
        WHERE v.vehicle_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit; }

header('Content-Type: application/json');
echo json_encode($row);