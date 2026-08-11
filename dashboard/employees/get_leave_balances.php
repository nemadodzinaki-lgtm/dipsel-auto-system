<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/database.php';
$empId = (int) ($_GET['employee_id'] ?? 0);
if (!$empId) exit('[]');
$stmt = $pdo->prepare("SELECT * FROM leave_balances WHERE employee_id = ? ORDER BY year DESC, leave_type");
$stmt->execute([$empId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));