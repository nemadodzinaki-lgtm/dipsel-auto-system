<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../reset_password.php?error=invalid_request');
    exit;
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';

if (empty($token)) {
    $_SESSION['reset_error'] = 'Missing reset token.';
    header('Location: ../../reset_password.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['reset_error'] = 'Password must be at least 8 characters.';
    header('Location: ../../reset_password.php?token=' . urlencode($token));
    exit;
}

if ($password !== $confirm) {
    $_SESSION['reset_error'] = 'Passwords do not match.';
    header('Location: ../../reset_password.php?token=' . urlencode($token));
    exit;
}

try {
    // Validate token again
    $stmt = $pdo->prepare("SELECT person_id FROM persons WHERE reset_token = :token AND reset_token_expiry > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['reset_error'] = 'Invalid or expired token.';
        header('Location: ../../reset_password.php');
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear reset token fields
    $update = $pdo->prepare("
        UPDATE persons 
        SET password = :password, reset_token = NULL, reset_token_expiry = NULL 
        WHERE person_id = :id
    ");
    $update->execute([
        'password' => $hashedPassword,
        'id'       => $user['person_id']
    ]);

    $_SESSION['reset_success'] = 'Your password has been reset successfully. You can now log in.';
    header('Location: ../../reset_password.php?token=' . urlencode($token)); // token still in URL, but we'll clear it on success
    exit;

} catch (Exception $e) {
    error_log('Password reset error: ' . $e->getMessage());
    $_SESSION['reset_error'] = 'An error occurred. Please try again.';
    header('Location: ../../reset_password.php?token=' . urlencode($token));
    exit;
}