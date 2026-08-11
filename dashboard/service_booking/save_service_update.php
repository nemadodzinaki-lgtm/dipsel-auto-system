<?php
require_once '../../config/database.php';

$data = $_POST;
$job_card_id = (int) ($data['job_card_id'] ?? 0);
$updated_by_employee_id = (int) ($data['updated_by_employee_id'] ?? 0);
$update_status = $data['update_status'] ?? '';
$comments = $data['comments'] ?? null;
$notify_customer = $data['notify_customer'] ?? 'Yes';

if ($job_card_id <= 0 || $updated_by_employee_id <= 0 || empty($update_status)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

try {
    $sql = "INSERT INTO service_updates (
        job_card_id, updated_by_employee_id, update_status, comments, notify_customer
    ) VALUES (
        :job_card_id, :updated_by_employee_id, :update_status, :comments, :notify_customer
    )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':job_card_id' => $job_card_id,
        ':updated_by_employee_id' => $updated_by_employee_id,
        ':update_status' => $update_status,
        ':comments' => $comments,
        ':notify_customer' => $notify_customer
    ]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}