<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Ensure customer is logged in
if ($_SESSION['user']['role'] !== 'Customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$receiver_person_id = (int) ($_POST['receiver_person_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$vehicle_id = (int) ($_POST['vehicle_id'] ?? 0);
$sender_person_id = $_SESSION['user']['person_id'];

if (!$receiver_person_id || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Receiver and message are required.']);
    exit;
}

// Optionally, verify receiver exists and is an employee/admin (for security)
$check = $pdo->prepare("SELECT person_id FROM persons WHERE person_id = ? AND role IN ('Employee', 'Admin')");
$check->execute([$receiver_person_id]);
if ($check->rowCount() == 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid receiver.']);
    exit;
}

$sql = "INSERT INTO messages (sender_person_id, receiver_person_id, vehicle_id, subject, message, sent_at)
        VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sender_person_id, $receiver_person_id, $vehicle_id, $subject, $message]);

echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);