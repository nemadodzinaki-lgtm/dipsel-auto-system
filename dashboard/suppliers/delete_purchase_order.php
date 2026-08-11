<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    // Delete items first (cascade may handle, but we do explicitly)
    $pdo->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM purchase_orders WHERE purchase_order_id = :id")->execute([':id' => $id]);
    $_SESSION['success'] = 'Purchase order deleted.';
} else {
    $_SESSION['error'] = 'Invalid ID.';
}
header('Location: index.php');
exit;