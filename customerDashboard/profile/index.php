<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

if (!isset($currentUser['person_id'])) {
    header('Location: ../../login.php');
    exit;
}

$personId = (int)$currentUser['person_id'];

// Fetch customer profile
$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.customer_id,
        c.customer_number,
        c.driver_license,
        c.preferred_contact,
        c.marketing_consent,
        c.loyalty_points
    FROM persons p
    INNER JOIN customers c ON p.person_id = c.person_id
    WHERE p.person_id = ?
");
$stmt->execute([$personId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Customer not found.');
}

$success = '';
$errors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE persons SET email = ?, phone = ? WHERE person_id = ?");
        $stmt->execute([$email, $phone, $personId]);

        $user['email'] = $email;
        $user['phone'] = $phone;
        $success = 'Profile updated successfully.';
    }
}

$pageTitle = "My Profile";
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

        .section-title {
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: #11998e;
        }

        .readonly-field {
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            cursor: not-allowed;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 0.95rem;
        }
        .readonly-field:focus {
            box-shadow: none;
        }

        .editable-field {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            font-size: 0.95rem;
            background: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .editable-field:focus {
            border-color: #11998e;
            box-shadow: 0 0 0 3px rgba(17,153,142,0.2);
            background: #fff;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border: none;
            padding: 10px 40px;
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

        .profile-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1f2937;
        }

        .loyalty-badge {
            background: #fbbf24;
            color: #1f2937;
            padding: 4px 14px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-primary-custom {
            background: #11998e;
            color: #fff;
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
            .profile-name {
                font-size: 1.4rem;
            }
            .section-title {
                font-size: 1.1rem;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .readonly-field, .editable-field {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            .btn-primary-custom {
                width: 100%;
                justify-content: center;
                padding: 12px 20px;
            }
            .page-header h4 {
                font-size: 1.2rem;
            }
            .loyalty-badge {
                font-size: 0.75rem;
                padding: 3px 10px;
            }
            .badge {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .content-card {
                padding: 12px;
                border-radius: 16px;
            }
            .profile-name {
                font-size: 1.2rem;
            }
            .section-title {
                font-size: 1rem;
            }
            .form-label {
                font-size: 0.8rem;
            }
            .readonly-field, .editable-field {
                font-size: 0.85rem;
                padding: 6px 10px;
                border-radius: 10px;
            }
            .btn-primary-custom {
                padding: 14px 20px;
                font-size: 1rem;
            }
            .page-header h4 {
                font-size: 1.1rem;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .loyalty-badge {
                font-size: 0.7rem;
                padding: 2px 8px;
            }
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.6rem;
            }
            .text-center.mb-4 {
                margin-bottom: 1rem !important;
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
                <i class="fas fa-user-circle me-2 text-primary"></i> My Profile
            </h4>
        </div>

        <!-- Profile Card -->
        <div class="content-card">
            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= implode('<br>', $errors) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Header -->
            <div class="text-center mb-4">
                <div class="profile-name">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </div>
                <div>
                    <span class="badge badge-primary-custom"><?= htmlspecialchars($user['role']) ?></span>
                    <span class="badge bg-secondary"><?= htmlspecialchars($user['status']) ?></span>
                    <span class="loyalty-badge ms-2"><i class="fas fa-star"></i> <?= (int)$user['loyalty_points'] ?> pts</span>
                </div>
            </div>

            <form method="POST">
                <div class="row g-4">
                    <!-- Left Column: Personal Information (Read-only) -->
                    <div class="col-md-6">
                        <div class="section-title"><i class="fas fa-id-card"></i> Personal Information</div>

                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['gender'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID Number</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['id_number'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Passport Number</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['passport_number'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['country'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Province</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['province'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['city'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control readonly-field" rows="2" readonly><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Postal Code</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>" readonly>
                        </div>
                    </div>

                    <!-- Right Column: Contact & Account (Editable) -->
                    <div class="col-md-6">
                        <div class="section-title"><i class="fas fa-address-book"></i> Contact & Account</div>

                        <!-- Editable fields -->
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control editable-field" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (Cell) <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control editable-field" value="<?= htmlspecialchars($user['phone']) ?>" required>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Customer Number</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['customer_number']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver License</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['driver_license'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preferred Contact</label>
                            <input class="form-control readonly-field" value="<?= htmlspecialchars($user['preferred_contact'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marketing Consent</label>
                            <input class="form-control readonly-field" value="<?= $user['marketing_consent'] ? 'Yes' : 'No' ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Loyalty Points</label>
                            <input class="form-control readonly-field" value="<?= (int)$user['loyalty_points'] ?>" readonly>
                        </div>

                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>