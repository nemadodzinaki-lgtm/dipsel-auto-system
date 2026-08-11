<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/auth.php';

/** @var array{first_name: string, last_name?: string, email?: string} $currentUser */
if (!isset($currentUser) || !is_array($currentUser)) {
    $currentUser = ['first_name' => 'Admin'];
}

// ─── Existing stats ─────────────────────────────────────────
$totalVehicles   = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalCustomers  = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalEmployees  = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totalAdmins     = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

// ─── New stats from appointments & service_bookings ───────
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$totalServiceBookings = $pdo->query("SELECT COUNT(*) FROM service_bookings")->fetchColumn();

// ─── Chart data: appointments by status ────────────────────
$apptStatusData = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
while ($row = $stmt->fetch()) {
    $apptStatusData[$row['status']] = (int)$row['count'];
}

// ─── Chart data: service bookings by status ────────────────
$serviceStatusData = [];
$stmt = $pdo->query("SELECT booking_status, COUNT(*) as count FROM service_bookings GROUP BY booking_status");
while ($row = $stmt->fetch()) {
    $serviceStatusData[$row['booking_status']] = (int)$row['count'];
}

// ─── Chart data: appointments by type ──────────────────────
$apptTypeData = [];
$stmt = $pdo->query("SELECT appointment_type, COUNT(*) as count FROM appointments GROUP BY appointment_type");
while ($row = $stmt->fetch()) {
    $apptTypeData[$row['appointment_type']] = (int)$row['count'];
}

// ─── Chart data: monthly appointment trend (last 6 months) ─
$monthlyTrend = [];
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM appointments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
while ($row = $stmt->fetch()) {
    $monthlyTrend[$row['month']] = (int)$row['count'];
}
// Fill missing months with 0 (optional, but we'll keep as is)
// We'll use the months as labels and counts as data

// Prepare arrays for Chart.js
$apptStatusLabels = array_keys($apptStatusData);
$apptStatusCounts = array_values($apptStatusData);

$serviceStatusLabels = array_keys($serviceStatusData);
$serviceStatusCounts = array_values($serviceStatusData);

$apptTypeLabels = array_keys($apptTypeData);
$apptTypeCounts = array_values($apptTypeData);

$trendLabels = array_keys($monthlyTrend);
$trendCounts = array_values($monthlyTrend);

// Ensure we have at least one month for trend
if (empty($trendLabels)) {
    // Fallback: last 6 months with 0
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $trendLabels[] = $month;
        $trendCounts[] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>

    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Google Font (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ── Global Reset ── */
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

        /* ── Welcome Header ── */
        .welcome-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px 35px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.35);
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
        /* Individual accents */
        .stat-card.vehicles   { border-left: 6px solid #4f46e5; }
        .stat-card.customers  { border-left: 6px solid #10b981; }
        .stat-card.employees  { border-left: 6px solid #f59e0b; }
        .stat-card.admins     { border-left: 6px solid #ef4444; }
        .stat-card.appointments { border-left: 6px solid #8b5cf6; }
        .stat-card.services   { border-left: 6px solid #ec4899; }

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
            border-left-color: #667eea;
            color: #1f2937;
        }
        .list-group-flush-custom .list-group-item:hover i {
            color: #667eea;
        }
        .list-group-flush-custom .list-group-item:last-child {
            margin-bottom: 0;
        }

        /* ── Chart container ── */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* ── Responsive fine‑tune ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            /* Welcome header smaller on tablets/phones */
            .welcome-header {
                padding: 20px 25px;
            }
            .welcome-header h2 {
                font-size: 1.6rem;
            }
            /* Stat cards: reduce padding and font size */
            .stat-card {
                padding: 15px 15px 15px 20px;
            }
            .stat-card .stat-number {
                font-size: 2rem;
            }
            .stat-card .stat-icon {
                font-size: 2rem;
            }
            /* Chart height */
            .chart-container {
                height: 200px;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }
            .welcome-header {
                padding: 15px 18px;
                border-radius: 15px;
            }
            .welcome-header h2 {
                font-size: 1.3rem;
            }
            .welcome-header p {
                font-size: 0.9rem;
            }
            .stat-card {
                padding: 12px 12px 12px 16px;
            }
            .stat-card .stat-number {
                font-size: 1.6rem;
            }
            .stat-card .stat-label {
                font-size: 0.75rem;
            }
            .stat-card .stat-icon {
                font-size: 1.6rem;
                right: 10px;
                bottom: 10px;
            }
            .card-custom .card-header {
                padding: 14px 18px;
                font-size: 1rem;
            }
            .card-custom .card-body {
                padding: 14px 18px;
            }
            .chart-container {
                height: 170px;
            }
            /* Table font size */
            .table-dashboard td,
            .table-dashboard th {
                font-size: 0.8rem;
                padding: 8px 0;
            }
            .table-dashboard .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
            .list-group-flush-custom .list-group-item {
                padding: 10px 14px;
                font-size: 0.9rem;
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
                        <i class="fas fa-hand-peace me-2"></i>
                        Welcome, <?= htmlspecialchars($currentUser['first_name']) ?>
                    </h2>
                    <p>
                        <i class="fas fa-chart-pie me-1"></i>
                        Super Administrator Dashboard — here's your overview
                    </p>
                </div>
            </div>

            <!-- Stats Cards (6 cards) -->
            <div class="row g-4 mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card vehicles">
                        <div class="stat-label">Total Vehicles</div>
                        <div class="stat-number"><?= $totalVehicles ?></div>
                        <div class="stat-icon"><i class="fas fa-car"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card customers">
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-number"><?= $totalCustomers ?></div>
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card employees">
                        <div class="stat-label">Total Employees</div>
                        <div class="stat-number"><?= $totalEmployees ?></div>
                        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card admins">
                        <div class="stat-label">Administrators</div>
                        <div class="stat-number"><?= $totalAdmins ?></div>
                        <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card appointments">
                        <div class="stat-label">Appointments</div>
                        <div class="stat-number"><?= $totalAppointments ?></div>
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="stat-card services">
                        <div class="stat-label">Service Bookings</div>
                        <div class="stat-number"><?= $totalServiceBookings ?></div>
                        <div class="stat-icon"><i class="fas fa-tools"></i></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1: Appointment Status & Service Booking Status -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>
                            Appointment Status Distribution
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="apptStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2 text-success"></i>
                            Service Booking Status
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="serviceStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2: Appointment Type & Monthly Trend -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-tag me-2 text-warning"></i>
                            Appointment Types
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="apptTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2 text-info"></i>
                            Monthly Appointment Trend (Last 6 Months)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Customers & Quick Actions -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card card-custom">
                        <div class="card-header">
                            <i class="fas fa-user-plus me-2 text-primary"></i>
                            Recent Customers
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dashboard">
                                    <thead>
                                        <tr>
                                            <th>Customer No</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("
                                            SELECT
                                                customers.customer_number,
                                                persons.first_name,
                                                persons.last_name,
                                                persons.phone,
                                                persons.email
                                            FROM customers
                                            INNER JOIN persons
                                                ON customers.person_id = persons.person_id
                                            ORDER BY customers.customer_id DESC
                                            LIMIT 5
                                        ");
                                        while ($row = $stmt->fetch()) {
                                        ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark px-3 py-2"><?= htmlspecialchars($row['customer_number']) ?></span></td>
                                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                                <td><i class="fas fa-phone-alt me-1 text-muted"></i> <?= htmlspecialchars($row['phone']) ?></td>
                                                <td><i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($row['email']) ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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
                                    <i class="fas fa-car"></i> Manage Vehicles
                                </a>
                                <a href="../customers_AdminManagement/index.php" class="list-group-item">
                                    <i class="fas fa-users"></i> Manage Customers
                                </a>
                                <a href="../employees/index.php" class="list-group-item">
                                    <i class="fas fa-user-tie"></i> Manage Employees
                                </a>
                                <a href="../settings/settings.php" class="list-group-item">
                                    <i class="fas fa-cogs"></i> System Settings
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

    <script>
        // ─── Pass PHP data to JavaScript ──────────────────────────────
        const apptStatusLabels = <?= json_encode($apptStatusLabels) ?>;
        const apptStatusCounts = <?= json_encode($apptStatusCounts) ?>;
        const serviceStatusLabels = <?= json_encode($serviceStatusLabels) ?>;
        const serviceStatusCounts = <?= json_encode($serviceStatusCounts) ?>;
        const apptTypeLabels = <?= json_encode($apptTypeLabels) ?>;
        const apptTypeCounts = <?= json_encode($apptTypeCounts) ?>;
        const trendLabels = <?= json_encode($trendLabels) ?>;
        const trendCounts = <?= json_encode($trendCounts) ?>;

        // ─── Color palette ─────────────────────────────────────────────
        const colors = [
            '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899',
            '#14b8a6', '#f97316', '#6366f1', '#84cc16'
        ];

        // ─── Appointment Status Pie Chart ──────────────────────────────
        new Chart(document.getElementById('apptStatusChart'), {
            type: 'pie',
            data: {
                labels: apptStatusLabels,
                datasets: [{
                    data: apptStatusCounts,
                    backgroundColor: colors.slice(0, apptStatusLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });

        // ─── Service Booking Status Bar Chart ──────────────────────────
        new Chart(document.getElementById('serviceStatusChart'), {
            type: 'bar',
            data: {
                labels: serviceStatusLabels,
                datasets: [{
                    label: 'Bookings',
                    data: serviceStatusCounts,
                    backgroundColor: colors.slice(0, serviceStatusLabels.length),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // ─── Appointment Types Pie Chart ──────────────────────────────
        new Chart(document.getElementById('apptTypeChart'), {
            type: 'pie',
            data: {
                labels: apptTypeLabels,
                datasets: [{
                    data: apptTypeCounts,
                    backgroundColor: colors.slice(0, apptTypeLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });

        // ─── Monthly Trend Line Chart ──────────────────────────────────
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Appointments',
                    data: trendCounts,
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    borderColor: '#4f46e5',
                    borderWidth: 3,
                    tension: 0.2,
                    pointBackgroundColor: '#4f46e5',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>

</body>

</html>