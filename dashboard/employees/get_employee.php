<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/database.php';
$id = (int) ($_GET['id'] ?? 0);
if (!$id) exit('{}');
$stmt = $pdo->prepare("
    SELECT
    e.employee_id,
    e.employee_number,
    e.department,
    e.position,
    e.employee_status,
    e.salary,
    e.hire_date,
    e.employment_type,

    p.first_name,
    p.last_name,
    p.phone,
    p.email,
    p.gender,
    p.date_of_birth,
    p.id_number,
    p.passport_number,
    p.address,
    p.city,
    p.country,
    p.province,
    p.postal_code,
    p.profile_photo,

    CONCAT(s.first_name, ' ', s.last_name) AS supervisor_name
    FROM employees e
    JOIN persons p ON e.person_id = p.person_id
    LEFT JOIN employees se ON e.supervisor_id = se.employee_id
    LEFT JOIN persons s ON se.person_id = s.person_id
    WHERE e.employee_id = ? AND e.deleted_at IS NULL
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($data);