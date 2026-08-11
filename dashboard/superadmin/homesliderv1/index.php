<?php
// ─── Authentication & Database ──────────────────────────────────
require_once '../../../includes/auth.php';
require_once '../../../config/database.php';

// ─── Helper Functions ──────────────────────────────────────────
function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ─── Handle Actions ────────────────────────────────────────────
$action = isset($_GET['action']) ? $_GET['action'] : '';
$slider_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── DELETE ─────────────────────────────────────────────────────
if ($action === 'delete' && $slider_id > 0) {
    $stmt = $pdo->prepare("SELECT image_path FROM homepage_sliders WHERE slider_id = ?");
    $stmt->execute([$slider_id]);
    $slide = $stmt->fetch();
    if ($slide && !empty($slide['image_path']) && file_exists('../../../' . $slide['image_path'])) {
        unlink('../../../' . $slide['image_path']);
    }
    $del = $pdo->prepare("DELETE FROM homepage_sliders WHERE slider_id = ?");
    $del->execute([$slider_id]);
    $_SESSION['success'] = "Slide deleted successfully.";
    redirect('index.php');
}

// ─── ADD / EDIT (form submission) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']);
    $subtitle    = sanitize($_POST['subtitle']);
    $button_text = sanitize($_POST['button_text']);
    $button_link = sanitize($_POST['button_link']);
    $display_order = (int)$_POST['display_order'];
    $is_active   = isset($_POST['is_active']) ? 'Yes' : 'No';
    $start_date  = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date    = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $created_by  = $_SESSION['user_id'] ?? 1; // fallback

    // Image upload
    $uploadDir = '../../../uploads/sliders/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $image_path = '';
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $newName = 'slider_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $newName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $image_path = 'uploads/sliders/' . $newName; // relative to root
            }
        }
    }

    if ($edit_id > 0) {
        // EDIT
        if (empty($image_path)) {
            $stmt = $pdo->prepare("SELECT image_path FROM homepage_sliders WHERE slider_id = ?");
            $stmt->execute([$edit_id]);
            $old = $stmt->fetch();
            $image_path = $old['image_path'] ?? '';
        }
        $sql = "UPDATE homepage_sliders SET
                    title = :title,
                    subtitle = :subtitle,
                    button_text = :button_text,
                    button_link = :button_link,
                    image_path = :image_path,
                    display_order = :display_order,
                    is_active = :is_active,
                    start_date = :start_date,
                    end_date = :end_date,
                    updated_at = NOW()
                WHERE slider_id = :slider_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'        => $title,
            ':subtitle'     => $subtitle,
            ':button_text'  => $button_text,
            ':button_link'  => $button_link,
            ':image_path'   => $image_path,
            ':display_order'=> $display_order,
            ':is_active'    => $is_active,
            ':start_date'   => $start_date,
            ':end_date'     => $end_date,
            ':slider_id'    => $edit_id
        ]);
        $_SESSION['success'] = "Slide updated successfully.";
        redirect('index.php');
    } else {
        // ADD
        $sql = "INSERT INTO homepage_sliders 
                    (title, subtitle, button_text, button_link, image_path, display_order, is_active, start_date, end_date, created_by, created_at) 
                VALUES 
                    (:title, :subtitle, :button_text, :button_link, :image_path, :display_order, :is_active, :start_date, :end_date, :created_by, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title'        => $title,
            ':subtitle'     => $subtitle,
            ':button_text'  => $button_text,
            ':button_link'  => $button_link,
            ':image_path'   => $image_path,
            ':display_order'=> $display_order,
            ':is_active'    => $is_active,
            ':start_date'   => $start_date,
            ':end_date'     => $end_date,
            ':created_by'   => $created_by
        ]);
        $_SESSION['success'] = "Slide added successfully.";
        redirect('index.php');
    }
}

// ─── Fetch all slides ──────────────────────────────────────────
$slides = $pdo->query("SELECT * FROM homepage_sliders ORDER BY display_order ASC, slider_id ASC")->fetchAll();

// ─── Counts ─────────────────────────────────────────────────────
$total = count($slides);
$active = array_reduce($slides, function($carry, $item) { return $carry + ($item['is_active'] === 'Yes' ? 1 : 0); }, 0);
$inactive = $total - $active;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Slider Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .stat-card { border-left: 4px solid #0d6efd; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { font-size: 2rem; opacity: 0.3; }
        .table-img { width: 80px; height: 50px; object-fit: cover; border-radius: 8px; background: #f8f9fa; }
        .badge-status { font-size: 0.85rem; padding: 0.4rem 0.8rem; }
        .action-btn { margin: 0 2px; }
        .modal-lg { max-width: 700px; }
        .form-label.required::after { content: "*"; color: red; margin-left: 4px; }
        .modal-body { max-height: 70vh; overflow-y: auto; }
    </style>
</head>
<body>

<?php include '../../../includes/sidebar.php'; ?>
<div style="margin-left:270px;">
    <?php include '../../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-sliders-h text-primary me-2"></i>Home Slider Management</h2>
                <p class="text-muted">Manage hero slides for the homepage.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#slideModal" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> Add Slide
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card stat-card h-100 shadow-sm"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Total Slides</h6><h2 class="fw-bold"><?= $total ?></h2></div><div class="stat-icon text-primary"><i class="fas fa-images"></i></div></div></div></div>
            <div class="col-md-4"><div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Active</h6><h2 class="fw-bold text-success"><?= $active ?></h2></div><div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div></div></div></div>
            <div class="col-md-4"><div class="card stat-card h-100 shadow-sm" style="border-left-color:#dc3545;"><div class="card-body d-flex align-items-center"><div class="flex-grow-1"><h6 class="text-muted">Inactive</h6><h2 class="fw-bold text-danger"><?= $inactive ?></h2></div><div class="stat-icon text-danger"><i class="fas fa-times-circle"></i></div></div></div></div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3"><h5 class="mb-0"><i class="fas fa-list me-2"></i>All Slides</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="slidesTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Image</th><th>Title</th><th>Subtitle</th><th>Button</th><th>Order</th><th>Status</th><th>Dates</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 1; foreach ($slides as $slide): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <?php if (!empty($slide['image_path']) && file_exists('../../../' . $slide['image_path'])): ?>
                                        <img src="../../../<?= htmlspecialchars($slide['image_path']) ?>" class="table-img" alt="slide">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($slide['title']) ?></strong></td>
                                <td><?= htmlspecialchars($slide['subtitle']) ?></td>
                                <td>
                                    <?php if (!empty($slide['button_text'])): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($slide['button_text']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $slide['display_order'] ?></td>
                                <td>
                                    <span class="badge badge-status bg-<?= $slide['is_active'] === 'Yes' ? 'success' : 'danger' ?>">
                                        <?= $slide['is_active'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($slide['start_date']): ?>
                                        <small>From: <?= date('d M Y', strtotime($slide['start_date'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($slide['end_date']): ?>
                                        <small>To: <?= date('d M Y', strtotime($slide['end_date'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning action-btn" onclick="editSlide(<?= $slide['slider_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="?action=delete&id=<?= $slide['slider_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this slide?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../../includes/footer.php'; ?>
</div>

<!-- ========== MODAL (Add / Edit) ========== -->
<div class="modal fade" id="slideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="slideForm">
                <input type="hidden" name="edit_id" id="edit_id" value="0">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtitle</label>
                        <input type="text" name="subtitle" id="subtitle" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" name="button_text" id="button_text" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Button Link</label>
                            <input type="text" name="button_link" id="button_link" class="form-control" placeholder="/vehicles.php">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image (JPG, PNG, WEBP)</label>
                        <input type="file" name="image" id="image" class="form-control" accept="image/*">
                        <div id="currentImagePreview" class="mt-2" style="display:none;">
                            <img id="imagePreview" src="" class="img-thumbnail" style="max-height:150px;">
                            <small id="currentImageInfo" class="text-muted d-block"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" id="display_order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Active</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                                <label class="form-check-label" for="is_active">Yes (checked = active)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Slide</button>
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
    $('#slidesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [1,8] }],
        language: { search: "Filter:", searchPlaceholder: "Search slides..." }
    });
});

function openAddModal() {
    $('#modalTitle').text('Add Slide');
    $('#edit_id').val(0);
    $('#slideForm')[0].reset();
    $('#currentImagePreview').hide();
    $('#saveBtn').text('Save Slide');
    $('#is_active').prop('checked', true);
    $('#display_order').val(0);
    $('#slideModal').modal('show');
}

function editSlide(id) {
    $.ajax({
        url: 'get_slide.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#modalTitle').text('Edit Slide');
            $('#edit_id').val(data.slider_id);
            $('#title').val(data.title);
            $('#subtitle').val(data.subtitle);
            $('#button_text').val(data.button_text);
            $('#button_link').val(data.button_link);
            $('#display_order').val(data.display_order);
            $('#is_active').prop('checked', data.is_active === 'Yes');
            $('#start_date').val(data.start_date || '');
            $('#end_date').val(data.end_date || '');
            // Image preview – use correct path
            if (data.image_path && data.image_path !== '') {
                $('#imagePreview').attr('src', '../../../' + data.image_path);
                $('#currentImagePreview').show();
                $('#currentImageInfo').text('Current image: ' + data.image_path.split('/').pop());
            } else {
                $('#currentImagePreview').hide();
            }
            $('#saveBtn').text('Update Slide');
            $('#slideModal').modal('show');
        },
        error: function() { alert('Error loading slide data.'); }
    });
}

// Image preview on file selection
$('#image').on('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            $('#imagePreview').attr('src', ev.target.result);
            $('#currentImagePreview').show();
            $('#currentImageInfo').text('New image selected');
        };
        reader.readAsDataURL(file);
    } else {
        $('#currentImagePreview').hide();
    }
});
</script>

</body>
</html>