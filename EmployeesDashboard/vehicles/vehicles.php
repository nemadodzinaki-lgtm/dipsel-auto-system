<?php
/**
 * Employee – Vehicle Showroom (Read‑Only)
 * Browse all vehicles with search/filter and view details in a modal.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only employees can access
if ($_SESSION['role'] !== 'Employee') {
    header('Location: login.php');
    exit;
}

$pageTitle = "Vehicle Showroom";

// Fetch all vehicles (or only available ones – change WHERE clause as needed)
// We'll show all but allow filtering by status.
$sql = "
    SELECT
        v.*,
        (SELECT vi.image_path FROM vehicle_images vi
         WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes'
         LIMIT 1) AS primary_image
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
            width: 80px;
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
        .vehicle-thumb {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 12px;
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
                width: 60px;
                height: 45px;
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
            .vehicle-thumb {
                max-height: 200px;
            }
            .detail-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
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
                width: 50px;
                height: 38px;
            }
            .vehicle-thumb {
                max-height: 160px;
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
            .modal-body .row > .col-md-5,
            .modal-body .row > .col-md-7 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .modal-body .row > .col-md-7 {
                margin-top: 1rem;
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
                width: 40px;
                height: 30px;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .vehicle-thumb {
                max-height: 120px;
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
            .modal-footer .btn {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
        }
    </style>
</head>

<body>

    <?php include '../../includes/sidebar.php'; ?>

    <div class="dashboard-wrapper">

        <?php include '../../includes/navbar.php'; ?>

        <div class="container-fluid mt-4 px-4">

            <!-- Page Header removed; starts directly with title and card -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fas fa-car me-2 text-primary"></i>Vehicle Showroom
                </h4>
                <span class="badge bg-secondary">All Vehicles</span>
            </div>

            <!-- Vehicle Table -->
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-list me-2 text-primary"></i>Complete Inventory
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="vehiclesTable" class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Stock No</th>
                                    <th>Make / Model</th>
                                    <th>Year</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; while ($row = $vehicles->fetch()):
                                    $img = !empty($row['primary_image']) ? '../../' . $row['primary_image'] : '../../assets/uploads/vehicles/default.png';
                                    $statusClass = match($row['status'] ?? 'unknown') {
                                        'available' => 'success',
                                        'sold' => 'danger',
                                        'reserved' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><img src="<?= htmlspecialchars($img) ?>" class="table-img" onerror="this.src='../../assets/uploads/vehicles/default.png'"></td>
                                        <td><strong><?= htmlspecialchars($row['stock_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['make'] . ' ' . $row['model']) . ($row['variant'] ? ' (' . $row['variant'] . ')' : '') ?></td>
                                        <td><?= htmlspecialchars($row['manufacture_year']) ?></td>
                                        <td class="fw-bold">R <?= number_format($row['price'], 2) ?></td>
                                        <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= ucfirst($row['status']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewVehicle(<?= $row['vehicle_id'] ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php include '../../includes/footer.php'; ?>

    </div>

    <!-- ========== MODAL: Vehicle Details ========== -->
    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleModalTitle">Vehicle Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="vehicleModalBody">
                    <div id="vehicleDetails">
                        <!-- Content loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Initialise DataTable
        $(document).ready(function() {
            $('#vehiclesTable').DataTable({
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
                    searchPlaceholder: "Search vehicles..."
                }
            });
        });

        // View vehicle details via AJAX
        function viewVehicle(vehicleId) {
            // Show loading indicator
            $('#vehicleDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-2">Loading...</p></div>');
            $('#vehicleModal').modal('show');

            $.ajax({
                url: 'get_vehicle_details.php?id=' + vehicleId,
                dataType: 'json',
                success: function(data) {
                    let html = buildDetailsHtml(data);
                    $('#vehicleDetails').html(html);
                    $('#vehicleModalTitle').text(data.make + ' ' + data.model + ' (' + data.manufacture_year + ')');
                },
                error: function() {
                    $('#vehicleDetails').html('<div class="alert alert-danger">Error loading vehicle details.</div>');
                }
            });
        }

        // Build the HTML for vehicle details
        function buildDetailsHtml(data) {
            let img = data.primary_image ? '../../' + data.primary_image : '../../assets/uploads/vehicles/default.png';
            let statusBadge = data.status ? `<span class="badge bg-${data.status == 'available' ? 'success' : data.status == 'sold' ? 'danger' : 'warning'}">${data.status}</span>` : '';

            let html = `
                <div class="row">
                    <div class="col-md-5">
                        <img src="${img}" class="vehicle-thumb" alt="Vehicle" onerror="this.src='../../assets/uploads/vehicles/default.png'">
                    </div>
                    <div class="col-md-7">
                        <h4>${data.make} ${data.model}</h4>
                        <p><strong>Stock #:</strong> ${data.stock_number} &nbsp;|&nbsp; <strong>Year:</strong> ${data.manufacture_year} &nbsp;|&nbsp; ${statusBadge}</p>
                        <p><strong>Price:</strong> <span class="fw-bold text-success">R ${parseFloat(data.price).toFixed(2)}</span></p>
                        <p><strong>Mileage:</strong> ${data.mileage ? Number(data.mileage).toLocaleString() + ' km' : 'N/A'}</p>
                        <p><strong>Location:</strong> ${data.current_location || 'N/A'}</p>
                        <p><strong>VIN:</strong> ${data.vin || 'N/A'}</p>
                        <p><strong>Colour:</strong> ${data.colour || 'N/A'}</p>
                        <p><strong>Fuel Type:</strong> ${data.fuel_type || 'N/A'} &nbsp;|&nbsp; <strong>Transmission:</strong> ${data.transmission || 'N/A'}</p>
                        <p><strong>Body Type:</strong> ${data.body_type || 'N/A'} &nbsp;|&nbsp; <strong>Drivetrain:</strong> ${data.drivetrain || 'N/A'}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Technical Specifications</h6>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Variant</span><span class="value">${data.variant || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Engine Size</span><span class="value">${data.engine_size || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Engine Number</span><span class="value">${data.engine_number || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Doors</span><span class="value">${data.doors || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Seats</span><span class="value">${data.seats || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Registration</span><span class="value">${data.registration_number || 'N/A'}</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Condition & History</h6>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="label">Condition</span><span class="value">${data.condition_type || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Service History</span><span class="value">${data.service_history || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Accident History</span><span class="value">${data.accident_history || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Damage Status</span><span class="value">${data.damage_status || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Damage Description</span><span class="value">${data.damage_description || 'None'}</span></div>
                            <div class="detail-item"><span class="label">Roadworthy</span><span class="value">${data.roadworthy || 'N/A'}</span></div>
                            <div class="detail-item"><span class="label">Warranty</span><span class="value">${data.warranty || 'N/A'}</span></div>
                        </div>
                    </div>
                </div>
                ${data.description ? `<div class="mt-3"><h6 class="section-title">Description</h6><p>${data.description}</p></div>` : ''}
                <div class="mt-3">
                    <small class="text-muted">Added: ${data.created_at} ${data.updated_at ? '| Updated: ' + data.updated_at : ''}</small>
                </div>
            `;
            return html;
        }
    </script>
</body>
</html>