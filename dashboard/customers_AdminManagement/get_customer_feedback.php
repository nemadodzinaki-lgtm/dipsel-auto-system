<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$customer_id = (int) ($_GET['customer_id'] ?? 0);
if (!$customer_id) { http_response_code(400); exit; }

$sql = "SELECT * FROM customer_feedback WHERE customer_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($feedbacks);