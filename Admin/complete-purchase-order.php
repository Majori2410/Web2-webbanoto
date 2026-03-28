<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// final commit
include '../User/connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Thiếu mã phiếu nhập.");
}

$purchase_id = (int)$_GET['id'];

$order_result = mysqli_query($connect, "
    SELECT * FROM purchase_orders
    WHERE purchase_id = $purchase_id
");

$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die("Không tìm thấy phiếu nhập.");
}

if ($order['status'] === 'completed') {
    header("Location: view-purchase-order.php?id=$purchase_id");
    exit();
}

$items_result = mysqli_query($connect, "
    SELECT * FROM purchase_order_items
    WHERE purchase_id = $purchase_id
");

mysqli_begin_transaction($connect);

try {
    while ($item = mysqli_fetch_assoc($items_result)) {
        $product_id = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        $import_price = (float)$item['import_price'];
        $profit_percent = (float)$item['profit_percent'];
        $selling_price = (float)$item['selling_price'];

        mysqli_query($connect, "
            UPDATE products
            SET remain_quantity = remain_quantity + $quantity,
                default_import_price = $import_price,
                profit_percent = $profit_percent,
                price = $selling_price
            WHERE product_id = $product_id
        ");
    }

    mysqli_query($connect, "
        UPDATE purchase_orders
        SET status = 'completed'
        WHERE purchase_id = $purchase_id
    ");

    mysqli_commit($connect);

    header("Location: view-purchase-order.php?id=$purchase_id");
    exit();
} catch (Exception $e) {
    mysqli_rollback($connect);
    die("Lỗi hoàn thành phiếu nhập: " . $e->getMessage());
}
?>