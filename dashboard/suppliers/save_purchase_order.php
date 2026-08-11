<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$po_id = (int) ($_POST['purchase_order_id'] ?? 0);
$supplier_id = (int) ($_POST['supplier_id'] ?? 0);
$order_number = trim($_POST['order_number'] ?? '');
$order_date = $_POST['order_date'] ?? date('Y-m-d');
$expected_delivery = !empty($_POST['expected_delivery']) ? $_POST['expected_delivery'] : null;
$status = $_POST['status'] ?? 'Pending';
$notes = trim($_POST['notes'] ?? '');
$total_amount = (float) ($_POST['total_amount'] ?? 0);

// Items arrays
$part_ids = $_POST['part_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$unit_prices = $_POST['unit_price'] ?? [];

// Validate
if (!$supplier_id) {
    $_SESSION['error'] = 'Please select a supplier.';
    header('Location: index.php');
    exit;
}
if (empty($order_number)) {
    $_SESSION['error'] = 'Order number is required.';
    header('Location: index.php');
    exit;
}
if (count($part_ids) === 0) {
    $_SESSION['error'] = 'Add at least one item.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    if ($po_id > 0) {
        // Update header
        $sql = "UPDATE purchase_orders SET
                    supplier_id = :supplier,
                    order_number = :order_num,
                    order_date = :order_date,
                    expected_delivery = :expected,
                    status = :status,
                    notes = :notes,
                    total_amount = :total,
                    updated_at = NOW()
                WHERE purchase_order_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':supplier'  => $supplier_id,
            ':order_num' => $order_number,
            ':order_date'=> $order_date,
            ':expected'  => $expected_delivery,
            ':status'    => $status,
            ':notes'     => $notes,
            ':total'     => $total_amount,
            ':id'        => $po_id
        ]);

        // Delete existing items
        $pdo->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = :id")->execute([':id' => $po_id]);
    } else {
        // Insert new header
        $sql = "INSERT INTO purchase_orders (supplier_id, order_number, order_date, expected_delivery, status, notes, total_amount, created_at)
                VALUES (:supplier, :order_num, :order_date, :expected, :status, :notes, :total, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':supplier'  => $supplier_id,
            ':order_num' => $order_number,
            ':order_date'=> $order_date,
            ':expected'  => $expected_delivery,
            ':status'    => $status,
            ':notes'     => $notes,
            ':total'     => $total_amount
        ]);
        $po_id = $pdo->lastInsertId();
    }

    // Insert items
    $itemSql = "INSERT INTO purchase_order_items (purchase_order_id, part_id, quantity, unit_price, total_price)
                VALUES (:po_id, :part_id, :qty, :price, :total_price)";
    $itemStmt = $pdo->prepare($itemSql);
    for ($i = 0; $i < count($part_ids); $i++) {
        $part_id = (int) $part_ids[$i];
        $qty = (int) $quantities[$i];
        $unit_price = (float) $unit_prices[$i];
        $total_price = $qty * $unit_price;
        $itemStmt->execute([
            ':po_id'       => $po_id,
            ':part_id'     => $part_id,
            ':qty'         => $qty,
            ':price'       => $unit_price,
            ':total_price' => $total_price
        ]);
    }

    $pdo->commit();
    $_SESSION['success'] = $po_id > 0 ? 'Purchase order updated successfully.' : 'Purchase order created successfully.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error saving purchase order: ' . $e->getMessage();
}

header('Location: index.php');
exit;