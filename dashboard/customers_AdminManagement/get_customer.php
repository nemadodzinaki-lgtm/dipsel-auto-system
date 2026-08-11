<?php
/**
 * AJAX endpoint: get full customer data with person details
 * Used by editCustomer() and viewCustomer() JavaScript functions.
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('Customer ID required.');
}

$sql = "
    SELECT
        customers.*,
        persons.first_name,
        persons.middle_name,
        persons.last_name,
        persons.gender,
        persons.date_of_birth,
        persons.id_number,
        persons.passport_number,
        persons.phone,
        persons.email,
        persons.country,
        persons.province,
        persons.city,
        persons.address,
        persons.postal_code,
        persons.status
    FROM customers
    INNER JOIN persons ON customers.person_id = persons.person_id
    WHERE customers.customer_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Customer not found.');
}

header('Content-Type: application/json');
echo json_encode($row);