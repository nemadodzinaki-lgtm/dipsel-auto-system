<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, role: string} $currentUser */

$vehicle_id = (int) ($_GET['id'] ?? 0);
if (!$vehicle_id) {
    die('Vehicle ID required.');
}

// Fetch vehicle details (with primary image)
$stmt = $pdo->prepare("
    SELECT v.*, 
           (SELECT vi.image_path FROM vehicle_images vi 
            WHERE vi.vehicle_id = v.vehicle_id AND vi.is_primary = 'yes' 
            LIMIT 1) AS primary_image
    FROM vehicles v
    WHERE v.vehicle_id = ?
");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$vehicle) {
    die('Vehicle not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Details – <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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

        /* ── Summary card ── */
        .vehicle-thumb {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 12px;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }

        /* ── Tabs ── */
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
        }

        /* ── Tables ── */
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

        /* ── Responsive fine‑tune (same as dashboard) ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            /* Header: stack */
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
            }
            /* Tabs: wrap and smaller font */
            .nav-tabs .nav-link {
                font-size: 0.85rem;
                padding: 0.5rem 0.75rem;
            }
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-item {
                white-space: nowrap;
            }
            /* Summary card: image on top */
            .summary-row > .col-md-3,
            .summary-row > .col-md-9 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .vehicle-thumb {
                max-height: 200px;
            }
            /* Modals */
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
            /* Container */
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .vehicle-thumb {
                max-height: 150px;
            }
            .table-img {
                width: 50px;
                height: 40px;
            }
            .badge-status {
                font-size: 0.7rem;
                padding: 0.3rem 0.7rem;
            }
            .action-btn {
                padding: 0.2rem 0.6rem;
                font-size: 0.7rem;
            }
            .nav-tabs .nav-link {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <!-- Header with back button -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-car text-primary me-2"></i>Vehicle Details</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['manufacture_year'] . ')') ?>
                    – Stock #<?= htmlspecialchars($vehicle['stock_number']) ?>
                </p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
            </div>
        </div>

        <!-- Vehicle Summary Card (now responsive) -->
        <div class="row g-4 mb-4 summary-row">
            <div class="col-md-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <?php $img = !empty($vehicle['primary_image']) ? '../../' . $vehicle['primary_image'] : '../../assets/uploads/vehicles/default.png'; ?>
                        <img src="<?= htmlspecialchars($img) ?>" class="vehicle-thumb" alt="Vehicle" onerror="this.src='../../assets/uploads/vehicles/default.png'">
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Stock Number:</strong> <?= htmlspecialchars($vehicle['stock_number']) ?></p>
                                <p><strong>VIN:</strong> <?= htmlspecialchars($vehicle['vin']) ?></p>
                                <p><strong>Registration:</strong> <?= htmlspecialchars($vehicle['registration_number'] ?? 'N/A') ?></p>
                                <p><strong>Make/Model:</strong> <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></p>
                                <p><strong>Variant:</strong> <?= htmlspecialchars($vehicle['variant'] ?? 'N/A') ?></p>
                                <p><strong>Year:</strong> <?= htmlspecialchars($vehicle['manufacture_year']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Colour:</strong> <?= htmlspecialchars($vehicle['colour'] ?? 'N/A') ?></p>
                                <p><strong>Mileage:</strong> <?= number_format($vehicle['mileage'] ?? 0) ?> km</p>
                                <p><strong>Price:</strong> <span class="fw-bold">R <?= number_format($vehicle['price'], 2) ?></span></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?= match($vehicle['status']) { 'available' => 'success', 'sold' => 'danger', 'reserved' => 'warning', default => 'secondary' } ?>">
                                        <?= ucfirst($vehicle['status']) ?>
                                    </span>
                                </p>
                                <p><strong>Location:</strong> <?= htmlspecialchars($vehicle['current_location']) ?></p>
                                <p><strong>Added:</strong> <?= date('Y-m-d H:i', strtotime($vehicle['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TABS ========== -->
        <ul class="nav nav-tabs mb-4" id="vehicleTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images">Images</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents">Documents</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inquiries">Inquiries</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inspections">Inspections</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-maintenance">Maintenance</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-owners">Owners</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-registrations">Registrations</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-viewings">Viewings</button></li>
        </ul>

        <div class="tab-content">
            <!-- ========================================================== -->
            <!-- OVERVIEW TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade show active" id="tab-overview">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Full Vehicle Details</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2">Specifications</h6>
                                <p><strong>Body Type:</strong> <?= htmlspecialchars($vehicle['body_type'] ?? 'N/A') ?></p>
                                <p><strong>Fuel Type:</strong> <?= htmlspecialchars($vehicle['fuel_type'] ?? 'N/A') ?></p>
                                <p><strong>Transmission:</strong> <?= htmlspecialchars($vehicle['transmission'] ?? 'N/A') ?></p>
                                <p><strong>Drivetrain:</strong> <?= htmlspecialchars($vehicle['drivetrain'] ?? 'N/A') ?></p>
                                <p><strong>Engine Size:</strong> <?= htmlspecialchars($vehicle['engine_size'] ?? 'N/A') ?></p>
                                <p><strong>Engine Number:</strong> <?= htmlspecialchars($vehicle['engine_number'] ?? 'N/A') ?></p>
                                <p><strong>Doors:</strong> <?= htmlspecialchars($vehicle['doors'] ?? 'N/A') ?></p>
                                <p><strong>Seats:</strong> <?= htmlspecialchars($vehicle['seats'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2">Condition & History</h6>
                                <p><strong>Condition:</strong> <?= htmlspecialchars($vehicle['condition_type'] ?? 'N/A') ?></p>
                                <p><strong>Service History:</strong> <?= htmlspecialchars($vehicle['service_history'] ?? 'N/A') ?></p>
                                <p><strong>Accident History:</strong> <?= htmlspecialchars($vehicle['accident_history'] ?? 'N/A') ?></p>
                                <p><strong>Damage Status:</strong> <?= htmlspecialchars($vehicle['damage_status'] ?? 'N/A') ?></p>
                                <p><strong>Damage Description:</strong> <?= htmlspecialchars($vehicle['damage_description'] ?? 'N/A') ?></p>
                                <p><strong>Roadworthy:</strong> <?= htmlspecialchars($vehicle['roadworthy'] ?? 'N/A') ?></p>
                                <p><strong>Warranty:</strong> <?= htmlspecialchars($vehicle['warranty'] ?? 'N/A') ?></p>
                                <p><strong>Advertised:</strong> <?= htmlspecialchars($vehicle['advertised'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-12 mt-3">
                                <h6 class="border-bottom pb-2">Description</h6>
                                <p><?= nl2br(htmlspecialchars($vehicle['description'] ?? 'No description')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- IMAGES TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-images">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-images me-2"></i>Vehicle Images</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openImageModal()"><i class="fas fa-plus me-1"></i> Add Image</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="imagesTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Image</th><th>Title</th><th>Type</th><th>Primary</th><th>Order</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- DOCUMENTS TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-documents">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Documents</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openDocumentModal()"><i class="fas fa-plus me-1"></i> Add Document</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="documentsTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Name</th><th>Type</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- INQUIRIES TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-inquiries">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Inquiries</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openInquiryModal()"><i class="fas fa-plus me-1"></i> Add Inquiry</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="inquiriesTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Customer</th><th>Subject</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- INSPECTIONS TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-inspections">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Inspections</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openInspectionModal()"><i class="fas fa-plus me-1"></i> Add Inspection</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="inspectionsTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Date</th><th>Type</th><th>Odometer</th><th>Condition</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- MAINTENANCE TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-maintenance">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Maintenance History</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openMaintenanceModal()"><i class="fas fa-plus me-1"></i> Add Maintenance</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="maintenanceTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Date</th><th>Type</th><th>Odometer</th><th>Cost</th><th>Next Due</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- OWNERS TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-owners">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>Owners</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openOwnerModal()"><i class="fas fa-plus me-1"></i> Add Owner</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="ownersTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Person</th><th>Type</th><th>Purchase Date</th><th>Purchase Price</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- REGISTRATIONS TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-registrations">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Registrations</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openRegistrationModal()"><i class="fas fa-plus me-1"></i> Add Registration</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="registrationsTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Reg No</th><th>Date</th><th>Expiry</th><th>Authority</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================== -->
            <!-- VIEWINGS TAB -->
            <!-- ========================================================== -->
            <div class="tab-pane fade" id="tab-viewings">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Viewings</h5>
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="openViewingModal()"><i class="fas fa-plus me-1"></i> Add Viewing</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="viewingsTable" class="table table-striped table-hover align-middle">
                                <thead><tr><th>Customer</th><th>Date/Time</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- ========================================================== -->
<!-- MODALS (each one is a separate modal for its table) -->
<!-- ========================================================== -->

<!-- IMAGE MODAL -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="imageModalTitle">Add Image</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="imageForm" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="image_id" id="image_id" value="0">
                <input type="hidden" name="action" id="imageAction" value="add_image">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Image File</label><input type="file" name="image_file" id="image_file" class="form-control" accept="image/*" required></div>
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" name="image_title" id="image_title" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Type</label>
                        <select name="image_type" id="image_type" class="form-select">
                            <option value="Front">Front</option><option value="Rear">Rear</option>
                            <option value="Left Side">Left Side</option><option value="Right Side">Right Side</option>
                            <option value="Interior">Interior</option><option value="Engine">Engine</option><option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><div class="form-check"><input type="checkbox" name="is_primary" id="image_is_primary" value="yes" class="form-check-input"><label class="form-check-label">Set as primary</label></div></div>
                    <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" id="image_display_order" class="form-control" value="1"></div>
                    <div id="imagePreviewContainer" style="display:none;"><img id="imagePreview" src="" class="img-thumbnail" style="max-height:150px;"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="imageSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- DOCUMENT MODAL -->
<div class="modal fade" id="documentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="documentModalTitle">Add Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="documentForm" enctype="multipart/form-data">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="document_id" id="document_id" value="0">
                <input type="hidden" name="action" id="documentAction" value="add_document">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Document Name</label><input type="text" name="document_name" id="document_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Type</label>
                        <select name="document_type" id="document_type" class="form-select">
                            <option value="Registration Certificate">Registration Certificate</option>
                            <option value="Roadworthy Certificate">Roadworthy Certificate</option>
                            <option value="Service History">Service History</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">File</label><input type="file" name="document_file" id="document_file" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" id="document_expiry_date" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select name="status" id="document_status" class="form-select">
                            <option value="Valid">Valid</option><option value="Expired">Expired</option><option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="documentSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- INQUIRY MODAL -->
<div class="modal fade" id="inquiryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="inquiryModalTitle">Add Inquiry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="inquiryForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="enquiry_id" id="inquiry_id" value="0">
                <input type="hidden" name="action" id="inquiryAction" value="add_inquiry">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Customer</label>
                        <select name="customer_id" id="inquiry_customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php $customers = $pdo->query("SELECT customer_id, customer_number FROM customers ORDER BY customer_number");
                            while ($c = $customers->fetch()) echo '<option value="'.$c['customer_id'].'">'.htmlspecialchars($c['customer_number']).'</option>'; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label required">Subject</label><input type="text" name="subject" id="inquiry_subject" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label required">Message</label><textarea name="message" id="inquiry_message" rows="3" class="form-control" required></textarea></div>
                    <div class="mb-3"><label class="form-label">Status</label>
                        <select name="enquiry_status" id="inquiry_status" class="form-select">
                            <option value="New">New</option><option value="In Progress">In Progress</option>
                            <option value="Responded">Responded</option><option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Assigned Employee</label>
                        <select name="assigned_employee_id" id="inquiry_employee_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php $emps = $pdo->query("SELECT employee_id, first_name, last_name FROM employees e JOIN persons p ON e.person_id = p.person_id");
                            while ($e = $emps->fetch()) echo '<option value="'.$e['employee_id'].'">'.htmlspecialchars($e['first_name'].' '.$e['last_name']).'</option>'; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Response</label><textarea name="response" id="inquiry_response" rows="2" class="form-control" placeholder="Optional reply"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="inquirySaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- INSPECTION MODAL -->
<div class="modal fade" id="inspectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="inspectionModalTitle">Add Inspection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="inspectionForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="inspection_id" id="inspection_id" value="0">
                <input type="hidden" name="action" id="inspectionAction" value="add_inspection">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label required">Inspection Date</label><input type="date" name="inspection_date" id="inspection_date" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Type</label>
                                <select name="inspection_type" id="inspection_type" class="form-select">
                                    <option value="Pre-Sale">Pre-Sale</option><option value="Roadworthy">Roadworthy</option>
                                    <option value="Workshop">Workshop</option><option value="Insurance">Insurance</option><option value="General">General</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Odometer Reading</label><input type="number" name="odometer_reading" id="inspection_odometer" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Overall Condition</label>
                                <select name="overall_condition" id="inspection_condition" class="form-select">
                                    <option value="Excellent">Excellent</option><option value="Good">Good</option><option value="Fair">Fair</option><option value="Poor">Poor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Brakes</label><select name="brakes" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Tyres</label><select name="tyres" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Suspension</label><select name="suspension" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Engine</label><select name="engine" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Transmission</label><select name="transmission" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Electrical</label><select name="electrical" class="form-select"><option value="Pass">Pass</option><option value="Fail">Fail</option></select></div>
                            <div class="mb-3"><label class="form-label">Next Inspection Due</label><input type="date" name="next_inspection_due" id="inspection_next_due" class="form-control"></div>
                        </div>
                        <div class="col-12"><div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="inspection_notes" rows="2" class="form-control"></textarea></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="inspectionSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MAINTENANCE MODAL -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="maintenanceModalTitle">Add Maintenance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="maintenanceForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="maintenance_id" id="maintenance_id" value="0">
                <input type="hidden" name="action" id="maintenanceAction" value="add_maintenance">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label required">Maintenance Date</label><input type="date" name="maintenance_date" id="maintenance_date" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Type</label>
                                <select name="maintenance_type" id="maintenance_type" class="form-select">
                                    <option value="Minor Service">Minor Service</option><option value="Major Service">Major Service</option>
                                    <option value="Repair">Repair</option><option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Odometer Reading</label><input type="number" name="odometer_reading" id="maintenance_odometer" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Total Cost (R)</label><input type="number" step="0.01" name="total_cost" id="maintenance_cost" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Next Service Due</label><input type="date" name="next_service_due" id="maintenance_next_due" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Serviced By Employee</label>
                                <select name="serviced_by_employee_id" id="maintenance_employee_id" class="form-select">
                                    <option value="">Select Employee</option>
                                    <?php $emps = $pdo->query("SELECT employee_id, first_name, last_name FROM employees e JOIN persons p ON e.person_id = p.person_id");
                                    while ($e = $emps->fetch()) echo '<option value="'.$e['employee_id'].'">'.htmlspecialchars($e['first_name'].' '.$e['last_name']).'</option>'; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Job Card ID (if linked)</label><input type="number" name="job_card_id" id="maintenance_job_card" class="form-control" placeholder="Optional"></div>
                            <div class="mb-3"><label class="form-label">Service Booking ID</label><input type="number" name="service_booking_id" id="maintenance_booking" class="form-control" placeholder="Optional"></div>
                        </div>
                        <div class="col-12"><div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="maintenance_description" rows="2" class="form-control"></textarea></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="maintenanceSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- OWNER MODAL -->
<div class="modal fade" id="ownerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="ownerModalTitle">Add Owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="ownerForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="owner_id" id="owner_id" value="0">
                <input type="hidden" name="action" id="ownerAction" value="add_owner">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Person</label>
                        <select name="person_id" id="owner_person_id" class="form-select" required>
                            <option value="">Select Person</option>
                            <?php $persons = $pdo->query("SELECT person_id, first_name, last_name FROM persons ORDER BY first_name");
                            while ($p = $persons->fetch()) echo '<option value="'.$p['person_id'].'">'.htmlspecialchars($p['first_name'].' '.$p['last_name']).'</option>'; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Ownership Type</label>
                        <select name="ownership_type" id="owner_type" class="form-select">
                            <option value="Current">Current</option><option value="Previous">Previous</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" id="owner_purchase_date" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Selling Date</label><input type="date" name="selling_date" id="owner_selling_date" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Purchase Price</label><input type="number" step="0.01" name="purchase_price" id="owner_purchase_price" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Selling Price</label><input type="number" step="0.01" name="selling_price" id="owner_selling_price" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Ownership Status</label>
                        <select name="ownership_status" id="owner_status" class="form-select">
                            <option value="Active">Active</option><option value="Transferred">Transferred</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="ownerSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- REGISTRATION MODAL -->
<div class="modal fade" id="registrationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="registrationModalTitle">Add Registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="registrationForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="registration_id" id="registration_id" value="0">
                <input type="hidden" name="action" id="registrationAction" value="add_registration">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label required">Registration Number</label><input type="text" name="registration_number" id="registration_number" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Licence Disc Number</label><input type="text" name="licence_disc_number" id="registration_disc" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Registration Date</label><input type="date" name="registration_date" id="registration_date" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Expiry Date</label><input type="date" name="expiry_date" id="registration_expiry" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Registering Authority</label><input type="text" name="registering_authority" id="registration_authority" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Province</label><input type="text" name="province" id="registration_province" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Status</label>
                                <select name="status" id="registration_status" class="form-select">
                                    <option value="Active">Active</option><option value="Expired">Expired</option><option value="Suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="registrationSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- VIEWING MODAL -->
<div class="modal fade" id="viewingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewingModalTitle">Add Viewing</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="viewingForm">
                <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                <input type="hidden" name="viewing_id" id="viewing_id" value="0">
                <input type="hidden" name="action" id="viewingAction" value="add_viewing">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label required">Customer</label>
                                <select name="customer_id" id="viewing_customer_id" class="form-select" required>
                                    <option value="">Select Customer</option>
                                    <?php $customers = $pdo->query("SELECT customer_id, customer_number FROM customers ORDER BY customer_number");
                                    while ($c = $customers->fetch()) echo '<option value="'.$c['customer_id'].'">'.htmlspecialchars($c['customer_number']).'</option>'; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label required">Date</label><input type="date" name="viewing_date" id="viewing_date" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label required">Time</label><input type="time" name="viewing_time" id="viewing_time" class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" id="viewing_location" class="form-control"></div>
                            <div class="mb-3"><label class="form-label">Status</label>
                                <select name="status" id="viewing_status" class="form-select">
                                    <option value="Pending">Pending</option><option value="Approved">Approved</option>
                                    <option value="Completed">Completed</option><option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Employee</label>
                                <select name="employee_id" id="viewing_employee_id" class="form-select">
                                    <option value="">Select Employee</option>
                                    <?php $emps = $pdo->query("SELECT employee_id, first_name, last_name FROM employees e JOIN persons p ON e.person_id = p.person_id");
                                    while ($e = $emps->fetch()) echo '<option value="'.$e['employee_id'].'">'.htmlspecialchars($e['first_name'].' '.$e['last_name']).'</option>'; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3"><label class="form-label">Customer Notes</label><textarea name="customer_notes" id="viewing_customer_notes" rows="2" class="form-control"></textarea></div>
                            <div class="mb-3"><label class="form-label">Employee Notes</label><textarea name="employee_notes" id="viewing_employee_notes" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" id="viewingSaveBtn">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- SCRIPTS -->
<!-- ========================================================== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
const vehicleId = <?= $vehicle_id ?>;

// ---------- Helper: load DataTable with AJAX ----------
function loadTable(tableId, ajaxUrl, columns, order) {
    return $('#' + tableId).DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: ajaxUrl,
            dataType: 'json',
            dataSrc: '',
            data: { vehicle_id: vehicleId },
            error: function(xhr, error, thrown) {
                console.error('DataTables error for', tableId, ':', error, thrown);
                console.log('Server response:', xhr.responseText);
                alert('Error loading data for ' + tableId + '. Please check the console for details.');
            }
        },
        columns: columns,
        order: order || [[0, 'desc']],
        pageLength: 10,
        language: { search: "Filter:", searchPlaceholder: "Search..." }
    });
}

// ---------- IMAGES ----------
function initImages() {
    window.imagesTable = loadTable('imagesTable', 'vehicle_ajax.php?action=get_images', [
        { data: 'image_path', render: function(data) { return '<img src="../../'+data+'" style="width:80px;height:60px;object-fit:cover;">'; } },
        { data: 'image_title' },
        { data: 'image_type' },
        { data: 'is_primary', render: function(data) { return data=='yes' ? '<span class="badge bg-primary">Primary</span>' : ''; } },
        { data: 'display_order' },
        { data: 'image_id', render: function(data, type, row) {
            let btns = '<button class="btn btn-sm btn-outline-warning me-1" onclick="editImage('+data+')"><i class="fas fa-edit"></i></button>';
            if (row.is_primary == 'yes') {
                btns += '<button class="btn btn-sm btn-outline-danger" disabled><i class="fas fa-trash"></i></button>';
            } else {
                btns += '<button class="btn btn-sm btn-outline-danger" onclick="deleteImage('+data+')"><i class="fas fa-trash"></i></button>';
            }
            return btns;
        }}
    ]);
}
function openImageModal(data) {
    if (data) {
        $('#imageAction').val('update_image');
        $('#image_id').val(data.image_id);
        $('#image_title').val(data.image_title);
        $('#image_type').val(data.image_type);
        $('#image_display_order').val(data.display_order);
        if (data.is_primary == 'yes') $('#image_is_primary').prop('checked', true);
        if (data.image_path) {
            $('#imagePreview').attr('src', '../../' + data.image_path);
            $('#imagePreviewContainer').show();
        }
        $('#imageModalTitle').text('Edit Image');
        $('#imageSaveBtn').text('Update Image');
    } else {
        $('#imageAction').val('add_image');
        $('#image_id').val(0);
        $('#imageForm')[0].reset();
        $('#imagePreviewContainer').hide();
        $('#imageModalTitle').text('Add Image');
        $('#imageSaveBtn').text('Save Image');
    }
    $('#imageModal').modal('show');
}
function editImage(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_image&image_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openImageModal(data);
        },
        error: function() { alert('Error loading image'); }
    });
}
function deleteImage(id) {
    if (!confirm('Delete this image?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_image', image_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { imagesTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#imageForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('vehicle_id', vehicleId);
    $.ajax({
        url: 'vehicle_ajax.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) { $('#imageModal').modal('hide'); imagesTable.ajax.reload(); } else alert(res.error || 'Error');
        }
    });
});

// ---------- DOCUMENTS ----------
function initDocuments() {
    window.documentsTable = loadTable('documentsTable', 'vehicle_ajax.php?action=get_documents', [
        { data: 'document_name' },
        { data: 'document_type' },
        { data: 'expiry_date' },
        { data: 'status', render: function(data) { return '<span class="badge bg-'+{Valid:'success',Expired:'danger',Pending:'warning'}[data]+'">'+data+'</span>'; } },
        {
            data: 'document_id',
            render: function(data, type, row) {
                let btns = '';
                if (row.file_path) {
                    btns += '<a href="../../' + row.file_path + '" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View"><i class="fas fa-eye"></i></a>';
                    btns += '<a href="download_document.php?id=' + data + '" class="btn btn-sm btn-outline-success me-1" title="Download"><i class="fas fa-download"></i></a>';
                }
                btns += '<button class="btn btn-sm btn-outline-warning me-1" onclick="editDocument('+data+')"><i class="fas fa-edit"></i></button>';
                btns += '<button class="btn btn-sm btn-outline-danger" onclick="deleteDocument('+data+')"><i class="fas fa-trash"></i></button>';
                return btns;
            }
        }
    ]);
}
function openDocumentModal(data) {
    if (data) {
        $('#documentAction').val('update_document');
        $('#document_id').val(data.document_id);
        $('#document_name').val(data.document_name);
        $('#document_type').val(data.document_type);
        $('#document_expiry_date').val(data.expiry_date);
        $('#document_status').val(data.status);
        // File input is optional during edit; we leave it as is
        $('#documentModalTitle').text('Edit Document');
        $('#documentSaveBtn').text('Update Document');
    } else {
        $('#documentAction').val('add_document');
        $('#document_id').val(0);
        $('#documentForm')[0].reset();
        $('#documentModalTitle').text('Add Document');
        $('#documentSaveBtn').text('Save Document');
    }
    $('#documentModal').modal('show');
}
function editDocument(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_document&document_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openDocumentModal(data);
        },
        error: function() { alert('Error loading document'); }
    });
}
function deleteDocument(id) {
    if (!confirm('Delete this document?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_document', document_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { documentsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#documentForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    formData.append('vehicle_id', vehicleId);
    $.ajax({
        url: 'vehicle_ajax.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) { $('#documentModal').modal('hide'); documentsTable.ajax.reload(); } else alert(res.error || 'Error');
        }
    });
});

// ---------- INQUIRIES ----------
function initInquiries() {
    window.inquiriesTable = loadTable('inquiriesTable', 'vehicle_ajax.php?action=get_inquiries', [
        { data: 'customer_name' },
        { data: 'subject' },
        { data: 'enquiry_status', render: function(data) { return '<span class="badge bg-'+{New:'secondary','In Progress':'warning',Responded:'info',Closed:'success'}[data]+'">'+data+'</span>'; } },
        { data: 'created_at' },
        { data: 'enquiry_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editInquiry('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteInquiry('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openInquiryModal(data) {
    if (data) {
        $('#inquiryAction').val('update_inquiry');
        $('#inquiry_id').val(data.enquiry_id);
        $('#inquiry_customer_id').val(data.customer_id);
        $('#inquiry_subject').val(data.subject);
        $('#inquiry_message').val(data.message);
        $('#inquiry_status').val(data.enquiry_status);
        $('#inquiry_employee_id').val(data.assigned_employee_id);
        $('#inquiry_response').val(data.response || '');
        $('#inquiryModalTitle').text('Edit Inquiry');
        $('#inquirySaveBtn').text('Update Inquiry');
    } else {
        $('#inquiryAction').val('add_inquiry');
        $('#inquiry_id').val(0);
        $('#inquiryForm')[0].reset();
        $('#inquiryModalTitle').text('Add Inquiry');
        $('#inquirySaveBtn').text('Save Inquiry');
    }
    $('#inquiryModal').modal('show');
}
function editInquiry(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_inquiry&enquiry_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openInquiryModal(data);
        },
        error: function() { alert('Error loading inquiry'); }
    });
}
function deleteInquiry(id) {
    if (!confirm('Delete this inquiry?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_inquiry', enquiry_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { inquiriesTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#inquiryForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#inquiryModal').modal('hide'); inquiriesTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- INSPECTIONS ----------
function initInspections() {
    window.inspectionsTable = loadTable('inspectionsTable', 'vehicle_ajax.php?action=get_inspections', [
        { data: 'inspection_date' },
        { data: 'inspection_type' },
        { data: 'odometer_reading' },
        { data: 'overall_condition' },
        { data: 'inspection_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editInspection('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteInspection('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openInspectionModal(data) {
    if (data) {
        $('#inspectionAction').val('update_inspection');
        $('#inspection_id').val(data.inspection_id);
        $('#inspection_date').val(data.inspection_date);
        $('#inspection_type').val(data.inspection_type);
        $('#inspection_odometer').val(data.odometer_reading);
        $('#inspection_condition').val(data.overall_condition);
        $('[name="brakes"]').val(data.brakes);
        $('[name="tyres"]').val(data.tyres);
        $('[name="suspension"]').val(data.suspension);
        $('[name="engine"]').val(data.engine);
        $('[name="transmission"]').val(data.transmission);
        $('[name="electrical"]').val(data.electrical);
        $('#inspection_notes').val(data.notes);
        $('#inspection_next_due').val(data.next_inspection_due);
        $('#inspectionModalTitle').text('Edit Inspection');
        $('#inspectionSaveBtn').text('Update Inspection');
    } else {
        $('#inspectionAction').val('add_inspection');
        $('#inspection_id').val(0);
        $('#inspectionForm')[0].reset();
        $('#inspectionModalTitle').text('Add Inspection');
        $('#inspectionSaveBtn').text('Save Inspection');
    }
    $('#inspectionModal').modal('show');
}
function editInspection(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_inspection&inspection_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openInspectionModal(data);
        },
        error: function() { alert('Error loading inspection'); }
    });
}
function deleteInspection(id) {
    if (!confirm('Delete this inspection?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_inspection', inspection_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { inspectionsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#inspectionForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#inspectionModal').modal('hide'); inspectionsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- MAINTENANCE ----------
function initMaintenance() {
    window.maintenanceTable = loadTable('maintenanceTable', 'vehicle_ajax.php?action=get_maintenance', [
        { data: 'maintenance_date' },
        { data: 'maintenance_type' },
        { data: 'odometer_reading' },
        { data: 'total_cost', render: function(data) { return 'R '+parseFloat(data).toFixed(2); } },
        { data: 'next_service_due' },
        { data: 'maintenance_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editMaintenance('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteMaintenance('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openMaintenanceModal(data) {
    if (data) {
        $('#maintenanceAction').val('update_maintenance');
        $('#maintenance_id').val(data.maintenance_id);
        $('#maintenance_date').val(data.maintenance_date);
        $('#maintenance_type').val(data.maintenance_type);
        $('#maintenance_odometer').val(data.odometer_reading);
        $('#maintenance_cost').val(data.total_cost);
        $('#maintenance_next_due').val(data.next_service_due);
        $('#maintenance_employee_id').val(data.serviced_by_employee_id);
        $('#maintenance_job_card').val(data.job_card_id);
        $('#maintenance_booking').val(data.service_booking_id);
        $('#maintenance_description').val(data.description);
        $('#maintenanceModalTitle').text('Edit Maintenance');
        $('#maintenanceSaveBtn').text('Update Maintenance');
    } else {
        $('#maintenanceAction').val('add_maintenance');
        $('#maintenance_id').val(0);
        $('#maintenanceForm')[0].reset();
        $('#maintenanceModalTitle').text('Add Maintenance');
        $('#maintenanceSaveBtn').text('Save Maintenance');
    }
    $('#maintenanceModal').modal('show');
}
function editMaintenance(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_maintenance_record&maintenance_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openMaintenanceModal(data);
        },
        error: function() { alert('Error loading maintenance record'); }
    });
}
function deleteMaintenance(id) {
    if (!confirm('Delete this maintenance record?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_maintenance', maintenance_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { maintenanceTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#maintenanceForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#maintenanceModal').modal('hide'); maintenanceTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- OWNERS ----------
function initOwners() {
    window.ownersTable = loadTable('ownersTable', 'vehicle_ajax.php?action=get_owners', [
        { data: 'person_name' },
        { data: 'ownership_type' },
        { data: 'purchase_date' },
        { data: 'purchase_price', render: function(data) { return data ? 'R '+parseFloat(data).toFixed(2) : ''; } },
        { data: 'owner_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editOwner('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteOwner('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openOwnerModal(data) {
    if (data) {
        $('#ownerAction').val('update_owner');
        $('#owner_id').val(data.owner_id);
        $('#owner_person_id').val(data.person_id);
        $('#owner_type').val(data.ownership_type);
        $('#owner_purchase_date').val(data.purchase_date);
        $('#owner_selling_date').val(data.selling_date);
        $('#owner_purchase_price').val(data.purchase_price);
        $('#owner_selling_price').val(data.selling_price);
        $('#owner_status').val(data.ownership_status);
        $('#ownerModalTitle').text('Edit Owner');
        $('#ownerSaveBtn').text('Update Owner');
    } else {
        $('#ownerAction').val('add_owner');
        $('#owner_id').val(0);
        $('#ownerForm')[0].reset();
        $('#ownerModalTitle').text('Add Owner');
        $('#ownerSaveBtn').text('Save Owner');
    }
    $('#ownerModal').modal('show');
}
function editOwner(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_owner&owner_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openOwnerModal(data);
        },
        error: function() { alert('Error loading owner'); }
    });
}
function deleteOwner(id) {
    if (!confirm('Delete this owner record?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_owner', owner_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { ownersTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#ownerForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#ownerModal').modal('hide'); ownersTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- REGISTRATIONS ----------
function initRegistrations() {
    window.registrationsTable = loadTable('registrationsTable', 'vehicle_ajax.php?action=get_registrations', [
        { data: 'registration_number' },
        { data: 'registration_date' },
        { data: 'expiry_date' },
        { data: 'registering_authority' },
        { data: 'status', render: function(data) { return '<span class="badge bg-'+{Active:'success',Expired:'danger',Suspended:'warning'}[data]+'">'+data+'</span>'; } },
        { data: 'registration_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editRegistration('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteRegistration('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openRegistrationModal(data) {
    if (data) {
        $('#registrationAction').val('update_registration');
        $('#registration_id').val(data.registration_id);
        $('#registration_number').val(data.registration_number);
        $('#registration_disc').val(data.licence_disc_number);
        $('#registration_date').val(data.registration_date);
        $('#registration_expiry').val(data.expiry_date);
        $('#registration_authority').val(data.registering_authority);
        $('#registration_province').val(data.province);
        $('#registration_status').val(data.status);
        $('#registrationModalTitle').text('Edit Registration');
        $('#registrationSaveBtn').text('Update Registration');
    } else {
        $('#registrationAction').val('add_registration');
        $('#registration_id').val(0);
        $('#registrationForm')[0].reset();
        $('#registrationModalTitle').text('Add Registration');
        $('#registrationSaveBtn').text('Save Registration');
    }
    $('#registrationModal').modal('show');
}
function editRegistration(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_registration&registration_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openRegistrationModal(data);
        },
        error: function() { alert('Error loading registration'); }
    });
}
function deleteRegistration(id) {
    if (!confirm('Delete this registration?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_registration', registration_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { registrationsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#registrationForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#registrationModal').modal('hide'); registrationsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- VIEWINGS ----------
function initViewings() {
    window.viewingsTable = loadTable('viewingsTable', 'vehicle_ajax.php?action=get_viewings', [
        { data: 'customer_name' },
        { data: 'viewing_date', render: function(data, type, row) { return data + ' ' + row.viewing_time; } },
        { data: 'location' },
        { data: 'status', render: function(data) { return '<span class="badge bg-'+{Pending:'secondary',Approved:'success',Completed:'info',Cancelled:'danger'}[data]+'">'+data+'</span>'; } },
        { data: 'viewing_id', render: function(data) {
            return '<button class="btn btn-sm btn-outline-warning me-1" onclick="editViewing('+data+')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" onclick="deleteViewing('+data+')"><i class="fas fa-trash"></i></button>';
        }}
    ]);
}
function openViewingModal(data) {
    if (data) {
        $('#viewingAction').val('update_viewing');
        $('#viewing_id').val(data.viewing_id);
        $('#viewing_customer_id').val(data.customer_id);
        $('#viewing_date').val(data.viewing_date);
        $('#viewing_time').val(data.viewing_time);
        $('#viewing_location').val(data.location);
        $('#viewing_status').val(data.status);
        $('#viewing_employee_id').val(data.employee_id);
        $('#viewing_customer_notes').val(data.customer_notes);
        $('#viewing_employee_notes').val(data.employee_notes);
        $('#viewingModalTitle').text('Edit Viewing');
        $('#viewingSaveBtn').text('Update Viewing');
    } else {
        $('#viewingAction').val('add_viewing');
        $('#viewing_id').val(0);
        $('#viewingForm')[0].reset();
        $('#viewingModalTitle').text('Add Viewing');
        $('#viewingSaveBtn').text('Save Viewing');
    }
    $('#viewingModal').modal('show');
}
function editViewing(id) {
    $.ajax({
        url: 'vehicle_ajax.php?action=get_viewing&viewing_id=' + id + '&vehicle_id=' + vehicleId,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert(data.error); return; }
            openViewingModal(data);
        },
        error: function() { alert('Error loading viewing'); }
    });
}
function deleteViewing(id) {
    if (!confirm('Delete this viewing?')) return;
    $.post('vehicle_ajax.php', { action: 'delete_viewing', viewing_id: id, vehicle_id: vehicleId }, function(res) {
        if (res.success) { viewingsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
}
$('#viewingForm').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    data += '&vehicle_id='+vehicleId;
    $.post('vehicle_ajax.php', data, function(res) {
        if (res.success) { $('#viewingModal').modal('hide'); viewingsTable.ajax.reload(); } else alert(res.error || 'Error');
    }, 'json');
});

// ---------- Initialise all tables on page load ----------
$(document).ready(function() {
    initImages();
    initDocuments();
    initInquiries();
    initInspections();
    initMaintenance();
    initOwners();
    initRegistrations();
    initViewings();
});
</script>
</body>
</html>