<?php
require_once '../../config/database.php';

$data = $_POST;
$job_card_id = (int) ($data['job_card_id'] ?? 0);
$booking_id = (int) ($data['booking_id'] ?? 0);

try {
    if ($job_card_id > 0) {
        // UPDATE
        $sql = "UPDATE job_cards SET
            job_card_number = :job_card_number,
            vehicle_id = :vehicle_id,
            customer_id = :customer_id,
            mechanic_employee_id = :mechanic_employee_id,
            service_advisor_employee_id = :service_advisor_employee_id,
            odometer_reading = :odometer_reading,
            fuel_level = :fuel_level,
            vehicle_condition = :vehicle_condition,
            customer_complaint = :customer_complaint,
            diagnosis = :diagnosis,
            work_performed = :work_performed,
            labour_hours = :labour_hours,
            parts_cost = :parts_cost,
            labour_cost = :labour_cost,
            total_cost = :total_cost,
            priority = :priority,
            status = :status,
            opened_at = :opened_at,
            completed_at = :completed_at,
            customer_signature = :customer_signature,
            advisor_notes = :advisor_notes,
            mechanic_notes = :mechanic_notes
        WHERE job_card_id = :job_card_id";
    } else {
        // INSERT
        $sql = "INSERT INTO job_cards (
            job_card_number, booking_id, vehicle_id, customer_id,
            mechanic_employee_id, service_advisor_employee_id,
            odometer_reading, fuel_level, vehicle_condition,
            customer_complaint, diagnosis, work_performed,
            labour_hours, parts_cost, labour_cost, total_cost,
            priority, status, opened_at, completed_at,
            customer_signature, advisor_notes, mechanic_notes
        ) VALUES (
            :job_card_number, :booking_id, :vehicle_id, :customer_id,
            :mechanic_employee_id, :service_advisor_employee_id,
            :odometer_reading, :fuel_level, :vehicle_condition,
            :customer_complaint, :diagnosis, :work_performed,
            :labour_hours, :parts_cost, :labour_cost, :total_cost,
            :priority, :status, :opened_at, :completed_at,
            :customer_signature, :advisor_notes, :mechanic_notes
        )";
    }

    $stmt = $pdo->prepare($sql);
    // Convert empty strings to null for numeric fields
    foreach (['vehicle_id', 'customer_id', 'mechanic_employee_id', 'service_advisor_employee_id', 'odometer_reading'] as $field) {
        if (isset($data[$field]) && $data[$field] === '') $data[$field] = null;
    }
    // Add booking_id only if insert
    if ($job_card_id == 0) {
        $data['booking_id'] = $booking_id;
    }
    // For update, we need job_card_id in params
    $data['job_card_id'] = $job_card_id;

    $stmt->execute($data);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}