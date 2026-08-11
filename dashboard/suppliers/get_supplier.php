<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($supplier ?: []);