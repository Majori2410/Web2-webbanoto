<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// final commit

include '../User/connect.php';

$message = "";

/* Lay danh sach nha cung cap */
$suppliers = mysqli_query($connect, "SELECT * FROM suppliers ORDER BY supplier_name ASC");

/* Lay danh sach san pham */
$products = mysqli_query($connect, "SELECT product_id, car_name FROM products ORDER BY car_name ASC");

/* Xu ly tao phieu nhap */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_code = trim($_POST['purchase_code']);
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $purchase_date = $_POST['purchase_date'];
    $note = trim($_POST['note']);

    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $import_prices = $_POST['import_price'] ?? [];
    $profit_percents = $_POST['profit_percent'] ?? [];

    if ($purchase_code === '' || $purchase_date === '') {
        $message = "Vui lòng nhập mã phiếu và ngày nhập.";
    } elseif (count($product_ids) === 0) {
        $message = "Vui lòng thêm ít nhất 1 sản phẩm.";
    } else {
        mysqli_begin_transaction($connect);

        try {
            $total_amount = 0;

            $stmt = $connect->prepare("
                INSERT INTO purchase_orders (supplier_id, created_by, purchase_code, purchase_date, status, note, total_amount)
                VALUES (?, NULL, ?, ?, 'draft', ?, 0)
            ");

            $stmt->bind_param("isss", $supplier_id, $purchase_code, $purchase_date, $note);
            $stmt->execute();

            $purchase_id = mysqli_insert_id($connect);

            $item_stmt = $connect->prepare("
                INSERT INTO purchase_order_items (purchase_id, product_id, quantity, import_price, profit_percent, selling_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            for ($i = 0; $i < count($product_ids); $i++) {
                $product_id = (int)$product_ids[$i];
                $quantity = (int)$quantities[$i];
                $import_price = (float)$import_prices[$i];
                $profit_percent = (float)$profit_percents[$i];

                if ($product_id <= 0 || $quantity <= 0 || $import_price <= 0) {
                    continue;
                }

                $selling_price = $import_price + ($import_price * $profit_percent / 100);
                $line_total = $quantity * $import_price;
                $total_amount += $line_total;

                $item_stmt->bind_param(
                    "iiiddd",
                    $purchase_id,
                    $product_id,
                    $quantity,
                    $import_price,
                    $profit_percent,
                    $selling_price
                );
                $item_stmt->execute();

                mysqli_query($connect, "
                    UPDATE products 
                    SET default_import_price = $import_price,
                        profit_percent = $profit_percent
                    WHERE product_id = $product_id
                ");
            }

            mysqli_query($connect, "
                UPDATE purchase_orders
                SET total_amount = $total_amount
                WHERE purchase_id = $purchase_id
            ");

            mysqli_commit($connect);
            header("Location: manage-purchase-orders.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $message = "Lỗi khi tạo phiếu nhập: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm phiếu nhập</title>
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
        .form-grid{
            display:grid;
            grid-template-columns: repeat(2, 1fr);
            gap:16px;
            margin-bottom:20px;
        }
        .form-group{
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        .form-group label{
            font-weight:bold;
            color:#2c3e50;
        }
        .form-group input,
        .form-group select,
        .form-group textarea{
            padding:12px;
            border:1px solid #ccc;
            border-radius:8px;
            font-size:14px;
        }
        .form-group textarea{
            min-height:100px;
            resize:vertical;
        }
        .full{
            grid-column:1 / -1;
        }
        .items-box{
            margin-top:20px;
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
            vertical-align: top;
        }
        th{
            background:#2c3e50;
            color:white;
        }
        .btn{
            padding:10px 16px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:bold;
        }
        .btn-primary{
            background:#1abc9c;
            color:white;
        }
        .btn-primary:hover{
            background:#16a085;
        }
        .btn-danger{
            background:#e74c3c;
            color:white;
        }
        .btn-danger:hover{
            background:#c0392b;
        }
        .btn-secondary{
            background:#ecf0f1;
            color:#2c3e50;
        }
        .message{
            margin-bottom:15px;
            padding:12px 16px;
            border-radius:8px;
            background:#fdecea;
            color:#b42318;
        }
        .actions{
            margin-top:20px;
            display:flex;
            gap:12px;
            flex-wrap:wrap;
        }
        .product-search{
            width:100%;
            box-sizing:border-box;
            padding:8px;
            margin-bottom:6px;
            border:1px solid #ccc;
            border-radius:6px;
            font-size:14px;
        }
        .product-select{
            width:100%;
            box-sizing:border-box;
            padding:8px;
            border:1px solid #ccc;
            border-radius:6px;
            font-size:14px;
        }
        @media (max-width: 768px){
            .form-grid{
                grid-template-columns: 1fr;
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
    <div class="page">
        <a class="top-link" href="manage-purchase-orders.php">← Quay lại danh sách phiếu nhập</a>
        <h1>Tạo phiếu nhập</h1>

        <?php if ($message !== ""): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Mã phiếu nhập</label>
                    <input type="text" name="purchase_code" placeholder="VD: PN001" required>
                </div>

                <div class="form-group">
                    <label>Ngày nhập</label>
                    <input type="date" name="purchase_date" required>
                </div>

                <div class="form-group">
                    <label>Nhà cung cấp</label>
                    <select name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php while ($supplier = mysqli_fetch_assoc($suppliers)): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Ghi chú</label>
                    <textarea name="note" placeholder="Nhập ghi chú nếu có..."></textarea>
                </div>
            </div>

            <div class="items-box">
                <h3>Danh sách sản phẩm nhập</h3>
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Giá nhập</th>
                            <th>% lợi nhuận</th>
                            <th>Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input 
                                    type="text" 
                                    placeholder="Tìm sản phẩm..." 
                                    onkeyup="filterProducts(this)" 
                                    class="product-search"
                                >

                                <select name="product_id[]" class="product-select" required>
                                    <option value="">-- Chọn sản phẩm --</option>
                                    <?php
                                    mysqli_data_seek($products, 0);
                                    while ($product = mysqli_fetch_assoc($products)):
                                    ?>
                                        <option value="<?php echo $product['product_id']; ?>">
                                            <?php echo htmlspecialchars($product['car_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td><input type="number" name="quantity[]" min="1" required></td>
                            <td><input type="number" step="0.01" name="import_price[]" min="0" required></td>
                            <td><input type="number" step="0.01" name="profit_percent[]" min="0" value="10"></td>
                            <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">Xóa</button></td>
                        </tr>
                    </tbody>
                </table>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="addRow()">+ Thêm sản phẩm</button>
                    <button type="submit" class="btn btn-primary">Lưu phiếu nhập</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const productOptions = `
            <input 
                type="text" 
                placeholder="Tìm sản phẩm..." 
                onkeyup="filterProducts(this)" 
                class="product-search"
            >

            <select name="product_id[]" class="product-select" required>
                <option value="">-- Chọn sản phẩm --</option>
                <?php
                mysqli_data_seek($products, 0);
                while ($product = mysqli_fetch_assoc($products)):
                ?>
                <option value="<?php echo $product['product_id']; ?>">
                    <?php echo htmlspecialchars($product['car_name']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        `;

        function addRow() {
            const tableBody = document.querySelector('#itemsTable tbody');
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    ${productOptions}
                </td>
                <td><input type="number" name="quantity[]" min="1" required></td>
                <td><input type="number" step="0.01" name="import_price[]" min="0" required></td>
                <td><input type="number" step="0.01" name="profit_percent[]" min="0" value="10"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeRow(this)">Xóa</button></td>
            `;

            tableBody.appendChild(row);
        }

        function removeRow(button) {
            const tableBody = document.querySelector('#itemsTable tbody');
            if (tableBody.rows.length === 1) {
                alert('Phiếu nhập phải có ít nhất 1 sản phẩm.');
                return;
            }
            button.closest('tr').remove();
        }

        function filterProducts(input) {
            const filter = input.value.toLowerCase();
            const select = input.nextElementSibling;

            if (!select) return;

            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                const text = option.text.toLowerCase();

                option.style.display = text.includes(filter) ? "" : "none";
            }

            if (select.selectedIndex > 0) {
                const selectedText = select.options[select.selectedIndex].text.toLowerCase();
                if (!selectedText.includes(filter)) {
                    select.selectedIndex = 0;
                }
            }
        }
    </script>
</body>
</html>