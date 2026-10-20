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

// 2. Read and fallback parameter processing for date ranges
$startDateInput = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDateInput = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

// Standardize time formatting boundaries to secure full timestamp coverages
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

    // Query tracking for Total Available Stocks allocated across this specific physical branch location
    // Note: Adjust table/column structural references if your stock levels reside in branch_inventory
    $stockSql = "SELECT SUM(Stock) as totalStocks FROM `branch_inventory`
                 WHERE BranchID = ?";
    // Ideal long term recommendation table alternative link tracking layout example:
    // $stockSql = "SELECT SUM(StockLevel) as totalStocks FROM branch_inventory WHERE BranchID = ?";
    
    $stmt = $conn->prepare($stockSql);
    $stmt->bind_param("i", $branchID);
    $stmt->execute();
    $stockResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Assemble metrics response structure
    $responseMetrics = [
        'totalCashSales'  => floatval($metricsResult['totalCashSales'] ?? 0),
        'totalOtherSales' => floatval($metricsResult['totalOtherSales'] ?? 0),
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

    // Output unified structural JSON payload array blocks
    echo json_encode([
        'metrics' => $responseMetrics,
        'chart' => [
            'dates'       => $chartDates,
            'revenues'    => $chartRevenues,
            'orderCounts' => $chartOrderCounts
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal dashboard data extraction workflow system fault.',
        'details' => $e->getMessage()
    ]);
}
