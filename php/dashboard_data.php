<?php
// ../api/dashboard_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../php/db_config.php'; // Adjust path to your file structure

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'Unauthorized session profile configuration.']);
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$startDateStr = $startDate . ' 00:00:00';
$endDateStr   = $endDate . ' 23:59:59';

$response = [
    'totalStock' => 0,
    'totalSold' => 0,
    'totalRevenue' => 0,
    'totalOrders' => 0,
    'branches' => []
];

$tanksResult = $conn->query("SELECT SUM(Stock) as total FROM branch_inventory");
if ($tanksResult && $row = $tanksResult->fetch_assoc()) {
    $response['totalStock'] = intval($row['total'] ?? 0);
}

$salesSql = "SELECT SUM(oi.Quantity) as sold, SUM(o.TotalAmount) as revenue 
             FROM `order` o 
             JOIN order_items oi ON o.OrderGroupID = oi.OrderGroupID 
             WHERE o.OrderDate BETWEEN ? AND ? AND o.Status = 'Completed'";
$salesStmt = $conn->prepare($salesSql);
$salesStmt->bind_param("ss", $startDateStr, $endDateStr);
$salesStmt->execute();
$salesRow = $salesStmt->get_result()->fetch_assoc();
$salesStmt->close();

$response['totalSold']    = intval($salesRow['sold'] ?? 0);
$response['totalRevenue'] = floatval($salesRow['revenue'] ?? 0);

$ordersSql = "SELECT COUNT(*) as total FROM `order` WHERE OrderDate BETWEEN ? AND ? AND Status = 'Completed'";
$ordersStmt = $conn->prepare($ordersSql);
$ordersStmt->bind_param("ss", $startDateStr, $endDateStr);
$ordersStmt->execute();
$ordersRow = $ordersStmt->get_result()->fetch_assoc();
$ordersStmt->close();

$response['totalOrders'] = intval($ordersRow['total'] ?? 0);

$branchSql = "SELECT b.BranchName, COUNT(o.OrderGroupID) AS OrdersInRange, IFNULL(SUM(o.TotalAmount), 0) AS branchRevenue
              FROM branches b
              LEFT JOIN `order` o ON b.BranchID = o.BranchID AND o.OrderDate BETWEEN ? AND ? AND o.Status = 'Completed'
              GROUP BY b.BranchID, b.BranchName";
$branchStmt = $conn->prepare($branchSql);
$branchStmt->bind_param("ss", $startDateStr, $endDateStr);
$branchStmt->execute();
$branchResult = $branchStmt->get_result();

while ($branchRow = $branchResult->fetch_assoc()) {
    $response['branches'][] = [
        'BranchName'   => $branchRow['BranchName'],
        'OrdersCount'  => intval($branchRow['OrdersInRange']),
        'TotalRevenue' => floatval($branchRow['branchRevenue'])
    ];
}
$branchStmt->close();
$conn->close();

echo json_encode($response);
exit();
