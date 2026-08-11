<?php
require_once '../../config/database.php';

$job_card_id = isset($_POST['job_card_id']) ? (int) $_POST['job_card_id'] : 0;

try {
    // Delete updates first
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM service_updates WHERE job_card_id = ?");
    $stmt->execute([$job_card_id]);
    $stmt = $pdo->prepare("DELETE FROM job_cards WHERE job_card_id = ?");
    $stmt->execute([$job_card_id]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}