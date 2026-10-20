<?php

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once '../php/mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your Gmail address.'
    ]);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid Gmail address (e.g. name@gmail.com).'
    ]);
    exit();
}

// Check if email is already registered
$stmt = $pdo->prepare("SELECT UserID FROM user WHERE Email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'This Gmail account is already registered.'
    ]);
    exit();
}

// Generate OTP
$otp = str_pad(
    (string) random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);

// OTP expires in 10 minutes
$expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));

// Delete previous OTP
$stmt = $pdo->prepare("
    DELETE FROM otp_verifications
    WHERE Email = ?
");
$stmt->execute([$email]);

// Insert new OTP
$stmt = $pdo->prepare("
    INSERT INTO otp_verifications
    (Email, OtpCode, ExpiresAt, Used)
    VALUES (?, ?, ?, 0)
");

try {
    $stmt->execute([
        $email,
        $otp,
        $expiresAt
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create OTP record. Please try again.'
    ]);
    exit();
}

$_SESSION['otp_email'] = $email;

// Send OTP
$mailSent = sendOTPEmail($email, $otp, '');

// echo json_encode([
//     'php_time' => date('Y-m-d H:i:s'),
//     'timezone' => date_default_timezone_get(),
//     'unix_time' => time(),
//     'expires_at' => $expiresAt
// ]);
// exit();

if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent to your Gmail. Please check your inbox and spam folder.',
        'otp_sent' => true
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Email could not be sent through SMTP right now. Your OTP is shown below for testing.',
        'otp_sent' => false,
        'dev_otp' => $otp,
        'smtp_error' => $GLOBALS['mail_error'] ?? null
    ]);
}