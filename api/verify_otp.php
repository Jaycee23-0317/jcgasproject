<?php

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$otp = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and OTP are required.'
    ]);
    exit();
}

// Make sure the email matches the email that requested the OTP
// if (
//     empty($_SESSION['otp_email']) ||
//     $_SESSION['otp_email'] !== $email
// ) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'OTP verification session expired. Please request a new OTP.'
//     ]);
//     exit();
// }

// Get latest OTP
$stmt = $pdo->prepare("
    SELECT OtpID, OtpCode, ExpiresAt, Used
    FROM otp_verifications
    WHERE Email = ?
    ORDER BY OtpID DESC
    LIMIT 1
");

$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        'success' => false,
        'message' => 'No OTP found for this email. Please request a new code.'
    ]);
    exit();
}

if ((int) $row['Used'] === 1) {
    echo json_encode([
        'success' => false,
        'message' => 'This OTP has already been used. Please request a new code.'
    ]);
    exit();
}

if (strtotime($row['ExpiresAt']) < time()) {
    echo json_encode([
        'success' => false,
        'message' => 'This OTP has expired. Please request a new code.'
    ]);
    exit();
}

if (!hash_equals((string) $row['OtpCode'], $otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Incorrect OTP code. Please try again.'
    ]);
    exit();
}

// Mark OTP as used
$stmt = $pdo->prepare("
    UPDATE otp_verifications
    SET Used = 1
    WHERE OtpID = ?
");

$stmt->execute([$row['OtpID']]);

// Store verified email
$_SESSION['verified_email'] = $email;

unset($_SESSION['otp_email']);

echo json_encode([
    'success' => true,
    'message' => 'Gmail verified successfully! Now create your username and password.',
    'redirect' => false
]);