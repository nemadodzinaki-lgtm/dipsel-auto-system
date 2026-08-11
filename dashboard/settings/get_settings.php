<?php
/**
 * Get Settings API – returns all or a single setting as JSON.
 */
require_once '../../includes/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (isset($_GET['key']) && $_GET['key'] !== '') {
        $key = trim($_GET['key']);
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['setting_key' => $key, 'setting_value' => $row['setting_value']]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Setting not found']);
        }
        exit;
    }

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode($settings);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}