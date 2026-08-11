<?php
/**
 * Admin – Customer Vehicle Submissions
 * Manage incoming vehicle sale submissions.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only admins or employees (with appropriate permission) can access


$pageTitle = "Vehicle Submissions";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';
$messageType = '';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
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

        /* ── Modals ── */
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
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .detail-value {
            font-weight: 500;
            color: #1f2937;
        }
        .image-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .status-timeline {
            border-left: 3px solid #e5e7eb;
            padding-left: 1rem;
        }
        .status-timeline .step {
            margin-bottom: 0.8rem;
        }
        .status-timeline .step .step-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            text-align: center;
            line-height: 24px;
            margin-right: 8px;
            font-size: 0.8rem;
        }
        .status-timeline .step.active .step-icon {
            background: #0d6efd;
            color: #fff;
        }
        .status-timeline .step.completed .step-icon {
            background: #10b981;
            color: #fff;
        }
        .select2-container .select2-selection--single {
            height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
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
            .card-custom .card-header {
                padding: 14px 18px;
                font-size: 1rem;
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
            .select2-container .select2-selection--single {
                height: 34px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 34px;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 34px;
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
                padding: 12px 14px;
                font-size: 0.95rem;
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
            .modal-body .row > .col-md-3,
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-12 {
                margin-top: 0.5rem;
            }
            .modal-body .btn {
                width: 100%;
                margin-top: 0.5rem;
            }
            .modal-body form .row .btn {
                width: 100%;
            }
            #updateForm .row .col-12 .btn {
                width: 100%;
            }
            .section-title {
                font-size: 0.95rem;
            }
            .detail-label {
                font-size: 0.8rem;
            }
            .detail-value {
                font-size: 0.85rem;
            }
            .image-thumb {
                width: 60px;
                height: 45px;
            }
            .status-timeline {
                padding-left: 0.5rem;
            }
            .status-timeline .step .step-icon {
                width: 20px;
                height: 20px;
                line-height: 20px;
                font-size: 0.7rem;
            }
            .select2-container .select2-selection--single {
                height: 32px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 32px;
                font-size: 0.85rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 32px;
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
                    <h2><i class="fas fa-clipboard-list me-2"></i>Vehicle Submissions</h2>
                    <p>Manage customer vehicle sale submissions – from pending to purchase.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- List -->
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-list me-2 text-primary"></i>All Submissions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="submissionsTable" class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Inspection Date</th>
                                    <th>Offer</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable will populate via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <?php include '../../includes/footer.php'; ?>

    </div>

    <!-- ========== MODAL: View/Edit Submission ========== -->
    <div class="modal fade" id="submissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submissionModalTitle">Submission Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="submissionModalBody">
                    <div id="submissionDetails">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: Add/Edit Image ========== -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Add Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="imageForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_image">
                    <input type="hidden" name="submission_id" id="image_submission_id" value="0">
                    <input type="hidden" name="image_id" id="image_id" value="0">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Image File</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="image_title" class="form-control" placeholder="e.g. Front view">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="image_type" class="form-select">
                                <option value="Front">Front</option>
                                <option value="Rear">Rear</option>
                                <option value="Left Side">Left Side</option>
                                <option value="Right Side">Right Side</option>
                                <option value="Interior">Interior</option>
                                <option value="Engine">Engine</option>
                                <option value="Boot">Boot</option>
                                <option value="Roof">Roof</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_primary" value="Yes" class="form-check-input" id="image_primary">
                                <label class="form-check-label" for="image_primary">Set as primary</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // DataTable
        let submissionsTable;

        $(document).ready(function() {
            submissionsTable = $('#submissionsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'vehicle_submissions_api.php?action=list',
                    type: 'GET',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'submission_id' },
                    {
                        data: null,
                        render: function(data) {
                            return data.customer_first_name + ' ' + data.customer_last_name;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return data.make + ' ' + data.model + ' (' + data.manufacture_year + ')';
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            const colors = {
                                'pending': 'secondary',
                                'inspection_scheduled': 'info',
                                'inspection_completed': 'primary',
                                'offer_made': 'warning',
                                'accepted': 'success',
                                'rejected': 'danger',
                                'purchased': 'dark'
                            };
                            return '<span class="badge bg-' + (colors[data] || 'secondary') + ' badge-status">' +
                                data.replace(/_/g, ' ').toUpperCase() + '</span>';
                        }
                    },
                    { data: 'inspection_scheduled_date' },
                    {
                        data: 'offer_price',
                        render: function(data) {
                            return data ? 'R ' + parseFloat(data).toFixed(2) : '';
                        }
                    },
                    { data: 'created_at' },
                    {
                        data: 'submission_id',
                        render: function(id) {
                            return '<button class="btn btn-sm btn-outline-primary action-btn" onclick="viewSubmission(' +
                                id + ')"><i class="fas fa-eye"></i></button>';
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                language: {
                    search: "Filter:",
                    searchPlaceholder: "Search..."
                }
            });
        });

        // View submission
        function viewSubmission(id) {
            $('#submissionDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p>Loading...</p></div>');
            $('#submissionModal').modal('show');

            $.ajax({
                url: 'vehicle_submissions_api.php?action=get&id=' + id,
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#submissionDetails').html('<div class="alert alert-danger">' + data.error + '</div>');
                        return;
                    }
                    let html = buildDetailsHtml(data);
                    $('#submissionDetails').html(html);
                    $('#submissionModalTitle').text('Submission #' + data.submission_id + ' – ' + data.make + ' ' +
                        data.model);
                    // Init select2 if present
                    $('.select2-select').select2({
                        theme: 'bootstrap-5',
                        width: '100%'
                    });
                },
                error: function() {
                    $('#submissionDetails').html('<div class="alert alert-danger">Error loading details.</div>');
                }
            });
        }

        // Build details HTML
        function buildDetailsHtml(data) {
            let statusBadge = '<span class="badge bg-' + ({
                'pending': 'secondary',
                'inspection_scheduled': 'info',
                'inspection_completed': 'primary',
                'offer_made': 'warning',
                'accepted': 'success',
                'rejected': 'danger',
                'purchased': 'dark'
            } [data.status] || 'secondary') + ' badge-status">' + data.status.replace(/_/g, ' ').toUpperCase() +
            '</span>';

            // Images
            let imagesHtml = '';
            if (data.images && data.images.length > 0) {
                imagesHtml = '<div class="row g-2">';
                data.images.forEach(function(img) {
                    let primary = img.is_primary == 'Yes' ? ' <span class="badge bg-primary">Primary</span>' : '';
                    imagesHtml += '<div class="col-md-3 col-6">' +
                        '<img src="../../' + img.image_path + '" class="img-fluid rounded" style="height:120px;object-fit:cover;width:100%;">' +
                        '<div class="small text-muted">' + img.image_title + ' ' + primary + '</div>' +
                        '<button class="btn btn-sm btn-outline-danger mt-1" onclick="deleteImage(' + img
                        .image_id + ', ' + data.submission_id + ')"><i class="fas fa-trash"></i></button>' +
                        '</div>';
                });
                imagesHtml += '</div>';
            } else {
                imagesHtml = '<p class="text-muted">No images uploaded.</p>';
            }

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Customer Information</h6>
                        <p><span class="detail-label">Name:</span> <span class="detail-value">${data.customer_first_name} ${data.customer_last_name}</span></p>
                        <p><span class="detail-label">Email:</span> <span class="detail-value">${data.customer_email}</span></p>
                        <p><span class="detail-label">Phone:</span> <span class="detail-value">${data.customer_phone}</span></p>
                        <p><span class="detail-label">Customer #:</span> <span class="detail-value">${data.customer_number}</span></p>
                        <p><span class="detail-label">Driver License:</span> <span class="detail-value">${data.driver_license || 'N/A'}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Vehicle Details</h6>
                        <p><span class="detail-label">Make / Model:</span> <span class="detail-value">${data.make} ${data.model}</span></p>
                        <p><span class="detail-label">Year:</span> <span class="detail-value">${data.manufacture_year}</span></p>
                        <p><span class="detail-label">Mileage:</span> <span class="detail-value">${data.mileage ? Number(data.mileage).toLocaleString() + ' km' : 'N/A'}</span></p>
                        <p><span class="detail-label">VIN:</span> <span class="detail-value">${data.vin || 'N/A'}</span></p>
                        <p><span class="detail-label">Colour:</span> <span class="detail-value">${data.colour || 'N/A'}</span></p>
                        <p><span class="detail-label">Condition:</span> <span class="detail-value">${data.condition_type || 'N/A'}</span></p>
                        <p><span class="detail-label">Service History:</span> <span class="detail-value">${data.service_history || 'N/A'}</span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Submission Status</h6>
                        <p><span class="detail-label">Status:</span> ${statusBadge}</p>
                        <p><span class="detail-label">Inspection Type:</span> <span class="detail-value">${data.inspection_type || 'N/A'}</span></p>
                        <p><span class="detail-label">Scheduled Date:</span> <span class="detail-value">${data.inspection_scheduled_date || 'Not scheduled'}</span></p>
                        <p><span class="detail-label">Inspector:</span> <span class="detail-value">${data.inspector_first_name ? data.inspector_first_name + ' ' + data.inspector_last_name : 'Unassigned'}</span></p>
                        <p><span class="detail-label">Inspection Notes:</span> <span class="detail-value">${data.inspection_notes || 'None'}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Offer & Decision</h6>
                        <p><span class="detail-label">Offer Price:</span> <span class="detail-value">${data.offer_price ? 'R ' + parseFloat(data.offer_price).toFixed(2) : 'Not offered'}</span></p>
                        <p><span class="detail-label">Purchase Price:</span> <span class="detail-value">${data.purchase_price ? 'R ' + parseFloat(data.purchase_price).toFixed(2) : 'N/A'}</span></p>
                        <p><span class="detail-label">Decision Date:</span> <span class="detail-value">${data.decision_date || 'N/A'}</span></p>
                        <p><span class="detail-label">Linked Vehicle ID:</span> <span class="detail-value">${data.vehicle_id ? data.vehicle_id : 'Not yet'}</span></p>
                        <p><span class="detail-label">Notes:</span> <span class="detail-value">${data.notes || 'None'}</span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6 class="section-title">Images</h6>
                        ${imagesHtml}
                        <button class="btn btn-sm btn-primary mt-2" onclick="openImageModal(${data.submission_id})"><i class="fas fa-plus"></i> Add Image</button>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="section-title">Actions</h6>
                        <form id="updateForm" class="row g-3">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="submission_id" value="${data.submission_id}">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending" ${data.status == 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="inspection_scheduled" ${data.status == 'inspection_scheduled' ? 'selected' : ''}>Inspection Scheduled</option>
                                    <option value="inspection_completed" ${data.status == 'inspection_completed' ? 'selected' : ''}>Inspection Completed</option>
                                    <option value="offer_made" ${data.status == 'offer_made' ? 'selected' : ''}>Offer Made</option>
                                    <option value="accepted" ${data.status == 'accepted' ? 'selected' : ''}>Accepted</option>
                                    <option value="rejected" ${data.status == 'rejected' ? 'selected' : ''}>Rejected</option>
                                    <option value="purchased" ${data.status == 'purchased' ? 'selected' : ''}>Purchased</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Inspection Type</label>
                                <select name="inspection_type" class="form-select">
                                    <option value="dealership" ${data.inspection_type == 'dealership' ? 'selected' : ''}>Dealership</option>
                                    <option value="on_site" ${data.inspection_type == 'on_site' ? 'selected' : ''}>On‑Site</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Inspector</label>
                                <select name="inspector_id" class="form-select select2-select">
                                    <option value="">Unassigned</option>
                                    <?php
                                    $emps = $pdo->query("SELECT e.employee_id, p.first_name, p.last_name FROM employees e JOIN persons p ON e.person_id = p.person_id ORDER BY p.first_name");
                                    while ($e = $emps->fetch()):
                                    ?>
                                        <option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Scheduled Date</label>
                                <input type="datetime-local" name="inspection_scheduled_date" class="form-control" value="${data.inspection_scheduled_date ? data.inspection_scheduled_date.replace(' ', 'T') : ''}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inspection Notes</label>
                                <textarea name="inspection_notes" class="form-control" rows="2">${data.inspection_notes || ''}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Offer Price (R)</label>
                                <input type="number" step="0.01" name="offer_price" class="form-control" value="${data.offer_price || ''}">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">${data.notes || ''}</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Submission</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            // After rendering, attach form submit handler
            setTimeout(function() {
                $('#updateForm').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    $.ajax({
                        url: 'vehicle_submissions_api.php',
                        type: 'POST',
                        data: formData,
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                alert(res.message);
                                submissionsTable.ajax.reload();
                                viewSubmission(data.submission_id); // refresh modal
                            } else {
                                alert(res.error || 'Update failed.');
                            }
                        },
                        error: function() {
                            alert('Error updating submission.');
                        }
                    });
                });
            }, 100);

            return html;
        }

        // Open image modal
        function openImageModal(submissionId) {
            $('#image_submission_id').val(submissionId);
            $('#imageModal').modal('show');
        }

        // Delete image
        function deleteImage(imageId, submissionId) {
            if (!confirm('Delete this image?')) return;
            $.ajax({
                url: 'vehicle_submissions_api.php',
                type: 'POST',
                data: {
                    action: 'delete_image',
                    image_id: imageId,
                    submission_id: submissionId,
                    csrf_token: '<?= $csrf_token ?>'
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(res.message);
                        viewSubmission(submissionId);
                    } else {
                        alert(res.error || 'Error deleting image.');
                    }
                }
            });
        }

        // Image form submit
        $('#imageForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'vehicle_submissions_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert(res.message);
                        $('#imageModal').modal('hide');
                        var sid = $('#image_submission_id').val();
                        viewSubmission(sid);
                    } else {
                        alert(res.error || 'Upload failed.');
                    }
                },
                error: function() {
                    alert('Error uploading image.');
                }
            });
        });
    </script>
</body>
</html>