<?php
session_start();

// Clear old errors after displaying once (we'll handle display below)
$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_input'] ?? [];
// Unset to avoid showing twice
unset($_SESSION['register_errors'], $_SESSION['register_input']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Customer Account – Dipsel Auto</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">

    <style>
        /* ─── Reset & Base ─────────────────────────────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f4f9;
            color: #1a2634;
            min-height: 100vh;
        }

        /* ─── Full page layout ────────────────────────────────────────── */
        .register-wrapper {
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ─── Left Brand Side ──────────────────────────────────────────── */
        .brand-side {
            flex: 0 0 45%;
            background: linear-gradient(145deg, #0b1a2e 0%, #1a3a5c 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .brand-side::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle at 70% 50%, rgba(45, 212, 191, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-side .brand-icon {
            font-size: 4.5rem;
            margin-bottom: 24px;
            color: #4fc3f7;
            filter: drop-shadow(0 8px 24px rgba(79, 195, 247, 0.25));
        }

        .brand-side h1 {
            font-size: 3.2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.1;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 60%, #4fc3f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-side p {
            font-size: 1.2rem;
            opacity: 0.8;
            max-width: 380px;
            margin: 0 auto;
            line-height: 1.7;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .brand-side .decor-lines {
            margin-top: 40px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .brand-side .decor-lines span {
            display: block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            animation: pulse-dot 2s infinite alternate;
        }

        .brand-side .decor-lines span:nth-child(2) { animation-delay: 0.3s; }
        .brand-side .decor-lines span:nth-child(3) { animation-delay: 0.6s; }

        @keyframes pulse-dot {
            0% { background: rgba(255, 255, 255, 0.15); transform: scale(0.8); }
            100% { background: rgba(79, 195, 247, 0.6); transform: scale(1.2); }
        }

        /* ─── Right Form Side ──────────────────────────────────────────── */
        .form-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f0f4f9;
            position: relative;
        }

        .form-side::before {
            content: '';
            position: absolute;
            top: -30%;
            left: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(79, 195, 247, 0.04) 0%, transparent 70%);
            pointer-events: none;
        }

        /* ─── Register Card ────────────────────────────────────────────── */
        .register-card {
            max-width: 600px;
            width: 100%;
            background: #ffffff;
            border-radius: 32px;
            padding: 40px 36px;
            box-shadow: 0 24px 64px rgba(11, 26, 46, 0.10), 0 8px 24px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(4px);
            transition: box-shadow 0.3s ease;
            position: relative;
        }

        .register-card:hover {
            box-shadow: 0 32px 80px rgba(11, 26, 46, 0.14);
        }

        /* ─── Home link ────────────────────────────────────────────────── */
        .home-link {
            position: absolute;
            top: 20px;
            right: 24px;
            color: #4a5c6e;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 40px;
            background: rgba(11, 26, 46, 0.04);
            transition: all 0.2s;
        }

        .home-link:hover {
            background: rgba(11, 26, 46, 0.08);
            color: #0b1a2e;
            transform: translateX(-2px);
        }

        .home-link i {
            font-size: 0.9rem;
        }

        /* ─── Card Header ──────────────────────────────────────────────── */
        .card-header-custom {
            margin-bottom: 28px;
        }

        .card-header-custom h2 {
            font-weight: 800;
            font-size: 1.8rem;
            color: #0b1a2e;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-custom h2 i {
            color: #1d4a7a;
            background: rgba(29, 74, 122, 0.08);
            padding: 8px;
            border-radius: 14px;
        }

        .card-header-custom .subtitle {
            color: #5a6f82;
            font-size: 0.95rem;
            margin-top: 2px;
        }

        /* ─── Login button row ────────────────────────────────────────── */
        .login-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 16px;
        }

        .login-row span {
            color: #4a5c6e;
            font-weight: 500;
        }

        .btn-login {
            border-radius: 40px;
            padding: 8px 24px;
            border: 2px solid #1d4a7a;
            color: #1d4a7a;
            font-weight: 700;
            transition: all 0.25s ease;
            background: transparent;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-login:hover {
            background: #1d4a7a;
            color: #fff;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(29, 74, 122, 0.25);
        }

        /* ─── Error Alert (beautiful) ──────────────────────────────────── */
        .error-alert {
            background: #fee9e7;
            border-left: 6px solid #e74c3c;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.08);
        }

        .error-alert .icon {
            font-size: 1.6rem;
            color: #e74c3c;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .error-alert .content {
            flex: 1;
        }

        .error-alert .content ul {
            margin: 0;
            padding-left: 20px;
            color: #7a2a2a;
            font-weight: 500;
        }

        .error-alert .content ul li {
            list-style-type: disc;
            margin-bottom: 2px;
        }

        .error-alert .close-btn {
            background: none;
            border: none;
            color: #a05050;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0 4px;
            transition: color 0.2s;
        }

        .error-alert .close-btn:hover {
            color: #6b2a2a;
        }

        /* ─── Form elements ────────────────────────────────────────────── */
        .form-control, .form-select {
            border-radius: 14px;
            padding: 12px 18px;
            border: 1.5px solid #e2e8f0;
            background: #fafcff;
            transition: all 0.25s;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1d4a7a;
            box-shadow: 0 0 0 4px rgba(29, 74, 122, 0.08);
            background: #ffffff;
        }

        .form-label {
            font-weight: 600;
            color: #1a2c40;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .form-label .required {
            color: #e74c3c;
            margin-left: 2px;
        }

        /* ─── ID/Passport Toggle ───────────────────────────────────────── */
        .identifier-toggle {
            display: flex;
            gap: 8px;
            background: #f1f4f9;
            padding: 4px;
            border-radius: 40px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
        }

        .identifier-toggle .toggle-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 40px;
            background: transparent;
            font-weight: 600;
            font-size: 0.85rem;
            color: #4a5c6e;
            transition: all 0.25s;
            cursor: pointer;
        }

        .identifier-toggle .toggle-btn.active {
            background: #ffffff;
            color: #0b1a2e;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .identifier-toggle .toggle-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.5);
        }

        .identifier-field {
            transition: all 0.3s ease;
        }

        .identifier-field.hidden {
            display: none;
        }

        /* ─── Password strength meter ──────────────────────────────────── */
        .password-strength {
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 6px;
            overflow: hidden;
            transition: background 0.3s;
        }

        .password-strength .bar {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width 0.3s, background 0.3s;
        }

        .password-strength-text {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 4px;
            color: #4a5c6e;
        }

        /* ─── Primary Button ────────────────────────────────────────────── */
        .btn-primary-custom {
            background: linear-gradient(135deg, #0b1a2e, #1d4a7a);
            border: none;
            padding: 14px;
            font-weight: 700;
            border-radius: 40px;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            color: #fff;
            width: 100%;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(29, 74, 122, 0.20);
        }

        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(29, 74, 122, 0.30);
            background: linear-gradient(135deg, #1d4a7a, #0b1a2e);
        }

        /* ─── Checkbox ──────────────────────────────────────────────────── */
        .form-check-input:checked {
            background-color: #1d4a7a;
            border-color: #1d4a7a;
        }

        .form-check-label {
            font-weight: 500;
            color: #1a2c40;
        }

        /* ─── Terms ────────────────────────────────────────────────────── */
        .terms {
            text-align: center;
            margin-top: 20px;
            color: #5a6f82;
            font-size: 0.85rem;
        }

        .terms a {
            color: #1d4a7a;
            font-weight: 600;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        /* ─── Responsive ────────────────────────────────────────────────── */
        @media (max-width: 992px) {
            .brand-side {
                display: none;
            }
            .form-side {
                padding: 16px;
            }
            .register-card {
                padding: 28px 20px;
            }
            .card-header-custom h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 20px 16px;
                border-radius: 24px;
            }
            .home-link {
                top: 12px;
                right: 16px;
                font-size: 0.8rem;
                padding: 4px 12px;
            }
            .login-row {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .btn-login {
                justify-content: center;
            }
            .identifier-toggle {
                flex-direction: row;
            }
        }
    </style>
</head>
<body>

<div class="register-wrapper">

    <!-- Left Brand Side -->
    <div class="brand-side">
        <div class="brand-icon"><i class="bi bi-car-front-fill"></i></div>
        <h1>Dipsel Auto</h1>
        <p>Buy • Sell • Service — Your trusted automotive partner.</p>
        <div class="decor-lines">
            <span></span><span></span><span></span>
        </div>
    </div>

    <!-- Right Form Side -->
    <div class="form-side">

        <div class="register-card">

            <!-- Home link -->
            <a href="index.php" class="home-link">
                <i class="bi bi-house"></i> Home
            </a>

            <!-- Header -->
            <div class="card-header-custom">
                <h2><i class="bi bi-person-plus"></i> Create Account</h2>
                <p class="subtitle">Register as a new customer</p>
            </div>

            <!-- Login row -->
            <div class="login-row">
                <span><i class="bi bi-box-arrow-in-right"></i> Already a member?</span>
                <a href="login.php" class="btn-login">
                    Login <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <!-- ─── Error messages (beautiful) ──────────────────────── -->
            <?php if (!empty($errors)): ?>
                <div class="error-alert" id="errorAlert">
                    <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div class="content">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button class="close-btn" id="closeErrorBtn" aria-label="Close error">&times;</button>
                </div>
            <?php endif; ?>

            <!-- ─── Registration Form ────────────────────────────────── -->
            <form action="api/auth/register.php" method="POST" enctype="multipart/form-data" id="registerForm" novalidate>

                <!-- First & Middle Name -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control"
                               value="<?= htmlspecialchars($old['middle_name'] ?? '') ?>">
                    </div>
                </div>

                <!-- Last Name -->
                <div class="mt-3">
                    <label class="form-label">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                </div>

                <!-- Gender & DOB -->
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Gender <span class="required">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Male" <?= isset($old['gender']) && $old['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= isset($old['gender']) && $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth <span class="required">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="<?= htmlspecialchars($old['date_of_birth'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- ─── ID / Passport Toggle ────────────────────────── -->
                <div class="mt-3">
                    <label class="form-label">Identification <span class="required">*</span></label>
                    <div class="identifier-toggle" id="identifierToggle">
                        <button type="button" class="toggle-btn active" data-type="id">ID Number</button>
                        <button type="button" class="toggle-btn" data-type="passport">Passport Number</button>
                    </div>

                    <!-- ID Number field -->
                    <div class="identifier-field" id="idField">
                        <input type="text" name="id_number" maxlength="13" class="form-control"
                               placeholder="e.g. 8001015009087"
                               value="<?= htmlspecialchars($old['id_number'] ?? '') ?>">
                    </div>

                    <!-- Passport Number field (hidden by default) -->
                    <div class="identifier-field hidden" id="passportField">
                        <input type="text" name="passport_number" class="form-control"
                               placeholder="e.g. A1234567"
                               value="<?= htmlspecialchars($old['passport_number'] ?? '') ?>">
                    </div>
                    <small class="text-muted">Select ID or Passport to provide your identification number.</small>
                </div>

                <!-- Phone & Email -->
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Password & Confirm -->
                <div class="mt-3">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                    <div class="password-strength mt-1">
                        <div class="bar" id="strengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="strengthText">Minimum 8 characters</div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    <div id="passwordMatchMsg" class="mt-1" style="font-size:0.85rem; font-weight:500;"></div>
                </div>

                <!-- Profile Photo -->
                <div class="mt-3">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                </div>

                <!-- Nationality & Province -->
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="country" class="form-control"
                               value="<?= htmlspecialchars($old['country'] ?? 'South Africa') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Province</label>
                        <select name="province" class="form-select">
                            <?php
                            $provinces = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'];
                            foreach ($provinces as $prov): ?>
                                <option value="<?= $prov ?>" <?= isset($old['province']) && $old['province'] === $prov ? 'selected' : '' ?>>
                                    <?= $prov ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- City, Address, Postal -->
                <div class="mt-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control"
                           value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                </div>
                <div class="mt-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                </div>
                <div class="mt-3">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>">
                </div>

                <!-- Driver License & Preferred Contact -->
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Driver License</label>
                        <input type="text" name="driver_license" class="form-control"
                               value="<?= htmlspecialchars($old['driver_license'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preferred Contact</label>
                        <select name="preferred_contact" class="form-select">
                            <?php
                            $contacts = ['Email', 'Phone', 'SMS'];
                            foreach ($contacts as $contact): ?>
                                <option value="<?= $contact ?>" <?= isset($old['preferred_contact']) && $old['preferred_contact'] === $contact ? 'selected' : '' ?>>
                                    <?= $contact ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Marketing Consent -->
                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" name="marketing_consent" value="1" id="marketing"
                        <?= isset($old['marketing_consent']) && $old['marketing_consent'] == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="marketing">I’d like to receive promotions and updates</label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary-custom mt-4">
                    <i class="bi bi-person-check"></i> Create Account
                </button>

            </form>

            <!-- Terms -->
            <div class="terms">
                By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ─── Custom JS ────────────────────────────────────────────────────── -->
<script>
    (function() {
        'use strict';

        // ─── Close error alert ──────────────────────────────────────────
        const closeBtn = document.getElementById('closeErrorBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                const alert = this.closest('.error-alert');
                if (alert) alert.style.display = 'none';
            });
        }

        // ─── ID / Passport toggle ──────────────────────────────────────
        const toggleBtns = document.querySelectorAll('.identifier-toggle .toggle-btn');
        const idField = document.getElementById('idField');
        const passportField = document.getElementById('passportField');

        // Get existing values to pre-fill
        const idVal = document.querySelector('input[name="id_number"]').value.trim();
        const passportVal = document.querySelector('input[name="passport_number"]').value.trim();

        // Determine which one to show initially based on values
        let activeType = 'id';
        if (passportVal && !idVal) {
            activeType = 'passport';
        } else if (!idVal && !passportVal) {
            activeType = 'id'; // default
        } else if (idVal) {
            activeType = 'id';
        }

        function setActive(type) {
            // Update buttons
            toggleBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.type === type);
            });
            // Show/hide fields
            if (type === 'id') {
                idField.classList.remove('hidden');
                passportField.classList.add('hidden');
                // Clear passport field to avoid confusion
                document.querySelector('input[name="passport_number"]').value = '';
            } else {
                passportField.classList.remove('hidden');
                idField.classList.add('hidden');
                document.querySelector('input[name="id_number"]').value = '';
            }
        }

        // Set initial state
        setActive(activeType);

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.dataset.type;
                setActive(type);
            });
        });

        // ─── Password strength meter ────────────────────────────────────
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        function checkPasswordStrength(pwd) {
            let score = 0;
            if (pwd.length >= 8) score++;
            if (pwd.length >= 12) score++;
            if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
            if (/\d/.test(pwd)) score++;
            if (/[^a-zA-Z0-9]/.test(pwd)) score++;
            return score; // 0-5
        }

        function updateStrengthMeter() {
            const pwd = passwordInput.value;
            const score = checkPasswordStrength(pwd);
            let width = 0;
            let color = '#e2e8f0';
            let text = '';

            if (pwd.length === 0) {
                text = 'Enter a password (min 8 characters)';
            } else {
                const percent = (score / 5) * 100;
                width = percent;
                if (score <= 1) {
                    color = '#e74c3c';
                    text = 'Weak — add more characters, numbers, and symbols';
                } else if (score <= 2) {
                    color = '#f39c12';
                    text = 'Fair — add uppercase and special characters';
                } else if (score <= 3) {
                    color = '#f1c40f';
                    text = 'Good — almost there!';
                } else if (score <= 4) {
                    color = '#2ecc71';
                    text = 'Strong — great password!';
                } else {
                    color = '#27ae60';
                    text = 'Very strong — excellent!';
                }
            }

            strengthBar.style.width = width + '%';
            strengthBar.style.background = color;
            strengthText.textContent = text;
        }

        passwordInput.addEventListener('input', updateStrengthMeter);
        updateStrengthMeter(); // initial

        // ─── Password match validation ──────────────────────────────────
        const confirmInput = document.getElementById('confirm_password');
        const matchMsg = document.getElementById('passwordMatchMsg');

        function checkMatch() {
            const pwd = passwordInput.value;
            const confirm = confirmInput.value;
            if (confirm.length === 0) {
                matchMsg.textContent = '';
                matchMsg.style.color = '';
                return;
            }
            if (pwd === confirm) {
                matchMsg.textContent = '✓ Passwords match';
                matchMsg.style.color = '#27ae60';
            } else {
                matchMsg.textContent = '✗ Passwords do not match';
                matchMsg.style.color = '#e74c3c';
            }
        }

        passwordInput.addEventListener('input', checkMatch);
        confirmInput.addEventListener('input', checkMatch);

        // ─── Form submit validation ──────────────────────────────────────
        const form = document.getElementById('registerForm');

        form.addEventListener('submit', function(e) {
            const pwd = passwordInput.value;
            const confirm = confirmInput.value;

            // Check password strength (optional, but we'll enforce min 8)
            if (pwd.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                passwordInput.focus();
                return;
            }

            if (pwd !== confirm) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
                confirmInput.focus();
                return;
            }

            // Check that either ID or Passport is filled
            const idVal = document.querySelector('input[name="id_number"]').value.trim();
            const passportVal = document.querySelector('input[name="passport_number"]').value.trim();
            if (!idVal && !passportVal) {
                e.preventDefault();
                alert('Please provide either an ID number or a Passport number.');
                return;
            }

            // If ID is active and id is empty, but passport has value, we may need to switch? But we already cleared.
            // This is just extra safety.
        });

        // ─── Toggle helper for ID/passport if user manually clears one ──
        // We'll allow both fields to be populated, but we only submit one because we clear the other.
        // No additional logic needed.

    })();
</script>

</body>
</html>