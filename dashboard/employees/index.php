<?php
/**
 * Employee Management - Complete Module
 * Handles employees, documents, leave balances, and leave requests (CRUD + Approve/Decline)
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

// ----------------------------------------------------------------------
// 1. HELPER FUNCTIONS
// ----------------------------------------------------------------------
function generateEmployeeNumber(PDO $pdo): string {
    $prefix = 'EMP' . date('Y') . '-';
    $pdo->exec("LOCK TABLES employees WRITE");
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(employee_number, LENGTH(:prefix) + 1) AS UNSIGNED)) AS last_num 
                           FROM employees WHERE employee_number LIKE :like_prefix");
    $stmt->execute([':prefix' => $prefix, ':like_prefix' => $prefix . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row['last_num'] ?? 0) + 1;
    $num = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    $pdo->exec("UNLOCK TABLES");
    return $num;
}

function sanitiseInput($value, $default = '') {
    $trimmed = trim($value ?? '');
    return $trimmed === '' ? $default : $trimmed;
}

function uploadFile($file, $type, &$errors) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
        $errors[] = 'File upload error: ' . $file['error'];
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'File size exceeds 5MB limit.';
        return null;
    }
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if ($type === 'document') {
        $allowedMime = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
        $allowedExt = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedMime, true)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedMime);
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        $errors[] = 'Invalid file extension. Allowed: ' . implode(', ', $allowedExt);
        return null;
    }
    if ($type === 'profile' && !getimagesize($file['tmp_name'])) {
        $errors[] = 'Uploaded file is not a valid image.';
        return null;
    }
    $subDir = ($type === 'profile') ? 'profiles' : 'documents';
    $uploadDir = "../../uploads/$subDir/";
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        $errors[] = 'Upload directory could not be created.';
        return null;
    }
    $filename = uniqid($type . '_') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'Failed to move uploaded file.';
        return null;
    }
    return "uploads/$subDir/" . $filename;
}

function deleteFile($path) {
    if ($path && file_exists("../../" . $path)) {
        unlink("../../" . $path);
    }
}

function exists($pdo, $table, $column, $value, $excludeId = null, $idColumn = 'id') {
    $sql = "SELECT 1 FROM $table WHERE $column = :value";
    $params = [':value' => $value];
    if ($excludeId) {
        $sql .= " AND $idColumn != :exclude";
        $params[':exclude'] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ? true : false;
}

function dateDiffDays($start, $end) {
    $start = new DateTime($start);
    $end = new DateTime($end);
    $diff = $start->diff($end);
    return $diff->days + 1;
}

// ----------------------------------------------------------------------
// 2. CSRF TOKEN
// ----------------------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ----------------------------------------------------------------------
// 3. PROCESS ACTIONS
// ----------------------------------------------------------------------
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$errors = [];

// Verify CSRF for all POST and for GET actions that modify data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token.';
        header('Location: index.php');
        exit;
    }
}
$getActions = ['approve_leave', 'decline_leave', 'delete_leave_request', 'delete', 'delete_document'];
if (in_array($action, $getActions) && isset($_GET['csrf_token']) && $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: index.php');
    exit;
}

// --- ADD EMPLOYEE ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Person data
    $firstName   = sanitiseInput($_POST['first_name'] ?? '');
    $middleName  = sanitiseInput($_POST['middle_name'] ?? '');
    $lastName    = sanitiseInput($_POST['last_name'] ?? '');
    $gender      = sanitiseInput($_POST['gender'] ?? '');
    $dob         = $_POST['date_of_birth'] ?? null;
    $idNumber    = sanitiseInput($_POST['id_number'] ?? '');
    $passport    = sanitiseInput($_POST['passport_number'] ?? '');
    $phone       = sanitiseInput($_POST['phone'] ?? '');
    $email       = sanitiseInput($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $country     = sanitiseInput($_POST['country'] ?? 'South Africa');
    $province    = sanitiseInput($_POST['province'] ?? '');
    $city        = sanitiseInput($_POST['city'] ?? '');
    $address     = sanitiseInput($_POST['address'] ?? '');
    $postalCode  = sanitiseInput($_POST['postal_code'] ?? '');

    // Employee data
    $department      = sanitiseInput($_POST['department'] ?? '');
    $position        = sanitiseInput($_POST['position'] ?? '');
    $employmentType  = sanitiseInput($_POST['employment_type'] ?? 'Permanent');
    $hireDate        = $_POST['hire_date'] ?? null;
    $salary          = !empty($_POST['salary']) ? (float) $_POST['salary'] : null;
    $supervisorId    = !empty($_POST['supervisor_id']) ? (int) $_POST['supervisor_id'] : null;
    $employeeStatus  = sanitiseInput($_POST['employee_status'] ?? 'Active');

    // Validate required
    if (empty($firstName) || empty($lastName) || empty($gender) || empty($phone) || empty($email) || empty($password) || empty($department) || empty($position) || empty($hireDate)) {
        $errors[] = 'Please fill in all required fields.';
    }
    if ($password !== $confirmPass) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (exists($pdo, 'persons', 'email', $email)) {
        $errors[] = 'Email already exists.';
    }

    // Profile photo removed – no upload
    $profilePhoto = null;

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert into persons (without profile_photo)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $personStmt = $pdo->prepare("
                INSERT INTO persons 
                (uuid, first_name, middle_name, last_name, gender, date_of_birth, id_number, passport_number, 
                 phone, email, password, country, province, city, address, postal_code, role, status)
                VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Employee', 'Active')
            ");
            $personStmt->execute([
                $firstName, $middleName, $lastName, $gender, $dob, $idNumber, $passport,
                $phone, $email, $hashedPassword, $country, $province, $city, $address, $postalCode
            ]);
            $personId = $pdo->lastInsertId();

            // Generate employee number
            $employeeNumber = generateEmployeeNumber($pdo);

            // Insert into employees
            $empStmt = $pdo->prepare("
                INSERT INTO employees 
                (person_id, employee_number, department, position, employment_type, hire_date, salary, supervisor_id, employee_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $empStmt->execute([
                $personId, $employeeNumber, $department, $position, $employmentType,
                $hireDate, $salary, $supervisorId, $employeeStatus
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Employee added successfully.";
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: index.php');
        exit;
    }
}

// --- EDIT EMPLOYEE ---
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    if (!$employeeId) {
        $_SESSION['error'] = 'Invalid employee ID.';
        header('Location: index.php');
        exit;
    }

    // Fetch current person and employee data
    $stmt = $pdo->prepare("SELECT p.*, e.* FROM employees e JOIN persons p ON e.person_id = p.person_id WHERE e.employee_id = ?");
    $stmt->execute([$employeeId]);
    $current = $stmt->fetch();
    if (!$current) {
        $_SESSION['error'] = 'Employee not found.';
        header('Location: index.php');
        exit;
    }

    $errors = [];

    // Person data
    $firstName   = sanitiseInput($_POST['first_name'] ?? '');
    $middleName  = sanitiseInput($_POST['middle_name'] ?? '');
    $lastName    = sanitiseInput($_POST['last_name'] ?? '');
    $gender      = sanitiseInput($_POST['gender'] ?? '');
    $dob         = $_POST['date_of_birth'] ?? null;
    $idNumber    = sanitiseInput($_POST['id_number'] ?? '');
    $passport    = sanitiseInput($_POST['passport_number'] ?? '');
    $phone       = sanitiseInput($_POST['phone'] ?? '');
    $email       = sanitiseInput($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $country     = sanitiseInput($_POST['country'] ?? 'South Africa');
    $province    = sanitiseInput($_POST['province'] ?? '');
    $city        = sanitiseInput($_POST['city'] ?? '');
    $address     = sanitiseInput($_POST['address'] ?? '');
    $postalCode  = sanitiseInput($_POST['postal_code'] ?? '');

    // Employee data
    $department      = sanitiseInput($_POST['department'] ?? '');
    $position        = sanitiseInput($_POST['position'] ?? '');
    $employmentType  = sanitiseInput($_POST['employment_type'] ?? 'Permanent');
    $hireDate        = $_POST['hire_date'] ?? null;
    $salary          = !empty($_POST['salary']) ? (float) $_POST['salary'] : null;
    $supervisorId    = !empty($_POST['supervisor_id']) ? (int) $_POST['supervisor_id'] : null;
    $employeeStatus  = sanitiseInput($_POST['employee_status'] ?? 'Active');

    // Validate required
    if (empty($firstName) || empty($lastName) || empty($gender) || empty($phone) || empty($email) || empty($department) || empty($position) || empty($hireDate)) {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!empty($password) && $password !== $confirmPass) {
        $errors[] = 'Passwords do not match.';
    }
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (exists($pdo, 'persons', 'email', $email, $current['person_id'], 'person_id')) {
        $errors[] = 'Email already exists.';
    }

    // Profile photo removed – keep existing but not updating
    $profilePhoto = $current['profile_photo']; // we keep the old value (not used in display)

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update persons (without photo field)
            $sql = "UPDATE persons SET 
                        first_name = ?, middle_name = ?, last_name = ?, gender = ?, date_of_birth = ?,
                        id_number = ?, passport_number = ?, phone = ?, email = ?,
                        country = ?, province = ?, city = ?, address = ?, postal_code = ?
                    WHERE person_id = ?";
            $params = [
                $firstName, $middleName, $lastName, $gender, $dob,
                $idNumber, $passport, $phone, $email,
                $country, $province, $city, $address, $postalCode,
                $current['person_id']
            ];
            // If password provided, update it
            if (!empty($password)) {
                $sql = "UPDATE persons SET 
                            first_name = ?, middle_name = ?, last_name = ?, gender = ?, date_of_birth = ?,
                            id_number = ?, passport_number = ?, phone = ?, email = ?, password = ?,
                            country = ?, province = ?, city = ?, address = ?, postal_code = ?
                        WHERE person_id = ?";
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $params = [
                    $firstName, $middleName, $lastName, $gender, $dob,
                    $idNumber, $passport, $phone, $email, $hashedPassword,
                    $country, $province, $city, $address, $postalCode,
                    $current['person_id']
                ];
            }
            $personStmt = $pdo->prepare($sql);
            $personStmt->execute($params);

            // Update employees
            $empStmt = $pdo->prepare("
                UPDATE employees SET 
                    department = ?, position = ?, employment_type = ?, hire_date = ?, salary = ?,
                    supervisor_id = ?, employee_status = ?
                WHERE employee_id = ?
            ");
            $empStmt->execute([
                $department, $position, $employmentType, $hireDate, $salary,
                $supervisorId, $employeeStatus, $employeeId
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Employee updated successfully.";
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: index.php');
        exit;
    }
}

// --- DELETE EMPLOYEE (soft delete) ---
if ($action === 'delete' && isset($_GET['id'])) {
    $employeeId = (int) $_GET['id'];
    $stmt = $pdo->prepare("UPDATE employees SET deleted_at = NOW() WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $_SESSION['success'] = 'Employee deleted.';
    header('Location: index.php');
    exit;
}

// --- DOCUMENT UPLOAD ---
if ($action === 'upload_document' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $docName = sanitiseInput($_POST['document_name'] ?? '');
    $docType = sanitiseInput($_POST['document_type'] ?? 'Other');
    $expiry = $_POST['expiry_date'] ?? null;

    if (!$employeeId || !$docName || !isset($_FILES['document_file'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    $errors = [];
    $filePath = uploadFile($_FILES['document_file'], 'document', $errors);
    if ($filePath === null) {
        echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO employee_documents (employee_id, document_name, document_type, file_path, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$employeeId, $docName, $docType, $filePath, $expiry]);
        echo json_encode(['success' => true, 'message' => 'Document uploaded.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- DOCUMENT DELETE ---
if ($action === 'delete_document' && isset($_GET['doc_id'])) {
    $docId = (int) $_GET['doc_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM employee_documents WHERE document_id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    if ($doc) {
        deleteFile($doc['file_path']);
        $del = $pdo->prepare("DELETE FROM employee_documents WHERE document_id = ?");
        $del->execute([$docId]);
        $_SESSION['success'] = 'Document deleted.';
    } else {
        $_SESSION['error'] = 'Document not found.';
    }
    header('Location: index.php');
    exit;
}

// --- UPDATE LEAVE BALANCE ---
if ($action === 'update_leave_balances' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $leaveType = sanitiseInput($_POST['leave_type'] ?? '');
    $totalDays = (float) ($_POST['total_days'] ?? 0);
    $usedDays = (float) ($_POST['used_days'] ?? 0);
    $year = (int) ($_POST['year'] ?? date('Y'));

    if (!$employeeId || !$leaveType || $totalDays < 0 || $usedDays < 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid input.']);
        exit;
    }

    try {
        // Check if balance exists
        $stmt = $pdo->prepare("SELECT balance_id FROM leave_balances WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $stmt->execute([$employeeId, $leaveType, $year]);
        if ($stmt->fetch()) {
            $upd = $pdo->prepare("UPDATE leave_balances SET total_days = ?, used_days = ? WHERE employee_id = ? AND leave_type = ? AND year = ?");
            $upd->execute([$totalDays, $usedDays, $employeeId, $leaveType, $year]);
        } else {
            $ins = $pdo->prepare("INSERT INTO leave_balances (employee_id, leave_type, total_days, used_days, year) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$employeeId, $leaveType, $totalDays, $usedDays, $year]);
        }
        echo json_encode(['success' => true, 'message' => 'Leave balance updated.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- ADD LEAVE REQUEST ---
if ($action === 'add_leave_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $leaveType = sanitiseInput($_POST['leave_type'] ?? '');
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $reason = sanitiseInput($_POST['reason'] ?? '');

    if (!$employeeId || !$leaveType || !$startDate || !$endDate) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }
    if (strtotime($startDate) > strtotime($endDate)) {
        echo json_encode(['success' => false, 'error' => 'End date must be after start date.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$employeeId, $leaveType, $startDate, $endDate, $reason]);
        echo json_encode(['success' => true, 'message' => 'Leave request submitted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- APPROVE LEAVE ---
if ($action === 'approve_leave' && isset($_GET['request_id'])) {
    $requestId = (int) $_GET['request_id'];
    $stmt = $pdo->prepare("SELECT employee_id, leave_type, start_date, end_date FROM leave_requests WHERE request_id = ? AND status = 'Pending'");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        $_SESSION['error'] = 'Request not found or already processed.';
        header('Location: index.php');
        exit;
    }

    $days = dateDiffDays($request['start_date'], $request['end_date']);
    $year = date('Y', strtotime($request['start_date']));

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("UPDATE leave_requests SET status = 'Approved' WHERE request_id = ?");
        $upd->execute([$requestId]);

        $bal = $pdo->prepare("SELECT balance_id, used_days FROM leave_balances
                              WHERE employee_id = ? AND leave_type = ? AND year = ?");
        $bal->execute([$request['employee_id'], $request['leave_type'], $year]);
        $balance = $bal->fetch();
        if ($balance) {
            $newUsed = $balance['used_days'] + $days;
            $updBal = $pdo->prepare("UPDATE leave_balances SET used_days = ? WHERE balance_id = ?");
            $updBal->execute([$newUsed, $balance['balance_id']]);
        } else {
            $insBal = $pdo->prepare("INSERT INTO leave_balances (employee_id, leave_type, total_days, used_days, year) VALUES (?, ?, 0, ?, ?)");
            $insBal->execute([$request['employee_id'], $request['leave_type'], $days, $year]);
        }

        $pdo->commit();
        $_SESSION['success'] = 'Leave request approved. Used days updated.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Approval failed: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// --- DECLINE LEAVE ---
if ($action === 'decline_leave' && isset($_GET['request_id'])) {
    $requestId = (int) $_GET['request_id'];
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'Declined' WHERE request_id = ? AND status = 'Pending'");
    $stmt->execute([$requestId]);
    if ($stmt->rowCount()) {
        $_SESSION['success'] = 'Leave request declined.';
    } else {
        $_SESSION['error'] = 'Request not found or already processed.';
    }
    header('Location: index.php');
    exit;
}

// --- DELETE LEAVE REQUEST ---
if ($action === 'delete_leave_request' && isset($_GET['request_id'])) {
    $requestId = (int) $_GET['request_id'];
    $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE request_id = ?");
    $stmt->execute([$requestId]);
    $_SESSION['success'] = 'Leave request deleted.';
    header('Location: index.php');
    exit;
}

// ----------------------------------------------------------------------
// 4. FETCH DATA FOR DISPLAY
// ----------------------------------------------------------------------
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL")->fetchColumn();
$activeEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE employee_status = 'Active' AND deleted_at IS NULL")->fetchColumn();
$onLeave = $pdo->query("SELECT COUNT(*) FROM employees WHERE employee_status = 'On Leave' AND deleted_at IS NULL")->fetchColumn();

$sql = "
    SELECT
        e.*,
        p.first_name, p.middle_name, p.last_name,
        p.phone, p.email, p.gender, p.status AS person_status,
        p.id_number
    FROM employees e
    INNER JOIN persons p ON e.person_id = p.person_id
    WHERE e.deleted_at IS NULL
    ORDER BY e.employee_id DESC
";
$employees = $pdo->query($sql);

$supervisors = $pdo->query("
    SELECT e.employee_id, p.first_name, p.last_name
    FROM employees e
    JOIN persons p ON e.person_id = p.person_id
    WHERE e.deleted_at IS NULL
    ORDER BY p.first_name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Dashboard Wrapper (same as other pages) ── */
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }

        /* ── Stats Cards ── */
        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            background: #fff;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        /* ── Table & Badges ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 0 2px;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 900px;
        }
        .form-label.required::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }
        .document-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .document-item:last-child {
            border-bottom: none;
        }

        /* ── Responsive fine‑tune (same as dashboard) ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            /* Page header: stack */
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
            }
            /* Stats cards */
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h2 {
                font-size: 1.8rem;
            }
            /* Container */
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            /* Modals */
            .modal-header {
                padding: 0.8rem 1rem;
            }
            .modal-body {
                padding: 1rem;
            }
            .modal-footer {
                padding: 0.8rem 1rem;
            }
            .form-control, .form-select {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            .modal-dialog {
                margin: 0.5rem;
            }
            .modal-content {
                border-radius: 16px;
            }
            /* Tabs in modals */
            .nav-tabs .nav-link {
                font-size: 0.85rem;
                padding: 0.4rem 0.6rem;
            }
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-item {
                white-space: nowrap;
            }
            /* Table font */
            .table td, .table th {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .badge-status {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            .action-btn {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .stat-card .card-body {
                padding: 0.8rem 1rem;
            }
            .stat-card h2 {
                font-size: 1.5rem;
            }
            .stat-icon {
                font-size: 1.5rem;
            }
            .table td, .table th {
                font-size: 0.75rem;
                padding: 0.3rem 0.2rem;
            }
            .badge-status {
                font-size: 0.65rem;
                padding: 0.2rem 0.5rem;
            }
            .action-btn {
                padding: 0.15rem 0.4rem;
                font-size: 0.65rem;
            }
            .modal-body .row > .col-md-6,
            .modal-body .row > .col-md-4,
            .modal-body .row > .col-md-3 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-12 {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-user-tie text-primary me-2"></i>Employee Management</h2>
                <p class="text-muted">Manage all staff members, documents, and leave.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#employeeModal" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> Add Employee
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Employees</h6>
                            <h2 class="fw-bold"><?= $totalEmployees ?></h2>
                        </div>
                        <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Active</h6>
                            <h2 class="fw-bold text-success"><?= $activeEmployees ?></h2>
                        </div>
                        <div class="stat-icon text-success"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#ffc107;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">On Leave</h6>
                            <h2 class="fw-bold text-warning"><?= $onLeave ?></h2>
                        </div>
                        <div class="stat-icon text-warning"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Employees</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employeesTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Employee No</th>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 1; while ($row = $employees->fetch()): 
                            $statusClass = match($row['employee_status'] ?? 'Active') {
                                'Active' => 'success', 
                                'On Leave' => 'warning', 
                                'Suspended' => 'danger', 
                                'Resigned' => 'secondary',
                                default => 'secondary'
                            };
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><strong><?= htmlspecialchars($row['employee_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['id_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= ucfirst($row['employee_status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info action-btn" onclick="viewEmployee(<?= $row['employee_id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-warning action-btn" onclick="editEmployee(<?= $row['employee_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="?action=delete&id=<?= $row['employee_id'] ?>&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this employee?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- ========== MODAL: Add / Edit Employee ========== -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="employeeForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="employee_id" id="editEmpId" value="0">
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" id="personal-tab" data-bs-toggle="tab" href="#personal" role="tab">Personal</a></li>
                        <li class="nav-item"><a class="nav-link" id="employment-tab" data-bs-toggle="tab" href="#employment" role="tab">Employment</a></li>
                        <li class="nav-item"><a class="nav-link" id="account-tab" data-bs-toggle="tab" href="#account" role="tab">Account</a></li>
                    </ul>
                    <div class="tab-content pt-3" id="formTabContent">
                        <!-- Personal -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label required">First Name</label><input type="text" name="first_name" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label required">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                                <div class="col-md-3"><label class="form-label required">Gender</label>
                                    <select name="gender" class="form-select" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                                </div>
                                <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" name="date_of_birth" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label">ID Number</label><input type="text" name="id_number" class="form-control"></div>
                                <div class="col-md-3"><label class="form-label">Passport Number</label><input type="text" name="passport_number" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label required">Phone</label><input type="text" name="phone" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label required">Email</label><input type="email" name="email" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="South Africa"></div>
                                <div class="col-md-4"><label class="form-label">Province</label><input type="text" name="province" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">City</label><input type="text" name="city" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Postal Code</label><input type="text" name="postal_code" class="form-control"></div>
                            </div>
                        </div>
                        <!-- Employment -->
                        <div class="tab-pane fade" id="employment" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label required">Department</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Administration">Administration</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Workshop">Workshop</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="IT">IT</option>
                                        <option value="HR">HR</option>
                                    </select>
                                </div>
                                <div class="col-md-6"><label class="form-label required">Position</label><input type="text" name="position" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Employment Type</label>
                                    <select name="employment_type" class="form-select">
                                        <option value="Permanent">Permanent</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Temporary">Temporary</option>
                                        <option value="Intern">Intern</option>
                                    </select>
                                </div>
                                <div class="col-md-4"><label class="form-label required">Hire Date</label><input type="date" name="hire_date" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Salary (R)</label><input type="number" step="0.01" name="salary" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label">Supervisor</label>
                                    <select name="supervisor_id" class="form-select">
                                        <option value="">None</option>
                                        <?php foreach ($supervisors as $sup): ?>
                                            <option value="<?= $sup['employee_id'] ?>"><?= htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6"><label class="form-label">Employee Status</label>
                                    <select name="employee_status" class="form-select">
                                        <option value="Active">Active</option>
                                        <option value="On Leave">On Leave</option>
                                        <option value="Suspended">Suspended</option>
                                        <option value="Resigned">Resigned</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Account (Password) -->
                        <div class="tab-pane fade" id="account" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label required" id="passwordLabel">Password</label><input type="password" name="password" id="passwordField" class="form-control" placeholder="Min 6 characters"></div>
                                <div class="col-md-6"><label class="form-label required" id="confirmLabel">Confirm Password</label><input type="password" name="confirm_password" id="confirmField" class="form-control" placeholder="Confirm password"></div>
                                <div class="col-12"><small class="text-muted" id="passwordHelp">Leave blank to keep current password (edit mode).</small></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== MODAL: View Employee ========== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="viewTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">Details</a></li>
                    <li class="nav-item"><a class="nav-link" id="documents-tab" data-bs-toggle="tab" href="#documents" role="tab">Documents</a></li>
                    <li class="nav-item"><a class="nav-link" id="leave-tab" data-bs-toggle="tab" href="#leave" role="tab">Leave Balances</a></li>
                    <li class="nav-item"><a class="nav-link" id="requests-tab" data-bs-toggle="tab" href="#requests" role="tab">Leave Requests</a></li>
                </ul>
                <div class="tab-content pt-3" id="viewTabContent">
                    <div class="tab-pane fade show active" id="details" role="tabpanel"><div id="viewDetails"></div></div>
                    <div class="tab-pane fade" id="documents" role="tabpanel">
                        <div id="viewDocuments">
                            <!-- Document upload form -->
                            <form id="documentUploadForm" enctype="multipart/form-data" class="mb-3">
                                <input type="hidden" name="action" value="upload_document">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="employee_id" id="docEmpId" value="">
                                <div class="row g-2">
                                    <div class="col-md-4"><input type="text" name="document_name" class="form-control" placeholder="Document Name" required></div>
                                    <div class="col-md-3">
                                        <select name="document_type" class="form-select">
                                            <option value="ID Copy">ID Copy</option>
                                            <option value="Passport">Passport</option>
                                            <option value="CV">CV</option>
                                            <option value="Employment Contract">Employment Contract</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><input type="date" name="expiry_date" class="form-control" placeholder="Expiry Date"></div>
                                    <div class="col-md-2"><input type="file" name="document_file" class="form-control" required></div>
                                    <div class="col-md-12"><button type="submit" class="btn btn-primary btn-sm">Upload Document</button></div>
                                </div>
                            </form>
                            <div id="documentList"></div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="leave" role="tabpanel">
                        <div id="viewLeaveBalances"></div>
                        <hr>
                        <h6>Update Balance</h6>
                        <form id="leaveBalanceForm" class="row g-2">
                            <input type="hidden" name="action" value="update_leave_balances">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="employee_id" id="balEmpId" value="">
                            <div class="col-md-3">
                                <select name="leave_type" class="form-select" required>
                                    <option value="Annual">Annual</option>
                                    <option value="Sick">Sick</option>
                                    <option value="Family Responsibility">Family Responsibility</option>
                                    <option value="Maternity">Maternity</option>
                                    <option value="Paternity">Paternity</option>
                                </select>
                            </div>
                            <div class="col-md-2"><input type="number" step="0.5" name="total_days" class="form-control" placeholder="Total" required></div>
                            <div class="col-md-2"><input type="number" step="0.5" name="used_days" class="form-control" placeholder="Used" value="0"></div>
                            <div class="col-md-2"><input type="number" name="year" class="form-control" placeholder="Year" value="<?= date('Y') ?>" required></div>
                            <div class="col-md-3"><button type="submit" class="btn btn-success btn-sm">Update Balance</button></div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="requests" role="tabpanel">
                        <div id="viewLeaveRequests">
                            <form id="leaveRequestForm" class="row g-2 mb-3">
                                <input type="hidden" name="action" value="add_leave_request">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="employee_id" id="reqEmpId" value="">
                                <div class="col-md-2">
                                    <select name="leave_type" class="form-select" required>
                                        <option value="">Type</option>
                                        <option value="Annual">Annual</option>
                                        <option value="Sick">Sick</option>
                                        <option value="Family Responsibility">Family Responsibility</option>
                                        <option value="Maternity">Maternity</option>
                                        <option value="Paternity">Paternity</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><input type="date" name="start_date" class="form-control" required></div>
                                <div class="col-md-2"><input type="date" name="end_date" class="form-control" required></div>
                                <div class="col-md-3"><input type="text" name="reason" class="form-control" placeholder="Reason (optional)"></div>
                                <div class="col-md-3"><button type="submit" class="btn btn-primary btn-sm">Request Leave</button></div>
                            </form>
                            <div id="requestList"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#employeesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search employees..." }
    });

    // Document upload AJAX
    $('#documentUploadForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    var empId = $('#docEmpId').val();
                    loadDocuments(empId);
                    $('#documentUploadForm')[0].reset();
                } else {
                    alert('Error: ' + res.error);
                }
            },
            error: function() { alert('Upload failed.'); }
        });
    });

    // Leave balance update AJAX
    $('#leaveBalanceForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    var empId = $('#balEmpId').val();
                    loadLeaveBalances(empId);
                    $('#leaveBalanceForm')[0].reset();
                } else {
                    alert('Error: ' + res.error);
                }
            },
            error: function() { alert('Update failed.'); }
        });
    });

    // Leave request submission AJAX
    $('#leaveRequestForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    var empId = $('#reqEmpId').val();
                    loadLeaveRequests(empId);
                    $('#leaveRequestForm')[0].reset();
                } else {
                    alert('Error: ' + res.error);
                }
            },
            error: function() { alert('Request failed.'); }
        });
    });
});

// -------------------- OPEN ADD MODAL --------------------
function openAddModal() {
    $('#employeeModalLabel').text('Add Employee');
    $('#formAction').val('add');
    $('#editEmpId').val(0);
    $('#employeeForm')[0].reset();
    $('#passwordField').prop('required', true);
    $('#confirmField').prop('required', true);
    $('#passwordHelp').text('Enter a password (min 6 characters).');
    $('#passwordLabel').text('Password *');
    $('#confirmLabel').text('Confirm Password *');
    $('#employeeModal').modal('show');
}

// -------------------- EDIT EMPLOYEE --------------------
function editEmployee(id) {
    $('#employeeModalLabel').text('Edit Employee');
    $('#formAction').val('edit');
    $('#editEmpId').val(id);
    $('#passwordField').prop('required', false);
    $('#confirmField').prop('required', false);
    $('#passwordHelp').text('Leave blank to keep current password.');
    $('#passwordLabel').text('Password (optional)');
    $('#confirmLabel').text('Confirm Password (optional)');

    $.ajax({
        url: 'get_employee.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            // Populate form fields
            $('input[name="first_name"]').val(data.first_name);
            $('input[name="middle_name"]').val(data.middle_name || '');
            $('input[name="last_name"]').val(data.last_name);
            $('select[name="gender"]').val(data.gender);
            $('input[name="date_of_birth"]').val(data.date_of_birth || '');
            $('input[name="id_number"]').val(data.id_number || '');
            $('input[name="passport_number"]').val(data.passport_number || '');
            $('input[name="phone"]').val(data.phone);
            $('input[name="email"]').val(data.email);
            $('input[name="country"]').val(data.country || 'South Africa');
            $('input[name="province"]').val(data.province || '');
            $('input[name="city"]').val(data.city || '');
            $('input[name="address"]').val(data.address || '');
            $('input[name="postal_code"]').val(data.postal_code || '');
            $('select[name="department"]').val(data.department);
            $('input[name="position"]').val(data.position);
            $('select[name="employment_type"]').val(data.employment_type || 'Permanent');
            $('input[name="hire_date"]').val(data.hire_date);
            $('input[name="salary"]').val(data.salary || '');
            $('select[name="supervisor_id"]').val(data.supervisor_id || '');
            $('select[name="employee_status"]').val(data.employee_status || 'Active');
            $('#employeeModal').modal('show');
        },
        error: function() { alert('Error loading employee data.'); }
    });
}

// -------------------- VIEW EMPLOYEE --------------------
function viewEmployee(id) {
    $('#docEmpId').val(id);
    $('#balEmpId').val(id);
    $('#reqEmpId').val(id);

    $.ajax({
        url: 'get_employee.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `<div class="row">
                <div class="col-md-12">
                    <h4>${data.first_name} ${data.last_name}</h4>
                    <p><strong>Employee No:</strong> ${data.employee_number}</p>
                    <p><strong>ID Number:</strong> ${data.id_number || 'N/A'}</p>
                    <p><strong>Department:</strong> ${data.department} | <strong>Position:</strong> ${data.position}</p>
                    <p><strong>Hire Date:</strong> ${data.hire_date} | <strong>Status:</strong> <span class="badge bg-${data.employee_status=='Active'?'success':data.employee_status=='On Leave'?'warning':'secondary'}">${data.employee_status}</span></p>
                    <p><strong>Phone:</strong> ${data.phone} | <strong>Email:</strong> ${data.email}</p>
                    <p><strong>Gender:</strong> ${data.gender} | <strong>DOB:</strong> ${data.date_of_birth || 'N/A'}</p>
                    <p><strong>Passport:</strong> ${data.passport_number || 'N/A'}</p>
                    <p><strong>Address:</strong> ${data.address || 'N/A'}, ${data.city || ''} ${data.province || ''} ${data.postal_code || ''}</p>
                    <p><strong>Employment Type:</strong> ${data.employment_type} | <strong>Salary:</strong> R ${data.salary ? parseFloat(data.salary).toFixed(2) : '0.00'}</p>
                    <p><strong>Supervisor:</strong> ${data.supervisor_name || 'None'}</p>
                </div>
            </div>`;
            $('#viewDetails').html(html);
            $('#viewModal').modal('show');
            loadDocuments(id);
            loadLeaveBalances(id);
            loadLeaveRequests(id);
        },
        error: function() { alert('Error loading employee details.'); }
    });
}

// -------------------- LOAD DOCUMENTS (with delete) --------------------
function loadDocuments(empId) {
    $.ajax({
        url: 'get_employee_documents.php?employee_id=' + empId,
        dataType: 'json',
        success: function(docs) {
            let html = '';
            if (docs.length === 0) {
                html = '<p class="text-muted">No documents uploaded.</p>';
            } else {
                html = `<table class="table table-sm table-bordered">
                    <thead><tr><th>Name</th><th>Type</th><th>Expiry</th><th>Action</th></tr></thead><tbody>`;
                docs.forEach(function(doc) {
                    let deleteLink = `<a href="?action=delete_document&doc_id=${doc.document_id}&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this document?')"><i class="fas fa-trash"></i></a>`;
                    html += `<tr>
                        <td><a href="../../${doc.file_path}" target="_blank">${doc.document_name}</a></td>
                        <td>${doc.document_type}</td>
                        <td>${doc.expiry_date || 'N/A'}</td>
                        <td>${deleteLink}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
            }
            $('#documentList').html(html);
        },
        error: function() { $('#documentList').html('<p class="text-danger">Failed to load documents.</p>'); }
    });
}

// -------------------- LOAD LEAVE BALANCES --------------------
function loadLeaveBalances(empId) {
    $.ajax({
        url: 'get_leave_balances.php?employee_id=' + empId,
        dataType: 'json',
        success: function(balances) {
            let html = '';
            if (balances.length === 0) {
                html = '<p class="text-muted">No leave balances set.</p>';
            } else {
                html = `<table class="table table-sm table-bordered">
                    <thead><tr><th>Type</th><th>Year</th><th>Total</th><th>Used</th><th>Remaining</th></tr></thead><tbody>`;
                balances.forEach(function(b) {
                    let remaining = b.total_days - b.used_days;
                    html += `<tr><td>${b.leave_type}</td><td>${b.year}</td><td>${b.total_days}</td><td>${b.used_days}</td><td>${remaining}</td></tr>`;
                });
                html += '</tbody></table>';
            }
            $('#viewLeaveBalances').html(html);
        },
        error: function() { $('#viewLeaveBalances').html('<p class="text-danger">Failed to load balances.</p>'); }
    });
}

// -------------------- LOAD LEAVE REQUESTS (with actions) --------------------
function loadLeaveRequests(empId) {
    $.ajax({
        url: 'get_leave_requests.php?employee_id=' + empId,
        dataType: 'json',
        success: function(requests) {
            let html = '';
            if (requests.length === 0) {
                html = '<p class="text-muted">No leave requests.</p>';
            } else {
                html = `<table class="table table-sm table-bordered">
                    <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead><tbody>`;
                requests.forEach(function(r) {
                    let statusClass = r.status == 'Approved' ? 'success' : (r.status == 'Declined' ? 'danger' : 'warning');
                    let actions = '';
                    if (r.status == 'Pending') {
                        actions = `
                            <a href="?action=approve_leave&request_id=${r.request_id}&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this request?')">Approve</a>
                            <a href="?action=decline_leave&request_id=${r.request_id}&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-danger" onclick="return confirm('Decline this request?')">Decline</a>
                            <a href="?action=delete_leave_request&request_id=${r.request_id}&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Delete this request?')">Delete</a>
                        `;
                    } else {
                        actions = `
                            <a href="?action=delete_leave_request&request_id=${r.request_id}&csrf_token=<?= $csrf_token ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Delete this request?')">Delete</a>
                        `;
                    }
                    html += `<tr>
                        <td>${r.leave_type}</td>
                        <td>${r.start_date}</td>
                        <td>${r.end_date}</td>
                        <td><span class="badge bg-${statusClass}">${r.status}</span></td>
                        <td>${r.reason || ''}</td>
                        <td>${actions}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
            }
            $('#requestList').html(html);
        },
        error: function() { $('#requestList').html('<p class="text-danger">Failed to load leave requests.</p>'); }
    });
}
</script>
</body>
</html>