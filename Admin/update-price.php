<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// final commit
include '../User/connect.php';

$product_id = (int)($_POST['product_id'] ?? 0);
$profit_percent = (float)($_POST['profit_percent'] ?? 0);

if ($product_id <= 0 || $profit_percent < 0) {
    die("Dữ liệu không hợp lệ.");
}

$product_result = mysqli_query($connect, "
    SELECT default_import_price
    FROM products
    WHERE product_id = $product_id
");

$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    die("Không tìm thấy sản phẩm.");
}

$import_price = (float)$product['default_import_price'];
$selling_price = $import_price + ($import_price * $profit_percent / 100);

mysqli_query($connect, "
    UPDATE products
    SET 
        profit_percent = $profit_percent,
        price = $selling_price
    WHERE product_id = $product_id
");

header("Location: manage-prices.php");
exit();
?>