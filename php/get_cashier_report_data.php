<?php
session_start();
require_once '../php/db_config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Guard safety checkpoint for user tracking validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized user session context.']);
    exit();
}

$branchID = $_SESSION['branch_id'];

$startDateInput = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDateInput = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

$startDate = $startDateInput . ' 00:00:00';
$endDate = $endDateInput . ' 23:59:59';

try {
    $metricsSql = "SELECT 
        SUM(CASE WHEN PaymentMethod = 'CASH' THEN TotalAmount ELSE 0 END) as totalCashSales,
        SUM(CASE WHEN PaymentMethod != 'CASH' THEN TotalAmount ELSE 0 END) as totalOtherSales,
        COUNT(OrderGroupID) as totalOrders
    FROM `order`
    WHERE BranchID = ? 
      AND OrderDate BETWEEN ? AND ? 
      AND Status NOT IN ('Cancelled', 'Voided')"; // Adjust status names to match your exact enum definitions

    $stmt = $conn->prepare($metricsSql);
    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $metricsResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stockSql = "SELECT SUM(Stock) as totalStocks FROM `branch_inventory`
                 WHERE BranchID = ?";
    
    $stmt = $conn->prepare($stockSql);
    $stmt->bind_param("i", $branchID);
    $stmt->execute();
    $stockResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalCompletedOrdersSql = "SELECT
            COUNT(DISTINCT o.OrderGroupID) as totalCompletedOrders,
            SUM(oi.Quantity) as totalSoldItems
        FROM `order` o
        INNER JOIN `order_items` oi ON o.OrderGroupID = oi.OrderGroupID
        WHERE BranchID = ? 
        AND o.OrderDate BETWEEN ? AND ? 
        AND o.Status = 'Completed'
        GROUP BY o.Status
    ";

    $stmt = $conn->prepare($totalCompletedOrdersSql);
    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $totalCompletedOrdersResult = $stmt->get_result()->fetch_assoc();

    $totalPendingOrdersSql = "SELECT
            COUNT(OrderGroupID) as totalPendingOrders
        FROM `order`
        WHERE BranchID = ? 
        AND OrderDate BETWEEN ? AND ?
        AND Status NOT IN ('Completed', 'Cancelled', 'Voided')
    ";

    $stmt = $conn->prepare($totalPendingOrdersSql);
    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $totalPendingOrdersResult = $stmt->get_result()->fetch_assoc();

    $totalCancelledOrdersSql = "SELECT
            COUNT(OrderGroupID) as totalCancelledOrders
        FROM `order`
        WHERE BranchID = ? 
        AND OrderDate BETWEEN ? AND ?
        AND Status = 'Cancelled'
    ";

    $stmt = $conn->prepare($totalCancelledOrdersSql);
    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $totalCancelledOrdersResult = $stmt->get_result()->fetch_assoc();

    // Assemble metrics response structure
    $responseMetrics = [
        'totalCashSales'  => floatval($metricsResult['totalCashSales'] ?? 0),
        'totalOtherSales' => floatval($metricsResult['totalOtherSales'] ?? 0),
        'totalCompletedOrders' => intval($totalCompletedOrdersResult['totalCompletedOrders'] ?? 0),
        'totalCancelledOrders' => intval($totalCancelledOrdersResult['totalCancelledOrders'] ?? 0),
        'totalPendingOrders' => intval($totalPendingOrdersResult['totalPendingOrders'] ?? 0),
        'totalSoldItems' => intval($totalCompletedOrdersResult['totalSoldItems'] ?? 0),
        'totalOrders'     => intval($metricsResult['totalOrders'] ?? 0),
        'totalStocks'     => intval($stockResult['totalStocks'] ?? 0)
    ];

    // ==========================================================================
    // DATA COLLECTION PHASE B: GRAPH TIMELINE CHRONOLOGY ROW COMPILATION
    // ==========================================================================
    
    // Extracts group trends mapped cleanly by short formatted date labels (e.g., "Aug 23")
    $chartSql = "SELECT 
        DATE_FORMAT(OrderDate, '%b %d') as orderDay,
        DATE(OrderDate) as pureDate,
        SUM(TotalAmount) as dailyRevenue,
        COUNT(OrderGroupID) as dailyOrders
    FROM `order`
    WHERE BranchID = ? 
      AND OrderDate BETWEEN ? AND ?
      AND Status NOT IN ('Cancelled', 'Voided')
    GROUP BY DATE(OrderDate)
    ORDER BY DATE(OrderDate) ASC";

    $stmt = $conn->prepare($chartSql);
    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $chartDataRaw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Map database output keys using standard tracking dates for easier parsing
    $dbTimelineMap = [];
    foreach ($chartDataRaw as $row) {
        $dbTimelineMap[$row['pureDate']] = [
            'label'   => $row['orderDay'],
            'revenue' => floatval($row['dailyRevenue']),
            'orders'  => intval($row['dailyOrders'])
        ];
    }

    // ==========================================================================
    // DATA COLLECTION PHASE C: GENERATING EMPTY CALENDAR GAPS FOR CONTINUOUS CHARTS
    // ==========================================================================
    
    $chartDates = [];
    $chartRevenues = [];
    $chartOrderCounts = [];

    $currentDateTracker = new DateTime($startDateInput);
    $endDateTracker = new DateTime($endDateInput);
    $endDateTracker->modify('+1 day'); // Ensure boundary loop verification reaches the last target day safely

    $interval = new DateInterval('P1D');
    $dateRangeLoop = new DatePeriod($currentDateTracker, $interval, $endDateTracker);

    // Iterates cleanly through every sequential calendar date window range requested
    foreach ($dateRangeLoop as $dateObj) {
        $formattedKey = $dateObj->format('Y-m-d');
        $displayLabel = $dateObj->format('M d'); // Recreates match schema structural definitions like "Aug 23"

        $chartDates[] = $displayLabel;

        if (isset($dbTimelineMap[$formattedKey])) {
            // Day exists in database sales table pool records
            $chartRevenues[]    = $dbTimelineMap[$formattedKey]['revenue'];
            $chartOrderCounts[] = $dbTimelineMap[$formattedKey]['orders'];
        } else {
            // Blank calendar gap correction processing configuration path
            $chartRevenues[]    = 0;
            $chartOrderCounts[] = 0;
        }
    }

    $soldItemChartSql = " SELECT 
            DATE_FORMAT(o.OrderDate, '%b %d') AS orderDay,
            DATE(o.OrderDate) AS pureDate,
            SUM(oi.Subtotal) AS dailyRevenue,
            SUM(oi.Quantity) AS dailySoldItems
        FROM `order` o
        INNER JOIN order_items oi
            ON o.OrderGroupID = oi.OrderGroupID
        WHERE o.BranchID = ?
        AND o.OrderDate BETWEEN ? AND ?
        AND o.Status NOT IN ('Cancelled', 'Voided')
        GROUP BY DATE(o.OrderDate)
        ORDER BY DATE(o.OrderDate) ASC
    ";

    $stmt = $conn->prepare($soldItemChartSql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare sold item chart query: ' . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "iss",
        $branchID,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $soldItemChartDataRaw = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    $soldItemTimelineMap = [];

    foreach ($soldItemChartDataRaw as $row) {

        $soldItemTimelineMap[$row['pureDate']] = [
            'label'      => $row['orderDay'],
            'revenue'    => floatval($row['dailyRevenue']),
            'soldItems'  => intval($row['dailySoldItems'])
        ];
    }

    $soldItemChartDates = [];
    $soldItemChartRevenues = [];
    $soldItemChartSoldItems = [];

    $currentDateTracker = new DateTime($startDateInput);
    $endDateTracker = new DateTime($endDateInput);
    $endDateTracker->modify('+1 day');

    $interval = new DateInterval('P1D');

    $dateRangeLoop = new DatePeriod(
        $currentDateTracker,
        $interval,
        $endDateTracker
    );

    foreach ($dateRangeLoop as $dateObj) {

        $formattedKey = $dateObj->format('Y-m-d');
        $displayLabel = $dateObj->format('M d');

        $soldItemChartDates[] = $displayLabel;

        if (isset($soldItemTimelineMap[$formattedKey])) {

            $soldItemChartRevenues[] =
                $soldItemTimelineMap[$formattedKey]['revenue'];

            $soldItemChartSoldItems[] =
                $soldItemTimelineMap[$formattedKey]['soldItems'];

        } else {

            $soldItemChartRevenues[] = 0;
            $soldItemChartSoldItems[] = 0;
        }
    }

    $ordersStatusChartSql = " SELECT 
            Status,
            COUNT(OrderGroupID) AS TotalOrders
        FROM `order`
        WHERE BranchID = ?
        AND OrderDate BETWEEN ? AND ?
        GROUP BY Status
        ORDER BY TotalOrders DESC
    ";

    $stmt = $conn->prepare($ordersStatusChartSql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare orders status chart query: ' . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "iss",
        $branchID,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $ordersStatusChartDataRaw = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    $ordersStatusLabels = [];
    $ordersStatusCounts = [];

    foreach ($ordersStatusChartDataRaw as $row) {
        $ordersStatusLabels[] = $row['Status'];
        $ordersStatusCounts[] = intval($row['TotalOrders']);
    }

    $totalSalesChartSql = " SELECT 
            PaymentMethod,
            SUM(TotalAmount) AS totalSales
        FROM `order`
        WHERE BranchID = ?
        AND OrderDate BETWEEN ? AND ?
        GROUP BY PaymentMethod
        ORDER BY totalSales DESC
    ";

    $stmt = $conn->prepare($totalSalesChartSql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare total sales chart query: ' . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "iss",
        $branchID,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $totalSalesChartDataRaw = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    $totalSalesLabels = [];
    $totalSalesValues = [];

    foreach ($totalSalesChartDataRaw as $row) {
        $totalSalesLabels[] = $row['PaymentMethod'];
        $totalSalesValues[] = floatval($row['totalSales']);
    }

    $soldItemsPerNameSql = " SELECT
            p.ProductID,
            p.ProductName,
            o.Status,
            SUM(oi.Quantity) AS totalSoldItems
        FROM `order` o
        INNER JOIN order_items oi
            ON o.OrderGroupID = oi.OrderGroupID
        INNER JOIN products p
            ON oi.ProductID = p.ProductID
        WHERE o.BranchID = ?
        AND o.OrderDate BETWEEN ? AND ?
        AND o.Status NOT IN (
            'Cancelled',
            'Voided',
            'Pending Review'
        )
        GROUP BY
            p.ProductID,
            p.ProductName,
            o.Status
        ORDER BY
            p.ProductName ASC
    ";

    $stmt = $conn->prepare($soldItemsPerNameSql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare total sales chart query: ' . $conn->error
        ]);
        exit();
    }

    $stmt->bind_param(
        "iss",
        $branchID,
        $startDate,
        $endDate
    );

    $stmt->execute();

    $soldItemsPerNameRaw = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    $soldItemsPerNameProducts = [];
    $soldItemsPerNameStatuses = [];

    foreach ($soldItemsPerNameRaw as $row) {

        $productName = $row['ProductName'];
        $status = $row['Status'];
        $quantity = intval($row['totalSoldItems']);

        // Store unique products
        if (!in_array(
            $productName,
            $soldItemsPerNameProducts
        )) {
            $soldItemsPerNameProducts[] =
                $productName;
        }

        // Store unique statuses
        if (!in_array(
            $status,
            $soldItemsPerNameStatuses
        )) {
            $soldItemsPerNameStatuses[] =
                $status;
        }
    }

    $soldItemsPerNameDatasets = [];

    foreach ($soldItemsPerNameStatuses as $status) {

        $statusValues = [];

        foreach ($soldItemsPerNameProducts as $productName) {

            $quantity = 0;

            foreach ($soldItemsPerNameRaw as $row) {

                if (
                    $row['ProductName'] === $productName &&
                    $row['Status'] === $status
                ) {
                    $quantity =
                        intval($row['totalSoldItems']);

                    break;
                }
            }

            $statusValues[] = $quantity;
        }

        $soldItemsPerNameDatasets[] = [
            'status' => $status,
            'values' => $statusValues
        ];
    }

    // Output unified structural JSON payload array blocks
    echo json_encode([
        'metrics' => $responseMetrics,

        'chart' => [
            'dates'       => $chartDates,
            'revenues'    => $chartRevenues,
            'orderCounts' => $chartOrderCounts
        ],

        'soldItemChart' => [
            'dates'      => $soldItemChartDates,
            'revenues'   => $soldItemChartRevenues,
            'soldItems'  => $soldItemChartSoldItems
        ],

        'ordersStatusChart' => [
            'labels' => $ordersStatusLabels,
            'counts' => $ordersStatusCounts
        ],

        'totalSalesChart' => [
            'labels' => $totalSalesLabels,
            'sales'  => $totalSalesValues
        ],

        'soldItemsPerNameChart' => [
            'labels'   => $soldItemsPerNameProducts,
            'datasets' => $soldItemsPerNameDatasets
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal dashboard data extraction workflow system fault.',
        'details' => $e->getMessage()
    ]);
}
