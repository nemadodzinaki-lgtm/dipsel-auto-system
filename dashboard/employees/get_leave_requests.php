<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/database.php';
$empId = (int) ($_GET['employee_id'] ?? 0);
if (!$empId) exit('[]');
$stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$empId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));