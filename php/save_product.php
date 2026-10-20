<?php

require_once '../api/branchInventory.php';
require_once '../api/activity_logs.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'db_config.php';
require_once '../api/activity_logs.php';
require_once '../api/product.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'Admin'
) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please sign in again.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$userID = $_SESSION['user_id'];

$productId = isset($_POST['product_id'])
    ? intval($_POST['product_id'])
    : 0;

$productBrand = trim($_POST['product_brand'] ?? '');
$productName  = trim($_POST['product_name'] ?? '');
$size         = trim($_POST['size'] ?? '');

if ($productName === '' || $size === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Product name and size are required.'
    ]);

    exit();
}

$targetDir = __DIR__ . '/../asset/img/product/';

if (!is_dir($targetDir)) {

    if (!mkdir($targetDir, 0777, true)) {

        echo json_encode([
            'success' => false,
            'message' => 'Unable to create product image directory.'
        ]);

        exit();
    }
}

$newImageFileName = null;
$oldImageFileName = null;
$imageData = null;

if ($productId > 0) {

    $oldSql = "
        SELECT ImageName
        FROM products
        WHERE ProductID = ?
    ";

    $oldStmt = $conn->prepare($oldSql);

    if (!$oldStmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare product lookup: ' . $conn->error
        ]);

        exit();
    }

    $oldStmt->bind_param("i", $productId);
    $oldStmt->execute();

    $oldResult = $oldStmt->get_result();
    $oldProduct = $oldResult->fetch_assoc();

    $oldStmt->close();

    if (!$oldProduct) {

        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);

        exit();
    }

    $oldImageFileName = $oldProduct['ImageName'];
}

if (
    isset($_FILES['product_image']) &&
    $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
) {

    $file = $_FILES['product_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {

        echo json_encode([
            'success' => false,
            'message' => 'Image upload failed. Error code: ' . $file['error']
        ]);

        exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) {

        echo json_encode([
            'success' => false,
            'message' => 'Image is too large. Maximum size is 5MB.'
        ]);

        exit();
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mimeType = finfo_file(
        $finfo,
        $file['tmp_name']
    );

    finfo_close($finfo);


    if (!isset($allowedMimeTypes[$mimeType])) {

        echo json_encode([
            'success' => false,
            'message' => 'Invalid image type. Allowed: JPG, PNG, GIF and WEBP.'
        ]);

        exit();
    }


    $extension = $allowedMimeTypes[$mimeType];

    if ($productId > 0) {

        $newImageFileName =
            'product_' . $productId . '.' . $extension;

    } else {

        $newImageFileName =
            'product_' . uniqid() . '.' . $extension;
    }

    $imageData = file_get_contents(
        $file['tmp_name']
    );

    if ($imageData === false) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to read uploaded image.'
        ]);

        exit();
    }

    $destination = $targetDir . $newImageFileName;

    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to save uploaded image to directory.'
        ]);

        exit();
    }
}

if ($productId > 0) {
    $isInserted = false;

    $oldProductData = getProduct($conn, $productId);

    if ($imageData !== null) {

        $sql = "
            UPDATE products
            SET
                Brand = ?,
                ProductName = ?,
                Size = ?,
                ImageName = ?,
                ImageFile = ?
            WHERE ProductID = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            // Remove the newly uploaded file because DB update
            // could not even be prepared.
            if (
                !empty($newImageFileName) &&
                file_exists($targetDir . $newImageFileName)
            ) {
                unlink($targetDir . $newImageFileName);
            }

            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare product update: ' . $conn->error
            ]);

            exit();
        }

        /*
        * ImageFile is parameter #5.
        *
        * 0 = Brand
        * 1 = ProductName
        * 2 = Size
        * 3 = ImageName
        * 4 = ImageFile
        * 5 = ProductID
        */

        $null = null;

        $stmt->bind_param(
            "ssssbi",
            $productBrand,
            $productName,
            $size,
            $newImageFileName,
            $null,
            $productId
        );

        /*
        * Send actual image binary to ImageFile.
        */
        $stmt->send_long_data(
            4,
            $imageData
        );

        // =================================================
        // UPDATE DATABASE FIRST
        // =================================================

        if (!$stmt->execute()) {

            // DB failed, so remove the newly uploaded file.
            if (
                !empty($newImageFileName) &&
                file_exists($targetDir . $newImageFileName)
            ) {
                unlink($targetDir . $newImageFileName);
            }

            echo json_encode([
                'success' => false,
                'message' => 'Failed to update product: ' . $stmt->error
            ]);

            $stmt->close();
            exit();
        }

        $stmt->close();

        // =================================================
        // DELETE OLD IMAGE FROM DIRECTORY
        // =================================================

        if (
            !empty($oldImageFileName) &&
            $oldImageFileName !== $newImageFileName
        ) {

            $oldImagePath = $targetDir . $oldImageFileName;

            if (file_exists($oldImagePath)) {

                if (!unlink($oldImagePath)) {

                    /*
                    * Database was already updated successfully,
                    * but old file could not be deleted.
                    */
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product updated successfully, but the old image file could not be deleted.',
                        'warning' => 'Old image: ' . $oldImageFileName
                    ]);

                    exit();
                }
            }
        }

        // =================================================
        // SUCCESS
        // =================================================

        echo json_encode([
            'success' => true,
            'message' => 'Product details and image successfully updated.',
            'imageName' => $newImageFileName
        ]);


        $isInserted = true;
        // exit();
    }

    // =====================================================
    // UPDATE WITHOUT NEW IMAGE
    // =====================================================

    else {

        $sql = "
            UPDATE products
            SET
                Brand = ?,
                ProductName = ?,
                Size = ?
            WHERE ProductID = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare product update: ' . $conn->error
            ]);

            exit();
        }

        $stmt->bind_param(
            "sssi",
            $productBrand,
            $productName,
            $size,
            $productId
        );

        if ($stmt->execute()) {
            $isInserted = true;

            echo json_encode([
                'success' => true,
                'message' => 'Product details successfully updated.'
            ]);

        } else {

            echo json_encode([
                'success' => false,
                'message' => 'Failed to update product: ' . $stmt->error
            ]);
        }


        $stmt->close();
    }
    
    if ($isInserted) {
        $logOldData = [
            'Brand' => $oldProductData['Brand'],
            'Product Name' => $oldProductData['ProductName'],
            'Size' => $oldProductData['Size'],
            'Image Name' => $oldProductData['ImageName']
        ];

        $imageFileName = $newImageFileName ?? $oldImageFileName;
        $logNewData = [
            'Brand' => $productBrand,
            'Product Name' => $productName,
            'Size' => $size,
            'Image Name' => $imageFileName
        ];

        createActivityLog(
            $conn,
            'UPDATE',
            'Product',
            $productId,
            $userID,
            null,
            "Updated product {$productName}",
            $logOldData,
            $logNewData
        );
    }
}

else {

    $sql = "
        INSERT INTO products
        (
            Brand,
            ProductName,
            Size,
            ImageName,
            ImageFile
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        if (
            $newImageFileName !== null &&
            file_exists($targetDir . $newImageFileName)
        ) {
            unlink($targetDir . $newImageFileName);
        }

        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare product insertion: ' . $conn->error
        ]);

        exit();
    }

    $null = null;

    $stmt->bind_param(
        "ssssb",
        $productBrand,
        $productName,
        $size,
        $newImageFileName,
        $null
    );

    if ($imageData !== null) {

        $stmt->send_long_data(
            4,
            $imageData
        );
    }

    if ($stmt->execute()) {

        $productID = $stmt->insert_id;

        $branchResult = addNewProductForAllBranches(
            $conn,
            $productID
        );

        if (!$branchResult) {

            echo json_encode([
                'success' => false,
                'message' => 'Product was created, but failed to add it to all branches.'
            ]);

            $stmt->close();
            $conn->close();
            exit();
        }

        $logNewData = [
            'Brand' => $productBrand,
            'Product Name' => $productName,
            'Size' => $size,
            'Image Name' => $newImageFileName
        ];

        createActivityLog(
            $conn,
            'CREATE',
            'Product',
            $productID,
            $userID,
            null,
            "Added product {$productName}",
            null,
            $logNewData
        );

        echo json_encode([
            'success' => true,
            'message' => 'New product successfully added to all branches.',
            'productID' => $productID
        ]);

    } else {

        if (
            $newImageFileName !== null &&
            file_exists($targetDir . $newImageFileName)
        ) {
            unlink($targetDir . $newImageFileName);
        }

        echo json_encode([
            'success' => false,
            'message' => 'Failed to insert product: ' . $stmt->error
        ]);
    }

    $stmt->close();
}

$conn->close();

exit();

?>