<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../User/connect.php';

$result = mysqli_query($connect, "
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giá bán</title>
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
        input{
            width:120px;
            padding:8px;
            border:1px solid #ccc;
            border-radius:6px;
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
        .muted{
            color:#6b7280;
            font-size:13px;
        }
        .zero-stock{
            color:#dc2626;
            font-weight:bold;
        }
        .has-stock{
            color:#166534;
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
        <h1>Quản lý giá bán</h1>
        <p>Hiển thị và nhập / sửa thông tin tỉ lệ % lợi nhuận theo sản phẩm.</p>

        <table>
            <tr>
                <th>Sản phẩm</th>
                <th>Tồn kho</th>
                <th>Giá vốn hiện tại</th>
                <th>% lợi nhuận</th>
                <th>Giá bán hiện tại</th>
                <th>Thao tác</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <form method="POST" action="update-price.php">
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($row['car_name']); ?></strong>
                        </td>

                        <td>
                            <?php if ((int)$row['remain_quantity'] > 0): ?>
                                <span class="has-stock"><?php echo (int)$row['remain_quantity']; ?></span>
                            <?php else: ?>
                                <span class="zero-stock">0</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div><?php echo number_format((float)$row['default_import_price'], 0, ',', '.'); ?> đ</div>
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
                            <strong><?php echo number_format((float)$row['price'], 0, ',', '.'); ?> đ</strong>
                        </td>

                        <td>
                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                            <button type="submit" class="btn">Lưu</button>
                        </td>
                    </tr>
                </form>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>