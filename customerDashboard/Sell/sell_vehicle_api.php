<?php
/**
 * Customer Sell Vehicle API
 * Handles submission, image upload, and fetching submissions.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$personId = (int) $_SESSION['person_id'];
$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$personId]);
$customer = $stmt->fetch();
if (!$customer) {
    echo json_encode(['error' => 'Customer record not found']);
    exit;
}
$customerId = $customer['customer_id'];

// CSRF check for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ------------------------------------------------------------
// 1. GET SINGLE SUBMISSION (with images)
// ------------------------------------------------------------
if ($action === 'get') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.*,
               e.first_name AS inspector_first_name,
               e.last_name AS inspector_last_name
        FROM customer_vehicle_submissions s
        LEFT JOIN employees emp ON s.inspector_id = emp.employee_id
        LEFT JOIN persons e ON emp.person_id = e.person_id
        WHERE s.submission_id = ? AND s.customer_id = ?
    ");
    $stmt->execute([$id, $customerId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$submission) {
        echo json_encode(['error' => 'Submission not found or not yours']);
        exit;
    }

    // Images
    $imgStmt = $pdo->prepare("SELECT * FROM submission_images WHERE submission_id = ? ORDER BY display_order, image_id");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    $submission['images'] = $images;

    echo json_encode($submission);
    exit;
}

// ------------------------------------------------------------
// 2. SUBMIT NEW VEHICLE
// ------------------------------------------------------------
if ($action === 'submit') {
    // Define required fields
    $required = ['make', 'model', 'manufacture_year', 'inspection_type'];
    $errors = [];

    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!empty($errors)) {
        echo json_encode(['error' => implode(' ', $errors)]);
        exit;
    }

    // Build data array
    $data = [
        'customer_id' => $customerId,
        'make' => trim($_POST['make']),
        'model' => trim($_POST['model']),
        'manufacture_year' => (int) $_POST['manufacture_year'],
        'mileage' => isset($_POST['mileage']) ? (int) $_POST['mileage'] : null,
        'vin' => trim($_POST['vin'] ?? '') ?: null,
        'colour' => trim($_POST['colour'] ?? '') ?: null,
        'condition_type' => trim($_POST['condition_type'] ?? 'Used'),
        'service_history' => trim($_POST['service_history'] ?? 'None'),
        'accident_history' => trim($_POST['accident_history'] ?? 'No'),
        'damage_status' => trim($_POST['damage_status'] ?? 'None'),
        'damage_description' => trim($_POST['damage_description'] ?? '') ?: null,
        'inspection_type' => trim($_POST['inspection_type']),
        'inspection_scheduled_date' => !empty($_POST['inspection_scheduled_date']) ? $_POST['inspection_scheduled_date'] : null,
        'notes' => trim($_POST['notes'] ?? '') ?: null,
        'status' => 'pending'
    ];

    // Insert submission
    $sql = "
        INSERT INTO customer_vehicle_submissions (
            customer_id, make, model, manufacture_year, mileage, vin, colour,
            condition_type, service_history, accident_history, damage_status,
            damage_description, inspection_type, inspection_scheduled_date,
            notes, status, created_at
        ) VALUES (
            :customer_id, :make, :model, :manufacture_year, :mileage, :vin, :colour,
            :condition_type, :service_history, :accident_history, :damage_status,
            :damage_description, :inspection_type, :inspection_scheduled_date,
            :notes, :status, NOW()
        )
    ";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($data);
        $submissionId = $pdo->lastInsertId();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }

    // Handle image uploads
    $uploaded = 0;
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $uploadDir = '../../uploads/submissions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach ($_FILES['images']['name'] as $index => $name) {
            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) continue;
            $tmpName = $_FILES['images']['tmp_name'][$index];
            $mime = finfo_file($finfo, $tmpName);
            if (!in_array($mime, $allowed)) continue;

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $filename = 'sub_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($tmpName, $dest)) {
                $path = 'uploads/submissions/' . $filename;
                $isPrimary = ($uploaded === 0) ? 'Yes' : 'No';
                $imgStmt = $pdo->prepare("
                    INSERT INTO submission_images (submission_id, image_path, is_primary, display_order)
                    VALUES (?, ?, ?, ?)
                ");
                $imgStmt->execute([$submissionId, $path, $isPrimary, $uploaded + 1]);
                $uploaded++;
            }
        }
        finfo_close($finfo);
    }

    echo json_encode(['success' => true, 'message' => 'Vehicle submitted successfully!', 'submission_id' => $submissionId]);
    exit;
}

// Default
echo json_encode(['error' => 'Invalid action']);
exit;