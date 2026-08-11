<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            $employeeId = $_GET['employee_id'] ?? null;
            if (!$employeeId) {
                http_response_code(400);
                echo json_encode(['error' => 'employee_id required']);
                exit;
            }

            if (isset($_GET['balances']) && $_GET['balances'] == 1) {
                // Return balances
                $stmt = $pdo->prepare("SELECT leave_type, total_days, used_days, remaining_days, year 
                                       FROM leave_balance WHERE employee_id = ?");
                $stmt->execute([$employeeId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            } else {
                // Return requests
                $stmt = $pdo->prepare("SELECT * FROM leave_request WHERE employee_id = ? ORDER BY created_at DESC");
                $stmt->execute([$employeeId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Create new leave request
            $required = ['employee_id', 'leave_type', 'start_date', 'end_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Missing field: $field"]);
                    exit;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO leave_request 
                                   (employee_id, leave_type, start_date, end_date, reason, status, created_at)
                                   VALUES (?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->execute([
                $input['employee_id'],
                $input['leave_type'],
                $input['start_date'],
                $input['end_date'],
                $input['reason'] ?? null
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            // Update status (approve/reject)
            if (empty($input['id']) || empty($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id and status required']);
                exit;
            }
            $allowed = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
            if (!in_array($input['status'], $allowed)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                exit;
            }
            $approvedBy = $input['approved_by_employee_id'] ?? null;
            $stmt = $pdo->prepare("UPDATE leave_request 
                                   SET status = ?, approved_by_employee_id = ? 
                                   WHERE leave_request_id = ?");
            $stmt->execute([$input['status'], $approvedBy, $input['id']]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}