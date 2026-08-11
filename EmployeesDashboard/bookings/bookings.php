<?php
/**
 * Employee – Assigned Bookings
 * View bookings assigned to the logged‑in employee.
 * Create job cards and add service updates.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Employee') {
    header('Location: login.php');
    exit;
}

$personId = (int) $_SESSION['person_id'];
$pageTitle = "My Assigned Bookings";

// Get employee ID
$stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE person_id = ?");
$stmt->execute([$personId]);
$employee = $stmt->fetch();
if (!$employee) die('Employee record not found.');
$employeeId = $employee['employee_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$messageType = '';

// ---------- Create Job Card ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_job_card') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } else {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $mechanicId = (int) ($_POST['mechanic_employee_id'] ?? 0);
        $advisorId = (int) ($_POST['service_advisor_employee_id'] ?? 0);
        $odometer = (int) ($_POST['odometer_reading'] ?? 0);
        $fuelLevel = $_POST['fuel_level'] ?? '1/2';
        $vehicleCondition = trim($_POST['vehicle_condition'] ?? '');
        $customerComplaint = trim($_POST['customer_complaint'] ?? '');
        $priority = $_POST['priority'] ?? 'Normal';
        $advisorNotes = trim($_POST['advisor_notes'] ?? '');
        $mechanicNotes = trim($_POST['mechanic_notes'] ?? '');

        // Fetch booking to get vehicle_id and customer_id
        $stmt = $pdo->prepare("SELECT  customer_id, customer_complaint FROM service_bookings WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            $message = 'Booking not found.';
            $messageType = 'danger';
        } else {
            $jobCardNumber = 'JC' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            if (empty($customerComplaint)) $customerComplaint = $booking['customer_complaint'] ?? '';
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO job_cards (
                        job_card_number, booking_id, customer_id,
                        mechanic_employee_id, service_advisor_employee_id,
                        odometer_reading, fuel_level, vehicle_condition,
                        customer_complaint, priority, advisor_notes, mechanic_notes,
                        status, opened_at
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        'Open', NOW()
                    )
                ");
                $stmt->execute([
                    $jobCardNumber, $bookingId,  $booking['customer_id'],
                    $mechanicId, $advisorId,
                    $odometer, $fuelLevel, $vehicleCondition,
                    $customerComplaint, $priority, $advisorNotes, $mechanicNotes
                ]);
                $message = 'Job card created successfully.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// ---------- Add Service Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service_update') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    } else {
        $jobCardId = (int) ($_POST['job_card_id'] ?? 0);
        $updateStatus = $_POST['update_status'] ?? '';
        $comments = trim($_POST['comments'] ?? '');
        $notifyCustomer = $_POST['notify_customer'] ?? 'Yes';

        if (empty($updateStatus)) {
            $message = 'Please select an update status.';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO service_updates (
                        job_card_id, updated_by_employee_id, update_status,
                        comments, notify_customer
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$jobCardId, $employeeId, $updateStatus, $comments, $notifyCustomer]);
                $message = 'Service update added.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// ---------- Fetch assigned bookings ----------
$bookingsStmt = $pdo->prepare("
    SELECT
        b.*,
        ws.workshop_name,
        ws.phone AS workshop_phone,
        ws.email AS workshop_email,
        ws.address AS workshop_address,
        ws.city AS workshop_city,
        ws.province AS workshop_province,
        s.service_name,
        s.category AS service_category,
        s.description AS service_description,
        s.base_price AS service_base_price,
        -- vehicle columns from b
        b.vehicle_name,
        b.vehicle_model,
        b.registration_number,
        -- customer
        p.first_name AS customer_first_name,
        p.middle_name AS customer_middle_name,
        p.last_name AS customer_last_name,
        p.phone AS customer_phone,
        p.email AS customer_email,
        p.address AS customer_address,
        p.city AS customer_city,
        p.province AS customer_province,
        p.postal_code AS customer_postal_code,
        c.customer_number,
        c.loyalty_points,
        c.driver_license
    FROM service_bookings b
    JOIN workshops ws ON b.workshop_id = ws.workshop_id
    JOIN workshop_services s ON b.service_id = s.service_id
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN persons p ON c.person_id = p.person_id
    WHERE b.assigned_employee_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$bookingsStmt->execute([$employeeId]);
$bookings = $bookingsStmt->fetchAll();

// Helper: get job card for a booking
function getJobCardForBooking($pdo, $bookingId) {
    $stmt = $pdo->prepare("
        SELECT jc.*,
               me.first_name AS mechanic_first_name, me.last_name AS mechanic_last_name,
               ae.first_name AS advisor_first_name, ae.last_name AS advisor_last_name
        FROM job_cards jc
        LEFT JOIN employees me_emp ON jc.mechanic_employee_id = me_emp.employee_id
        LEFT JOIN persons me ON me_emp.person_id = me.person_id
        LEFT JOIN employees ae_emp ON jc.service_advisor_employee_id = ae_emp.employee_id
        LEFT JOIN persons ae ON ae_emp.person_id = ae.person_id
        WHERE jc.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    return $stmt->fetch();
}

// Helper: get updates for a job card
function getUpdatesForJobCard($pdo, $jobCardId) {
    $stmt = $pdo->prepare("
        SELECT su.*,
               p.first_name, p.last_name
        FROM service_updates su
        JOIN employees e ON su.updated_by_employee_id = e.employee_id
        JOIN persons p ON e.person_id = p.person_id
        WHERE su.job_card_id = ?
        ORDER BY su.created_at DESC
    ");
    $stmt->execute([$jobCardId]);
    return $stmt->fetchAll();
}
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
        body { font-family: 'Inter', sans-serif; background: #f4f7fc; }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            background: #ffffff;
            transition: box-shadow 0.3s;
        }
        .card-custom:hover { box-shadow: 0 15px 35px rgba(0, 0, 0, 0.07); }
        .card-custom .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f2f5;
            padding: 18px 25px;
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }
        .booking-row { cursor: pointer; transition: background 0.2s; }
        .booking-row:hover { background: #f9fafb; }
        .badge-status { font-size: 0.85rem; padding: 0.4rem 0.8rem; }
        .detail-label { font-weight: 600; color: #6b7280; font-size: 0.9rem; }
        .detail-value { font-weight: 500; color: #1f2937; }
        .modal-lg { max-width: 900px; }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin: 1.5rem 0 0.75rem 0;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        .update-item {
            padding: 12px 16px;
            border-left: 4px solid #3b82f6;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .update-item .timestamp { font-size: 0.8rem; color: #6b7280; }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
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

        @media (max-width: 576px) {
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
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-6:last-child {
                margin-bottom: 0;
            }
            .section-title {
                font-size: 0.9rem;
            }
            .detail-label {
                font-size: 0.8rem;
            }
            .detail-value {
                font-size: 0.85rem;
            }
            .update-item {
                padding: 8px 12px;
            }
            .update-item .timestamp {
                font-size: 0.7rem;
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

            <!-- Page Header removed; page starts directly with the table -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fas fa-tasks me-2 text-primary"></i>My Assigned Bookings
                </h4>
                <span class="badge bg-secondary"><?= count($bookings) ?> bookings</span>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header"><i class="fas fa-list me-2 text-primary"></i>Assigned Bookings</div>
                <div class="card-body p-0">
                    <?php if (count($bookings) === 0): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>No bookings assigned to you.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Vehicle</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking):
                                        $jobCard = getJobCardForBooking($pdo, $booking['booking_id']);
                                        $hasJobCard = ($jobCard !== false);
                                    ?>
                                        <tr class="booking-row" onclick="viewBooking(<?= $booking['booking_id'] ?>, this)" data-booking-id="<?= $booking['booking_id'] ?>">
                                            <td><strong><?= htmlspecialchars($booking['booking_reference']) ?></strong></td>
                                            <td><?= htmlspecialchars($booking['customer_first_name'] . ' ' . $booking['customer_last_name']) ?></td>
                                            <td><?= htmlspecialchars($booking['service_name']) ?></td>
                                            <td><?= htmlspecialchars($booking['vehicle_name'] . ' ' . $booking['vehicle_model']) ?></td>
                                            <td><?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $booking['booking_status'] == 'Completed' ? 'success' : ($booking['booking_status'] == 'Pending' ? 'warning' : 'primary') ?> badge-status">
                                                    <?= htmlspecialchars($booking['booking_status']) ?>
                                                </span>
                                                <?php if ($hasJobCard): ?>
                                                    <span class="badge bg-info text-dark ms-1"><i class="fas fa-clipboard"></i> JC</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewBooking(<?= $booking['booking_id'] ?>, this.closest('tr'))">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
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

    <!-- MODALS -->
    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingModalBody">
                    <div id="bookingDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Job Card Modal -->
    <div class="modal fade" id="jobCardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Job Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="jobCardForm">
                    <input type="hidden" name="action" value="create_job_card">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="booking_id" id="jc_booking_id" value="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mechanic</label>
                                <select name="mechanic_employee_id" class="form-select" required>
                                    <option value="">Select Mechanic</option>
                                    <?php
                                    $mechStmt = $pdo->query("
                                        SELECT e.employee_id, p.first_name, p.last_name
                                        FROM employees e
                                        JOIN persons p ON e.person_id = p.person_id
                                        WHERE p.role = 'Employee'
                                        ORDER BY p.first_name
                                    ");
                                    while ($m = $mechStmt->fetch()):
                                    ?>
                                        <option value="<?= $m['employee_id'] ?>" <?= $m['employee_id'] == $employeeId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Service Advisor</label>
                                <select name="service_advisor_employee_id" class="form-select">
                                    <option value="">Select Advisor</option>
                                    <?php
                                    $advisorStmt = $pdo->query("
                                        SELECT e.employee_id, p.first_name, p.last_name
                                        FROM employees e
                                        JOIN persons p ON e.person_id = p.person_id
                                        WHERE p.role = 'Employee'
                                        ORDER BY p.first_name
                                    ");
                                    while ($a = $advisorStmt->fetch()):
                                    ?>
                                        <option value="<?= $a['employee_id'] ?>"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Odometer</label>
                                <input type="number" name="odometer_reading" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fuel Level</label>
                                <select name="fuel_level" class="form-select">
                                    <option value="Empty">Empty</option>
                                    <option value="1/4">1/4</option>
                                    <option value="1/2" selected>1/2</option>
                                    <option value="3/4">3/4</option>
                                    <option value="Full">Full</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="Low">Low</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Vehicle Condition</label>
                                <textarea name="vehicle_condition" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Customer Complaint</label>
                                <textarea name="customer_complaint" class="form-control" rows="2" id="jc_customer_complaint"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Advisor Notes</label>
                                <textarea name="advisor_notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mechanic Notes</label>
                                <textarea name="mechanic_notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Job Card</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Service Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="updateForm">
                    <input type="hidden" name="action" value="add_service_update">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="job_card_id" id="update_job_card_id" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select name="update_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="Vehicle Received">Vehicle Received</option>
                                <option value="Inspection">Inspection</option>
                                <option value="Diagnosis">Diagnosis</option>
                                <option value="Waiting for Parts">Waiting for Parts</option>
                                <option value="Repair in Progress">Repair in Progress</option>
                                <option value="Quality Control">Quality Control</option>
                                <option value="Ready for Collection">Ready for Collection</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea name="comments" class="form-control" rows="3" placeholder="Add details..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notify Customer</label>
                            <select name="notify_customer" class="form-select">
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Main function to view booking details via AJAX
        function viewBooking(bookingId, rowElement) {
            if (rowElement) {
                document.querySelectorAll('.booking-row').forEach(r => r.style.background = '');
                rowElement.style.background = '#e5e7eb';
            }

            fetch('get_booking_details.php?booking_id=' + bookingId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    let html = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="section-title">Booking Information</h6>
                                <p><span class="detail-label">Reference:</span> <span class="detail-value">${data.booking_reference}</span></p>
                                <p><span class="detail-label">Date:</span> <span class="detail-value">${data.booking_date} ${data.booking_time}</span></p>
                                <p><span class="detail-label">Status:</span> <span class="badge bg-${data.booking_status == 'Completed' ? 'success' : 'primary'}">${data.booking_status}</span></p>
                                <p><span class="detail-label">Estimated Cost:</span> <span class="detail-value">R ${(data.total_estimated_cost ? parseFloat(data.total_estimated_cost).toFixed(2)  : '0.00')}</span></p>
                                <p><span class="detail-label">Mileage:</span> <span class="detail-value">${data.vehicle_mileage || 'N/A'}</span></p>
                                <p><span class="detail-label">Customer Complaint:</span> <span class="detail-value">${data.customer_complaint || 'None'}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="section-title">Customer Details</h6>
                                <p><span class="detail-label">Name:</span> <span class="detail-value">${data.customer_first_name} ${data.customer_last_name}</span></p>
                                <p><span class="detail-label">Phone:</span> <span class="detail-value">${data.customer_phone}</span></p>
                                <p><span class="detail-label">Email:</span> <span class="detail-value">${data.customer_email}</span></p>
                                <p><span class="detail-label">Address:</span> <span class="detail-value">${data.customer_address}, ${data.customer_city}, ${data.customer_province}</span></p>
                                <p><span class="detail-label">Customer No:</span> <span class="detail-value">${data.customer_number}</span></p>
                                <p><span class="detail-label">Driver License:</span> <span class="detail-value">${data.driver_license || 'N/A'}</span></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="section-title">Vehicle</h6>
                                <p><span class="detail-label">Name:</span> <span class="detail-value">${data.vehicle_name}</span></p>
                                <p><span class="detail-label">Model:</span> <span class="detail-value">${data.vehicle_model}</span></p>
                                <p><span class="detail-label">Registration:</span> <span class="detail-value">${data.registration_number}</span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="section-title">Workshop & Service</h6>
                                <p><span class="detail-label">Workshop:</span> <span class="detail-value">${data.workshop_name}</span></p>
                                <p><span class="detail-label">Service:</span> <span class="detail-value">${data.service_name}</span></p>
                                <p><span class="detail-label">Category:</span> <span class="detail-value">${data.service_category}</span></p>
                                <p><span class="detail-label">Description:</span> <span class="detail-value">${data.service_description || 'N/A'}</span></p>
                                <p><span class="detail-label">Base Price:</span> <span class="detail-value">R ${(data.service_base_price ? parseFloat(data.service_base_price).toFixed(2) : '0.00')}</span></p>
                                <p><span class="detail-label">Workshop Phone:</span> <span class="detail-value">${data.workshop_phone || 'N/A'}</span></p>
                            </div>
                        </div>
                    `;

                    if (data.job_card) {
                        const jc = data.job_card;
                        html += `
                            <hr>
                            <h6 class="section-title">Job Card</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><span class="detail-label">JC Number:</span> <span class="detail-value">${jc.job_card_number}</span></p>
                                    <p><span class="detail-label">Status:</span> <span class="badge bg-${jc.status == 'Open' ? 'warning' : 'success'}">${jc.status}</span></p>
                                    <p><span class="detail-label">Mechanic:</span> <span class="detail-value">${jc.mechanic_first_name || ''} ${jc.mechanic_last_name || ''}</span></p>
                                    <p><span class="detail-label">Advisor:</span> <span class="detail-value">${jc.advisor_first_name || ''} ${jc.advisor_last_name || ''}</span></p>
                                    <p><span class="detail-label">Opened:</span> <span class="detail-value">${jc.opened_at}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><span class="detail-label">Odometer:</span> <span class="detail-value">${jc.odometer_reading}</span></p>
                                    <p><span class="detail-label">Fuel Level:</span> <span class="detail-value">${jc.fuel_level}</span></p>
                                    <p><span class="detail-label">Priority:</span> <span class="detail-value">${jc.priority}</span></p>
                                    <p><span class="detail-label">Advisor Notes:</span> <span class="detail-value">${jc.advisor_notes || 'None'}</span></p>
                                    <p><span class="detail-label">Mechanic Notes:</span> <span class="detail-value">${jc.mechanic_notes || 'None'}</span></p>
                                </div>
                            </div>
                        `;

                        if (data.updates && data.updates.length > 0) {
                            html += `<h6 class="section-title">Service Updates</h6><div>`;
                            data.updates.forEach(upd => {
                                html += `
                                    <div class="update-item">
                                        <div><strong>${upd.update_status}</strong> <span class="timestamp">${upd.created_at}</span></div>
                                        <div>${upd.comments || ''}</div>
                                        <div><small>by ${upd.first_name} ${upd.last_name} | Notify: ${upd.notify_customer}</small></div>
                                    </div>
                                `;
                            });
                            html += `</div>`;
                        }

                        html += `
                            <div class="mt-3">
                                <button class="btn btn-primary btn-sm" id="addUpdateBtn" data-jobcard-id="${jc.job_card_id}">
                                    <i class="fas fa-plus-circle"></i> Add Update
                                </button>
                            </div>
                        `;
                    } else {
                        html += `
                            <hr>
                            <div class="alert alert-info">No job card created yet.</div>
                            <div class="mt-3">
                                <button class="btn btn-success" id="createJobCardBtn">
                                    <i class="fas fa-clipboard"></i> Create Job Card
                                </button>
                            </div>
                        `;
                    }

                    document.getElementById('bookingDetails').innerHTML = html;
                    document.getElementById('jc_booking_id').value = bookingId;
                    document.getElementById('jc_customer_complaint').value = data.customer_complaint || '';

                    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                    modal.show();

                    // Attach listeners after modal is shown
                    document.getElementById('bookingModal').addEventListener('shown.bs.modal', function handler() {
                        document.getElementById('bookingModal').removeEventListener('shown.bs.modal', handler);
                        const createBtn = document.getElementById('createJobCardBtn');
                        if (createBtn) {
                            createBtn.addEventListener('click', function() {
                                bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                                new bootstrap.Modal(document.getElementById('jobCardModal')).show();
                            });
                        }
                        const updateBtn = document.getElementById('addUpdateBtn');
                        if (updateBtn) {
                            updateBtn.addEventListener('click', function() {
                                document.getElementById('update_job_card_id').value = this.getAttribute('data-jobcard-id');
                                bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                                new bootstrap.Modal(document.getElementById('updateModal')).show();
                            });
                        }
                    });
                })
                .catch(error => {
                    alert('Error loading booking details.');
                    console.error(error);
                });
        }
    </script>
</body>
</html>