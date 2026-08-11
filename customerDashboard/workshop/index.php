<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only workshop staff (Workshop_Status) can access


$pageTitle = "Job Cards";

// --- Handle search/filter parameters ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// --- Build the query ---
$sql = "
    SELECT
        jc.job_card_id,
        jc.job_card_number,
        jc.booking_id,
        jc.vehicle_id,
        jc.customer_id,
        jc.mechanic_employee_id,
        jc.service_advisor_employee_id,
        jc.odometer_reading,
        jc.fuel_level,
        jc.vehicle_condition,
        jc.customer_complaint,
        jc.diagnosis,
        jc.work_performed,
        jc.labour_hours,
        jc.parts_cost,
        jc.labour_cost,
        jc.total_cost,
        jc.priority,
        jc.status,
        jc.opened_at,
        jc.completed_at,
        jc.advisor_notes,
        jc.mechanic_notes,
        jc.created_at,
        -- Booking details
        sb.booking_reference,
        sb.vehicle_name,
        sb.vehicle_model,
        sb.registration_number,
        sb.booking_date,
        -- Customer
        CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
        -- Mechanic
        CONCAT(mp.first_name, ' ', mp.last_name) AS mechanic_name,
        -- Service
        ws.service_name,
        -- Workshop
        w.workshop_name
    FROM job_cards jc
    LEFT JOIN service_bookings sb ON jc.booking_id = sb.booking_id
    LEFT JOIN customers c ON jc.customer_id = c.customer_id
    LEFT JOIN persons p ON c.person_id = p.person_id
    LEFT JOIN employees me ON jc.mechanic_employee_id = me.employee_id
    LEFT JOIN persons mp ON me.person_id = mp.person_id
    LEFT JOIN workshop_services ws ON sb.service_id = ws.service_id
    LEFT JOIN workshops w ON sb.workshop_id = w.workshop_id
    WHERE 1=1
";

$params = [];

// --- Apply filters ---
if (!empty($search)) {
    $sql .= " AND (jc.job_card_number LIKE ? OR sb.booking_reference LIKE ? OR sb.registration_number LIKE ? OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (!empty($statusFilter)) {
    $sql .= " AND jc.status = ?";
    $params[] = $statusFilter;
}
if (!empty($priorityFilter)) {
    $sql .= " AND jc.priority = ?";
    $params[] = $priorityFilter;
}
if (!empty($dateFrom)) {
    $sql .= " AND DATE(jc.opened_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND DATE(jc.opened_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY jc.opened_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobCards = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            margin: 0;
            padding: 0;
        }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }
        .content-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
            padding: 25px 30px;
            margin-bottom: 30px;
        }
        .content-card .card-title {
            font-weight: 700;
            color: #1f2937;
            letter-spacing: -0.3px;
        }
        .filter-form .form-control,
        .filter-form .form-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            font-size: 0.95rem;
            background: #f9fafb;
        }
        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 3px rgba(17,153,142,0.2);
            background: #fff;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(17,153,142,0.35);
            color: #fff;
        }
        .btn-secondary-custom {
            background: #e5e7eb;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #1f2937;
            transition: background 0.2s;
        }
        .btn-secondary-custom:hover {
            background: #d1d5db;
        }
        @media (max-width: 992px) {
            .dashboard-wrapper { margin-left: 0; }
        }
        .table-dashboard th {
            border-top: none;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 0 10px 0;
            border-bottom: 2px solid #e5e7eb;
        }
        .table-dashboard td {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            color: #374151;
        }
        .table-dashboard tr:last-child td {
            border-bottom: none;
        }
        .table-dashboard tr:hover td {
            background-color: #f9fafb;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 50px;
        }
        .priority-badge {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 50px;
        }
        .action-btn {
            border-radius: 50px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .content-card {
                padding: 20px;
            }
            .filter-form .col-md-3,
            .filter-form .col-md-2,
            .filter-form .col-md-1 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .filter-form .col-md-1.d-flex {
                flex-direction: row;
                gap: 0.5rem;
            }
            .filter-form .col-md-1.d-flex .btn {
                width: 50%;
            }
            .table-dashboard th,
            .table-dashboard td {
                font-size: 0.8rem;
                padding: 8px 0;
            }
            .status-badge {
                font-size: 0.7rem;
                padding: 3px 10px;
            }
            .priority-badge {
                font-size: 0.65rem;
                padding: 2px 8px;
            }
            .action-btn {
                font-size: 0.7rem;
                padding: 3px 8px;
            }
        }

        @media (max-width: 768px) {
            .content-card {
                padding: 15px;
            }
            .filter-form .col-md-1.d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            .filter-form .col-md-1.d-flex .btn {
                width: 100%;
            }
            .table-dashboard th,
            .table-dashboard td {
                font-size: 0.7rem;
                padding: 6px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.65rem;
                padding: 2px 8px;
            }
            .status-badge {
                font-size: 0.65rem;
                padding: 2px 8px;
            }
            .priority-badge {
                font-size: 0.6rem;
                padding: 2px 6px;
            }
            .action-btn {
                font-size: 0.65rem;
                padding: 2px 6px;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
            .content-card .card-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .content-card {
                padding: 12px;
                border-radius: 16px;
            }
            .filter-form .form-control,
            .filter-form .form-select {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
            .btn-primary-custom,
            .btn-secondary-custom {
                font-size: 0.85rem;
                padding: 8px 16px;
            }
            .table-dashboard th,
            .table-dashboard td {
                font-size: 0.65rem;
                padding: 4px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.6rem;
                padding: 2px 6px;
            }
            .status-badge {
                font-size: 0.6rem;
                padding: 2px 6px;
            }
            .priority-badge {
                font-size: 0.55rem;
                padding: 1px 5px;
            }
            .action-btn {
                font-size: 0.6rem;
                padding: 2px 5px;
            }
            .page-header h4 {
                font-size: 1.1rem;
            }
            .content-card .card-title {
                font-size: 1rem;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
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
        <div class="d-flex align-items-center justify-content-between mb-4 page-header">
            <h4 class="fw-bold text-dark mb-0">
                <i class="fas fa-clipboard-list me-2 text-primary"></i> Job Cards
            </h4>
        </div>

        <!-- Filter / Search Form -->
        <div class="content-card">
            <form method="GET" action="" class="filter-form row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           placeholder="Card #, Booking #, Reg, Customer..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="Open" <?= $statusFilter == 'Open' ? 'selected' : '' ?>>Open</option>
                        <option value="Diagnosing" <?= $statusFilter == 'Diagnosing' ? 'selected' : '' ?>>Diagnosing</option>
                        <option value="Waiting for Parts" <?= $statusFilter == 'Waiting for Parts' ? 'selected' : '' ?>>Waiting for Parts</option>
                        <option value="Repairing" <?= $statusFilter == 'Repairing' ? 'selected' : '' ?>>Repairing</option>
                        <option value="Quality Control" <?= $statusFilter == 'Quality Control' ? 'selected' : '' ?>>Quality Control</option>
                        <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Closed" <?= $statusFilter == 'Closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">All</option>
                        <option value="Low" <?= $priorityFilter == 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Normal" <?= $priorityFilter == 'Normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="High" <?= $priorityFilter == 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Urgent" <?= $priorityFilter == 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                           value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                           value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="index.php" class="btn btn-secondary-custom w-100">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Job Cards Table -->
        <div class="content-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-list me-2"></i> Job Cards List
                <span class="badge bg-secondary ms-2"><?= count($jobCards) ?></span>
            </h5>

            <?php if (count($jobCards) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dashboard">
                        <thead>
                            <tr>
                                <th>Job Card #</th>
                                <th>Booking Ref</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Service</th>
                                <th>Workshop</th>
                                <th>Mechanic</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Opened</th>
                                <th>Total Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobCards as $jc): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-dark text-white px-3 py-2">
                                            <?= htmlspecialchars($jc['job_card_number']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($jc['booking_reference'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($jc['customer_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($jc['registration_number'])): ?>
                                            <?= htmlspecialchars($jc['registration_number']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($jc['vehicle_name'] ?? 'N/A') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($jc['service_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($jc['workshop_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($jc['mechanic_name'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <span class="priority-badge bg-<?= $jc['priority'] == 'Urgent' ? 'danger' : ($jc['priority'] == 'High' ? 'warning' : ($jc['priority'] == 'Normal' ? 'info' : 'secondary')) ?> text-white">
                                            <?= htmlspecialchars($jc['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge bg-<?= $jc['status'] == 'Completed' ? 'success' : ($jc['status'] == 'Closed' ? 'secondary' : ($jc['status'] == 'Open' ? 'primary' : ($jc['status'] == 'Diagnosing' ? 'info' : ($jc['status'] == 'Waiting for Parts' ? 'warning' : 'dark')))) ?> text-white">
                                            <?= htmlspecialchars($jc['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($jc['opened_at'])) ?></td>
                                    <td><?= number_format($jc['total_cost'], 2) ?></td>
                                    <td>
                                        <a href="view.php?id=<?= $jc['job_card_id'] ?>" class="btn btn-sm btn-outline-primary action-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $jc['job_card_id'] ?>" class="btn btn-sm btn-outline-secondary action-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No job cards found matching your criteria.
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>