<?php
/**
 * Book a Test Drive
 * - Customer selects a vehicle, date/time, duration, and adds notes
 * - Pre-selects a vehicle if vehicle_id is passed via GET
 */

require_once '../../includes/auth.php';
require_once '../../config/database.php';

// Only customers can access
if (!isset($currentUser['person_id']) || $currentUser['role'] !== 'Customer') {
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

$message = '';
$error = '';

// Pre-select vehicle from GET parameter
$preselectedVehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $test_drive_date = $_POST['test_drive_date'] ?? '';
    $test_drive_time = $_POST['test_drive_time'] ?? '';
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 30);
    $customer_notes = trim($_POST['customer_notes'] ?? '');

    // Validate
    if ($vehicle_id <= 0) {
        $error = 'Please select a vehicle.';
    } elseif (empty($test_drive_date)) {
        $error = 'Please select a date.';
    } elseif (empty($test_drive_time)) {
        $error = 'Please select a time.';
    } elseif ($duration_minutes < 15) {
        $error = 'Duration must be at least 15 minutes.';
    } else {
        // Check if vehicle is still available
        $stmt = $pdo->prepare("
            SELECT status, available_for_sale 
            FROM vehicles 
            WHERE vehicle_id = ? AND status = 'Available' AND available_for_sale = 'Yes'
        ");
        $stmt->execute([$vehicle_id]);
        if (!$stmt->fetch()) {
            $error = 'Selected vehicle is not available for test drive.';
        } else {
            // Insert test drive booking
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicle_test_drives (
                        vehicle_id, customer_id, test_drive_date, test_drive_time,
                        duration_minutes, customer_notes, status, drivers_license_verified
                    ) VALUES (?, ?, ?, ?, ?, ?, 'Pending', 'Pending')
                ");
                $stmt->execute([
                    $vehicle_id,
                    $customerId,
                    $test_drive_date,
                    $test_drive_time,
                    $duration_minutes,
                    $customer_notes
                ]);
                $message = 'Test drive booked successfully! We will confirm your booking shortly.';
                // Reset preselected on success
                $preselectedVehicleId = 0;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch available vehicles for dropdown
$stmt = $pdo->query("
    SELECT vehicle_id, make, model, registration_number, price, colour, manufacture_year
    FROM vehicles
    WHERE status = 'Available' AND available_for_sale = 'Yes'
    ORDER BY make, model
");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Test Drive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* ── Global ── */
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f0f2f5;
            transition: all 0.3s;
        }
        .page-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }
        .page-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .page-header {
            background: linear-gradient(135deg, #1a5c3a, #4fc3f7);
            padding: 30px;
            text-align: center;
            color: #fff;
        }
        .page-header h2 {
            font-weight: 600;
        }
        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-green {
            background: #1a7a4a;
            border-color: #1a7a4a;
            color: #fff;
            border-radius: 50px;
            padding: 10px 40px;
            font-weight: 600;
            touch-action: manipulation;
        }
        .btn-green:hover {
            background: #13603a;
            border-color: #13603a;
            color: #fff;
        }
        .btn-back {
            background: #6c757d;
            border-color: #6c757d;
            color: #fff;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            touch-action: manipulation;
        }
        .btn-back:hover {
            background: #5a6268;
            border-color: #545b62;
            color: #fff;
        }
        .vehicle-option {
            padding: 12px 16px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            transition: background 0.2s;
            cursor: pointer;
            min-height: 55px;
            display: flex;
            align-items: center;
        }
        .vehicle-option:hover {
            background: #e9ecef;
        }
        .vehicle-option input[type="radio"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        .vehicle-option .vehicle-details {
            display: inline-block;
        }
        .vehicle-option.selected {
            background: #d4edda;
            border-color: #28a745;
        }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }
            .page-header h2 {
                font-size: 1.4rem;
            }
            .page-header p {
                font-size: 0.9rem;
            }
            .card-body {
                padding: 1.5rem !important;
            }
            .vehicle-option {
                padding: 14px;
                min-height: 56px;
            }
            .vehicle-option .vehicle-details {
                font-size: 0.9rem;
            }
            .btn-green, .btn-back {
                padding: 12px 20px;
                font-size: 0.95rem;
                width: 100%;
                justify-content: center;
            }
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 12px;
            }
            .d-flex.justify-content-between .btn {
                width: 100%;
            }
            .form-control {
                font-size: 0.95rem;
                padding: 0.6rem 0.75rem;
            }
            .page-wrapper {
                padding-left: 10px;
                padding-right: 10px;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 16px;
            }
            .page-header h2 {
                font-size: 1.2rem;
            }
            .card-body {
                padding: 1rem !important;
            }
            .vehicle-option {
                padding: 12px;
                min-height: 50px;
            }
            .vehicle-option input[type="radio"] {
                width: 22px;
                height: 22px;
                margin-right: 10px;
            }
            .vehicle-option .vehicle-details {
                font-size: 0.85rem;
            }
            .btn-green, .btn-back {
                padding: 14px 20px;
                font-size: 1rem;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .form-control {
                font-size: 0.9rem;
                padding: 0.5rem 0.6rem;
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

    <div class="container py-4 page-wrapper">
        <div class="page-card card">
            <div class="page-header">
                <h2><i class="fas fa-car me-2"></i>Book a Test Drive</h2>
                <p>Select a vehicle and choose your preferred date and time</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($vehicles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-car-side fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No vehicles are currently available for test drives.</p>
                        <a href="../vehicle_sales/index.php" class="btn btn-back"><i class="fas fa-arrow-left me-1"></i> Back to Showroom</a>
                    </div>
                <?php else: ?>
                    <form method="POST" id="testDriveForm">
                        <div class="mb-4">
                            <label class="form-label required">Select Vehicle</label>
                            <div class="row g-2">
                                <?php foreach ($vehicles as $v): ?>
                                    <?php $checked = ($preselectedVehicleId === (int)$v['vehicle_id']) ? 'checked' : ''; ?>
                                    <div class="col-md-6">
                                        <div class="vehicle-option <?= $checked ? 'selected' : '' ?>">
                                            <input type="radio" name="vehicle_id" value="<?= $v['vehicle_id'] ?>" id="vehicle_<?= $v['vehicle_id'] ?>" <?= $checked ?> required>
                                            <label for="vehicle_<?= $v['vehicle_id'] ?>" class="vehicle-details">
                                                <strong><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></strong>
                                                <br><small class="text-muted">
                                                    <?= htmlspecialchars($v['manufacture_year']) ?> · 
                                                    <?= htmlspecialchars($v['colour']) ?> · 
                                                    Reg: <?= htmlspecialchars($v['registration_number'] ?? 'N/A') ?>
                                                    <br>Price: R <?= number_format($v['price'], 2) ?>
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Date</label>
                                    <input type="date" name="test_drive_date" class="form-control" min="<?= $today ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label required">Time</label>
                                    <input type="time" name="test_drive_time" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" name="duration_minutes" class="form-control" value="30" min="15" step="5">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="customer_notes" class="form-control" rows="3" placeholder="Any special requirements or questions..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="../vehicle_sales/index.php" class="btn btn-back"><i class="fas fa-arrow-left me-1"></i> Back to Showroom</a>
                            <button type="submit" class="btn btn-green">
                                <i class="fas fa-calendar-check me-2"></i>Book Test Drive
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Set default time to current time + 1 hour (rounded)
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        now.setHours(now.getHours() + 1);
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const defaultTime = hours + ':' + minutes;
        const timeInput = document.querySelector('input[name="test_drive_time"]');
        if (timeInput) {
            timeInput.value = defaultTime;
        }
    });
</script>
</body>
</html>