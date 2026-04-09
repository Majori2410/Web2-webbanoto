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

$message = '';
$messageType = '';

$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($purchase_id <= 0) {
    die('Invalid purchase order ID.');
}

/*
    LOAD PRODUCTS FOR DATALIST

    Rules:
    - previous_import_price = latest COMPLETED import price
    - max_import_sequence = highest COMPLETED import sequence
    - import_count shown on UI = max_import_sequence + 1
    - exclude current purchase order from stats
*/
$products = [];
$productSql = "
    SELECT
        p.product_id,
        p.car_name,
        COALESCE(p.remain_quantity, 0) AS remain_quantity,
        COALESCE(import_stats.max_import_sequence, 0) AS max_import_sequence,
        CASE
            WHEN COALESCE(import_stats.max_import_sequence, 0) > 0
                THEN import_stats.max_import_sequence + 1
            ELSE 1
        END AS import_count,
        import_stats.previous_import_price
    FROM products p
    LEFT JOIN (
        SELECT
            x.product_id,
            MAX(x.import_sequence) AS max_import_sequence,
            SUBSTRING_INDEX(
                GROUP_CONCAT(x.import_price ORDER BY x.import_sequence DESC, x.item_id DESC),
                ',',
                1
            ) AS previous_import_price
        FROM (
            SELECT
                poi.item_id,
                poi.product_id,
                poi.import_sequence,
                poi.import_price
            FROM purchase_order_items poi
            INNER JOIN purchase_orders po
                ON po.purchase_id = poi.purchase_id
            WHERE po.status = 'completed'
              AND poi.purchase_id <> ?
        ) x
        GROUP BY x.product_id
    ) import_stats ON import_stats.product_id = p.product_id
    ORDER BY p.car_name ASC
";
$productStmt = $connect->prepare($productSql);
$productStmt->bind_param("i", $purchase_id);
$productStmt->execute();
$productResult = $productStmt->get_result();

if ($productResult) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

/* LOAD SUPPLIERS FOR DATALIST */
$suppliers = [];
$supplierQuery = mysqli_query($connect, "SELECT supplier_name FROM suppliers ORDER BY supplier_name ASC");
if ($supplierQuery) {
    while ($row = mysqli_fetch_assoc($supplierQuery)) {
        $suppliers[] = $row['supplier_name'];
    }
}

/* LOAD PURCHASE ORDER */
$orderSql = "
    SELECT
        po.purchase_id,
        po.purchase_code,
        po.purchase_date,
        po.note,
        po.status,
        s.supplier_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.purchase_id = ?
    LIMIT 1
";
$orderStmt = $connect->prepare($orderSql);
$orderStmt->bind_param("i", $purchase_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if (!$orderResult || $orderResult->num_rows === 0) {
    die('Purchase order not found.');
}

$order = $orderResult->fetch_assoc();

if ($order['status'] !== 'draft') {
    die('Only draft purchase orders can be edited.');
}

/*
    LOAD CURRENT ITEMS OF THIS DRAFT

    Rules:
    - current item import_sequence is baseline max completed sequence stored in draft
    - displayed import count for draft = import_sequence + 1
    - previous_import_price comes from completed orders only, excluding current order
*/
$itemSql = "
    SELECT
        poi.item_id,
        poi.product_id,
        poi.import_sequence,
        poi.quantity,
        poi.import_price,
        p.car_name,
        COALESCE(p.remain_quantity, 0) AS remain_quantity,
        import_stats.previous_import_price
    FROM purchase_order_items poi
    INNER JOIN products p ON poi.product_id = p.product_id
    LEFT JOIN (
        SELECT
            x.product_id,
            SUBSTRING_INDEX(
                GROUP_CONCAT(x.import_price ORDER BY x.import_sequence DESC, x.item_id DESC),
                ',',
                1
            ) AS previous_import_price
        FROM (
            SELECT
                poi3.item_id,
                poi3.product_id,
                poi3.import_sequence,
                poi3.import_price
            FROM purchase_order_items poi3
            INNER JOIN purchase_orders po3
                ON po3.purchase_id = poi3.purchase_id
            WHERE po3.status = 'completed'
              AND poi3.purchase_id <> ?
        ) x
        GROUP BY x.product_id
    ) import_stats ON import_stats.product_id = poi.product_id
    WHERE poi.purchase_id = ?
    ORDER BY poi.item_id ASC
";
$itemStmt = $connect->prepare($itemSql);
$itemStmt->bind_param("ii", $purchase_id, $purchase_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
if ($itemResult) {
    while ($row = $itemResult->fetch_assoc()) {
        $items[] = $row;
    }
}

function toPositiveFloat($value): float {
    return max(0, (float)$value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($connect);

    try {
        $purchase_code = trim($_POST['purchase_code'] ?? '');
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $note = trim($_POST['note'] ?? '');

        $product_ids = $_POST['product_id'] ?? [];
        $product_names = $_POST['product_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $new_import_prices = $_POST['new_import_price'] ?? [];

        if ($purchase_code === '' || $purchase_date === '' || $supplier_name === '') {
            throw new Exception('Please enter purchase order code, purchase date, and supplier name.');
        }

        /* CHECK DUPLICATE PURCHASE CODE EXCLUDING CURRENT ORDER */
        $checkCode = $connect->prepare("
            SELECT purchase_id
            FROM purchase_orders
            WHERE purchase_code = ?
              AND purchase_id <> ?
        ");
        $checkCode->bind_param("si", $purchase_code, $purchase_id);
        $checkCode->execute();
        $checkCodeResult = $checkCode->get_result();

        if ($checkCodeResult && $checkCodeResult->num_rows > 0) {
            throw new Exception("Purchase order code '{$purchase_code}' already exists.");
        }

        /* CHECK / INSERT SUPPLIER */
        $supplier_id = null;

        $checkSupplier = $connect->prepare("SELECT supplier_id FROM suppliers WHERE supplier_name = ?");
        $checkSupplier->bind_param("s", $supplier_name);
        $checkSupplier->execute();
        $supplierResult = $checkSupplier->get_result();

        if ($supplierResult && $supplierResult->num_rows > 0) {
            $supplierRow = $supplierResult->fetch_assoc();
            $supplier_id = (int)$supplierRow['supplier_id'];
        } else {
            $insertSupplier = $connect->prepare("INSERT INTO suppliers (supplier_name) VALUES (?)");
            $insertSupplier->bind_param("s", $supplier_name);

            if (!$insertSupplier->execute()) {
                throw new Exception('Unable to add new supplier.');
            }

            $supplier_id = $insertSupplier->insert_id;
        }

        /* VALIDATE ITEMS */
        $total_amount = 0;
        $valid_items = [];
        $used_product_ids = [];

        $lineCount = max(
            count($product_ids),
            count($product_names),
            count($quantities),
            count($new_import_prices)
        );

        for ($i = 0; $i < $lineCount; $i++) {
            $product_id = isset($product_ids[$i]) ? (int)$product_ids[$i] : 0;
            $product_name = trim($product_names[$i] ?? '');
            $quantity = isset($quantities[$i]) ? (int)$quantities[$i] : 0;

            $new_import_price_raw = $new_import_prices[$i] ?? '0';
            $new_import_price_clean = str_replace('.', '', $new_import_price_raw);
            $new_import_price = toPositiveFloat($new_import_price_clean);

            if ($product_name === '' && $quantity === 0 && $new_import_price == 0) {
                continue;
            }

            if ($product_id <= 0) {
                throw new Exception("Product '{$product_name}' is invalid. Please select a product from the suggestion list.");
            }

            if (in_array($product_id, $used_product_ids, true)) {
                throw new Exception("Product '{$product_name}' is duplicated in the same purchase order.");
            }
            $used_product_ids[] = $product_id;

            if ($quantity <= 0) {
                throw new Exception("Import quantity for '{$product_name}' must be greater than 0.");
            }

            if ($new_import_price <= 0) {
                throw new Exception("New import price (this order) for '{$product_name}' must be greater than 0.");
            }

            /*
                IMPORTANT:
                only completed orders affect baseline import sequence and previous import price
                current draft order is excluded
            */
            $productCheck = $connect->prepare("
                SELECT
                    p.product_id,
                    p.car_name,
                    COALESCE(p.remain_quantity, 0) AS remain_quantity,
                    COALESCE(import_stats.max_import_sequence, 0) AS max_import_sequence,
                    CASE
                        WHEN COALESCE(import_stats.max_import_sequence, 0) > 0
                            THEN import_stats.max_import_sequence + 1
                        ELSE 1
                    END AS import_count,
                    import_stats.previous_import_price
                FROM products p
                LEFT JOIN (
                    SELECT
                        x.product_id,
                        MAX(x.import_sequence) AS max_import_sequence,
                        SUBSTRING_INDEX(
                            GROUP_CONCAT(x.import_price ORDER BY x.import_sequence DESC, x.item_id DESC),
                            ',',
                            1
                        ) AS previous_import_price
                    FROM (
                        SELECT
                            poi.item_id,
                            poi.product_id,
                            poi.import_sequence,
                            poi.import_price
                        FROM purchase_order_items poi
                        INNER JOIN purchase_orders po
                            ON po.purchase_id = poi.purchase_id
                        WHERE po.status = 'completed'
                          AND poi.purchase_id <> ?
                    ) x
                    GROUP BY x.product_id
                ) import_stats ON import_stats.product_id = p.product_id
                WHERE p.product_id = ?
                LIMIT 1
            ");
            $productCheck->bind_param("ii", $purchase_id, $product_id);
            $productCheck->execute();
            $productResult = $productCheck->get_result();

            if (!$productResult || $productResult->num_rows === 0) {
                throw new Exception("Selected product '{$product_name}' no longer exists.");
            }

            $dbProduct = $productResult->fetch_assoc();
            $dbProductName = $dbProduct['car_name'];
            $dbCurrentStock = (int)$dbProduct['remain_quantity'];
            $maxImportSequence = (int)($dbProduct['max_import_sequence'] ?? 0);
            $displayImportCount = (int)($dbProduct['import_count'] ?? 1);
            $previousImportPrice = isset($dbProduct['previous_import_price']) && $dbProduct['previous_import_price'] !== null
                ? (float)$dbProduct['previous_import_price']
                : null;

            /*
                Draft stores baseline max completed import sequence.
                Actual import sequence will be assigned only when completed.
            */
            $import_sequence = $maxImportSequence;

            $line_total = $quantity * $new_import_price;
            $total_amount += $line_total;

            $valid_items[] = [
                'product_id' => $product_id,
                'product_name' => $dbProductName,
                'current_stock' => $dbCurrentStock,
                'previous_import_price' => $previousImportPrice,
                'display_import_count' => $displayImportCount,
                'import_sequence' => $import_sequence,
                'quantity' => $quantity,
                'new_import_price' => $new_import_price,
                'line_total' => $line_total
            ];
        }

        if (count($valid_items) === 0) {
            throw new Exception('Please add at least one valid product.');
        }

        /* UPDATE PURCHASE ORDER */
        $updateOrder = $connect->prepare("
            UPDATE purchase_orders
            SET
                purchase_code = ?,
                supplier_id = ?,
                purchase_date = ?,
                note = ?,
                total_amount = ?
            WHERE purchase_id = ?
              AND status = 'draft'
        ");
        $updateOrder->bind_param(
            "sissdi",
            $purchase_code,
            $supplier_id,
            $purchase_date,
            $note,
            $total_amount,
            $purchase_id
        );

        if (!$updateOrder->execute()) {
            throw new Exception('Unable to update purchase order.');
        }

        /* DELETE OLD ITEMS OF THIS DRAFT */
        $deleteItems = $connect->prepare("DELETE FROM purchase_order_items WHERE purchase_id = ?");
        $deleteItems->bind_param("i", $purchase_id);

        if (!$deleteItems->execute()) {
            throw new Exception('Unable to replace old product lines.');
        }

        /* INSERT NEW DRAFT ITEMS */
        $insertItem = $connect->prepare("
            INSERT INTO purchase_order_items (
                purchase_id,
                product_id,
                import_sequence,
                quantity,
                import_price
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($valid_items as $item) {
            $insertItem->bind_param(
                "iiiid",
                $purchase_id,
                $item['product_id'],
                $item['import_sequence'],
                $item['quantity'],
                $item['new_import_price']
            );

            if (!$insertItem->execute()) {
                throw new Exception("Unable to save product '{$item['product_name']}'.");
            }
        }

        mysqli_commit($connect);

        header("Location: view-purchase-order.php?id=" . $purchase_id . "&updated=1");
        exit();

    } catch (Exception $e) {
        mysqli_rollback($connect);
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Purchase Order</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f4f6f9;
        }

        .page{
            max-width:1450px;
            margin:30px auto;
            background:#fff;
            padding:24px;
            border-radius:14px;
            box-shadow:0 8px 24px rgba(0,0,0,0.08);
        }

        h1{
            margin-top:0;
            color:#2c3e50;
            margin-bottom:20px;
        }

        .top-link{
            display:inline-block;
            margin-bottom:18px;
            color:#1abc9c;
            text-decoration:none;
            font-weight:bold;
        }

        .message{
            padding:14px 16px;
            border-radius:10px;
            margin-bottom:18px;
            font-weight:600;
        }

        .message.success{
            background:#dcfce7;
            color:#166534;
        }

        .message.error{
            background:#fee2e2;
            color:#991b1b;
        }

        .form-grid{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:18px 20px;
            margin-bottom:18px;
        }

        .form-group{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .form-group.full{
            grid-column:1 / -1;
        }

        label{
            font-weight:700;
            color:#334155;
            font-size:14px;
        }

        .form-input{
            width:100%;
            height:44px;
            padding:10px 12px;
            border:1px solid #d1d5db;
            border-radius:8px;
            font-size:14px;
            box-sizing:border-box;
            outline:none;
            background:#fff;
        }

        .form-input:focus{
            border-color:#1abc9c;
            box-shadow:0 0 0 3px rgba(26,188,156,0.15);
        }

        textarea.form-input{
            height:110px;
            resize:vertical;
            line-height:1.5;
            padding-top:12px;
        }

        .section-title{
            font-size:22px;
            color:#2c3e50;
            margin:24px 0 12px;
            font-weight:700;
        }

        .section-desc{
            margin-top:-2px;
            margin-bottom:16px;
            color:#64748b;
            font-size:14px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
        }

        th, td{
            padding:10px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            vertical-align:middle;
        }

        th{
            background:#2c3e50;
            color:#fff;
            font-size:14px;
        }

        .input-wrap{
            position:relative;
            width:100%;
        }

        .product-input{
            width:100%;
            min-width:260px;
            padding-right:36px;
        }

        .small-input{
            width:100%;
            min-width:130px;
        }

        .readonly-input{
            background:#f8fafc;
            color:#475569;
        }

        .first-import-note{
            display:block;
            margin-top:6px;
            color:#2563eb;
            font-size:12px;
            font-style:italic;
        }

        .input-icon{
            position:absolute;
            top:50%;
            right:12px;
            transform:translateY(-50%);
            color:#64748b;
            font-size:12px;
            pointer-events:none;
            display:block !important;
            z-index:2;
        }

        input[list]::-webkit-calendar-picker-indicator{
            display:none !important;
            opacity:0 !important;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            opacity:1;
            margin:0;
        }

        input[type="number"] {
            appearance:auto;
            -webkit-appearance:auto;
            -moz-appearance:auto;
        }

        .btn{
            padding:10px 16px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:700;
            transition:0.2s ease;
            text-decoration:none;
            display:inline-block;
        }

        .btn-primary{
            background:#1abc9c;
            color:#fff;
        }

        .btn-primary:hover{
            background:#16a085;
        }

        .btn-secondary{
            background:#e5e7eb;
            color:#334155;
        }

        .btn-secondary:hover{
            background:#d1d5db;
        }

        .btn-danger{
            background:#ef4444;
            color:#fff;
        }

        .btn-danger:hover{
            background:#dc2626;
        }

        .actions{
            display:flex;
            gap:12px;
            margin-top:18px;
            flex-wrap:wrap;
            align-items:center;
            justify-content:space-between;
        }

        .left-actions,
        .right-actions{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            align-items:center;
        }

        .hint{
            color:#6b7280;
            font-size:13px;
            margin-top:4px;
        }

        .summary-box{
            margin-top:18px;
            padding:16px 18px;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:12px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
        }

        .summary-title{
            color:#334155;
            font-weight:700;
        }

        .summary-value{
            color:#0f172a;
            font-size:22px;
            font-weight:800;
        }

        .text-danger{
            color:#dc2626;
            font-size:12px;
            margin-top:4px;
            display:block;
        }

        @media (max-width: 992px){
            table{
                display:block;
                overflow-x:auto;
                white-space:nowrap;
            }
        }

        @media (max-width: 768px){
            .form-grid{
                grid-template-columns:1fr;
            }

            .actions{
                flex-direction:column;
                align-items:stretch;
            }

            .left-actions,
            .right-actions{
                width:100%;
            }

            .product-input{
                min-width:220px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="page">
        <a class="top-link" href="manage-purchase-orders.php">← Back to Purchase Orders</a>
        <h1>Edit Purchase Order</h1>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="purchaseOrderForm">
            <div class="form-grid">
                <div class="form-group">
                    <label>Purchase Order Code</label>
                    <input
                        type="text"
                        name="purchase_code"
                        class="form-input"
                        value="<?php echo htmlspecialchars($_POST['purchase_code'] ?? $order['purchase_code']); ?>"
                        placeholder="Example: PN001"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Purchase Date</label>
                    <input
                        type="date"
                        name="purchase_date"
                        class="form-input"
                        value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? $order['purchase_date']); ?>"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label>Supplier Name</label>
                    <input
                        type="text"
                        name="supplier_name"
                        class="form-input"
                        list="supplier-list"
                        placeholder="Enter supplier name"
                        value="<?php echo htmlspecialchars($_POST['supplier_name'] ?? ($order['supplier_name'] ?? '')); ?>"
                        required
                    >
                    <div class="hint">If the supplier does not exist yet, the system will automatically create a new supplier record.</div>
                </div>

                <div class="form-group full">
                    <label>Note</label>
                    <textarea
                        name="note"
                        class="form-input"
                        placeholder="Enter note if needed..."
                    ><?php echo htmlspecialchars($_POST['note'] ?? $order['note']); ?></textarea>
                </div>
            </div>

            <div class="section-title">Imported Product List</div>
            <div class="section-desc">
                This screen edits the current draft batch information only. Previous Import Price is taken from the latest completed import. Import Count shows the next expected import number based on completed purchase orders only. Product stock and average import price will be updated only when the purchase order is completed.
            </div>

            <table id="productTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Stock</th>
                        <th>Previous Import Price (VND)</th>
                        <th>Import Count</th>
                        <th>Import Quantity</th>
                        <th>New Import Price (This Order) (VND)</th>
                        <th>Line Total (VND)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                                $displayImportCount = ((int)$item['import_sequence']) + 1;
                                $hasPreviousImportPrice = isset($item['previous_import_price']) && $item['previous_import_price'] !== null && $item['previous_import_price'] !== '';
                            ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="product_id[]" class="product-id-hidden" value="<?php echo (int)$item['product_id']; ?>">

                                    <div class="input-wrap">
                                        <input
                                            type="text"
                                            name="product_name[]"
                                            class="form-input product-input product-name-input"
                                            list="product-list"
                                            placeholder="Search product..."
                                            value="<?php echo htmlspecialchars($item['car_name']); ?>"
                                            required
                                        >
                                        <span class="input-icon">▼</span>
                                    </div>
                                    <span class="first-import-note">
                                        <?php if (!$hasPreviousImportPrice): ?>
                                            This is the first completed import for this product.
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-danger row-error"></span>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-input small-input readonly-input current-stock-display"
                                        value="<?php echo (int)$item['remain_quantity']; ?>"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-input small-input readonly-input previous-import-price-display"
                                        value="<?php echo $hasPreviousImportPrice ? number_format((float)$item['previous_import_price'], 0, ',', '.') : ''; ?>"
                                        readonly
                                        data-raw="<?php echo $hasPreviousImportPrice ? (float)$item['previous_import_price'] : ''; ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-input small-input readonly-input import-count-display"
                                        value="<?php echo $displayImportCount; ?>"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="quantity[]"
                                        class="form-input small-input quantity-input"
                                        min="1"
                                        placeholder="Quantity"
                                        value="<?php echo (int)$item['quantity']; ?>"
                                        required
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="new_import_price[]"
                                        class="form-input small-input new-import-price-input money-input"
                                        inputmode="numeric"
                                        placeholder="Price"
                                        value="<?php echo number_format((float)$item['import_price'], 0, ',', '.'); ?>"
                                        required
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="form-input small-input readonly-input line-total-display"
                                        value="<?php echo number_format((float)$item['quantity'] * (float)$item['import_price'], 0, ',', '.'); ?>"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>
                                <input type="hidden" name="product_id[]" class="product-id-hidden">

                                <div class="input-wrap">
                                    <input
                                        type="text"
                                        name="product_name[]"
                                        class="form-input product-input product-name-input"
                                        list="product-list"
                                        placeholder="Search product..."
                                        required
                                    >
                                    <span class="input-icon">▼</span>
                                </div>
                                <span class="first-import-note"></span>
                                <span class="text-danger row-error"></span>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-input small-input readonly-input current-stock-display"
                                    value="0"
                                    readonly
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-input small-input readonly-input previous-import-price-display"
                                    value=""
                                    readonly
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-input small-input readonly-input import-count-display"
                                    value="1"
                                    readonly
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    name="quantity[]"
                                    class="form-input small-input quantity-input"
                                    min="1"
                                    placeholder="Quantity"
                                    required
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="new_import_price[]"
                                    class="form-input small-input new-import-price-input money-input"
                                    inputmode="numeric"
                                    placeholder="Price"
                                    required
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="form-input small-input readonly-input line-total-display"
                                    value=""
                                    readonly
                                >
                            </td>

                            <td>
                                <button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <datalist id="product-list">
                <?php foreach ($products as $product): ?>
                    <option
                        value="<?php echo htmlspecialchars($product['car_name']); ?>"
                        data-id="<?php echo (int)$product['product_id']; ?>"
                        data-stock="<?php echo (int)$product['remain_quantity']; ?>"
                        data-prev-price="<?php echo isset($product['previous_import_price']) && $product['previous_import_price'] !== null ? (float)$product['previous_import_price'] : ''; ?>"
                        data-count="<?php echo (int)$product['import_count']; ?>"
                    ></option>
                <?php endforeach; ?>
            </datalist>

            <datalist id="supplier-list">
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="summary-box">
                <div class="summary-title">Grand Total</div>
                <div class="summary-value" id="grandTotalText">0</div>
            </div>

            <div class="actions">
                <div class="left-actions">
                    <button type="button" class="btn btn-secondary" onclick="addRow()">+ Add Product</button>
                </div>
                <div class="right-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function formatMoney(value) {
            const number = Math.round(Number(value) || 0);
            return number.toLocaleString('vi-VN');
        }

        function formatMoneyInput(value) {
            value = value.replace(/[^\d]/g, '');
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function parseMoneyInput(value) {
            return value.replace(/\./g, '');
        }

        function bindMoneyInput(input) {
            input.addEventListener('input', function () {
                const raw = parseMoneyInput(this.value);
                this.value = formatMoneyInput(raw);
                updateRowCalculations(this.closest('tr'));
            });
        }

        function findProductOptionByName(name) {
            const options = document.querySelectorAll('#product-list option');
            for (const option of options) {
                if (option.value.trim() === name.trim()) {
                    return option;
                }
            }
            return null;
        }

        function clearRowProductData(row) {
            row.querySelector('.product-id-hidden').value = '';
            row.querySelector('.current-stock-display').value = '0';
            row.querySelector('.previous-import-price-display').value = '';
            row.querySelector('.previous-import-price-display').dataset.raw = '';
            row.querySelector('.import-count-display').value = '1';
            row.querySelector('.line-total-display').value = '';
            row.querySelector('.first-import-note').textContent = '';
            row.querySelector('.row-error').textContent = '';
        }

        function updateRowCalculations(row) {
            const quantity = parseInt(row.querySelector('.quantity-input').value || '0', 10);
            const rawValue = row.querySelector('.new-import-price-input').value || '0';
            const newImportPrice = parseFloat(rawValue.replace(/\./g, '')) || 0;

            let lineTotal = 0;

            if (quantity > 0 && newImportPrice > 0) {
                lineTotal = quantity * newImportPrice;
            }

            row.querySelector('.line-total-display').value = lineTotal > 0 ? formatMoney(lineTotal) : '';
            updateGrandTotal();
        }

        function updateGrandTotal() {
            let grandTotal = 0;

            document.querySelectorAll('#productTable tbody tr').forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value || '0', 10);
                const rawValue = row.querySelector('.new-import-price-input').value || '0';
                const newImportPrice = parseFloat(rawValue.replace(/\./g, '')) || 0;

                if (quantity > 0 && newImportPrice > 0) {
                    grandTotal += quantity * newImportPrice;
                }
            });

            document.getElementById('grandTotalText').textContent = formatMoney(grandTotal);
        }

        function checkDuplicateProduct(row) {
            const currentId = row.querySelector('.product-id-hidden').value;
            const errorEl = row.querySelector('.row-error');
            errorEl.textContent = '';

            if (!currentId) {
                return false;
            }

            let duplicateCount = 0;
            document.querySelectorAll('.product-id-hidden').forEach(input => {
                if (input.value === currentId) {
                    duplicateCount++;
                }
            });

            if (duplicateCount > 1) {
                errorEl.textContent = 'This product is already selected in another row.';
                return true;
            }

            return false;
        }

        function syncProductSelection(input) {
            const row = input.closest('tr');
            const hiddenId = row.querySelector('.product-id-hidden');
            const currentStockDisplay = row.querySelector('.current-stock-display');
            const previousImportPriceDisplay = row.querySelector('.previous-import-price-display');
            const importCountDisplay = row.querySelector('.import-count-display');
            const firstImportNote = row.querySelector('.first-import-note');
            const rowError = row.querySelector('.row-error');

            rowError.textContent = '';
            firstImportNote.textContent = '';

            const option = findProductOptionByName(input.value);

            if (!option) {
                clearRowProductData(row);
                updateGrandTotal();
                return;
            }

            const productId = option.getAttribute('data-id') || '';
            const stock = parseInt(option.getAttribute('data-stock') || '0', 10);
            const prevPriceAttr = option.getAttribute('data-prev-price');
            const count = parseInt(option.getAttribute('data-count') || '1', 10);

            hiddenId.value = productId;
            currentStockDisplay.value = stock;
            importCountDisplay.value = count;

            if (prevPriceAttr === null || prevPriceAttr === '') {
                previousImportPriceDisplay.value = '';
                previousImportPriceDisplay.dataset.raw = '';
                firstImportNote.textContent = 'This is the first completed import for this product.';
            } else {
                const prevPrice = parseFloat(prevPriceAttr || '0');
                previousImportPriceDisplay.dataset.raw = prevPrice;
                previousImportPriceDisplay.value = formatMoney(prevPrice);
            }

            if (checkDuplicateProduct(row)) {
                hiddenId.value = '';
            }

            updateRowCalculations(row);
        }

        function bindRowEvents(row) {
            const productInput = row.querySelector('.product-name-input');
            const quantityInput = row.querySelector('.quantity-input');
            const newImportPriceInput = row.querySelector('.new-import-price-input');

            productInput.addEventListener('input', function () {
                syncProductSelection(this);
            });

            productInput.addEventListener('change', function () {
                syncProductSelection(this);
            });

            quantityInput.addEventListener('input', function () {
                updateRowCalculations(row);
            });

            bindMoneyInput(newImportPriceInput);
        }

        function addRow() {
            const tbody = document.querySelector('#productTable tbody');
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>
                    <input type="hidden" name="product_id[]" class="product-id-hidden">

                    <div class="input-wrap">
                        <input
                            type="text"
                            name="product_name[]"
                            class="form-input product-input product-name-input"
                            list="product-list"
                            placeholder="Search product..."
                            required
                        >
                        <span class="input-icon">▼</span>
                    </div>
                    <span class="first-import-note"></span>
                    <span class="text-danger row-error"></span>
                </td>

                <td>
                    <input
                        type="text"
                        class="form-input small-input readonly-input current-stock-display"
                        value="0"
                        readonly
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-input small-input readonly-input previous-import-price-display"
                        value=""
                        readonly
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-input small-input readonly-input import-count-display"
                        value="1"
                        readonly
                    >
                </td>

                <td>
                    <input
                        type="number"
                        name="quantity[]"
                        class="form-input small-input quantity-input"
                        min="1"
                        placeholder="Quantity"
                        required
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="new_import_price[]"
                        class="form-input small-input new-import-price-input money-input"
                        inputmode="numeric"
                        placeholder="Price"
                        required
                    >
                </td>

                <td>
                    <input
                        type="text"
                        class="form-input small-input readonly-input line-total-display"
                        value=""
                        readonly
                    >
                </td>

                <td>
                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">Remove</button>
                </td>
            `;

            tbody.appendChild(tr);
            bindRowEvents(tr);
        }

        function removeRow(button) {
            const tbody = document.querySelector('#productTable tbody');
            if (tbody.rows.length === 1) {
                alert('A purchase order must contain at least one product.');
                return;
            }

            button.closest('tr').remove();
            updateGrandTotal();
        }

        document.querySelectorAll('#productTable tbody tr').forEach(row => bindRowEvents(row));
        updateGrandTotal();

        document.getElementById('purchaseOrderForm').addEventListener('submit', function (e) {
            const rows = document.querySelectorAll('#productTable tbody tr');
            let hasValidRow = false;

            for (const row of rows) {
                const productName = row.querySelector('.product-name-input').value.trim();
                const productId = row.querySelector('.product-id-hidden').value.trim();
                const quantity = parseInt(row.querySelector('.quantity-input').value || '0', 10);
                const rawValue = row.querySelector('.new-import-price-input').value || '0';
                const newImportPrice = parseFloat(rawValue.replace(/\./g, '')) || 0;
                const rowError = row.querySelector('.row-error');

                rowError.textContent = '';

                const rowLooksEmpty = productName === '' && quantity === 0 && newImportPrice === 0;
                if (rowLooksEmpty) {
                    continue;
                }

                hasValidRow = true;

                if (!productId) {
                    rowError.textContent = 'Please select a valid product from the list.';
                    e.preventDefault();
                    return;
                }

                if (checkDuplicateProduct(row)) {
                    e.preventDefault();
                    return;
                }

                if (quantity <= 0) {
                    rowError.textContent = 'Quantity must be greater than 0.';
                    e.preventDefault();
                    return;
                }

                if (newImportPrice <= 0) {
                    rowError.textContent = 'New import price (this order) must be greater than 0.';
                    e.preventDefault();
                    return;
                }
            }

            if (!hasValidRow) {
                alert('Please add at least one valid product.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>