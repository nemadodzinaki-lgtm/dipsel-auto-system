<?php
session_start();
require_once __DIR__ . '/../../config/database.php'; // adjust path to your DB config

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../forgot_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['fp_error'] = 'Please enter a valid email address.';
    header('Location: ../../forgot_password.php');
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT person_id FROM persons WHERE email = :email AND status = 'Active'");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // For security, we still show "If the email exists, a link has been sent"
        $_SESSION['fp_success'] = 'If the email exists, a password reset link has been sent.';
        header('Location: ../../forgot_password.php');
        exit;
    }

    // Generate a secure token (64 bytes hex)
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token and expiry in the persons table
    $update = $pdo->prepare("UPDATE persons SET reset_token = :token, reset_token_expiry = :expiry WHERE person_id = :id");
    $update->execute([
        'token'  => $token,
        'expiry' => $expiry,
        'id'     => $user['person_id']
    ]);

    // Build reset link
    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;

    // Send email (replace with your actual mailer)
    $subject = "Password Reset Request";
    $message = "Hello,\n\nYou requested to reset your password. Click the link below to set a new password:\n\n$resetLink\n\nThis link expires in 1 hour.\n\nIf you did not request this, please ignore this email.";
    $headers = "From: no-reply@dipselauto.com\r\n";
    $mailSent = mail($email, $subject, $message, $headers);

    // Always show success (even if mail fails, we log error)
    if (!$mailSent) {
        error_log("Password reset email failed for $email");
    }

    $_SESSION['fp_success'] = 'If the email exists, a password reset link has been sent.';
    header('Location: ../../forgot_password.php');
    exit;

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    $_SESSION['fp_error'] = 'An error occurred. Please try again later.';
    header('Location: ../../forgot_password.php');
    exit;
}