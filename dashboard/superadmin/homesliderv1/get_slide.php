<?php
require_once '../../config/database.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM homepage_sliders WHERE slider_id = ?");
    $stmt->execute([$id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($slide) {
        header('Content-Type: application/json');
        echo json_encode($slide);
        exit;
    }
}
http_response_code(404);
echo json_encode(['error' => 'Slide not found']);