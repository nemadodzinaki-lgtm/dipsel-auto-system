<?php
/**
 * create_appointment.php
 * Handles appointment booking from the vehicle showroom.
 * Expects POST with:
 *   - customer_id (int, must match logged-in user)
 *   - vehicle_id (int)
 *   - appointment_type (string, e.g. 'Vehicle Viewing', 'Test Drive', 'Workshop')
 *   - appointment_date (date, YYYY-MM-DD)
 *   - appointment_time (time, HH:MM:SS or HH:MM)
 *   - employee_id (optional, int)
 *   - notes (optional, string)
 * Returns JSON with success flag and appointment_id or error message.
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Ensure user is logged in and has a valid customer record
if (empty($currentUser['person_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit;
}

// Get the logged-in customer ID from the database to verify ownership
$stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE person_id = ?");
$stmt->execute([$currentUser['person_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have a customer profile.']);
    exit;
}
$loggedCustomerId = (int) $customer['customer_id'];

// Get POST data
$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
$vehicle_id = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
$appointment_type = trim($_POST['appointment_type'] ?? '');
$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';
$employee_id = isset($_POST['employee_id']) && $_POST['employee_id'] !== '' ? (int) $_POST['employee_id'] : null;
$notes = trim($_POST['notes'] ?? '');

// --- Validation ---

// 1. Check that the customer_id matches the logged-in user's customer ID
if ($customer_id !== $loggedCustomerId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Customer ID mismatch.']);
    exit;
}

// 2. Required fields
if (empty($vehicle_id) || empty($appointment_type) || empty($appointment_date) || empty($appointment_time)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: vehicle_id, appointment_type, appointment_date, appointment_time.']);
    exit;
}

// 3. Validate date and time format
$dateObj = DateTime::createFromFormat('Y-m-d', $appointment_date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $appointment_date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}
// Allow time in H:i or H:i:s
$timeObj = DateTime::createFromFormat('H:i', $appointment_time);
if (!$timeObj) {
    $timeObj = DateTime::createFromFormat('H:i:s', $appointment_time);
}
if (!$timeObj) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid time format. Use HH:MM or HH:MM:SS.']);
    exit;
}
$appointment_time_formatted = $timeObj->format('H:i:s'); // ensure standard format

// 4. Verify that the vehicle exists and is available (optional but recommended)
$stmt = $pdo->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ? AND available_for_sale = 'Yes' AND status = 'Available'");
$stmt->execute([$vehicle_id]);
if (!$stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Selected vehicle is not available for booking.']);
    exit;
}

// 5. If employee_id provided, check that employee exists and is active
if ($employee_id !== null) {
    $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE employee_id = ? AND status = 'Active'");
    $stmt->execute([$employee_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Selected employee is not available.']);
        exit;
    }
}

// --- Insert appointment ---
try {
    $sql = "INSERT INTO appointments 
                (customer_id, vehicle_id, employee_id, appointment_type, appointment_date, appointment_time, status, notes)
            VALUES 
                (:customer_id, :vehicle_id, :employee_id, :appointment_type, :appointment_date, :appointment_time, 'Pending', :notes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':customer_id' => $customer_id,
        ':vehicle_id' => $vehicle_id,
        ':employee_id' => $employee_id,
        ':appointment_type' => $appointment_type,
        ':appointment_date' => $appointment_date,
        ':appointment_time' => $appointment_time_formatted,
        ':notes' => $notes
    ]);

    $appointmentId = (int) $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'appointment_id' => $appointmentId,
        'message' => 'Appointment booked successfully.'
    ]);

} catch (PDOException $e) {
    // Log error (optional)
    error_log('Appointment booking error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}