<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Provider ID required.']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM insurance_providers WHERE provider_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['error' => 'Provider not found.']); exit; }
    echo json_encode($row);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}