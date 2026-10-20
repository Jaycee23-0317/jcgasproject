<?php
// ../api/sales_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once '../php/db_config.php'; // Adjust path variables to match your system config

// 1. Capture dynamic range parameters, safely parsing null references
$filterBranch = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$filterType   = isset($_GET['order_type']) ? trim($_GET['order_type']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$startDate    = $_GET['start_date'] ?? date('Y-m-d');
$endDate      = $_GET['end_date'] ?? date('Y-m-d');

// Construct full timestamp boundaries to capture complete operational records
$startDateStr = $startDate . ' 00:00:00';
$endDateStr   = $endDate . ' 23:59:59';

$where = [];
$groupParams = [];
$groupTypes = "";

$where[] = "o.OrderDate BETWEEN ? AND ?";

$groupParams[] = $startDateStr;
$groupParams[] = $endDateStr;

$groupTypes .= "ss";

if ($filterBranch > 0) {

    $where[] = "o.BranchID = ?";

    $groupParams[] = $filterBranch;

    $groupTypes .= "i";
}

if ($filterType !== '') {

    $where[] = "o.OrderType = ?";

    $groupParams[] = $filterType;

    $groupTypes .= "s";
}

if ($filterStatus !== '') {

    $where[] = "o.Status = ?";

    $groupParams[] = $filterStatus;

    $groupTypes .= "s";
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

// 2. CONSTRUCT DYNAMIC GROUPED ORDERS QUERY STATEMENT MATRIX
$groupSql = "
    SELECT
        o.*,
        oi.ItemID, oi.ProductID, oi.Quantity, oi.UnitPrice, oi.Subtotal,
        b.BranchName, b.Address AS BranchAddress, b.Email, b.ContactNumber,
        p.ProductName, p.Size, p.ImageName AS ProductImage
    FROM `order` o
    INNER JOIN order_items oi ON o.OrderGroupID = oi.OrderGroupID
    INNER JOIN products p ON oi.ProductID = p.ProductID
    INNER JOIN branches b ON o.BranchID = b.BranchID
    INNER JOIN `user` u ON o.UserID = u.UserID

    $whereSql

    ORDER BY
        o.OrderDate DESC
";

$groupStmt = $conn->prepare($groupSql);
if (!$groupStmt) {
    echo json_encode(['success' => false, 'error' => 'API Query structural trace failure: ' . $conn->error]);
    exit();
}

$groupStmt->bind_param($groupTypes, ...$groupParams);
$groupStmt->execute();
$groupResult = $groupStmt->get_result();

$ordersList = [];
if ($groupResult) {
    $flatRows = $groupResult->fetch_all(MYSQLI_ASSOC);
    $orders = [];
    foreach ($flatRows as $row) {
        $orderGroupId = $row['OrderGroupID'];
        if (!isset($orders[$orderGroupId])) {
            $orders[$orderGroupId] = [
                'OrderGroupID'    => $row['OrderGroupID'],
                'OrderID'         => $row['OrderID'],
                'TransactionID'   => $row['TransactionID'],
                'UserID'          => $row['UserID'],
                'BranchID'        => $row['BranchID'],
                'BranchName'      => $row['BranchName'],
                'BranchAddress'   => $row['BranchAddress'],
                'BranchEmail'     => $row['Email'],
                'BranchContactNumber'     => $row['ContactNumber'],
                'OrderType'       => $row['OrderType'],
                'PaymentMethod'   => $row['PaymentMethod'],
                'Status'          => $row['Status'],
                'CustomerName'    => $row['CustomerName'],
                'TotalAmount'     => $row['TotalAmount'],
                'OrderDate'       => $row['OrderDate'],
                'DeliveryAddress'       => $row['DeliveryAddress'],
                'DeliveryLatitude'       => $row['DeliveryLatitude'],
                'DeliveryLongitude'       => $row['DeliveryLongitude'],
                'items'           => []
            ];
        }
        $orders[$orderGroupId]['items'][] = [
            'ItemID'       => $row['ItemID'],
            'ProductID'    => $row['ProductID'],
            'ProductName'  => $row['ProductName'],
            'Size'         => $row['Size'],
            'OrderedPrice' => $row['UnitPrice'],
            'Quantity'     => $row['Quantity'],
            'Subtotal'     => $row['Subtotal']
        ];
    }
    $ordersList = array_values($orders);
}
$groupStmt->close();

// 3. CONSTRUCT REVENUE COUNTERS QUERY BOUNDED WITHIN SPECIFIC FILTER RULES
$revenueSql = "
    SELECT COUNT(DISTINCT o.OrderGroupID) as total_orders, SUM(oi.Subtotal) as total_revenue 
    FROM `order` o 
    JOIN order_items oi ON o.OrderGroupID = oi.OrderGroupID 
    WHERE o.Status = 'Completed' AND o.OrderDate BETWEEN ? AND ?
";

$revenueParams = [$startDateStr, $endDateStr];
$revenueTypes = "ss";

if ($filterBranch > 0) {
    $revenueSql .= " AND o.BranchID = ?";
    $revenueParams[] = $filterBranch;
    $revenueTypes .= "i";
}
if ($filterType !== '') {
    $revenueSql .= " AND o.OrderType = ?";
    $revenueParams[] = $filterType;
    $revenueTypes .= "s";
}

$revenueStmt = $conn->prepare($revenueSql);
$revenueStmt->bind_param($revenueTypes, ...$revenueParams);
$revenueStmt->execute();
$revenueRow = $revenueStmt->get_result()->fetch_assoc();
$revenueStmt->close();

$totalOrders  = intval($revenueRow['total_orders'] ?? 0);
$totalRevenue = floatval($revenueRow['total_revenue'] ?? 0);

$conn->close();

// 4. RETURN SYNCHRONIZED COMPOSITE PAYLOAD BACK TO JAVASCRIPT FETCH THREAD
echo json_encode([
    'totalOrders'  => $totalOrders,
    'totalRevenue' => $totalRevenue,
    'orders'       => $ordersList
]);
exit();
