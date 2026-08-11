<?php
/**
 * Insurance Providers - Customer View
 * - Display all active insurance providers with full details
 * - Card-based layout with icons and badges
 * - No CRUD actions (view only)
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isset($currentUser['person_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Fetch only active providers (or all if you prefer)
$stmt = $pdo->prepare("
    SELECT * FROM insurance_providers 
    WHERE status = 'Active'
    ORDER BY provider_name
");
$stmt->execute();
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count totals
$total = count($providers);
$active = $total;
$inactive = $pdo->query("SELECT COUNT(*) FROM insurance_providers WHERE status = 'Inactive'")->fetchColumn();

$pageTitle = "Insurance Providers";
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
        .stat-card.total {
            border-left: 6px solid #4f46e5;
        }
        .stat-card.active {
            border-left: 6px solid #10b981;
        }
        .stat-card.inactive {
            border-left: 6px solid #ef4444;
        }

        /* ── Provider Cards ── */
        .provider-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #f0f2f5;
            height: 100%;
        }
        .provider-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .provider-card .provider-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
        }
        .provider-card .provider-detail {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            color: #374151;
        }
        .provider-card .provider-detail i {
            width: 20px;
            color: #11998e;
        }
        .provider-card .badge-status {
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-inactive {
            background: #fecaca;
            color: #991b1b;
        }
        .provider-card .website-link {
            color: #11998e;
            text-decoration: none;
            word-break: break-all;
        }
        .provider-card .website-link:hover {
            text-decoration: underline;
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
            .provider-card {
                padding: 16px 18px;
            }
            .provider-card .provider-name {
                font-size: 1.1rem;
            }
            .provider-card .provider-detail {
                font-size: 0.9rem;
                gap: 8px;
            }
            .provider-card .provider-detail i {
                width: 18px;
                font-size: 0.9rem;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
            .content-card .card-title {
                font-size: 1.1rem;
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
            .provider-card {
                padding: 12px 14px;
            }
            .provider-card .provider-name {
                font-size: 1rem;
            }
            .provider-card .provider-detail {
                font-size: 0.85rem;
                gap: 6px;
                margin: 6px 0;
            }
            .provider-card .provider-detail i {
                width: 16px;
                font-size: 0.85rem;
            }
            .provider-card .badge-status {
                font-size: 0.65rem;
                padding: 2px 10px;
            }
            .page-header h4 {
                font-size: 1.1rem;
            }
            .content-card .card-title {
                font-size: 1rem;
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
                <i class="fas fa-shield-alt me-2 text-primary"></i> Insurance Providers
            </h4>
            <!-- You can add an action button if needed -->
        </div>

        <!-- Statistics Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card total">
                    <div class="stat-label">Total Providers</div>
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card active">
                    <div class="stat-label">Active</div>
                    <div class="stat-number"><?= $active ?></div>
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card inactive">
                    <div class="stat-label">Inactive</div>
                    <div class="stat-number"><?= $inactive ?></div>
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Provider Cards -->
        <div class="content-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-list me-2"></i> Our Trusted Partners
            </h5>

            <?php if (empty($providers)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>No insurance providers are currently available.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($providers as $provider): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="provider-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="provider-name"><?= htmlspecialchars($provider['provider_name']) ?></div>
                                    <span class="badge-status badge-active">Active</span>
                                </div>

                                <?php if (!empty($provider['contact_person'])): ?>
                                    <div class="provider-detail">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($provider['contact_person']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($provider['phone'])): ?>
                                    <div class="provider-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?= htmlspecialchars($provider['phone']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($provider['email'])): ?>
                                    <div class="provider-detail">
                                        <i class="fas fa-envelope"></i>
                                        <a href="mailto:<?= htmlspecialchars($provider['email']) ?>" class="website-link">
                                            <?= htmlspecialchars($provider['email']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($provider['website'])): ?>
                                    <div class="provider-detail">
                                        <i class="fas fa-globe"></i>
                                        <a href="<?= htmlspecialchars($provider['website']) ?>" target="_blank" class="website-link">
                                            <?= htmlspecialchars($provider['website']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($provider['address'])): ?>
                                    <div class="provider-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($provider['address']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>