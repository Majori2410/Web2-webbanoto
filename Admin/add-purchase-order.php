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

/* LAY DANH SACH SAN PHAM CHO DATALIST */
$products = [];
$productQuery = mysqli_query($connect, "SELECT product_id, car_name FROM products ORDER BY car_name ASC");
if ($productQuery) {
    while ($row = mysqli_fetch_assoc($productQuery)) {
        $products[] = $row;
    }
}

/* TU DONG TAO MA PHIEU NHAP GOI Y */
$nextCode = 'PN001';
$codeQuery = mysqli_query($connect, "SELECT purchase_code FROM purchase_orders ORDER BY purchase_id DESC LIMIT 1");
if ($codeQuery && mysqli_num_rows($codeQuery) > 0) {
    $lastRow = mysqli_fetch_assoc($codeQuery);
    if (preg_match('/PN(\d+)/i', $lastRow['purchase_code'], $matches)) {
        $nextNumber = (int)$matches[1] + 1;
        $nextCode = 'PN' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($connect);

    try {
        $purchase_code = trim($_POST['purchase_code'] ?? '');
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $status = 'draft';

        $product_ids = $_POST['product_id'] ?? [];
        $product_names = $_POST['product_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $import_prices = $_POST['import_price'] ?? [];

        if ($purchase_code === '' || $purchase_date === '' || $supplier_name === '') {
            throw new Exception('Vui lòng nhập đầy đủ mã phiếu, ngày nhập và nhà cung cấp.');
        }

        /* KIEM TRA / THEM NHA CUNG CAP */
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

        /* KIEM TRA DANH SACH SAN PHAM */
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

        /* THEM PHIEU NHAP */
        $insertOrder = $connect->prepare("
            INSERT INTO purchase_orders (
                purchase_code,
                supplier_id,
                purchase_date,
                note,
                status,
                total_amount
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $insertOrder->bind_param(
            "sisssd",
            $purchase_code,
            $supplier_id,
            $purchase_date,
            $note,
            $status,
            $total_amount
        );

        if (!$insertOrder->execute()) {
            throw new Exception('Không thể lưu phiếu nhập.');
        }

        $purchase_id = $insertOrder->insert_id;

        /* THEM CHI TIET PHIEU NHAP
           profit_percent de 0, selling_price = import_price
           vi % loi nhuan chi quan ly o trang Quan ly gia ban
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

        $message = 'Tạo phiếu nhập thành công.';
        $messageType = 'success';

        /* RESET FORM */
        $nextCode = 'PN001';
        $generatedNext = mysqli_query($connect, "SELECT purchase_code FROM purchase_orders ORDER BY purchase_id DESC LIMIT 1");
        if ($generatedNext && mysqli_num_rows($generatedNext) > 0) {
            $lastRow = mysqli_fetch_assoc($generatedNext);
            if (preg_match('/PN(\d+)/i', $lastRow['purchase_code'], $matches)) {
                $nextNumber = (int)$matches[1] + 1;
                $nextCode = 'PN' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
            }
        }

        $_POST = [];

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
    <title>Tạo phiếu nhập</title>
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
        <a class="top-link" href="manage-purchase-orders.php">← Quay lại danh sách phiếu nhập</a>
        <h1>Tạo phiếu nhập</h1>

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
                        value="<?php echo htmlspecialchars($_POST['purchase_code'] ?? $nextCode); ?>"
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
                        value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')); ?>"
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
                        value="<?php echo htmlspecialchars($_POST['supplier_name'] ?? ''); ?>"
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
                    ><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
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
                <button type="submit" class="btn btn-primary">Lưu phiếu nhập</button>
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