<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only customers can access this page
if ($_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit;
}

$pageTitle = "New Service Booking";
$personId = $_SESSION['person_id'];

// Get customer_id from person_id
$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$personId]);
$customer = $stmt->fetch();
if (!$customer) {
    die("Customer record not found.");
}
$customerId = $customer['customer_id'];

// --- AJAX: fetch workshops for a given service name (used by Select2) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_workshops') {
    header('Content-Type: application/json');
    if (empty($_GET['service_name'])) {
        echo json_encode([]);
        exit;
    }
    $serviceName = $_GET['service_name'];
    $stmt = $pdo->prepare("
        SELECT w.workshop_id, w.workshop_name
        FROM workshop_services ws
        JOIN workshops w ON ws.workshop_id = w.workshop_id
        WHERE ws.service_name = ? AND ws.status = 'Available' AND w.status = 'Open'
        ORDER BY w.workshop_name
    ");
    $stmt->execute([$serviceName]);
    $workshops = $stmt->fetchAll();
    echo json_encode($workshops);
    exit;
}

// --- Handle form submission ---
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name'] ?? '');
    $workshop_id = (int)($_POST['workshop_id'] ?? 0);
    $vehicle_name = trim($_POST['vehicle_name'] ?? '');
    $vehicle_model = trim($_POST['vehicle_model'] ?? '');
    $registration_number = trim($_POST['registration_number'] ?? '');
    $booking_date = $_POST['booking_date'] ?? '';
    $booking_time = $_POST['booking_time'] ?? '';
    $vehicle_mileage = !empty($_POST['vehicle_mileage']) ? (int)$_POST['vehicle_mileage'] : null;
    $customer_complaint = trim($_POST['customer_complaint'] ?? '');

    // Validate required fields
    if (empty($service_name) || empty($workshop_id) || empty($vehicle_name) || empty($vehicle_model) ||
        empty($registration_number) || empty($booking_date) || empty($booking_time)) {
        $error = "All fields except mileage and complaint are required.";
    } else {
        // Validate date: not past
        $today = date('Y-m-d');
        if ($booking_date < $today) {
            $error = "Booking date cannot be in the past.";
        }
        // Validate time: between 08:00 and 17:00
        elseif ($booking_time < '08:00' || $booking_time > '17:00') {
            $error = "Booking time must be between 08:00 and 17:00.";
        } else {
            // Get service details
            $stmt = $pdo->prepare("
                SELECT service_id, base_price, estimated_duration_hours
                FROM workshop_services
                WHERE workshop_id = ? AND service_name = ? AND status = 'Available'
                LIMIT 1
            ");
            $stmt->execute([$workshop_id, $service_name]);
            $service = $stmt->fetch();
            if (!$service) {
                $error = "Selected service is not available at the chosen workshop.";
            } else {
                $service_id = $service['service_id'];
                $total_estimated_cost = $service['base_price'];

                // Generate unique booking reference
                $booking_reference = 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));

                // Insert booking
                $stmt = $pdo->prepare("
                    INSERT INTO service_bookings (
                        booking_reference, customer_id, vehicle_id, workshop_id, service_id,
                        assigned_employee_id, vehicle_name, vehicle_model, registration_number,
                        booking_date, booking_time, vehicle_mileage, customer_complaint,
                        estimated_completion_date, actual_completion_date, booking_status,
                        total_estimated_cost, total_actual_cost
                    ) VALUES (
                        ?, ?, NULL, ?, ?, NULL,
                        ?, ?, ?, ?, ?,
                        ?, ?, NULL, NULL, 'Pending',
                        ?, 0.00
                    )
                ");
                $params = [
                    $booking_reference, $customerId, $workshop_id, $service_id,
                    $vehicle_name, $vehicle_model, $registration_number,
                    $booking_date, $booking_time, $vehicle_mileage, $customer_complaint,
                    $total_estimated_cost
                ];

                if ($stmt->execute($params)) {
                    $success = "Booking created successfully! Your reference: " . htmlspecialchars($booking_reference);
                } else {
                    $error = "Failed to create booking. Please try again.";
                }
            }
        }
    }
}

// --- Fetch distinct service names for dropdown ---
$stmt = $pdo->query("
    SELECT DISTINCT service_name, category
    FROM workshop_services
    WHERE status = 'Available'
    ORDER BY service_name
");
$services = $stmt->fetchAll();

// --- Fetch customer's bookings with filters (date range) ---
$filterStart = isset($_GET['filter_start']) ? $_GET['filter_start'] : '';
$filterEnd   = isset($_GET['filter_end']) ? $_GET['filter_end'] : '';

$bookings = [];
$params = [$customerId];
$sql = "
    SELECT
        b.*,
        w.workshop_name,
        ws.service_name,
        ws.category
    FROM service_bookings b
    JOIN workshops w ON b.workshop_id = w.workshop_id
    JOIN workshop_services ws ON b.service_id = ws.service_id
    WHERE b.customer_id = ?
";

if (!empty($filterStart) && !empty($filterEnd)) {
    $sql .= " AND b.booking_date BETWEEN ? AND ?";
    $params[] = $filterStart;
    $params[] = $filterEnd;
} elseif (!empty($filterStart)) {
    $sql .= " AND b.booking_date >= ?";
    $params[] = $filterStart;
} elseif (!empty($filterEnd)) {
    $sql .= " AND b.booking_date <= ?";
    $params[] = $filterEnd;
}

$sql .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
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

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Google Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <style>
        /* ── Global ── */
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

        /* ── Cards ── */
        .form-card, .bookings-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
            padding: 30px 35px;
            margin-bottom: 30px;
        }
        .form-card .card-title, .bookings-card .card-title {
            font-weight: 700;
            color: #1f2937;
            letter-spacing: -0.3px;
        }

        /* ── Form elements ── */
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 0.95rem;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 3px rgba(17,153,142,0.2);
            background: #fff;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            padding: 12px 30px;
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
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            color: #1f2937;
            transition: background 0.2s;
        }
        .btn-secondary-custom:hover {
            background: #d1d5db;
        }

        /* ── Alert styling ── */
        .alert-custom {
            border-radius: 16px;
            border: none;
        }

        /* ── Select2 override ── */
        .select2-container--default .select2-selection--single {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            height: auto;
            background: #f9fafb;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            color: #1f2937;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }

        /* ── Tables ── */
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

        /* ── Filter form ── */
        .filter-form .form-control, .filter-form .btn {
            border-radius: 12px;
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .form-card, .bookings-card {
                padding: 20px;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        @media (max-width: 768px) {
            .form-card, .bookings-card {
                padding: 15px;
            }
            .form-card .card-title, .bookings-card .card-title {
                font-size: 1.2rem;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
                padding: 10px 14px;
            }
            .btn-primary-custom, .btn-secondary-custom {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
            }
            .filter-form .col-md-4 {
                margin-bottom: 10px;
            }
            .filter-form .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .filter-form .d-flex .btn {
                width: 100%;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.75rem;
                padding: 8px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.7rem;
            }
            /* Stack form submit/reset buttons */
            .col-12.d-flex {
                flex-direction: column;
                gap: 10px;
            }
            .col-12.d-flex .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .form-card, .bookings-card {
                padding: 12px;
                border-radius: 16px;
            }
            .form-card .card-title, .bookings-card .card-title {
                font-size: 1.1rem;
            }
            .form-label {
                font-size: 0.8rem;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.7rem;
                padding: 6px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
            .alert-custom {
                font-size: 0.9rem;
                padding: 12px 16px;
            }
            /* Improve touch targets */
            .btn-primary-custom, .btn-secondary-custom {
                padding: 14px 20px;
                font-size: 1rem;
            }
            .select2-container--default .select2-selection--single {
                padding: 6px 10px;
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
                <i class="fas fa-calendar-plus me-2 text-primary"></i> New Service Booking
            </h4>
        </div>

        <!-- Display messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-custom d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?= $success ?></div>
            </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <div class="form-card">
            <h5 class="card-title mb-4"><i class="fas fa-tools me-2 text-success"></i> Booking Details</h5>

            <form method="POST" action="" id="bookingForm">
                <div class="row g-4">
                    <!-- Service selection (searchable dropdown) -->
                    <div class="col-md-6">
                        <label for="service_name" class="form-label">Service Type <span class="text-danger">*</span></label>
                        <select class="form-select select2-service" id="service_name" name="service_name" required>
                            <option value="">-- Select a Service --</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= htmlspecialchars($s['service_name']) ?>" <?= (isset($_POST['service_name']) && $_POST['service_name'] == $s['service_name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['service_name']) ?>
                                    <?= $s['category'] ? ' (' . htmlspecialchars($s['category']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Workshop selection (searchable dropdown, populated via AJAX) -->
                    <div class="col-md-6">
                        <label for="workshop_id" class="form-label">Workshop <span class="text-danger">*</span></label>
                        <select class="form-select select2-workshop" id="workshop_id" name="workshop_id" required disabled>
                            <option value="">-- First select a service --</option>
                        </select>
                    </div>

                    <!-- Vehicle fields -->
                    <div class="col-md-4">
                        <label for="vehicle_name" class="form-label">Vehicle Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="vehicle_name" name="vehicle_name"
                               value="<?= htmlspecialchars($_POST['vehicle_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="vehicle_model" class="form-label">Vehicle Model <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="vehicle_model" name="vehicle_model"
                               value="<?= htmlspecialchars($_POST['vehicle_model'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="registration_number" class="form-label">Registration Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="registration_number" name="registration_number"
                               value="<?= htmlspecialchars($_POST['registration_number'] ?? '') ?>" required>
                    </div>

                    <!-- Date & Time with validation -->
                    <div class="col-md-6">
                        <label for="booking_date" class="form-label">Booking Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="booking_date" name="booking_date"
                               value="<?= htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')) ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="booking_time" class="form-label">Booking Time <span class="text-danger">*</span> (08:00 – 17:00)</label>
                        <input type="time" class="form-control" id="booking_time" name="booking_time"
                               value="<?= htmlspecialchars($_POST['booking_time'] ?? '08:00') ?>"
                               min="08:00" max="17:00" step="900" required>
                    </div>

                    <!-- Mileage & Complaint (optional) -->
                    <div class="col-md-6">
                        <label for="vehicle_mileage" class="form-label">Vehicle Mileage (km) <span class="text-muted">(optional)</span></label>
                        <input type="number" class="form-control" id="vehicle_mileage" name="vehicle_mileage"
                               value="<?= htmlspecialchars($_POST['vehicle_mileage'] ?? '') ?>" min="0">
                    </div>
                    <div class="col-md-6">
                        <label for="customer_complaint" class="form-label">Customer Complaint <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="customer_complaint" name="customer_complaint" rows="3"><?= htmlspecialchars($_POST['customer_complaint'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit / Reset -->
                    <div class="col-12 mt-4 d-flex gap-3">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-2"></i> Book Service
                        </button>
                        <button type="button" class="btn btn-secondary-custom" id="resetFormBtn">
                            <i class="fas fa-undo me-2"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- My Bookings Section with Date Filter -->
        <div class="bookings-card">
            <h5 class="card-title mb-4"><i class="fas fa-history me-2 text-primary"></i> My Bookings</h5>

            <!-- Filter Form -->
            <form method="GET" action="" class="filter-form row g-3 mb-4">
                <div class="col-md-4">
                    <label for="filter_start" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="filter_start" name="filter_start"
                           value="<?= htmlspecialchars($filterStart) ?>">
                </div>
                <div class="col-md-4">
                    <label for="filter_end" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="filter_end" name="filter_end"
                           value="<?= htmlspecialchars($filterEnd) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100">
                        <i class="fas fa-filter me-2"></i> Filter
                    </button>
                    <a href="create.php" class="btn btn-secondary-custom w-100">
                        <i class="fas fa-undo me-2"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Bookings Table -->
            <?php if (count($bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dashboard">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Service</th>
                                <th>Workshop</th>
                                <th>Vehicle</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Est. Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark px-3 py-2">#<?= htmlspecialchars($b['booking_reference']) ?></span></td>
                                    <td><?= htmlspecialchars($b['service_name']) ?></td>
                                    <td><?= htmlspecialchars($b['workshop_name']) ?></td>
                                    <td><?= htmlspecialchars($b['registration_number']) ?></td>
                                    <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                    <td><?= date('H:i', strtotime($b['booking_time'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['booking_status'] == 'Completed' ? 'success' : ($b['booking_status'] == 'Pending' ? 'warning' : ($b['booking_status'] == 'Confirmed' ? 'info' : 'secondary')) ?>">
                                            <?= htmlspecialchars($b['booking_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($b['total_estimated_cost'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No bookings found for the selected period.
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for service dropdown (searchable)
        $('.select2-service').select2({
            placeholder: '-- Search or select a service --',
            allowClear: false,
            width: '100%'
        });

        // Initialize Select2 for workshop dropdown (will be updated dynamically)
        $('.select2-workshop').select2({
            placeholder: '-- First select a service --',
            allowClear: false,
            width: '100%'
        });

        // Function to load workshops and update Select2
        function loadWorkshops(serviceName) {
            var workshopSelect = $('#workshop_id');
            if (!serviceName) {
                workshopSelect.html('<option value="">-- First select a service --</option>').prop('disabled', true);
                workshopSelect.trigger('change'); // update Select2
                return;
            }

            $.ajax({
                url: window.location.pathname + '?action=get_workshops',
                data: { service_name: serviceName },
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">-- Select a workshop --</option>';
                    $.each(data, function(index, ws) {
                        options += '<option value="' + ws.workshop_id + '">' + ws.workshop_name + '</option>';
                    });
                    workshopSelect.html(options).prop('disabled', false);
                    workshopSelect.trigger('change'); // update Select2
                },
                error: function() {
                    workshopSelect.html('<option value="">Error loading workshops</option>').prop('disabled', true);
                    workshopSelect.trigger('change');
                }
            });
        }

        // On service change, load workshops
        $('#service_name').on('change', function() {
            var serviceName = $(this).val();
            loadWorkshops(serviceName);
        });

        // If service is pre-selected (e.g., after form error), load workshops on page load
        var initialService = $('#service_name').val();
        if (initialService) {
            loadWorkshops(initialService);
            // Also, if workshop was previously selected, re-select it
            var previousWorkshop = "<?= isset($_POST['workshop_id']) ? (int)$_POST['workshop_id'] : '' ?>";
            if (previousWorkshop) {
                setTimeout(function() {
                    $('#workshop_id').val(previousWorkshop).trigger('change');
                }, 200);
            }
        }

        // Custom reset for the booking form
        $('#resetFormBtn').on('click', function() {
            // Reset all form fields (text, number, date, time, textarea)
            $('#bookingForm')[0].reset();

            // Reset Select2 service dropdown to first option (placeholder)
            var serviceSelect = $('#service_name');
            serviceSelect.val('').trigger('change');

            // Reset workshop dropdown: clear options, disable, and reset Select2
            var workshopSelect = $('#workshop_id');
            workshopSelect.html('<option value="">-- First select a service --</option>').prop('disabled', true);
            workshopSelect.trigger('change');

            // Clear any error/success messages (optional)
            // $('.alert').remove();
        });

        // Validate time on form submit (additional client-side)
        $('#bookingForm').on('submit', function(e) {
            var time = $('#booking_time').val();
            if (time < '08:00' || time > '17:00') {
                alert('Booking time must be between 08:00 and 17:00.');
                e.preventDefault();
                return false;
            }
            // Date validation is handled by HTML5 min attribute
            return true;
        });
    });
</script>
</body>
</html>