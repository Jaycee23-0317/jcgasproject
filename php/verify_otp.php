<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$otp   = trim($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
    exit();
}

// Fetch the latest unused OTP for this email
$sql = "SELECT OtpID, OtpCode, ExpiresAt, Used FROM otp_verifications WHERE Email = ? ORDER BY OtpID DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No OTP found for this email. Please request a new code.']);
    exit();
}

if ((int)$row['Used'] === 1) {
    echo json_encode(['success' => false, 'message' => 'This OTP has already been used. Please request a new code.']);
    exit();
}

if (strtotime($row['ExpiresAt']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This OTP has expired. Please request a new code.']);
    exit();
}

if (!hash_equals($row['OtpCode'], $otp)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect OTP code. Please try again.']);
    exit();
}

// Mark OTP as used
$markUsed = $conn->prepare("UPDATE otp_verifications SET Used = 1 WHERE OtpID = ?");
$markUsed->bind_param("i", $row['OtpID']);
$markUsed->execute();
$markUsed->close();

// Store verified email in session - user is NOT created yet
$_SESSION['verified_email'] = $email;
unset($_SESSION['otp_email']);

echo json_encode([
    'success'  => true,
    'message'  => 'Gmail verified successfully! Now create your username and password.',
    'redirect' => false // Stay on the registration page for Step 2
]);

$conn->close();
