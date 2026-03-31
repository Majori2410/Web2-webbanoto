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

/* PHAN 1: QUAN LY GIA BAN THEO SAN PHAM */
$product_result = mysqli_query($connect, "
    SELECT 
        product_id,
        car_name,
        default_import_price,
        profit_percent,
        price,
        remain_quantity
    FROM products
    ORDER BY car_name ASC
");

/* PHAN 2: TRA CUU GIA THEO LO NHAP */
$keyword = trim($_GET['keyword'] ?? '');

$lot_sql = "
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
    $lot_sql .= " 
        WHERE p.car_name LIKE ?
        OR po.purchase_code LIKE ?
        OR s.supplier_name LIKE ?
    ";
}

$lot_sql .= " ORDER BY po.purchase_date DESC, poi.item_id DESC";

if ($keyword !== '') {
    $stmt = $connect->prepare($lot_sql);
    $like = "%" . $keyword . "%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $lot_result = $stmt->get_result();
} else {
    $lot_result = mysqli_query($connect, $lot_sql);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giá bán theo sản phẩm và theo lô nhập</title>
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
        h2{
            margin-top:30px;
            color:#2c3e50;
            font-size:24px;
        }
        .section-desc{
            margin-bottom:16px;
            color:#555;
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
        input[type="number"], input[type="text"]{
            width:120px;
            padding:8px;
            border:1px solid #ccc;
            border-radius:6px;
        }
        .search-input{
            width:280px;
        }
        .btn{
padding:8px 14px;
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
        .zero-stock{
            color:#dc3545;
            font-weight:bold;
        }
        .has-stock{
            color:#166534;
            font-weight:bold;
        }
        .empty-value{
            color:#9ca3af;
            font-style:italic;
        }
        .toolbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            margin-bottom:20px;
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
        <h1>Quản lý giá bán theo sản phẩm và theo lô nhập</h1>

        <h2>1. Theo sản phẩm</h2>
        <p class="section-desc">Hiển thị và nhập / sửa thông tin tỉ lệ % lợi nhuận theo sản phẩm.</p>

        <table>
            <tr>
                <th>Sản phẩm</th>
                <th>Tồn kho</th>
                <th>Giá vốn hiện tại</th>
                <th>% lợi nhuận</th>
                <th>Giá bán hiện tại</th>
                <th>Thao tác</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($product_result)): ?>
                <form method="POST" action="update-price.php">
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['car_name']); ?></strong></td>
                        <td>
                            <?php if ((int)$row['remain_quantity'] > 0): ?>
                                <span class="has-stock"><?php echo (int)$row['remain_quantity']; ?></span>
                            <?php else: ?>
                                <span class="zero-stock">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((float)$row['default_import_price'] > 0): ?>
                                <?php echo number_format((float)$row['default_import_price'], 0, ',', '.'); ?> đ
                            <?php else: ?>
                                <span class="empty-value">Chưa có giá vốn</span>
                            <?php endif; ?>
                        </td>
<td>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="profit_percent"
                                value="<?php echo (float)$row['profit_percent']; ?>"
                                required
                            >
                        </td>
                        <td>
                            <?php if ((float)$row['price'] > 0): ?>
                                <strong><?php echo number_format((float)$row['price'], 0, ',', '.'); ?> đ</strong>
                            <?php else: ?>
                                <span class="empty-value">Chưa có giá bán</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                            <button type="submit" class="btn">Lưu</button>
                        </td>
                    </tr>
                </form>
            <?php endwhile; ?>
        </table>

        <h2>2. Theo lô nhập</h2>
        <p class="section-desc">Hiển thị và tra cứu giá vốn, % lợi nhuận, giá bán của sản phẩm theo lô hàng đã nhập.</p>

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

        <?php if ($lot_result && mysqli_num_rows($lot_result) > 0): ?>
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

                <?php while($row = mysqli_fetch_assoc($lot_result)): ?>
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
