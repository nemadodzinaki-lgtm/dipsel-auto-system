<?php
/**
 * AJAX endpoint: returns full sale details with related data
 * Used by viewSale() JavaScript function.
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Sale ID required.');
}

$sql = "
    SELECT
        vs.*,
        v.make, v.model, v.manufacture_year, v.stock_number,
        c.customer_number,
        p_cust.first_name AS customer_first,
        p_cust.last_name AS customer_last,
        p_emp.first_name AS employee_first,
        p_emp.last_name AS employee_last
    FROM vehicle_sales vs
    INNER JOIN vehicles v ON vs.vehicle_id = v.vehicle_id
    INNER JOIN customers c ON vs.customer_id = c.customer_id
    INNER JOIN persons p_cust ON c.person_id = p_cust.person_id
    LEFT JOIN employees e ON vs.employee_id = e.employee_id
    LEFT JOIN persons p_emp ON e.person_id = p_emp.person_id
    WHERE vs.sale_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Sale not found.');
}

header('Content-Type: application/json');
echo json_encode($row);