<?php

session_start();
require_once 'db_config.php';
require_once 'mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a customer.']);
    exit();
}

$userId = $_SESSION['user_id'];

$userSql = "SELECT FullName, Email FROM user WHERE UserID = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User record not found.']);
    exit();
}

$email = strtolower(trim($user['Email'] ?? ''));

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'No Gmail account is currently linked to your profile.']);
    exit();
}

// Require a Gmail address as requested
if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    echo json_encode(['success' => false, 'message' => 'Your current linked account is not a valid Gmail address.']);
    exit();
}

// --- Generate OTP ------------------------------------------------------
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes

// Remove any previous pending OTP for this email
$cleanup = $conn->prepare("DELETE FROM otp_verifications WHERE Email = ?");
$cleanup->bind_param("s", $email);
$cleanup->execute();
$cleanup->close();

// Store OTP
$insertOtp = $conn->prepare("INSERT INTO otp_verifications (Email, OtpCode, ExpiresAt) VALUES (?, ?, ?)");
$insertOtp->bind_param("sss", $email, $otp, $expiresAt);
if (!$insertOtp->execute()) {
    $insertOtp->close();
    echo json_encode(['success' => false, 'message' => 'Failed to create OTP record. Please try again.']);
    exit();
}
$insertOtp->close();

// Store the email being verified in session
$_SESSION['otp_email'] = $email;

// --- Send via PHPMailer (Gmail SMTP) -----------------------------------
$mailSent = sendOTPEmail($email, $otp, $user['FullName'] ?? '');

if ($mailSent) {
    echo json_encode([
        'success'  => true,
        'message'  => 'OTP sent to your current Gmail. Please check your inbox (and spam folder).',
        'otp_sent' => true
    ]);
} else {
    // SMTP failed - show the OTP on screen so local testing can continue.
    echo json_encode([
        'success'  => true,
        'message'  => 'Email could not be sent through SMTP right now. Your OTP is shown below for testing.',
        'otp_sent' => false,
        'dev_otp'  => $otp,
        'smtp_error' => $GLOBALS['mail_error'] ?? null
    ]);
}

$conn->close();
