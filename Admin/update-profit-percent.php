<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

include_once '../User/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$profit_percent = isset($_POST['profit_percent']) ? (float)$_POST['profit_percent'] : -1;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID.'
    ]);
    exit();
}

if ($profit_percent < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Profit percent must be greater than or equal to 0.'
    ]);
    exit();
}

mysqli_begin_transaction($connect);

try {
    $getProduct = $connect->prepare("
        SELECT
            product_id,
            COALESCE(average_import_price, 0) AS average_import_price
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");
    $getProduct->bind_param("i", $product_id);
    $getProduct->execute();
    $result = $getProduct->get_result();

    if (!$result || $result->num_rows === 0) {
        throw new Exception('Product not found.');
    }

    $product = $result->fetch_assoc();
    $average_import_price = (float)$product['average_import_price'];

    $selling_price = round($average_import_price * (1 + ($profit_percent / 100)));

    $updateProduct = $connect->prepare("
        UPDATE products
        SET
            profit_percent = ?,
            price = ?
        WHERE product_id = ?
    ");
    $updateProduct->bind_param("ddi", $profit_percent, $selling_price, $product_id);

    if (!$updateProduct->execute()) {
        throw new Exception('Failed to update product pricing.');
    }

    mysqli_commit($connect);

    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'profit_percent' => $profit_percent,
        'selling_price_raw' => $selling_price,
        'selling_price_formatted' => number_format($selling_price, 0, ',', '.') . ' đ'
    ]);
    exit();

} catch (Exception $e) {
    mysqli_rollback($connect);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}