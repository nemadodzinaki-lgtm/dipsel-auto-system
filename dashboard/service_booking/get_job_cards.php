<?php
require_once '../../config/database.php';

$booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$job_card_id = isset($_GET['job_card_id']) ? (int) $_GET['job_card_id'] : 0;

try {
    $sql = "SELECT * FROM job_cards WHERE 1=1";
    $params = [];
    if ($booking_id > 0) {
        $sql .= " AND booking_id = :booking_id";
        $params['booking_id'] = $booking_id;
    } elseif ($job_card_id > 0) {
        $sql .= " AND job_card_id = :job_card_id";
        $params['job_card_id'] = $job_card_id;
    } else {
        $sql .= " AND 1=0"; // return empty
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}