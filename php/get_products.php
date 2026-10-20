<?php
header('Content-Type: application/json');

require_once 'db_config.php'; // Adjust this path to wherever your database config lives
include_once '../api/product.php';

$branchId = $_GET['branch_id'] ?? null;

if (!$branchId) {
    echo json_encode(['error' => 'No branch ID provided']);
    exit;
}

$products = getAllProductsPerBranch($conn, $branchId);

if (isset($products['error'])) {
    echo json_encode(['error' => $products['error']]);
    exit;
}

$groupedProducts = [];
foreach ($products as $product) {
    $imagePath = "../asset/img/product/" . $product['Image'];

    if (empty($product['Image']) || !file_exists($imagePath)) {
        $product['Image'] = null;
    }

    $brand = !empty($product['Brand']) ? $product['Brand'] : 'Unbranded Items';
    $groupedProducts[$brand][] = $product;
}

echo json_encode($groupedProducts);
exit;
