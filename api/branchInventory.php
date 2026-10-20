<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../php/db_config.php';
require_once '../api/activity_logs.php';

if (basename($_SERVER['SCRIPT_FILENAME']) === 'branchInventory.php') {
    handleBranchInventoryRequest($conn);
}

function getAllBranchInventory($conn)
{
    $sql = "SELECT 
                b.BranchID,      -- Added for your JS hidden input mapping
                bi.ProductID,    -- Added for your JS hidden input mapping
                b.BranchName,
                p.Brand,
                p.ProductName,
                p.Size,
                bi.Stock, 
                bi.Price
            FROM branch_inventory bi
            INNER JOIN products p ON bi.ProductID = p.ProductID        
            INNER JOIN branches b ON bi.BranchID = b.BranchID;
        ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $flatRows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $flatRows;
    }
    $stmt->close();
    return ['error' => 'Query failed: ' . $conn->error];
}

function  getBranchInventory($conn, $inventoryID) {
    $sql = "SELECT 
                bi.*,
                b.BranchName,
                p.ProductName
            FROM branch_inventory bi
            INNER JOIN branches b ON bi.BranchID = b.BranchID
            INNER JOIN products p ON bi.ProductID = p.ProductID
            WHERE bi.InventoryID = ?
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $inventoryID);

    if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }
}

function getBranchInventoryByBranchID($conn, $branchID) {
    $sql = "SELECT
                bi.*,
                b.BranchName,
                p.Brand,
                p.ProductName,
                p.Size
            FROM branch_inventory bi
            INNER JOIN branches b ON bi.BranchID = b.BranchID
            INNER JOIN products p ON bi.ProductID = p.ProductID
            WHERE bi.BranchID = ?
        ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchID);

    if (!$stmt) return ['error' => 'Prepare failed: ' . $conn->error];

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $flatRows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $flatRows;
    }
}

function updatedBranchInventoryStock($conn, $inventoryID) {
    $branchID     = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
    $stock     = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    $priceString = $_POST['price'] ?? '0.00';
    $price       = floatval(preg_replace('/[^\d.]/', '', $priceString));

    $userID = $_SESSION['user_id'];

    $oldBranchInventoryData = getBranchInventory($conn, $inventoryID);

    if ($inventoryID <= 0 || $stock < 0 || $price < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided. Stock and price values cannot be negative numbers.']);
        exit();
    }

    try {
        $sql = "UPDATE branch_inventory SET Stock = ?, Price = ? WHERE InventoryID = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Query preparation trace failed: " . $conn->error);
        }

        $stmt->bind_param("idi", $stock, $price, $inventoryID);

        if ($stmt->execute()) {
            $logOldData = [
                'Branch Name' => $oldBranchInventoryData['BranchName'],
                'Product Name' => $oldBranchInventoryData['ProductName'],
                'Stock' => $oldBranchInventoryData['Stock'],
                'Price' => $oldBranchInventoryData['Price'],
            ];

            $logNewData = [
                'Branch Name' => $oldBranchInventoryData['BranchName'],
                'Product Name' => $oldBranchInventoryData['ProductName'],
                'Stock' => $stock,
                'Price' => $price,
            ];

            createActivityLog(
                $conn,
                'UPDATE',
                'Inventory',
                $inventoryID,
                $userID,
                $branchID,
                "Updated inventory stock of {$oldBranchInventoryData['ProductName']} for {$oldBranchInventoryData['BranchName']}",
                $logOldData,
                $logNewData
            );
            echo json_encode(['success' => true, 'message' => 'Inventory records successfully updated!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution tracking update failure occurred: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database processing exception occurred: ' . $e->getMessage()]);
    }

    $conn->close();
    exit();
}

function addNewProductForAllBranches($conn, $productID) {

    $sql = "SELECT BranchID FROM branches";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->execute();

    $branches = $stmt->get_result();

    if ($branches->num_rows > 0) {

        $insertSql = "
            INSERT INTO branch_inventory
            (BranchID, ProductID)
            VALUES (?, ?)
        ";

        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            $stmt->close();
            return false;
        }

        foreach ($branches as $branch) {

            $branchID = $branch['BranchID'];

            $insertStmt->bind_param(
                "ii",
                $branchID,
                $productID
            );

            if (!$insertStmt->execute()) {
                $insertStmt->close();
                $stmt->close();
                return false;
            }
        }

        $insertStmt->close();
    }

    $stmt->close();

    return true;
}

function addAllProductsForNewBranch($conn, $branchID) {

    $sql = "SELECT ProductID FROM products";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->execute();

    $products = $stmt->get_result();

    if ($products->num_rows > 0) {

        $insertSql = "
            INSERT INTO branch_inventory
            (BranchID, ProductID)
            VALUES (?, ?)
        ";

        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            $stmt->close();
            return false;
        }

        foreach ($products as $product) {

            $productID = $product['ProductID'];

            $insertStmt->bind_param(
                "ii",
                $branchID,
                $productID
            );

            if (!$insertStmt->execute()) {
                $insertStmt->close();
                $stmt->close();
                return false;
            }
        }

        $insertStmt->close();
    }

    $stmt->close();

    return true;
} 

function handleBranchInventoryRequest($conn)
{
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'GET') {
        if (isset($_GET['branchId'])) {
            $selectedBranchID = $_GET['branchId'];
            $selectedBranchInventory = getBranchInventoryByBranchID($conn, $selectedBranchID);
            echo json_encode($selectedBranchInventory);
        } else {
            $inventory = getAllBranchInventory($conn);
            echo json_encode($inventory);
        }
    } else if ($method == 'POST') {
        if (isset($_GET['inventoryID'])) {
            $selectedInventoryID = $_GET['inventoryID'];
            $updatedBranchInventoryStock = updatedBranchInventoryStock($conn, $selectedInventoryID);
            echo json_encode($updatedBranchInventoryStock);
        }
    }


}