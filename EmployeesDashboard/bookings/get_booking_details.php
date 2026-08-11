<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'Employee') {
    http_response_code(403);
    exit('Unauthorized');
}

$bookingId = (int) ($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    echo json_encode(['error' => 'Invalid booking ID']);
    exit;
}

// Fetch booking details – no join to vehicles, use columns from b
$stmt = $pdo->prepare("
    SELECT
        b.*,
        ws.workshop_name,
        ws.phone AS workshop_phone,
        ws.email AS workshop_email,
        ws.address AS workshop_address,
        ws.city AS workshop_city,
        ws.province AS workshop_province,
        s.service_name,
        s.category AS service_category,
        s.description AS service_description,
        s.base_price AS service_base_price,
        -- vehicle columns from b
        b.vehicle_name,
        b.vehicle_model,
        b.registration_number,
        -- customer
        p.first_name AS customer_first_name,
        p.middle_name AS customer_middle_name,
        p.last_name AS customer_last_name,
        p.phone AS customer_phone,
        p.email AS customer_email,
        p.address AS customer_address,
        p.city AS customer_city,
        p.province AS customer_province,
        p.postal_code AS customer_postal_code,
        c.customer_number,
        c.loyalty_points,
        c.driver_license
    FROM service_bookings b
    JOIN workshops ws ON b.workshop_id = ws.workshop_id
    JOIN workshop_services s ON b.service_id = s.service_id
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN persons p ON c.person_id = p.person_id
    WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// Verify assignment
$personId = (int) $_SESSION['person_id'];

$empStmt = $pdo->prepare(
    "SELECT employee_id
     FROM employees
     WHERE person_id = ?"
);

$empStmt->execute([$personId]);

$emp = $empStmt->fetch(PDO::FETCH_ASSOC);

if (!$emp || $booking['assigned_employee_id'] != $emp['employee_id']) {
    echo json_encode(['error' => 'You are not authorized to view this booking']);
    exit;
}

// Get job card if exists
$jcStmt = $pdo->prepare("
    SELECT jc.*,
           me.first_name AS mechanic_first_name, me.last_name AS mechanic_last_name,
           ae.first_name AS advisor_first_name, ae.last_name AS advisor_last_name
    FROM job_cards jc
    LEFT JOIN employees me_emp ON jc.mechanic_employee_id = me_emp.employee_id
    LEFT JOIN persons me ON me_emp.person_id = me.person_id
    LEFT JOIN employees ae_emp ON jc.service_advisor_employee_id = ae_emp.employee_id
    LEFT JOIN persons ae ON ae_emp.person_id = ae.person_id
    WHERE jc.booking_id = ?
");
$jcStmt->execute([$bookingId]);
$jobCard = $jcStmt->fetch();

$updates = [];
if ($jobCard) {
    $updStmt = $pdo->prepare("
        SELECT su.*,
               p.first_name, p.last_name
        FROM service_updates su
        JOIN employees e ON su.updated_by_employee_id = e.employee_id
        JOIN persons p ON e.person_id = p.person_id
        WHERE su.job_card_id = ?
        ORDER BY su.created_at DESC
    ");
    $updStmt->execute([$jobCard['job_card_id']]);
    $updates = $updStmt->fetchAll();
}

$response = array_merge($booking, [
    'job_card' => $jobCard,
    'updates' => $updates
]);

header('Content-Type: application/json');
echo json_encode($response);