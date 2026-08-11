<?php
session_start();
$error = $_SESSION['fp_error'] ?? null;
$success = $_SESSION['fp_success'] ?? null;
unset($_SESSION['fp_error'], $_SESSION['fp_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Dipsel Auto</title>
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
        .card-forgot {
            max-width: 450px;
            width: 100%;
            padding: 35px 30px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(11,26,46,0.12);
            border-top: 6px solid #1d4a7a;
        }
        .card-forgot h2 {
            font-weight: 700;
            color: #0b1a2e;
        }
        .card-forgot .subtitle {
            color: #5a6f82;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0b1a2e, #1d4a7a);
            border: none;
            padding: 12px;
            font-weight: 700;
            border-radius: 40px;
            transition: transform 0.2s, box-shadow 0.2s;
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
        .back-link {
            color: #1d4a7a;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card-forgot">
    <h2><i class="bi bi-envelope"></i> Forgot Password</h2>
    <p class="subtitle">Enter your email address and we'll send you a reset link.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="api/auth/forgot_password.php" method="POST">
        <div class="mb-3">
            <label>Email address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100">Send Reset Link</button>
    </form>

    <div class="text-center mt-3">
        <a href="login.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>