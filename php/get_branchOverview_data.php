<?php
include '../php/db_config.php'; // Change this to your real connection file path

header('Content-Type: application/json');

// 1. Fetch Customers
$customersResult = $conn->query("
    SELECT UserID, FullName, Username, Email, ContactNumber, Address, CreatedAt
    FROM user WHERE Role = 'Customer' ORDER BY CreatedAt DESC
");
$customers = [];
while ($row = $customersResult->fetch_assoc()) {
    $customers[] = $row;
}

// 2. Fetch Cashiers
$cashiersResult = $conn->query("
    SELECT u.UserID, u.FullName, u.Username, u.CreatedAt, b.BranchName, u.Address
    FROM user u LEFT JOIN branches b ON u.BranchID = b.BranchID WHERE u.Role = 'Cashier' ORDER BY b.BranchName, u.FullName
");
$cashiers = [];
while ($row = $cashiersResult->fetch_assoc()) {
    $cashiers[] = $row;
}

// 3. Fetch Branches
$branchesResult = $conn->query("
    SELECT b.BranchID, b.BranchName, b.Address, b.ContactNumber,
           COUNT(DISTINCT u.UserID) AS CashierCount
    FROM branches b
    LEFT JOIN `user` u ON b.BranchID = u.BranchID AND u.Role = 'Cashier'
    LEFT JOIN `order` o ON b.BranchID = o.BranchID
    GROUP BY b.BranchID
");
$branches = [];
while ($row = $branchesResult->fetch_assoc()) {
    $branches[] = $row;
}

$conn->close();

// Return clean JSON structure matching JavaScript variables
echo json_encode([
    "branches" => $branches,
    "cashiers" => $cashiers,
    "customers" => $customers
]);
exit;
