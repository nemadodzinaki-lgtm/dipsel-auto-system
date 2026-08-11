<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');

$results = [];

$stmt = $pdo->prepare("
    SELECT
        person_id,
        first_name,
        last_name,
        id_number
    FROM persons
    WHERE CONCAT(first_name, ' ', last_name) LIKE :search1
       OR first_name LIKE :search2
       OR last_name LIKE :search3
    ORDER BY last_name, first_name
    LIMIT 20
");

$stmt->execute([
    ':search1' => '%' . $search . '%',
    ':search2' => '%' . $search . '%',
    ':search3' => '%' . $search . '%'
]);
    while ($person = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $results[] = [
            'id' => $person['person_id'],
            'text' => $person['last_name'] . ', ' .
                      $person['first_name'] .
                      (!empty($person['id_number'])
                          ? ' (ID: ' . $person['id_number'] . ')'
                          : '')
        ];
    }

echo json_encode($results);
exit;