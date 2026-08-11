<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, last_name: string, role: string} $currentUser */

// ---------- Helper: status badge class ----------
function getStatusBadgeClass($status) {
    $map = [
        'Pending'   => 'bg-warning text-dark',
        'Ordered'   => 'bg-info',
        'Received'  => 'bg-success',
        'Cancelled' => 'bg-danger',
    ];
    return $map[$status] ?? 'bg-secondary';
}

// ---------- Stats for Suppliers ----------
$totalSuppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$activeSuppliers = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'Active'")->fetchColumn();
$inactiveSuppliers = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'Inactive'")->fetchColumn();

// ---------- Stats for Purchase Orders ----------
$totalPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
$pendingPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'Pending'")->fetchColumn();
$receivedPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'Received'")->fetchColumn();

// ---------- Fetch all suppliers (for dropdowns) ----------
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

// ---------- Fetch all purchase orders with supplier names for DataTable ----------
$purchaseOrders = $pdo->query("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.order_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers & Purchase Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Dashboard Wrapper ── */
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

        /* ── Badges ── */
        .status-badge {
            font-size: 0.85rem;
        }

        /* ── Tabs ── */
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 3px solid #0d6efd;
        }

        /* ── Item rows ── */
        .item-row {
            background: #f8f9fa;
            padding: 5px;
            border-radius: 4px;
        }
        .remove-item {
            cursor: pointer;
            color: #dc3545;
        }
        .line-total {
            font-weight: 500;
        }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
                margin-right: 0 !important;
            }
            .page-header .btn:last-child {
                margin-top: 0.5rem;
            }
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h2 {
                font-size: 1.8rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
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
            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .status-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            .btn-sm {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
            }
            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
            .select2-container .select2-selection--single {
                height: 34px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 34px;
                font-size: 0.85rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 34px;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
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
            .table th, .table td {
                font-size: 0.75rem;
                padding: 0.3rem 0.2rem;
            }
            .status-badge {
                font-size: 0.65rem;
                padding: 0.2rem 0.5rem;
            }
            .btn-sm {
                padding: 0.15rem 0.4rem;
                font-size: 0.65rem;
            }
            .modal-body .row > .col-md-6,
            .modal-body .row > .col-md-4 {
                margin-bottom: 0.5rem;
            }
            .modal-body .row > .col-md-6:last-child,
            .modal-body .row > .col-md-4:last-child {
                margin-bottom: 0;
            }
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
            .nav-tabs .nav-link {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-item {
                white-space: nowrap;
            }
            #itemsTable th, #itemsTable td {
                font-size: 0.75rem;
                padding: 0.2rem;
            }
            .remove-item i {
                font-size: 1rem;
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
                <h2><i class="fas fa-truck text-primary me-2"></i>Suppliers & Purchase Orders</h2>
                <p class="text-muted">Manage suppliers and their purchase orders from one place.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="resetSupplierForm()">
                    <i class="fas fa-plus me-1"></i> Add Supplier
                </button>
                <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#poModal" onclick="resetPOForm()">
                    <i class="fas fa-plus me-1"></i> New Purchase Order
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab">
                    <i class="fas fa-warehouse me-1"></i> Suppliers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchase-orders-tab" data-bs-toggle="tab" data-bs-target="#purchase-orders" type="button" role="tab">
                    <i class="fas fa-shopping-cart me-1"></i> Purchase Orders
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- ==================== SUPPLIERS TAB ==================== -->
            <div class="tab-pane fade show active" id="suppliers" role="tabpanel">
                <!-- Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Total Suppliers</h6>
                                    <h2 class="fw-bold"><?= $totalSuppliers ?></h2>
                                </div>
                                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Active</h6>
                                    <h2 class="fw-bold text-success"><?= $activeSuppliers ?></h2>
                                </div>
                                <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#dc3545;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Inactive</h6>
                                    <h2 class="fw-bold text-danger"><?= $inactiveSuppliers ?></h2>
                                </div>
                                <div class="stat-icon text-danger"><i class="fas fa-times-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Suppliers Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Suppliers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="suppliersTable" class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Supplier Name</th>
                                        <th>Contact Person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $suppliersList = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_name");
                                while ($row = $suppliersList->fetch()): ?>
                                    <tr>
                                        <td><?= $row['supplier_id'] ?></td>
                                        <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_person'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['phone'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?: '-') ?></td>
                                        <td><?= htmlspecialchars($row['city'] ?: '-') ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?> status-badge">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editSupplier(<?= $row['supplier_id'] ?>)"><i class="fas fa-edit"></i></button>
                                            <a href="delete_supplier.php?id=<?= $row['supplier_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== PURCHASE ORDERS TAB ==================== -->
            <div class="tab-pane fade" id="purchase-orders" role="tabpanel">
                <!-- Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#0d6efd;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Total Orders</h6>
                                    <h2 class="fw-bold"><?= $totalPOs ?></h2>
                                </div>
                                <div class="stat-icon text-primary"><i class="fas fa-file-invoice"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#ffc107;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Pending</h6>
                                    <h2 class="fw-bold text-warning"><?= $pendingPOs ?></h2>
                                </div>
                                <div class="stat-icon text-warning"><i class="fas fa-clock"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted">Received</h6>
                                    <h2 class="fw-bold text-success"><?= $receivedPOs ?></h2>
                                </div>
                                <div class="stat-icon text-success"><i class="fas fa-check-double"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchase Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Purchase Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="poTable" class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>PO #</th>
                                        <th>Order #</th>
                                        <th>Supplier</th>
                                        <th>Order Date</th>
                                        <th>Expected Delivery</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = $purchaseOrders->fetch()): ?>
                                    <tr>
                                        <td><?= $row['purchase_order_id'] ?></td>
                                        <td><?= htmlspecialchars($row['order_number']) ?></td>
                                        <td><?= htmlspecialchars($row['supplier_name'] ?: 'Unknown') ?></td>
                                        <td><?= date('Y-m-d', strtotime($row['order_date'])) ?></td>
                                        <td><?= $row['expected_delivery'] ? date('Y-m-d', strtotime($row['expected_delivery'])) : '-' ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadgeClass($row['status']) ?> status-badge">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewPO(<?= $row['purchase_order_id'] ?>)"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editPO(<?= $row['purchase_order_id'] ?>)"><i class="fas fa-edit"></i></button>
                                            <a href="delete_purchase_order.php?id=<?= $row['purchase_order_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this purchase order?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- ============================================================ -->
<!-- ================ SUPPLIER MODAL (Add/Edit) ================= -->
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalTitle"><i class="fas fa-plus me-2"></i>Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="save_supplier.php" id="supplierForm">
                <input type="hidden" name="supplier_id" id="supplier_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Supplier Name</label>
                            <input type="text" name="supplier_name" id="supplier_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="contact_person" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="address" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="city" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" id="website" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax Number</label>
                            <input type="text" name="tax_number" id="tax_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- =========== PURCHASE ORDER MODAL (Add/Edit) ================ -->
<div class="modal fade" id="poModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="poModalTitle"><i class="fas fa-plus me-2"></i>New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="save_purchase_order.php" id="poForm">
                <input type="hidden" name="purchase_order_id" id="po_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Supplier</label>
                            <select name="supplier_id" id="po_supplier" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Order Number</label>
                            <input type="text" name="order_number" id="po_order_number" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">Order Date</label>
                            <input type="date" name="order_date" id="po_order_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Expected Delivery</label>
                            <input type="date" name="expected_delivery" id="po_expected_delivery" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="po_status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Ordered">Ordered</option>
                                <option value="Received">Received</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Amount</label>
                            <input type="text" name="total_amount" id="po_total" class="form-control" readonly style="background:#e9ecef; font-weight:bold;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="po_notes" rows="2" class="form-control"></textarea>
                    </div>

                    <!-- Items Section -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Order Items</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="itemsTable">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th style="width:40%;">Part</th>
                                            <th style="width:20%;">Quantity</th>
                                            <th style="width:20%;">Unit Price</th>
                                            <th style="width:15%;">Line Total</th>
                                            <th style="width:5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <!-- Dynamic rows will be inserted here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                            <td id="grandTotal" class="fw-bold">$0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow()">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ================ VIEW PURCHASE ORDER MODAL ================== -->
<div class="modal fade" id="viewPOModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPOBody">
                <!-- populated by AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
// --------------------- Helper: status badge class (JS for view modal) ---------------------
function statusBadgeClass(status) {
    const map = {
        'Pending': 'bg-warning text-dark',
        'Ordered': 'bg-info',
        'Received': 'bg-success',
        'Cancelled': 'bg-danger'
    };
    return map[status] || 'bg-secondary';
}

// --------------------- DataTables ---------------------
$(document).ready(function() {
    $('#suppliersTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search suppliers..." }
    });

    $('#poTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search POs..." }
    });
});

// --------------------- Supplier CRUD ---------------------
function resetSupplierForm() {
    $('#supplierModalTitle').html('<i class="fas fa-plus me-2"></i>Add Supplier');
    $('#supplierForm')[0].reset();
    $('#supplier_id').val('');
    $('#status').val('Active');
}

function editSupplier(id) {
    $.ajax({
        url: 'get_supplier.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#supplierModalTitle').html('<i class="fas fa-edit me-2"></i>Edit Supplier');
            $('#supplier_id').val(data.supplier_id);
            $('#supplier_name').val(data.supplier_name);
            $('#contact_person').val(data.contact_person || '');
            $('#phone').val(data.phone || '');
            $('#email').val(data.email || '');
            $('#address').val(data.address || '');
            $('#city').val(data.city || '');
            $('#website').val(data.website || '');
            $('#tax_number').val(data.tax_number || '');
            $('#status').val(data.status || 'Active');
            $('#supplierModal').modal('show');
        },
        error: function() { alert('Error loading supplier data.'); }
    });
}

// --------------------- Purchase Order CRUD ---------------------
// Initialize Select2 for part selection (used in item rows)
function initPartSelect(selector) {
    $(selector).select2({
        dropdownParent: $('#poModal'),
        ajax: {
            url: 'get_parts.php',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        placeholder: 'Search part...',
        minimumInputLength: 2,
        templateResult: function(part) {
            if (part.loading) return part.text;
            return $('<span>').text(part.text);
        },
        templateSelection: function(part) {
            return part.text;
        }
    });
}

// Add a new item row
function addItemRow(part_id = null, quantity = 1, unit_price = 0.00) {
    const rowId = Date.now() + Math.random().toString(36).substr(2, 5);
    const html = `
        <tr id="item-${rowId}" class="item-row">
            <td>
                <select name="part_id[]" class="form-select part-select" style="width:100%;" required>
                    <option value="">Search part...</option>
                </select>
            </td>
            <td>
                <input type="number" name="quantity[]" class="form-control quantity" value="${quantity}" min="1" step="1" required>
            </td>
            <td>
                <input type="number" name="unit_price[]" class="form-control unit-price" value="${unit_price}" min="0" step="0.01" required>
            </td>
            <td>
                <span class="line-total">$0.00</span>
            </td>
            <td>
                <span class="remove-item" onclick="removeItem('item-${rowId}')"><i class="fas fa-times-circle fa-lg"></i></span>
            </td>
        </tr>
    `;
    $('#itemsBody').append(html);
    // Initialize Select2 for this new row
    initPartSelect('#item-' + rowId + ' .part-select');
    // If part_id provided, we'll set it after row is added (edit function handles this)
    // Attach input events to update line totals
    $('#item-' + rowId + ' .quantity, #item-' + rowId + ' .unit-price').on('input', function() {
        updateLineTotal($(this).closest('tr'));
    });
    // Trigger initial calculation
    updateLineTotal($('#item-' + rowId));
    updateGrandTotal();
}

function removeItem(rowId) {
    $('#' + rowId).remove();
    updateGrandTotal();
}

function updateLineTotal(row) {
    const qty = parseFloat(row.find('.quantity').val()) || 0;
    const price = parseFloat(row.find('.unit-price').val()) || 0;
    const total = qty * price;
    row.find('.line-total').text('$' + total.toFixed(2));
    updateGrandTotal();
}

function updateGrandTotal() {
    let grand = 0;
    $('.line-total').each(function() {
        const val = parseFloat($(this).text().replace('$', '')) || 0;
        grand += val;
    });
    $('#grandTotal').text('$' + grand.toFixed(2));
    $('#po_total').val(grand.toFixed(2));
}

// Reset PO form
function resetPOForm() {
    $('#poModalTitle').html('<i class="fas fa-plus me-2"></i>New Purchase Order');
    $('#poForm')[0].reset();
    $('#po_id').val('');
    $('#po_order_date').val(new Date().toISOString().split('T')[0]);
    $('#po_total').val('0.00');
    $('#itemsBody').empty();
    // Add one default item row
    addItemRow();
    // Set total to 0
    updateGrandTotal();
}

// Edit PO: load data
function editPO(id) {
    $.ajax({
        url: 'get_purchase_order.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            if (!data || !data.header) {
                alert('Error loading purchase order.');
                return;
            }
            const header = data.header;
            $('#poModalTitle').html('<i class="fas fa-edit me-2"></i>Edit Purchase Order');
            $('#po_id').val(header.purchase_order_id);
            $('#po_supplier').val(header.supplier_id);
            $('#po_order_number').val(header.order_number);
            $('#po_order_date').val(header.order_date);
            $('#po_expected_delivery').val(header.expected_delivery || '');
            $('#po_status').val(header.status);
            $('#po_notes').val(header.notes || '');
            // Clear existing items
            $('#itemsBody').empty();
            // Add items
            if (data.items && data.items.length) {
                data.items.forEach(function(item) {
                    addItemRow();
                    const lastRow = $('#itemsBody tr:last');
                    // Set part via Select2 - we need to set the value and display name
                    const select = lastRow.find('.part-select');
                    // Create an option with the part name and value, then trigger change
                    const newOption = new Option(item.part_name, item.part_id, true, true);
                    select.append(newOption).trigger('change');
                    // Set quantity and unit price
                    lastRow.find('.quantity').val(item.quantity);
                    lastRow.find('.unit-price').val(item.unit_price);
                    // Update line total
                    updateLineTotal(lastRow);
                });
            } else {
                // Add one empty row if no items
                addItemRow();
            }
            updateGrandTotal();
            $('#poModal').modal('show');
        },
        error: function() { alert('Error loading purchase order.'); }
    });
}

// View PO details
function viewPO(id) {
    $.ajax({
        url: 'get_purchase_order.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            if (!data || !data.header) {
                alert('Error loading purchase order.');
                return;
            }
            const h = data.header;
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Order #:</strong> ${h.order_number}</p>
                        <p><strong>Supplier:</strong> ${h.supplier_name}</p>
                        <p><strong>Order Date:</strong> ${h.order_date}</p>
                        <p><strong>Expected Delivery:</strong> ${h.expected_delivery || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge ${statusBadgeClass(h.status)}">${h.status}</span></p>
                        <p><strong>Total Amount:</strong> $${parseFloat(h.total_amount).toFixed(2)}</p>
                        <p><strong>Notes:</strong> ${h.notes || 'N/A'}</p>
                    </div>
                </div>
                <hr>
                <h6>Items</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Part</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            if (data.items && data.items.length) {
                data.items.forEach(function(item) {
                    html += `
                        <tr>
                            <td>${item.part_name}</td>
                            <td>${item.quantity}</td>
                            <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>$${parseFloat(item.total_price).toFixed(2)}</td>
                        </tr>
                    `;
                });
            } else {
                html += `<tr><td colspan="4" class="text-center">No items</td></tr>`;
            }
            html += `
                    </tbody>
                    <tfoot>
                        <tr><th colspan="3" class="text-end">Grand Total:</th><th>$${parseFloat(h.total_amount).toFixed(2)}</th></tr>
                    </tfoot>
                </table>
            `;
            $('#viewPOBody').html(html);
            $('#viewPOModal').modal('show');
        },
        error: function() { alert('Error loading purchase order.'); }
    });
}

// Attach event listeners for dynamic row updates
$(document).on('input', '.quantity, .unit-price', function() {
    const row = $(this).closest('tr');
    updateLineTotal(row);
});

// When modal is hidden, clean up Select2 instances to avoid duplication
$('#poModal').on('hidden.bs.modal', function() {
    $('.part-select').each(function() {
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });
});

// Initialize first item row when modal is shown for new PO
$('#poModal').on('show.bs.modal', function() {
    if ($('#itemsBody tr').length === 0) {
        addItemRow();
    }
});

// On submit, ensure all part selects have a value
$('#poForm').on('submit', function(e) {
    let valid = true;
    $('.part-select').each(function() {
        if (!$(this).val()) {
            valid = false;
            $(this).closest('td').addClass('has-error');
        } else {
            $(this).closest('td').removeClass('has-error');
        }
    });
    if (!valid) {
        e.preventDefault();
        alert('Please select a part for each item.');
        return false;
    }
});

// --------------------- End of PO scripts ---------------------
</script>
</body>
</html>