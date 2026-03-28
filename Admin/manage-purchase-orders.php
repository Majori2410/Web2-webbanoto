<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../User/connect.php';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
";

if ($keyword !== '') {
    $sql .= " WHERE po.purchase_code LIKE ?";
}

$sql .= " ORDER BY po.purchase_id DESC";

if ($keyword !== '') {
    $stmt = $connect->prepare($sql);
    $like = "%" . $keyword . "%";
    $stmt->bind_param("s", $like);
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
    <title>Danh sách phiếu nhập</title>
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
            min-width:240px;
        }
        .btn{
            padding:10px 16px;
            border:none;
            border-radius:8px;
            text-decoration:none;
            cursor:pointer;
            font-weight:bold;
        }
        .btn-primary{
            background:#1abc9c;
            color:white;
        }
        .btn-secondary{
            background:#ecf0f1;
            color:#2c3e50;
        }
        table{
            width:100%;
            border-collapse:collapse;
            background:white;
        }
        th, td{
            padding:12px;
            border-bottom:1px solid #ddd;
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
        .top-link{
            display:inline-block;
            margin-bottom:18px;
            color:#1abc9c;
            text-decoration:none;
            font-weight:bold;
        }
    </style>
</head>
<body>
    <div class="page">
        <a class="top-link" href="index.php">← Quay lại Admin</a>
        <h1>Danh sách phiếu nhập</h1>
        <p>Theo dõi phiếu nhập kho, tìm kiếm và tạo phiếu nhập mới.</p>

        <div class="toolbar">
            <form method="GET">
                <input 
                    type="text" 
                    name="keyword" 
                    class="search-input"
                    placeholder="Tìm theo mã phiếu nhập..."
                    value="<?php echo htmlspecialchars($keyword); ?>"
                >
                <button type="submit" class="btn btn-secondary">Search</button>
            </form>

            <a href="add-purchase-order.php" class="btn btn-primary">+ Tạo phiếu nhập</a>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Mã phiếu</th>
                    <th>Nhà cung cấp</th>
                    <th>Ngày nhập</th>
                    <th>Trạng thái</th>
                    <th>Tổng tiền</th>
                    <th>Thao tác</th>
                </tr>

                <tr>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['purchase_id']; ?></td>
                <td><?php echo htmlspecialchars($row['purchase_code']); ?></td>
                <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'Chưa có'); ?></td>
                <td><?php echo htmlspecialchars($row['purchase_date']); ?></td>
                <td>
                    <?php if ($row['status'] === 'draft'): ?>
                        <span class="badge-draft">Nháp</span>
                    <?php else: ?>
                        <span class="badge-completed">Hoàn thành</span>
                    <?php endif; ?>
                </td>
                <td><?php echo number_format((float)$row['total_amount'], 0, ',', '.'); ?> đ</td>
                <td>
                    <a href="view-purchase-order.php?id=<?php echo $row['purchase_id']; ?>" class="btn btn-secondary">
                        Xem chi tiết
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>Chưa có phiếu nhập nào.</p>
<?php endif; ?>
    </div>
</body>
</html>