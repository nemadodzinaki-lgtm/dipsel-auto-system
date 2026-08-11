<?php

require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Only customers can access this page
if ($_SESSION['role'] !== 'Customer') {
    header('Location: login.php');
    exit;
}

$pageTitle = "Customer Dashboard";

$personId = $_SESSION['person_id'];

// Fetch customer details
$stmt = $pdo->prepare("
    SELECT
        p.first_name,
        p.last_name,
        c.customer_number,
        c.loyalty_points
    FROM persons p
    INNER JOIN customers c
        ON p.person_id = c.person_id
    WHERE p.person_id = ?
");
$stmt->execute([$personId]);
$customer = $stmt->fetch();

// Total vehicles saved by this customer
$stmtVehicles = $pdo->prepare("
    SELECT COUNT(*) FROM saved_vehicles WHERE customer_id = (
        SELECT customer_id FROM customers WHERE person_id = ?
    )
");
$stmtVehicles->execute([$personId]);
$totalVehicles = $stmtVehicles->fetchColumn();

// Total bookings for this customer (assuming a bookings table exists)
$stmtBookings = $pdo->prepare("
    SELECT COUNT(*) FROM service_bookings
    WHERE customer_id = (SELECT customer_id FROM customers WHERE person_id = ?)
");
$stmtBookings->execute([$personId]);
$totalBookings = $stmtBookings->fetchColumn();

// Fetch recent bookings (limit 5)
$stmtRecent = $pdo->prepare("
    SELECT
        b.booking_id,
        b.booking_date,
        b.booking_status AS status,
        b.registration_number
    FROM service_bookings b
    WHERE b.customer_id = (SELECT customer_id FROM customers WHERE person_id = ?)
    ORDER BY b.booking_date DESC
    LIMIT 5
");
$stmtRecent->execute([$personId]);
$recentBookings = $stmtRecent->fetchAll();
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
        /* ── Global Reset ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        /* ── Dashboard Container ── */
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }

        /* ── Welcome Header (Green Gradient) ── */
        .welcome-header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 20px;
            padding: 30px 35px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(17, 153, 142, 0.35);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .welcome-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }
        .welcome-header h2 {
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .welcome-header p {
            opacity: 0.85;
            margin-bottom: 0;
            font-weight: 400;
        }

        /* ── Stats Cards ── */
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 20px 20px 20px 25px;
            transition: transform 0.25s ease, box-shadow 0.3s ease;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
        }
        .stat-card .stat-icon {
            font-size: 2.8rem;
            opacity: 0.2;
            position: absolute;
            right: 15px;
            bottom: 15px;
            transition: all 0.3s;
        }
        .stat-card:hover .stat-icon {
            opacity: 0.35;
            transform: scale(1.05);
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        /* Individual card accents (customer-specific) */
        .stat-card.customer-number {
            border-left: 6px solid #4f46e5;
        }
        .stat-card.loyalty {
            border-left: 6px solid #f59e0b;
        }
        .stat-card.vehicles {
            border-left: 6px solid #10b981;
        }
        .stat-card.bookings {
            border-left: 6px solid #ef4444;
        }

        /* ── Card Headers ── */
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            background: #ffffff;
            transition: box-shadow 0.3s;
            height: 100%;
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
            letter-spacing: -0.2px;
        }
        .card-custom .card-body {
            padding: 20px 25px;
        }

        /* ── Table ── */
        .table-dashboard {
            margin-bottom: 0;
        }
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
            font-weight: 500;
        }
        .table-dashboard tr:last-child td {
            border-bottom: none;
        }
        .table-dashboard tr:hover td {
            background-color: #f9fafb;
        }

        /* ── Quick Actions List ── */
        .list-group-flush-custom .list-group-item {
            border: none;
            padding: 14px 20px;
            font-weight: 500;
            color: #374151;
            background: transparent;
            transition: all 0.2s;
            border-left: 4px solid transparent;
            margin-bottom: 4px;
            border-radius: 12px;
        }
        .list-group-flush-custom .list-group-item i {
            width: 28px;
            color: #6b7280;
            transition: color 0.2s;
        }
        .list-group-flush-custom .list-group-item:hover {
            background: #f3f4f6;
            border-left-color: #11998e;
            color: #1f2937;
        }
        .list-group-flush-custom .list-group-item:hover i {
            color: #11998e;
        }
        .list-group-flush-custom .list-group-item:last-child {
            margin-bottom: 0;
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .welcome-header {
                padding: 24px 28px;
            }
            .welcome-header h2 {
                font-size: 1.6rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .stat-card .stat-number {
                font-size: 2.2rem;
            }
            .stat-card .stat-label {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 768px) {
            .welcome-header {
                padding: 20px;
                border-radius: 16px;
            }
            .welcome-header h2 {
                font-size: 1.4rem;
            }
            .welcome-header p {
                font-size: 0.95rem;
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
                bottom: 12px;
            }
            .card-custom .card-header {
                padding: 14px 18px;
                font-size: 1rem;
            }
            .card-custom .card-body {
                padding: 16px 18px;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.75rem;
                padding: 8px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.7rem;
            }
            .list-group-flush-custom .list-group-item {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
            .list-group-flush-custom .list-group-item i {
                width: 24px;
                font-size: 0.9rem;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            .welcome-header {
                padding: 16px 18px;
                border-radius: 14px;
            }
            .welcome-header h2 {
                font-size: 1.2rem;
            }
            .welcome-header p {
                font-size: 0.85rem;
            }
            .stat-card {
                padding: 12px 12px 12px 16px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            .stat-card .stat-label {
                font-size: 0.7rem;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem;
                right: 10px;
                bottom: 10px;
            }
            .card-custom .card-header {
                padding: 12px 14px;
                font-size: 0.95rem;
            }
            .card-custom .card-body {
                padding: 12px 14px;
            }
            .table-dashboard th, .table-dashboard td {
                font-size: 0.7rem;
                padding: 6px 0;
            }
            .table-dashboard td .badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }
            .list-group-flush-custom .list-group-item {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            .list-group-flush-custom .list-group-item i {
                width: 22px;
                font-size: 0.85rem;
            }
            .page-header h4 {
                font-size: 1.1rem;
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

            <!-- Welcome Header -->
            <div class="welcome-header">
                <div>
                    <h2>
                        <i class="fas fa-user-check me-2"></i>
                        Welcome back, <?= htmlspecialchars($customer['first_name']) ?>
                    </h2>
                    <p>
                        <i class="fas fa-gem me-1"></i>
                        Customer Dashboard — your service hub
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card customer-number">
                        <div class="stat-label">Customer Number</div>
                        <div class="stat-number"><?= htmlspecialchars($customer['customer_number']) ?></div>
                        <div class="stat-icon"><i class="fas fa-id-card"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card loyalty">
                        <div class="stat-label">Loyalty Points</div>
                        <div class="stat-number"><?= (int)$customer['loyalty_points'] ?></div>
                        <div class="stat-icon"><i class="fas fa-star"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card vehicles">
                        <div class="stat-label">Saved Vehicles </div>
                        <div class="stat-number"><?= $totalVehicles ?></div>
                        <div class="stat-icon"><i class="fas fa-car"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card bookings">
                        <div class="stat-label">Total Bookings</div>
                        <div class="stat-number"><?= $totalBookings ?></div>
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings & Quick Actions -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-clock me-2 text-primary"></i>
                            Recent Bookings
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recentBookings) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-dashboard">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Vehicle</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBookings as $booking): ?>
                                                <tr>
                                                    <td><span class="badge bg-light text-dark px-3 py-2">#<?= htmlspecialchars($booking['booking_id']) ?></span></td>
                                                    <td><?= htmlspecialchars($booking['registration_number']) ?></td>
                                                    <td><i class="far fa-calendar-alt me-1 text-muted"></i> <?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $booking['status'] == 'Completed' ? 'success' : ($booking['status'] == 'Pending' ? 'warning' : 'secondary') ?>">
                                                            <?= htmlspecialchars($booking['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No bookings yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-bolt me-2 text-warning"></i>
                            Quick Actions
                        </div>
                        <div class="card-body p-2">
                            <div class="list-group list-group-flush-custom">
                                <a href="../vehicles/index.php" class="list-group-item">
                                    <i class="fas fa-car"></i> My Vehicles
                                </a>
                                <a href="../bookings/create.php" class="list-group-item">
                                    <i class="fas fa-calendar-plus"></i> New Booking
                                </a>
                                <a href="../bookings/index.php" class="list-group-item">
                                    <i class="fas fa-calendar-alt"></i> View Bookings
                                </a>
                                <a href="../profile/index.php" class="list-group-item">
                                    <i class="fas fa-user-edit"></i> My Profile
                                </a>
                                <a href="../support/index.php" class="list-group-item">
                                    <i class="fas fa-headset"></i> Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- /container-fluid -->

        <?php include '../../includes/footer.php'; ?>

    </div> <!-- /dashboard-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>