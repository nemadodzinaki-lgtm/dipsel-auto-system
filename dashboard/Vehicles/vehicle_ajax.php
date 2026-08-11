<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// ---------------------- Helper Functions ----------------------
function uploadVehicleImage(array $file, array &$errors): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes, true)) {
        $errors[] = 'Invalid image format. Only JPG, PNG, GIF, WEBP allowed.';
        return null;
    }
    $uploadDir = '../../uploads/vehicles/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        $errors[] = 'Could not create upload directory.';
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'vehicle_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'Failed to move uploaded file.';
        return null;
    }
    return 'uploads/vehicles/' . $filename;
}

// ---------------------- Main Request Handling ----------------------
$action = $_REQUEST['action'] ?? '';
$vehicle_id = (int) ($_REQUEST['vehicle_id'] ?? 0);
if (!$vehicle_id && !in_array($action, ['get_vehicles'])) {
    echo json_encode(['error' => 'Vehicle ID required']);
    exit;
}

$response = [];

try {
    // ---------- IMAGES ----------
    if ($action === 'get_images') {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY display_order, is_primary DESC");
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_image') {
        $image_id = (int) ($_REQUEST['image_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE image_id = ? AND vehicle_id = ?");
        $stmt->execute([$image_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_image') {
        $uploadDir = '../../uploads/vehicles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = 'vehicle_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
            $path = 'uploads/vehicles/' . $filename;
            $is_primary = isset($_POST['is_primary']) ? 'yes' : 'no';
            $stmt = $pdo->prepare("INSERT INTO vehicle_images (vehicle_id, image_path, image_title, image_type, is_primary, display_order) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$vehicle_id, $path, $_POST['image_title'] ?? '', $_POST['image_type'] ?? 'Other', $is_primary, $_POST['display_order'] ?? 1]);
            $response = ['success' => true];
        } else {
            $response = ['error' => 'Upload failed'];
        }
    }
    elseif ($action === 'update_image') {
        $image_id = (int) ($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE image_id = ? AND vehicle_id = ?");
        $stmt->execute([$image_id, $vehicle_id]);
        $old = $stmt->fetch();
        if (!$old) { $response = ['error' => 'Image not found']; echo json_encode($response); exit; }

        $file_path = $old['image_path'];
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $errors = [];
            $new_path = uploadVehicleImage($_FILES['image_file'], $errors);
            if ($new_path) {
                @unlink('../../' . $old['image_path']);
                $file_path = $new_path;
            } else {
                $response = ['error' => 'Image upload failed: ' . implode(', ', $errors)];
                echo json_encode($response); exit;
            }
        }

        $is_primary = isset($_POST['is_primary']) ? 'yes' : 'no';
        $stmt = $pdo->prepare("UPDATE vehicle_images SET 
            image_path = ?, image_title = ?, image_type = ?, is_primary = ?, display_order = ?
            WHERE image_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $file_path,
            $_POST['image_title'] ?? '',
            $_POST['image_type'] ?? 'Other',
            $is_primary,
            (int)($_POST['display_order'] ?? 1),
            $image_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_image') {
        $image_id = (int) ($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE image_id = ? AND vehicle_id = ?");
        $stmt->execute([$image_id, $vehicle_id]);
        $row = $stmt->fetch();
        if ($row) {
            @unlink('../../' . $row['image_path']);
            $del = $pdo->prepare("DELETE FROM vehicle_images WHERE image_id = ?");
            $del->execute([$image_id]);
            $response = ['success' => true];
        } else {
            $response = ['error' => 'Image not found'];
        }
    }

    // ---------- DOCUMENTS ----------
    elseif ($action === 'get_documents') {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_documents WHERE vehicle_id = ? ORDER BY document_id DESC");
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_document') {
        $doc_id = (int) ($_REQUEST['document_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_documents WHERE document_id = ? AND vehicle_id = ?");
        $stmt->execute([$doc_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_document') {
        $uploadDir = '../../uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $filename = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $dest)) {
            $path = 'uploads/documents/' . $filename;
            $stmt = $pdo->prepare("INSERT INTO vehicle_documents (vehicle_id, document_name, document_type, file_path, expiry_date, status, uploaded_by) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $vehicle_id,
                $_POST['document_name'],
                $_POST['document_type'] ?? 'Other',
                $path,
                $_POST['expiry_date'] ?? null,
                $_POST['status'] ?? 'Valid',
                (int)($_SESSION['person_id'] ?? 0)
            ]);
            $response = ['success' => true];
        } else {
            $response = ['error' => 'Upload failed'];
        }
    }
    elseif ($action === 'update_document') {
        $doc_id = (int) ($_POST['document_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT file_path FROM vehicle_documents WHERE document_id = ? AND vehicle_id = ?");
        $stmt->execute([$doc_id, $vehicle_id]);
        $old = $stmt->fetch();
        if (!$old) { $response = ['error' => 'Document not found']; echo json_encode($response); exit; }

        $file_path = $old['file_path'];
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/documents/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
            $filename = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $dest)) {
                @unlink('../../' . $old['file_path']);
                $file_path = 'uploads/documents/' . $filename;
            } else {
                $response = ['error' => 'File upload failed'];
                echo json_encode($response); exit;
            }
        }

        $stmt = $pdo->prepare("UPDATE vehicle_documents SET 
            document_name = ?, document_type = ?, file_path = ?, expiry_date = ?, status = ?
            WHERE document_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['document_name'],
            $_POST['document_type'] ?? 'Other',
            $file_path,
            $_POST['expiry_date'] ?? null,
            $_POST['status'] ?? 'Valid',
            $doc_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_document') {
        $id = (int) ($_POST['document_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_documents WHERE document_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- INQUIRIES ----------
    elseif ($action === 'get_inquiries') {
        $sql = "SELECT e.*, CONCAT(p.first_name,' ',p.last_name) AS customer_name, c.customer_number
                FROM vehicle_enquiries e
                JOIN customers c ON e.customer_id = c.customer_id
                JOIN persons p ON c.person_id = p.person_id
                WHERE e.vehicle_id = ?
                ORDER BY e.enquiry_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_inquiry') {
        $enquiry_id = (int) ($_REQUEST['enquiry_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_enquiries WHERE enquiry_id = ? AND vehicle_id = ?");
        $stmt->execute([$enquiry_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_inquiry') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_enquiries (vehicle_id, customer_id, assigned_employee_id, subject, message, enquiry_status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['customer_id'],
            $_POST['assigned_employee_id'] ?? null,
            $_POST['subject'],
            $_POST['message'],
            $_POST['enquiry_status'] ?? 'New'
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_inquiry') {
        $enquiry_id = (int) ($_POST['enquiry_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_enquiries WHERE enquiry_id = ? AND vehicle_id = ?");
        $stmt->execute([$enquiry_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Inquiry not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_enquiries SET 
            customer_id = ?, subject = ?, message = ?, enquiry_status = ?, assigned_employee_id = ?,
            response = ?, updated_at = NOW()
            WHERE enquiry_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['customer_id'],
            $_POST['subject'],
            $_POST['message'],
            $_POST['enquiry_status'] ?? 'New',
            $_POST['assigned_employee_id'] ?? null,
            $_POST['response'] ?? null,
            $enquiry_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_inquiry') {
        $id = (int) ($_POST['enquiry_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_enquiries WHERE enquiry_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- INSPECTIONS ----------
    elseif ($action === 'get_inspections') {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_inspections WHERE vehicle_id = ? ORDER BY inspection_id DESC");
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_inspection') {
        $inspection_id = (int) ($_REQUEST['inspection_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_inspections WHERE inspection_id = ? AND vehicle_id = ?");
        $stmt->execute([$inspection_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_inspection') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_inspections (vehicle_id, inspected_by_employee_id, inspection_date, inspection_type, odometer_reading, overall_condition, brakes, tyres, suspension, engine, transmission, electrical, notes, next_inspection_due) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['inspected_by_employee_id'] ?? null,
            $_POST['inspection_date'],
            $_POST['inspection_type'] ?? 'General',
            $_POST['odometer_reading'] ?? null,
            $_POST['overall_condition'] ?? 'Good',
            $_POST['brakes'] ?? 'Pass',
            $_POST['tyres'] ?? 'Pass',
            $_POST['suspension'] ?? 'Pass',
            $_POST['engine'] ?? 'Pass',
            $_POST['transmission'] ?? 'Pass',
            $_POST['electrical'] ?? 'Pass',
            $_POST['notes'] ?? null,
            $_POST['next_inspection_due'] ?? null
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_inspection') {
        $inspection_id = (int) ($_POST['inspection_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_inspections WHERE inspection_id = ? AND vehicle_id = ?");
        $stmt->execute([$inspection_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Inspection not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_inspections SET
            inspected_by_employee_id = ?, inspection_date = ?, inspection_type = ?,
            odometer_reading = ?, overall_condition = ?, brakes = ?, tyres = ?,
            suspension = ?, engine = ?, transmission = ?, electrical = ?,
            notes = ?, next_inspection_due = ?
            WHERE inspection_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['inspected_by_employee_id'] ?? null,
            $_POST['inspection_date'],
            $_POST['inspection_type'] ?? 'General',
            $_POST['odometer_reading'] ?? null,
            $_POST['overall_condition'] ?? 'Good',
            $_POST['brakes'] ?? 'Pass',
            $_POST['tyres'] ?? 'Pass',
            $_POST['suspension'] ?? 'Pass',
            $_POST['engine'] ?? 'Pass',
            $_POST['transmission'] ?? 'Pass',
            $_POST['electrical'] ?? 'Pass',
            $_POST['notes'] ?? null,
            $_POST['next_inspection_due'] ?? null,
            $inspection_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_inspection') {
        $id = (int) ($_POST['inspection_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_inspections WHERE inspection_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- MAINTENANCE ----------
    elseif ($action === 'get_maintenance') {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_maintenance_history WHERE vehicle_id = ? ORDER BY maintenance_id DESC");
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_maintenance_record') {
        $maintenance_id = (int) ($_REQUEST['maintenance_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_maintenance_history WHERE maintenance_id = ? AND vehicle_id = ?");
        $stmt->execute([$maintenance_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_maintenance') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_maintenance_history (vehicle_id, job_card_id, service_booking_id, serviced_by_employee_id, maintenance_date, odometer_reading, maintenance_type, description, total_cost, next_service_due) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['job_card_id'] ?? null,
            $_POST['service_booking_id'] ?? null,
            $_POST['serviced_by_employee_id'] ?? null,
            $_POST['maintenance_date'],
            $_POST['odometer_reading'] ?? null,
            $_POST['maintenance_type'] ?? 'Other',
            $_POST['description'] ?? null,
            $_POST['total_cost'] ?? 0,
            $_POST['next_service_due'] ?? null
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_maintenance') {
        $maintenance_id = (int) ($_POST['maintenance_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_maintenance_history WHERE maintenance_id = ? AND vehicle_id = ?");
        $stmt->execute([$maintenance_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Maintenance record not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_maintenance_history SET
            job_card_id = ?, service_booking_id = ?, serviced_by_employee_id = ?,
            maintenance_date = ?, odometer_reading = ?, maintenance_type = ?,
            description = ?, total_cost = ?, next_service_due = ?
            WHERE maintenance_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['job_card_id'] ?? null,
            $_POST['service_booking_id'] ?? null,
            $_POST['serviced_by_employee_id'] ?? null,
            $_POST['maintenance_date'],
            $_POST['odometer_reading'] ?? null,
            $_POST['maintenance_type'] ?? 'Other',
            $_POST['description'] ?? null,
            $_POST['total_cost'] ?? 0,
            $_POST['next_service_due'] ?? null,
            $maintenance_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_maintenance') {
        $id = (int) ($_POST['maintenance_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_maintenance_history WHERE maintenance_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- OWNERS ----------
    elseif ($action === 'get_owners') {
        $sql = "SELECT o.*, CONCAT(p.first_name,' ',p.last_name) AS person_name
                FROM vehicle_owners o
                JOIN persons p ON o.person_id = p.person_id
                WHERE o.vehicle_id = ?
                ORDER BY o.owner_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_owner') {
        $owner_id = (int) ($_REQUEST['owner_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_owners WHERE owner_id = ? AND vehicle_id = ?");
        $stmt->execute([$owner_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_owner') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_owners (vehicle_id, person_id, ownership_type, purchase_date, selling_date, purchase_price, selling_price, ownership_status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['person_id'],
            $_POST['ownership_type'] ?? 'Current',
            $_POST['purchase_date'] ?? null,
            $_POST['selling_date'] ?? null,
            $_POST['purchase_price'] ?? null,
            $_POST['selling_price'] ?? null,
            $_POST['ownership_status'] ?? 'Active'
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_owner') {
        $owner_id = (int) ($_POST['owner_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_owners WHERE owner_id = ? AND vehicle_id = ?");
        $stmt->execute([$owner_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Owner record not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_owners SET
            person_id = ?, ownership_type = ?, purchase_date = ?, selling_date = ?,
            purchase_price = ?, selling_price = ?, ownership_status = ?
            WHERE owner_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['person_id'],
            $_POST['ownership_type'] ?? 'Current',
            $_POST['purchase_date'] ?? null,
            $_POST['selling_date'] ?? null,
            $_POST['purchase_price'] ?? null,
            $_POST['selling_price'] ?? null,
            $_POST['ownership_status'] ?? 'Active',
            $owner_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_owner') {
        $id = (int) ($_POST['owner_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_owners WHERE owner_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- REGISTRATIONS ----------
    elseif ($action === 'get_registrations') {
        $stmt = $pdo->prepare("SELECT * FROM vehicle_registrations WHERE vehicle_id = ? ORDER BY registration_id DESC");
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_registration') {
        $registration_id = (int) ($_REQUEST['registration_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_registrations WHERE registration_id = ? AND vehicle_id = ?");
        $stmt->execute([$registration_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_registration') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_registrations (vehicle_id, registration_number, licence_disc_number, registration_date, expiry_date, registering_authority, province, status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['registration_number'],
            $_POST['licence_disc_number'] ?? null,
            $_POST['registration_date'] ?? null,
            $_POST['expiry_date'] ?? null,
            $_POST['registering_authority'] ?? null,
            $_POST['province'] ?? null,
            $_POST['status'] ?? 'Active'
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_registration') {
        $registration_id = (int) ($_POST['registration_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_registrations WHERE registration_id = ? AND vehicle_id = ?");
        $stmt->execute([$registration_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Registration not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_registrations SET
            registration_number = ?, licence_disc_number = ?, registration_date = ?,
            expiry_date = ?, registering_authority = ?, province = ?, status = ?
            WHERE registration_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['registration_number'],
            $_POST['licence_disc_number'] ?? null,
            $_POST['registration_date'] ?? null,
            $_POST['expiry_date'] ?? null,
            $_POST['registering_authority'] ?? null,
            $_POST['province'] ?? null,
            $_POST['status'] ?? 'Active',
            $registration_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_registration') {
        $id = (int) ($_POST['registration_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_registrations WHERE registration_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- VIEWINGS ----------
    elseif ($action === 'get_viewings') {
        $sql = "SELECT v.*, CONCAT(p.first_name,' ',p.last_name) AS customer_name, c.customer_number
                FROM vehicle_viewings v
                JOIN customers c ON v.customer_id = c.customer_id
                JOIN persons p ON c.person_id = p.person_id
                WHERE v.vehicle_id = ?
                ORDER BY v.viewing_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vehicle_id]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'get_viewing') {
        $viewing_id = (int) ($_REQUEST['viewing_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vehicle_viewings WHERE viewing_id = ? AND vehicle_id = ?");
        $stmt->execute([$viewing_id, $vehicle_id]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    elseif ($action === 'add_viewing') {
        $stmt = $pdo->prepare("INSERT INTO vehicle_viewings (vehicle_id, customer_id, employee_id, viewing_date, viewing_time, location, status, customer_notes, employee_notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $vehicle_id,
            $_POST['customer_id'],
            $_POST['employee_id'] ?? null,
            $_POST['viewing_date'],
            $_POST['viewing_time'],
            $_POST['location'] ?? null,
            $_POST['status'] ?? 'Pending',
            $_POST['customer_notes'] ?? null,
            $_POST['employee_notes'] ?? null
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'update_viewing') {
        $viewing_id = (int) ($_POST['viewing_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM vehicle_viewings WHERE viewing_id = ? AND vehicle_id = ?");
        $stmt->execute([$viewing_id, $vehicle_id]);
        if (!$stmt->fetchColumn()) { $response = ['error' => 'Viewing not found']; echo json_encode($response); exit; }

        $stmt = $pdo->prepare("UPDATE vehicle_viewings SET
            customer_id = ?, employee_id = ?, viewing_date = ?, viewing_time = ?,
            location = ?, status = ?, customer_notes = ?, employee_notes = ?
            WHERE viewing_id = ? AND vehicle_id = ?");
        $stmt->execute([
            $_POST['customer_id'],
            $_POST['employee_id'] ?? null,
            $_POST['viewing_date'],
            $_POST['viewing_time'],
            $_POST['location'] ?? null,
            $_POST['status'] ?? 'Pending',
            $_POST['customer_notes'] ?? null,
            $_POST['employee_notes'] ?? null,
            $viewing_id,
            $vehicle_id
        ]);
        $response = ['success' => true];
    }
    elseif ($action === 'delete_viewing') {
        $id = (int) ($_POST['viewing_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM vehicle_viewings WHERE viewing_id = ? AND vehicle_id = ?");
        $stmt->execute([$id, $vehicle_id]);
        $response = ['success' => true];
    }

    // ---------- Default ----------
    else {
        $response = ['error' => 'Invalid action'];
    }

} catch (PDOException $e) {
    $response = ['error' => 'Database error: ' . $e->getMessage()];
} catch (Exception $e) {
    $response = ['error' => 'Server error: ' . $e->getMessage()];
}

echo json_encode($response);