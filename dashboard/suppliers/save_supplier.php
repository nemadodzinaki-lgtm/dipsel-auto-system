<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$supplier_id = (int) ($_POST['supplier_id'] ?? 0);
$supplier_name = trim($_POST['supplier_name'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$website = trim($_POST['website'] ?? '');
$tax_number = trim($_POST['tax_number'] ?? '');
$status = $_POST['status'] ?? 'Active';

// Validate
if (empty($supplier_name)) {
    $_SESSION['error'] = 'Supplier name is required.';
    header('Location: index.php');
    exit;
}

if ($supplier_id > 0) {
    // Update existing
    $sql = "UPDATE suppliers SET
                supplier_name = :name,
                contact_person = :contact,
                phone = :phone,
                email = :email,
                address = :address,
                city = :city,
                website = :website,
                tax_number = :tax,
                status = :status,
                updated_at = NOW()
            WHERE supplier_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'    => $supplier_name,
        ':contact' => $contact_person,
        ':phone'   => $phone,
        ':email'   => $email,
        ':address' => $address,
        ':city'    => $city,
        ':website' => $website,
        ':tax'     => $tax_number,
        ':status'  => $status,
        ':id'      => $supplier_id
    ]);
    $_SESSION['success'] = 'Supplier updated successfully.';
} else {
    // Insert new
    $sql = "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, city, website, tax_number, status, created_at)
            VALUES (:name, :contact, :phone, :email, :address, :city, :website, :tax, :status, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'    => $supplier_name,
        ':contact' => $contact_person,
        ':phone'   => $phone,
        ':email'   => $email,
        ':address' => $address,
        ':city'    => $city,
        ':website' => $website,
        ':tax'     => $tax_number,
        ':status'  => $status
    ]);
    $_SESSION['success'] = 'Supplier added successfully.';
}

header('Location: index.php');
exit;