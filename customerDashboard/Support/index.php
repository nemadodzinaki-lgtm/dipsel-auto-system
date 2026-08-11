<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

if ($_SESSION['role'] != 'Customer') {
    header('Location: ../login.php');
    exit;
}

$person_id = $_SESSION['person_id'];

// --- Handle reply submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $receiver_id = (int) $_POST['receiver_id'];
    $subject     = trim($_POST['subject']);
    $message     = trim($_POST['message']);

    if (!empty($message)) {
        $sql = "INSERT INTO messages (sender_person_id, receiver_person_id, subject, message, sent_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$person_id, $receiver_id, $subject, $message]);
        $_SESSION['success'] = "Reply sent successfully.";
    } else {
        $_SESSION['error'] = "Message cannot be empty.";
    }
    header('Location: index.php');
    exit;
}

// --- Mark message as read (AJAX) ---
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $msg_id = (int) $_GET['id'];
    $check = $pdo->prepare("SELECT message_id FROM messages WHERE message_id = ? AND receiver_person_id = ?");
    $check->execute([$msg_id, $person_id]);
    if ($check->rowCount() > 0) {
        $update = $pdo->prepare("UPDATE messages SET is_read = 'Yes' WHERE message_id = ?");
        $update->execute([$msg_id]);
        echo 'ok';
    } else {
        http_response_code(403);
    }
    exit;
}

// --- Fetch all messages for this customer (incoming only) ---
$sql = "SELECT m.*,
               CONCAT(p.first_name, ' ', p.last_name) AS sender_name,
               p.email AS sender_email,
               m.sender_person_id
        FROM messages m
        JOIN persons p ON m.sender_person_id = p.person_id
        WHERE m.receiver_person_id = ?
        ORDER BY m.sent_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$person_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_person_id = ? AND is_read = 'No'");
$unreadStmt->execute([$person_id]);
$unreadCount = $unreadStmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }
        .dashboard-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            background: #f4f7fc;
            transition: all 0.3s;
        }
        .unread { font-weight: bold; background-color: #f8f9fa; }
        .badge-unread { background-color: #dc3545; }
        .table-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .conversation .message-item { padding: 10px; border-radius: 10px; margin-bottom: 10px; max-width: 80%; }
        .message-item.sent { background-color: #d1e7dd; align-self: flex-end; }
        .message-item.received { background-color: #f8f9fa; align-self: flex-start; }
        .conversation { display: flex; flex-direction: column; max-height: 400px; overflow-y: auto; }
        .message-meta { font-size: 0.8rem; color: #6c757d; }

        /* ── RESPONSIVE TWEAKS ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }
            .page-header .badge {
                align-self: flex-start;
            }
            .table th, .table td {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .btn-sm {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
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
            .modal-dialog {
                margin: 0.5rem;
            }
            .modal-content {
                border-radius: 16px;
            }
            .conversation .message-item {
                max-width: 90%;
            }
            .conversation {
                max-height: 300px;
            }
        }

        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 1.3rem;
            }
            .page-header p {
                font-size: 0.9rem;
            }
            .table th, .table td {
                font-size: 0.75rem;
                padding: 0.3rem 0.2rem;
            }
            .badge {
                font-size: 0.7rem;
            }
            .btn-sm {
                padding: 0.15rem 0.4rem;
                font-size: 0.65rem;
            }
            .conversation .message-item {
                max-width: 100%;
                padding: 8px;
            }
            .conversation {
                max-height: 250px;
            }
            #replyMessage {
                font-size: 0.9rem;
            }
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
            .card-header h5 {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.1rem;
            }
            .page-header .badge {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }
            .table th, .table td {
                font-size: 0.7rem;
                padding: 0.2rem 0.15rem;
            }
            .table td .btn {
                font-size: 0.6rem;
                padding: 0.1rem 0.3rem;
            }
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            .modal-body {
                padding: 0.8rem;
            }
            .conversation .message-item {
                padding: 6px;
                font-size: 0.9rem;
            }
            .message-meta {
                font-size: 0.7rem;
            }
            .conversation {
                max-height: 200px;
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
                <h2><i class="fas fa-envelope text-primary me-2"></i>Support Messages</h2>
                <p class="text-muted">All messages sent to you.</p>
            </div>
            <div>
                <span class="badge bg-danger rounded-pill fs-6"><?= $unreadCount ?> unread</span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Messages Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-inbox me-2"></i>Inbox</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>From</th>
                                <th>Subject</th>
                                <th>Sent</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($messages)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No messages yet.</td></tr>
                        <?php else: $counter=1; foreach ($messages as $msg): ?>
                            <tr class="<?= ($msg['is_read'] == 'No') ? 'unread' : '' ?>">
                                <td><?= $counter++ ?></td>
                                <td>
                                    <?= htmlspecialchars($msg['sender_name']) ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($msg['sender_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($msg['subject']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?></td>
                                <td>
                                    <?php if ($msg['is_read'] == 'No'): ?>
                                        <span class="badge bg-danger">Unread</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal"
                                            onclick="openConversation(<?= $msg['message_id'] ?>, <?= $msg['sender_person_id'] ?>, '<?= addslashes($msg['sender_name']) ?>')">
                                        <i class="fas fa-eye"></i> View / Reply
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../includes/footer.php'; ?>
</div>

<!-- View / Reply Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Conversation with <span id="otherName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Conversation area -->
                <div id="conversationContainer" class="conversation p-2 mb-3">
                    <!-- messages will be loaded here via AJAX -->
                </div>
                <hr>
                <!-- Reply form -->
                <form method="POST" action="index.php" id="replyForm">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="receiver_id" id="replyReceiverId">
                    <input type="hidden" name="subject" id="replySubject">
                    <div class="mb-3">
                        <label for="replyMessage" class="form-label">Your Reply</label>
                        <textarea name="message" id="replyMessage" rows="3" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openConversation(messageId, otherId, otherName) {
    document.getElementById('otherName').textContent = otherName;
    document.getElementById('replyReceiverId').value = otherId;
    // We'll fetch the conversation via AJAX
    fetch('get_conversation.php?other_id=' + otherId)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('conversationContainer');
            container.innerHTML = '';
            if (data.length === 0) {
                container.innerHTML = '<p class="text-muted">No messages in this conversation.</p>';
            } else {
                data.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'message-item ' + (msg.sender_person_id == <?= $person_id ?> ? 'sent' : 'received');
                    div.innerHTML = `
                        <div>${msg.message}</div>
                        <div class="message-meta">${msg.sender_name} - ${new Date(msg.sent_at).toLocaleString()}</div>
                    `;
                    container.appendChild(div);
                });
                // Scroll to bottom
                container.scrollTop = container.scrollHeight;
                // Set subject for reply (use the subject of the first message, but we can also use the latest)
                if (data.length > 0) {
                    const last = data[data.length - 1];
                    document.getElementById('replySubject').value = (last.subject.startsWith('Re:') ? '' : 'Re: ') + last.subject;
                }
            }
            // Mark this specific message as read (if it's incoming)
            if (messageId) {
                fetch('index.php?action=read&id=' + messageId)
                    .then(() => {
                        // Update the row's badge and class without reloading
                        const rows = document.querySelectorAll('tr');
                        rows.forEach(row => {
                            const btn = row.querySelector('button[onclick*="openConversation(' + messageId + ',"]');
                            if (btn) {
                                const badge = row.querySelector('.badge');
                                if (badge) {
                                    badge.className = 'badge bg-success';
                                    badge.textContent = 'Read';
                                }
                                row.classList.remove('unread');
                            }
                        });
                    })
                    .catch(() => {});
            }
        })
        .catch(err => {
            document.getElementById('conversationContainer').innerHTML = '<p class="text-danger">Failed to load conversation.</p>';
        });
}
</script>

</body>
</html>