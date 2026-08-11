<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = :id");
    $stmt->execute([':id' => $id]);
    $_SESSION['success'] = 'Supplier deleted successfully.';
} else {
    $_SESSION['error'] = 'Invalid supplier ID.';
}

header('Location: index.php');
exit;