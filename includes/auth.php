<?php

/**
 * ----------------------------------------------------
 * Dipsel Auto System
 * Authentication Middleware
 * ----------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['person_id'])) {
    header("Location: ../../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Current User
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    p.*,
    a.admin_id,
    a.admin_level,
    a.admin_number
FROM persons p
LEFT JOIN admins a
    ON p.person_id = a.person_id
WHERE p.person_id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['person_id']]);

$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| User Not Found
|--------------------------------------------------------------------------
*/

if (!$currentUser) {

    session_destroy();

    header("Location: ../../login.php");

    exit;
}

/*
|--------------------------------------------------------------------------
| Account Status
|--------------------------------------------------------------------------
*/

if ($currentUser['status'] !== 'Active') {

    session_destroy();

    header("Location: ../../login.php");

    exit;
}

/*
|--------------------------------------------------------------------------
| Super Admin Protection
|--------------------------------------------------------------------------
|
| Any page inside dashboard/superadmin/
| automatically requires a Super Admin.
|
*/

$currentPath = str_replace("\\", "/", $_SERVER['PHP_SELF']);

if (strpos($currentPath, "/dashboard/superadmin/") !== false) {

    if (
        $currentUser['role'] !== 'Admin' ||
        $currentUser['admin_level'] !== 'SuperAdmin'
    ) {

        http_response_code(403);

        exit("403 - Access Denied");

    }

}