<?php
/**
 * Employee Dashboard
 * For logged‑in employees to view their own info, leave, and documents.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only employees can access this page
if ($_SESSION['role'] !== 'Employee') {
    header('Location: login.php');
    exit;
}

$personId = (int) $_SESSION['person_id'];

$pageTitle = "Employee Dashboard";

// Fetch employee details
$stmt = $pdo->prepare("
    SELECT
        e.employee_id,
        e.employee_number,
        e.department,
        e.position,
        e.employment_type,
        e.hire_date,
        e.salary,
        e.employee_status,
        p.first_name,
        p.middle_name,
        p.last_name,
        p.gender,
        p.date_of_birth,
        p.phone,
        p.email,
        p.id_number,
        p.passport_number,
        p.country,
        p.province,
        p.city,
        p.address,
        p.postal_code,
        p.profile_photo
    FROM employees e
    INNER JOIN persons p ON e.person_id = p.person_id
    WHERE p.person_id = ?
");
$stmt->execute([$personId]);
$employee = $stmt->fetch();

if (!$employee) {
    die('Employee record not found. Please contact HR.');
}

$employeeId = $employee['employee_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle leave request submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_leave') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid security token.';
        $messageType = 'danger';
    } else {
        $leaveType = trim($_POST['leave_type'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (empty($leaveType) || empty($startDate) || empty($endDate)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'danger';
        } elseif (strtotime($startDate) > strtotime($endDate)) {
            $message = 'End date must be after start date.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO leave_requests
                    (employee_id, leave_type, start_date, end_date, reason, status)
                    VALUES (?, ?, ?, ?, ?, 'Pending')
                ");
                $stmt->execute([$employeeId, $leaveType, $startDate, $endDate, $reason]);
                $message = 'Leave request submitted successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Fetch leave balances
$balances = $pdo->prepare("
    SELECT leave_type, year, total_days, used_days
    FROM leave_balances
    WHERE employee_id = ?
    ORDER BY year DESC, leave_type
");
$balances->execute([$employeeId]);

// Fetch leave requests
$requests = $pdo->prepare("
    SELECT request_id, leave_type, start_date, end_date, reason, status, created_at
    FROM leave_requests
    WHERE employee_id = ?
    ORDER BY created_at DESC
");
$requests->execute([$employeeId]);

// Fetch documents
$documents = $pdo->prepare("
    SELECT document_id, document_name, document_type, file_path, expiry_date, uploaded_at
    FROM employee_documents
    WHERE employee_id = ?
    ORDER BY uploaded_at DESC
");
$documents->execute([$employeeId]);

// Profile photo
$photo = !empty($employee['profile_photo']) ? '/' . $employee['profile_photo'] : '/assets/uploads/profiles/default.png';

// Helper: total leave days remaining (sum over all types)
$balances->execute([$employeeId]); // re-run to get fresh data for stats
$totalRemaining = 0;
while ($bal = $balances->fetch()) {
    $totalRemaining += ($bal['total_days'] - $bal['used_days']);
}
$balances->execute([$employeeId]); // reset again for display
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
        /* ── Global Reset ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        /* ── Dashboard Container ── */
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }

        /* ── Welcome Header ── */
        .welcome-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 20px;
            padding: 30px 35px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(30, 58, 138, 0.35);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .welcome-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }
        .welcome-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .welcome-header p {
            opacity: 0.85;
            margin-bottom: 0;
            font-weight: 400;
        }

        /* ── Stats Cards ── */
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
        /* Accents */
        .stat-card.employee-number { border-left: 6px solid #4f46e5; }
        .stat-card.department { border-left: 6px solid #10b981; }
        .stat-card.position { border-left: 6px solid #f59e0b; }
        .stat-card.leave-remaining { border-left: 6px solid #ef4444; }

        /* ── Card Headers ── */
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            background: #ffffff;
            transition: box-shadow 0.3s;
            height: 100%;
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
            letter-spacing: -0.2px;
        }
        .card-custom .card-body {
            padding: 20px 25px;
        }

        /* ── Tables ── */
        .table-dashboard {
            margin-bottom: 0;
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
            font-weight: 500;
        }
        .table-dashboard tr:last-child td {
            border-bottom: none;
        }
        .table-dashboard tr:hover td {
            background-color: #f9fafb;
        }

        /* ── Tabs ── */
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #3b82f6;
        }

        /* ── Responsive fine‑tune ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .welcome-header {
                padding: 24px 28px;
            }
            .welcome-header h2 {
                font-size: 1.6rem;
            }
            .stat-card .stat-number {
                font-size: 2.2rem;
            }
            .card-custom .card-header {
                padding: 14px 18px;
                font-size: 1rem;
            }
            .card-custom .card-body {
                padding: 16px 18px;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .welcome-header {
                padding: 20px;
                border-radius: 16px;
            }
            .welcome-header h2 {
                font-size: 1.4rem;
            }
            .welcome-header p {
                font-size: 0.95rem;
            }
            .stat-card {
                padding: 16px 16px 16px 20px;
            }
            .stat-card .stat-number {
                font-size: 1.8rem;
            }
            .stat-card .stat-icon {
                font-size: 2rem;
                right: 12px;
                bottom: 12px;
            }
            .stat-card .stat-label {
                font-size: 0.8rem;
            }
            .profile-photo {
                margin-bottom: 1rem;
            }
            .table-sm th, .table-sm td {
                font-size: 0.8rem;
                padding: 0.4rem 0.2rem;
            }
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-item {
                white-space: nowrap;
            }
            .nav-tabs .nav-link {
                font-size: 0.85rem;
                padding: 0.4rem 0.6rem;
            }
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .welcome-header {
                padding: 16px 18px;
                border-radius: 14px;
            }
            .welcome-header h2 {
                font-size: 1.2rem;
            }
            .welcome-header p {
                font-size: 0.85rem;
            }
            .stat-card {
                padding: 12px 12px 12px 16px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            .stat-card .stat-label {
                font-size: 0.7rem;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem;
                right: 10px;
                bottom: 10px;
            }
            .card-custom .card-header {
                font-size: 0.95rem;
                padding: 12px 14px;
            }
            .card-custom .card-body {
                padding: 12px 14px;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.7rem;
                padding: 0.3rem 0.15rem;
            }
            .table-sm th, .table-sm td {
                font-size: 0.7rem;
                padding: 0.25rem 0.1rem;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .profile-photo img {
                max-width: 100px !important;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 0.4rem 0.6rem;
            }
            .btn-primary, .btn-outline-primary {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
        }
    </style>
</head>

<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="dashboard-wrapper">

        <?php include '../../includes/navbar.php'; ?>

        <div class="container-fluid mt-4 px-4">

            <!-- Welcome Header -->
            <div class="welcome-header">
                <div>
                    <h2>
                        <i class="fas fa-user-tie me-2"></i>
                        Welcome, <?= htmlspecialchars($employee['first_name']) ?>
                    </h2>
                    <p>
                        <i class="fas fa-briefcase me-1"></i>
                        Employee Dashboard — your work hub
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card employee-number">
                        <div class="stat-label">Employee Number</div>
                        <div class="stat-number"><?= htmlspecialchars($employee['employee_number']) ?></div>
                        <div class="stat-icon"><i class="fas fa-id-badge"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card department">
                        <div class="stat-label">Department</div>
                        <div class="stat-number" style="font-size:1.8rem;"><?= htmlspecialchars($employee['department']) ?></div>
                        <div class="stat-icon"><i class="fas fa-building"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card position">
                        <div class="stat-label">Position</div>
                        <div class="stat-number" style="font-size:1.8rem;"><?= htmlspecialchars($employee['position']) ?></div>
                        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card leave-remaining">
                        <div class="stat-label">Leave Days Remaining</div>
                        <div class="stat-number"><?= $totalRemaining ?></div>
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Tabs -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-user-cog me-2 text-primary"></i>
                            My Details
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab">Profile</a></li>
                                <li class="nav-item"><a class="nav-link" id="leave-tab" data-bs-toggle="tab" href="#leave" role="tab">Leave</a></li>
                                <li class="nav-item"><a class="nav-link" id="docs-tab" data-bs-toggle="tab" href="#docs" role="tab">Documents</a></li>
                            </ul>
                            <div class="tab-content pt-3" id="myTabContent">
                                <!-- Profile Tab -->
                                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-3 text-center profile-photo">
                                            <img src="<?= htmlspecialchars($photo) ?>" class="img-fluid rounded-circle" style="max-width:150px; border:4px solid #e5e7eb;">
                                        </div>
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-sm table-borderless">
                                                        <tr><th>Full Name</th><td><?= htmlspecialchars($employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . $employee['last_name']) ?></td></tr>
                                                        <tr><th>Employee No.</th><td><?= htmlspecialchars($employee['employee_number']) ?></td></tr>
                                                        <tr><th>Department</th><td><?= htmlspecialchars($employee['department']) ?></td></tr>
                                                        <tr><th>Position</th><td><?= htmlspecialchars($employee['position']) ?></td></tr>
                                                        <tr><th>Employment Type</th><td><?= htmlspecialchars($employee['employment_type']) ?></td></tr>
                                                        <tr><th>Hire Date</th><td><?= htmlspecialchars($employee['hire_date']) ?></td></tr>
                                                        <tr><th>Salary</th><td>R <?= number_format($employee['salary'] ?? 0, 2) ?></td></tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-sm table-borderless">
                                                        <tr><th>Gender</th><td><?= htmlspecialchars($employee['gender']) ?></td></tr>
                                                        <tr><th>Date of Birth</th><td><?= htmlspecialchars($employee['date_of_birth'] ?? 'N/A') ?></td></tr>
                                                        <tr><th>Phone</th><td><?= htmlspecialchars($employee['phone']) ?></td></tr>
                                                        <tr><th>Email</th><td><?= htmlspecialchars($employee['email']) ?></td></tr>
                                                        <tr><th>ID Number</th><td><?= htmlspecialchars($employee['id_number'] ?? 'N/A') ?></td></tr>
                                                        <tr><th>Passport</th><td><?= htmlspecialchars($employee['passport_number'] ?? 'N/A') ?></td></tr>
                                                        <tr><th>Address</th><td><?= htmlspecialchars($employee['address'] ?? '') ?>, <?= htmlspecialchars($employee['city'] ?? '') ?>, <?= htmlspecialchars($employee['province'] ?? '') ?></td></tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Leave Tab -->
                                <div class="tab-pane fade" id="leave" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Leave Balances</h5>
                                            <?php if ($balances->rowCount() == 0): ?>
                                                <p class="text-muted">No leave balances set.</p>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead><tr><th>Type</th><th>Year</th><th>Total</th><th>Used</th><th>Remaining</th></tr></thead>
                                                        <tbody>
                                                        <?php while ($bal = $balances->fetch()):
                                                            $remaining = $bal['total_days'] - $bal['used_days'];
                                                        ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($bal['leave_type']) ?></td>
                                                                <td><?= htmlspecialchars($bal['year']) ?></td>
                                                                <td><?= htmlspecialchars($bal['total_days']) ?></td>
                                                                <td><?= htmlspecialchars($bal['used_days']) ?></td>
                                                                <td><strong><?= $remaining ?></strong></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Request Leave</h5>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="request_leave">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <div class="mb-2">
                                                    <label class="form-label">Leave Type</label>
                                                    <select name="leave_type" class="form-select" required>
                                                        <option value="">Select...</option>
                                                        <option value="Annual">Annual</option>
                                                        <option value="Sick">Sick</option>
                                                        <option value="Family Responsibility">Family Responsibility</option>
                                                        <option value="Maternity">Maternity</option>
                                                        <option value="Paternity">Paternity</option>
                                                    </select>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" name="start_date" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" name="end_date" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Reason (optional)</label>
                                                    <input type="text" name="reason" class="form-control" placeholder="Brief reason">
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                                            </form>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3">My Leave Requests</h5>
                                    <?php if ($requests->rowCount() == 0): ?>
                                        <p class="text-muted">You have not submitted any leave requests.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Reason</th><th>Submitted</th></tr></thead>
                                                <tbody>
                                                <?php while ($req = $requests->fetch()):
                                                    $statusClass = match($req['status']) {
                                                        'Approved' => 'success',
                                                        'Declined' => 'danger',
                                                        default => 'warning'
                                                    };
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($req['leave_type']) ?></td>
                                                        <td><?= htmlspecialchars($req['start_date']) ?></td>
                                                        <td><?= htmlspecialchars($req['end_date']) ?></td>
                                                        <td><span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                                                        <td><?= htmlspecialchars($req['reason'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($req['created_at']) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Documents Tab -->
                                <div class="tab-pane fade" id="docs" role="tabpanel">
                                    <h5 class="mb-3">My Documents</h5>
                                    <?php if ($documents->rowCount() == 0): ?>
                                        <p class="text-muted">No documents uploaded.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead><tr><th>Name</th><th>Type</th><th>Expiry</th><th>Uploaded</th><th>Action</th></tr></thead>
                                                <tbody>
                                                <?php while ($doc = $documents->fetch()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($doc['document_name']) ?></td>
                                                        <td><?= htmlspecialchars($doc['document_type']) ?></td>
                                                        <td><?= htmlspecialchars($doc['expiry_date'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($doc['uploaded_at']) ?></td>
                                                        <td>
                                                            <a href="/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- /container-fluid -->

        <?php include '../../includes/footer.php'; ?>

    </div> <!-- /dashboard-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>