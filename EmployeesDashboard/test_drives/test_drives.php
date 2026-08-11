<?php
/**
 * Employee – Test Drive Management
 * View and manage test drive requests for customers.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Employee') {
    header('Location: login.php');
    exit;
}

$pageTitle = "Test Drive Management";

// CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$messageType = '';

// ---------- Update test drive status ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_test_drive') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } else {
        $testDriveId = (int) ($_POST['test_drive_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $licenseVerified = $_POST['drivers_license_verified'] ?? '';
        $employeeNotes = trim($_POST['employee_notes'] ?? '');
        $employeeId = (int) ($_POST['employee_id'] ?? 0);

        if (!$testDriveId || !$status) {
            $message = 'Missing required fields.';
            $messageType = 'danger';
        } else {
            try {
                $sql = "UPDATE vehicle_test_drives SET 
                            status = :status,
                            drivers_license_verified = :license,
                            employee_notes = :notes,
                            employee_id = :employee_id,
                            updated_at = NOW()
                        WHERE test_drive_id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':status' => $status,
                    ':license' => $licenseVerified,
                    ':notes' => $employeeNotes,
                    ':employee_id' => $employeeId,
                    ':id' => $testDriveId
                ]);
                $message = 'Test drive updated successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// ---------- Fetch test drives ----------
$stmt = $pdo->prepare("
    SELECT
        td.*,
        v.make, v.model, v.manufacture_year, v.stock_number,
        (SELECT vi.image_path FROM vehicle_images vi WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes' LIMIT 1) AS vehicle_image,
        c.customer_number,
        p.first_name AS customer_first_name,
        p.last_name AS customer_last_name,
        p.phone AS customer_phone,
        p.email AS customer_email,
        p.address AS customer_address,
        p.city AS customer_city,
        p.province AS customer_province,
        c.driver_license,
        e.first_name AS employee_first_name,
        e.last_name AS employee_last_name
    FROM vehicle_test_drives td
    JOIN vehicles v ON td.vehicle_id = v.vehicle_id
    JOIN customers c ON td.customer_id = c.customer_id
    JOIN persons p ON c.person_id = p.person_id
    LEFT JOIN employees emp ON td.employee_id = emp.employee_id
    LEFT JOIN persons e ON emp.person_id = e.person_id
    ORDER BY td.test_drive_date DESC, td.test_drive_time DESC
");
$stmt->execute();
$testDrives = $stmt->fetchAll();

// Get employees list for dropdown
$employeesStmt = $pdo->query("
    SELECT e.employee_id, p.first_name, p.last_name
    FROM employees e
    JOIN persons p ON e.person_id = p.person_id
    ORDER BY p.first_name
");
$employees = $employeesStmt->fetchAll();

// Get current employee ID
$currentEmployeeStmt = $pdo->prepare("SELECT employee_id FROM employees WHERE person_id = ?");
$currentEmployeeStmt->execute([$_SESSION['person_id']]);
$currentEmployee = $currentEmployeeStmt->fetch();
$currentEmployeeId = $currentEmployee ? $currentEmployee['employee_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }
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
        .table-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .detail-value {
            font-weight: 500;
            color: #1f2937;
        }
        .modal-lg {
            max-width: 900px;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin: 1.5rem 0 0.75rem 0;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem 1.5rem;
        }
        .detail-grid .detail-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #f0f2f5;
            padding: 0.4rem 0;
        }
        .detail-grid .detail-item .label {
            font-weight: 500;
            color: #6b7280;
        }
        .detail-grid .detail-item .value {
            font-weight: 500;
            color: #1f2937;
        }

        /* ── RESPONSIVE TWEAKS ── */
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
            .card-custom .card-header {
                padding: 14px 18px;
                font-size: 1rem;
            }
            .card-custom .card-body {
                padding: 12px 16px;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .badge-status {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            .btn-sm {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
            }
            .table-img {
                width: 50px;
                height: 50px;
            }
            .detail-grid {
                grid-template-columns: 1fr 1fr;
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
            .modal-dialog {
                margin: 0.5rem;
            }
            .modal-content {
                border-radius: 16px;
            }
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 16px 20px;
                border-radius: 16px;
            }
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
            }
            .table th, .table td {
                font-size: 0.75rem;
                padding: 0.3rem 0.2rem;
            }
            .badge-status {
                font-size: 0.65rem;
                padding: 0.2rem 0.5rem;
            }
            .btn-sm {
                padding: 0.15rem 0.4rem;
                font-size: 0.65rem;
            }
            .table-img {
                width: 40px;
                height: 40px;
            }
            .detail-grid {
                grid-template-columns: 1fr;
                gap: 0.3rem 0;
            }
            .detail-grid .detail-item {
                padding: 0.3rem 0;
                flex-wrap: wrap;
            }
            .detail-grid .detail-item .label {
                width: 40%;
                font-size: 0.8rem;
            }
            .detail-grid .detail-item .value {
                width: 60%;
                font-size: 0.8rem;
                text-align: right;
            }
            .section-title {
                font-size: 0.95rem;
                margin: 1rem 0 0.5rem 0;
            }
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-6:last-child {
                margin-bottom: 0;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 0.4rem 0.6rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 12px 16px;
                border-radius: 14px;
            }
            .page-header h2 {
                font-size: 1.1rem;
            }
            .page-header p {
                font-size: 0.85rem;
            }
            .card-custom .card-header {
                font-size: 0.95rem;
                padding: 10px 12px;
            }
            .card-custom .card-body {
                padding: 10px 12px;
            }
            .table th, .table td {
                font-size: 0.65rem;
                padding: 0.2rem 0.15rem;
            }
            .badge-status {
                font-size: 0.55rem;
                padding: 0.15rem 0.4rem;
            }
            .btn-sm {
                padding: 0.1rem 0.3rem;
                font-size: 0.6rem;
            }
            .table-img {
                width: 30px;
                height: 30px;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .modal-footer .btn {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            .detail-grid .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .detail-grid .detail-item .label {
                width: 100%;
                font-size: 0.75rem;
            }
            .detail-grid .detail-item .value {
                width: 100%;
                font-size: 0.8rem;
                text-align: left;
            }
            .section-title {
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

            

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-list me-2 text-primary"></i>Test Drive Requests
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="testDrivesTable" class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Vehicle</th>
                                    <th>Customer</th>
                                    <th>Date / Time</th>
                                    <th>License Verified</th>
                                    <th>Status</th>
                                    <th>Employee</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; foreach ($testDrives as $td):
                                    $img = !empty($td['vehicle_image']) ? '../../' . $td['vehicle_image'] : '../../assets/uploads/vehicles/default.png';
                                    $statusClass = match($td['status']) {
                                        'Pending' => 'warning',
                                        'Approved' => 'success',
                                        'Completed' => 'info',
                                        'Cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                    $licenseClass = match($td['drivers_license_verified']) {
                                        'Verified' => 'success',
                                        'Rejected' => 'danger',
                                        default => 'warning'
                                    };
                                    $employeeName = $td['employee_first_name'] ? $td['employee_first_name'] . ' ' . $td['employee_last_name'] : 'Unassigned';
                                ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($img) ?>" class="table-img" onerror="this.src='../../assets/uploads/vehicles/default.png'">
                                            <br><small><?= htmlspecialchars($td['make'] . ' ' . $td['model']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($td['customer_first_name'] . ' ' . $td['customer_last_name']) ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($td['customer_number']) ?></small>
                                        </td>
                                        <td>
                                            <?= date('d M Y', strtotime($td['test_drive_date'])) ?>
                                            <br><small><?= date('H:i', strtotime($td['test_drive_time'])) ?> (<?= $td['duration_minutes'] ?> min)</small>
                                        </td>
                                        <td><span class="badge bg-<?= $licenseClass ?>"><?= htmlspecialchars($td['drivers_license_verified']) ?></span></td>
                                        <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= htmlspecialchars($td['status']) ?></span></td>
                                        <td><?= htmlspecialchars($employeeName) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTestDrive(<?= $td['test_drive_id'] ?>)">
                                                <i class="fas fa-edit"></i> Manage
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php include '../../includes/footer.php'; ?>

    </div>

    <!-- ========== MODAL: Manage Test Drive ========== -->
    <div class="modal fade" id="testDriveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testDriveModalTitle">Manage Test Drive</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="testDriveForm">
                    <input type="hidden" name="action" value="update_test_drive">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="test_drive_id" id="modal_test_drive_id" value="0">
                    <div class="modal-body" id="testDriveModalBody">
                        <!-- Content loaded via AJAX -->
                        <div id="testDriveDetails">
                            <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p>Loading...</p></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
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
            $('#testDrivesTable').DataTable({
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [
                    { orderable: false, targets: [1, 7] }
                ],
                language: {
                    search: "Filter:",
                    searchPlaceholder: "Search test drives..."
                }
            });
        });

        // Load test drive details into modal
        function viewTestDrive(testDriveId) {
            $('#modal_test_drive_id').val(testDriveId);
            $('#testDriveDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p>Loading...</p></div>');
            $('#testDriveModal').modal('show');

            $.ajax({
                url: 'get_test_drive.php?id=' + testDriveId,
                dataType: 'json',
                success: function(data) {
                    let html = buildDetailsHtml(data);
                    $('#testDriveDetails').html(html);
                    $('#testDriveModalTitle').text('Test Drive #' + data.test_drive_id + ' – ' + data.customer_first_name + ' ' + data.customer_last_name);
                    // Set form fields
                    $('#modal_test_drive_id').val(data.test_drive_id);
                    $('select[name="status"]').val(data.status);
                    $('select[name="drivers_license_verified"]').val(data.drivers_license_verified);
                    $('textarea[name="employee_notes"]').val(data.employee_notes || '');
                    $('select[name="employee_id"]').val(data.employee_id || '');
                },
                error: function() {
                    $('#testDriveDetails').html('<div class="alert alert-danger">Error loading test drive details.</div>');
                }
            });
        }

        // Build the HTML for the modal body with customer, vehicle, and editable fields
        function buildDetailsHtml(data) {
            let statusOptions = ['Pending', 'Approved', 'Completed', 'Cancelled'];
            let statusSelect = `<select name="status" class="form-select" required>
                ${statusOptions.map(s => `<option value="${s}" ${data.status == s ? 'selected' : ''}>${s}</option>`).join('')}
            </select>`;

            let licenseOptions = ['Pending', 'Verified', 'Rejected'];
            let licenseSelect = `<select name="drivers_license_verified" class="form-select" required>
                ${licenseOptions.map(l => `<option value="${l}" ${data.drivers_license_verified == l ? 'selected' : ''}>${l}</option>`).join('')}
            </select>`;

            let employeeSelect = `<select name="employee_id" class="form-select">
                <option value="">Unassigned</option>`;
            // We'll pass employeesList from PHP via JavaScript variable
            if (typeof employeesList !== 'undefined') {
                employeesList.forEach(emp => {
                    let selected = emp.employee_id == data.employee_id ? 'selected' : '';
                    employeeSelect += `<option value="${emp.employee_id}" ${selected}>${emp.first_name} ${emp.last_name}</option>`;
                });
            }
            employeeSelect += `</select>`;

            let vehicleImg = data.vehicle_image ? '../../' + data.vehicle_image : '../../assets/uploads/vehicles/default.png';

            return `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Customer Details</h6>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Name</span><span class="value">${data.customer_first_name} ${data.customer_last_name}</span></div>
                            <div class="detail-item"><span class="label">Customer Number</span><span class="value">${data.customer_number}</span></div>
                            <div class="detail-item"><span class="label">Phone</span><span class="value">${data.customer_phone || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Email</span><span class="value">${data.customer_email || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Address</span><span class="value">${data.customer_address ? data.customer_address + ', ' + data.customer_city + ', ' + data.customer_province : 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Driver's License</span><span class="value">${data.driver_license || 'N/A'}</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Vehicle Details</h6>
                        <div class="text-center mb-3">
                            <img src="${vehicleImg}" style="max-width:100%; max-height:150px; border-radius:8px;" onerror="this.src='../../assets/uploads/vehicles/default.png'">
                        </div>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Make / Model</span><span class="value">${data.make} ${data.model}</span></div>
                            <div class="detail-item"><span class="label">Year</span><span class="value">${data.manufacture_year}</span></div>
                            <div class="detail-item"><span class="label">Stock Number</span><span class="value">${data.stock_number}</span></div>
                            <div class="detail-item"><span class="label">Registration</span><span class="value">${data.registration_number || 'N/A'}</span></div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Test Drive Details</h6>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Date</span><span class="value">${data.test_drive_date}</span></div>
                            <div class="detail-item"><span class="label">Time</span><span class="value">${data.test_drive_time}</span></div>
                            <div class="detail-item"><span class="label">Duration (min)</span><span class="value">${data.duration_minutes}</span></div>
                            <div class="detail-item"><span class="label">Customer Notes</span><span class="value">${data.customer_notes || 'None'}</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Status & Verification</h6>
                        <div class="mb-3">
                            <label class="form-label">Test Drive Status</label>
                            ${statusSelect}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver's License Verification</label>
                            ${licenseSelect}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assigned Employee</label>
                            ${employeeSelect}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Employee Notes</label>
                            <textarea name="employee_notes" class="form-control" rows="3">${data.employee_notes || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }

        // Pass employees list to JavaScript
        var employeesList = <?= json_encode($employees) ?>;

        // Form submit handler
        $('#testDriveForm').on('submit', function(e) {
            // The form submits via POST to the same page, so we don't need AJAX here.
            // We'll just let it submit naturally.
            // But we could add a confirm or validation.
            // return true;
        });
    </script>
</body>
</html>