<?php
/**
 * Vehicle Sales Module – Customer View
 * - Browse advertised, available vehicles
 * - View details and images
 * - Save vehicles to favourites
 * - Record viewings when detail is opened
 * - Book a test drive (links to test_drive_book.php with vehicle_id)
 * - Book an appointment (modal with auto-filled customer/vehicle IDs)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

/** @var array{person_id: int, first_name: string, role: string} $currentUser */

// Require login to view the showroom at all
if (empty($currentUser['person_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Filter dropdown options — scoped to what's actually shown in the catalog
$catalogScope = "available_for_sale = 'Yes' AND status = 'Available'";
$makes           = $pdo->query("SELECT DISTINCT make FROM vehicles WHERE $catalogScope ORDER BY make")->fetchAll(PDO::FETCH_COLUMN);
$bodyTypes       = $pdo->query("SELECT DISTINCT body_type FROM vehicles WHERE $catalogScope ORDER BY body_type")->fetchAll(PDO::FETCH_COLUMN);
$fuelTypes       = $pdo->query("SELECT DISTINCT fuel_type FROM vehicles WHERE $catalogScope ORDER BY fuel_type")->fetchAll(PDO::FETCH_COLUMN);
$transmissions   = $pdo->query("SELECT DISTINCT transmission FROM vehicles WHERE $catalogScope ORDER BY transmission")->fetchAll(PDO::FETCH_COLUMN);
$conditionTypes  = $pdo->query("SELECT DISTINCT condition_type FROM vehicles WHERE $catalogScope ORDER BY condition_type")->fetchAll(PDO::FETCH_COLUMN);

// For the saved vehicles feature, we need the logged-in customer's customer_id
$customerId = null;
$customerName = '';
$stmt = $pdo->prepare("SELECT customer_id, first_name FROM customers c JOIN persons p ON c.person_id = p.person_id WHERE c.person_id = ?");
$stmt->execute([$currentUser['person_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if ($customer) {
    $customerId = (int) $customer['customer_id'];
    $customerName = $customer['first_name'];
}

// Fetch employees for appointment dropdown (optional)
$employees = [];
if ($customerId) {
    $stmt = $pdo->query("SELECT e.employee_id, p.first_name, p.last_name 
                          FROM employees e 
                          JOIN persons p ON e.person_id = p.person_id 
                          WHERE employee_status = 'Active'");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* ---------- Global ---------- */
        body {
            background: #f4f7fc;
            font-family: 'Inter', sans-serif;
        }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }

        /* ---------- Vehicle Card ---------- */
        .vehicle-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            background: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .vehicle-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        .vehicle-card .card-img-top {
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s ease;
            background: #f0f2f5;
        }
        .vehicle-card:hover .card-img-top {
            transform: scale(1.03);
        }
        .vehicle-card .card-body {
            padding: 1rem 1rem 1.2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .vehicle-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: #1e293b;
        }
        .vehicle-card .card-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .vehicle-card .vehicle-price {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0d6efd;
            margin: 0.4rem 0;
        }
        .vehicle-card .vehicle-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem 0.6rem;
            font-size: 0.8rem;
            color: #475569;
            margin-top: 0.3rem;
        }
        .vehicle-card .vehicle-specs span {
            background: #f1f5f9;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            white-space: nowrap;
        }
        .vehicle-card .badge-condition {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            backdrop-filter: blur(4px);
            z-index: 2;
        }
        .vehicle-card .card-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
            border-radius: 0.75rem;
        }
        .vehicle-card:hover .card-overlay {
            opacity: 1;
            pointer-events: auto;
        }
        .vehicle-card .card-overlay .btn-view,
        .vehicle-card .card-overlay .btn-testdrive {
            background: rgba(255,255,255,0.9);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            color: #0d6efd;
            transform: scale(0.9);
            transition: transform 0.2s;
            pointer-events: auto;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .vehicle-card .card-overlay .btn-view:hover,
        .vehicle-card .card-overlay .btn-testdrive:hover {
            transform: scale(1);
            background: #fff;
        }
        .vehicle-card .card-overlay .btn-testdrive {
            color: #1a7a4a;
        }
        .vehicle-card .card-img-wrapper {
            position: relative;
            overflow: hidden;
        }
        .vehicle-card .card-img-wrapper .card-img-top {
            display: block;
            width: 100%;
        }
        /* ---------- Heart Icon ---------- */
        .heart-icon {
            cursor: pointer;
            font-size: 1.5rem;
            transition: color 0.2s, transform 0.2s;
            color: #ccc;
            line-height: 1;
        }
        .heart-icon.saved {
            color: #dc3545;
        }
        .heart-icon:hover {
            transform: scale(1.15);
        }
        /* ---------- Filter Bar ---------- */
        .filter-section {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem 0.75rem;
        }
        .filter-bar .filter-item {
            flex: 0 0 auto;
            min-width: 120px;
        }
        .filter-bar .filter-item label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 0.1rem;
            display: block;
        }
        .filter-bar .filter-item input,
        .filter-bar .filter-item select {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            height: 32px;
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            background: #fff;
            width: 100%;
        }
        .filter-bar .filter-item input:focus,
        .filter-bar .filter-item select:focus {
            border-color: #0d6efd;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
        }
        .filter-bar .filter-actions {
            display: flex;
            gap: 0.4rem;
            align-items: center;
            margin-left: auto;
        }
        .filter-bar .filter-actions .btn {
            font-size: 0.85rem;
            padding: 0.2rem 0.8rem;
            height: 32px;
            border-radius: 0.375rem;
        }
        .filter-bar .filter-actions .btn-filter {
            background: #0d6efd;
            color: #fff;
            border: none;
        }
        .filter-bar .filter-actions .btn-filter:hover {
            background: #0b5ed7;
        }
        .filter-bar .filter-actions .btn-reset {
            background: transparent;
            border: 1px solid #ced4da;
            color: #495057;
        }
        .filter-bar .filter-actions .btn-reset:hover {
            background: #e9ecef;
        }
        .filter-toggle-link {
            font-size: 0.8rem;
            color: #0d6efd;
            cursor: pointer;
            text-decoration: none;
        }
        .filter-toggle-link:hover {
            text-decoration: underline;
        }
        .advanced-filters {
            display: none;
            padding-top: 0.75rem;
            border-top: 1px dashed #dee2e6;
            margin-top: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
        }
        .advanced-filters.show {
            display: flex;
        }
        /* ---------- Other ---------- */
        .detail-label { font-weight: 600; color: #6c757d; }
        .modal-lg { max-width: 900px; }
        .no-vehicles { text-align: center; padding: 50px 0; color: #6c757d; }
        .btn-testdrive-modal {
            background: #1a7a4a;
            color: #fff;
            border: none;
        }
        .btn-testdrive-modal:hover {
            background: #13603a;
            color: #fff;
        }
        .btn-appointment-modal {
            background: #0d6efd;
            color: #fff;
            border: none;
        }
        .btn-appointment-modal:hover {
            background: #0b5ed7;
            color: #fff;
        }

        /* ---------- RESPONSIVE TWEAKS ---------- */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .filter-item {
                min-width: unset;
                width: 100%;
            }
            .filter-bar .filter-actions {
                margin-left: 0;
                justify-content: stretch;
                flex-wrap: wrap;
            }
            .filter-bar .filter-actions .btn {
                flex: 1 1 auto;
                min-width: 0;
                padding: 0.4rem 0.5rem;
                font-size: 0.8rem;
            }
            .filter-toggle-link {
                text-align: center;
                display: block;
                margin-top: 0.5rem;
            }
            .advanced-filters {
                flex-direction: column;
            }
            .advanced-filters .filter-item {
                width: 100%;
            }
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
            }
            .modal-lg {
                max-width: 100%;
                margin: 0.5rem;
            }
            .modal-body .carousel-item img {
                height: 200px;
                object-fit: cover;
            }
            .modal-footer {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .modal-footer .btn {
                flex: 1 1 auto;
                min-width: 0;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.1rem;
            }
            .filter-bar .filter-actions .btn {
                font-size: 0.75rem;
                padding: 0.3rem 0.4rem;
            }
            .vehicle-card .card-title {
                font-size: 1rem;
            }
            .vehicle-card .vehicle-price {
                font-size: 1.1rem;
            }
            .vehicle-card .card-img-top {
                height: 160px;
            }
            .modal-body .carousel-item img {
                height: 150px;
            }
            .modal-footer .btn {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            .heart-icon {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <div>
                <h2><i class="fas fa-car text-primary me-2"></i>Vehicle Showroom</h2>
                <p class="text-muted">Browse our available vehicles and book a test drive or appointment.</p>
            </div>
            <?php if (!$customerId): ?>
                <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Please complete your customer profile to save favourites or book appointments.</span>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <div class="filter-bar">
                <!-- Make dropdown -->
                <div class="filter-item" style="min-width:130px;">
                    <label for="makeFilter">Make</label>
                    <select id="makeFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($makes as $make): ?>
                            <option value="<?= htmlspecialchars($make) ?>"><?= htmlspecialchars($make) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Model textbox -->
                <div class="filter-item" style="min-width:140px;">
                    <label for="modelFilter">Model</label>
                    <input type="text" id="modelFilter" class="form-control" placeholder="e.g. Corolla">
                </div>
                <!-- Body dropdown -->
                <div class="filter-item" style="min-width:130px;">
                    <label for="bodyFilter">Body</label>
                    <select id="bodyFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($bodyTypes as $body): ?>
                            <option value="<?= htmlspecialchars($body) ?>"><?= htmlspecialchars($body) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Condition dropdown -->
                <div class="filter-item" style="min-width:130px;">
                    <label for="conditionFilter">Condition</label>
                    <select id="conditionFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($conditionTypes as $cond): ?>
                            <option value="<?= htmlspecialchars($cond) ?>"><?= htmlspecialchars($cond) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-filter" onclick="loadVehicles()"><i class="fas fa-search me-1"></i> Filter</button>
                    <button type="button" class="btn btn-reset" onclick="resetFilters()">Reset</button>
                    <a class="filter-toggle-link" onclick="toggleAdvancedFilters()">
                        <i class="fas fa-sliders-h"></i> More
                    </a>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="advanced-filters" id="advancedFilters">
                <div class="filter-item" style="min-width:130px;">
                    <label for="fuelFilter">Fuel</label>
                    <select id="fuelFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($fuelTypes as $fuel): ?>
                            <option value="<?= htmlspecialchars($fuel) ?>"><?= htmlspecialchars($fuel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item" style="min-width:130px;">
                    <label for="transmissionFilter">Transmission</label>
                    <select id="transmissionFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($transmissions as $trans): ?>
                            <option value="<?= htmlspecialchars($trans) ?>"><?= htmlspecialchars($trans) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-item" style="min-width:140px;">
                    <label>Price Range</label>
                    <div class="d-flex gap-1">
                        <input type="number" id="minPrice" class="form-control" placeholder="Min" step="1000" min="0" style="width:60px;">
                        <span style="line-height:32px;">–</span>
                        <input type="number" id="maxPrice" class="form-control" placeholder="Max" step="1000" min="0" style="width:60px;">
                    </div>
                </div>
                <div class="filter-item" style="min-width:130px;">
                    <label class="d-block">Saved</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="savedOnly" style="margin-left:0;">
                        <label class="form-check-label" for="savedOnly">Only saved</label>
                    </div>
                </div>
            </div>
        </div>

        <div id="vehicleGrid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <div class="col-12 text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>

    </div>
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</div>

<!-- ========== VEHICLE DETAIL MODAL ========== -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Vehicle Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="testDriveModalBtn" class="btn btn-testdrive-modal" target="_blank">
                    <i class="fas fa-road"></i> Book Test Drive
                </a>
                <button type="button" class="btn btn-appointment-modal" id="bookAppointmentBtn" onclick="openAppointmentModal()">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== APPOINTMENT MODAL ========== -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="appointmentForm">
                <div class="modal-body">
                    <div id="appointmentVehicleInfo" class="mb-3"></div>
                    <input type="hidden" name="vehicle_id" id="apptVehicleId">
                    <input type="hidden" name="customer_id" id="apptCustomerId" value="<?= $customerId ?? '' ?>">

                    <div class="mb-3">
                        <label class="form-label">Appointment Type</label>
                        <select name="appointment_type" class="form-select" required>
                            <option value="">Select type...</option>
                            <option value="Vehicle Viewing">Vehicle Viewing</option>
                            <option value="Test Drive">Test Drive</option>
                            <option value="Workshop">Workshop</option>
                            <!-- add more as needed -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" name="appointment_time" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Preferred Employee (optional)</label>
                        <select name="employee_id" class="form-select">
                            <option value="">Any</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['employee_id'] ?>">
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitAppointmentBtn">
                        <span id="apptSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                        Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CUSTOMER_ID = <?= $customerId ? (int) $customerId : 'null' ?>;
let currentVehicleId = null;
let filterDebounce = null;

function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function formatMoney(n) {
    return 'R ' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function toggleAdvancedFilters() {
    document.getElementById('advancedFilters').classList.toggle('show');
}

$(document).ready(function () {
    loadVehicles();

    // Auto-filter on change
    $('#filterForm select').on('change', loadVehicles);
    $('#modelFilter, #minPrice, #maxPrice, #savedOnly').on('input change', function () {
        clearTimeout(filterDebounce);
        filterDebounce = setTimeout(loadVehicles, 400);
    });

    // Appointment form submission
    $('#appointmentForm').on('submit', function (e) {
        e.preventDefault();
        if (!CUSTOMER_ID) {
            alert('You must have a customer profile to book an appointment.');
            return;
        }
        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(item => { data[item.name] = item.value; });

        // Validate required fields
        if (!data.vehicle_id || !data.appointment_type || !data.appointment_date || !data.appointment_time) {
            alert('Please fill in all required fields.');
            return;
        }

        const $btn = $('#submitAppointmentBtn');
        $btn.prop('disabled', true);
        $('#apptSpinner').removeClass('d-none');

        $.ajax({
            url: 'create_appointment.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert('Appointment booked successfully! Reference: ' + response.appointment_id);
                    $('#appointmentModal').modal('hide');
                    $('#detailModal').modal('hide');
                } else {
                    alert('Error: ' + (response.error || 'Unknown error.'));
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error booking appointment.';
                alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#apptSpinner').addClass('d-none');
            }
        });
    });
});

function loadVehicles() {
    const params = {
        make: $('#makeFilter').val(),
        model: $('#modelFilter').val(),
        body_type: $('#bodyFilter').val(),
        fuel_type: $('#fuelFilter').val(),
        transmission: $('#transmissionFilter').val(),
        condition_type: $('#conditionFilter').val(),
        min_price: $('#minPrice').val(),
        max_price: $('#maxPrice').val(),
        saved_only: $('#savedOnly').is(':checked') ? '1' : '0'
    };

    $.ajax({
        url: 'get_vehicles.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function (vehicles) {
            if (vehicles && vehicles.error) {
                $('#vehicleGrid').html('<div class="col-12"><div class="alert alert-danger">' + esc(vehicles.error) + '</div></div>');
                return;
            }
            renderVehicles(vehicles);
        },
        error: function (xhr) {
            if (xhr.status === 401) {
                window.location.href = '../../login.php';
                return;
            }
            $('#vehicleGrid').html('<div class="col-12"><div class="alert alert-danger">Failed to load vehicles. Please refresh and try again.</div></div>');
        }
    });
}

function renderVehicles(vehicles) {
    const grid = $('#vehicleGrid');
    if (!vehicles || vehicles.length === 0) {
        grid.html('<div class="col-12 no-vehicles"><i class="fas fa-car fa-3x mb-3"></i><h5>No vehicles match your criteria</h5></div>');
        return;
    }

    let html = '';
    vehicles.forEach(v => {
        const price = parseFloat(v.price).toFixed(2);
        const primaryImg = v.primary_image ? '../../' + v.primary_image : '../../assets/images/no-image.png';
        const mileageText = (v.mileage !== null && v.mileage !== undefined) ? Number(v.mileage).toLocaleString() + ' km' : 'N/A';
        const conditionBadge = v.condition_type ? `<span class="badge-condition">${esc(v.condition_type)}</span>` : '';
        const savedClass = v.saved ? 'saved' : '';

        html += `
            <div class="col">
                <div class="vehicle-card">
                    <div class="card-img-wrapper">
                        <img src="${esc(primaryImg)}" class="card-img-top" alt="${esc(v.make)} ${esc(v.model)}">
                        ${conditionBadge}
                        <div class="card-overlay">
                            <button class="btn-view" onclick="event.stopPropagation(); viewVehicle(${parseInt(v.vehicle_id, 10)})">
                                <i class="fas fa-eye me-1"></i> Quick View
                            </button>
                            <a href="test_drive_book.php?vehicle_id=${parseInt(v.vehicle_id, 10)}" class="btn-testdrive" target="_blank">
                                <i class="fas fa-road me-1"></i> Test Drive
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title mb-0">${esc(v.make)} ${esc(v.model)}</h5>
                            <i class="fas fa-heart heart-icon ${savedClass}" 
                               data-vehicle-id="${v.vehicle_id}" 
                               onclick="event.stopPropagation(); toggleSave(this, ${v.vehicle_id})"></i>
                        </div>
                        <div class="card-subtitle">${esc(v.manufacture_year)}</div>
                        <div class="vehicle-price">${formatMoney(price)}</div>
                        <div class="vehicle-specs">
                            <span><i class="fas fa-tachometer-alt me-1"></i>${mileageText}</span>
                            <span><i class="fas fa-car me-1"></i>${esc(v.body_type)}</span>
                            <span><i class="fas fa-gas-pump me-1"></i>${esc(v.fuel_type)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    grid.html(html);
}

function viewVehicle(vehicleId) {
    currentVehicleId = vehicleId;

    // Record viewing (if customer exists)
    if (CUSTOMER_ID) {
        $.ajax({
            url: 'record_viewing.php',
            method: 'POST',
            data: { vehicle_id: vehicleId },
            dataType: 'json'
        });
    }

    $.ajax({
        url: 'get_vehicle.php',
        method: 'GET',
        data: { vehicle_id: vehicleId },
        dataType: 'json',
        success: function (data) {
            if (data.error) {
                alert(data.error);
                return;
            }
            const v = data.vehicle;
            const images = data.images || [];
            const mileageText = (v.mileage !== null && v.mileage !== undefined) ? Number(v.mileage).toLocaleString() + ' km' : 'N/A';

            // Build detail HTML
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div id="detailCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                ${images.length ? images.map((img, idx) => `
                                    <div class="carousel-item ${idx === 0 ? 'active' : ''}">
                                        <img src="../../${esc(img.image_path)}" class="d-block w-100" alt="${esc(img.image_title || 'Vehicle')}">
                                    </div>
                                `).join('') : `
                                    <div class="carousel-item active">
                                        <img src="https://via.placeholder.com/800x350?text=No+Images" class="d-block w-100" alt="No image">
                                    </div>
                                `}
                            </div>
                            ${images.length > 1 ? `
                                <button class="carousel-control-prev" type="button" data-bs-target="#detailCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#detailCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-start">
                            <h3>${esc(v.make)} ${esc(v.model)}</h3>
                            <i class="fas fa-heart heart-icon ${v.saved ? 'saved' : ''}" 
                               data-vehicle-id="${v.vehicle_id}" 
                               onclick="toggleSave(this, ${v.vehicle_id})"
                               style="font-size:2rem;"></i>
                        </div>
                        <p><span class="detail-label">Year:</span> ${esc(v.manufacture_year)}</p>
                        <p><span class="detail-label">Price:</span> <strong>${formatMoney(v.price)}</strong></p>
                        <p><span class="detail-label">Mileage:</span> ${mileageText}</p>
                        <p><span class="detail-label">Body Type:</span> ${esc(v.body_type)}</p>
                        <p><span class="detail-label">Fuel:</span> ${esc(v.fuel_type)}</p>
                        <p><span class="detail-label">Transmission:</span> ${esc(v.transmission)}</p>
                        <p><span class="detail-label">Drivetrain:</span> ${esc(v.drivetrain)}</p>
                        <p><span class="detail-label">Engine Size:</span> ${esc(v.engine_size || 'N/A')}</p>
                        <p><span class="detail-label">Colour:</span> ${esc(v.colour || 'N/A')}</p>
                        <p><span class="detail-label">Condition:</span> ${esc(v.condition_type)}</p>
                        <p><span class="detail-label">Status:</span> <span class="badge bg-${v.status === 'Available' ? 'success' : 'danger'}">${esc(v.status)}</span></p>
                        ${v.description ? `<p><span class="detail-label">Description:</span><br>${esc(v.description)}</p>` : ''}
                    </div>
                </div>
            `;
            $('#detailModalBody').html(html);

            // Set up Test Drive button
            const testDriveBtn = document.getElementById('testDriveModalBtn');
            testDriveBtn.href = `test_drive_book.php?vehicle_id=${vehicleId}`;

            // Set up Appointment button
            const apptBtn = document.getElementById('bookAppointmentBtn');
            if (CUSTOMER_ID) {
                apptBtn.style.display = 'inline-block';
                // Store vehicle id for appointment modal
                $('#apptVehicleId').val(vehicleId);
                $('#apptCustomerId').val(CUSTOMER_ID);
                $('#appointmentVehicleInfo').html(`<strong>${esc(v.make)} ${esc(v.model)}</strong> (${esc(v.manufacture_year)})`);
            } else {
                apptBtn.style.display = 'none';
                // Show a message that they need a profile
                $('#detailModalBody').append('<div class="alert alert-info mt-3">Please complete your customer profile to book appointments.</div>');
            }

            $('#detailModal').modal('show');
        },
        error: function (xhr) {
            if (xhr.status === 401) {
                window.location.href = '../../login.php';
                return;
            }
            const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error loading vehicle details.';
            alert(msg);
        }
    });
}

function toggleSave(element, vehicleId) {
    if (!CUSTOMER_ID) {
        alert('Please complete your customer profile to save vehicles.');
        return;
    }
    const $icon = $(element);
    const currentlySaved = $icon.hasClass('saved');
    $icon.toggleClass('saved');
    $('.heart-icon[data-vehicle-id="' + vehicleId + '"]').toggleClass('saved');

    $.ajax({
        url: 'toggle_save.php',
        method: 'POST',
        data: { vehicle_id: vehicleId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const newState = response.action === 'saved';
                $('.heart-icon[data-vehicle-id="' + vehicleId + '"]').each(function() {
                    $(this).toggleClass('saved', newState);
                });
                if ($('#savedOnly').is(':checked')) {
                    loadVehicles();
                }
            } else {
                alert('Error: ' + response.error);
                $('.heart-icon[data-vehicle-id="' + vehicleId + '"]').each(function() {
                    $(this).toggleClass('saved', currentlySaved);
                });
            }
        },
        error: function() {
            alert('Error toggling save.');
            $('.heart-icon[data-vehicle-id="' + vehicleId + '"]').each(function() {
                $(this).toggleClass('saved', currentlySaved);
            });
        }
    });
}

function openAppointmentModal() {
    if (!CUSTOMER_ID) {
        alert('You must have a customer profile to book an appointment.');
        return;
    }
    if (!currentVehicleId) {
        alert('No vehicle selected.');
        return;
    }
    // Pre-fill vehicle info again (already set in viewVehicle)
    $('#appointmentModal').modal('show');
}

function resetFilters() {
    $('#filterForm')[0].reset();
    $('#modelFilter').val('');
    loadVehicles();
}
</script>
</body>
</html>