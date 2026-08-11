<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

/** @var array{person_id: int, first_name: string, last_name: string, role: string, email?: string} $currentUser */

// ---------- Fetch message count (unread) for badge ----------
$unreadCount = $pdo->query("SELECT COUNT(*) FROM messages WHERE receiver_person_id = " . (int)$currentUser['person_id'] . " AND is_read = 'No'")->fetchColumn();

// ---------- Fetch all messages with sender/receiver details ----------
$sql = "
    SELECT
        m.*,
        s.first_name AS sender_first, s.last_name AS sender_last, s.person_id AS sender_id,
        r.first_name AS receiver_first, r.last_name AS receiver_last, r.person_id AS receiver_id
    FROM messages m
    LEFT JOIN persons s ON m.sender_person_id = s.person_id
    LEFT JOIN persons r ON m.receiver_person_id = r.person_id
    ORDER BY m.sent_at DESC
";
$messages = $pdo->query($sql);

// ---------- Fetch vehicles for dropdown (optional) ----------
$vehicles = $pdo->query("SELECT vehicle_id, make, model, stock_number FROM vehicles ORDER BY make, model");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
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
        .badge-unread {
            background: #dc3545;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 700px;
        }
        .message-preview {
            max-height: 150px;
            overflow-y: auto;
        }

        /* ── Select2 ── */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
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
            .form-control, .form-select {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            .modal-dialog {
                margin: 0.5rem;
            }
            .modal-content {
                border-radius: 16px;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .select2-container .select2-selection--single {
                height: 34px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 34px;
                font-size: 0.85rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 34px;
            }
            .select2-container--bootstrap-5 .select2-selection {
                min-height: 34px;
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
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
            .select2-container .select2-selection--single {
                height: 32px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 32px;
                font-size: 0.85rem;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 32px;
            }
            .select2-container--bootstrap-5 .select2-selection {
                min-height: 32px;
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
                <h2><i class="fas fa-envelope text-primary me-2"></i>Messages</h2>
                <p class="text-muted">View and send messages across the system.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#composeModal">
                <i class="fas fa-pencil-alt me-1"></i> New Message
            </button>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Messages</h6>
                            <h2 class="fw-bold"><?= $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn() ?></h2>
                        </div>
                        <div class="stat-icon text-primary"><i class="fas fa-inbox"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Unread (for you)</h6>
                            <h2 class="fw-bold text-danger"><?= $unreadCount ?></h2>
                        </div>
                        <div class="stat-icon text-danger"><i class="fas fa-envelope"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#ffc107;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Sent by you</h6>
                            <h2 class="fw-bold text-warning"><?= $pdo->query("SELECT COUNT(*) FROM messages WHERE sender_person_id = " . (int)$currentUser['person_id'])->fetchColumn() ?></h2>
                        </div>
                        <div class="stat-icon text-warning"><i class="fas fa-paper-plane"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Messages</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="messagesTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Read</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter = 1; while ($row = $messages->fetch()): 
                            $sender = ($row['sender_first'] ? $row['sender_first'] . ' ' . $row['sender_last'] : 'Unknown');
                            $receiver = ($row['receiver_first'] ? $row['receiver_first'] . ' ' . $row['receiver_last'] : 'Unknown');
                            $isRead = $row['is_read'] === 'Yes';
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($sender) ?></td>
                                <td><?= htmlspecialchars($receiver) ?></td>
                                <td><?= htmlspecialchars($row['subject'] ?: '(no subject)') ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($row['sent_at'])) ?></td>
                                <td>
                                    <?php if ($isRead): ?>
                                        <span class="badge bg-secondary">Read</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewMessage(<?= $row['message_id'] ?>)"><i class="fas fa-eye"></i></button>
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

<!-- ========== COMPOSE MODAL ========== -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pencil-alt me-2"></i>New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="send_message.php" id="composeForm">
                <div class="modal-body">
                    <!-- Recipient search -->
                    <div class="mb-3">
                        <label class="form-label required">Recipient</label>
                        <select name="receiver_person_id" id="recipientSelect" class="form-select" style="width:100%;" required>
                            <option value="">Search for a person...</option>
                        </select>
                        <small class="text-muted">Type name, surname, or ID number.</small>
                    </div>
                    <!-- Optional vehicle -->
                    <div class="mb-3">
                        <label class="form-label">Related Vehicle (optional)</label>
                        <select name="vehicle_id" id="vehicleSelect" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['vehicle_id'] ?>"><?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['stock_number'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" placeholder="Brief subject line">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Message</label>
                        <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== VIEW MESSAGE MODAL ========== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // DataTable
    $('#messagesTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [6] }],
        language: { search: "Filter:", searchPlaceholder: "Search messages..." }
    });

    // Select2 for recipient search
    $('#recipientSelect').select2({
        dropdownParent: $('#composeModal'),
        placeholder: 'Search by surname or first name',
        minimumInputLength: 1,
        ajax: {
            url: 'get_persons.php',
            type: 'GET',
            dataType: 'json',
            data: function (params) {
                return { search: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: false
        }
    });
});

function viewMessage(id) {
    $.ajax({
        url: 'get_message.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>From:</strong> ${data.sender_first} ${data.sender_last}</p>
                        <p><strong>To:</strong> ${data.receiver_first} ${data.receiver_last}</p>
                        <p><strong>Subject:</strong> ${data.subject || '(no subject)'}</p>
                        <p><strong>Sent:</strong> ${data.sent_at}</p>
                        ${data.vehicle_id ? `<p><strong>Vehicle:</strong> ${data.make} ${data.model} (${data.stock_number})</p>` : ''}
                    </div>
                    <div class="col-md-12">
                        <hr>
                        <p><strong>Message:</strong></p>
                        <div class="p-3 bg-light rounded">${data.message.replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
            $('#viewModalBody').html(html);
            $('#viewModal').modal('show');
        },
        error: function() { alert('Error loading message.'); }
    });
}
</script>
</body>
</html>