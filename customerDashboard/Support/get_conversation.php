<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if ($_SESSION['role'] != 'Customer') {
    http_response_code(403);
    exit;
}

$person_id = $_SESSION['person_id'];
$other_id = (int) ($_GET['other_id'] ?? 0);
if (!$other_id) {
    http_response_code(400);
    exit;
}

$sql = "SELECT m.*,
               CONCAT(p.first_name, ' ', p.last_name) AS sender_name
        FROM messages m
        JOIN persons p ON m.sender_person_id = p.person_id
        WHERE (sender_person_id = ? AND receiver_person_id = ?)
           OR (sender_person_id = ? AND receiver_person_id = ?)
        ORDER BY m.sent_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$person_id, $other_id, $other_id, $person_id]);
$conversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($conversation);