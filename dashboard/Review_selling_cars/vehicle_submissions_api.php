<?php
/**
 * API for Vehicle Submissions Management
 * Handles listing, CRUD, workflow updates, and image uploads.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only admins/employees can access API
$allowedRoles = ['Admin', 'Manager', 'Employee'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// CSRF protection for POST requests (except GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ------------------------------------------------------------
// 1. LIST (DataTable server‑side)
// ------------------------------------------------------------
if ($action === 'list') {
    $columns = ['submission_id', 'customer_id', 'make', 'model', 'status', 'inspection_scheduled_date', 'offer_price', 'created_at'];
    $search = $_GET['search']['value'] ?? '';
    $orderColumn = $columns[$_GET['order'][0]['column'] ?? 0] ?? 'submission_id';
    $orderDir = $_GET['order'][0]['dir'] ?? 'DESC';
    $start = (int) ($_GET['start'] ?? 0);
    $length = (int) ($_GET['length'] ?? 10);

    $where = [];
    $params = [];
    if (!empty($search)) {
        $where[] = "(s.make LIKE ? OR s.model LIKE ? OR s.vin LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like, $like];
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            s.*,
            p.first_name AS customer_first_name,
            p.last_name AS customer_last_name,
            p.email AS customer_email,
            p.phone AS customer_phone,
            c.customer_number,
            c.driver_license
        FROM customer_vehicle_submissions s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN persons p ON c.person_id = p.person_id
        $whereSql
        ORDER BY $orderColumn $orderDir
        LIMIT $start, $length
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

    echo json_encode([
        'draw' => (int) ($_GET['draw'] ?? 1),
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => $rows
    ]);
    exit;
}

// ------------------------------------------------------------
// 2. GET SINGLE SUBMISSION
// ------------------------------------------------------------
if ($action === 'get') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.*,
               p.first_name AS customer_first_name,
               p.last_name AS customer_last_name,
               p.email AS customer_email,
               p.phone AS customer_phone,
               c.customer_number,
               c.driver_license,
               e.first_name AS inspector_first_name,
               e.last_name AS inspector_last_name
        FROM customer_vehicle_submissions s
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN persons p ON c.person_id = p.person_id
        LEFT JOIN employees emp ON s.inspector_id = emp.employee_id
        LEFT JOIN persons e ON emp.person_id = e.person_id
        WHERE s.submission_id = ?
    ");
    $stmt->execute([$id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$submission) {
        echo json_encode(['error' => 'Submission not found']);
        exit;
    }

    // Fetch images
    $imgStmt = $pdo->prepare("SELECT * FROM submission_images WHERE submission_id = ? ORDER BY display_order, image_id");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    $submission['images'] = $images;

    echo json_encode($submission);
    exit;
}

// ------------------------------------------------------------
// 3. UPDATE SUBMISSION (workflow)
// ------------------------------------------------------------
if ($action === 'update') {
    $id = (int) ($_POST['submission_id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'Missing submission ID']);
        exit;
    }

    $fields = [
        'status', 'inspection_type', 'inspector_id',
        'inspection_scheduled_date', 'inspection_notes',
        'offer_price', 'purchase_price', 'notes',
        'decision_date', 'vehicle_id'
    ];

    $updates = [];
    $params = [];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $_POST[$field] === '' ? null : $_POST[$field];
        }
    }

    if (empty($updates)) {
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }

    // If status changes to 'accepted' or 'rejected', set decision_date
    if (isset($_POST['status']) && in_array($_POST['status'], ['accepted', 'rejected', 'purchased'])) {
        $updates[] = "decision_date = NOW()";
    }

    $sql = "UPDATE customer_vehicle_submissions SET " . implode(', ', $updates) . " WHERE submission_id = :id";
    $params[':id'] = $id;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Submission updated']);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ------------------------------------------------------------
// 4. ADD IMAGE
// ------------------------------------------------------------
if ($action === 'add_image') {
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    if (!$submissionId) {
        echo json_encode(['error' => 'Missing submission ID']);
        exit;
    }

    $file = $_FILES['image_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'File upload error']);
        exit;
    }

    // Validate image
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP']);
        exit;
    }

    $uploadDir = '../../uploads/submissions/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'sub_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['error' => 'Failed to move uploaded file']);
        exit;
    }

    $path = 'uploads/submissions/' . $filename;
    $title = $_POST['image_title'] ?? '';
    $type = $_POST['image_type'] ?? 'Other';
    $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] == 'Yes' ? 'Yes' : 'No';
    $order = (int) ($_POST['display_order'] ?? 1);

    // If this is primary, unset any existing primary
    if ($isPrimary === 'Yes') {
        $pdo->prepare("UPDATE submission_images SET is_primary = 'No' WHERE submission_id = ?")->execute([$submissionId]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO submission_images (submission_id, image_path, image_title, image_type, is_primary, display_order)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$submissionId, $path, $title, $type, $isPrimary, $order]);

    echo json_encode(['success' => true, 'message' => 'Image uploaded']);
    exit;
}

// ------------------------------------------------------------
// 5. DELETE IMAGE
// ------------------------------------------------------------
if ($action === 'delete_image') {
    $imageId = (int) ($_POST['image_id'] ?? 0);
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    if (!$imageId || !$submissionId) {
        echo json_encode(['error' => 'Missing image or submission ID']);
        exit;
    }

    // Get path to delete file
    $stmt = $pdo->prepare("SELECT image_path FROM submission_images WHERE image_id = ? AND submission_id = ?");
    $stmt->execute([$imageId, $submissionId]);
    $img = $stmt->fetch();
    if (!$img) {
        echo json_encode(['error' => 'Image not found']);
        exit;
    }

    // Delete file
    if (file_exists('../../' . $img['image_path'])) {
        unlink('../../' . $img['image_path']);
    }

    // Delete DB record
    $del = $pdo->prepare("DELETE FROM submission_images WHERE image_id = ?");
    $del->execute([$imageId]);

    // If deleted image was primary, set another as primary (oldest)
    if ($img['is_primary'] === 'Yes') {
        $newPrimary = $pdo->prepare("SELECT image_id FROM submission_images WHERE submission_id = ? ORDER BY display_order, image_id LIMIT 1");
        $newPrimary->execute([$submissionId]);
        $new = $newPrimary->fetch();
        if ($new) {
            $pdo->prepare("UPDATE submission_images SET is_primary = 'Yes' WHERE image_id = ?")->execute([$new['image_id']]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Image deleted']);
    exit;
}

// ------------------------------------------------------------
// Default fallback
// ------------------------------------------------------------
echo json_encode(['error' => 'Invalid action']);
exit;