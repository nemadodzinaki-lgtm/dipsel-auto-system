<?php
require_once '../../config/database.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM insurance_providers WHERE provider_id = ?");
    $stmt->execute([$id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($provider) {
        header('Content-Type: application/json');
        echo json_encode($provider);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Provider not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
}