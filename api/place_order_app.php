<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/activity_logs.php";
require_once "../php/mail.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

$input = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}

$userId =
    intval(
        $input["user_id"] ?? 0
    );

$branchId =
    intval(
        $input["branch_id"] ?? 0
    );

$customerName =
    trim(
        $input["customer_name"] ?? ""
    );

$deliveryAddress =
    trim(
        $input["delivery_address"] ?? ""
    );

$deliveryLatitude =
    $input["delivery_latitude"]
        ?? null;

$deliveryLongitude =
    $input["delivery_longitude"]
        ?? null;

$paymentMethod =
    trim(
        $input["payment_method"] ?? ""
    );

$totalAmount =
    floatval(
        $input["total_amount"] ?? 0
    );

$cartItems =
    $input["cart_items"] ?? [];

if ($userId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid user ID."
    ]);

    exit;
}

if ($branchId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid branch ID."
    ]);

    exit;
}

if ($customerName === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Customer name is required."
    ]);

    exit;
}

if ($deliveryAddress === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Delivery address is required."
    ]);

    exit;
}

$normalizedPaymentMethod =
    strtolower(
        trim($paymentMethod)
    );


if (
    $normalizedPaymentMethod !== "cash" &&
    $normalizedPaymentMethod !== "cash on delivery"
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid payment method."
    ]);

    exit;
}

if (
    $normalizedPaymentMethod === "cash"
) {

    $paymentMethod = "Cash";

} else {

    $paymentMethod = "Cash On Delivery";
}

if ($totalAmount <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order total."
    ]);

    exit;
}

if (
    !is_array($cartItems) ||
    count($cartItems) === 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Cart is empty."
    ]);

    exit;
}

try {

    $userStmt =
        $pdo->prepare("
            SELECT
                UserID,
                FullName,
                BranchID
            FROM `user`
            WHERE UserID = ?
            LIMIT 1
        ");

    $userStmt->execute([
        $userId
    ]);

    $user =
        $userStmt->fetch();


    if (!$user) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);

        exit;
    }

    $branchStmt =
        $pdo->prepare("
            SELECT
                BranchID,
                BranchName,
                Address,
                Email,
                ContactNumber
            FROM branches
            WHERE BranchID = ?
            LIMIT 1
        ");

    $branchStmt->execute([
        $branchId
    ]);

    $branch =
        $branchStmt->fetch();


    if (!$branch) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Branch not found."
        ]);

        exit;
    }

    $pdo->beginTransaction();

    $transactionId =
        "JCGAS-" .
        strtoupper(
            bin2hex(
                random_bytes(5)
            )
        );

    $orderType =
        "Online";

    $orderStatus =
        "Pending Review";

    $orderIdStmt =
        $pdo->prepare("
            SELECT
                COALESCE(
                    MAX(OrderID),
                    0
                ) + 1 AS NextOrderID
            FROM `order`
            WHERE BranchID = ?
            AND DATE(OrderDate) = CURDATE()
        ");

    $orderIdStmt->execute([
        $branchId
    ]);

    $orderIdResult =
        $orderIdStmt->fetch();

    $orderId =
        intval(
            $orderIdResult["NextOrderID"]
        );

    $orderStmt =
        $pdo->prepare("
            INSERT INTO `order` (
                OrderID,
                TransactionID,
                UserID,
                BranchID,
                OrderType,
                PaymentMethod,
                Status,
                CustomerName,
                TotalAmount,
                DeliveryAddress,
                DeliveryLatitude,
                DeliveryLongitude,
                OrderDate
            )
            VALUES (
                :order_id,
                :transaction_id,
                :user_id,
                :branch_id,
                :order_type,
                :payment_method,
                :status,
                :customer_name,
                :total_amount,
                :delivery_address,
                :delivery_latitude,
                :delivery_longitude,
                NOW()
            )
        ");


    $orderStmt->execute([

        ":order_id" =>
            $orderId,

        ":transaction_id" =>
            $transactionId,

        ":user_id" =>
            $userId,

        ":branch_id" =>
            $branchId,

        ":order_type" =>
            $orderType,

        ":payment_method" =>
            $paymentMethod,

        ":status" =>
            $orderStatus,

        ":customer_name" =>
            $customerName,

        ":total_amount" =>
            $totalAmount,

        ":delivery_address" =>
            $deliveryAddress,

        ":delivery_latitude" =>
            $deliveryLatitude,

        ":delivery_longitude" =>
            $deliveryLongitude
    ]);

    $orderGroupId =
        intval(
            $pdo->lastInsertId()
        );

    $productStmt =
        $pdo->prepare("
            SELECT
                p.ProductID,
                p.ProductName,
                p.Brand,
                p.Size,
                bi.Price,
                bi.Stock
            FROM products p
            INNER JOIN branch_inventory bi
                ON p.ProductID = bi.ProductID
            WHERE p.ProductID = :product_id
            AND bi.BranchID = :branch_id
            LIMIT 1
            FOR UPDATE
        ");

    $itemStmt =
        $pdo->prepare("
            INSERT INTO order_items (
                OrderGroupID,
                ProductID,
                Quantity,
                UnitPrice,
                Subtotal
            )
            VALUES (
                :order_group_id,
                :product_id,
                :quantity,
                :unit_price,
                :subtotal
            )
        ");

    $calculatedTotal = 0.0;
    $logItems = [];

    foreach ($cartItems as $item) {

        $productId =
            intval(
                $item["product_id"] ?? 0
            );

        $quantity =
            intval(
                $item["quantity"] ?? 0
            );

        if ($productId <= 0) {

            throw new Exception(
                "Invalid product ID."
            );
        }

        if ($quantity <= 0) {

            throw new Exception(
                "Invalid quantity for product ID {$productId}."
            );
        }

        $productStmt->execute([

            ":product_id" =>
                $productId,

            ":branch_id" =>
                $branchId
        ]);


        $product =
            $productStmt->fetch();


        if (!$product) {

            throw new Exception(
                "Product ID {$productId} is not available in this branch."
            );
        }

        $stock =
            intval(
                $product["Stock"]
            );

        if ($quantity > $stock) {

            throw new Exception(
                "Not enough stock for {$product["ProductName"]}. Available stock: {$stock}."
            );
        }

        $unitPrice =
            floatval(
                $product["Price"]
            );

        $subtotal = $unitPrice * $quantity;

        $calculatedTotal +=  $subtotal;

        $itemStmt->execute([

            ":order_group_id" =>
                $orderGroupId,

            ":product_id" =>
                $productId,

            ":quantity" =>
                $quantity,

            ":unit_price" =>
                $unitPrice,

            ":subtotal" =>
                $subtotal
        ]);

        $logItems[
            (string) $productId
        ] = [

            "name" =>
                $product["ProductName"],

            "size" =>
                $product["Size"],

            "qty" =>
                $quantity,

            "price" =>
                $unitPrice
        ];
    }

    $calculatedTotal =
        round(
            $calculatedTotal,
            2
        );

    if (
        abs(
            $calculatedTotal -
            $totalAmount
        ) > 0.01
    ) {

        throw new Exception(
            "Order total does not match the current product prices."
        );
    }

    $logNewData = [

        "TransactionID" =>
            $transactionId,

        "OrderID" =>
            $orderGroupId,

        "CustomerName" =>
            $customerName,

        "OrderType" =>
            $orderType,

        "Status" =>
            $orderStatus,

        "PaymentMethod" =>
            $paymentMethod,

        "TotalAmount" =>
            $calculatedTotal,

        "DeliveryAddress" =>
            $deliveryAddress,

        "Items" =>
            $logItems
    ];

    createActivityLog(
        $conn,
        "CREATE",
        "Order",
        $orderGroupId,
        $userId,
        $branchId,
        "Created order {$transactionId}",
        null,
        $logNewData
    );

    $pdo->commit();

    http_response_code(200);

    $infoSql = "SELECT 
                o.CustomerName,
                u.Email,
                o.BranchID,
                o.UserID,
                o.TransactionID
            FROM `order` o
            INNER JOIN `user` u ON o.UserID = u.UserID
            WHERE o.OrderGroupID = ?
            LIMIT 1";

    $infoStmt = $pdo->prepare($infoSql);
    $infoStmt->execute([$orderGroupId]);

    $customer = $infoStmt->fetch(PDO::FETCH_ASSOC);

    if ($customer && !empty($customer['Email'])) {
        sendOrderStatusEmail(
            $customer['Email'],
            $customer['CustomerName'],
            $orderGroupId,
            $customer['TransactionID'],
            $orderStatus
        );
    }

    echo json_encode([
        "success" =>
            true,
        "message" =>
            "Order placed successfully.",

        "OrderGroupID" =>
            $orderGroupId,

        "OrderID" =>
            $orderId,

        "TransactionID" =>
            $transactionId,

        "Status" =>
            $orderStatus,

        "TotalAmount" =>
            $calculatedTotal
    ]);

} catch (Throwable $e) {

    if (
        isset($pdo) &&
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([

        "success" =>
            false,

        "message" =>
            $e->getMessage()
    ]);
}
?>