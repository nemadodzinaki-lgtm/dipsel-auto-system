<?php
/**
 * Workshops & Services Management
 * - Complete CRUD for workshop_services and workshops
 * - Employee dropdown for workshop manager
 * - Statistics, DataTables, modals, AJAX views
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

// ----------------------------------------------------------------------
// 1. HELPER FUNCTIONS
// ----------------------------------------------------------------------
function sanitiseInput(string $value, string $default = ''): string {
    $trimmed = trim($value);
    return $trimmed === '' ? $default : $trimmed;
}

// ----------------------------------------------------------------------
// 2. PROCESS ACTIONS
// ----------------------------------------------------------------------

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$errors = [];

// ---------- SERVICE ACTIONS ----------
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'workshop_id'              => (int) ($_POST['workshop_id'] ?? 0),
        'service_name'             => sanitiseInput($_POST['service_name'] ?? ''),
        'category'                 => sanitiseInput($_POST['category'] ?? 'General Service'),
        'description'              => sanitiseInput($_POST['description'] ?? ''),
        'estimated_duration_hours' => (float) ($_POST['estimated_duration_hours'] ?? 1.00),
        'base_price'               => (float) ($_POST['base_price'] ?? 0),
        'status'                   => sanitiseInput($_POST['status'] ?? 'Available'),
    ];

    if (empty($data['workshop_id']))       $errors[] = 'Workshop is required.';
    if (empty($data['service_name']))      $errors[] = 'Service name is required.';
    if ($data['base_price'] <= 0)          $errors[] = 'Base price must be greater than 0.';
    if ($data['estimated_duration_hours'] < 0) $errors[] = 'Duration cannot be negative.';

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO workshop_services (
                workshop_id, service_name, category, description,
                estimated_duration_hours, base_price, status, created_at
            ) VALUES (
                :workshop_id, :service_name, :category, :description,
                :duration, :base_price, :status, NOW()
            )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':workshop_id' => $data['workshop_id'],
                ':service_name' => $data['service_name'],
                ':category' => $data['category'],
                ':description' => $data['description'],
                ':duration' => $data['estimated_duration_hours'],
                ':base_price' => $data['base_price'],
                ':status' => $data['status'],
            ]);
            $_SESSION['success'] = "Service added successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    if (!$serviceId) {
        $_SESSION['error'] = 'Invalid service ID.';
        header('Location: index.php');
        exit;
    }

    $data = [
        'workshop_id'              => (int) ($_POST['workshop_id'] ?? 0),
        'service_name'             => sanitiseInput($_POST['service_name'] ?? ''),
        'category'                 => sanitiseInput($_POST['category'] ?? 'General Service'),
        'description'              => sanitiseInput($_POST['description'] ?? ''),
        'estimated_duration_hours' => (float) ($_POST['estimated_duration_hours'] ?? 1.00),
        'base_price'               => (float) ($_POST['base_price'] ?? 0),
        'status'                   => sanitiseInput($_POST['status'] ?? 'Available'),
    ];

    if (empty($data['workshop_id']))       $errors[] = 'Workshop is required.';
    if (empty($data['service_name']))      $errors[] = 'Service name is required.';
    if ($data['base_price'] <= 0)          $errors[] = 'Base price must be greater than 0.';
    if ($data['estimated_duration_hours'] < 0) $errors[] = 'Duration cannot be negative.';

    if (empty($errors)) {
        try {
            $sql = "UPDATE workshop_services SET
                workshop_id = :workshop_id,
                service_name = :service_name,
                category = :category,
                description = :description,
                estimated_duration_hours = :duration,
                base_price = :base_price,
                status = :status,
                updated_at = NOW()
                WHERE service_id = :service_id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':workshop_id' => $data['workshop_id'],
                ':service_name' => $data['service_name'],
                ':category' => $data['category'],
                ':description' => $data['description'],
                ':duration' => $data['estimated_duration_hours'],
                ':base_price' => $data['base_price'],
                ':status' => $data['status'],
                ':service_id' => $serviceId,
            ]);
            $_SESSION['success'] = "Service #$serviceId updated successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $del = $pdo->prepare("DELETE FROM workshop_services WHERE service_id = ?");
        $del->execute([$id]);
        $_SESSION['success'] = "Service #$id deleted.";
    } catch (Exception $e) {
        $_SESSION['error'] = 'Delete failed: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// ---------- WORKSHOP ACTIONS ----------
if ($action === 'workshop_add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'workshop_name'   => sanitiseInput($_POST['workshop_name'] ?? ''),
        'phone'           => sanitiseInput($_POST['phone'] ?? ''),
        'email'           => sanitiseInput($_POST['email'] ?? ''),
        'address'         => sanitiseInput($_POST['address'] ?? ''),
        'city'            => sanitiseInput($_POST['city'] ?? ''),
        'province'        => sanitiseInput($_POST['province'] ?? ''),
        'operating_hours' => sanitiseInput($_POST['operating_hours'] ?? ''),
        'status'          => sanitiseInput($_POST['status'] ?? 'Open'),
        'manager_employee_id' => !empty($_POST['manager_employee_id']) ? (int) $_POST['manager_employee_id'] : null,
    ];

    if (empty($data['workshop_name'])) $errors[] = 'Workshop name is required.';

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO workshops (workshop_name, phone, email, address, city, province, operating_hours, status, manager_employee_id, created_at)
                    VALUES (:workshop_name, :phone, :email, :address, :city, :province, :operating_hours, :status, :manager_employee_id, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $_SESSION['success'] = "Workshop added successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

if ($action === 'workshop_edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['workshop_id'] ?? 0);
    if (!$id) {
        $_SESSION['error'] = 'Invalid workshop ID.';
        header('Location: index.php');
        exit;
    }
    $data = [
        'workshop_name'   => sanitiseInput($_POST['workshop_name'] ?? ''),
        'phone'           => sanitiseInput($_POST['phone'] ?? ''),
        'email'           => sanitiseInput($_POST['email'] ?? ''),
        'address'         => sanitiseInput($_POST['address'] ?? ''),
        'city'            => sanitiseInput($_POST['city'] ?? ''),
        'province'        => sanitiseInput($_POST['province'] ?? ''),
        'operating_hours' => sanitiseInput($_POST['operating_hours'] ?? ''),
        'status'          => sanitiseInput($_POST['status'] ?? 'Open'),
        'manager_employee_id' => !empty($_POST['manager_employee_id']) ? (int) $_POST['manager_employee_id'] : null,
    ];

    if (empty($data['workshop_name'])) $errors[] = 'Workshop name is required.';

    if (empty($errors)) {
        try {
            $sql = "UPDATE workshops SET
                        workshop_name = :workshop_name,
                        phone = :phone,
                        email = :email,
                        address = :address,
                        city = :city,
                        province = :province,
                        operating_hours = :operating_hours,
                        status = :status,
                        manager_employee_id = :manager_employee_id,
                        updated_at = NOW()
                    WHERE workshop_id = :workshop_id";
            $data['workshop_id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $_SESSION['success'] = "Workshop #$id updated successfully.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

if ($action === 'workshop_delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $del = $pdo->prepare("DELETE FROM workshops WHERE workshop_id = ?");
        $del->execute([$id]);
        $_SESSION['success'] = "Workshop #$id deleted.";
    } catch (Exception $e) {
        $_SESSION['error'] = 'Delete failed: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// ----------------------------------------------------------------------
// 3. FETCH DATA FOR DISPLAY
// ----------------------------------------------------------------------

// ---- Service stats ----
$totalServices = $pdo->query("SELECT COUNT(*) FROM workshop_services")->fetchColumn();
$available = $pdo->query("SELECT COUNT(*) FROM workshop_services WHERE status = 'Available'")->fetchColumn();
$unavailable = $pdo->query("SELECT COUNT(*) FROM workshop_services WHERE status = 'Unavailable'")->fetchColumn();

// ---- Services list ----
$services = $pdo->query("
    SELECT ws.*, w.workshop_name
    FROM workshop_services ws
    INNER JOIN workshops w ON ws.workshop_id = w.workshop_id
    ORDER BY ws.service_id DESC
");

// ---- Workshops list ----
$workshops = $pdo->query("SELECT * FROM workshops ORDER BY workshop_id DESC");

// ---- For dropdowns ----
$workshopDropdown = $pdo->query("SELECT workshop_id, workshop_name FROM workshops ORDER BY workshop_name");
// Employees dropdown (adjust table/column names as needed)
$employees = $pdo->query("
    SELECT
        e.employee_id,
        CONCAT(
            p.first_name,
            ' ',
            p.last_name
        ) AS full_name
    FROM employees e
    INNER JOIN persons p
        ON e.person_id = p.person_id
    ORDER BY p.first_name, p.last_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshops & Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Dashboard Wrapper ── */
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

        /* ── Badges & Buttons ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 0 2px;
        }

        /* ── Tabs ── */
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 800px;
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

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
                margin-right: 0 !important;
            }
            .page-header .btn:last-child {
                margin-top: 0.5rem;
            }
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h2 {
                font-size: 1.8rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
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
            .table th, .table td {
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
            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
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
            .table th, .table td {
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
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-6:last-child {
                margin-bottom: 0;
            }
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
            .nav-tabs .nav-link {
                font-size: 0.8rem;
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
                <h2><i class="fas fa-tools text-primary me-2"></i>Workshops & Services</h2>
                <p class="text-muted">Manage workshops and the services they offer.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#workshopModal" onclick="openAddWorkshopModal()">
                    <i class="fas fa-plus me-1"></i> Add Workshop
                </button>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="openAddServiceModal()">
                    <i class="fas fa-plus me-1"></i> Add Service
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">Services</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="workshops-tab" data-bs-toggle="tab" data-bs-target="#workshops" type="button" role="tab">Workshops</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- ========== SERVICES TAB ========== -->
            <div class="tab-pane fade show active" id="services" role="tabpanel">
                <!-- Stats for Services -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Total Services</h6>
                                    <h2 class="fw-bold"><?= $totalServices ?></h2>
                                </div>
                                <div class="stat-icon text-primary"><i class="fas fa-list"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Available</h6>
                                    <h2 class="fw-bold text-success"><?= $available ?></h2>
                                </div>
                                <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#dc3545;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Unavailable</h6>
                                    <h2 class="fw-bold text-danger"><?= $unavailable ?></h2>
                                </div>
                                <div class="stat-icon text-danger"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Services</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="servicesTable" class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Workshop</th>
                                        <th>Service Name</th>
                                        <th>Category</th>
                                        <th>Duration</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $counter = 1; while ($row = $services->fetch()): 
                                    $statusClass = $row['status'] === 'Available' ? 'success' : 'danger';
                                ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['workshop_name']) ?></td>
                                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                                        <td><?= htmlspecialchars($row['category']) ?></td>
                                        <td><?= number_format($row['estimated_duration_hours'], 1) ?> h</td>
                                        <td class="fw-bold">R <?= number_format($row['base_price'], 2) ?></td>
                                        <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= ucfirst($row['status']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info action-btn" onclick="viewService(<?= $row['service_id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning action-btn" onclick="editService(<?= $row['service_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            <a href="?action=delete&id=<?= $row['service_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this service?')" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== WORKSHOPS TAB ========== -->
            <div class="tab-pane fade" id="workshops" role="tabpanel">
                <!-- Workshops Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0"><i class="fas fa-store me-2"></i>All Workshops</h5>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#workshopModal" onclick="openAddWorkshopModal()">
                            <i class="fas fa-plus me-1"></i> Add Workshop
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="workshopsTable" class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th>Province</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($w = $workshops->fetch()): 
                                    $statusClass = $w['status'] === 'Open' ? 'success' : ($w['status'] === 'Closed' ? 'secondary' : 'warning');
                                ?>
                                    <tr>
                                        <td><?= $w['workshop_id'] ?></td>
                                        <td><?= htmlspecialchars($w['workshop_name']) ?></td>
                                        <td><?= htmlspecialchars($w['phone']) ?></td>
                                        <td><?= htmlspecialchars($w['email']) ?></td>
                                        <td><?= htmlspecialchars($w['city']) ?></td>
                                        <td><?= htmlspecialchars($w['province']) ?></td>
                                        <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= ucfirst($w['status']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info action-btn" onclick="viewWorkshop(<?= $w['workshop_id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning action-btn" onclick="editWorkshop(<?= $w['workshop_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                            <a href="?action=workshop_delete&id=<?= $w['workshop_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this workshop? This may affect services.')" title="Delete"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- ========== SERVICE MODAL (Add / Edit) ========== -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="serviceModalTitle">Add Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="serviceForm">
                <input type="hidden" name="action" id="serviceFormAction" value="add">
                <input type="hidden" name="service_id" id="serviceId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Workshop</label>
                                <select name="workshop_id" id="service_workshop_id" class="form-select" required>
                                    <option value="">Select Workshop</option>
                                    <?php 
                                    $workshopDropdown->execute(); 
                                    while ($w = $workshopDropdown->fetch()): ?>
                                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['workshop_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Service Name</label>
                                <input type="text" name="service_name" id="service_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" id="service_category" class="form-select">
                                    <option value="General Service">General Service</option>
                                    <option value="Major Service">Major Service</option>
                                    <option value="Engine">Engine</option>
                                    <option value="Transmission">Transmission</option>
                                    <option value="Brakes">Brakes</option>
                                    <option value="Suspension">Suspension</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Air Conditioning">Air Conditioning</option>
                                    <option value="Body Repair">Body Repair</option>
                                    <option value="Paint">Paint</option>
                                    <option value="Diagnostics">Diagnostics</option>
                                    <option value="Wheels & Tyres">Wheels & Tyres</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="service_description" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estimated Duration (hours)</label>
                                <input type="number" step="0.5" name="estimated_duration_hours" id="service_duration" class="form-control" value="1.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Base Price (R)</label>
                                <input type="number" step="0.01" name="base_price" id="service_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="service_status" class="form-select">
                                    <option value="Available">Available</option>
                                    <option value="Unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="serviceSaveBtn">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== WORKSHOP MODAL (Add / Edit) ========== -->
<div class="modal fade" id="workshopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workshopModalTitle">Add Workshop</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="workshopForm">
                <input type="hidden" name="action" id="workshopFormAction" value="workshop_add">
                <input type="hidden" name="workshop_id" id="workshopId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Workshop Name</label>
                                <input type="text" name="workshop_name" id="workshop_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="workshop_phone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="workshop_email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="workshop_address" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" id="workshop_city" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" id="workshop_province" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Operating Hours</label>
                                <input type="text" name="operating_hours" id="workshop_hours" class="form-control" placeholder="e.g. Mon-Fri 8am-5pm">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="workshop_status" class="form-select">
                                    <option value="Open">Open</option>
                                    <option value="Closed">Closed</option>
                                    <option value="Maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manager</label>
                                <select name="manager_employee_id" id="workshop_manager" class="form-select">
                                    <option value="">Select Employee (optional)</option>
                                    <?php while ($emp = $employees->fetch()): ?>
                                        <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="workshopSaveBtn">Save Workshop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== VIEW MODAL (used for both) ========== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalTitle">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
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
    $('#servicesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search services..." }
    });

    $('#workshopsTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search workshops..." }
    });
});

// ----- SERVICE MODAL FUNCTIONS -----
function openAddServiceModal() {
    $('#serviceModalTitle').text('Add Service');
    $('#serviceFormAction').val('add');
    $('#serviceId').val(0);
    $('#serviceForm')[0].reset();
    $('#serviceSaveBtn').text('Save Service');
    $('#serviceModal').modal('show');
}

function editService(id) {
    $.ajax({
        url: 'get_service.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#serviceModalTitle').text('Edit Service');
            $('#serviceFormAction').val('edit');
            $('#serviceId').val(data.service_id);
            $('#service_workshop_id').val(data.workshop_id);
            $('#service_name').val(data.service_name);
            $('#service_category').val(data.category);
            $('#service_description').val(data.description);
            $('#service_duration').val(data.estimated_duration_hours);
            $('#service_price').val(data.base_price);
            $('#service_status').val(data.status);
            $('#serviceSaveBtn').text('Update Service');
            $('#serviceModal').modal('show');
        },
        error: function() { alert('Error loading service data.'); }
    });
}

function viewService(id) {
    $.ajax({
        url: 'get_service.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `<div class="row">
                <div class="col-md-6">
                    <p><strong>Service Name:</strong> ${data.service_name}</p>
                    <p><strong>Workshop:</strong> ${data.workshop_name}</p>
                    <p><strong>Category:</strong> ${data.category}</p>
                    <p><strong>Description:</strong><br>${data.description || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Duration:</strong> ${parseFloat(data.estimated_duration_hours).toFixed(1)} hours</p>
                    <p><strong>Base Price:</strong> R ${parseFloat(data.base_price).toFixed(2)}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${data.status=='Available'?'success':'danger'}">${data.status}</span></p>
                    <p><strong>Created:</strong> ${data.created_at}</p>
                    <p><strong>Last Updated:</strong> ${data.updated_at}</p>
                </div>
            </div>`;
            $('#viewModalTitle').text('Service Details');
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
        },
        error: function() { alert('Error loading service data.'); }
    });
}

// ----- WORKSHOP MODAL FUNCTIONS -----
function openAddWorkshopModal() {
    $('#workshopModalTitle').text('Add Workshop');
    $('#workshopFormAction').val('workshop_add');
    $('#workshopId').val(0);
    $('#workshopForm')[0].reset();
    $('#workshopSaveBtn').text('Save Workshop');
    $('#workshopModal').modal('show');
}

function editWorkshop(id) {
    $.ajax({
        url: 'get_service.php?type=workshop&id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#workshopModalTitle').text('Edit Workshop');
            $('#workshopFormAction').val('workshop_edit');
            $('#workshopId').val(data.workshop_id);
            $('#workshop_name').val(data.workshop_name);
            $('#workshop_phone').val(data.phone);
            $('#workshop_email').val(data.email);
            $('#workshop_address').val(data.address);
            $('#workshop_city').val(data.city);
            $('#workshop_province').val(data.province);
            $('#workshop_hours').val(data.operating_hours);
            $('#workshop_status').val(data.status);
            $('#workshop_manager').val(data.manager_employee_id);
            $('#workshopSaveBtn').text('Update Workshop');
            $('#workshopModal').modal('show');
        },
        error: function() { alert('Error loading workshop data.'); }
    });
}

function viewWorkshop(id) {
    $.ajax({
        url: 'get_service.php?type=workshop&id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `<div class="row">
                <div class="col-md-6">
                    <p><strong>Workshop Name:</strong> ${data.workshop_name}</p>
                    <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                    <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                    <p><strong>Address:</strong><br>${data.address || 'N/A'}</p>
                    <p><strong>City:</strong> ${data.city || 'N/A'}</p>
                    <p><strong>Province:</strong> ${data.province || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Operating Hours:</strong> ${data.operating_hours || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${data.status=='Open'?'success':data.status=='Closed'?'secondary':'warning'}">${data.status}</span></p>
                    <p><strong>Manager:</strong> ${data.manager_employee_id || 'None'}</p>
                    <p><strong>Created:</strong> ${data.created_at}</p>
                    <p><strong>Last Updated:</strong> ${data.updated_at}</p>
                </div>
            </div>`;
            $('#viewModalTitle').text('Workshop Details');
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
        },
        error: function() { alert('Error loading workshop data.'); }
    });
}
</script>

</body>
</html>