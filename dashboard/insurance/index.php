<?php
/**
 * Insurance Management – Providers only
 * Full CRUD for insurance providers.
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, role: string} $currentUser */

// ---------- Helper Functions ----------
function sanitizeInput(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ---------- Handle Actions ----------
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$errors = [];

// --- PROVIDER ADD ---
if ($action === 'add_provider' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerName  = sanitizeInput($_POST['provider_name'] ?? '');
    $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
    $phone         = sanitizeInput($_POST['phone'] ?? '');
    $email         = sanitizeInput($_POST['email'] ?? '');
    $address       = sanitizeInput($_POST['address'] ?? '');
    $status        = sanitizeInput($_POST['status'] ?? 'Active');

    if (empty($providerName)) $errors[] = 'Provider name is required.';
    if (empty($phone)) $errors[] = 'Phone is required.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO insurance_providers (provider_name, contact_person, phone, email, address, status) VALUES (:provider_name, :contact_person, :phone, :email, :address, :status)");
            $stmt->execute([
                ':provider_name' => $providerName,
                ':contact_person'=> $contactPerson,
                ':phone'         => $phone,
                ':email'         => $email,
                ':address'       => $address,
                ':status'        => $status
            ]);
            $_SESSION['success'] = "Provider '$providerName' added.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

// --- PROVIDER EDIT ---
if ($action === 'edit_provider' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerId = (int) ($_POST['provider_id'] ?? 0);
    if (!$providerId) { $_SESSION['error'] = 'Invalid provider ID.'; header('Location: index.php'); exit; }

    $providerName  = sanitizeInput($_POST['provider_name'] ?? '');
    $contactPerson = sanitizeInput($_POST['contact_person'] ?? '');
    $phone         = sanitizeInput($_POST['phone'] ?? '');
    $email         = sanitizeInput($_POST['email'] ?? '');
    $address       = sanitizeInput($_POST['address'] ?? '');
    $status        = sanitizeInput($_POST['status'] ?? 'Active');

    if (empty($providerName)) $errors[] = 'Provider name is required.';
    if (empty($phone)) $errors[] = 'Phone is required.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE insurance_providers SET provider_name = :provider_name, contact_person = :contact_person, phone = :phone, email = :email, address = :address, status = :status WHERE provider_id = :provider_id");
            $stmt->execute([
                ':provider_name' => $providerName,
                ':contact_person'=> $contactPerson,
                ':phone'         => $phone,
                ':email'         => $email,
                ':address'       => $address,
                ':status'        => $status,
                ':provider_id'   => $providerId
            ]);
            $_SESSION['success'] = "Provider #$providerId updated.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

// --- PROVIDER DELETE ---
if ($action === 'delete_provider' && isset($_GET['id'])) {
    $providerId = (int) $_GET['id'];
    try {
        $del = $pdo->prepare("DELETE FROM insurance_providers WHERE provider_id = ?");
        $del->execute([$providerId]);
        $_SESSION['success'] = "Provider #$providerId deleted.";
    } catch (Exception $e) {
        $_SESSION['error'] = 'Delete failed: ' . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// ---------- Fetch Data for Display ----------
$totalProviders = $pdo->query("SELECT COUNT(*) FROM insurance_providers")->fetchColumn();
$activeProviders = $pdo->query("SELECT COUNT(*) FROM insurance_providers WHERE status = 'Active'")->fetchColumn();
$providers = $pdo->query("SELECT * FROM insurance_providers ORDER BY provider_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Providers</title>
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

        /* ── Stats Cards ── */
        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            background: #fff;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }

        /* ── Table & Badges ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 0 2px;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 700px;
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
            /* Page header: stack */
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
            }
            /* Stats cards */
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h2 {
                font-size: 1.8rem;
            }
            /* Container */
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
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
            /* Table font */
            .table td, .table th {
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
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .stat-card .card-body {
                padding: 0.8rem 1rem;
            }
            .stat-card h2 {
                font-size: 1.5rem;
            }
            .stat-icon {
                font-size: 1.5rem;
            }
            .table td, .table th {
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
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-building text-primary me-2"></i>Insurance Providers</h2>
                <p class="text-muted">Manage all insurance providers.</p>
            </div>
            <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#providerModal" onclick="openAddProvider()">
                <i class="fas fa-plus me-1"></i> New Provider
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Providers</h6>
                            <h2 class="fw-bold"><?= $totalProviders ?></h2>
                        </div>
                        <div class="stat-icon text-primary"><i class="fas fa-building"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Active</h6>
                            <h2 class="fw-bold text-success"><?= $activeProviders ?></h2>
                        </div>
                        <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Providers Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Providers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="providersTable" class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Provider Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter=1; while($row=$providers->fetch()): 
                            $statusBadge = $row['status'] === 'Active' ? 'success' : 'secondary';
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><strong><?= htmlspecialchars($row['provider_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['contact_person'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-<?= $statusBadge ?> badge-status"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info action-btn" onclick="viewProvider(<?= $row['provider_id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-warning action-btn" onclick="editProvider(<?= $row['provider_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="?action=delete_provider&id=<?= $row['provider_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this provider?')" title="Delete"><i class="fas fa-trash"></i></a>
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

<!-- ============================================================= -->
<!-- MODAL – Provider Add/Edit -->
<!-- ============================================================= -->
<div class="modal fade" id="providerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="providerModalTitle">Add Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="providerForm">
                <input type="hidden" name="action" id="providerAction" value="add_provider">
                <input type="hidden" name="provider_id" id="providerId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Provider Name</label>
                                <input type="text" name="provider_name" id="provider_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Phone</label>
                                <input type="text" name="phone" id="provider_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="provider_email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="provider_address" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="provider_status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="providerSaveBtn">Save Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- VIEW MODAL (shared for providers) -->
<!-- ============================================================= -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Provider Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
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
    $('#providersTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [6] }],
        language: { search: "Filter:", searchPlaceholder: "Search providers..." }
    });
});

// ----- PROVIDER FUNCTIONS -----
function openAddProvider() {
    $('#providerModalTitle').text('Add Provider');
    $('#providerAction').val('add_provider');
    $('#providerId').val(0);
    $('#providerForm')[0].reset();
    $('#providerSaveBtn').text('Save Provider');
    $('#providerModal').modal('show');
}

function editProvider(id) {
    $.ajax({
        url: 'get_provider.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert('Error: ' + data.error); return; }
            $('#providerModalTitle').text('Edit Provider');
            $('#providerAction').val('edit_provider');
            $('#providerId').val(data.provider_id);
            $('#provider_name').val(data.provider_name);
            $('#contact_person').val(data.contact_person);
            $('#provider_phone').val(data.phone);
            $('#provider_email').val(data.email);
            $('#provider_address').val(data.address);
            $('#provider_status').val(data.status);
            $('#providerSaveBtn').text('Update Provider');
            $('#providerModal').modal('show');
        },
        error: function(xhr) {
            let err = 'Error loading provider.';
            try { const resp = JSON.parse(xhr.responseText); if (resp.error) err = resp.error; } catch(e) {}
            alert(err);
        }
    });
}

function viewProvider(id) {
    $.ajax({
        url: 'get_provider.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            if (data.error) { alert('Error: ' + data.error); return; }
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Provider:</strong> ${data.provider_name}</p>
                        <p><strong>Contact Person:</strong> ${data.contact_person || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${data.phone}</p>
                        <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${data.status=='Active'?'success':'secondary'}">${data.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Address:</strong><br>${data.address || 'N/A'}</p>
                        <p><strong>Created:</strong> ${data.created_at}</p>
                    </div>
                </div>
            `;
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
        },
        error: function(xhr) {
            let err = 'Error loading provider details.';
            try { const resp = JSON.parse(xhr.responseText); if (resp.error) err = resp.error; } catch(e) {}
            alert(err);
        }
    });
}
</script>
</body>
</html>