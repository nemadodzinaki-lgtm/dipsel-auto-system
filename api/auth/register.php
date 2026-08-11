<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../../config/database.php';

// ─── Helper Functions ──────────────────────────────────────────────

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function generateCustomerNumber(PDO $pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
    $count = $stmt->fetchColumn() + 1;
    return "CUS" . str_pad($count, 6, "0", STR_PAD_LEFT);
}

/**
 * Validate South African ID number.
 * Removed Luhn checksum – only validates length, birth date, and gender.
 * Returns array with 'valid' (bool), 'errors' (array), and extracted data.
 */
function validateSAID($id, $dob, $gender) {
    $errors = [];

    // 1. Must be 13 digits
    if (!preg_match('/^\d{13}$/', $id)) {
        $errors[] = "ID number must be exactly 13 digits.";
        return ['valid' => false, 'errors' => $errors];
    }

    // 2. Extract birth date from first 6 digits: YYMMDD
    $yy = substr($id, 0, 2);
    $mm = substr($id, 2, 2);
    $dd = substr($id, 4, 2);
    // Convert YY to full year (handle 2000+)
    $currentYY = (int)date('y');
    $year = ((int)$yy <= $currentYY) ? 2000 + (int)$yy : 1900 + (int)$yy;
    if (!checkdate($mm, $dd, $year)) {
        $errors[] = "Invalid birth date in ID number.";
    }

    // 3. Compare with provided date_of_birth
    if (!empty($dob)) {
        $dobObj = DateTime::createFromFormat('Y-m-d', $dob);
        if ($dobObj) {
            $idDob = sprintf('%04d-%02d-%02d', $year, $mm, $dd);
            if ($idDob !== $dobObj->format('Y-m-d')) {
                $errors[] = "ID number does not match the provided date of birth.";
            }
        }
    }

    // 4. Extract gender from 7th digit (0-4 female, 5-9 male)
    $genderDigit = (int)substr($id, 6, 1);
    $idGender = ($genderDigit < 5) ? 'Female' : 'Male';
    if (!empty($gender) && $idGender !== $gender) {
        $errors[] = "ID number indicates a different gender than selected.";
    }

    // 5. Luhn checksum REMOVED – no longer validated

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'birth_date' => sprintf('%04d-%02d-%02d', $year, $mm, $dd),
        'gender' => $idGender,
        'citizenship' => (substr($id, 10, 1) == 0) ? 'SA citizen' : 'Permanent resident'
    ];
}

// ─── Only POST requests ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit();
}

// ─── Collect and sanitise inputs ──────────────────────────────────
$input = [
    'first_name'       => trim($_POST['first_name'] ?? ''),
    'middle_name'      => trim($_POST['middle_name'] ?? ''),
    'last_name'        => trim($_POST['last_name'] ?? ''),
    'gender'           => $_POST['gender'] ?? '',
    'date_of_birth'    => $_POST['date_of_birth'] ?? '',
    'id_number'        => trim($_POST['id_number'] ?? ''),
    'passport_number'  => trim($_POST['passport_number'] ?? ''),
    'phone'            => trim($_POST['phone'] ?? ''),
    'email'            => strtolower(trim($_POST['email'] ?? '')),
    'password'         => $_POST['password'] ?? '',
    'confirm_password' => $_POST['confirm_password'] ?? '',
    'country'          => trim($_POST['country'] ?? ''),
    'province'         => trim($_POST['province'] ?? ''),
    'city'             => trim($_POST['city'] ?? ''),
    'address'          => trim($_POST['address'] ?? ''),
    'postal_code'      => trim($_POST['postal_code'] ?? ''),
    'driver_license'   => trim($_POST['driver_license'] ?? ''),
    'preferred_contact' => $_POST['preferred_contact'] ?? 'Email',
    'marketing_consent' => isset($_POST['marketing_consent']) ? 1 : 0
];

// ─── Validation ────────────────────────────────────────────────────
$errors = [];

// Required fields
$required = ['first_name', 'last_name', 'email', 'password', 'phone', 'date_of_birth', 'gender', 'country', 'province', 'city'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
    }
}

// Email
if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

// Password
if (!empty($input['password'])) {
    if ($input['password'] !== $input['confirm_password']) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($input['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $input['password'])) {
        $errors[] = "Password must contain an uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $input['password'])) {
        $errors[] = "Password must contain a lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $input['password'])) {
        $errors[] = "Password must contain a number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $input['password'])) {
        $errors[] = "Password must contain a special character.";
    }
}

// Phone (SA format)
if (!empty($input['phone']) && !preg_match('/^(?:\+27|0)[0-9]{9}$/', preg_replace('/\s+/', '', $input['phone']))) {
    $errors[] = "Phone must be a valid SA number (e.g. 0739131020 or +27739131020).";
}

// Date of birth
if (!empty($input['date_of_birth'])) {
    $dob = DateTime::createFromFormat('Y-m-d', $input['date_of_birth']);
    if (!$dob) {
        $errors[] = "Invalid date format.";
    } else {
        $now = new DateTime();
        if ($dob > $now) {
            $errors[] = "Date of birth cannot be in the future.";
        }
        if ($now->diff($dob)->y < 18) {
            $errors[] = "You must be at least 18 years old.";
        }
    }
}

// ID / Passport
if (!empty($input['id_number'])) {
    $idValidation = validateSAID($input['id_number'], $input['date_of_birth'], $input['gender']);
    if (!$idValidation['valid']) {
        $errors = array_merge($errors, $idValidation['errors']);
    }
} else {
    if (empty($input['passport_number'])) {
        $errors[] = "Either ID number or Passport number is required.";
    }
}

if (!empty($input['passport_number']) && strlen($input['passport_number']) < 4) {
    $errors[] = "Passport number must be at least 4 characters.";
}

// Postal code
if (!empty($input['postal_code']) && !preg_match('/^\d{4,5}$/', $input['postal_code'])) {
    $errors[] = "Postal code must be 4-5 digits.";
}

// ─── If errors, store and redirect ────────────────────────────────
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_input'] = $input;
    header("Location: ../../register.php");
    exit();
}

// ─── Database uniqueness checks ───────────────────────────────────
$duplicateChecks = [
    'email' => 'Email already exists.',
    'phone' => 'Phone number already exists.'
];
if (!empty($input['id_number'])) {
    $duplicateChecks['id_number'] = 'ID Number already exists.';
}
foreach ($duplicateChecks as $field => $msg) {
    $stmt = $pdo->prepare("SELECT person_id FROM persons WHERE $field = ?");
    $stmt->execute([$input[$field]]);
    if ($stmt->rowCount()) {
        $_SESSION['register_errors'] = [$msg];
        $_SESSION['register_input'] = $input;
        header("Location: ../../register.php");
        exit();
    }
}

// ─── Profile photo ──────────────────────────────────────────────────
$photo = "default.png";
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($_FILES['profile_photo']['size'] > $maxSize) {
        $_SESSION['register_errors'] = ["Profile photo too large (max 5MB)."];
        $_SESSION['register_input'] = $input;
        header("Location: ../../register.php");
        exit();
    }
    if (in_array($ext, $allowed)) {
        $photo = uniqid() . '.' . $ext;
        $uploadPath = "../../assets/uploads/" . $photo;
        if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
            $_SESSION['register_errors'] = ["Failed to upload photo."];
            $_SESSION['register_input'] = $input;
            header("Location: ../../register.php");
            exit();
        }
    } else {
        $_SESSION['register_errors'] = ["Invalid image format. Allowed: jpg, jpeg, png, webp."];
        $_SESSION['register_input'] = $input;
        header("Location: ../../register.php");
        exit();
    }
}

// ─── Hash password ─────────────────────────────────────────────────
$passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

// ─── Generate UUID, customer number, etc. ────────────────────────
$uuid = generateUUID();
$customer_number = generateCustomerNumber($pdo);
$status = "Active";
$role = "Customer";
$email_verified = 0;
$loyalty_points = 0;

// ─── Insert into database (transaction) ──────────────────────────
try {
    $pdo->beginTransaction();

    // Insert person
    $stmt = $pdo->prepare("
        INSERT INTO persons (
            uuid, first_name, middle_name, last_name, gender,
            date_of_birth, id_number, passport_number,
            phone, email, password, profile_photo,
            country, province, city, address, postal_code,
            role, status, email_verified
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?
        )
    ");
    $stmt->execute([
        $uuid,
        $input['first_name'],
        $input['middle_name'],
        $input['last_name'],
        $input['gender'],
        $input['date_of_birth'],
        $input['id_number'],
        $input['passport_number'],
        $input['phone'],
        $input['email'],
        $passwordHash,
        $photo,
        $input['country'],
        $input['province'],
        $input['city'],
        $input['address'],
        $input['postal_code'],
        $role,
        $status,
        $email_verified
    ]);

    $person_id = $pdo->lastInsertId();

    // Insert customer
    $stmt = $pdo->prepare("
        INSERT INTO customers (
            person_id, customer_number,
            driver_license, preferred_contact,
            marketing_consent, loyalty_points
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $person_id,
        $customer_number,
        $input['driver_license'],
        $input['preferred_contact'],
        $input['marketing_consent'],
        $loyalty_points
    ]);

    $pdo->commit();

    $_SESSION['success'] = "Registration Successful.";
    header("Location: ../../pages/login/login.php");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    $_SESSION['register_errors'] = ["Registration failed. Please try again later."];
    $_SESSION['register_input'] = $input;
    header("Location: ../../register.php");
    exit();
}
?>