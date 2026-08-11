<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');
$results = [];

if (strlen($search) >= 2) {
    // Adjust table/column names to your parts table
    $stmt = $pdo->prepare("
        SELECT part_id, part_name, part_number
        FROM parts
        WHERE part_name LIKE :search OR part_number LIKE :search
        ORDER BY part_name
        LIMIT 20
    ");
    $stmt->execute(['search' => '%' . $search . '%']);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'id'   => (int)$row['part_id'],
            'text' => $row['part_name'] . ' (' . $row['part_number'] . ')'
        ];
    }
}

echo json_encode($results);