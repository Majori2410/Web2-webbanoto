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

$message = '';
$messageType = '';

if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = 'Purchase order updated successfully.';
    $messageType = 'success';
} elseif (isset($_GET['completed']) && $_GET['completed'] == '1') {
    $message = 'Purchase order completed successfully. Product stock, average import price, and selling price have been updated.';
    $messageType = 'success';
}

/*
    Order info:
    - supplier name
    - calculated total from item lines
*/
$orderStmt = $connect->prepare("
    SELECT
        po.purchase_id,
        po.purchase_code,
        po.purchase_date,
        po.status,
        po.note,
        s.supplier_name,
        COALESCE(SUM(poi.quantity * poi.import_price), 0) AS calculated_total
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchase_order_items poi ON po.purchase_id = poi.purchase_id
    WHERE po.purchase_id = ?
    GROUP BY
        po.purchase_id,
        po.purchase_code,
        po.purchase_date,
        po.status,
        po.note,
        s.supplier_name
    LIMIT 1
");
$orderStmt->bind_param("i", $purchase_id);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    die("Purchase order not found.");
}

/*
    Item list:
    - For draft order: import_sequence stores baseline max completed sequence
      => displayed import count = import_sequence + 1
    - For completed order: import_sequence is actual import sequence
      => displayed import count = import_sequence
*/
$itemsStmt = $connect->prepare("
    SELECT
        poi.item_id,
        poi.product_id,
        poi.import_sequence,
        poi.quantity,
        poi.import_price,
        p.car_name,
        (poi.quantity * poi.import_price) AS line_total
    FROM purchase_order_items poi
    LEFT JOIN products p ON poi.product_id = p.product_id
    WHERE poi.purchase_id = ?
    ORDER BY poi.item_id ASC
");
$itemsStmt->bind_param("i", $purchase_id);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Details</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f4f6f9;
        }
        .page{
            max-width:1200px;
            margin:30px auto;
            background:#fff;
            padding:24px;
            border-radius:14px;
            box-shadow:0 8px 24px rgba(0,0,0,0.08);
        }
        h1{
            margin-top:0;
            color:#2c3e50;
            margin-bottom:18px;
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
        .info-grid{
            display:grid;
            grid-template-columns: repeat(2, 1fr);
            gap:16px;
            margin-bottom:24px;
        }
        .info-box{
            background:#f9fafb;
            border:1px solid #e5e7eb;
            border-radius:10px;
            padding:14px;
        }
        .label{
            font-size:13px;
            color:#6b7280;
            margin-bottom:6px;
        }
        .value{
            font-size:18px;
            font-weight:bold;
            color:#1f2937;
        }
        .value-note{
            font-size:15px;
            font-weight:normal;
            line-height:1.5;
            color:#374151;
        }
        .section-title{
            font-size:20px;
            color:#2c3e50;
            margin:8px 0 12px;
            font-weight:700;
        }
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
        }
        th, td{
            border-bottom:1px solid #ddd;
            padding:12px;
            text-align:left;
            vertical-align:middle;
        }
        th{
            background:#2c3e50;
            color:white;
        }
        .badge-draft{
            background:#fef3c7;
            color:#92400e;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
        }
        .badge-completed{
            background:#dcfce7;
            color:#166534;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
        }
        .actions{
            margin-top:20px;
            display:flex;
            gap:12px;
            flex-wrap:wrap;
        }
        .btn{
            padding:10px 16px;
            border:none;
            border-radius:8px;
            text-decoration:none;
            cursor:pointer;
            font-weight:bold;
            display:inline-block;
        }
        .btn-primary{
            background:#1abc9c;
            color:white;
        }
        .btn-warning{
            background:#f59e0b;
            color:white;
        }
        .btn-secondary{
            background:#e5e7eb;
            color:#334155;
        }
        .btn:hover{
            opacity:0.92;
        }
        .empty-message{
            margin-top:12px;
            color:#6b7280;
            font-style:italic;
        }

        @media (max-width: 768px){
            .info-grid{
                grid-template-columns:1fr;
            }
            table{
                display:block;
                overflow-x:auto;
                white-space:nowrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="page">
        <a class="top-link" href="manage-purchase-orders.php">← Back to Purchase Orders</a>
        <h1>Purchase Order Details</h1>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="info-grid">
            <div class="info-box">
                <div class="label">Purchase Order Code</div>
                <div class="value"><?php echo htmlspecialchars($order['purchase_code']); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Supplier Name</div>
                <div class="value"><?php echo htmlspecialchars($order['supplier_name'] ?? 'N/A'); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Purchase Date</div>
                <div class="value">
                    <?php
                        echo !empty($order['purchase_date'])
                            ? date('d/m/Y', strtotime($order['purchase_date']))
                            : 'N/A';
                    ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">Status</div>
                <div class="value">
                    <?php if ($order['status'] === 'completed'): ?>
                        <span class="badge-completed">Completed</span>
                    <?php else: ?>
                        <span class="badge-draft">Draft</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">Total Amount (VND)</div>
                <div class="value"><?php echo number_format((float)$order['calculated_total'], 0, ',', '.'); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Note</div>
                <div class="value value-note">
                    <?php echo nl2br(htmlspecialchars($order['note'] ?: 'No note available.')); ?>
                </div>
            </div>
        </div>

        <div class="section-title">Imported Product List</div>

        <?php if ($itemsResult && mysqli_num_rows($itemsResult) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Import Count</th>
                    <th>Quantity</th>
                    <th>New Import Price (This Order) (VND)</th>
                    <th>Line Total (VND)</th>
                </tr>

                <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>
                    <?php
                        $displayImportCount = ($order['status'] === 'completed')
                            ? (int)$item['import_sequence']
                            : ((int)$item['import_sequence'] + 1);
                    ?>
                    <tr>
                        <td><?php echo (int)$item['item_id']; ?></td>
                        <td><?php echo htmlspecialchars($item['car_name'] ?? 'Unknown Product'); ?></td>
                        <td><?php echo $displayImportCount; ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo number_format((float)$item['import_price'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format((float)$item['line_total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="empty-message">No imported products found for this purchase order.</p>
        <?php endif; ?>

        <div class="actions">
            <?php if ($order['status'] === 'draft'): ?>
                <a href="edit-purchase-order.php?id=<?php echo $order['purchase_id']; ?>" class="btn btn-warning">
                    Edit Purchase Order
                </a>

                <a
                    href="complete-purchase-order.php?id=<?php echo $order['purchase_id']; ?>"
                    class="btn btn-primary"
                    onclick="return confirm('Are you sure you want to complete this purchase order? After completion, it can no longer be edited and product stock, average import price, and selling price will be updated.');"
                >
                    Complete Purchase Order
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>