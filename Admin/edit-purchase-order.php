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
    die('ID phiếu nhập không hợp lệ.');
}

/* LẤY DANH SÁCH SẢN PHẨM CHO DATALIST */
$products = [];
$productQuery = mysqli_query($connect, "SELECT product_id, car_name FROM products ORDER BY car_name ASC");
if ($productQuery) {
    while ($row = mysqli_fetch_assoc($productQuery)) {
        $products[] = $row;
    }
}

/* LẤY THÔNG TIN PHIẾU NHẬP */
$orderSql = "
    SELECT po.*, s.supplier_name
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
    die('Không tìm thấy phiếu nhập.');
}

$order = $orderResult->fetch_assoc();

if ($order['status'] !== 'draft') {
    die('Chỉ được sửa phiếu nhập ở trạng thái draft.');
}

/* LẤY CHI TIẾT PHIẾU NHẬP */
$itemSql = "
    SELECT poi.*, p.car_name
    FROM purchase_order_items poi
    JOIN products p ON poi.product_id = p.product_id
    WHERE poi.purchase_id = ?
    ORDER BY poi.item_id ASC
";
$itemStmt = $connect->prepare($itemSql);
$itemStmt->bind_param("i", $purchase_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
if ($itemResult) {
    while ($row = $itemResult->fetch_assoc()) {
        $items[] = $row;
    }
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
        $import_prices = $_POST['import_price'] ?? [];

        if ($purchase_code === '' || $purchase_date === '' || $supplier_name === '') {
            throw new Exception('Vui lòng nhập đầy đủ mã phiếu, ngày nhập và nhà cung cấp.');
        }

        /* KIỂM TRA / THÊM NHÀ CUNG CẤP */
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
                throw new Exception('Không thể thêm nhà cung cấp mới.');
            }
            $supplier_id = $insertSupplier->insert_id;
        }

        /* KIỂM TRA ITEM */
        $total_amount = 0;
        $valid_items = [];

        for ($i = 0; $i < count($product_names); $i++) {
            $product_id = isset($product_ids[$i]) ? (int)$product_ids[$i] : 0;
            $product_name = trim($product_names[$i] ?? '');
            $quantity = isset($quantities[$i]) ? (int)$quantities[$i] : 0;
            $import_price = isset($import_prices[$i]) ? (float)$import_prices[$i] : 0;

            if ($product_name === '' && $quantity === 0 && $import_price == 0) {
                continue;
            }

            if ($product_id <= 0) {
                throw new Exception("Sản phẩm '{$product_name}' không hợp lệ. Vui lòng chọn sản phẩm từ danh sách gợi ý.");
            }

            if ($quantity <= 0) {
                throw new Exception("Số lượng của sản phẩm '{$product_name}' phải lớn hơn 0.");
            }

            if ($import_price <= 0) {
                throw new Exception("Giá nhập của sản phẩm '{$product_name}' phải lớn hơn 0.");
            }

            $line_total = $quantity * $import_price;
            $total_amount += $line_total;

            $valid_items[] = [
                'product_id' => $product_id,
                'product_name' => $product_name,
                'quantity' => $quantity,
                'import_price' => $import_price
            ];
        }

        if (count($valid_items) === 0) {
            throw new Exception('Vui lòng nhập ít nhất 1 sản phẩm.');
        }

        /* UPDATE PHIẾU NHẬP */
        $updateOrder = $connect->prepare("
            UPDATE purchase_orders
            SET purchase_code = ?, supplier_id = ?, purchase_date = ?, note = ?, total_amount = ?
            WHERE purchase_id = ? AND status = 'draft'
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
            throw new Exception('Không thể cập nhật phiếu nhập.');
        }

        /* XÓA CHI TIẾT CŨ */
        $deleteItems = $connect->prepare("DELETE FROM purchase_order_items WHERE purchase_id = ?");
        $deleteItems->bind_param("i", $purchase_id);
        if (!$deleteItems->execute()) {
            throw new Exception('Không thể cập nhật danh sách sản phẩm.');
        }

        /* THÊM LẠI CHI TIẾT MỚI
           % lợi nhuận không quản lý ở trang này
        */
        $insertItem = $connect->prepare("
            INSERT INTO purchase_order_items (
                purchase_id,
                product_id,
                quantity,
                import_price,
                profit_percent,
                selling_price
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($valid_items as $item) {
            $profit_percent = 0;
            $selling_price = $item['import_price'];

            $insertItem->bind_param(
                "iiiddd",
                $purchase_id,
                $item['product_id'],
                $item['quantity'],
                $item['import_price'],
                $profit_percent,
                $selling_price
            );

            if (!$insertItem->execute()) {
                throw new Exception("Không thể lưu sản phẩm '{$item['product_name']}'.");
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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa phiếu nhập</title>
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

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
        }

        th, td{
            padding:12px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            vertical-align:middle;
        }

        th{
            background:#2c3e50;
            color:#fff;
        }

        .input-wrap{
            position:relative;
            width:100%;
        }

        .product-input{
            width:100%;
            min-width:320px;
            padding-right:36px;
        }

        .small-input{
            width:100%;
            min-width:140px;
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
        }

        .hint{
            color:#6b7280;
            font-size:13px;
            margin-top:4px;
        }

        @media (max-width: 768px){
            .form-grid{
                grid-template-columns:1fr;
            }

            table{
                display:block;
                overflow-x:auto;
                white-space:nowrap;
            }

            .product-input{
                min-width:260px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="page">
        <a class="top-link" href="view-purchase-order.php?id=<?php echo $purchase_id; ?>">← Quay lại chi tiết phiếu nhập</a>
        <h1>Sửa phiếu nhập</h1>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Mã phiếu nhập</label>
                    <input
                        type="text"
                        name="purchase_code"
                        class="form-input"
                        value="<?php echo htmlspecialchars($_POST['purchase_code'] ?? $order['purchase_code']); ?>"
                        placeholder="VD: PN001"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Ngày nhập</label>
                    <input
                        type="date"
                        name="purchase_date"
                        class="form-input"
                        value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? $order['purchase_date']); ?>"
                        required
                    >
                </div>

                <div class="form-group full">
                    <label>Nhà cung cấp</label>
                    <input
                        type="text"
                        name="supplier_name"
                        class="form-input"
                        placeholder="Nhập tên nhà cung cấp"
                        value="<?php echo htmlspecialchars($_POST['supplier_name'] ?? ($order['supplier_name'] ?? '')); ?>"
                        required
                    >
                    <div class="hint">Nếu nhà cung cấp chưa có trong hệ thống, hệ thống sẽ tự thêm mới.</div>
                </div>

                <div class="form-group full">
                    <label>Ghi chú</label>
                    <textarea
                        name="note"
                        class="form-input"
                        placeholder="Nhập ghi chú nếu có..."
                    ><?php echo htmlspecialchars($_POST['note'] ?? $order['note']); ?></textarea>
                </div>
            </div>

            <div class="section-title">Danh sách sản phẩm nhập</div>

            <table id="productTable">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Giá nhập</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="product_id[]" class="product-id-hidden" value="<?php echo (int)$item['product_id']; ?>">
                                    <div class="input-wrap">
                                        <input
                                            type="text"
                                            name="product_name[]"
                                            class="form-input product-input product-name-input"
                                            list="product-list"
                                            placeholder="Tìm sản phẩm..."
                                            value="<?php echo htmlspecialchars($item['car_name']); ?>"
                                            required
                                        >
                                        <span class="input-icon">▼</span>
                                    </div>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        name="quantity[]"
                                        class="form-input small-input"
                                        min="1"
                                        placeholder="Số lượng"
                                        value="<?php echo (int)$item['quantity']; ?>"
                                        required
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        name="import_price[]"
                                        class="form-input small-input"
                                        min="0"
                                        step="0.01"
                                        placeholder="Giá nhập"
                                        value="<?php echo (float)$item['import_price']; ?>"
                                        required
                                    >
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">Xóa</button>
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
                                        placeholder="Tìm sản phẩm..."
                                        required
                                    >
                                    <span class="input-icon">▼</span>
                                </div>
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="quantity[]"
                                    class="form-input small-input"
                                    min="1"
                                    placeholder="Số lượng"
                                    required
                                >
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="import_price[]"
                                    class="form-input small-input"
                                    min="0"
                                    step="0.01"
                                    placeholder="Giá nhập"
                                    required
                                >
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger" onclick="removeRow(this)">Xóa</button>
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
                    ></option>
                <?php endforeach; ?>
            </datalist>

            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="addRow()">+ Thêm sản phẩm</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>

    <script>
        function bindProductInputEvents(scope = document) {
            const inputs = scope.querySelectorAll('.product-name-input');

            inputs.forEach(input => {
                input.addEventListener('input', function () {
                    const row = this.closest('tr');
                    const hiddenInput = row.querySelector('.product-id-hidden');
                    const value = this.value.trim();
                    const options = document.querySelectorAll('#product-list option');

                    hiddenInput.value = '';

                    options.forEach(option => {
                        if (option.value === value) {
                            hiddenInput.value = option.getAttribute('data-id');
                        }
                    });
                });

                input.addEventListener('change', function () {
                    const row = this.closest('tr');
                    const hiddenInput = row.querySelector('.product-id-hidden');
                    const value = this.value.trim();
                    const options = document.querySelectorAll('#product-list option');

                    hiddenInput.value = '';

                    options.forEach(option => {
                        if (option.value === value) {
                            hiddenInput.value = option.getAttribute('data-id');
                        }
                    });
                });
            });
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
                            placeholder="Tìm sản phẩm..."
                            required
                        >
                        <span class="input-icon">▼</span>
                    </div>
                </td>
                <td>
                    <input
                        type="number"
                        name="quantity[]"
                        class="form-input small-input"
                        min="1"
                        placeholder="Số lượng"
                        required
                    >
                </td>
                <td>
                    <input
                        type="number"
                        name="import_price[]"
                        class="form-input small-input"
                        min="0"
                        step="0.01"
                        placeholder="Giá nhập"
                        required
                    >
                </td>
                <td>
                    <button type="button" class="btn btn-danger" onclick="removeRow(this)">Xóa</button>
                </td>
            `;

            tbody.appendChild(tr);
            bindProductInputEvents(tr);
        }

        function removeRow(button) {
            const tbody = document.querySelector('#productTable tbody');
            if (tbody.rows.length === 1) {
                alert('Phiếu nhập phải có ít nhất 1 sản phẩm.');
                return;
            }

            button.closest('tr').remove();
        }

        bindProductInputEvents();
    </script>
</body>
</html>