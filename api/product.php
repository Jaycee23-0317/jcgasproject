<?php

include_once '../php/db_config.php';

function getAllProducts($conn)
{
    $sql = "SELECT
                ProductID,
                Brand,
                ProductName,
                Size,
                ImageName,
                CreatedAt
            FROM products
        ";
    $result = $conn->query($sql);

    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($products as $key => $p) {
            $imagePath = "../asset/img/product/" . $p['ImageName'];

            if (empty($p['ImageName']) || !file_exists($imagePath)) {
                $products[$key]['ImageName'] = null; // Force value to null so JS renders the fallback card seamlessly
            }
        }

        return $products;
    } else {
        return ['error' => 'Database query failed: ' . $conn->error];
    }
}


function addProduct($conn, $data)
{
    $sql = "INSERT INTO products (ProductName, Size, Price, Image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssds",
        $data['ProductName'],
        $data['Size'],
        $data['Price'],
        $data['ImageName'] ?? null
    );
    $success = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    return $success ? $newId : false;
}

function updateProduct($conn, $productID, $data)
{
    // Build dynamic SET clause based on provided fields
    $allowed = ['ProductName' => 's', 'Size' => 's', 'Price' => 'd', 'ImageName' => 's'];
    $fields = [];
    $types = '';
    $values = [];

    foreach ($allowed as $field => $type) {
        if (array_key_exists($field, $data)) {
            $fields[] = "`$field` = ?";
            $types .= $type;
            $values[] = $data[$field];
        }
    }

    if (empty($fields)) {
        return false;
    }

    $types .= 'i';
    $values[] = $productID;

    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function getProduct($conn, $productID)
{
    $sql = "SELECT * FROM products WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "i",
        $productID
    );
    $success = $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->fetch_assoc();
}

function deleteProduct($conn, $productID)
{
    $sql = "DELETE FROM products WHERE ProductID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productID);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function getAllProductsPerBranch($conn, $branchID)
{
    $sql = "SELECT
                p.Brand, p.ProductID, p.ProductName, p.Size, bi.Price, p.ImageName AS Image, bi.Stock
            FROM products p
            JOIN branch_inventory bi ON p.ProductID = bi.ProductID
            WHERE bi.BranchID = ?
            ORDER BY p.ProductID";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }

    $stmt->bind_param("i", $branchID);
    $success = $stmt->execute();

    if ($success) {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return ['error' => 'Database query failed: ' . $stmt->error];
    }
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'product.php') {
    handleOrderRequest($conn);
}

function handleOrderRequest($conn)
{
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];

    $branchId = $_GET['branch_id'] ?? null;

    if ($branchId) {
        $products = getAllProductsPerBranch($conn, $branchId);
        echo json_encode($products);
    } else if ($method == 'GET') {
        $products = getAllProducts($conn);
        echo json_encode($products);
    }
}
