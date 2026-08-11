<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Slide ID required.');
}

$stmt = $pdo->prepare("SELECT * FROM homepage_sliders WHERE slider_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Slide not found.');
}

header('Content-Type: application/json');
echo json_encode($row);