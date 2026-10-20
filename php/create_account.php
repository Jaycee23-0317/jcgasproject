<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (empty($_SESSION['verified_email'])) {
    echo json_encode(['success' => false, 'message' => 'Please verify your Gmail address first.']);
    exit();
}

$email = $_SESSION['verified_email'];

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
    exit();
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters.']);
    exit();
}

// --- Duplicate username check -------------------------------------------
$checkUser = $conn->prepare("SELECT UserID FROM user WHERE Username = ?");
$checkUser->bind_param("s", $username);
$checkUser->execute();
$checkUser->store_result();
if ($checkUser->num_rows > 0) {
    $checkUser->close();
    echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose another.']);
    exit();
}
$checkUser->close();

// --- Create the Customer account ----------------------------------------
$role = 'Customer';
$branchId = null;
$fullName = ''; // Completed on the profile page

$insertSql = "INSERT INTO user (FullName, Username, Password, Role, BranchID, Email, ContactNumber, EmailVerified)
              VALUES (?, ?, ?, ?, ?, ?, NULL, 1)";
$insertStmt = $conn->prepare($insertSql);
$insertStmt->bind_param("ssssis", $fullName, $username, $password, $role, $branchId, $email);

if (!$insertStmt->execute()) {
    $insertStmt->close();
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    exit();
}

$userId = $conn->insert_id;
$insertStmt->close();

// Clean up the verified email marker and set login session
unset($_SESSION['verified_email']);
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;
$_SESSION['role']     = 'Customer';

echo json_encode([
    'success'  => true,
    'message'  => 'Account created! Complete your profile to start shopping.',
    'redirect' => false
]);

$conn->close();
