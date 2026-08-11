<?php
/**
 * Vehicle Sales Overview (Read-Only)
 * - Statistics: total sales, revenue, deposits, pending/completed counts
 * - DataTable with sale reference, vehicle, customer, employee, amounts, statuses
 * - View modal for full details
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

// ---------- Fetch Statistics ----------
$totalSales = $pdo->query("SELECT COUNT(*) FROM vehicle_sales")->fetchColumn();

// Total revenue (sum of sale_price)
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(sale_price), 0) FROM vehicle_sales")->fetchColumn();

// Total deposits collected
$totalDeposits = $pdo->query("SELECT COALESCE(SUM(deposit_amount), 0) FROM vehicle_sales")->fetchColumn();

// Pending sales (sale_status = 'Pending')
$pendingSales = $pdo->query("SELECT COUNT(*) FROM vehicle_sales WHERE sale_status = 'Pending'")->fetchColumn();

// Completed sales
$completedSales = $pdo->query("SELECT COUNT(*) FROM vehicle_sales WHERE sale_status = 'Completed'")->fetchColumn();

// ---------- Fetch Sales with related data ----------
$sql = "
    SELECT
        vs.*,
        v.make, v.model, v.manufacture_year, v.stock_number,
        c.customer_number,
        p_cust.first_name AS customer_first,
        p_cust.last_name AS customer_last,
        p_emp.first_name AS employee_first,
        p_emp.last_name AS employee_last
    FROM vehicle_sales vs
    INNER JOIN vehicles v ON vs.vehicle_id = v.vehicle_id
    INNER JOIN customers c ON vs.customer_id = c.customer_id
    INNER JOIN persons p_cust ON c.person_id = p_cust.person_id
    LEFT JOIN employees e ON vs.employee_id = e.employee_id
    LEFT JOIN persons p_emp ON e.person_id = p_emp.person_id
    ORDER BY vs.sale_id DESC
";
$sales = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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

        /* ── Badges & Buttons ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 0 2px;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 900px;
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
            .modal-body .row > .col-md-6:last-child {
                margin-bottom: 0;
            }
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
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
                <h2><i class="fas fa-hand-holding-usd text-primary me-2"></i>Vehicle Sales</h2>
                <p class="text-muted">Overview of all vehicle sales.</p>
            </div>
        </div>

        <!-- Alerts (if any) -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Sales</h6>
                            <h2 class="fw-bold"><?= $totalSales ?></h2>
                        </div>
                        <div class="stat-icon text-primary"><i class="fas fa-file-invoice"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Revenue</h6>
                            <h2 class="fw-bold text-success">R <?= number_format($totalRevenue, 2) ?></h2>
                        </div>
                        <div class="stat-icon text-success"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#ffc107;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Deposits</h6>
                            <h2 class="fw-bold text-warning">R <?= number_format($totalDeposits, 2) ?></h2>
                        </div>
                        <div class="stat-icon text-warning"><i class="fas fa-piggy-bank"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#0dcaf0;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Pending / Completed</h6>
                            <h2 class="fw-bold text-info"><?= $pendingSales ?> / <?= $completedSales ?></h2>
                        </div>
                        <div class="stat-icon text-info"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Sales</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="salesTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Vehicle</th>
                                <th>Customer</th>
                                <th>Salesperson</th>
                                <th>Sale Price</th>
                                <th>Payment Status</th>
                                <th>Sale Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 1; while ($row = $sales->fetch()): 
                            $vehicleDisplay = $row['make'] . ' ' . $row['model'] . ' (' . $row['manufacture_year'] . ')';
                            $customerName = $row['customer_first'] . ' ' . $row['customer_last'];
                            $employeeName = ($row['employee_first'] && $row['employee_last']) 
                                ? $row['employee_first'] . ' ' . $row['employee_last'] 
                                : 'N/A';
                            $paymentBadge = match($row['payment_status']) {
                                'Paid' => 'success',
                                'Partial' => 'warning',
                                'Pending' => 'secondary',
                                'Cancelled' => 'danger',
                                default => 'secondary'
                            };
                            $saleBadge = match($row['sale_status']) {
                                'Completed' => 'success',
                                'Pending' => 'warning',
                                'Cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><strong><?= htmlspecialchars($row['sale_reference']) ?></strong></td>
                                <td><?= htmlspecialchars($vehicleDisplay) ?></td>
                                <td><?= htmlspecialchars($customerName) ?></td>
                                <td><?= htmlspecialchars($employeeName) ?></td>
                                <td>R <?= number_format($row['sale_price'], 2) ?></td>
                                <td><span class="badge bg-<?= $paymentBadge ?> badge-status"><?= ucfirst($row['payment_status']) ?></span></td>
                                <td><span class="badge bg-<?= $saleBadge ?> badge-status"><?= ucfirst($row['sale_status']) ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info action-btn" onclick="viewSale(<?= $row['sale_id'] ?>)" title="View Details"><i class="fas fa-eye"></i></button>
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

<!-- ========== VIEW MODAL ========== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sale Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- populated by AJAX -->
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
$(document).ready(function() {
    $('#salesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [8] }],
        language: { search: "Filter:", searchPlaceholder: "Search sales..." }
    });
});

function viewSale(id) {
    $.ajax({
        url: 'get_sale.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Sale Information</h6>
                        <p><strong>Reference:</strong> ${data.sale_reference}</p>
                        <p><strong>Sale Date:</strong> ${data.sale_date}</p>
                        <p><strong>Sale Price:</strong> R ${parseFloat(data.sale_price).toFixed(2)}</p>
                        <p><strong>Deposit:</strong> R ${parseFloat(data.deposit_amount).toFixed(2)}</p>
                        <p><strong>Discount:</strong> R ${parseFloat(data.discount_amount).toFixed(2)}</p>
                        <p><strong>Net Amount:</strong> R ${(parseFloat(data.sale_price) - parseFloat(data.deposit_amount) - parseFloat(data.discount_amount)).toFixed(2)}</p>
                        <p><strong>Payment Method:</strong> ${data.payment_method}</p>
                        <p><strong>Payment Status:</strong> <span class="badge bg-${data.payment_status=='Paid'?'success':data.payment_status=='Partial'?'warning':'secondary'}">${data.payment_status}</span></p>
                        <p><strong>Sale Status:</strong> <span class="badge bg-${data.sale_status=='Completed'?'success':data.sale_status=='Pending'?'warning':'danger'}">${data.sale_status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2">Delivery Information</h6>
                        <p><strong>Delivery Date:</strong> ${data.delivery_date || 'Not set'}</p>
                        <p><strong>Delivery Status:</strong> ${data.delivery_status}</p>
                        <hr>
                        <h6 class="border-bottom pb-2">Related Parties</h6>
                        <p><strong>Vehicle:</strong> ${data.make} ${data.model} (${data.manufacture_year})<br>Stock: ${data.stock_number}</p>
                        <p><strong>Customer:</strong> ${data.customer_first} ${data.customer_last}<br>Customer No: ${data.customer_number}</p>
                        <p><strong>Salesperson:</strong> ${data.employee_first ? data.employee_first + ' ' + data.employee_last : 'N/A'}</p>
                        <hr>
                        <p><strong>Notes:</strong><br>${data.notes || 'None'}</p>
                    </div>
                </div>
            `;
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
        },
        error: function() { alert('Error loading sale details.'); }
    });
}
</script>
</body>
</html>