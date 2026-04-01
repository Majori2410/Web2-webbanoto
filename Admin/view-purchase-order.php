<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// final commit
include '../User/connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Thiếu mã phiếu nhập.");
}

$purchase_id = (int)$_GET['id'];

$order_sql = "
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.purchase_id = $purchase_id
";
$order_result = mysqli_query($connect, $order_sql);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die("Không tìm thấy phiếu nhập.");
}

$items_sql = "
    SELECT poi.*, p.car_name
    FROM purchase_order_items poi
    LEFT JOIN products p ON poi.product_id = p.product_id
    WHERE poi.purchase_id = $purchase_id
    ORDER BY poi.item_id ASC
";
$items_result = mysqli_query($connect, $items_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phiếu nhập</title>
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
        }
        .top-link{
            display:inline-block;
            margin-bottom:18px;
            color:#1abc9c;
            text-decoration:none;
            font-weight:bold;
        }
        .info-grid{
            display:grid;
            grid-template-columns: repeat(2, 1fr);
            gap:16px;
            margin-bottom:20px;
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
        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
        }
        th, td{
            border-bottom:1px solid #ddd;
            padding:12px;
            text-align:left;
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
        .btn:hover{
            opacity:0.92;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>
    <div class="page">
        <a class="top-link" href="manage-purchase-orders.php">← Quay lại danh sách phiếu nhập</a>
        <h1>Chi tiết phiếu nhập</h1>

        <div class="info-grid">
            <div class="info-box">
                <div class="label">Mã phiếu</div>
                <div class="value"><?php echo htmlspecialchars($order['purchase_code']); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Nhà cung cấp</div>
                <div class="value"><?php echo htmlspecialchars($order['supplier_name'] ?? 'Chưa có'); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Ngày nhập</div>
                <div class="value"><?php echo htmlspecialchars($order['purchase_date']); ?></div>
            </div>

            <div class="info-box">
                <div class="label">Trạng thái</div>
                <div class="value">
                    <?php if ($order['status'] === 'completed'): ?>
                        <span class="badge-completed">Completed</span>
                    <?php else: ?>
                        <span class="badge-draft">Draft</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-box">
                <div class="label">Tổng tiền</div>
                <div class="value"><?php echo number_format((float)$order['total_amount'], 0, ',', '.'); ?> đ</div>
            </div>

            <div class="info-box">
                <div class="label">Ghi chú</div>
                <div class="value" style="font-size:15px; font-weight:normal;">
                    <?php echo nl2br(htmlspecialchars($order['note'] ?: 'Không có ghi chú')); ?>
                </div>
            </div>
        </div>

        <h3>Danh sách sản phẩm nhập</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Giá nhập</th>
                <th>Giá bán dự kiến</th>
            </tr>

            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td><?php echo $item['item_id']; ?></td>
                    <td><?php echo htmlspecialchars($item['car_name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format((float)$item['import_price'], 0, ',', '.'); ?> đ</td>
                </td> <td><?php echo number_format((float)$item['selling_price'], 0, ',', '.'); ?> đ</td>
                </tr>
            <?php endwhile; ?>
        </table>

        <div class="actions">
    <?php if ($order['status'] === 'draft'): ?>
        <a href="edit-purchase-order.php?id=<?php echo $order['purchase_id']; ?>" class="btn btn-warning">Sửa phiếu nhập</a>

        <a href="complete-purchase-order.php?id=<?php echo $order['purchase_id']; ?>"
           class="btn btn-primary"
           onclick="return confirm('Bạn có chắc muốn hoàn thành phiếu nhập này không? Sau khi hoàn thành sẽ không sửa được nữa.');">
           Hoàn thành phiếu nhập
        </a>
    <?php endif; ?>
        </div>
    </div>
</body>
</html>