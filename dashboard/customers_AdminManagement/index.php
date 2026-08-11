<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function generateCustomerNumber($pdo) {
    $prefix = 'CUST-' . date('Y') . '-';
    $sql = "SELECT MAX(CAST(SUBSTRING(customer_number, LENGTH(:prefix) + 1) AS UNSIGNED)) AS last_num 
            FROM customers WHERE customer_number LIKE :like_prefix";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix, ':like_prefix' => $prefix . '%']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row['last_num'] ?? 0) + 1;
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// --- ADD CUSTOMER ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect person fields
    $first_name    = trim($_POST['first_name'] ?? '');
    $middle_name   = trim($_POST['middle_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $gender        = trim($_POST['gender'] ?? 'Male');
    $dob           = trim($_POST['date_of_birth'] ?? '');
    $id_number     = trim($_POST['id_number'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $country       = trim($_POST['country'] ?? 'South Africa');
    $province      = trim($_POST['province'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $postal_code   = trim($_POST['postal_code'] ?? '');
    $role          = 'Customer';
    $status        = trim($_POST['status'] ?? 'Active');

    // Customer fields
    $customer_number   = trim($_POST['customer_number'] ?? '');
    $driver_license    = trim($_POST['driver_license'] ?? '');
    $preferred_contact = trim($_POST['preferred_contact'] ?? 'Phone');
    $marketing_consent = isset($_POST['marketing_consent']) ? 1 : 0;

    // Validate
    $errors = [];
    if (empty($first_name))  $errors[] = 'First name is required.';
    if (empty($last_name))   $errors[] = 'Last name is required.';
    if (empty($phone))       $errors[] = 'Phone is required.';
    if (empty($email))       $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($password))    $errors[] = 'Password is required.';

    // Check email uniqueness
    $check = $pdo->prepare("SELECT person_id FROM persons WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) $errors[] = 'Email already registered.';

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sqlPerson = "INSERT INTO persons (
            first_name, middle_name, last_name, gender, date_of_birth,
            id_number, phone, email, password, country, province, city,
            address, postal_code, role, status, created_at
        ) VALUES (
            :first, :middle, :last, :gender, :dob,
            :id_num, :phone, :email, :pass, :country, :province, :city,
            :address, :postal, :role, :status, NOW()
        )";
        $stmt = $pdo->prepare($sqlPerson);
        $stmt->execute([
            ':first'   => $first_name,
            ':middle'  => $middle_name,
            ':last'    => $last_name,
            ':gender'  => $gender,
            ':dob'     => $dob ?: null,
            ':id_num'  => $id_number,
            ':phone'   => $phone,
            ':email'   => $email,
            ':pass'    => $hashed_password,
            ':country' => $country,
            ':province'=> $province,
            ':city'    => $city,
            ':address' => $address,
            ':postal'  => $postal_code,
            ':role'    => $role,
            ':status'  => $status
        ]);
        $person_id = $pdo->lastInsertId();

        if (empty($customer_number)) $customer_number = generateCustomerNumber($pdo);

        $sqlCust = "INSERT INTO customers (
            person_id, customer_number, driver_license, preferred_contact,
            marketing_consent, loyalty_points, created_at
        ) VALUES (
            :pid, :cnum, :lic, :pref,
            :mark, :points, NOW()
        )";
        $stmt = $pdo->prepare($sqlCust);
        $stmt->execute([
            ':pid'   => $person_id,
            ':cnum'  => $customer_number,
            ':lic'   => $driver_license,
            ':pref'  => $preferred_contact,
            ':mark'  => $marketing_consent,
            ':points'=> 0
        ]);

        $_SESSION['success'] = "Customer added successfully. #: $customer_number";
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

// --- EDIT CUSTOMER ---
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int) ($_POST['customer_id'] ?? 0);
    if (!$customer_id) {
        $_SESSION['error'] = 'Invalid customer ID.';
        header('Location: index.php');
        exit;
    }

    $cust = $pdo->prepare("SELECT person_id FROM customers WHERE customer_id = ?");
    $cust->execute([$customer_id]);
    $custData = $cust->fetch();
    if (!$custData) {
        $_SESSION['error'] = 'Customer not found.';
        header('Location: index.php');
        exit;
    }
    $person_id = $custData['person_id'];

    // Collect person fields
    $first_name    = trim($_POST['first_name'] ?? '');
    $middle_name   = trim($_POST['middle_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $gender        = trim($_POST['gender'] ?? 'Male');
    $dob           = trim($_POST['date_of_birth'] ?? '');
    $id_number     = trim($_POST['id_number'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $country       = trim($_POST['country'] ?? 'South Africa');
    $province      = trim($_POST['province'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $postal_code   = trim($_POST['postal_code'] ?? '');
    $status        = trim($_POST['status'] ?? 'Active');

    // Customer fields
    $customer_number   = trim($_POST['customer_number'] ?? '');
    $driver_license    = trim($_POST['driver_license'] ?? '');
    $preferred_contact = trim($_POST['preferred_contact'] ?? 'Phone');
    $marketing_consent = isset($_POST['marketing_consent']) ? 1 : 0;

    // Validate
    $errors = [];
    if (empty($first_name))  $errors[] = 'First name is required.';
    if (empty($last_name))   $errors[] = 'Last name is required.';
    if (empty($phone))       $errors[] = 'Phone is required.';
    if (empty($email))       $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    $check = $pdo->prepare("SELECT person_id FROM persons WHERE email = ? AND person_id != ?");
    $check->execute([$email, $person_id]);
    if ($check->rowCount() > 0) $errors[] = 'Email already used by another person.';

    if (empty($errors)) {
        $sqlPerson = "UPDATE persons SET
            first_name = :first,
            middle_name = :middle,
            last_name = :last,
            gender = :gender,
            date_of_birth = :dob,
            id_number = :id_num,
            phone = :phone,
            email = :email,
            country = :country,
            province = :province,
            city = :city,
            address = :address,
            postal_code = :postal,
            status = :status,
            updated_at = NOW()";
        $params = [
            ':first'   => $first_name,
            ':middle'  => $middle_name,
            ':last'    => $last_name,
            ':gender'  => $gender,
            ':dob'     => $dob ?: null,
            ':id_num'  => $id_number,
            ':phone'   => $phone,
            ':email'   => $email,
            ':country' => $country,
            ':province'=> $province,
            ':city'    => $city,
            ':address' => $address,
            ':postal'  => $postal_code,
            ':status'  => $status,
            ':pid'     => $person_id
        ];
        if (!empty($password)) {
            $sqlPerson = str_replace('updated_at = NOW()', 'password = :pass, updated_at = NOW()', $sqlPerson);
            $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sqlPerson .= " WHERE person_id = :pid";
        $stmt = $pdo->prepare($sqlPerson);
        $stmt->execute($params);

        $sqlCust = "UPDATE customers SET
            customer_number = :cnum,
            driver_license = :lic,
            preferred_contact = :pref,
            marketing_consent = :mark,
            updated_at = NOW()
        WHERE customer_id = :cid";
        $stmt = $pdo->prepare($sqlCust);
        $stmt->execute([
            ':cnum' => $customer_number,
            ':lic'  => $driver_license,
            ':pref' => $preferred_contact,
            ':mark' => $marketing_consent,
            ':cid'  => $customer_id
        ]);

        $_SESSION['success'] = "Customer #$customer_id updated.";
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    header('Location: index.php');
    exit;
}

// --- DELETE CUSTOMER ---
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $cust = $pdo->prepare("SELECT person_id FROM customers WHERE customer_id = ?");
    $cust->execute([$id]);
    $custData = $cust->fetch();
    if ($custData) {
        $person_id = $custData['person_id'];
        $delCust = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
        $delCust->execute([$id]);
        $delPerson = $pdo->prepare("DELETE FROM persons WHERE person_id = ?");
        $delPerson->execute([$person_id]);
        $_SESSION['success'] = "Customer #$id deleted.";
    } else {
        $_SESSION['error'] = 'Customer not found.';
    }
    header('Location: index.php');
    exit;
}

// --- DELETE FEEDBACK ---
if ($action === 'delete_feedback' && isset($_GET['id'])) {
    $feedback_id = (int) $_GET['id'];
    $del = $pdo->prepare("DELETE FROM customer_feedback WHERE feedback_id = ?");
    $del->execute([$feedback_id]);
    $_SESSION['success'] = "Feedback deleted.";
    header('Location: index.php');
    exit;
}

// -------- FETCH DATA FOR LISTING --------
$total = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM customers c JOIN persons p ON c.person_id = p.person_id WHERE p.status = 'Active'")->fetchColumn();

$sql = "SELECT c.*, p.first_name, p.last_name, p.email, p.phone, p.status, p.id_number
        FROM customers c
        JOIN persons p ON c.person_id = p.person_id
        ORDER BY c.customer_id DESC";
$customers = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        /* ── Global ── */
        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fc;
        }

        /* ── Dashboard Wrapper (same as other pages) ── */
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

        /* ── Table & Badges ── */
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .action-btn {
            margin: 0 2px;
        }

        /* ── Modals ── */
        .modal-lg {
            max-width: 800px;
        }
        .form-label.required::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        .modal-footer {
            flex-shrink: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }

        /* ── Responsive fine‑tune (same as dashboard) ── */
        @media (max-width: 992px) {
            .dashboard-wrapper {
                margin-left: 0;
            }
            /* Page header: stack */
            .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem;
            }
            .page-header .btn {
                width: 100%;
            }
            /* Stats cards */
            .stat-card .card-body {
                padding: 1rem 1.2rem;
            }
            .stat-card h2 {
                font-size: 1.8rem;
            }
            /* Container */
            .container-fluid {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
            /* Modals */
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
            /* Table font */
            .table td, .table th {
                font-size: 0.85rem;
                padding: 0.5rem 0.3rem;
            }
            .badge-status {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            .action-btn {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.3rem;
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
            .table td, .table th {
                font-size: 0.75rem;
                padding: 0.3rem 0.2rem;
            }
            .badge-status {
                font-size: 0.65rem;
                padding: 0.2rem 0.5rem;
            }
            .action-btn {
                padding: 0.15rem 0.4rem;
                font-size: 0.65rem;
            }
            .modal-body .row > .col-md-6 {
                margin-bottom: 0.5rem;
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
                <h2><i class="fas fa-users text-primary me-2"></i>Customer Management</h2>
                <p class="text-muted">Manage all customers and their feedback.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="openAddModal()">
                <i class="fas fa-plus me-1"></i> Add Customer
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['error']); endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Total Customers</h6>
                            <h2 class="fw-bold"><?= $total ?></h2>
                        </div>
                        <div class="stat-icon text-primary"><i class="fas fa-user-friends"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card h-100 shadow-sm" style="border-left-color:#198754;">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted">Active Customers</h6>
                            <h2 class="fw-bold text-success"><?= $active ?></h2>
                        </div>
                        <div class="stat-icon text-success"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="customersTable" class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Customer #</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $counter=1; while($row=$customers->fetch()):
                            $statusClass = ($row['status'] == 'Active') ? 'success' : (($row['status'] == 'Inactive') ? 'warning' : 'danger');
                        ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><strong><?= htmlspecialchars($row['first_name']) ?> <?= htmlspecialchars($row['last_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['id_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['customer_number']) ?></span></td>
                                <td><span class="badge bg-<?= $statusClass ?> badge-status"><?= $row['status'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info action-btn" onclick="viewCustomer(<?= $row['customer_id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-warning action-btn" onclick="editCustomer(<?= $row['customer_id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <a href="?action=delete&id=<?= $row['customer_id'] ?>" class="btn btn-sm btn-outline-danger action-btn" onclick="return confirm('Delete this customer?')" title="Delete"><i class="fas fa-trash"></i></a>
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

<!-- ========== MODAL (Add / Edit) ========== -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="customerForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="customer_id" id="customerId" value="0">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left column: Personal Info -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Personal Details</h6>
                            <div class="mb-3">
                                <label class="form-label required">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" id="id_number" class="form-control" placeholder="e.g. 8001015009087">
                            </div>
                        </div>
                        <!-- Right column: Contact & Account -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Contact & Account</h6>
                            <div class="mb-3">
                                <label class="form-label required">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters">
                                <small class="text-muted">Leave blank to keep current (edit).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" id="country" class="form-control" value="South Africa">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" id="province" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" id="city" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="address" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                            <hr>
                            <h6 class="border-bottom pb-2">Customer Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Customer Number</label>
                                <input type="text" name="customer_number" id="customer_number" class="form-control" placeholder="Leave blank to auto-generate">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Driver License</label>
                                <input type="text" name="driver_license" id="driver_license" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preferred Contact</label>
                                <select name="preferred_contact" id="preferred_contact" class="form-select">
                                    <option value="Phone">Phone</option>
                                    <option value="Email">Email</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                </select>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="marketing_consent" id="marketing_consent" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="marketing_consent">Marketing Consent</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== VIEW MODAL (includes feedback) ========== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- filled by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#customersTable').DataTable({
        pageLength: 10,
        lengthMenu: [[5,10,25,50,-1],[5,10,25,50,"All"]],
        order: [[0,'desc']],
        columnDefs: [{ orderable: false, targets: [7] }],
        language: { search: "Filter:", searchPlaceholder: "Search customers..." }
    });
});

function openAddModal() {
    $('#modalTitle').text('Add Customer');
    $('#formAction').val('add');
    $('#customerId').val(0);
    $('#customerForm')[0].reset();
    $('#password').prop('required', true);
    $('#saveBtn').text('Save Customer');
    $('#gender').val('Male');
    $('#country').val('South Africa');
    $('#status').val('Active');
    $('#preferred_contact').val('Phone');
    $('#marketing_consent').prop('checked', true);
    $('#customerModal').modal('show');
}

function editCustomer(id) {
    $.ajax({
        url: 'get_customer.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            $('#modalTitle').text('Edit Customer');
            $('#formAction').val('edit');
            $('#customerId').val(data.customer_id);
            // Person fields
            $('#first_name').val(data.first_name);
            $('#middle_name').val(data.middle_name);
            $('#last_name').val(data.last_name);
            $('#gender').val(data.gender);
            $('#date_of_birth').val(data.date_of_birth);
            $('#id_number').val(data.id_number);
            $('#phone').val(data.phone);
            $('#email').val(data.email);
            $('#country').val(data.country);
            $('#province').val(data.province);
            $('#city').val(data.city);
            $('#address').val(data.address);
            $('#postal_code').val(data.postal_code);
            $('#status').val(data.status);
            // Customer fields
            $('#customer_number').val(data.customer_number);
            $('#driver_license').val(data.driver_license);
            $('#preferred_contact').val(data.preferred_contact);
            $('#marketing_consent').prop('checked', data.marketing_consent == 1);
            $('#password').val('').prop('required', false);
            $('#saveBtn').text('Update Customer');
            $('#customerModal').modal('show');
        },
        error: function() { alert('Error loading customer data.'); }
    });
}

function viewCustomer(id) {
    $.ajax({
        url: 'get_customer.php?id=' + id,
        dataType: 'json',
        success: function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-4">
                        <h5 class="border-bottom pb-2">Personal Information</h5>
                        <p><strong>Name:</strong> ${data.first_name} ${data.last_name}</p>
                        <p><strong>ID Number:</strong> ${data.id_number || 'N/A'}</p>
                        <p><strong>Gender:</strong> ${data.gender}</p>
                        <p><strong>Date of Birth:</strong> ${data.date_of_birth || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${data.status=='Active'?'success':data.status=='Inactive'?'warning':'danger'}">${data.status}</span></p>
                    </div>
                    <div class="col-md-4">
                        <h5 class="border-bottom pb-2">Contact Details</h5>
                        <p><strong>Email:</strong> ${data.email}</p>
                        <p><strong>Phone:</strong> ${data.phone}</p>
                        <p><strong>Address:</strong> ${data.address || 'N/A'}</p>
                        <p><strong>City:</strong> ${data.city || 'N/A'}</p>
                        <p><strong>Province:</strong> ${data.province || 'N/A'}</p>
                        <p><strong>Country:</strong> ${data.country || 'N/A'}</p>
                    </div>
                    <div class="col-md-4">
                        <h5 class="border-bottom pb-2">Account Details</h5>
                        <p><strong>Customer #:</strong> <span class="badge bg-secondary">${data.customer_number}</span></p>
                        <p><strong>Driver License:</strong> ${data.driver_license || 'N/A'}</p>
                        <p><strong>Preferred Contact:</strong> ${data.preferred_contact}</p>
                        <p><strong>Loyalty Points:</strong> ${data.loyalty_points || 0}</p>
                        <p><strong>Marketing Consent:</strong> ${data.marketing_consent ? 'Yes' : 'No'}</p>
                    </div>
                </div>
                <hr>
                <h6>Feedback</h6>
                <div id="feedbackList">
                    <!-- feedback will be loaded via AJAX below -->
                </div>
            `;
            $('#viewModalBody').html(html);
            // Load feedback for this customer
            $.ajax({
                url: 'get_customer_feedback.php?customer_id=' + id,
                dataType: 'json',
                success: function(feedbacks) {
                    let listHtml = '<ul class="list-group">';
                    if (feedbacks.length === 0) {
                        listHtml += '<li class="list-group-item">No feedback found.</li>';
                    } else {
                        feedbacks.forEach(function(fb) {
                            listHtml += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${fb.feedback_type}</strong> - Rating: ${fb.rating}/5
                                        <br><small>${fb.feedback}</small>
                                        <br><small class="text-muted">${fb.created_at}</small>
                                    </div>
                                    <a href="?action=delete_feedback&id=${fb.feedback_id}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this feedback?')"><i class="fas fa-trash"></i></a>
                                </li>
                            `;
                        });
                    }
                    listHtml += '</ul>';
                    $('#feedbackList').html(listHtml);
                }
            });
            $('#viewModal').modal('show');
        },
        error: function() { alert('Error loading customer data.'); }
    });
}
</script>

</body>
</html>