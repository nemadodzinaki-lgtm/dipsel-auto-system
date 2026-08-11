<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// ----------------------------------------------------------------------
// 1. HELPER FUNCTIONS
// ----------------------------------------------------------------------
function generateStockNumber(PDO $pdo): string {
    $prefix = 'STK' . date('Y') . '-';
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(stock_number, LENGTH(:prefix) + 1) AS UNSIGNED)) AS last_num 
                           FROM vehicles WHERE stock_number LIKE :like_prefix");
    $stmt->execute([':prefix' => $prefix, ':like_prefix' => $prefix . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row['last_num'] ?? 0) + 1;
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

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

function sanitiseInput(string $value, string $default = ''): string {
    $trimmed = trim($value);
    return $trimmed === '' ? $default : $trimmed;
}

// ----------------------------------------------------------------------
// 2. PROCESS ACTIONS (add / edit / delete)
// ----------------------------------------------------------------------
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$errors = [];

// --- ADD ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitise all fields
    $data = [
        'stock_number'        => sanitiseInput($_POST['stock_number'] ?? ''),
        'vin'                 => sanitiseInput($_POST['vin_number'] ?? ''),
        'registration_number' => sanitiseInput($_POST['registration_number'] ?? ''),
        'make'                => sanitiseInput($_POST['make'] ?? ''),
        'model'               => sanitiseInput($_POST['model'] ?? ''),
        'variant'             => sanitiseInput($_POST['variant'] ?? ''),
        'year'                => (int) ($_POST['manufacture_year'] ?? 0),
        'colour'              => sanitiseInput($_POST['colour'] ?? ''),
        'fuel_type'           => sanitiseInput($_POST['fuel_type'] ?? 'Petrol'),
        'transmission'        => sanitiseInput($_POST['transmission'] ?? 'Automatic'),
        'mileage'             => (int) ($_POST['mileage'] ?? 0),
        'price'               => (float) ($_POST['price'] ?? 0),
        'vehicle_source'      => sanitiseInput($_POST['vehicle_source'] ?? 'Dealership'),
        'current_location'    => sanitiseInput($_POST['current_location'] ?? 'Showroom'),
        'description'         => sanitiseInput($_POST['description'] ?? ''),
        'status'              => sanitiseInput($_POST['status'] ?? 'available'),
        'engine_size'         => sanitiseInput($_POST['engine_size'] ?? ''),
        'engine_number'       => sanitiseInput($_POST['engine_number'] ?? ''),
        'doors'               => (int) ($_POST['doors'] ?? 0),
        'seats'               => (int) ($_POST['seats'] ?? 0),
        'body_type'           => sanitiseInput($_POST['body_type'] ?? 'Sedan'),
        'drivetrain'          => sanitiseInput($_POST['drivetrain'] ?? 'FWD'),
        'acquisition_type'    => sanitiseInput($_POST['acquisition_type'] ?? 'Purchased'),
        'condition_type'      => sanitiseInput($_POST['condition_type'] ?? 'Not specified'),
        'service_history'     => sanitiseInput($_POST['service_history'] ?? 'Not specified'),
        'accident_history'    => sanitiseInput($_POST['accident_history'] ?? 'None'),
        'damage_status'       => sanitiseInput($_POST['damage_status'] ?? 'None'),
        'damage_description'  => sanitiseInput($_POST['damage_description'] ?? 'None'),
        'roadworthy'          => sanitiseInput($_POST['roadworthy'] ?? 'No'),
        'warranty'            => sanitiseInput($_POST['warranty'] ?? 'None'),
        'advertised'          => sanitiseInput($_POST['advertised'] ?? 'Yes'),
        'available_for_sale'  => sanitiseInput($_POST['available_for_sale'] ?? 'Yes'),
        'featured'            => sanitiseInput($_POST['featured'] ?? 'No'),
        'purchase_date'       => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
        'purchase_price'      => !empty($_POST['purchase_price']) ? (float) $_POST['purchase_price'] : null,
    ];

    // Validation
    if (empty($data['vin']))         $errors[] = 'VIN is required.';
    if (empty($data['make']))        $errors[] = 'Make is required.';
    if (empty($data['model']))       $errors[] = 'Model is required.';
    if ($data['year'] < 1900 || $data['year'] > date('Y') + 1) $errors[] = 'Invalid year.';
    if ($data['price'] <= 0)         $errors[] = 'Price must be greater than 0.';

    // Image upload
    $imagePath = null;
    if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagePath = uploadVehicleImage($_FILES['vehicle_image'], $errors);
    }

    if (empty($errors)) {
        $stockNumber = empty($data['stock_number']) ? generateStockNumber($pdo) : $data['stock_number'];

        $sql = "INSERT INTO vehicles (
            stock_number, vin, registration_number, make, model, variant,
            manufacture_year, price, mileage, colour, fuel_type,
            transmission, vehicle_source, current_location, description,
            status, engine_size, engine_number, doors, seats,
            condition_type, service_history, accident_history,
            damage_status, damage_description, roadworthy, warranty,
            seller_person_id, current_owner_person_id,
            advertised, available_for_sale,
            purchase_date, purchase_price, acquisition_type, body_type, drivetrain,
            featured,
            created_at
        ) VALUES (
            :stock, :vin, :reg, :make, :model, :variant,
            :year, :price, :mileage, :colour, :fuel,
            :trans, :src, :loc, :desc,
            :status, :engine_size, :engine_number, :doors, :seats,
            :condition_type, :service_history, :accident_history,
            :damage_status, :damage_description, :roadworthy, :warranty,
            1, 1, :advertised, :available_for_sale,
            :purchase_date, :purchase_price, :acquisition_type, :body_type, :drivetrain,
            :featured,
            NOW()
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':stock' => $stockNumber,
            ':vin' => $data['vin'],
            ':reg' => $data['registration_number'],
            ':make' => $data['make'],
            ':model' => $data['model'],
            ':variant' => $data['variant'],
            ':year' => $data['year'],
            ':price' => $data['price'],
            ':mileage' => $data['mileage'],
            ':colour' => $data['colour'],
            ':fuel' => $data['fuel_type'],
            ':trans' => $data['transmission'],
            ':src' => $data['vehicle_source'],
            ':loc' => $data['current_location'],
            ':desc' => $data['description'],
            ':status' => $data['status'],
            ':engine_size' => $data['engine_size'],
            ':engine_number' => $data['engine_number'],
            ':doors' => $data['doors'],
            ':seats' => $data['seats'],
            ':condition_type' => $data['condition_type'],
            ':service_history' => $data['service_history'],
            ':accident_history' => $data['accident_history'],
            ':damage_status' => $data['damage_status'],
            ':damage_description' => $data['damage_description'],
            ':roadworthy' => $data['roadworthy'],
            ':warranty' => $data['warranty'],
            ':advertised' => $data['advertised'],
            ':available_for_sale' => $data['available_for_sale'],
            ':purchase_date' => $data['purchase_date'],
            ':purchase_price' => $data['purchase_price'],
            ':acquisition_type' => $data['acquisition_type'],
            ':body_type' => $data['body_type'],
            ':drivetrain' => $data['drivetrain'],
            ':featured' => $data['featured'],
        ]);

        $vehicleId = $pdo->lastInsertId();

        if ($imagePath) {
            $imgSql = "INSERT INTO vehicle_images (vehicle_id, image_path, is_primary, display_order, uploaded_at)
                       VALUES (:vid, :path, 'yes', 'yes', NOW())";
            $imgStmt = $pdo->prepare($imgSql);
            $imgStmt->execute([':vid' => $vehicleId, ':path' => $imagePath]);
        }

        $_SESSION['success'] = "Vehicle added successfully. Stock #: $stockNumber";
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: index.php');
    exit;
}

// --- EDIT ---
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    if (!$vehicleId) {
        $_SESSION['error'] = 'Invalid vehicle ID.';
        header('Location: index.php');
        exit;
    }

    // Collect and sanitise (same as add)
    $data = [
        'stock_number'        => sanitiseInput($_POST['stock_number'] ?? ''),
        'vin'                 => sanitiseInput($_POST['vin_number'] ?? ''),
        'registration_number' => sanitiseInput($_POST['registration_number'] ?? ''),
        'make'                => sanitiseInput($_POST['make'] ?? ''),
        'model'               => sanitiseInput($_POST['model'] ?? ''),
        'variant'             => sanitiseInput($_POST['variant'] ?? ''),
        'year'                => (int) ($_POST['manufacture_year'] ?? 0),
        'colour'              => sanitiseInput($_POST['colour'] ?? ''),
        'fuel_type'           => sanitiseInput($_POST['fuel_type'] ?? 'Petrol'),
        'transmission'        => sanitiseInput($_POST['transmission'] ?? 'Automatic'),
        'mileage'             => (int) ($_POST['mileage'] ?? 0),
        'price'               => (float) ($_POST['price'] ?? 0),
        'vehicle_source'      => sanitiseInput($_POST['vehicle_source'] ?? 'Dealership'),
        'current_location'    => sanitiseInput($_POST['current_location'] ?? 'Showroom'),
        'description'         => sanitiseInput($_POST['description'] ?? ''),
        'status'              => sanitiseInput($_POST['status'] ?? 'available'),
        'engine_size'         => sanitiseInput($_POST['engine_size'] ?? ''),
        'engine_number'       => sanitiseInput($_POST['engine_number'] ?? ''),
        'doors'               => (int) ($_POST['doors'] ?? 0),
        'seats'               => (int) ($_POST['seats'] ?? 0),
        'body_type'           => sanitiseInput($_POST['body_type'] ?? 'Sedan'),
        'drivetrain'          => sanitiseInput($_POST['drivetrain'] ?? 'FWD'),
        'acquisition_type'    => sanitiseInput($_POST['acquisition_type'] ?? 'Purchased'),
        'condition_type'      => sanitiseInput($_POST['condition_type'] ?? 'Not specified'),
        'service_history'     => sanitiseInput($_POST['service_history'] ?? 'Not specified'),
        'accident_history'    => sanitiseInput($_POST['accident_history'] ?? 'None'),
        'damage_status'       => sanitiseInput($_POST['damage_status'] ?? 'None'),
        'damage_description'  => sanitiseInput($_POST['damage_description'] ?? 'None'),
        'roadworthy'          => sanitiseInput($_POST['roadworthy'] ?? 'No'),
        'warranty'            => sanitiseInput($_POST['warranty'] ?? 'None'),
        'advertised'          => sanitiseInput($_POST['advertised'] ?? 'Yes'),
        'available_for_sale'  => sanitiseInput($_POST['available_for_sale'] ?? 'Yes'),
        'featured'            => sanitiseInput($_POST['featured'] ?? 'No'),
        'purchase_date'       => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
        'purchase_price'      => !empty($_POST['purchase_price']) ? (float) $_POST['purchase_price'] : null,
    ];

    // Validate
    if (empty($data['vin']))         $errors[] = 'VIN is required.';
    if (empty($data['make']))        $errors[] = 'Make is required.';
    if (empty($data['model']))       $errors[] = 'Model is required.';
    if ($data['year'] < 1900 || $data['year'] > date('Y') + 1) $errors[] = 'Invalid year.';
    if ($data['price'] <= 0)         $errors[] = 'Price must be greater than 0.';

    // Image upload (if any)
    $imagePath = null;
    if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imagePath = uploadVehicleImage($_FILES['vehicle_image'], $errors);
    }

    if (empty($errors)) {
        $sql = "UPDATE vehicles SET
            stock_number = :stock,
            vin = :vin,
            registration_number = :reg,
            make = :make,
            model = :model,
            variant = :variant,
            manufacture_year = :year,
            price = :price,
            mileage = :mileage,
            colour = :colour,
            fuel_type = :fuel,
            transmission = :trans,
            vehicle_source = :src,
            current_location = :loc,
            description = :desc,
            status = :status,
            engine_size = :engine_size,
            engine_number = :engine_number,
            doors = :doors,
            seats = :seats,
            condition_type = :condition_type,
            service_history = :service_history,
            accident_history = :accident_history,
            damage_status = :damage_status,
            damage_description = :damage_description,
            roadworthy = :roadworthy,
            warranty = :warranty,
            advertised = :advertised,
            available_for_sale = :available_for_sale,
            purchase_date = :purchase_date,
            purchase_price = :purchase_price,
            acquisition_type = :acquisition_type,
            body_type = :body_type,
            drivetrain = :drivetrain,
            featured = :featured,
            updated_at = NOW()
        WHERE vehicle_id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':stock' => $data['stock_number'],
            ':vin' => $data['vin'],
            ':reg' => $data['registration_number'],
            ':make' => $data['make'],
            ':model' => $data['model'],
            ':variant' => $data['variant'],
            ':year' => $data['year'],
            ':price' => $data['price'],
            ':mileage' => $data['mileage'],
            ':colour' => $data['colour'],
            ':fuel' => $data['fuel_type'],
            ':trans' => $data['transmission'],
            ':src' => $data['vehicle_source'],
            ':loc' => $data['current_location'],
            ':desc' => $data['description'],
            ':status' => $data['status'],
            ':engine_size' => $data['engine_size'],
            ':engine_number' => $data['engine_number'],
            ':doors' => $data['doors'],
            ':seats' => $data['seats'],
            ':condition_type' => $data['condition_type'],
            ':service_history' => $data['service_history'],
            ':accident_history' => $data['accident_history'],
            ':damage_status' => $data['damage_status'],
            ':damage_description' => $data['damage_description'],
            ':roadworthy' => $data['roadworthy'],
            ':warranty' => $data['warranty'],
            ':advertised' => $data['advertised'],
            ':available_for_sale' => $data['available_for_sale'],
            ':purchase_date' => $data['purchase_date'],
            ':purchase_price' => $data['purchase_price'],
            ':acquisition_type' => $data['acquisition_type'],
            ':body_type' => $data['body_type'],
            ':drivetrain' => $data['drivetrain'],
            ':featured' => $data['featured'],
            ':id' => $vehicleId
        ]);

        // If a new image was uploaded, replace the primary image
        if ($imagePath) {
            // Delete old primary
            $delStmt = $pdo->prepare("DELETE FROM vehicle_images WHERE vehicle_id = :vid AND is_primary = 1");
            $delStmt->execute([':vid' => $vehicleId]);

            $imgSql = "INSERT INTO vehicle_images (vehicle_id, image_path, is_primary, display_order, uploaded_at)
                       VALUES (:vid, :path, 'yes', 'yes', NOW())";
            $imgStmt = $pdo->prepare($imgSql);
            $imgStmt->execute([':vid' => $vehicleId, ':path' => $imagePath]);
        }

        $_SESSION['success'] = "Vehicle #$vehicleId updated successfully.";
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: index.php');
    exit;
}

// --- DELETE ---
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    // Delete images first
    $delImg = $pdo->prepare("DELETE FROM vehicle_images WHERE vehicle_id = ?");
    $delImg->execute([$id]);
    // Delete vehicle
    $del = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    $del->execute([$id]);
    $_SESSION['success'] = "Vehicle #$id deleted.";
    header('Location: index.php');
    exit;
}

// ----------------------------------------------------------------------
// 3. FETCH DATA FOR DISPLAY
// ----------------------------------------------------------------------
$total = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$available = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'")->fetchColumn();
$sold = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'sold'")->fetchColumn();

$sql = "
    SELECT v.*, 
           (SELECT vi.image_path FROM vehicle_images vi 
            WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes' 
            LIMIT 1) AS image_path
    FROM vehicles v
    ORDER BY v.vehicle_id DESC
";
$vehicles = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Dashboard Wrapper (same as dashboard) ── */
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }

        /* ── Stats Cards (identical to dashboard) ── */
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 20px 20px 20px 25px;
            transition: transform 0.25s ease, box-shadow 0.3s ease;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
        }
        .stat-card .stat-icon {
            font-size: 2.8rem;
            opacity: 0.2;
            position: absolute;
            right: 15px;
            bottom: 15px;
            transition: all 0.3s;
        }
        .stat-card:hover .stat-icon {
            opacity: 0.35;
            transform: scale(1.05);
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        /* Accent colours */
        .stat-card.vehicles   { border-left: 6px solid #4f46e5; }
        .stat-card.available  { border-left: 6px solid #10b981; }
        .stat-card.sold       { border-left: 6px solid #ef4444; }

        /* ── Page Header ── */
        .page-header {
            padding: 0 0 1.5rem 0;
        }
        .page-header h2 {
            font-weight: 700;
            color: #0f172a;
        }
        .page-header p {
            color: #64748b;
        }

        /* ── Table and cards ── */
        .card-shadow {
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: none;
            border-radius: 20px;
            background: #fff;
        }

        .table-img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            background: #f1f5f9;
        }
        .badge-status {
            padding: 0.45rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .action-btn {
            border-radius: 40px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            margin: 0 2px;
        }
        .featured-badge {
            background: #fbbf24;
            color: #0f172a;
            font-weight: 700;
            padding: 0.15rem 0.8rem;
            border-radius: 40px;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        /* ── Modal ── */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }
        .modal-header {
            border-bottom: 1px solid #eef2f6;
            padding: 1.2rem 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #eef2f6;
        }
        .form-label.required::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            border-radius: 40px;
            padding: 0.6rem 1.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37,99,235,0.3);
        }
        .btn-outline-secondary {
            border-radius: 40px;
            border-color: #d1d9e6;
            color: #475569;
        }
        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
        .help-text {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* ── DataTables overrides ── */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 40px;
            padding: 0.4rem 1rem;
            border: 1.5px solid #e2e8f0;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .table thead th {
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #eef2f6;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.3px;
            padding: 0.8rem 0.5rem;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* ── Modal scroll fix ── */
        .modal-dialog-scrollable .modal-content {
            max-height: 92vh;
        }
        .modal-dialog-scrollable .modal-body {
            max-height: calc(92vh - 160px);
            overflow-y: auto;
        }

        /* ── RESPONSIVE (exactly like dashboard) ── */
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
            .page-header h2 {
                font-size: 1.6rem;
            }
            .page-header .btn {
                align-self: flex-start;
            }
            /* Stats cards */
            .stat-card {
                padding: 15px 15px 15px 20px;
            }
            .stat-card .stat-number {
                font-size: 2rem;
            }
            .stat-card .stat-icon {
                font-size: 2rem;
            }
            /* Container padding */
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            /* Alerts */
            .alert {
                border-radius: 12px !important;
                padding: 0.8rem 1rem;
            }
            /* Modal */
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
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .stat-card {
                padding: 12px 12px 12px 16px;
            }
            .stat-card .stat-number {
                font-size: 1.6rem;
            }
            .stat-card .stat-label {
                font-size: 0.75rem;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem;
                right: 10px;
                bottom: 10px;
            }
            .table-img {
                width: 40px;
                height: 40px;
            }
            .badge-status {
                font-size: 0.7rem;
                padding: 0.3rem 0.7rem;
            }
            .action-btn {
                padding: 0.2rem 0.6rem;
                font-size: 0.7rem;
            }
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .btn-primary {
                padding: 0.5rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4 px-4">

        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-car text-primary me-2"></i>Vehicle Management</h2>
                <p class="mb-0">Complete control over your inventory – add, edit, or remove vehicles.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> Add Vehicle
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-pill">
                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-pill">
                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Stats Cards (3 columns) -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card vehicles">
                    <div class="stat-label">Total Vehicles</div>
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-icon"><i class="fas fa-warehouse"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card available">
                    <div class="stat-label">Available</div>
                    <div class="stat-number"><?= $available ?></div>
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card sold">
                    <div class="stat-label">Sold</div>
                    <div class="stat-number"><?= $sold ?></div>
                    <div class="stat-icon"><i class="fas fa-sold-sign"></i></div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="vehiclesTable" class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Stock No</th>
                                <th>Vehicle</th>
                                <th>Year</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 1; while ($row = $vehicles->fetch()): 
                            $img = !empty($row['image_path']) ? '../../' . $row['image_path'] : '../../assets/uploads/vehicles/default.png';
                            $statusClass = match($row['status'] ?? 'unknown') {
                                'available' => 'success', 
                                'sold' => 'danger', 
                                'reserved' => 'warning', 
                                default => 'secondary'
                            };
                            $featured = ($row['featured'] ?? 'No') === 'Yes';
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><img src="<?= htmlspecialchars($img) ?>" class="table-img" onerror="this.src='../../assets/uploads/vehicles/default.png'"></td>
                                <td><strong><?= htmlspecialchars($row['stock_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['make']) ?> <?= htmlspecialchars($row['model']) ?></td>
                                <td><?= htmlspecialchars($row['manufacture_year']) ?></td>
                                <td class="fw-bold">R <?= number_format($row['price'], 2) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= ucfirst($row['status']) ?></span></td>
                                <td>
                                    <?php if ($featured): ?>
                                        <span class="featured-badge"><i class="fas fa-star me-1"></i> Featured</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary action-btn" onclick="location.href='vehicle_details.php?id=<?= $row['vehicle_id'] ?>'" title="Manage"><i class="fas fa-cogs"></i></button>
                                    <button class="btn btn-sm btn-outline-warning action-btn" onclick="editVehicle(<?= $row['vehicle_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="?action=delete&id=<?= $row['vehicle_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this vehicle?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>
</div> <!-- /dashboard-wrapper -->

<!-- ========== MODAL (Add / Edit) ========== -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-car me-2"></i>Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="vehicleForm" novalidate>
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="vehicle_id" id="vehicleId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left column: Basic & Specs -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted mb-3"><i class="fas fa-info-circle me-1"></i>Basic Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Stock Number</label>
                                <input type="text" name="stock_number" id="stock_number" class="form-control" placeholder="Leave blank to auto-generate">
                                <div class="help-text">Auto‑generated if left empty.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">VIN Number</label>
                                <input type="text" name="vin_number" id="vin_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" id="registration_number" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Make</label>
                                <input type="text" name="make" id="make" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Model</label>
                                <input type="text" name="model" id="model" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Variant</label>
                                <input type="text" name="variant" id="variant" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Year</label>
                                <input type="number" name="manufacture_year" id="manufacture_year" class="form-control" required min="1900" max="<?= date('Y')+1 ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Colour</label>
                                <input type="text" name="colour" id="colour" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fuel Type</label>
                                <select name="fuel_type" id="fuel_type" class="form-select">
                                    <option>Petrol</option><option>Diesel</option><option>Hybrid</option><option>Electric</option><option>Plug-in Hybrid</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Transmission</label>
                                <select name="transmission" id="transmission" class="form-select">
                                    <option>Automatic</option><option>Manual</option><option>CVT</option><option>Semi-Automatic</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mileage (km)</label>
                                <input type="number" name="mileage" id="mileage" class="form-control" min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Price (R)</label>
                                <input type="number" step="0.01" name="price" id="price" class="form-control" required min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Body Type</label>
                                <select name="body_type" id="body_type" class="form-select">
                                    <option value="Sedan">Sedan</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Hatchback">Hatchback</option>
                                    <option value="Coupe">Coupe</option>
                                    <option value="Convertible">Convertible</option>
                                    <option value="Wagon">Wagon</option>
                                    <option value="Van">Van</option>
                                    <option value="Truck">Truck</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Drivetrain</label>
                                <select name="drivetrain" id="drivetrain" class="form-select">
                                    <option value="FWD">FWD</option><option value="RWD">RWD</option><option value="AWD">AWD</option><option value="4WD">4WD</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Engine Size</label>
                                <input type="text" name="engine_size" id="engine_size" class="form-control" placeholder="e.g. 2.0L">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Engine Number</label>
                                <input type="text" name="engine_number" id="engine_number" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Doors</label>
                                    <input type="number" name="doors" id="doors" class="form-control" min="2" max="6">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Seats</label>
                                    <input type="number" name="seats" id="seats" class="form-control" min="2" max="9">
                                </div>
                            </div>
                        </div>

                        <!-- Right column: Status, Condition, Purchase, etc. -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted mb-3"><i class="fas fa-cog me-1"></i>Status & Condition</h6>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="available">Available</option>
                                    <option value="sold">Sold</option>
                                    <option value="reserved">Reserved</option>
                                    <option value="hidden">Hidden</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Advertised</label>
                                <select name="advertised" id="advertised" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Available for Sale</label>
                                <select name="available_for_sale" id="available_for_sale" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Featured</label>
                                <select name="featured" id="featured" class="form-select">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                                <div class="help-text">Mark as featured to highlight in the showroom.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vehicle Source</label>
                                <select name="vehicle_source" id="vehicle_source" class="form-select">
                                    <option>Dealership</option><option>Customer</option><option>Trade-In</option><option>Consignment</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Location</label>
                                <select name="current_location" id="current_location" class="form-select">
                                    <option>Showroom</option><option>Workshop</option><option>Storage</option><option>Customer</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Condition Type</label>
                                <select name="condition_type" id="condition_type" class="form-select">
                                    <option value="Not specified">Not specified</option>
                                    <option value="Excellent">Excellent</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                    <option value="New">New</option>
                                    <option value="Demo">Demo</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Service History</label>
                                <select name="service_history" id="service_history" class="form-select">
                                    <option value="None">None</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Full">Full</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Accident History</label>
                                <select name="accident_history" id="accident_history" class="form-select">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Damage Status</label>
                                <select name="damage_status" id="damage_status" class="form-select">
                                    <option value="None">None</option>
                                    <option value="Minor">Minor</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Major">Major</option>
                                    <option value="Write-Off">Write-Off</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Damage Description</label>
                                <textarea name="damage_description" id="damage_description" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Roadworthy</label>
                                <select name="roadworthy" id="roadworthy" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Warranty</label>
                                <input type="text" name="warranty" id="warranty" class="form-control" placeholder="e.g. 1 year / 20 000 km">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Acquisition Type</label>
                                <select name="acquisition_type" id="acquisition_type" class="form-select">
                                    <option value="Purchased">Purchased</option>
                                    <option value="Trade-In">Trade‑In</option>
                                    <option value="Consignment">Consignment</option>
                                    <option value="Customer">Customer</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" id="purchase_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Price (R)</label>
                                <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vehicle Image</label>
                                <input type="file" name="vehicle_image" id="vehicle_image" class="form-control" accept="image/*">
                                <div class="help-text">Allowed: JPG, PNG, GIF, WEBP. Max size: 5MB.</div>
                                <div id="currentImagePreview" class="mt-2" style="display:none;">
                                    <img id="imagePreview" src="" class="img-thumbnail" style="max-height:150px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="saveBtn"><i class="fas fa-save me-1"></i> Save Vehicle</button>
                </div>
            </form>
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
    $('#vehiclesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [1,8] }],
        language: { search: "Filter:", searchPlaceholder: "Search vehicles..." }
    });
});

function openAddModal() {
    $('#modalTitle').html('<i class="fas fa-car me-2"></i>Add Vehicle');
    $('#formAction').val('add');
    $('#vehicleId').val(0);
    $('#vehicleForm')[0].reset();
    $('#currentImagePreview').hide();
    $('#saveBtn').html('<i class="fas fa-save me-1"></i> Save Vehicle');
    // Set defaults
    $('#status').val('available');
    $('#advertised').val('Yes');
    $('#available_for_sale').val('Yes');
    $('#featured').val('No');
    $('#condition_type').val('Not specified');
    $('#service_history').val('None');
    $('#accident_history').val('No');
    $('#damage_status').val('None');
    $('#damage_description').val('');
    $('#roadworthy').val('Yes');
    $('#warranty').val('');
    $('#body_type').val('Sedan');
    $('#drivetrain').val('FWD');
    $('#acquisition_type').val('Purchased');
    $('#purchase_date').val('');
    $('#purchase_price').val('');
    $('#fuel_type').val('Petrol');
    $('#transmission').val('Automatic');
    $('#vehicle_source').val('Dealership');
    $('#current_location').val('Showroom');
    $('#engine_size').val('');
    $('#engine_number').val('');
    $('#doors').val(4);
    $('#seats').val(5);
    $('#vehicleModal').modal('show');
}

function editVehicle(id) {
    $.ajax({
        url: 'get_vehicle.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Vehicle');
            $('#formAction').val('edit');
            $('#vehicleId').val(data.vehicle_id);
            $('#stock_number').val(data.stock_number);
            $('#vin_number').val(data.vin);
            $('#registration_number').val(data.registration_number);
            $('#make').val(data.make);
            $('#model').val(data.model);
            $('#variant').val(data.variant);
            $('#manufacture_year').val(data.manufacture_year);
            $('#colour').val(data.colour);
            $('#fuel_type').val(data.fuel_type);
            $('#transmission').val(data.transmission);
            $('#mileage').val(data.mileage);
            $('#price').val(data.price);
            $('#vehicle_source').val(data.vehicle_source);
            $('#current_location').val(data.current_location);
            $('#status').val(data.status);
            $('#engine_size').val(data.engine_size);
            $('#engine_number').val(data.engine_number);
            $('#doors').val(data.doors);
            $('#seats').val(data.seats);
            $('#condition_type').val(data.condition_type || 'Not specified');
            $('#service_history').val(data.service_history || 'None');
            $('#accident_history').val(data.accident_history || 'No');
            $('#damage_status').val(data.damage_status || 'None');
            $('#damage_description').val(data.damage_description || '');
            $('#roadworthy').val(data.roadworthy || 'Yes');
            $('#warranty').val(data.warranty || '');
            $('#description').val(data.description);
            $('#body_type').val(data.body_type || 'Sedan');
            $('#drivetrain').val(data.drivetrain || 'FWD');
            $('#acquisition_type').val(data.acquisition_type || 'Purchased');
            $('#purchase_date').val(data.purchase_date || '');
            $('#purchase_price').val(data.purchase_price || '');
            $('#advertised').val(data.advertised || 'Yes');
            $('#available_for_sale').val(data.available_for_sale || 'Yes');
            $('#featured').val(data.featured || 'No');

            if (data.image_path) {
                $('#imagePreview').attr('src', '../../' + data.image_path);
                $('#currentImagePreview').show();
            } else {
                $('#currentImagePreview').hide();
            }
            $('#saveBtn').html('<i class="fas fa-save me-1"></i> Update Vehicle');
            $('#vehicleModal').modal('show');
        },
        error: function() { alert('Error loading vehicle data.'); }
    });
}
</script>
</body>
</html>