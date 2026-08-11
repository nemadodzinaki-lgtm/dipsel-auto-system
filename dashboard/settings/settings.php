<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Helper
function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['setting_value'] : '';
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $textFields = [
        'company_name', 'footer_about', 'footer_email',
        'footer_phone', 'footer_address',
        'facebook_url', 'twitter_url', 'instagram_url', 'tiktok_url'
    ];
    foreach ($textFields as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
    }

    // Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $filePath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
            $relativePath = 'uploads/logos/' . $filename;
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'logo_path'");
            $stmt->execute([$relativePath]);
        }
    }

    header('Location: settings.php?updated=1');
    exit;
}

// Fetch all settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Count social links
$socialKeys = ['facebook_url', 'twitter_url', 'instagram_url', 'tiktok_url'];
$socialCount = 0;
foreach ($socialKeys as $key) {
    if (!empty($settings[$key])) $socialCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - System Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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

        /* ── Form Cards ── */
        .section-card .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .section-card .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
        }
        .form-label.required::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .current-logo {
            max-height: 60px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 4px;
            background: #fff;
        }
        .file-input-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
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
            }
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h5 {
                font-size: 1.1rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .file-input-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            .file-input-wrapper .form-control {
                width: 100% !important;
            }
            .current-logo {
                max-height: 50px;
            }
            .section-card .card-body {
                padding: 1rem;
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
            .stat-card h5 {
                font-size: 1rem;
            }
            .stat-card h6 {
                font-size: 0.8rem;
            }
            .stat-card i {
                font-size: 1.5rem;
            }
            .section-card .card-header h5 {
                font-size: 1rem;
            }
            .section-card .card-body {
                padding: 0.8rem;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
                padding: 0.5rem 0.8rem;
            }
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
            .file-input-wrapper .form-control {
                font-size: 0.85rem;
            }
            .current-logo {
                max-height: 40px;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="dashboard-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4">

        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-cogs text-primary me-2"></i>System Settings</h2>
                <p class="text-muted">Configure company information, social media, branding, and website preferences.</p>
            </div>
            <button type="submit" form="settingsForm" class="btn btn-primary rounded-pill px-4">
                <i class="fas fa-save me-1"></i> Save Changes
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> Settings updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm">
                    <div class="card-body d-flex justify-content-between">
                        <div><h6 class="text-muted">Company Name</h6><h5><?= htmlspecialchars($settings['company_name'] ?? 'Not Set') ?></h5></div>
                        <i class="fas fa-building fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex justify-content-between">
                        <div><h6 class="text-muted">Social Networks</h6><h5><?= $socialCount ?></h5></div>
                        <i class="fas fa-share-alt fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color:#0d6efd;">
                    <div class="card-body d-flex justify-content-between">
                        <div><h6 class="text-muted">Status</h6><h5 class="text-success">Online</h5></div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm" style="border-left-color:#ffc107;">
                    <div class="card-body d-flex justify-content-between">
                        <div><h6 class="text-muted">Last Update</h6><h5><?= date('d M Y') ?></h5></div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="settingsForm" action="settings.php" method="post" enctype="multipart/form-data">

            <!-- Company Settings -->
            <div class="card shadow-sm section-card mb-4">
                <div class="card-header"><h5><i class="fas fa-building me-2"></i>Company Settings</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label required">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Logo</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="logo" accept="image/*" class="form-control" style="width:auto;">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <img src="../<?= htmlspecialchars($settings['logo_path']) ?>" alt="Current Logo" class="current-logo">
                                    <span class="text-muted small">Current logo</span>
                                <?php endif; ?>
                            </div>
                            <div class="form-text">Upload a new image to replace the current logo.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card shadow-sm section-card mb-4">
                <div class="card-header"><h5><i class="fas fa-phone me-2"></i>Contact Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" name="footer_email" class="form-control" value="<?= htmlspecialchars($settings['footer_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="footer_phone" class="form-control" value="<?= htmlspecialchars($settings['footer_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="footer_address" class="form-control" value="<?= htmlspecialchars($settings['footer_address'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">About Text (Footer)</label>
                            <textarea name="footer_about" rows="3" class="form-control"><?= htmlspecialchars($settings['footer_about'] ?? '') ?></textarea>
                            <div class="form-text">This text appears in the footer of the website.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="card shadow-sm section-card mb-4">
                <div class="card-header"><h5><i class="fas fa-share-alt me-2"></i>Social Media</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label><i class="fab fa-facebook-f me-1"></i> Facebook URL</label>
                            <input type="text" name="facebook_url" class="form-control" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        <div class="col-md-6">
                            <label><i class="fab fa-x-twitter me-1"></i> Twitter / X URL</label>
                            <input type="text" name="twitter_url" class="form-control" value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/yourhandle">
                        </div>
                        <div class="col-md-6">
                            <label><i class="fab fa-instagram me-1"></i> Instagram URL</label>
                            <input type="text" name="instagram_url" class="form-control" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/yourprofile">
                        </div>
                        <div class="col-md-6">
                            <label><i class="fab fa-tiktok me-1"></i> TikTok URL</label>
                            <input type="text" name="tiktok_url" class="form-control" value="<?= htmlspecialchars($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@yourhandle">
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>