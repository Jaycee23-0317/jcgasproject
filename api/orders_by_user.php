<?php

header('Content-Type: application/json');

require_once 'config/database.php';

try {

    $userID = isset($_GET['UserID'])
        ? intval($_GET['UserID'])
        : 0;

    if ($userID <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid UserID."
        ]);

        exit;
    }

    $startDate = "2000-01-01 00:00:00";

    $endDate = date(
        "Y-m-d H:i:s",
        strtotime("+1 day")
    );


    $sql = "SELECT 
                o.OrderGroupID,
                o.OrderID,
                o.TransactionID,
                o.UserID,
                o.BranchID,

                b.BranchName,
                b.Address,
                b.Email,
                b.ContactNumber,

                o.OrderType,
                o.PaymentMethod,
                o.Status,
                o.CustomerName,
                o.TotalAmount,
                o.OrderDate,

                o.DeliveryAddress,
                o.DeliveryLatitude,
                o.DeliveryLongitude,

                od.ItemID,
                od.ProductID,
                od.Quantity,
                od.UnitPrice,
                od.Subtotal,

                p.ProductName,
                p.Size

            FROM `order` o

            INNER JOIN order_items od
                ON o.OrderGroupID = od.OrderGroupID

            INNER JOIN products p
                ON od.ProductID = p.ProductID

            INNER JOIN branches b
                ON o.BranchID = b.BranchID

            WHERE o.UserID = ?
              AND o.OrderDate BETWEEN ? AND ?

            ORDER BY
                o.OrderGroupID DESC,
                od.ItemID DESC";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $userID,
        $startDate,
        $endDate
    ]);


    $flatRows = $stmt->fetchAll();

    $orders = [];


    foreach ($flatRows as $row) {

        $orderGroupId =
            $row["OrderGroupID"];


        if (!isset($orders[$orderGroupId])) {

            $orders[$orderGroupId] = [

                "OrderGroupID" =>
                    $row["OrderGroupID"],

                "OrderID" =>
                    $row["OrderID"],

                "TransactionID" =>
                    $row["TransactionID"],

                "UserID" =>
                    $row["UserID"],

                "BranchID" =>
                    $row["BranchID"],

                "BranchName" =>
                    $row["BranchName"],

                "BranchAddress" =>
                    $row["Address"],

                "BranchEmail" =>
                    $row["Email"],

                "BranchContactNumber" =>
                    $row["ContactNumber"],

                "OrderType" =>
                    $row["OrderType"],

                "PaymentMethod" =>
                    $row["PaymentMethod"],

                "Status" =>
                    $row["Status"],

                "CustomerName" =>
                    $row["CustomerName"],

                "TotalAmount" =>
                    $row["TotalAmount"],

                "OrderDate" =>
                    $row["OrderDate"],

                "DeliveryAddress" =>
                    $row["DeliveryAddress"],

                "DeliveryLatitude" =>
                    $row["DeliveryLatitude"],

                "DeliveryLongitude" =>
                    $row["DeliveryLongitude"],

                "items" => []
            ];
        }


        $orders[$orderGroupId]["items"][] = [

            "ItemID" =>
                $row["ItemID"],

            "ProductName" =>
                $row["ProductName"],

            "Size" =>
                $row["Size"],

            "OrderedPrice" =>
                $row["UnitPrice"],

            "Quantity" =>
                $row["Quantity"],

            "Subtotal" =>
                $row["Subtotal"]
        ];
    }


    echo json_encode(
        array_values($orders)
    );

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);
}