<?php
/**
 * Admin – Leave Requests Management
 * View, approve/decline leave requests, and update employee status.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Restrict access to Admin, HR, or Manager roles
$allowedRoles = ['Admin', 'HR', 'Manager'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Leave Requests";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$messageType = '';

// ------------------------------------------------------------
// 1. Process Approve / Decline
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } else {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $newStatus = $_POST['action'] === 'approve' ? 'Approved' : 'Declined';

        if ($requestId) {
            try {
                $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE request_id = ?");
                $stmt->execute([$newStatus, $requestId]);
                $message = "Leave request $newStatus successfully.";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = 'Invalid request ID.';
            $messageType = 'danger';
        }
    }
}

// ------------------------------------------------------------
// 2. Process Employee Status Update
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } else {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $newStatus = $_POST['employee_status'] ?? '';

        if ($employeeId && in_array($newStatus, ['Active', 'On Leave', 'Suspended', 'Resigned'])) {
            try {
                $stmt = $pdo->prepare("UPDATE employees SET employee_status = ? WHERE employee_id = ?");
                $stmt->execute([$newStatus, $employeeId]);
                $message = "Employee status updated to $newStatus.";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = 'Invalid employee or status.';
            $messageType = 'danger';
        }
    }
}

// ------------------------------------------------------------
// 3. Fetch all leave requests with employee details
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT
        lr.*,
        e.employee_id,
        e.employee_number,
        e.department,
        e.position,
        e.employee_status,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.email,
        p.phone,
        -- leave balance (optional)
        lb.total_days,
        lb.used_days,
        lb.remaining_days,
        lb.year
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.employee_id
    JOIN persons p ON e.person_id = p.person_id
    LEFT JOIN leave_balances lb ON e.employee_id = lb.employee_id AND lr.leave_type = lb.leave_type AND YEAR(lr.start_date) = lb.year
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
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

        /* ── Page Header ── */
        .page-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 20px;
            padding: 25px 35px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(30, 58, 138, 0.35);
            margin-bottom: 30px;
        }
        .page-header h2 {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .page-header p {
            opacity: 0.85;
            margin-bottom: 0;
        }

        /* ── Cards ── */
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            background: #ffffff;
            transition: box-shadow 0.3s;
        }
        .card-custom:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.07);
        }
        .card-custom .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f2f5;
            padding: 18px 25px;
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }

        /* ── Badges & Buttons ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 2px;
        }

        /* ── Status Update Form ── */
        .status-update-form {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-update-form select {
            width: 130px;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
        }
        .status-update-form button {
            padding: 0.25rem 0.6rem;
        }

        /* ── Table ── */
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap;
        }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .page-header {
                padding: 20px 25px;
            }
            .page-header h2 {
                font-size: 1.6rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            /* Table font size reduced */
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
            .status-update-form {
                display: flex;
                flex-wrap: wrap;
                gap: 3px;
            }
            .status-update-form select {
                width: 100px;
                font-size: 0.75rem;
            }
            .status-update-form button {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 15px 18px;
                border-radius: 15px;
            }
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
            }
            .card-custom .card-header {
                padding: 12px 16px;
                font-size: 1rem;
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
            .status-update-form {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            .status-update-form select {
                width: 100%;
                font-size: 0.7rem;
            }
            .status-update-form button {
                width: 100%;
                font-size: 0.7rem;
            }
            /* Stack action buttons */
            .table td .btn-group-vertical {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }
            .table td form {
                display: inline-block;
                width: 100%;
            }
            .table td form button {
                width: 100%;
                margin-bottom: 2px;
            }
        }
    </style>
</head>

<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="dashboard-wrapper">

        <?php include '../../includes/navbar.php'; ?>

        <div class="container-fluid mt-4 px-4">

            <div class="page-header">
                <div>
                    <h2><i class="fas fa-calendar-check me-2"></i>Leave Requests</h2>
                    <p>Manage all employee leave requests and update employee status.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-list me-2 text-primary"></i>All Leave Requests
                </div>
                <div class="card-body p-0">
                    <?php if (count($requests) === 0): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>No leave requests found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Leave Type</th>
                                        <th>Dates</th>
                                        <th>Days</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Balance (T/U/R)</th>
                                        <th>Actions</th>
                                        <th>Change Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $row):
                                        $start = new DateTime($row['start_date']);
                                        $end = new DateTime($row['end_date']);
                                        $days = $start->diff($end)->days + 1;
                                        $statusClass = match($row['status']) {
                                            'Approved' => 'success',
                                            'Declined' => 'danger',
                                            default => 'warning'
                                        };
                                        $employeeStatus = $row['employee_status'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['employee_number']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['department']) ?></td>
                                            <td><?= htmlspecialchars($row['leave_type']) ?></td>
                                            <td>
                                                <?= date('d M Y', strtotime($row['start_date'])) ?><br>
                                                <small>to</small><br>
                                                <?= date('d M Y', strtotime($row['end_date'])) ?>
                                            </td>
                                            <td><?= $days ?></td>
                                            <td><?= htmlspecialchars($row['reason'] ?? '') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?> badge-status">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['total_days'] !== null): ?>
                                                    <?= $row['total_days'] ?> / <?= $row['used_days'] ?> / <?= $row['remaining_days'] ?>
                                                    <small class="text-muted d-block">(<?= $row['year'] ?>)</small>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Pending'): ?>
                                                    <form method="POST" style="display:inline-block;">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success action-btn" onclick="return confirm('Approve this leave request?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        <button type="submit" name="action" value="decline" class="btn btn-sm btn-danger action-btn" onclick="return confirm('Decline this leave request?')">
                                                            <i class="fas fa-times"></i> Decline
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Employee Status Update Form -->
                                                <form method="POST" class="status-update-form">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                    <input type="hidden" name="employee_id" value="<?= $row['employee_id'] ?>">
                                                    <select name="employee_status" class="form-select form-select-sm">
                                                        <option value="Active" <?= $employeeStatus == 'Active' ? 'selected' : '' ?>>Active</option>
                                                        <option value="On Leave" <?= $employeeStatus == 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                                        <option value="Suspended" <?= $employeeStatus == 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                                                        <option value="Resigned" <?= $employeeStatus == 'Resigned' ? 'selected' : '' ?>>Resigned</option>
                                                    </select>
                                                    <button type="submit" name="update_status" value="1" class="btn btn-sm btn-outline-secondary">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <?php include '../../includes/footer.php'; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>