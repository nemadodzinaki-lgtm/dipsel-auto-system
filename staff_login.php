<?php
session_start();

// Capture redirect parameter (staff might also need redirects)
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Clear any old errors
$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Dipsel Auto</title>
    <!-- Bootstrap 5, Icons, Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* ─── Same base styles as login.php ───────────────────────────── */
        /* We can reuse the same CSS, but change colors slightly for distinction */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0f4f9; color:#1a2634; min-height:100vh; }
        .login-wrapper { display:flex; min-height:100vh; overflow:hidden; }

        .brand-side {
            flex:0 0 45%;
            background: linear-gradient(145deg, #0b2a3e 0%, #1a4a6c 100%);
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            padding:40px; color:#fff; text-align:center; position:relative; overflow:hidden;
        }
        .brand-side::after {
            content:''; position:absolute; top:-50%; right:-30%; width:80%; height:200%;
            background: radial-gradient(circle at 70% 50%, rgba(45,212,191,0.08) 0%, transparent 70%);
            pointer-events:none;
        }
        .brand-side .brand-icon { font-size:4.5rem; margin-bottom:24px; color:#4fc3f7; filter:drop-shadow(0 8px 24px rgba(79,195,247,0.25)); }
        .brand-side h1 {
            font-size:3.2rem; font-weight:800; letter-spacing:-0.5px; line-height:1.1; margin-bottom:16px;
            background:linear-gradient(135deg, #ffffff 60%, #4fc3f7); -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .brand-side p { font-size:1.2rem; opacity:0.8; max-width:380px; margin:0 auto; line-height:1.7; }
        .brand-side .decor-lines { margin-top:40px; display:flex; gap:12px; justify-content:center; }
        .brand-side .decor-lines span {
            display:block; width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,0.15);
            animation:pulse-dot 2s infinite alternate;
        }
        .brand-side .decor-lines span:nth-child(2){ animation-delay:0.3s; }
        .brand-side .decor-lines span:nth-child(3){ animation-delay:0.6s; }
        @keyframes pulse-dot {
            0% { background:rgba(255,255,255,0.15); transform:scale(0.8); }
            100% { background:rgba(79,195,247,0.6); transform:scale(1.2); }
        }

        .form-side {
            flex:1; display:flex; align-items:center; justify-content:center; padding:24px;
            background:#f0f4f9; position:relative;
        }
        .form-side::before {
            content:''; position:absolute; top:-30%; left:-20%; width:60%; height:60%;
            background:radial-gradient(circle, rgba(79,195,247,0.04) 0%, transparent 70%);
            pointer-events:none;
        }

        .login-card {
            max-width:440px; width:100%; background:#ffffff; border-radius:32px; padding:40px 36px;
            box-shadow:0 24px 64px rgba(11,26,46,0.10), 0 8px 24px rgba(0,0,0,0.04);
            border:1px solid rgba(255,255,255,0.6); backdrop-filter:blur(4px);
            transition:box-shadow 0.3s ease; position:relative;
        }
        .login-card:hover { box-shadow:0 32px 80px rgba(11,26,46,0.14); }

        .home-link {
            position:absolute; top:20px; right:24px; color:#4a5c6e; text-decoration:none; font-weight:600;
            font-size:0.9rem; display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
            border-radius:40px; background:rgba(11,26,46,0.04); transition:all 0.2s;
        }
        .home-link:hover { background:rgba(11,26,46,0.08); color:#0b1a2e; transform:translateX(-2px); }

        .card-header-custom { margin-bottom:28px; }
        .card-header-custom h2 {
            font-weight:800; font-size:1.8rem; color:#0b1a2e; letter-spacing:-0.5px;
            display:flex; align-items:center; gap:10px;
        }
        .card-header-custom h2 i { color:#1d4a7a; background:rgba(29,74,122,0.08); padding:8px; border-radius:14px; }
        .card-header-custom .subtitle { color:#5a6f82; font-size:0.95rem; margin-top:2px; }

        .register-row {
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;
            margin-bottom:24px; padding:12px 16px; background:#f8fafc; border-radius:16px;
        }
        .register-row span { color:#4a5c6e; font-weight:500; }
        .btn-register {
            border-radius:40px; padding:8px 24px; border:2px solid #1d4a7a; color:#1d4a7a;
            font-weight:700; transition:all 0.25s ease; background:transparent; font-size:0.9rem;
            display:inline-flex; align-items:center; gap:6px;
        }
        .btn-register:hover { background:#1d4a7a; color:#fff; transform:translateX(4px); box-shadow:0 4px 12px rgba(29,74,122,0.25); }

        .error-alert {
            background:#fee9e7; border-left:6px solid #e74c3c; border-radius:16px; padding:14px 18px;
            margin-bottom:24px; display:flex; align-items:center; gap:14px;
            box-shadow:0 4px 12px rgba(231,76,60,0.08);
        }
        .error-alert .icon { font-size:1.6rem; color:#e74c3c; flex-shrink:0; }
        .error-alert .content { flex:1; color:#7a2a2a; font-weight:500; }
        .error-alert .close-btn { background:none; border:none; color:#a05050; font-size:1.2rem; cursor:pointer; padding:0 4px; transition:color 0.2s; }
        .error-alert .close-btn:hover { color:#6b2a2a; }

        .form-control {
            border-radius:14px; padding:12px 18px; border:1.5px solid #e2e8f0; background:#fafcff;
            transition:all 0.25s; font-size:0.95rem;
        }
        .form-control:focus { border-color:#1d4a7a; box-shadow:0 0 0 4px rgba(29,74,122,0.08); background:#ffffff; }
        .form-label { font-weight:600; color:#1a2c40; font-size:0.9rem; margin-bottom:4px; }

        .password-wrapper { position:relative; }
        .password-wrapper .toggle-pwd {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:#4a5c6e; cursor:pointer; font-size:1.1rem; padding:4px;
            transition:color 0.2s;
        }
        .password-wrapper .toggle-pwd:hover { color:#0b1a2e; }

        .btn-primary-custom {
            background:linear-gradient(135deg, #0b1a2e, #1d4a7a); border:none; padding:14px;
            font-weight:700; border-radius:40px; letter-spacing:0.3px; transition:all 0.3s ease;
            color:#fff; width:100%; font-size:1rem; display:inline-flex; align-items:center; justify-content:center; gap:10px;
            box-shadow:0 4px 16px rgba(29,74,122,0.20);
        }
        .btn-primary-custom:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(29,74,122,0.30); background:linear-gradient(135deg, #1d4a7a, #0b1a2e); }

        .form-check-input:checked { background-color:#1d4a7a; border-color:#1d4a7a; }
        .form-check-label { font-weight:500; color:#1a2c40; }

        .forgot-link { color:#1d4a7a; font-weight:600; text-decoration:none; font-size:0.9rem; transition:color 0.2s; display:inline-flex; align-items:center; gap:4px; }
        .forgot-link:hover { color:#0b1a2e; text-decoration:underline; }

        .register-link { color:#1d4a7a; font-weight:700; text-decoration:none; transition:color 0.2s; }
        .register-link:hover { color:#0b1a2e; text-decoration:underline; }

        .footer-links {
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;
            margin-top:20px; padding-top:16px; border-top:1px solid #eef2f7;
        }

        /* Staff-specific extra style */
        .customer-link {
            text-align:center; margin-top:12px; font-size:0.9rem;
        }
        .customer-link a { color:#1d4a7a; font-weight:600; text-decoration:none; }
        .customer-link a:hover { text-decoration:underline; }

        @media (max-width:992px) { .brand-side { display:none; } .form-side { padding:16px; } .login-card { padding:28px 20px; } .card-header-custom h2 { font-size:1.5rem; } }
        @media (max-width:576px) {
            .login-card { padding:20px 16px; border-radius:24px; }
            .home-link { top:12px; right:16px; font-size:0.8rem; padding:4px 12px; }
            .register-row { flex-direction:column; align-items:stretch; text-align:center; }
            .btn-register { justify-content:center; }
            .footer-links { flex-direction:column; align-items:center; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Brand Side (slightly different message) -->
        <div class="brand-side">
            <div class="brand-icon"><i class="bi bi-person-badge"></i></div>
            <h1>Staff Portal</h1>
            <p>Administrators &amp; Employees — manage your dashboard.</p>
            <div class="decor-lines"><span></span><span></span><span></span></div>
        </div>

        <!-- Right Form Side -->
        <div class="form-side">
            <div class="login-card">
                <!-- Home link -->
                <a href="index.php" class="home-link"><i class="bi bi-house"></i> Home</a>

                <div class="card-header-custom">
                    <h2><i class="bi bi-shield-lock"></i> Staff Login</h2>
                    <p class="subtitle">Access your staff dashboard</p>
                </div>

                <!-- Register row (staff don't register here, but we can show a link back) -->
                <div class="register-row">
                    <span><i class="bi bi-person"></i> Not staff?</span>
                    <a href="login.php" class="btn-register">Customer Login <i class="bi bi-arrow-right"></i></a>
                </div>

                <!-- Error messages -->
                <?php if ($loginError): ?>
                    <div class="error-alert" id="errorAlert">
                        <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        <div class="content"><?= htmlspecialchars($loginError) ?></div>
                        <button class="close-btn" id="closeErrorBtn">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="api/auth/login.php" method="POST" id="loginForm" novalidate>
                    <!-- Hidden field to indicate staff login -->
                    <input type="hidden" name="user_type" value="staff">

                    <div class="mb-3">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" required>
                        <div class="invalid-feedback" id="emailFeedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required minlength="8">
                            <button type="button" class="toggle-pwd" id="togglePwd"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="invalid-feedback" id="pwdFeedback">Password is required.</div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>

                    <!-- Redirect hidden field (preserved) -->
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-box-arrow-in-right"></i> Staff Login
                    </button>
                </form>

                <!-- Footer links -->
                <div class="footer-links">
                    <a href="forgot_password.php" class="forgot-link"><i class="bi bi-key"></i> Forgot Password?</a>
                    <span style="color:#4a5c6e; font-size:0.9rem;">
                        Not staff? <a href="login.php" class="register-link">Customer Login</a>
                    </span>
                </div>

                <!-- Link to customer login -->
                <div class="customer-link">
                    <i class="bi bi-person"></i> Are you a customer?
                    <a href="login.php">Login here</a>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts (same as login.php) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ─── Close error alert ──────────────────────────────────────────
        document.getElementById('closeErrorBtn')?.addEventListener('click', function() {
            this.closest('.error-alert').style.display = 'none';
        });

        // ─── Password visibility toggle ────────────────────────────────
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput = document.getElementById('password');
        toggleBtn.addEventListener('click', function() {
            const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwdInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });

        // ─── Client-side validation ──────────────────────────────────────
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const emailFeedback = document.getElementById('emailFeedback');
        const pwdFeedback = document.getElementById('pwdFeedback');

        emailInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                emailFeedback.textContent = 'Email is required.';
            } else if (!this.validity.valid) {
                this.classList.add('is-invalid');
                emailFeedback.textContent = 'Please enter a valid email address.';
            } else {
                this.classList.remove('is-invalid');
            }
        });

        pwdInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                pwdFeedback.textContent = 'Password is required.';
            } else if (this.value.length < 8) {
                this.classList.add('is-invalid');
                pwdFeedback.textContent = 'Password must be at least 8 characters.';
            } else {
                this.classList.remove('is-invalid');
            }
        });

        form.addEventListener('submit', function(e) {
            let valid = true;
            const email = emailInput.value.trim();
            if (email === '' || !emailInput.validity.valid) {
                emailInput.classList.add('is-invalid');
                emailFeedback.textContent = email === '' ? 'Email is required.' : 'Please enter a valid email address.';
                valid = false;
            } else {
                emailInput.classList.remove('is-invalid');
            }

            const pwd = pwdInput.value.trim();
            if (pwd === '' || pwd.length < 8) {
                pwdInput.classList.add('is-invalid');
                pwdFeedback.textContent = pwd === '' ? 'Password is required.' : 'Password must be at least 8 characters.';
                valid = false;
            } else {
                pwdInput.classList.remove('is-invalid');
            }

            if (!valid) {
                e.preventDefault();
                if (emailInput.classList.contains('is-invalid')) emailInput.focus();
                else if (pwdInput.classList.contains('is-invalid')) pwdInput.focus();
            }
        });
    </script>
</body>
</html>