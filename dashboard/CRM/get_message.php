<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int} $currentUser */

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Message ID required.');
}

$sql = "
    SELECT
        m.*,
        s.first_name AS sender_first, s.last_name AS sender_last,
        r.first_name AS receiver_first, r.last_name AS receiver_last,
        v.make, v.model, v.stock_number
    FROM messages m
    LEFT JOIN persons s ON m.sender_person_id = s.person_id
    LEFT JOIN persons r ON m.receiver_person_id = r.person_id
    LEFT JOIN vehicles v ON m.vehicle_id = v.vehicle_id
    WHERE m.message_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Message not found.');
}

// Mark as read if the current user is the receiver
if ($row['receiver_person_id'] == $currentUser['person_id'] && $row['is_read'] === 'No') {
    $update = $pdo->prepare("UPDATE messages SET is_read = 'Yes' WHERE message_id = ?");
    $update->execute([$id]);
}

header('Content-Type: application/json');
echo json_encode($row);