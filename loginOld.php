<?php
session_start();

// Clear any old errors after displaying (we'll display below)
$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Dipsel Auto System</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ─── Global ────────────────────────────────────────────────────── */
        body {
            background: #f4f7fb;
        }

        /* ─── Left side – gradient overlay ─────────────────────────────── */
        .left-side {
            background: url('assets/images/login-bg.jpg') center/cover no-repeat;
            position: relative;
        }
        .left-side .overlay {
            background: linear-gradient(145deg, rgba(11, 26, 46, 0.85), rgba(29, 74, 122, 0.75));
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            padding: 40px;
        }
        .left-side .overlay h1 {
            font-size: 3.5rem;
            font-weight: 800;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .left-side .overlay p {
            font-size: 1.3rem;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        /* ─── Right side – colourful gradient ──────────────────────────── */
        .right-col {
            background: linear-gradient(135deg, #eef2f7 0%, #d9e2ec 100%);
            padding: 30px 20px;
        }

        /* ─── Login Card ────────────────────────────────────────────────── */
        .card-login {
            max-width: 430px;
            width: 100%;
            padding: 35px 30px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(11, 26, 46, 0.12);
            border-top: 6px solid #1d4a7a;
            transition: box-shadow 0.3s;
            position: relative;
        }
        .card-login:hover {
            box-shadow: 0 30px 70px rgba(11, 26, 46, 0.18);
        }

        /* ─── Home link (top right) ────────────────────────────────────── */
        .home-link {
            position: absolute;
            top: 18px;
            right: 20px;
            font-size: 0.9rem;
            color: #1d4a7a;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .home-link i {
            margin-right: 4px;
        }
        .home-link:hover {
            color: #0b1a2e;
            transform: translateX(-2px);
        }

        .card-login h2 {
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 4px;
        }
        .card-login h2 i {
            color: #1d4a7a;
            margin-right: 8px;
        }
        .card-login .subtitle {
            color: #5a6f82;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }

        /* ─── Form elements ────────────────────────────────────────────── */
        .form-control {
            border-radius: 12px;
            padding: 10px 16px;
            border: 1px solid #dde3e9;
            background: #fafcff;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1d4a7a;
            box-shadow: 0 0 0 0.25rem rgba(29, 74, 122, 0.15);
            background: #fff;
        }
        label {
            font-weight: 600;
            color: #1a2c40;
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        /* ─── Primary Button ────────────────────────────────────────────── */
        .btn-primary {
            background: linear-gradient(135deg, #0b1a2e, #1d4a7a);
            border: none;
            padding: 13px;
            font-weight: 700;
            border-radius: 40px;
            letter-spacing: 0.3px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(29, 74, 122, 0.35);
            background: linear-gradient(135deg, #1d4a7a, #0b1a2e);
        }

        /* ─── Checkbox ──────────────────────────────────────────────────── */
        .form-check-input:checked {
            background-color: #1d4a7a;
            border-color: #1d4a7a;
        }

        /* ─── Create Account link ──────────────────────────────────────── */
        .create-account-link {
            color: #1d4a7a;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .create-account-link:hover {
            color: #0b1a2e;
            text-decoration: underline;
        }

        /* ─── Responsive tweaks ────────────────────────────────────────── */
        @media (max-width: 576px) {
            .card-login {
                padding: 25px 18px;
            }
            .left-side .overlay h1 {
                font-size: 2.2rem;
            }
            .home-link {
                top: 14px;
                right: 14px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">

    <div class="row vh-100">

        <!-- Left Side -->
        <div class="col-lg-6 d-none d-lg-flex left-side">
            <div class="overlay">
                <h1>Dipsel Auto System</h1>
                <p>Buy • Sell • Service • Workshop</p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-col">

            <div class="card-login">

                <!-- Home link (top right) -->
                <a href="index.php" class="home-link">
                    <i class="bi bi-house"></i> Home
                </a>

                <h2><i class="bi bi-box-arrow-in-right"></i> Login</h2>
                <p class="subtitle">Welcome back</p>

                <!-- Error message -->
                <?php if ($loginError): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="api/auth/login.php" method="POST">

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>

                    <button class="btn btn-primary w-100">Login</button>
                </form>

               
<div class="text-center mt-2">
    <a href="forgot_password.php" class="text-decoration-none" style="font-size:0.9rem; color:#1d4a7a;">
        <i class="bi bi-key"></i> Forgot Password?
    </a>
</div>

                <div class="text-center mt-3">
                    <a href="register.php" class="create-account-link">Create Account</a>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JS (optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>