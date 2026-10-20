<?php
session_start();
require_once 'db_config.php';

// Ensure data exists before processing
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT u.*, b.BranchName FROM user u LEFT JOIN branches b ON u.BranchID = b.BranchID WHERE u.Username = ? AND u.Password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    $_SESSION['branch_id'] = $user['BranchID'];
    $_SESSION['branch_name'] = $user['BranchName'];
    $_SESSION['full_name'] = $user['FullName'];

    // Define standard fallback path
    $redirectUrl = "../page/customerPage.php";

    if ($user['Role'] === 'Admin') {
        $redirectUrl = "../page/admin.php";
    } elseif ($user['Role'] === 'Cashier') {
        $redirectUrl = "../page/cashier.php";
    } elseif ($user['Role'] === 'Customer') {
        $redirectUrl = "../page/customer.php";
    } elseif (empty($user['FullName'])) {
        $redirectUrl = "../page/createProfile.php";
    }

    // Return success to JavaScript along with destination route
    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
} else {
    // Return error message directly back to the active page
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
}

$conn->close();
