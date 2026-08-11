<?php
/**
 * Customer – Sell Vehicle
 * Submit a vehicle for sale and track submissions.
 */

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only customers can access
if ($_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit;
}

$personId = (int) $_SESSION['person_id'];
$pageTitle = "Sell Your Vehicle";

// Get customer ID
$stmt = $pdo->prepare("SELECT customer_id, customer_number FROM customers WHERE person_id = ?");
$stmt->execute([$personId]);
$customer = $stmt->fetch();
if (!$customer) {
    die('Customer record not found.');
}
$customerId = $customer['customer_id'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch customer's submissions
$submissions = $pdo->prepare("
    SELECT s.*,
           (SELECT si.image_path FROM submission_images si
            WHERE si.submission_id = s.submission_id AND si.is_primary = 'Yes'
            LIMIT 1) AS primary_image
    FROM customer_vehicle_submissions s
    WHERE s.customer_id = ?
    ORDER BY s.submission_id DESC
");
$submissions->execute([$customerId]);
$submissionList = $submissions->fetchAll(PDO::FETCH_ASSOC);
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 20px;
            padding: 25px 35px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(17, 153, 142, 0.35);
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
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .table-img {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            background: #f8f9fa;
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
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview-container .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            width: 100px;
            height: 100px;
            overflow: hidden;
        }
        .image-preview-container .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-preview-container .preview-item .remove-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 0, 0, 0.7);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
        }
        .image-upload-box {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload-box:hover {
            border-color: #0d6efd;
            background: #f8f9ff;
        }

        /* ========== FIX: Modal scrolling ========== */
        .modal-body-scroll {
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 10px; /* avoid content hiding behind scrollbar */
        }
        /* Ensure footer stays at bottom */
        .modal-footer {
            flex-shrink: 0;
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
                padding: 16px 18px;
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
            .modal-body-scroll {
                max-height: 55vh;
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
            .image-preview-container .preview-item {
                width: 80px;
                height: 80px;
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
            .card-custom .card-header {
                font-size: 0.95rem;
                padding: 12px 14px;
            }
            .card-custom .card-body {
                padding: 12px 14px;
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
                height: 30px;
            }
            .btn-success {
                width: 100%;
                justify-content: center;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            .modal-body-scroll {
                max-height: 50vh;
            }
            .image-preview-container .preview-item {
                width: 70px;
                height: 70px;
            }
            .image-upload-box {
                padding: 15px;
                font-size: 0.9rem;
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
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 14px 16px;
                border-radius: 14px;
            }
            .page-header h2 {
                font-size: 1.1rem;
            }
            .page-header p {
                font-size: 0.85rem;
            }
            .card-custom .card-header {
                font-size: 0.9rem;
                padding: 10px 12px;
            }
            .card-custom .card-body {
                padding: 10px 12px;
            }
            .table th, .table td {
                font-size: 0.7rem;
                padding: 0.2rem 0.15rem;
            }
            .badge-status {
                font-size: 0.6rem;
                padding: 0.15rem 0.4rem;
            }
            .btn-sm {
                padding: 0.1rem 0.3rem;
                font-size: 0.6rem;
            }
            .table-img {
                width: 30px;
                height: 22px;
            }
            .modal-body-scroll {
                max-height: 45vh;
                padding-right: 5px;
            }
            .image-preview-container .preview-item {
                width: 60px;
                height: 60px;
            }
            .image-upload-box {
                padding: 12px;
                font-size: 0.85rem;
            }
            .section-title {
                font-size: 0.9rem;
            }
            .detail-label {
                font-size: 0.75rem;
            }
            .detail-value {
                font-size: 0.8rem;
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

            <div class="page-header">
                <div>
                    <h2><i class="fas fa-car me-2"></i>Sell Your Vehicle</h2>
                    <p>Submit your vehicle for evaluation and track its progress.</p>
                </div>
            </div>

            <!-- Alerts -->
            <div id="alertContainer"></div>

            <!-- Submit Button & Form Modal -->
            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <div class="card card-custom">
                        <div class="card-body text-center py-4">
                            <h5>Ready to sell your car?</h5>
                            <p class="text-muted">Submit your vehicle details and our team will get back to you.</p>
                            <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#submitModal">
                                <i class="fas fa-plus me-1"></i> Submit New Vehicle
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submissions List -->
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-history me-2 text-primary"></i>My Submissions
                </div>
                <div class="card-body">
                    <?php if (count($submissionList) === 0): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            You haven't submitted any vehicles yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="submissionsTable" class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Vehicle</th>
                                        <th>Year</th>
                                        <th>Status</th>
                                        <th>Inspection</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissionList as $row):
                                        $statusColors = [
                                            'pending' => 'secondary',
                                            'inspection_scheduled' => 'info',
                                            'inspection_completed' => 'primary',
                                            'offer_made' => 'warning',
                                            'accepted' => 'success',
                                            'rejected' => 'danger',
                                            'purchased' => 'dark'
                                        ];
                                        $statusLabel = str_replace('_', ' ', ucfirst($row['status']));
                                        $img = !empty($row['primary_image']) ? '../../' . $row['primary_image'] : '../../assets/uploads/vehicles/default.png';
                                    ?>
                                        <tr>
                                            <td>#<?= $row['submission_id'] ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($img) ?>" class="table-img me-2" onerror="this.src='../../assets/uploads/vehicles/default.png'">
                                                <?= htmlspecialchars($row['make'] . ' ' . $row['model']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['manufacture_year']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $statusColors[$row['status']] ?? 'secondary' ?> badge-status">
                                                    <?= $statusLabel ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['inspection_type'] ?? 'N/A') ?></td>
                                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewSubmission(<?= $row['submission_id'] ?>)">
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

    <!-- ========== MODAL: Submit Vehicle (fixed scrolling) ========== -->
    <div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Vehicle for Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="submitForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="customer_id" value="<?= $customerId ?>">
                    <!-- Scrolling body -->
                    <div class="modal-body modal-body-scroll">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Make</label>
                                <input type="text" name="make" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Model</label>
                                <input type="text" name="model" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Year</label>
                                <input type="number" name="manufacture_year" class="form-control" required min="1900" max="<?= date('Y') + 1 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mileage (km)</label>
                                <input type="number" name="mileage" class="form-control" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Colour</label>
                                <input type="text" name="colour" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">VIN</label>
                                <input type="text" name="vin" class="form-control" placeholder="Vehicle Identification Number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Condition Type</label>
                                <select name="condition_type" class="form-select">
                                    <option value="New">New</option>
                                    <option value="Used" selected>Used</option>
                                    <option value="Demo">Demo</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Service History</label>
                                <select name="service_history" class="form-select">
                                    <option value="Full">Full</option>
                                    <option value="Partial">Partial</option>
                                    <option value="None">None</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Accident History</label>
                                <select name="accident_history" class="form-select">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Damage Status</label>
                                <select name="damage_status" class="form-select">
                                    <option value="None">None</option>
                                    <option value="Minor">Minor</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Major">Major</option>
                                    <option value="Write-Off">Write‑Off</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Damage Description</label>
                                <textarea name="damage_description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Inspection Type</label>
                                <select name="inspection_type" class="form-select" required>
                                    <option value="dealership">Bring to Dealership</option>
                                    <option value="on_site">On‑Site Inspection</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preferred Inspection Date</label>
                                <input type="datetime-local" name="inspection_scheduled_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Additional Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Upload Images (max 5)</label>
                                <div class="image-upload-box" id="imageDropZone">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <p class="mb-0">Click or drag images here</p>
                                    <input type="file" name="images[]" id="imageInput" accept="image/*" multiple style="display:none;">
                                </div>
                                <div class="image-preview-container" id="imagePreviewContainer"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Fixed footer with submit button -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="submitBtn">Submit Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========== MODAL: View Submission Details ========== -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submission Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <div id="viewDetails">
                        <!-- AJAX content -->
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
    <script>
        // ---------- Image upload preview ----------
        const dropZone = document.getElementById('imageDropZone');
        const fileInput = document.getElementById('imageInput');
        const previewContainer = document.getElementById('imagePreviewContainer');
        const MAX_IMAGES = 5;
        let selectedFiles = [];

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#0d6efd';
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '#ccc';
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            if (e.dataTransfer.files.length) {
                handleFiles(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                handleFiles(fileInput.files);
                fileInput.value = ''; // reset to allow re-selection
            }
        });

        function handleFiles(files) {
            const remaining = MAX_IMAGES - selectedFiles.length;
            const toAdd = Math.min(files.length, remaining);
            for (let i = 0; i < toAdd; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                }
            }
            renderPreviews();
        }

        function renderPreviews() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-btn" data-index="${index}">&times;</button>
                    `;
                    previewContainer.appendChild(div);
                    div.querySelector('.remove-btn').addEventListener('click', function() {
                        const idx = parseInt(this.dataset.index);
                        selectedFiles.splice(idx, 1);
                        renderPreviews();
                    });
                };
                reader.readAsDataURL(file);
            });
            // Update file input with current list (for form submission)
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(f => dataTransfer.items.add(f));
            fileInput.files = dataTransfer.files;
        }

        // ---------- Display alert ----------
        function showAlert(message, type = 'danger') {
            const container = document.getElementById('alertContainer');
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            container.innerHTML = alertHtml;
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }

        // ---------- Form submit ----------
        $('#submitForm').on('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Submitting...';

            var formData = new FormData(this);
            selectedFiles.forEach((file) => {
                formData.append('images[]', file);
            });

            $.ajax({
                url: 'sell_vehicle_api.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Vehicle';
                    if (res.success) {
                        showAlert(res.message, 'success');
                        $('#submitModal').modal('hide');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert(res.error || 'Submission failed.', 'danger');
                    }
                },
                error: function(xhr) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Vehicle';
                    let msg = 'Error submitting. Please try again.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.error) msg = res.error;
                    } catch (e) {}
                    showAlert(msg, 'danger');
                }
            });
        });

        // ---------- View submission ----------
        function viewSubmission(id) {
            $('#viewDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p>Loading...</p></div>');
            $('#viewModal').modal('show');

            $.ajax({
                url: 'sell_vehicle_api.php?action=get&id=' + id,
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#viewDetails').html('<div class="alert alert-danger">' + data.error + '</div>');
                        return;
                    }
                    let html = buildViewHtml(data);
                    $('#viewDetails').html(html);
                },
                error: function() {
                    $('#viewDetails').html('<div class="alert alert-danger">Error loading details.</div>');
                }
            });
        }

        function buildViewHtml(data) {
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

            let imagesHtml = '';
            if (data.images && data.images.length > 0) {
                imagesHtml = '<div class="row g-2">';
                data.images.forEach(function(img) {
                    let primary = img.is_primary == 'Yes' ? ' <span class="badge bg-primary">Primary</span>' : '';
                    imagesHtml += '<div class="col-md-3 col-6">' +
                        '<img src="../../' + img.image_path + '" class="img-fluid rounded" style="height:120px;object-fit:cover;width:100%;">' +
                        '<div class="small text-muted">' + (img.image_title || img.image_type) + ' ' + primary +
                        '</div>' +
                        '</div>';
                });
                imagesHtml += '</div>';
            } else {
                imagesHtml = '<p class="text-muted">No images uploaded.</p>';
            }

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="section-title">Vehicle Details</h6>
                        <p><span class="detail-label">Make / Model:</span> <span class="detail-value">${data.make} ${data.model}</span></p>
                        <p><span class="detail-label">Year:</span> <span class="detail-value">${data.manufacture_year}</span></p>
                        <p><span class="detail-label">Mileage:</span> <span class="detail-value">${data.mileage ? Number(data.mileage).toLocaleString() + ' km' : 'N/A'}</span></p>
                        <p><span class="detail-label">VIN:</span> <span class="detail-value">${data.vin || 'N/A'}</span></p>
                        <p><span class="detail-label">Colour:</span> <span class="detail-value">${data.colour || 'N/A'}</span></p>
                        <p><span class="detail-label">Condition:</span> <span class="detail-value">${data.condition_type || 'N/A'}</span></p>
                        <p><span class="detail-label">Service History:</span> <span class="detail-value">${data.service_history || 'N/A'}</span></p>
                        <p><span class="detail-label">Accident History:</span> <span class="detail-value">${data.accident_history || 'N/A'}</span></p>
                        <p><span class="detail-label">Damage Status:</span> <span class="detail-value">${data.damage_status || 'None'}</span></p>
                        ${data.damage_description ? `<p><span class="detail-label">Damage Description:</span> <span class="detail-value">${data.damage_description}</span></p>` : ''}
                    </div>
                    <div class="col-md-6">
                        <h6 class="section-title">Submission Status</h6>
                        <p><span class="detail-label">Status:</span> ${statusBadge}</p>
                        <p><span class="detail-label">Inspection Type:</span> <span class="detail-value">${data.inspection_type || 'N/A'}</span></p>
                        <p><span class="detail-label">Scheduled Date:</span> <span class="detail-value">${data.inspection_scheduled_date || 'Not scheduled'}</span></p>
                        <p><span class="detail-label">Inspector:</span> <span class="detail-value">${data.inspector_first_name ? data.inspector_first_name + ' ' + data.inspector_last_name : 'Unassigned'}</span></p>
                        <p><span class="detail-label">Inspection Notes:</span> <span class="detail-value">${data.inspection_notes || 'None'}</span></p>
                        <p><span class="detail-label">Offer Price:</span> <span class="detail-value">${data.offer_price ? 'R ' + parseFloat(data.offer_price).toFixed(2) : 'Not offered'}</span></p>
                        <p><span class="detail-label">Purchase Price:</span> <span class="detail-value">${data.purchase_price ? 'R ' + parseFloat(data.purchase_price).toFixed(2) : 'N/A'}</span></p>
                        <p><span class="detail-label">Decision Date:</span> <span class="detail-value">${data.decision_date || 'N/A'}</span></p>
                        <p><span class="detail-label">Notes:</span> <span class="detail-value">${data.notes || 'None'}</span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6 class="section-title">Images</h6>
                        ${imagesHtml}
                    </div>
                </div>
            `;
            return html;
        }
    </script>
</body>
</html>