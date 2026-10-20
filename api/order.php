<?php
session_start();
include_once '../php/db_config.php';
require_once '../api/activity_logs.php';
include_once '../php/mail.php';

if (basename($_SERVER['SCRIPT_FILENAME']) === 'order.php') {
    handleOrderRequest($conn);
}

function handleOrderRequest($conn) {

    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $startDateInput = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
        $endDateInput = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d');

        $startDate = $startDateInput . ' 00:00:00';
        $endDate = $endDateInput . ' 23:59:59';

        if (isset($_GET['branchID'])) {
            $branchID = (int)$_GET['branchID'];
            $data = getAllOrdersByBranch($conn, $branchID, $startDate, $endDate);
            echo json_encode($data);
        } else if (isset($_GET['userID'])) {
            $userID = (int)$_GET['userID'];
            $data = getAllOrdersByUser($conn, $userID, $startDate, $endDate);
            echo json_encode($data);
        } else if (isset($_GET['requestingAllPendingReviewStatusByBranchID'])) {
            $branchID = $_GET['requestingAllPendingReviewStatusByBranchID'] ?? null;
            $data = getAllOrdersWithPendingReviewStatus($conn, $branchID);
            echo json_encode($data);
        }


    } else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (isset($input['status'])) {
            $orderGroupID = isset($input['orderGroupID']) ? (int)$input['orderGroupID'] : 0;
            $userID = isset($input['userID']) ? (int)$input['userID'] : '';
            $newStatus    = isset($input['status']) ? trim($input['status']) : '';
            $oldStatus    = isset($input['oldStatus']) ? trim($input['oldStatus']) : '';
            $cancelReason    = isset($input['cancelReason']) ? trim($input['cancelReason']) : '';

            $result = updateOrderStatus($conn, $orderGroupID, $userID, $newStatus, $oldStatus, $cancelReason);
            echo json_encode($result);
        } else {
            placeOrder($conn);
        }


    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing data']);
        exit;
    }
       
}

function getAllOrdersByBranch($conn, $branchID, $startDate, $endDate) {
    $sql = "SELECT 
                o.*,
                b.*,
                od.*, 
                p.*
            FROM `order` o
            INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
            INNER JOIN `products` p ON od.ProductID = p.ProductID
            INNER JOIN `branches` b ON o.BranchID = b.BranchID
            WHERE o.BranchID = ? AND o.OrderDate BETWEEN ? AND ?
            ORDER BY o.OrderGroupID DESC, od.ItemID DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

    $stmt->bind_param("iss", $branchID, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $flatRows = $result->fetch_all(MYSQLI_ASSOC);
        $orders = arrangeOrderDetails($flatRows);
        $stmt->close();
        return array_values($orders);
    }

    $stmt->close();
    return ['error' => 'Query failed: ' . $conn->error];
}

function getAllOrdersByUser($conn, $userID, $startDate, $endDate) {
    $sql = "SELECT 
                o.*,
                b.*,
                od.*, 
                p.*
            FROM `order` o
            INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
            INNER JOIN `products` p ON od.ProductID = p.ProductID
            INNER JOIN `branches` b ON o.BranchID = b.BranchID
            WHERE o.userID = ? AND o.OrderDate BETWEEN ? AND ?
            ORDER BY o.OrderGroupID DESC, od.ItemID DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

    $stmt->bind_param("iss", $userID, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $flatRows = $result->fetch_all(MYSQLI_ASSOC);
        $orders = arrangeOrderDetails($flatRows);
        
        $stmt->close();
        return array_values($orders);
    }

    $stmt->close();
    return ['error' => 'Query failed: ' . $conn->error];
}

function arrangeOrderDetails($data) {

    $orders = [];
    foreach ($data as $row) {
        $orderGroupId = $row['OrderGroupID'];
        if (!isset($orders[$orderGroupId])) {
            $orders[$orderGroupId] = [
                'OrderGroupID'      => $row['OrderGroupID'],
                'OrderID'           => $row['OrderID'],
                'TransactionID'     => $row['TransactionID'],
                'BranchID'          => $row['BranchID'],
                'BranchName'        => $row['BranchName'],
                'BranchAddress'     => $row['Address'],
                'BranchEmail'       => $row['Email'],
                'BranchContactNumber'     => $row['ContactNumber'],
                'OrderType'         => $row['OrderType'],
                'PaymentMethod'     => $row['PaymentMethod'],
                'Status'            => $row['Status'],
                'CustomerName'      => $row['CustomerName'],
                'CancelReason'      => $row['CancelReason'],
                'TotalAmount'       => $row['TotalAmount'],
                'OrderDate'         => $row['OrderDate'],
                'DeliveryAddress'   => $row['DeliveryAddress'],
                'DeliveryLatitude'  => $row['DeliveryLatitude'],
                'DeliveryLongitude' => $row['DeliveryLongitude'],
                'items'             => []
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

    return $orders;
}

function placeOrder($conn) {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'] ?? 'Customer';

    $branchId = null;
    $orderType = 'Walk-in';

    $totalAmountString = $_POST['total_amount'] ?? '0.00';
    $deliveryAddress = $_POST['delivery_address'] ?? null;
    $deliveryLatitude = $_POST['delivery_latitude'] ?? null;
    $deliveryLongitude = $_POST['delivery_longitude'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? null;

    $totalAmount = floatval(preg_replace('/[^\d.]/', '', $totalAmountString));

    if (strtolower($userRole) === 'cashier') {
        $customerName = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : 'Walk-In Customer';
        $orderStatus = 'Completed';
        $branchId = $_SESSION['branch_id'] ?? null;
    } else {
        $customerName = $_SESSION['full_name'] ?? 'Registered Customer';
        $orderStatus = 'Pending Review';
        $branchId = $_POST['branch_id'] ?? null;
        $orderType = 'Online';
    }

    $orderItemsJson = $_POST['OrderItems'] ?? '{}';
    $cartItems = json_decode($orderItemsJson, true);

    if (empty($branchId) || empty($cartItems) || $totalAmount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing critical execution parameter records. Check your item cart selection details.']);
        exit;
    }

    $dateToday = date('Y-m-d');
    $countSql = "SELECT MAX(OrderID) AS last_num 
                FROM `order` 
                WHERE BranchID = ? AND DATE(OrderDate) = ?";

    $stmt = $conn->prepare($countSql);
    $stmt->bind_param("is", $branchId, $dateToday);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $orderId = ($row['last_num'] ?? 0) + 1;

    $isInserted = false;
    $attempts = 0;

    while (!$isInserted && $attempts < 5) {
        $transactionId = 'JCGAS-' . strtoupper(bin2hex(random_bytes(4)));

        $conn->begin_transaction();

        try {

            $headerSql = "INSERT INTO `order` (
                OrderID, TransactionID, UserID, BranchID, OrderType, Status, PaymentMethod, CustomerName, DeliveryAddress, DeliveryLatitude, DeliveryLongitude, TotalAmount, OrderDate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $headerStmt = $conn->prepare($headerSql);
            if (!$headerStmt) {
                throw new Exception("Header mapping preparation failure: " . $conn->error);
            }

            $headerStmt->bind_param(
                "isiisssssssd",
                $orderId,
                $transactionId,
                $userId,
                $branchId,
                $orderType,
                $orderStatus,
                $paymentMethod,
                $customerName,
                $deliveryAddress,
                $deliveryLatitude,
                $deliveryLongitude,
                $totalAmount
            );

            if (!$headerStmt->execute()) {
                throw new Exception("Header row entry execution failure: " . $headerStmt->error);
            }

            $orderGroupId = $conn->insert_id;
            $headerStmt->close();

            $itemSql = "INSERT INTO order_items (OrderGroupID, ProductID, Quantity, UnitPrice, Subtotal) VALUES (?, ?, ?, ?, ?)";
            $itemStmt = $conn->prepare($itemSql);
            if (!$itemStmt) {
                throw new Exception("Itemized execution maps preparation failure: " . $conn->error);
            }

            foreach ($cartItems as $productId => $item) {
                $productId = intval($productId);
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $subtotal = $qty * $price;

                $itemStmt->bind_param("iiidd", $orderGroupId, $productId, $qty, $price, $subtotal);
                if (!$itemStmt->execute()) {
                    throw new Exception("Item sequence row injection insertion failure: " . $itemStmt->error);
                }
            }

            if ($userRole === 'Cashier') {
                $updateSql = "UPDATE branch_inventory SET Stock = Stock - ? WHERE BranchID = ? AND ProductID = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($cartItems as $productId => $item) {
                    $productId = intval($productId);
                    $branchId  = intval($branchId);
                    $quantity  = intval($item['qty'] ?? 0);
                    if ($quantity <= 0) continue;

                    $updateStmt->bind_param("iii", $quantity, $branchId, $productId);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Stock adjustment execution failure: " . $updateStmt->error);
                    }
                }
                $updateStmt->close();
            }
            $itemStmt->close();

            $logNewData = [
                'TransactionID' => $transactionId,
                'OrderID' => $orderGroupId,
                'CustomerName' => $customerName,
                'OrderType' => $orderType,
                'Status' => $orderStatus,
                'PaymentMethod' => $paymentMethod,
                'TotalAmount' => $totalAmount,
                'DeliveryAddress' => $deliveryAddress,
                'Items' => $cartItems
            ];

            createActivityLog(
                $conn,
                'CREATE',
                'Order',
                $orderGroupId,
                $userId,
                $branchId,
                "Created order {$transactionId}",
                null,
                $logNewData
            );

            $conn->commit();

            $isInserted = true;

            $infoSql = "SELECT o.CustomerName, u.Email, o.BranchID, o.UserID, o.TransactionID 
                    FROM `order` o 
                    INNER JOIN `user` u ON o.UserID = u.UserID 
                    WHERE o.OrderGroupID = ? 
                    LIMIT 1";

            $infoStmt = $conn->prepare($infoSql);
            $infoStmt->bind_param("i", $orderGroupId);
            $infoStmt->execute();
            $result = $infoStmt->get_result();
            $customer = $result->fetch_assoc();
            $infoStmt->close();

            if ($customer && !empty($customer['Email'])) {
                sendOrderStatusEmail($customer['Email'], $customer['CustomerName'], $orderGroupId, $customer['TransactionID'], $orderStatus);
            }

            $redirectUrl = (strtolower($userRole) === 'cashier') ? 'cashier_dashboard.php' : 'customer_orders.php';

            echo json_encode([
                'success' => true,
                'message' => 'Order with transaction ID: ' . $transactionId . ' has been successfully added!',
                'redirect' => $redirectUrl
            ]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();

            if ($e->getCode() == 1062) {
                $attempts++;
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'System processing error: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }

    if (!$isInserted) {
        echo json_encode([
            'success' => false,
            'message' => 'System transaction engine busy. Please attempt checkout processing again.'
        ]);
        exit;
    }
}

function updateOrderStatus($conn, $orderGroupID, $userID, $newStatus, $oldStatus, $cancelReason) {
    if ($newStatus === 'Order Placed') {

        $sql = "SELECT 
                    o.OrderGroupID, o.BranchID,
                    od.ItemID, od.ProductID, od.Quantity, od.UnitPrice
                FROM `order` o
                INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
                WHERE o.OrderGroupID = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) return ['success' => false, 'error' => 'Prepare failed: ' . $conn->error];

        $stmt->bind_param("i", $orderGroupID);
        $stmt->execute();
        $orderItemsResult = $stmt->get_result(); // FIXED: Renamed to preserve dataset stream pointer references
        $stmt->close();

        if ($orderItemsResult && $orderItemsResult->num_rows > 0) {

            $itemsArray = $orderItemsResult->fetch_all(MYSQLI_ASSOC);

            $allAvailable = true;
            $insufficientItems = [];

            foreach ($itemsArray as $orderItem) {
                $productId = intval($orderItem['ProductID']);
                $branchId  = intval($orderItem['BranchID']);
                $quantity  = intval($orderItem['Quantity'] ?? 0);
                if ($quantity <= 0) continue;

                $checkSql = "SELECT Stock FROM branch_inventory WHERE BranchID = ? AND ProductID = ? AND Stock >= ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("iii", $branchId, $productId, $quantity);
                $checkStmt->execute();
                $inventoryMatch = $checkStmt->get_result()->fetch_assoc(); // FIXED: Saved to distinct variable pointer
                $checkStmt->close();

                if (!$inventoryMatch) {
                    $allAvailable = false;
                    $insufficientItems[] = "Product ID #{$productId} (Qty Required: {$quantity})";
                }
            }

            if (!$allAvailable) {
                return [
                    'success' => false,
                    'error' => 'Sufficient stock is unavailable for: ' . implode(", ", $insufficientItems)
                ];
            }

            $conn->begin_transaction(); // Start transaction to guarantee database integrity

            try {
                $updateSql = "UPDATE branch_inventory SET Stock = Stock - ? WHERE BranchID = ? AND ProductID = ?";
                $updateStmt = $conn->prepare($updateSql);

                foreach ($itemsArray as $orderItem) {
                    $productId = intval($orderItem['ProductID']);
                    $branchId  = intval($orderItem['BranchID']);
                    $quantity  = intval($orderItem['Quantity'] ?? 0);
                    if ($quantity <= 0) continue;

                    $updateStmt->bind_param("iii", $quantity, $branchId, $productId);
                    if (!$updateStmt->execute()) {
                        throw new Exception("Stock adjustment execution failure: " . $updateStmt->error);
                    }
                }
                $updateStmt->close();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback(); // Cancel adjustments if an unexpected update error happens
                return ['success' => false, 'error' => 'Inventory update transaction failed: ' . $e->getMessage()];
            }
        } else {
            return ['success' => false, 'error' => 'No item records found associated with this OrderGroupID number.'];
        }
    }

    $sql = null;
    $stmt = null;

    if ($newStatus === 'Cancelled' && $cancelReason !== "") {
        $sql = "UPDATE `order` SET `Status` = ?, `CancelReason` = ?, `CancelledBy` = ? WHERE `OrderGroupID` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $newStatus, $cancelReason, $userID, $orderGroupID);
    } else {
        $sql = "UPDATE `order` SET `Status` = ? WHERE `OrderGroupID` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newStatus, $orderGroupID);
    }

    if (!$stmt) return ['success' => false, 'error' => $conn->error];

    $actionUserID = $userID ?? $_SESSION['user_id'];

    if ($stmt->execute()) {
        $stmt->close();

        $infoSql = "SELECT o.CustomerName, u.Email, o.BranchID, o.UserID, o.TransactionID 
                    FROM `order` o 
                    INNER JOIN `user` u ON o.UserID = u.UserID 
                    WHERE o.OrderGroupID = ? 
                    LIMIT 1";

        $infoStmt = $conn->prepare($infoSql);
        if ($infoStmt) {
            $infoStmt->bind_param("i", $orderGroupID);
            $infoStmt->execute();
            $result = $infoStmt->get_result();
            $customer = $result->fetch_assoc();
            $infoStmt->close();

            $logOldData = [
                'Status' => $oldStatus,
            ];

            $logNewData = [
                'Status' => $newStatus,
            ];

            $logAction = 'UPDATE';
            $logDescription = "Updated order status for {$customer['TransactionID']}";

            if ($newStatus === 'Cancelled' && $cancelReason !== "") {
                $logNewData['CancelReason'] = $cancelReason;

                $logAction = 'CANCEL';
                $logDescription = "Cancelled order {$customer['TransactionID']}";
            }

            createActivityLog(
                $conn,
                $logAction,
                'Order',
                $orderGroupID,
                $actionUserID,
                $customer['BranchID'],
                $logDescription,
                $logOldData,
                $logNewData
            );

            if ($customer && !empty($customer['Email'])) {
                sendOrderStatusEmail($customer['Email'], $customer['CustomerName'], $orderGroupID, $customer['TransactionID'], $newStatus);
            }
        }

        
        return ['success' => true, 'message' => "Order status successfully updated to '{$newStatus}'."];
    }

    $error = $stmt->error;
    $stmt->close();
    return ['success' => false, 'error' => $error];
}

function getAllOrdersWithPendingReviewStatus($conn, $branchID) {
    $sql = "SELECT
            o.OrderGroupID, o.OrderID, o.TransactionID, o.OrderDate, o.OrderType, o.CustomerName, o.DeliveryAddress, o.Status,
            COUNT(oi.ItemID) as ItemCount, 
            o.TotalAmount
        FROM `order` o
        JOIN order_items oi ON o.OrderGroupID = oi.OrderGroupID
        JOIN products p ON oi.ProductID = p.ProductID
        LEFT JOIN `user` u ON o.UserID = u.UserID
        WHERE o.BranchID = ? AND o.Status = 'Pending Review'
        GROUP BY o.OrderGroupID, o.OrderID, o.TransactionID, o.OrderDate, o.OrderType, o.CustomerName, o.DeliveryAddress, o.Status, o.TotalAmount
        ORDER BY o.OrderDate DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return json_encode(['error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $branchID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $orders;
    } else {
        $stmt->close();
        return json_encode(['error' => 'Query execution failed: ' . $conn->error]);
    }
}

// function getAllOrders($conn, $branchID)
// {
//     $sql = "SELECT 
//                 o.*,
//                 od.ItemID, od.ProductID, od.Quantity, od.UnitPrice, od.Subtotal,
//                 b.BranchName, b.Address, b.Email, b.ContactNumber,
//                 p.ProductName, p.Size, p.Image AS ProductImage
//             FROM `order` o
//             INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
//             INNER JOIN `products` p ON od.ProductID = p.ProductID
//             INNER JOIN `branches` b ON o.BranchID = b.BranchID
//             WHERE o.BranchID = ?
//             ORDER BY o.OrderGroupID DESC, od.ItemID ASC";

//     $stmt = $conn->prepare($sql);
//     if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

//     $stmt->bind_param("i", $branchID);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result) {
//         $flatRows = $result->fetch_all(MYSQLI_ASSOC);
//         $orders = [];
//         foreach ($flatRows as $row) {
//             $orderGroupId = $row['OrderGroupID'];
//             if (!isset($orders[$orderGroupId])) {
//                 $orders[$orderGroupId] = [
//                     'OrderGroupID'    => $row['OrderGroupID'],
//                     'OrderID'         => $row['OrderID'],
//                     'TransactionID'   => $row['TransactionID'],
//                     'UserID'          => $row['UserID'],
//                     'BranchID'        => $row['BranchID'],
//                     'BranchName'      => $row['BranchName'],
//                     'BranchAddress'   => $row['Address'],
//                     'BranchEmail'     => $row['Email'],
//                     'BranchContactNumber'     => $row['ContactNumber'],
//                     'OrderType'       => $row['OrderType'],
//                     'PaymentMethod'   => $row['PaymentMethod'],
//                     'Status'          => $row['Status'],
//                     'CustomerName'    => $row['CustomerName'],
//                     'TotalAmount'     => $row['TotalAmount'],
//                     'OrderDate'       => $row['OrderDate'],
//                     'DeliveryAddress'       => $row['DeliveryAddress'],
//                     'DeliveryLatitude'       => $row['DeliveryLatitude'],
//                     'DeliveryLongitude'       => $row['DeliveryLongitude'],
//                     'items'           => []
//                 ];
//             }
//             $orders[$orderGroupId]['items'][] = [
//                 'ItemID'       => $row['ItemID'],
//                 'ProductName'  => $row['ProductName'],
//                 'Size'         => $row['Size'],
//                 'OrderedPrice' => $row['UnitPrice'],
//                 'Quantity'     => $row['Quantity'],
//                 'Subtotal'     => $row['Subtotal']
//             ];
//         }
//         return array_values($orders);
//     }
//     return ['error' => 'Query failed: ' . $conn->error];
// }

// function getAllOrdersByUser($conn, $userID)
// {
//     $sql = "SELECT 
//                 o.*,
//                 od.ItemID, od.ProductID, od.Quantity, od.UnitPrice, od.Subtotal,
//                 b.BranchName, b.Address, b.Email, b.ContactNumber,
//                 p.ProductName, p.ProductID, p.Size, p.Image AS ProductImage
//             FROM `order` o
//             INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
//             INNER JOIN `products` p ON od.ProductID = p.ProductID
//             INNER JOIN `branches` b ON o.BranchID = b.BranchID
//             WHERE o.UserID = ?
//             ORDER BY o.OrderGroupID DESC, od.ItemID ASC";

//     $stmt = $conn->prepare($sql);
//     if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

//     $stmt->bind_param("i", $userID);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result) {
//         $flatRows = $result->fetch_all(MYSQLI_ASSOC);
//         $orders = [];
//         foreach ($flatRows as $row) {
//             $orderGroupId = $row['OrderGroupID'];
//             if (!isset($orders[$orderGroupId])) {
//                 $orders[$orderGroupId] = [
//                     'OrderGroupID'    => $row['OrderGroupID'],
//                     'OrderID'         => $row['OrderID'],
//                     'TransactionID'   => $row['TransactionID'],
//                     'UserID'          => $row['UserID'],
//                     'BranchID'        => $row['BranchID'],
//                     'BranchName'      => $row['BranchName'],
//                     'BranchAddress'   => $row['Address'],
//                     'BranchEmail'     => $row['Email'],
//                     'BranchContactNumber'     => $row['ContactNumber'],
//                     'OrderType'       => $row['OrderType'],
//                     'PaymentMethod'   => $row['PaymentMethod'],
//                     'Status'          => $row['Status'],
//                     'CustomerName'    => $row['CustomerName'],
//                     'TotalAmount'     => $row['TotalAmount'],
//                     'OrderDate'       => $row['OrderDate'],
//                     'DeliveryAddress'       => $row['DeliveryAddress'],
//                     'DeliveryLatitude'       => $row['DeliveryLatitude'],
//                     'DeliveryLongitude'       => $row['DeliveryLongitude'],
//                     'items'           => []
//                 ];
//             }
//             $orders[$orderGroupId]['items'][] = [
//                 'ItemID'       => $row['ItemID'],
//                 'ProductID'    => $row['ProductID'],
//                 'ProductName'  => $row['ProductName'],
//                 'Size'         => $row['Size'],
//                 'OrderedPrice' => $row['UnitPrice'],
//                 'Quantity'     => $row['Quantity'],
//                 'Subtotal'     => $row['Subtotal']
//             ];
//         }
//         return array_values($orders);
//     }
//     return ['error' => 'Query failed: ' . $conn->error];
// }

// function updateOrderStatus($conn, $orderGroupID, $newStatus, $oldStatus, $cancelReason)
// {
//     if ($newStatus === 'Order Placed') {

//         $sql = "SELECT 
//                     o.OrderGroupID, o.BranchID,
//                     od.ItemID, od.ProductID, od.Quantity, od.UnitPrice
//                 FROM `order` o
//                 INNER JOIN `order_items` od ON o.OrderGroupID = od.OrderGroupID
//                 WHERE o.OrderGroupID = ?";

//         $stmt = $conn->prepare($sql);
//         if (!$stmt) return ['success' => false, 'error' => 'Prepare failed: ' . $conn->error];

//         $stmt->bind_param("i", $orderGroupID);
//         $stmt->execute();
//         $orderItemsResult = $stmt->get_result(); // FIXED: Renamed to preserve dataset stream pointer references
//         $stmt->close();

//         if ($orderItemsResult && $orderItemsResult->num_rows > 0) {

//             $itemsArray = $orderItemsResult->fetch_all(MYSQLI_ASSOC);

//             $allAvailable = true;
//             $insufficientItems = [];

//             foreach ($itemsArray as $orderItem) {
//                 $productId = intval($orderItem['ProductID']);
//                 $branchId  = intval($orderItem['BranchID']);
//                 $quantity  = intval($orderItem['Quantity'] ?? 0);
//                 if ($quantity <= 0) continue;

//                 $checkSql = "SELECT Stock FROM branch_inventory WHERE BranchID = ? AND ProductID = ? AND Stock >= ?";
//                 $checkStmt = $conn->prepare($checkSql);
//                 $checkStmt->bind_param("iii", $branchId, $productId, $quantity);
//                 $checkStmt->execute();
//                 $inventoryMatch = $checkStmt->get_result()->fetch_assoc(); // FIXED: Saved to distinct variable pointer
//                 $checkStmt->close();

//                 if (!$inventoryMatch) {
//                     $allAvailable = false;
//                     $insufficientItems[] = "Product ID #{$productId} (Qty Required: {$quantity})";
//                 }
//             }

//             if (!$allAvailable) {
//                 return [
//                     'success' => false,
//                     'error' => 'Sufficient stock is unavailable for: ' . implode(", ", $insufficientItems)
//                 ];
//             }

//             $conn->begin_transaction(); // Start transaction to guarantee database integrity

//             try {
//                 $updateSql = "UPDATE branch_inventory SET Stock = Stock - ? WHERE BranchID = ? AND ProductID = ?";
//                 $updateStmt = $conn->prepare($updateSql);

//                 foreach ($itemsArray as $orderItem) {
//                     $productId = intval($orderItem['ProductID']);
//                     $branchId  = intval($orderItem['BranchID']);
//                     $quantity  = intval($orderItem['Quantity'] ?? 0);
//                     if ($quantity <= 0) continue;

//                     $updateStmt->bind_param("iii", $quantity, $branchId, $productId);
//                     if (!$updateStmt->execute()) {
//                         throw new Exception("Stock adjustment execution failure: " . $updateStmt->error);
//                     }
//                 }
//                 $updateStmt->close();

//                 $conn->commit();
//             } catch (Exception $e) {
//                 $conn->rollback(); // Cancel adjustments if an unexpected update error happens
//                 return ['success' => false, 'error' => 'Inventory update transaction failed: ' . $e->getMessage()];
//             }
//         } else {
//             return ['success' => false, 'error' => 'No item records found associated with this OrderGroupID number.'];
//         }
//     }

//     $sql = null;
//     $stmt = null;

//     if ($newStatus === 'Cancelled' && $cancelReason !== "") {
//         $sql = "UPDATE `order` SET `Status` = ?, `CancelReason` = ? WHERE `OrderGroupID` = ?";
//         $stmt = $conn->prepare($sql);
//         $stmt->bind_param("ssi", $newStatus, $cancelReason, $orderGroupID);
//     } else {
//         $sql = "UPDATE `order` SET `Status` = ? WHERE `OrderGroupID` = ?";
//         $stmt = $conn->prepare($sql);
//         $stmt->bind_param("si", $newStatus, $orderGroupID);
//     }

//     if (!$stmt) return ['success' => false, 'error' => $conn->error];


//     if ($stmt->execute()) {
//         $stmt->close();

//         $infoSql = "SELECT o.CustomerName, u.Email, o.BranchID, o.UserID, o.TransactionID 
//                     FROM `order` o 
//                     INNER JOIN `user` u ON o.UserID = u.UserID 
//                     WHERE o.OrderGroupID = ? 
//                     LIMIT 1";

//         $infoStmt = $conn->prepare($infoSql);
//         if ($infoStmt) {
//             $infoStmt->bind_param("i", $orderGroupID);
//             $infoStmt->execute();
//             $result = $infoStmt->get_result();
//             $customer = $result->fetch_assoc();
//             $infoStmt->close();

//             $logOldData = [
//                 'Status' => $oldStatus,
//             ];

//             $logNewData = [
//                 'Status' => $newStatus,
//             ];

//             $logAction = 'UPDATE';
//             $logDescription = "Updated order status for {$customer['TransactionID']}";

//             if ($newStatus === 'Cancelled' && $cancelReason !== "") {
//                 $logNewData['CancelReason'] = $cancelReason;

//                 $logAction = 'CANCEL';
//                 $logDescription = "Cancelled order {$customer['TransactionID']}";
//             }

//             createActivityLog(
//                 $conn,
//                 $logAction,
//                 'Order',
//                 $orderGroupID,
//                 $_SESSION['user_id'],
//                 $customer['BranchID'],
//                 $logDescription,
//                 $logOldData,
//                 $logNewData
//             );

//             if ($customer && !empty($customer['Email'])) {
//                 ($customer['Email'], $customer['CustomerName'], $orderGroupID, $newStatus);
//             }
//         }

        
//         return ['success' => true, 'message' => "Order status successfully updated to '{$newStatus}'."];
//     }

//     $error = $stmt->error;
//     $stmt->close();
//     return ['success' => false, 'error' => $error];
// }

// function handleOrderRequest($conn)
// {

//     header('Content-Type: application/json; charset=utf-8');
//     $method = $_SERVER['REQUEST_METHOD'];

//     if ($method === 'GET') {
//         if (isset($_GET['branchID'])) {
//             $branchID = isset($_GET['branchID']) ? (int)$_GET['branchID'] : 0;
//             if ($branchID <= 0) {
//                 http_response_code(400);
//                 echo json_encode(['error' => 'Invalid or missing branchID']);
//                 exit;
//             }

//             $data = getAllOrders($conn, $branchID);
//             if (isset($data['error'])) http_response_code(500);
//             echo json_encode($data);
//         } else if (isset($_GET['userID'])) {
//             $userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
//             if ($userID <= 0) {
//                 http_response_code(400);
//                 echo json_encode(['error' => 'Invalid or missing userID']);
//                 exit;
//             }

//             $data = getAllOrdersByUser($conn, $userID);
//             if (isset($data['error'])) http_response_code(500);
//             echo json_encode($data);
//         }
//         exit;
//     } elseif ($method === 'POST') {
//         $input = json_decode(file_get_contents('php://input'), true);
//         $orderGroupID = isset($input['orderGroupID']) ? (int)$input['orderGroupID'] : 0;
//         $newStatus    = isset($input['status']) ? trim($input['status']) : '';
//         $oldStatus    = isset($input['oldStatus']) ? trim($input['oldStatus']) : '';
//         $cancelReason    = isset($input['cancelReason']) ? trim($input['cancelReason']) : '';

//         if ($orderGroupID <= 0 || empty($newStatus)) {
//             http_response_code(400);
//             echo json_encode(['success' => false, 'error' => 'Missing required parameter values']);
//             exit;
//         }

//         $result = updateOrderStatus($conn, $orderGroupID, $newStatus, $oldStatus, $cancelReason);
//         if (!$result['success']) http_response_code(500);
//         echo json_encode($result);
//         exit;
//     }
// }

// if (basename($_SERVER['SCRIPT_FILENAME']) === 'order.php') {
//     handleOrderRequest($conn);
// }

// function getAllOrdersToday($conn, $branchID)
// {
//     $sql = "SELECT
//                 o.OrderGroupID, o.OrderID, o.OrderDate, o.OrderType, o.CustomerName, o.DeliveryAddress, o.Status,
//                 COUNT(oi.ItemID) as ItemCount, 
//                 o.TotalAmount
//             FROM `order` o
//             JOIN order_items oi ON o.OrderGroupID = oi.OrderGroupID
//             JOIN products p ON oi.ProductID = p.ProductID
//             LEFT JOIN `user` u ON o.UserID = u.UserID
//             WHERE o.BranchID = ? AND DATE(o.OrderDate) = CURDATE()
//             GROUP BY o.OrderGroupID, o.OrderID, o.OrderDate, o.OrderType, o.CustomerName, o.DeliveryAddress, o.Status, o.TotalAmount
//             ORDER BY o.OrderDate DESC";

//     $stmt = $conn->prepare($sql);
//     if (!$stmt) {
//         return ['error' => 'Prepare failed: ' . $conn->error];
//     }

//     $stmt->bind_param("i", $branchID);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result) {
//         $orders = $result->fetch_all(MYSQLI_ASSOC);

//         $stmt->close();

//         return $orders; // Removed redundant array_values()
//     }

//     $stmt->close();
//     return ['error' => 'Query failed: ' . $conn->error];
// }