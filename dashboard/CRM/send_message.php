<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int} $currentUser */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
$sender_id = (int) $currentUser['person_id'];
$receiver_id = (int) ($_POST['receiver_person_id'] ?? 0);
$vehicle_id = (int) ($_POST['vehicle_id'] ?? 0) ?: null;
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate
if (!$receiver_id) {
    $_SESSION['error'] = 'Please select a recipient.';
    header('Location: index.php');
    exit;
}
if (empty($message)) {
    $_SESSION['error'] = 'Message body is required.';
    header('Location: index.php');
    exit;
}

// Insert
$sql = "INSERT INTO messages (sender_person_id, receiver_person_id, vehicle_id, subject, message, sent_at)
        VALUES (:sender, :receiver, :vehicle, :subject, :message, NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':sender' => $sender_id,
    ':receiver' => $receiver_id,
    ':vehicle' => $vehicle_id,
    ':subject' => $subject,
    ':message' => $message,
]);

$_SESSION['success'] = 'Message sent successfully.';
header('Location: index.php');
exit;