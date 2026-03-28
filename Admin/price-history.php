<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../User/connect.php';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT 
        poi.item_id,
        p.car_name,
        po.purchase_code,
        po.purchase_date,
        s.supplier_name,
        poi.quantity,
        poi.import_price,
        poi.profit_percent,
        poi.selling_price,
        po.status
    FROM purchase_order_items poi
    JOIN products p ON poi.product_id = p.product_id
    JOIN purchase_orders po ON poi.purchase_id = po.purchase_id
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
";

if ($keyword !== '') {
    $sql .= " 
        WHERE p.car_name LIKE ?
        OR po.purchase_code LIKE ?
        OR s.supplier_name LIKE ?
    ";
}

$sql .= " ORDER BY po.purchase_date DESC, poi.item_id DESC";

if ($keyword !== '') {
    $stmt = $connect->prepare($sql);
    $like = "%" . $keyword . "%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = mysqli_query($connect, $sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu giá theo lô nhập</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f4f6f9;
        }
        .page{
            max-width:1300px;
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
        .toolbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-bottom:20px;
        }
        .search-input{
            padding:10px 12px;
            border:1px solid #ccc;
            border-radius:8px;
            min-width:280px;
        }
        .btn{
            padding:10px 16px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:bold;
            background:#1abc9c;
            color:white;
        }
        .btn:hover{
            background:#16a085;
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
        @media (max-width: 768px){
            table{
                display:block;
                overflow-x:auto;
                white-space:nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <a class="top-link" href="index.php">← Quay lại Admin</a>
        <h1>Tra cứu giá theo lô nhập</h1>
        <p>Hiển thị và tra cứu giá vốn, % lợi nhuận, giá bán của sản phẩm theo lô hàng đã nhập.</p>

        <div class="toolbar">
            <form method="GET">
                <input 
                    type="text" 
                    name="keyword" 
                    class="search-input"
                    placeholder="Tìm theo sản phẩm / mã phiếu / nhà cung cấp..."
                    value="<?php echo htmlspecialchars($keyword); ?>"
                >
                <button type="submit" class="btn">Tìm kiếm</button>
            </form>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Mã phiếu</th>
                    <th>Ngày nhập</th>
                    <th>Nhà cung cấp</th>
                    <th>Số lượng</th>
                    <th>Giá vốn</th>
                    <th>% lợi nhuận</th>
                    <th>Giá bán</th>
                    <th>Trạng thái lô</th>
                </tr>

                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['car_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['purchase_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['purchase_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'Chưa có'); ?></td>
                        <td><?php echo (int)$row['quantity']; ?></td>
                        <td><?php echo number_format((float)$row['import_price'], 0, ',', '.'); ?> đ</td>
                        <td><?php echo number_format((float)$row['profit_percent'], 2); ?>%</td>
                        <td><?php echo number_format((float)$row['selling_price'], 0, ',', '.'); ?> đ</td>
                        <td>
                            <?php if ($row['status'] === 'completed'): ?>
                                <span class="badge-completed">Completed</span>
                            <?php else: ?>
                                <span class="badge-draft">Draft</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Không có dữ liệu lô nhập phù hợp.</p>
        <?php endif; ?>
    </div>
</body>
</html>