<?php
require_once '../../config/database.php';

$job_card_id = isset($_GET['job_card_id']) ? (int) $_GET['job_card_id'] : 0;

try {
    $sql = "SELECT * FROM service_updates WHERE job_card_id = :job_card_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['job_card_id' => $job_card_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}