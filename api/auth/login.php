
<?php

session_start();

require '../../config/database.php';

$email = trim($_POST['email']);
$password = $_POST['password'];
$userType = $_POST['user_type'] ?? 'customer'; // 'customer' or 'staff'

$stmt = $pdo->prepare("
    SELECT *
    FROM persons
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['login_error'] = "Invalid Email or Password";
    header("Location: " . ($userType === 'staff' ? '../../staff_login.php' : '../../login.php'));
    exit();
}

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = "Invalid Email or Password";
    header("Location: " . ($userType === 'staff' ? '../../staff_login.php' : '../../login.php'));
    exit();
}

if ($user['status'] != "Active") {
    $_SESSION['login_error'] = "Account is not active.";
    header("Location: " . ($userType === 'staff' ? '../../staff_login.php' : '../../login.php'));
    exit();
}

// ─── Role validation based on user_type ──────────────────────────
if ($userType === 'customer' && $user['role'] !== 'Customer') {
    $_SESSION['login_error'] = "This login page is for customers only. Please use the staff login.";
    header("Location: ../../login.php");
    exit();
}

if ($userType === 'staff' && !in_array($user['role'], ['Admin', 'Employee'])) {
    $_SESSION['login_error'] = "This login page is for staff only. Please use the customer login.";
    header("Location: ../../staff_login.php");
    exit();
}

// ─── Set session ──────────────────────────────────────────────
$_SESSION['person_id'] = $user['person_id'];
$_SESSION['uuid']      = $user['uuid'];
$_SESSION['name']      = $user['first_name'] . " " . $user['last_name'];
$_SESSION['role']      = $user['role'];

// Update last_login
$update = $pdo->prepare("UPDATE persons SET last_login = NOW() WHERE person_id = ?");
$update->execute([$user['person_id']]);

// ─── Handle redirect parameter (only for Customers) ──────────
$redirect = isset($_POST['redirect']) ? trim($_POST['redirect']) : '';

// Only allow internal redirects (starts with '/', no double slashes)
$isSafe = (!empty($redirect) && strpos($redirect, '/') === 0 && strpos($redirect, '//') === false);

if ($isSafe && $user['role'] === 'Customer') {
    // Customer + valid redirect → go to that page (e.g., booking)
    header("Location: " . $redirect);
    exit();
}

// ─── Fallback: role‑based dashboard ─────────────────────────
switch ($user['role']) {
    case 'Admin':
        header("Location: ../../dashboard/superadmin/index.php");
        break;
    case 'Employee':
        header("Location: ../../EmployeesDashboard/employee/index.php");
        break;
    case 'Customer':
        header("Location: ../../customerDashboard/customer/index.php");
        break;
    default:
        header("Location: ../../");
}
exit();