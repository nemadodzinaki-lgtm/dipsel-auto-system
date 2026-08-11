<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

// Get header with supplier name
$stmt = $pdo->prepare("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.purchase_order_id = :id
");
$stmt->execute([':id' => $id]);
$header = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$header) {
    echo json_encode([]);
    exit;
}

// Get items with part name (assuming parts table has part_id, part_name)
$itemsStmt = $pdo->prepare("
    SELECT poi.*, p.part_name
    FROM purchase_order_items poi
    LEFT JOIN parts p ON poi.part_id = p.part_id
    WHERE poi.purchase_order_id = :id
");
$itemsStmt->execute([':id' => $id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['header' => $header, 'items' => $items]);