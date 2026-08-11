<?php
/**
 * Admin Appointment Management
 * - View all appointments with customer and vehicle details
 * - Change appointment status (Pending, Confirmed, Completed, Cancelled)
 * - Delete appointments
 * - Only accessible by admin users
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Ensure user is logged in and has admin role
if (empty($currentUser['person_id']) || $currentUser['role'] !== 'Admin') {
    header('Location: ../../login.php');
    exit;
}

// Handle AJAX actions (update status / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?");
            $stmt->execute([$newStatus, $appointmentId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid status.']);
        }
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch all appointments with customer and vehicle details
$stmt = $pdo->query("
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
        c.customer_id,
        p.first_name AS customer_first,
        p.last_name AS customer_last,
        p.email,
        p.phone,
        e.employee_id,
        emp.first_name AS employee_first,
        emp.last_name AS employee_last
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    JOIN customers c ON a.customer_id = c.customer_id
    JOIN persons p ON c.person_id = p.person_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    LEFT JOIN persons emp ON e.person_id = emp.person_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Manage Appointments";
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

        .status-select {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
        }
        .status-select:focus {
            border-color: #4f46e5;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(79,70,229,0.25);
        }

        .btn-delete {
            background: transparent;
            border: none;
            color: #ef4444;
            transition: color 0.2s;
        }
        .btn-delete:hover {
            color: #dc2626;
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

        /* ── MOBILE FRIENDLY TWEAKS ── */
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
            .table-dashboard th,
            .table-dashboard td {
                font-size: 0.8rem;
                padding: 8px 0;
            }
            .table-dashboard td {
                padding: 8px 0;
            }
            .status-select {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
            .btn-delete {
                font-size: 1rem;
                padding: 0.2rem 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .content-card {
                padding: 15px;
            }
            .table-dashboard th,
            .table-dashboard td {
                font-size: 0.7rem;
                padding: 6px 0;
            }
            .status-select {
                font-size: 0.65rem;
                padding: 0.15rem 0.3rem;
                min-height: 30px;
            }
            .btn-delete {
                font-size: 0.9rem;
                padding: 0.15rem 0.4rem;
            }
            .badge-status {
                font-size: 0.65rem;
                padding: 2px 10px;
            }
            .content-card .card-title {
                font-size: 1.1rem;
            }
            .page-header h4 {
                font-size: 1.2rem;
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
                <i class="fas fa-calendar-check me-2 text-primary"></i> Manage Appointments
            </h4>
            <div>
                <span class="badge bg-primary me-2">Total: <?= count($appointments) ?></span>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="content-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-list me-2"></i> All Appointments
            </h5>

            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <p>No appointments have been booked yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dashboard" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Date / Time</th>
                                <th>Status</th>
                                <th>Employee</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <tr data-appointment-id="<?= $appt['appointment_id'] ?>">
                                    <td><strong>#<?= htmlspecialchars($appt['appointment_id']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($appt['customer_first'] . ' ' . $appt['customer_last']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($appt['email']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($appt['make'] . ' ' . $appt['model']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($appt['registration_number'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($appt['appointment_type']) ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($appt['appointment_date'])) ?>
                                        <br><small><?= date('H:i', strtotime($appt['appointment_time'])) ?></small>
                                    </td>
                                    <td>
                                        <select class="status-select status-dropdown" data-id="<?= $appt['appointment_id'] ?>">
                                            <option value="Pending" <?= $appt['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Confirmed" <?= $appt['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Completed" <?= $appt['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Cancelled" <?= $appt['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <span class="badge-status d-none status-badge 
                                            <?= match($appt['status']) {
                                                'Pending' => 'badge-pending',
                                                'Confirmed' => 'badge-confirmed',
                                                'Completed' => 'badge-completed',
                                                'Cancelled' => 'badge-cancelled',
                                                default => ''
                                            } ?>">
                                            <?= htmlspecialchars($appt['status']) ?>
                                        </span>
                                    </td>
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
                                    <td>
                                        <button class="btn btn-sm btn-delete delete-btn" data-id="<?= $appt['appointment_id'] ?>" title="Delete appointment">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container-fluid -->

    <?php include '../../includes/footer.php'; ?>

</div> <!-- /dashboard-wrapper -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Enable tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle status change via dropdown
    $('.status-dropdown').change(function() {
        const $select = $(this);
        const appointmentId = $select.data('id');
        const newStatus = $select.val();
        const $row = $select.closest('tr');
        const $badge = $row.find('.status-badge');

        // Show loading state
        $select.prop('disabled', true);

        $.ajax({
            url: window.location.href, // same page
            method: 'POST',
            data: {
                action: 'update_status',
                appointment_id: appointmentId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update badge
                    $badge
                        .removeClass('badge-pending badge-confirmed badge-completed badge-cancelled')
                        .addClass(
                            newStatus === 'Pending' ? 'badge-pending' :
                            newStatus === 'Confirmed' ? 'badge-confirmed' :
                            newStatus === 'Completed' ? 'badge-completed' :
                            'badge-cancelled'
                        )
                        .text(newStatus)
                        .removeClass('d-none');
                    // Optionally hide the dropdown? We keep it visible.
                    // Show a temporary success message
                    $select.after('<span class="text-success ms-2 small"><i class="fas fa-check"></i></span>');
                    setTimeout(() => {
                        $select.siblings('.text-success').remove();
                    }, 2000);
                } else {
                    alert('Error updating status: ' + (response.error || 'Unknown error.'));
                    // Revert dropdown to original value
                    $select.val($badge.text().trim());
                }
            },
            error: function() {
                alert('Error updating status. Please try again.');
                $select.val($badge.text().trim());
            },
            complete: function() {
                $select.prop('disabled', false);
            }
        });
    });

    // Handle delete
    $('.delete-btn').click(function() {
        const $btn = $(this);
        const appointmentId = $btn.data('id');
        if (!confirm('Are you sure you want to delete appointment #' + appointmentId + '? This action cannot be undone.')) {
            return;
        }

        const $row = $btn.closest('tr');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'delete',
                appointment_id: appointmentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert('Error deleting appointment: ' + (response.error || 'Unknown error.'));
                }
            },
            error: function() {
                alert('Error deleting appointment. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i>');
            }
        });
    });
});
</script>
</body>
</html>