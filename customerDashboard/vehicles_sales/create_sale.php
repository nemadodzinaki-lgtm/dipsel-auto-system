<?php
/**
 * POST /vehicles/create_sale.php
 * Creates a sale for the logged-in customer and flips the vehicle's status.
 * The customer_id, sale_reference, and final sale_price are all derived
 * server-side — nothing about who is buying or what they pay is trusted
 * from the client.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// TODO: set this to a real row in your employees table used for self-service
// online sales (a "House Account" employee, for example). Alternatively,
// run this once and remove this constant + its usage below:
//   ALTER TABLE vehicle_sales MODIFY employee_id INT(11) NULL;
const DEFAULT_ONLINE_EMPLOYEE_ID = 1;

if (empty($currentUser['person_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to purchase.']);
    exit;
}

if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not established.']);
    exit;
}

// Resolve the logged-in person to their own customer_id — never trust customer_id from $_POST
$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$currentUser['person_id']]);
$customerRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customerRow) {
    http_response_code(403);
    echo json_encode(['error' => 'No customer profile is linked to your account. Please complete your profile first.']);
    exit;
}
$customer_id = (int) $customerRow['customer_id'];

$vehicle_id      = (int) ($_POST['vehicle_id'] ?? 0);
$deposit_amount  = (float) ($_POST['deposit_amount'] ?? 0);
$discount_amount = (float) ($_POST['discount_amount'] ?? 0);
$payment_method  = $_POST['payment_method'] ?? 'EFT';
$payment_status  = $_POST['payment_status'] ?? 'Pending';
$delivery_date   = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
$notes           = trim($_POST['notes'] ?? '');

$validPaymentMethods = ['Cash', 'EFT', 'Bank Transfer', 'Card', 'Finance'];
$validPaymentStatus  = ['Pending', 'Partial', 'Paid'];
if (!in_array($payment_method, $validPaymentMethods, true)) {
    $payment_method = 'EFT';
}
if (!in_array($payment_status, $validPaymentStatus, true)) {
    $payment_status = 'Pending';
}

if (!$vehicle_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Vehicle ID required']);
    exit;
}
if ($deposit_amount < 0 || $discount_amount < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Deposit and discount amounts cannot be negative.']);
    exit;
}

/** Generate a unique sale reference, retrying on the rare collision. */
function generateSaleReference(PDO $pdo): string
{
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = 'SALE-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $check = $pdo->prepare("SELECT 1 FROM vehicle_sales WHERE sale_reference = ?");
        $check->execute([$candidate]);
        if (!$check->fetch()) {
            return $candidate;
        }
    }
    // Extremely unlikely fallback: append a high-resolution timestamp
    return 'SALE-' . date('Ymd') . '-' . substr((string) microtime(true), -6);
}

try {
    $pdo->beginTransaction();

    // Lock the vehicle row so two concurrent purchases can't both succeed
    $stmt = $pdo->prepare(
        "SELECT price, status, advertised, available_for_sale
         FROM vehicles WHERE vehicle_id = ? FOR UPDATE"
    );
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found.']);
        exit;
    }
    if ($vehicle['status'] !== 'Available' || $vehicle['available_for_sale'] !== 'Yes') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'This vehicle is no longer available for purchase.']);
        exit;
    }

    // The only source of truth for price is the database, never the client
    $listPrice  = (float) $vehicle['price'];
    $sale_price = $listPrice - $discount_amount;

    if ($discount_amount > $listPrice) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Discount cannot exceed the vehicle price.']);
        exit;
    }
    if ($deposit_amount > $sale_price) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'Deposit cannot exceed the sale price.']);
        exit;
    }

    $sale_reference = generateSaleReference($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO vehicle_sales (
            vehicle_id, customer_id, employee_id, sale_reference,
            sale_price, deposit_amount, discount_amount,
            payment_method, payment_status, sale_status,
            sale_date, delivery_date, notes
        ) VALUES (
            :vehicle_id, :customer_id, :employee_id, :sale_reference,
            :sale_price, :deposit_amount, :discount_amount,
            :payment_method, :payment_status, 'Pending',
            NOW(), :delivery_date, :notes
        )"
    );
    $stmt->execute([
        ':vehicle_id'      => $vehicle_id,
        ':customer_id'     => $customer_id,
        ':employee_id'     => DEFAULT_ONLINE_EMPLOYEE_ID,
        ':sale_reference'  => $sale_reference,
        ':sale_price'      => $sale_price,
        ':deposit_amount'  => $deposit_amount,
        ':discount_amount' => $discount_amount,
        ':payment_method'  => $payment_method,
        ':payment_status'  => $payment_status,
        ':delivery_date'   => $delivery_date,
        ':notes'           => $notes,
    ]);
    $sale_id = $pdo->lastInsertId();

    // Flip vehicle status so it drops out of the catalog immediately
    $stmt = $pdo->prepare(
        "UPDATE vehicles SET status = 'Sold', available_for_sale = 'No' WHERE vehicle_id = ?"
    );
    $stmt->execute([$vehicle_id]);

    // Transfer ownership to the buying customer's person record
    $stmt = $pdo->prepare("SELECT person_id FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($person) {
        $stmt = $pdo->prepare(
            "UPDATE vehicles SET current_owner_person_id = ? WHERE vehicle_id = ?"
        );
        $stmt->execute([$person['person_id'], $vehicle_id]);
    }

    $pdo->commit();
    echo json_encode([
        'success'        => true,
        'sale_id'        => $sale_id,
        'sale_reference' => $sale_reference,
        'sale_price'     => $sale_price,
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('create_sale error: ' . $e->getMessage());
    echo json_encode(['error' => 'Could not complete the sale. Please try again.']);
}