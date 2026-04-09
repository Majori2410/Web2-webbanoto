<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include_once '../User/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $profit_percent = isset($_POST['profit_percent']) ? (float)$_POST['profit_percent'] : 0;

    if ($product_id <= 0) {
        header("Location: manage-prices.php");
        exit();
    }

    $stmt = $connect->prepare("
        UPDATE products
        SET 
            profit_percent = ?,
            price = CASE
                WHEN default_import_price > 0
                THEN default_import_price * (1 + ? / 100)
                ELSE price
            END
        WHERE product_id = ?
    ");
    $stmt->bind_param("ddi", $profit_percent, $profit_percent, $product_id);
    $stmt->execute();

    header("Location: manage-prices.php?success=1&tab=product");
    exit();
}

header("Location: manage-prices.php");
exit();
?>