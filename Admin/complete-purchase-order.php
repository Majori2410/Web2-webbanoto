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

include '../User/connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Missing purchase order ID.");
}

$purchase_id = (int)$_GET['id'];

/* Get purchase order */
$orderStmt = $connect->prepare("
    SELECT purchase_id, status
    FROM purchase_orders
    WHERE purchase_id = ?
    LIMIT 1
");
$orderStmt->bind_param("i", $purchase_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    die("Purchase order not found.");
}

if ($order['status'] === 'completed') {
    header("Location: view-purchase-order.php?id=" . $purchase_id);
    exit();
}

/* Get all items of this purchase order */
$itemsStmt = $connect->prepare("
    SELECT
        item_id,
        product_id,
        quantity,
        import_price
    FROM purchase_order_items
    WHERE purchase_id = ?
    ORDER BY item_id ASC
");
$itemsStmt->bind_param("i", $purchase_id);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

if (!$itemsResult || $itemsResult->num_rows === 0) {
    die("This purchase order has no items to complete.");
}

mysqli_begin_transaction($connect);

try {
    /*
        Load current product data.

        We need:
        - remain_quantity
        - average_import_price
        - profit_percent

        profit_percent is needed to recalculate products.price
        whenever average_import_price changes after completion.
    */
    $getProductStmt = $connect->prepare("
        SELECT
            product_id,
            COALESCE(remain_quantity, 0) AS remain_quantity,
            COALESCE(average_import_price, 0) AS average_import_price,
            COALESCE(profit_percent, 0) AS profit_percent
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");

    /*
        Get completed import stats of this product BEFORE completing current order.

        Business rules:
        - actual import sequence = MAX(completed import_sequence) + 1
        - previous_import_price = latest completed import price
          (ordered by import_sequence DESC, item_id DESC)
    */
    $getImportStatsStmt = $connect->prepare("
        SELECT
            COALESCE(MAX(poi.import_sequence), 0) AS max_import_sequence,
            SUBSTRING_INDEX(
                GROUP_CONCAT(poi.import_price ORDER BY poi.import_sequence DESC, poi.item_id DESC),
                ',',
                1
            ) AS previous_import_price
        FROM purchase_order_items poi
        INNER JOIN purchase_orders po
            ON po.purchase_id = poi.purchase_id
        WHERE poi.product_id = ?
          AND po.status = 'completed'
    ");

    /*
        Update product:
        - remain_quantity
        - average_import_price
        - price (selling price)
    */
    $updateProductStmt = $connect->prepare("
        UPDATE products
        SET
            remain_quantity = ?,
            average_import_price = ?,
            price = ?
        WHERE product_id = ?
    ");

    /* Update actual import sequence when completed */
    $updateItemSequenceStmt = $connect->prepare("
        UPDATE purchase_order_items
        SET import_sequence = ?
        WHERE item_id = ?
    ");

    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $item_id = (int)$item['item_id'];
        $product_id = (int)$item['product_id'];
        $import_quantity = (int)$item['quantity'];
        $new_import_price = (float)$item['import_price'];

        if ($import_quantity <= 0) {
            throw new Exception("Invalid import quantity for item ID {$item_id}.");
        }

        if ($new_import_price <= 0) {
            throw new Exception("Invalid new import price for item ID {$item_id}.");
        }

        /* Load current product data */
        $getProductStmt->bind_param("i", $product_id);
        $getProductStmt->execute();
        $productResult = $getProductStmt->get_result();
        $product = $productResult->fetch_assoc();

        if (!$product) {
            throw new Exception("Product ID {$product_id} not found.");
        }

        $current_stock = (int)$product['remain_quantity'];
        $current_profit_percent = (float)$product['profit_percent'];

        /* Load completed import stats before this completion */
        $getImportStatsStmt->bind_param("i", $product_id);
        $getImportStatsStmt->execute();
        $statsResult = $getImportStatsStmt->get_result();
        $stats = $statsResult->fetch_assoc();

        $max_import_sequence = (int)($stats['max_import_sequence'] ?? 0);
        $previous_import_price = isset($stats['previous_import_price']) && $stats['previous_import_price'] !== null
            ? (float)$stats['previous_import_price']
            : 0.0;

        /*
            Actual import sequence is assigned ONLY when completing.

            Correct rule:
            actual import sequence = MAX(completed import_sequence) + 1

            DO NOT use COUNT(*)
        */
        $actual_import_sequence = $max_import_sequence + 1;

        $new_remain_quantity = $current_stock + $import_quantity;

        /*
            Average import price rule:
            average import price =
            (current stock * previous import price + import quantity * new import price)
            / (current stock + import quantity)

            If this is the first completed import:
            - current stock may be 0
            - previous import price may be 0
            => result becomes new import price
        */
        if ($new_remain_quantity > 0) {
            $new_average_import_price =
                (($current_stock * $previous_import_price) + ($import_quantity * $new_import_price))
                / $new_remain_quantity;
        } else {
            $new_average_import_price = 0;
        }

        /*
            Recalculate current selling price after average import price changes.

            Formula:
            selling price = average import price * (1 + profit_percent / 100)
        */
        $new_selling_price = round($new_average_import_price * (1 + ($current_profit_percent / 100)));

        /* Update product */
        $updateProductStmt->bind_param(
            "iddi",
            $new_remain_quantity,
            $new_average_import_price,
            $new_selling_price,
            $product_id
        );

        if (!$updateProductStmt->execute()) {
            throw new Exception("Failed to update product ID {$product_id}.");
        }

        /* Update actual import sequence for this item */
        $updateItemSequenceStmt->bind_param("ii", $actual_import_sequence, $item_id);

        if (!$updateItemSequenceStmt->execute()) {
            throw new Exception("Failed to update import sequence for item ID {$item_id}.");
        }
    }

    /* Mark purchase order as completed */
    $completeStmt = $connect->prepare("
        UPDATE purchase_orders
        SET status = 'completed'
        WHERE purchase_id = ?
    ");
    $completeStmt->bind_param("i", $purchase_id);

    if (!$completeStmt->execute()) {
        throw new Exception("Failed to complete purchase order.");
    }

    mysqli_commit($connect);

    header("Location: view-purchase-order.php?id=" . $purchase_id . "&completed=1");
    exit();

} catch (Exception $e) {
    mysqli_rollback($connect);
    die("Error while completing purchase order: " . $e->getMessage());
}
?>