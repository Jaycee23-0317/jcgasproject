<?php
session_start();
require_once 'db_config.php';
require_once 'mail.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));

// --- Validation ---------------------------------------------------------
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your Gmail address.']);
    exit();
}

// Require a Gmail address as requested
if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid Gmail address (e.g. name@gmail.com).']);
    exit();
}

// --- Duplicate email check ---------------------------------------------
$checkEmail = $conn->prepare("SELECT UserID FROM user WHERE Email = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
$checkEmail->store_result();
if ($checkEmail->num_rows > 0) {
    $checkEmail->close();
    echo json_encode(['success' => false, 'message' => 'This Gmail account is already registered.']);
    exit();
}
$checkEmail->close();

// --- Generate OTP ------------------------------------------------------
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes

// Remove any previous pending OTP for this email (Username/Password are NULL at this stage)
$cleanup = $conn->prepare("DELETE FROM otp_verifications WHERE Email = ?");
$cleanup->bind_param("s", $email);
$cleanup->execute();
$cleanup->close();

// Store OTP with NULL username/password (Gmail verified first, credentials come later)
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
$mailSent = sendOTPEmail($email, $otp, '');

if ($mailSent) {
    echo json_encode([
        'success'  => true,
        'message'  => 'OTP sent to your Gmail. Please check your inbox (and spam folder).',
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
