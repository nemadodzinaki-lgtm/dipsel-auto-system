<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Claim ID required.']); exit; }

try {
    $sql = "
        SELECT c.*, cust.customer_number, p.first_name AS cust_first, p.last_name AS cust_last, ip.provider_name
        FROM insurance_claims c
        INNER JOIN customers cust ON c.customer_id = cust.customer_id
        INNER JOIN persons p ON cust.person_id = p.person_id
        INNER JOIN insurance_providers ip ON c.insurance_id = ip.provider_id
        WHERE c.claim_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['error' => 'Claim not found.']); exit; }
    echo json_encode($row);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}