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
    <title>Create Customer Account</title>

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
            background: url('https://via.placeholder.com/800x1200/1a2a3a/ffffff?text=Dipsel+Auto') center/cover no-repeat;
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
            position: relative;
        }

        /* ─── Register Card ────────────────────────────────────────────── */
        .register-card {
            max-width: 540px;
            width: 100%;
            padding: 35px 30px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(11, 26, 46, 0.12);
            border-top: 6px solid #1d4a7a;
            transition: box-shadow 0.3s;
            position: relative; /* for absolute positioning of home link */
        }
        .register-card:hover {
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

        .register-card h2 {
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 2px;
        }
        .register-card h2 i {
            color: #1d4a7a;
            margin-right: 8px;
        }

        .register-card .subtitle {
            color: #5a6f82;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }

        /* ─── Login button ──────────────────────────────────────────────── */
        .btn-outline-primary {
            border-radius: 40px;
            padding: 10px 28px;
            border-color: #1d4a7a;
            color: #1d4a7a;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-outline-primary:hover {
            background: #1d4a7a;
            color: #fff;
            border-color: #1d4a7a;
            transform: translateX(4px);
        }

        /* ─── Form elements ────────────────────────────────────────────── */
        .form-control, .form-select {
            border-radius: 12px;
            padding: 10px 16px;
            border: 1px solid #dde3e9;
            background: #fafcff;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
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

        /* ─── Error alert ──────────────────────────────────────────────── */
        .alert-danger ul {
            margin: 0;
            padding-left: 20px;
        }

        /* ─── Responsive tweaks ────────────────────────────────────────── */
        @media (max-width: 576px) {
            .register-card {
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
                <p>Buy • Sell • Service Vehicles</p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center right-col">

            <div class="register-card">

                <!-- Home link (top right) -->
                <a href="index.php" class="home-link">
                    <i class="bi bi-house"></i> Home
                </a>

                <h2><i class="bi bi-person-plus"></i> Create Account</h2>
                <p class="subtitle">Register as Customer</p>

                <!-- "Already have an account?" button -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted">Already have an account?</span>
                    <a href="login.php" class="btn btn-outline-primary btn-sm">
                        Login <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- ─── Error messages ────────────────────────────────── -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- ─── Registration Form ────────────────────────────── -->
                <form action="api/auth/register.php" method="POST" enctype="multipart/form-data" id="registerForm">

                    <!-- First & Middle Name -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" class="form-control"
                                   value="<?= htmlspecialchars($old['middle_name'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Last Name -->
                    <div class="mb-3">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                    </div>

                    <!-- Gender & DOB -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male" <?= isset($old['gender']) && $old['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= isset($old['gender']) && $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="<?= htmlspecialchars($old['date_of_birth'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- ID & Passport -->
                    <div class="mb-3">
                        <label>ID Number</label>
                        <input type="text" name="id_number" maxlength="13" class="form-control"
                               value="<?= htmlspecialchars($old['id_number'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Passport Number</label>
                        <input type="text" name="passport_number" class="form-control"
                               value="<?= htmlspecialchars($old['passport_number'] ?? '') ?>">
                    </div>

                    <!-- Phone & Email -->
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    </div>

                    <!-- Password & Confirm -->
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>

                    <!-- Profile Photo -->
                    <div class="mb-3">
                        <label>Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control">
                    </div>

                    <!-- Nationality & Province -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nationality</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= htmlspecialchars($old['country'] ?? 'South Africa') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Province</label>
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
                    <div class="mb-3">
                        <label>City</label>
                        <input type="text" name="city" class="form-control"
                               value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control"
                               value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>">
                    </div>

                    <!-- Driver License & Preferred Contact -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Driver License</label>
                            <input type="text" name="driver_license" class="form-control"
                                   value="<?= htmlspecialchars($old['driver_license'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Preferred Contact</label>
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
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="marketing_consent" value="1" id="marketing"
                            <?= isset($old['marketing_consent']) && $old['marketing_consent'] == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="marketing">Receive Promotions</label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary w-100">Register</button>

                </form>

                <!-- Terms -->
                <div class="text-center mt-3">
                    <small class="text-muted">By registering you agree to our <a href="#">Terms</a></small>
                </div>

            </div>

        </div>

    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Client-side password match (optional) -->
<script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const pwd = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        if (pwd !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
</script>

</body>
</html>