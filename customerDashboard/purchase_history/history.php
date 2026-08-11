<?php
/**
 * Customer Appointment History
 * - Display all appointments booked by the logged-in customer
 * - Shows appointment details, vehicle info, status, employee assigned
 * - Includes summary stats (total, pending, completed/cancelled)
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isset($currentUser['person_id'])) {
    header('Location: ../../login.php');
    exit;
}

$personId = (int)$currentUser['person_id'];

// Get customer_id
$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$personId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die('Customer record not found.');
}

$customerId = (int)$customer['customer_id'];

// Fetch appointment history
$stmt = $pdo->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_type,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.notes,
        a.created_at,
        a.updated_at,
        v.make,
        v.model,
        v.manufacture_year,
        v.registration_number,
        v.colour,
        v.body_type,
        e.employee_id,
        p.first_name AS employee_first,
        p.last_name AS employee_last
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    LEFT JOIN persons p ON e.person_id = p.person_id
    WHERE a.customer_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$customerId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalAppointments = count($appointments);
$pendingCount = array_filter($appointments, fn($a) => $a['status'] === 'Pending');
$pendingCount = count($pendingCount);
$confirmedCount = array_filter($appointments, fn($a) => $a['status'] === 'Confirmed');
$confirmedCount = count($confirmedCount);
$completedCount = array_filter($appointments, fn($a) => $a['status'] === 'Completed');
$completedCount = count($completedCount);
$cancelledCount = array_filter($appointments, fn($a) => $a['status'] === 'Cancelled');
$cancelledCount = count($cancelledCount);

$pageTitle = "Appointment History";
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

    <!-- Google Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <style>
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

        .content-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
            padding: 25px 30px;
            margin-bottom: 30px;
        }

        .content-card .card-title {
            font-weight: 700;
            color: #1f2937;
            letter-spacing: -0.3px;
        }

        /* ── Stats Cards ── */
        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 20px 20px 20px 25px;
            transition: transform 0.25s ease, box-shadow 0.3s ease;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.15;
            position: absolute;
            right: 15px;
            bottom: 10px;
            transition: all 0.3s;
        }
        .stat-card:hover .stat-icon {
            opacity: 0.3;
            transform: scale(1.05);
        }
        .stat-card .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .stat-card .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        .stat-card.total-appointments {
            border-left: 6px solid #4f46e5;
        }
        .stat-card.pending {
            border-left: 6px solid #f59e0b;
        }
        .stat-card.completed {
            border-left: 6px solid #10b981;
        }
        .stat-card.cancelled {
            border-left: 6px solid #ef4444;
        }

        /* ── Table ── */
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

        /* ── Badges ── */
        .badge-status {
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-confirmed {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-cancelled {
            background: #fecaca;
            color: #991b1b;
        }
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        /* ── Buttons ── */
        .btn-primary-custom {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            padding: 8px 24px;
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
        .btn-outline-secondary-custom {
            border-radius: 50px;
            padding: 8px 24px;
            font-weight: 600;
            border-color: #d1d5db;
            color: #374151;
        }
        .btn-outline-secondary-custom:hover {
            background: #f3f4f6;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
        }
        .empty-state p {
            font-size: 1.2rem;
            color: #6b7280;
            margin-top: 15px;
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .content-card {
                padding: 20px;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        @media (max-width: 768px) {
            .content-card {
                padding: 15px;
            }
            .stat-card {
                padding: 16px 16px 16px 20px;
            }
            .stat-card .stat-number {
                font-size: 1.8rem;
            }
            .stat-card .stat-icon {
                font-size: 2rem;
                right: 12px;
                bottom: 8px;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }
            .page-header .btn {
                width: 100%;
                text-align: center;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.75rem;
                padding: 8px 0;
            }
            .table-dashboard td .badge-status {
                font-size: 0.65rem;
                padding: 2px 10px;
            }
            .btn-primary-custom, .btn-outline-secondary-custom {
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
            }
            .mt-3.text-end {
                text-align: center !important;
            }
        }

        @media (max-width: 576px) {
            .content-card {
                padding: 12px;
                border-radius: 16px;
            }
            .stat-card {
                padding: 12px 12px 12px 16px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            .stat-card .stat-label {
                font-size: 0.75rem;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem;
                right: 10px;
                bottom: 6px;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.7rem;
                padding: 6px 0;
            }
            .table-dashboard td .badge-status {
                font-size: 0.6rem;
                padding: 2px 8px;
            }
            .btn-primary-custom, .btn-outline-secondary-custom {
                font-size: 0.9rem;
                padding: 12px 20px;
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

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 page-header">
            <h4 class="fw-bold text-dark mb-0">
                <i class="fas fa-calendar-check me-2 text-primary"></i> Appointment History
            </h4>
            <a href="../vehicles_sales/index.php" class="btn btn-primary-custom">
                <i class="fas fa-plus me-2"></i> Book New Appointment
            </a>
        </div>

        <!-- Statistics Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card total-appointments">
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-number"><?= $totalAppointments ?></div>
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card pending">
                    <div class="stat-label">Pending</div>
                    <div class="stat-number"><?= $pendingCount ?></div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card completed">
                    <div class="stat-label">Confirmed / Completed</div>
                    <div class="stat-number"><?= $confirmedCount + $completedCount ?></div>
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card cancelled">
                    <div class="stat-label">Cancelled</div>
                    <div class="stat-number"><?= $cancelledCount ?></div>
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="content-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-list me-2"></i> Your Appointments
            </h5>

            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <p>You have not booked any appointments yet.</p>
                    <a href="../vehicles_sales/index.php" class="btn btn-primary-custom mt-3">
                        <i class="fas fa-car me-2"></i> Browse Vehicles
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dashboard">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Date / Time</th>
                                <th>Status</th>
                                <th>Employee</th>
                                <th>Notes</th>
                                <th>Booked On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <?php
                                // Status badge class
                                $statusClass = match($appt['status']) {
                                    'Pending'   => 'badge-pending',
                                    'Confirmed' => 'badge-confirmed',
                                    'Completed' => 'badge-completed',
                                    'Cancelled' => 'badge-cancelled',
                                    default     => 'badge-secondary'
                                };
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($appt['appointment_id']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($appt['make'] . ' ' . $appt['model']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($appt['registration_number'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($appt['appointment_type']) ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($appt['appointment_date'])) ?>
                                        <br><small><?= date('H:i', strtotime($appt['appointment_time'])) ?></small>
                                    </td>
                                    <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($appt['status']) ?></span></td>
                                    <td>
                                        <?php if ($appt['employee_first']): ?>
                                            <?= htmlspecialchars($appt['employee_first'] . ' ' . $appt['employee_last']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($appt['notes'])): ?>
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="<?= htmlspecialchars($appt['notes']) ?>">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($appt['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <a href="../profile/index.php" class="btn btn-outline-secondary-custom">
                        <i class="fas fa-arrow-left me-1"></i> Back to Profile
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Enable tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>
</html>