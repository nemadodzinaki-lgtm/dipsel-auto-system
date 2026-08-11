<?php
/**
 * Service Booking Management Module
 * - Full CRUD for bookings (Add, Edit, Delete, View)
 * - Statistics cards (total, pending, in-progress, completed)
 * - DataTable with search/status filter
 * - Modal for add/edit with all fields
 * - AJAX loading of job cards and service updates
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, role: string} $currentUser */

// ---------- Helper Functions ----------
function sanitizeInput(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ---------- Handle Actions ----------
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$errors = [];

// ---------- ADD / EDIT (via POST) ----------
if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect all fields
    $booking_id               = (int) ($_POST['booking_id'] ?? 0);
    $booking_reference        = sanitizeInput($_POST['booking_reference'] ?? '');
    $customer_id              = (int) ($_POST['customer_id'] ?? 0);
    $vehicle_id               = (int) ($_POST['vehicle_id'] ?? 0);
    $workshop_id              = (int) ($_POST['workshop_id'] ?? 0);
    $service_id               = (int) ($_POST['service_id'] ?? 0);
    $assigned_employee_id     = (int) ($_POST['assigned_employee_id'] ?? 0);
    $vehicle_name             = sanitizeInput($_POST['vehicle_name'] ?? '');
    $vehicle_model            = sanitizeInput($_POST['vehicle_model'] ?? '');
    $registration_number      = sanitizeInput($_POST['registration_number'] ?? '');
    $booking_date             = $_POST['booking_date'] ?? null;
    $booking_time             = $_POST['booking_time'] ?? null;
    $vehicle_mileage          = (int) ($_POST['vehicle_mileage'] ?? 0);
    $customer_complaint       = sanitizeInput($_POST['customer_complaint'] ?? '');
    $estimated_completion_date= $_POST['estimated_completion_date'] ?? null;
    $actual_completion_date   = $_POST['actual_completion_date'] ?? null;
    $booking_status           = $_POST['booking_status'] ?? 'Pending';
    $total_estimated_cost     = (float) ($_POST['total_estimated_cost'] ?? 0);
    $total_actual_cost        = (float) ($_POST['total_actual_cost'] ?? 0);

    // Validation
    if (empty($booking_reference)) $errors[] = 'Booking reference is required.';
    if (empty($customer_id)) $errors[] = 'Customer ID is required.';
    if (empty($workshop_id)) $errors[] = 'Workshop ID is required.';
    if (empty($service_id)) $errors[] = 'Service ID is required.';
    if (empty($vehicle_name)) $errors[] = 'Vehicle name is required.';
    if (empty($vehicle_model)) $errors[] = 'Vehicle model is required.';
    if (empty($registration_number)) $errors[] = 'Registration number is required.';
    if (empty($booking_date)) $errors[] = 'Booking date is required.';
    if (empty($booking_time)) $errors[] = 'Booking time is required.';

    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $sql = "INSERT INTO service_bookings (
                    booking_reference, customer_id, vehicle_id, workshop_id, service_id,
                    assigned_employee_id, vehicle_name, vehicle_model, registration_number,
                    booking_date, booking_time, vehicle_mileage, customer_complaint,
                    estimated_completion_date, actual_completion_date, booking_status,
                    total_estimated_cost, total_actual_cost
                ) VALUES (
                    :booking_reference, :customer_id, :vehicle_id, :workshop_id, :service_id,
                    :assigned_employee_id, :vehicle_name, :vehicle_model, :registration_number,
                    :booking_date, :booking_time, :vehicle_mileage, :customer_complaint,
                    :estimated_completion_date, :actual_completion_date, :booking_status,
                    :total_estimated_cost, :total_actual_cost
                )";
            } else {
                $sql = "UPDATE service_bookings SET
                    booking_reference = :booking_reference,
                    customer_id = :customer_id,
                    vehicle_id = :vehicle_id,
                    workshop_id = :workshop_id,
                    service_id = :service_id,
                    assigned_employee_id = :assigned_employee_id,
                    vehicle_name = :vehicle_name,
                    vehicle_model = :vehicle_model,
                    registration_number = :registration_number,
                    booking_date = :booking_date,
                    booking_time = :booking_time,
                    vehicle_mileage = :vehicle_mileage,
                    customer_complaint = :customer_complaint,
                    estimated_completion_date = :estimated_completion_date,
                    actual_completion_date = :actual_completion_date,
                    booking_status = :booking_status,
                    total_estimated_cost = :total_estimated_cost,
                    total_actual_cost = :total_actual_cost
                WHERE booking_id = :booking_id";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':booking_reference'         => $booking_reference,
                ':customer_id'               => $customer_id,
                ':vehicle_id'                => $vehicle_id ?: null,
                ':workshop_id'               => $workshop_id,
                ':service_id'                => $service_id,
                ':assigned_employee_id'      => $assigned_employee_id ?: null,
                ':vehicle_name'              => $vehicle_name,
                ':vehicle_model'             => $vehicle_model,
                ':registration_number'       => $registration_number,
                ':booking_date'              => $booking_date,
                ':booking_time'              => $booking_time,
                ':vehicle_mileage'           => $vehicle_mileage ?: null,
                ':customer_complaint'        => $customer_complaint ?: null,
                ':estimated_completion_date' => $estimated_completion_date ?: null,
                ':actual_completion_date'    => $actual_completion_date ?: null,
                ':booking_status'            => $booking_status,
                ':total_estimated_cost'      => $total_estimated_cost,
                ':total_actual_cost'         => $total_actual_cost,
                ':booking_id'                => $booking_id
            ]);

            $_SESSION['success'] = "Booking " . ($action === 'add' ? 'created' : 'updated') . " successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    $booking_id = (int) $_GET['id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE su FROM service_updates su
                               INNER JOIN job_cards jc ON su.job_card_id = jc.job_card_id
                               WHERE jc.booking_id = ?");
        $stmt->execute([$booking_id]);
        $stmt = $pdo->prepare("DELETE FROM job_cards WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $stmt = $pdo->prepare("DELETE FROM service_bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $pdo->commit();
        $_SESSION['success'] = "Booking #$booking_id deleted successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Delete failed: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// ---------- Fetch Data for Statistics ----------
$totalBookings = $pdo->query("SELECT COUNT(*) FROM service_bookings")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM service_bookings WHERE booking_status = 'Pending'")->fetchColumn();
$inProgressBookings = $pdo->query("SELECT COUNT(*) FROM service_bookings WHERE booking_status = 'In Progress'")->fetchColumn();
$completedBookings = $pdo->query("SELECT COUNT(*) FROM service_bookings WHERE booking_status = 'Completed'")->fetchColumn();

// ---------- Fetch dropdown data for booking modal ----------
$customers = $pdo->query("
    SELECT c.customer_id, p.first_name, p.last_name
    FROM customers c
    JOIN persons p ON c.person_id = p.person_id
    ORDER BY p.first_name
")->fetchAll(PDO::FETCH_ASSOC);

$workshops = $pdo->query("
    SELECT workshop_id, workshop_name
    FROM workshops
    ORDER BY workshop_name
")->fetchAll(PDO::FETCH_ASSOC);

$services = $pdo->query("
    SELECT service_id, service_name
    FROM workshop_services
    ORDER BY service_name
")->fetchAll(PDO::FETCH_ASSOC);

$employees = $pdo->query("
    SELECT e.employee_id, p.first_name, p.last_name, e.position
    FROM employees e
    JOIN persons p ON e.person_id = p.person_id
    WHERE e.employee_status = 'Active'
    ORDER BY p.first_name
")->fetchAll(PDO::FETCH_ASSOC);

$vehicles = $pdo->query("
    SELECT vehicle_id, make, model, registration_number
    FROM vehicles
    ORDER BY make
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Booking Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
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
        .detail-section {
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        .job-card-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .update-item {
            background: #fff;
            border-left: 3px solid #0d6efd;
            padding: 5px 10px;
            margin-bottom: 5px;
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
            .card-header .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            .card-header .d-flex input,
            .card-header .d-flex select {
                width: 100% !important;
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
            .job-card-item .btn {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
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
            .job-card-item {
                padding: 8px;
            }
            .job-card-item .btn {
                font-size: 0.65rem;
                padding: 0.15rem 0.4rem;
            }
            .detail-section h4 {
                font-size: 1.1rem;
            }
            #jobCardsContainer .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
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
                <h2><i class="fas fa-calendar-check text-primary me-2"></i>Service Bookings</h2>
                <p class="text-muted">Manage all service bookings, job cards, and updates.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#bookingModal" onclick="openAddBookingModal()">
                <i class="fas fa-plus me-1"></i> New Booking
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
            <div class="col-md-3"><div class="card stat-card h-100 shadow-sm"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Total Bookings</h6><h2 class="fw-bold"><?= $totalBookings ?></h2></div><div class="stat-icon text-primary"><i class="fas fa-calendar-alt"></i></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card h-100 shadow-sm" style="border-left-color:#ffc107;"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Pending</h6><h2 class="fw-bold text-warning"><?= $pendingBookings ?></h2></div><div class="stat-icon text-warning"><i class="fas fa-clock"></i></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card h-100 shadow-sm" style="border-left-color:#0dcaf0;"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">In Progress</h6><h2 class="fw-bold text-info"><?= $inProgressBookings ?></h2></div><div class="stat-icon text-info"><i class="fas fa-spinner"></i></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Completed</h6><h2 class="fw-bold text-success"><?= $completedBookings ?></h2></div><div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div></div></div></div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Bookings</h5>
                <div class="d-flex flex-wrap gap-2">
                    <input type="text" id="searchBooking" class="form-control form-control-sm" style="width:200px;" placeholder="Search...">
                    <select id="statusFilter" class="form-select form-select-sm" style="width:150px;">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="bookingsTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Reference</th>
                                <th>Customer ID</th>
                                <th>Vehicle</th>
                                <th>Registration</th>
                                <th>Workshop</th>
                                <th>Service</th>
                                <th>Employee</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th>Est. Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Booking Detail Area (Job Cards & Updates) -->
        <div id="bookingDetail" class="detail-section" style="display:none;">
            <h4>Job Cards for Booking <span id="detailBookingRef"></span></h4>
            <div id="jobCardsContainer"></div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- ========== BOOKING MODAL (Add / Edit) ========== -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingModalTitle">Add Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="bookingForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="booking_id" id="bookingId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left column: Basic Info -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Booking Details</h6>
                            <div class="mb-3">
                                <label class="form-label required">Reference</label>
                                <input type="text" name="booking_reference" id="booking_reference" class="form-control" required readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['customer_id'] ?>">
                                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (ID: ' . $c['customer_id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Workshop</label>
                                <select name="workshop_id" id="workshop_id" class="form-select" required>
                                    <option value="">Select Workshop</option>
                                    <?php foreach ($workshops as $w): ?>
                                        <option value="<?= $w['workshop_id'] ?>"><?= htmlspecialchars($w['workshop_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Service</label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= $s['service_id'] ?>"><?= htmlspecialchars($s['service_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assigned Employee</label>
                                <select name="assigned_employee_id" id="assigned_employee_id" class="form-select">
                                    <option value="">None</option>
                                    <?php foreach ($employees as $e): ?>
                                        <option value="<?= $e['employee_id'] ?>">
                                            <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['position'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Vehicle Name</label>
                                <input type="text" name="vehicle_name" id="vehicle_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Vehicle Model</label>
                                <input type="text" name="vehicle_model" id="vehicle_model" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Registration Number</label>
                                <input type="text" name="registration_number" id="registration_number" class="form-control" required>
                            </div>
                        </div>
                        <!-- Right column: Dates, Costs, Status -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Schedule & Costs</h6>
                            <div class="mb-3">
                                <label class="form-label required">Booking Date</label>
                                <input type="date" name="booking_date" id="booking_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Booking Time</label>
                                <input type="time" name="booking_time" id="booking_time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vehicle Mileage</label>
                                <input type="number" name="vehicle_mileage" id="vehicle_mileage" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Complaint</label>
                                <textarea name="customer_complaint" id="customer_complaint" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estimated Completion Date</label>
                                <input type="date" name="estimated_completion_date" id="estimated_completion_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Actual Completion Date</label>
                                <input type="date" name="actual_completion_date" id="actual_completion_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="booking_status" id="booking_status" class="form-select">
                                    <option value="Pending">Pending</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Estimated Cost</label>
                                <input type="number" step="0.01" name="total_estimated_cost" id="total_estimated_cost" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Actual Cost</label>
                                <input type="number" step="0.01" name="total_actual_cost" id="total_actual_cost" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBookingBtn">Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== JOB CARD MODAL ========== -->
<div class="modal fade" id="jobCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobCardModalTitle">Add Job Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="jobCardForm">
                <input type="hidden" name="job_card_id" id="job_card_id" value="0">
                <input type="hidden" name="booking_id" id="jc_booking_id" value="0">
                <input type="hidden" name="action" value="save_job_card">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Job Card Number</label>
                                <input type="text" name="job_card_number" id="job_card_number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Vehicle</label>
                                <select name="vehicle_id" id="jc_vehicle_id" class="form-select">
                                    <option value="">Select Vehicle</option>
                                    <?php foreach ($vehicles as $v): ?>
                                        <option value="<?= $v['vehicle_id'] ?>">
                                            <?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['registration_number'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" id="jc_customer_id" class="form-select">
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['customer_id'] ?>">
                                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name'] . ' (ID: ' . $c['customer_id'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mechanic</label>
                                <select name="mechanic_employee_id" id="mechanic_employee_id" class="form-select">
                                    <option value="">Select Mechanic</option>
                                    <?php foreach ($employees as $e): ?>
                                        <option value="<?= $e['employee_id'] ?>">
                                            <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['position'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Service Advisor</label>
                                <select name="service_advisor_employee_id" id="service_advisor_employee_id" class="form-select">
                                    <option value="">Select Advisor</option>
                                    <?php foreach ($employees as $e): ?>
                                        <option value="<?= $e['employee_id'] ?>">
                                            <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['position'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Odometer Reading</label>
                                <input type="number" name="odometer_reading" id="odometer_reading" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fuel Level</label>
                                <select name="fuel_level" id="fuel_level" class="form-select">
                                    <option value="Empty">Empty</option>
                                    <option value="1/4">1/4</option>
                                    <option value="1/2" selected>1/2</option>
                                    <option value="3/4">3/4</option>
                                    <option value="Full">Full</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Vehicle Condition</label>
                                <textarea name="vehicle_condition" id="vehicle_condition" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Customer Complaint</label>
                                <textarea name="customer_complaint" id="jc_customer_complaint" rows="2" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Diagnosis</label>
                                <textarea name="diagnosis" id="diagnosis" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Work Performed</label>
                                <textarea name="work_performed" id="work_performed" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Labour Hours</label>
                                <input type="number" step="0.01" name="labour_hours" id="labour_hours" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Parts Cost</label>
                                <input type="number" step="0.01" name="parts_cost" id="parts_cost" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Labour Cost</label>
                                <input type="number" step="0.01" name="labour_cost" id="labour_cost" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total Cost</label>
                                <input type="number" step="0.01" name="total_cost" id="total_cost" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="Low">Low</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="jc_status" class="form-select">
                                    <option value="Open">Open</option>
                                    <option value="Diagnosing">Diagnosing</option>
                                    <option value="Waiting for Parts">Waiting for Parts</option>
                                    <option value="Repairing">Repairing</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Opened At</label>
                                <input type="datetime-local" name="opened_at" id="opened_at" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Completed At</label>
                                <input type="datetime-local" name="completed_at" id="completed_at" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Signature (path)</label>
                                <input type="text" name="customer_signature" id="customer_signature" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Advisor Notes</label>
                                <textarea name="advisor_notes" id="advisor_notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mechanic Notes</label>
                                <textarea name="mechanic_notes" id="mechanic_notes" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveJobCardBtn">Save Job Card</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== SERVICE UPDATE MODAL ========== -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Service Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateForm">
                    <input type="hidden" name="job_card_id" id="update_job_card_id">
                    <div class="mb-3">
                        <label class="form-label required">Updated By Employee ID</label>
                        <input type="number" name="updated_by_employee_id" id="updated_by_employee_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Update Status</label>
                        <select name="update_status" id="update_status" class="form-select" required>
                            <option value="Vehicle Received">Vehicle Received</option>
                            <option value="Inspection">Inspection</option>
                            <option value="Diagnosis">Diagnosis</option>
                            <option value="Waiting for Approval">Waiting for Approval</option>
                            <option value="Repair in Progress">Repair in Progress</option>
                            <option value="Quality Control">Quality Control</option>
                            <option value="Ready for Delivery">Ready for Delivery</option>
                            <option value="Delivered">Delivered</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea name="comments" id="update_comments" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notify Customer</label>
                        <select name="notify_customer" id="notify_customer" class="form-select">
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUpdateBtn">Save Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    loadBookings();

    // Search and filter events
    $('#searchBooking').on('keyup change', loadBookings);
    $('#statusFilter').on('change', loadBookings);

    // Job Card save
    $('#saveJobCardBtn').click(saveJobCard);

    // Update save
    $('#saveUpdateBtn').click(saveUpdate);
});

// ---------- Load Bookings via AJAX ----------
function loadBookings() {
    const search = $('#searchBooking').val();
    const status = $('#statusFilter').val();
    $.ajax({
        url: 'get_service_bookings.php',
        method: 'GET',
        data: { search: search, status: status },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }
            renderBookings(data);
        },
        error: function() {
            alert('Error loading bookings.');
        }
    });
}

function renderBookings(bookings) {
    let html = '';
    bookings.forEach(b => {
        const statusBadge = getStatusBadge(b.booking_status);
        html += `<tr>
            <td>${b.booking_id}</td>
            <td><strong>${b.booking_reference}</strong></td>
            <td>${b.customer_id}</td>
            <td>${b.vehicle_name} (${b.vehicle_model})</td>
            <td>${b.registration_number}</td>
            <td>${b.workshop_id}</td>
            <td>${b.service_id}</td>
            <td>${b.assigned_employee_id || '-'}</td>
            <td>${b.booking_date} ${b.booking_time}</td>
            <td>${statusBadge}</td>
            <td>${parseFloat(b.total_estimated_cost).toFixed(2)}</td>
            <td>
                <button class="btn btn-sm btn-outline-info action-btn" onclick="viewBooking(${b.booking_id})" title="View Job Cards"><i class="fas fa-folder-open"></i></button>
                <button class="btn btn-sm btn-outline-warning action-btn" onclick="editBooking(${b.booking_id})" title="Edit"><i class="fas fa-edit"></i></button>
                <a href="?action=delete&id=${b.booking_id}" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this booking and all related job cards?')" title="Delete"><i class="fas fa-trash"></i></a>
            </td>
        </tr>`;
    });
    $('#bookingsBody').html(html);
}

function getStatusBadge(status) {
    const map = {
        'Pending': 'secondary',
        'Confirmed': 'primary',
        'In Progress': 'warning',
        'Completed': 'success',
        'Cancelled': 'danger'
    };
    const cls = map[status] || 'secondary';
    return `<span class="badge bg-${cls} badge-status">${status}</span>`;
}

// ---------- Booking Modal ----------
function openAddBookingModal() {
    $('#bookingModalTitle').text('Add Booking');
    $('#formAction').val('add');
    $('#bookingId').val(0);
    $('#bookingForm')[0].reset();
    $('#customer_id').val('');
    $('#workshop_id').val('');
    $('#service_id').val('');
    $('#assigned_employee_id').val('');
    const ref = 'BK' + Date.now().toString().slice(-6);
    $('#booking_reference').val(ref);
    $('#saveBookingBtn').text('Save Booking');
    $('#bookingModal').modal('show');
}

function editBooking(id) {
    $.ajax({
        url: 'get_service_bookings.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            if (data.error || data.length === 0) {
                alert('Booking not found.');
                return;
            }
            const b = data[0];
            $('#bookingModalTitle').text('Edit Booking');
            $('#formAction').val('edit');
            $('#bookingId').val(b.booking_id);
            $('#booking_reference').val(b.booking_reference);
            $('#customer_id').val(b.customer_id);
            $('#workshop_id').val(b.workshop_id);
            $('#service_id').val(b.service_id);
            $('#assigned_employee_id').val(b.assigned_employee_id);
            $('#vehicle_name').val(b.vehicle_name);
            $('#vehicle_model').val(b.vehicle_model);
            $('#registration_number').val(b.registration_number);
            $('#booking_date').val(b.booking_date);
            $('#booking_time').val(b.booking_time);
            $('#vehicle_mileage').val(b.vehicle_mileage);
            $('#customer_complaint').val(b.customer_complaint);
            $('#estimated_completion_date').val(b.estimated_completion_date);
            $('#actual_completion_date').val(b.actual_completion_date);
            $('#booking_status').val(b.booking_status);
            $('#total_estimated_cost').val(b.total_estimated_cost);
            $('#total_actual_cost').val(b.total_actual_cost);
            $('#saveBookingBtn').text('Update Booking');
            $('#bookingModal').modal('show');
        },
        error: function() {
            alert('Error loading booking data.');
        }
    });
}

// ---------- View Booking (load Job Cards) ----------
function viewBooking(bookingId) {
    $('#bookingDetail').show();
    $('#detailBookingRef').text(bookingId);
    $('#jobCardsContainer').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading job cards...</div>');

    $.ajax({
        url: 'get_job_cards.php',
        method: 'GET',
        data: { booking_id: bookingId },
        dataType: 'json',
        success: function(jobCards) {
            let html = `<div class="mb-3"><button class="btn btn-primary btn-sm" onclick="openJobCardModal(${bookingId}, 0)"><i class="fas fa-plus"></i> Add Job Card</button></div>`;
            if (jobCards.length === 0) {
                html += '<p class="text-muted">No job cards for this booking.</p>';
            } else {
                html += `<div class="row">`;
                jobCards.forEach(jc => {
                    html += `<div class="col-md-6 job-card-item">
                        <h6><strong>${jc.job_card_number}</strong> 
                            <span class="badge bg-${jc.status === 'Completed' ? 'success' : 'secondary'}">${jc.status}</span>
                        </h6>
                        <p><small>Mechanic: ${jc.mechanic_employee_id || '-'} | Advisor: ${jc.service_advisor_employee_id || '-'}</small></p>
                        <p><strong>Cost:</strong> ${parseFloat(jc.total_cost).toFixed(2)}</p>
                        <div>
                            <button class="btn btn-sm btn-outline-info" onclick="viewUpdates(${jc.job_card_id})">Updates</button>
                            <button class="btn btn-sm btn-outline-warning" onclick="openJobCardModal(${jc.booking_id}, ${jc.job_card_id})">Edit</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteJobCard(${jc.job_card_id})">Delete</button>
                        </div>
                        <div id="updates_${jc.job_card_id}" style="margin-top:10px;"></div>
                    </div>`;
                });
                html += '</div>';
            }
            $('#jobCardsContainer').html(html);
        },
        error: function() {
            $('#jobCardsContainer').html('<div class="alert alert-danger">Failed to load job cards.</div>');
        }
    });
}

// ---------- Job Card CRUD ----------
function openJobCardModal(bookingId, jobCardId) {
    $('#jc_booking_id').val(bookingId);
    $('#jobCardForm')[0].reset();
    $('#job_card_id').val(0);
    $('#jobCardModalTitle').text('Add Job Card');
    $('#saveJobCardBtn').text('Save Job Card');

    if (jobCardId > 0) {
        $.ajax({
            url: 'get_job_cards.php',
            method: 'GET',
            data: { job_card_id: jobCardId },
            dataType: 'json',
            success: function(data) {
                if (data.error || data.length === 0) {
                    alert('Job card not found.');
                    return;
                }
                const jc = data[0];
                $('#job_card_id').val(jc.job_card_id);
                $('#job_card_number').val(jc.job_card_number);
                $('#jc_vehicle_id').val(jc.vehicle_id);
                $('#jc_customer_id').val(jc.customer_id);
                $('#mechanic_employee_id').val(jc.mechanic_employee_id);
                $('#service_advisor_employee_id').val(jc.service_advisor_employee_id);
                $('#odometer_reading').val(jc.odometer_reading);
                $('#fuel_level').val(jc.fuel_level);
                $('#vehicle_condition').val(jc.vehicle_condition);
                $('#jc_customer_complaint').val(jc.customer_complaint);
                $('#diagnosis').val(jc.diagnosis);
                $('#work_performed').val(jc.work_performed);
                $('#labour_hours').val(jc.labour_hours);
                $('#parts_cost').val(jc.parts_cost);
                $('#labour_cost').val(jc.labour_cost);
                $('#total_cost').val(jc.total_cost);
                $('#priority').val(jc.priority);
                $('#jc_status').val(jc.status);
                $('#opened_at').val(jc.opened_at ? jc.opened_at.replace(' ', 'T') : '');
                $('#completed_at').val(jc.completed_at ? jc.completed_at.replace(' ', 'T') : '');
                $('#customer_signature').val(jc.customer_signature);
                $('#advisor_notes').val(jc.advisor_notes);
                $('#mechanic_notes').val(jc.mechanic_notes);
                $('#jobCardModalTitle').text('Edit Job Card');
                $('#saveJobCardBtn').text('Update Job Card');
                $('#jobCardModal').modal('show');
            },
            error: function() {
                alert('Error loading job card data.');
            }
        });
    } else {
        const now = new Date().toISOString().slice(0,16);
        $('#opened_at').val(now);
        $('#jobCardModal').modal('show');
    }
}

function saveJobCard() {
    const formData = $('#jobCardForm').serializeArray();
    const data = {};
    formData.forEach(item => { data[item.name] = item.value; });

    $.ajax({
        url: 'save_job_card.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#jobCardModal').modal('hide');
                const bookingId = $('#jc_booking_id').val();
                viewBooking(bookingId);
            } else {
                alert(response.error || 'Failed to save job card.');
            }
        },
        error: function() {
            alert('Error saving job card.');
        }
    });
}

function deleteJobCard(jobCardId) {
    if (!confirm('Delete this job card and its updates?')) return;
    $.ajax({
        url: 'delete_job_card.php',
        method: 'POST',
        data: { job_card_id: jobCardId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const bookingId = $('#detailBookingRef').text();
                viewBooking(bookingId);
            } else {
                alert(response.error || 'Delete failed.');
            }
        },
        error: function() {
            alert('Error deleting job card.');
        }
    });
}

// ---------- Service Updates ----------
function viewUpdates(jobCardId) {
    const container = $('#updates_' + jobCardId);
    container.html('<div class="text-muted small">Loading updates...</div>');

    $.ajax({
        url: 'get_service_updates.php',
        method: 'GET',
        data: { job_card_id: jobCardId },
        dataType: 'json',
        success: function(updates) {
            let html = `<div class="mt-2">
                <button class="btn btn-sm btn-primary" onclick="openUpdateModal(${jobCardId})"><i class="fas fa-plus"></i> Add Update</button>
                <div class="mt-1">`;
            if (updates.length === 0) {
                html += '<p class="text-muted small">No updates yet.</p>';
            } else {
                updates.forEach(u => {
                    html += `<div class="update-item">
                        <strong>${u.update_status}</strong> 
                        <span class="text-muted small">by ${u.updated_by_employee_id}</span>
                        <p class="mb-0 small">${u.comments || ''}</p>
                        <span class="text-muted small">${u.created_at}</span>
                        ${u.notify_customer === 'Yes' ? '<span class="badge bg-info">Notified</span>' : ''}
                    </div>`;
                });
            }
            html += '</div></div>';
            container.html(html);
        },
        error: function() {
            container.html('<div class="alert alert-danger small">Failed to load updates.</div>');
        }
    });
}

function openUpdateModal(jobCardId) {
    $('#update_job_card_id').val(jobCardId);
    $('#updateForm')[0].reset();
    $('#updateModal').modal('show');
}

function saveUpdate() {
    const formData = $('#updateForm').serializeArray();
    const data = {};
    formData.forEach(item => { data[item.name] = item.value; });

    $.ajax({
        url: 'save_service_update.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#updateModal').modal('hide');
                const jobCardId = data.job_card_id;
                viewUpdates(jobCardId);
            } else {
                alert(response.error || 'Failed to save update.');
            }
        },
        error: function() {
            alert('Error saving update.');
        }
    });
}
</script>
</body>
</html>