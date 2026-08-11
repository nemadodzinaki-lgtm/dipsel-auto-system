<?php
/**
 * Admin Profile – Clean & Focused
 * - Only accessible to SuperAdmin and Admin roles
 * - View all personal details (read-only)
 * - Edit phone and email only
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, last_name: string, role: string, email?: string, phone?: string} $currentUser */

// Restrict to admins only
if (!in_array($currentUser['role'], ['SuperAdmin', 'Admin'])) {
    header('Location: index.php');
    exit;
}

$personId = (int) $currentUser['person_id'];
$role = $currentUser['role'];

// ---------- Fetch person details ----------
$stmt = $pdo->prepare("SELECT * FROM persons WHERE person_id = ?");
$stmt->execute([$personId]);
$person = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$person) {
    die('User not found.');
}

// ---------- Handle form submission (edit phone, email) ----------
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($phone)) $errors[] = 'Phone is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if (empty($errors)) {
        $sql = "UPDATE persons SET phone = :phone, email = :email WHERE person_id = :person_id";
        $params = [':phone' => $phone, ':email' => $email, ':person_id' => $personId];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_email'] = $email;

        $success = 'Profile updated successfully.';
        $person['phone'] = $phone;
        $person['email'] = $email;
    }
}

$roleDisplay = $role === 'SuperAdmin' ? 'Super Administrator' : 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Profile Wrapper (same as dashboard) ── */
        .profile-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: margin-left 0.3s;
        }

        /* ── Page Header ── */
        .profile-header {
            background: linear-gradient(135deg, #1a2a4a 0%, #2d4a7a 100%);
            border-radius: 24px;
            padding: 32px 36px;
            color: #fff;
            box-shadow: 0 15px 35px rgba(26, 42, 74, 0.25);
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        .profile-header::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
            pointer-events: none;
        }
        .profile-avatar {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.6);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        /* ── Cards ── */
        .info-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            background: #fff;
            height: 100%;
        }
        .info-card .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f2f5;
            font-weight: 600;
            color: #1a2a4a;
            padding: 1rem 1.5rem;
        }

        /* ── Info items ── */
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-item .label {
            width: 140px;
            font-weight: 500;
            color: #6b7280;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .info-item .value {
            flex: 1;
            color: #1f2937;
            font-weight: 500;
        }

        /* ── Form ── */
        .form-label.required::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #1a2a4a, #2d4a7a);
            border: none;
            border-radius: 40px;
            padding: 0.6rem 2rem;
            font-weight: 600;
            color: #fff;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 42, 74, 0.3);
            color: #fff;
        }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .profile-wrapper {
                margin-left: 0;
            }
            .profile-header {
                padding: 24px 28px;
            }
            .profile-header h2 {
                font-size: 1.6rem;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .info-item .label {
                width: 110px;
                font-size: 0.8rem;
            }
            .info-item .value {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 576px) {
            .profile-header {
                padding: 18px 20px;
                border-radius: 18px;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center;
            }
            .profile-header .ms-auto {
                margin-left: 0 !important;
                margin-top: 10px;
            }
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            .profile-header h2 {
                font-size: 1.3rem;
            }
            .profile-header p {
                font-size: 0.9rem;
            }
            .profile-header .badge {
                font-size: 0.75rem;
                padding: 0.4rem 1rem;
            }
            .info-card .card-header {
                font-size: 1rem;
                padding: 0.8rem 1rem;
            }
            .info-card .card-body {
                padding: 0.8rem 1rem;
            }
            .info-item {
                flex-wrap: wrap;
                padding: 8px 0;
            }
            .info-item .label {
                width: 100%;
                font-size: 0.75rem;
                color: #6b7280;
            }
            .info-item .value {
                width: 100%;
                font-size: 0.85rem;
                padding-left: 0;
            }
            .form-control {
                font-size: 0.85rem;
                padding: 0.5rem 0.8rem;
            }
            .btn-primary-custom {
                width: 100%;
                justify-content: center;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            .alert {
                border-radius: 12px !important;
                padding: 0.8rem 1rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="profile-wrapper">
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid mt-4 px-4">

        <!-- Profile Header -->
        <div class="profile-header d-flex align-items-center flex-wrap gap-4">
            <div class="flex-shrink-0">
                <img src="../../assets/uploads/profiles/default.png" class="profile-avatar" alt="Profile Photo">
            </div>
            <div>
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($person['first_name'] . ' ' . $person['last_name']) ?></h2>
                <p class="mb-0 opacity-75"><i class="fas fa-shield-alt me-2"></i><?= $roleDisplay ?></p>
                <p class="mb-0 opacity-75 small"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($person['email'] ?? '') ?></p>
            </div>
            <div class="ms-auto">
                <span class="badge bg-light text-dark px-4 py-2 rounded-pill"><i class="far fa-calendar-alt me-1"></i> Last login: <?= date('d M Y, H:i', strtotime($person['last_login'] ?? 'now')) ?></span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-pill shadow-sm"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-pill shadow-sm"><?= implode('<br>', $errors) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Two columns: Personal Info (left) and Edit Form (right) -->
        <div class="row g-4">
            <!-- Left: Personal Information (Read-Only) -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="card-header"><i class="fas fa-id-card me-2"></i>Personal Information</div>
                    <div class="card-body">
                        <?php
                        $readonlyFields = [
                            'first_name' => 'First Name',
                            'middle_name' => 'Middle Name',
                            'last_name' => 'Last Name',
                            'gender' => 'Gender',
                            'date_of_birth' => 'Date of Birth',
                            'id_number' => 'ID Number',
                            'passport_number' => 'Passport Number',
                            'country' => 'Country',
                            'province' => 'Province',
                            'city' => 'City',
                            'address' => 'Address',
                            'postal_code' => 'Postal Code',
                            'status' => 'Status'
                        ];
                        foreach ($readonlyFields as $field => $label):
                            $value = $person[$field] ?? 'N/A';
                            if ($field === 'date_of_birth' && $value && $value !== 'N/A') $value = date('Y-m-d', strtotime($value));
                        ?>
                        <div class="info-item">
                            <div class="label"><?= $label ?></div>
                            <div class="value"><?= htmlspecialchars($value) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Edit Profile -->
            <div class="col-lg-6">
                <div class="info-card">
                    <div class="card-header"><i class="fas fa-edit me-2"></i>Edit Profile</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="mb-3">
                                <label class="form-label required">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($person['phone'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($person['email'] ?? '') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-1"></i> Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>