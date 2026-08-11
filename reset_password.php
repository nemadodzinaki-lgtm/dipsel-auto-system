<?php
session_start();
require_once __DIR__ . '/config/database.php'; // adjust path

$token = $_GET['token'] ?? '';
$error = null;
$validToken = false;
$personId = null;

if (empty($token)) {
    $error = 'No reset token provided.';
} else {
    // Check token in DB
    try {
        $stmt = $pdo->prepare("SELECT person_id, reset_token_expiry FROM persons WHERE reset_token = :token AND reset_token_expiry > NOW()");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $validToken = true;
            $personId = $row['person_id'];
        } else {
            $error = 'Invalid or expired reset token. Please request a new one.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again.';
        error_log('Reset token validation error: ' . $e->getMessage());
    }
}

// Handle form submission via AJAX or separate script? We'll use a separate POST handler.
// We'll keep the form action to api/auth/reset_password.php and pass token as hidden.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Dipsel Auto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f4f7fb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card-reset {
            max-width: 450px;
            width: 100%;
            padding: 35px 30px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(11,26,46,0.12);
            border-top: 6px solid #1d4a7a;
        }
        .card-reset h2 {
            font-weight: 700;
            color: #0b1a2e;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0b1a2e, #1d4a7a);
            border: none;
            padding: 12px;
            font-weight: 700;
            border-radius: 40px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(29,74,122,0.35);
        }
        .form-control {
            border-radius: 12px;
            padding: 10px 16px;
            border: 1px solid #dde3e9;
            background: #fafcff;
        }
        .form-control:focus {
            border-color: #1d4a7a;
            box-shadow: 0 0 0 0.25rem rgba(29,74,122,0.15);
        }
    </style>
</head>
<body>

<div class="card-reset">
    <h2><i class="bi bi-lock"></i> Reset Password</h2>
    <p class="text-muted">Enter your new password below.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <div class="text-center mt-3">
            <a href="forgot_password.php" class="btn btn-outline-primary">Request New Link</a>
        </div>
    <?php elseif ($validToken): ?>
        <!-- Display reset form -->
        <?php
        // If there is a session message from the reset handler, show it
        $resetError = $_SESSION['reset_error'] ?? null;
        $resetSuccess = $_SESSION['reset_success'] ?? null;
        unset($_SESSION['reset_error'], $_SESSION['reset_success']);
        ?>
        <?php if ($resetError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($resetError) ?></div>
        <?php endif; ?>
        <?php if ($resetSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($resetSuccess) ?></div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <form action="api/auth/reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="mb-3">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" class="form-control" required minlength="8">
                </div>
                <button class="btn btn-primary w-100">Reset Password</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>