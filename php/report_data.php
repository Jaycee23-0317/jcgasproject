<?php
// ../api/report_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../php/db_config.php'; // Verify active $conn reference variables path structures

// Authorization Gatekeeper Check: Only Admins allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized report access profiles.']);
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$startDateStr = $startDate . ' 00:00:00';
$endDateStr   = $endDate . ' 23:59:59';

$response = [
    'summary' => [
        'online_count' => 0,
        'online_revenue' => 0,
        'walkin_count' => 0,
        'walkin_revenue' => 0
    ],
    'chart' => ['dates' => [], 'revenues' => [], 'orderCounts' => []],
    'top_products' => [],
    'branch_performance' => []
];

// Query A: Summary Types Cards (Online vs Walk-in parameters bounded by range)
$summarySql = "SELECT 
                    OrderType, 
                    COUNT(DISTINCT OrderGroupID) as orders_count, 
                    SUM(TotalAmount) as revenue_total 
               FROM `order` 
               WHERE OrderDate BETWEEN ? AND ? AND Status = 'Completed'
               GROUP BY OrderType";
$summaryStmt = $conn->prepare($summarySql);
$summaryStmt->bind_param("ss", $startDateStr, $endDateStr);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result();

while ($row = $summaryResult->fetch_assoc()) {
    if (strtolower($row['OrderType']) === 'online') {
        $response['summary']['online_count'] = intval($row['orders_count']);
        $response['summary']['online_revenue'] = floatval($row['revenue_total']);
    } else {
        $response['summary']['walkin_count'] = intval($row['orders_count']);
        $response['summary']['walkin_revenue'] = floatval($row['revenue_total']);
    }
}
$summaryStmt->close();

// Query B: Chart Timeline Data metrics grouping chronologically by calendar date
$chartSql = "SELECT 
                DATE(OrderDate) as order_day, 
                COUNT(OrderGroupID) as daily_orders, 
                SUM(TotalAmount) as daily_revenue 
             FROM `order` 
             WHERE OrderDate BETWEEN ? AND ? AND Status = 'Completed'
             GROUP BY DATE(OrderDate) 
             ORDER BY DATE(OrderDate) ASC";
$chartStmt = $conn->prepare($chartSql);
$chartStmt->bind_param("ss", $startDateStr, $endDateStr);
$chartStmt->execute();
$chartResult = $chartStmt->get_result();

while ($row = $chartResult->fetch_assoc()) {
    $response['chart']['dates'][]       = date('M d', strtotime($row['order_day']));
    $response['chart']['revenues'][]    = floatval($row['daily_revenue']);
    $response['chart']['orderCounts'][] = intval($row['daily_orders']);
}
$chartStmt->close();

// Query C: Top Selling Products loop
$productSql = "SELECT 
                    p.ProductName, 
                    p.Size, 
                    SUM(oi.Quantity) as total_qty, 
                    SUM(oi.Subtotal) as product_revenue
               FROM order_items oi
               JOIN `order` o ON oi.OrderGroupID = o.OrderGroupID
               JOIN products p ON oi.ProductID = p.ProductID
               WHERE o.OrderDate BETWEEN ? AND ? AND o.Status = 'Completed'
               GROUP BY p.ProductID, p.ProductName, p.Size
               ORDER BY total_qty DESC LIMIT 10";
$productStmt = $conn->prepare($productSql);
$productStmt->bind_param("ss", $startDateStr, $endDateStr);
$productStmt->execute();
$productResult = $productStmt->get_result();

while ($row = $productResult->fetch_assoc()) {
    $response['top_products'][] = [
        'ProductName'  => $row['ProductName'],
        'Size'         => $row['Size'],
        'TotalSold'    => intval($row['total_qty']),
        'TotalRevenue' => floatval($row['product_revenue'])
    ];
}
$productStmt->close();

// Query D: Branch Performance loops matching parameters bounds
$branchSql = "SELECT 
                    b.BranchName, 
                    COUNT(DISTINCT o.OrderGroupID) AS OrdersInRange, 
                    IFNULL(SUM(o.TotalAmount), 0) AS branchRevenue
              FROM branches b
              LEFT JOIN `order` o ON b.BranchID = o.BranchID AND o.OrderDate BETWEEN ? AND ? AND o.Status = 'Completed'
              GROUP BY b.BranchID, b.BranchName
              ORDER BY branchRevenue DESC";
$branchStmt = $conn->prepare($branchSql);
$branchStmt->bind_param("ss", $startDateStr, $endDateStr);
$branchStmt->execute();
$branchResult = $branchStmt->get_result();

while ($row = $branchResult->fetch_assoc()) {
    $response['branch_performance'][] = [
        'BranchName'   => $row['BranchName'],
        'OrdersCount'  => intval($row['OrdersInRange']),
        'TotalRevenue' => floatval($row['branchRevenue'])
    ];
}
$branchStmt->close();
$conn->close();

echo json_encode($response);
exit();
