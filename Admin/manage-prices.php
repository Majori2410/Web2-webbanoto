<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT
        product_id,
        car_name,
        COALESCE(remain_quantity, 0) AS remain_quantity,
        COALESCE(average_import_price, 0) AS average_import_price,
        COALESCE(profit_percent, 0) AS profit_percent,
        COALESCE(price, 0) AS price
    FROM products
";

$params = [];
$types = "";

if ($keyword !== '') {
    $sql .= " WHERE car_name LIKE ?";
    $params[] = '%' . $keyword . '%';
    $types .= "s";
}

$sql .= " ORDER BY car_name ASC";

$stmt = $connect->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($connect));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$productResult = $stmt->get_result();

if (!$productResult) {
    die("Query error: " . mysqli_error($connect));
}

$resultCount = mysqli_num_rows($productResult);
?>

<style>
    body{
        margin:0;
        font-family: Arial, sans-serif;
        background:#f4f6f9;
    }

    .admin-section {
        margin: 20px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .filter-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .filter-container h2 {
        color: #2c3e50;
        font-size: 1.5rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: minmax(260px, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        color: #34495e;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-group label i {
        color: #1abc9c;
        width: 16px;
    }

    .filter-group input {
        padding: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .filter-group input:focus {
        border-color: #1abc9c;
        box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
        outline: none;
    }

    .filter-buttons {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .filter-btn,
    .reset-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .filter-btn {
        background: linear-gradient(135deg, #1abc9c, #16a085);
        color: white;
        cursor: pointer;
    }

    .reset-btn {
        background: #f8f9fa;
        color: #666;
        border: 1px solid #ddd;
        text-decoration: none;
    }

    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
    }

    .reset-btn:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .section-desc{
        margin-bottom:16px;
        color:#555;
        line-height:1.6;
    }

    .formula-box{
        background:#f8fffd;
        border:1px solid #cfeee7;
        border-left:5px solid #1abc9c;
        padding:16px 18px;
        border-radius:8px;
        margin-bottom:18px;
    }

    .formula-title{
        margin:0 0 8px 0;
        color:#2c3e50;
        font-size:18px;
        font-weight:700;
        display:flex;
        align-items:center;
        gap:10px;
    }

    .formula-title i{
        color:#1abc9c;
    }

    .formula-box p{
        margin:6px 0;
        color:#374151;
        line-height:1.6;
    }

    .formula-code{
        font-weight:700;
        color:#0f766e;
    }

    .result-info{
        margin: 12px 0 16px;
        color:#555;
        font-size:15px;
    }

    .table-scroll{
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    }

    .admin-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        white-space: nowrap;
    }

    .admin-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
    }

    .admin-table th i {
        margin-right: 8px;
        color: #1abc9c;
        width: 16px;
        text-align: center;
    }

    .stock-positive{
        color:#166534;
        font-weight:bold;
    }

    .stock-zero{
        color:#dc2626;
        font-weight:bold;
    }

    .money-positive{
        color:#008000;
        font-weight:bold;
    }

    .empty-value{
        color:#9ca3af;
        font-style:italic;
    }

    .profit-input{
        width: 120px;
        padding: 8px 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: #f8f9fa;
        transition: all 0.2s ease;
    }

    .profit-input:focus{
        border-color:#1abc9c;
        box-shadow:0 0 0 3px rgba(26, 188, 156, 0.12);
        outline:none;
        background:#fff;
    }

    .profit-input.saving{
        border-color:#f59e0b;
        background:#fff8eb;
    }

    .profit-input.success{
        border-color:#10b981;
        background:#ecfdf5;
    }

    .profit-input.error{
        border-color:#ef4444;
        background:#fef2f2;
    }

    .saving-text{
        display:inline-block;
        margin-left:8px;
        font-size:12px;
        color:#f59e0b;
        font-weight:600;
    }

    .selling-price-cell{
        font-weight:bold;
        color:#008000;
    }

    .toast{
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 280px;
        max-width: 360px;
        padding: 14px 16px;
        border-radius: 10px;
        color: white;
        box-shadow: 0 10px 24px rgba(0,0,0,0.18);
        z-index: 9999;
        display: none;
        font-weight: 600;
    }

    .toast.success{
        background: linear-gradient(135deg, #1abc9c, #16a085);
    }

    .toast.error{
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .inline-note{
        font-size:12px;
        color:#6b7280;
        margin-top:4px;
        display:block;
        white-space:normal;
        line-height:1.4;
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .filter-buttons {
            flex-direction: column;
        }

        .filter-btn,
        .reset-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div id="toast" class="toast"></div>

<main>
    <div class="admin-section filter-container">
        <h2><i class="fas fa-search-dollar"></i> Search Product Pricing</h2>
        <form method="GET" action="manage-prices.php">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="keyword"><i class="fas fa-car"></i> Product Name</label>
                    <input
                        type="text"
                        id="keyword"
                        name="keyword"
                        placeholder="Search by product name..."
                        value="<?php echo htmlspecialchars($keyword); ?>"
                    >
                </div>
            </div>

            <div class="filter-buttons">
                <button type="submit" class="filter-btn">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="manage-prices.php" class="reset-btn">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="admin-section">
        <h2><i class="fas fa-tags"></i>&nbsp;Manage Selling Prices</h2>
        <p class="section-desc">
            View current stock, average import price, profit percent, and current selling price of each product.
            Profit percent is editable directly. Selling price is recalculated automatically whenever profit percent or average import price changes.
        </p>

        <div class="formula-box">
            <div class="formula-title">
                <i class="fas fa-circle-info"></i>
                Pricing Formula
            </div>
            <p>
                <span class="formula-code">Average Import Price</span> is the weighted average import cost after each completed purchase order.
            </p>
            <p class="formula-code">
                Average Import Price =
                (Current Stock × Previous Average Price + Import Quantity × New Import Price)
                /
                (Current Stock + Import Quantity)
            </p>
            <p class="formula-code">
                Selling Price = Average Import Price × (1 + Profit Percent / 100)
            </p>
        </div>

        <div class="result-info">
            <?php if ($keyword !== ''): ?>
                Found <strong><?php echo $resultCount; ?></strong> product(s) for "<strong><?php echo htmlspecialchars($keyword); ?></strong>".
            <?php else: ?>
                Total products: <strong><?php echo $resultCount; ?></strong>.
            <?php endif; ?>
        </div>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-car"></i> Product</th>
                        <th><i class="fas fa-boxes-stacked"></i> Current Stock</th>
                        <th title="Weighted average import cost after completed purchase orders">
                            <i class="fas fa-scale-balanced"></i> Average Import Price (VND)
                        </th>
                        <th><i class="fas fa-percent"></i> Profit Percent</th>
                        <th><i class="fas fa-money-bill-wave"></i> Current Selling Price (VND)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productResult && mysqli_num_rows($productResult) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($productResult)): ?>
                            <?php
                                $productId = (int)$row['product_id'];
                                $stock = (int)$row['remain_quantity'];
                                $averageImportPrice = (float)$row['average_import_price'];
                                $profitPercent = (float)$row['profit_percent'];
                                $sellingPrice = (float)$row['price'];
                            ?>
                            <tr data-product-id="<?php echo $productId; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($row['car_name']); ?></strong>
                                </td>

                                <td>
                                    <?php if ($stock > 0): ?>
                                        <span class="stock-positive"><?php echo $stock; ?></span>
                                    <?php else: ?>
                                        <span class="stock-zero">0</span>
                                    <?php endif; ?>
                                </td>

                                <td class="average-import-price-cell" data-raw="<?php echo htmlspecialchars((string)$averageImportPrice); ?>">
                                    <?php if ($averageImportPrice > 0): ?>
                                        <span class="money-positive">
                                            <?php echo number_format($averageImportPrice, 0, ',', '.'); ?> đ
                                        </span>
                                    <?php else: ?>
                                        <span class="empty-value">No import history yet</span>
                                    <?php endif; ?>
                                    <span class="inline-note">Weighted average cost after completed imports</span>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        class="profit-input"
                                        min="0"
                                        step="0.01"
                                        value="<?php echo htmlspecialchars((string)$profitPercent); ?>"
                                        data-original="<?php echo htmlspecialchars((string)$profitPercent); ?>"
                                    >
                                    <span class="saving-text" style="display:none;">Saving...</span>
                                </td>

                                <td class="selling-price-cell" data-raw="<?php echo htmlspecialchars((string)$sellingPrice); ?>">
                                    <?php if ($sellingPrice > 0): ?>
                                        <?php echo number_format($sellingPrice, 0, ',', '.'); ?> đ
                                    <?php else: ?>
                                        <span class="empty-value">No selling price yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function formatMoney(value) {
        const number = Math.round(Number(value) || 0);
        return number.toLocaleString('vi-VN') + ' đ';
    }

    function calculateSellingPrice(avgImportPrice, profitPercent) {
        const avg = Number(avgImportPrice) || 0;
        const profit = Number(profitPercent) || 0;
        return Math.round(avg * (1 + profit / 100));
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.className = 'toast ' + type;
        toast.textContent = message;
        toast.style.display = 'block';

        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 2500);
    }

    async function saveProfitPercent(row, input) {
        const productId = row.getAttribute('data-product-id');
        const avgImportPriceCell = row.querySelector('.average-import-price-cell');
        const sellingPriceCell = row.querySelector('.selling-price-cell');
        const savingText = row.querySelector('.saving-text');

        const avgImportPrice = parseFloat(avgImportPriceCell.getAttribute('data-raw') || '0') || 0;
        const profitPercent = parseFloat(input.value || '0');

        if (isNaN(profitPercent) || profitPercent < 0) {
            input.classList.remove('saving', 'success');
            input.classList.add('error');
            showToast('Profit percent must be greater than or equal to 0.', 'error');
            input.value = input.getAttribute('data-original') || '0';
            const rollbackPrice = parseFloat(sellingPriceCell.getAttribute('data-raw') || '0') || 0;
            sellingPriceCell.innerHTML = rollbackPrice > 0
                ? formatMoney(rollbackPrice)
                : '<span class="empty-value">No selling price yet</span>';
            return;
        }

        const previewSellingPrice = calculateSellingPrice(avgImportPrice, profitPercent);
        sellingPriceCell.innerHTML = previewSellingPrice > 0
            ? formatMoney(previewSellingPrice)
            : '<span class="empty-value">No selling price yet</span>';

        input.classList.remove('error', 'success');
        input.classList.add('saving');
        savingText.style.display = 'inline-block';

        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('profit_percent', profitPercent);

            const response = await fetch('update-profit-percent.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to update profit percent.');
            }

            input.classList.remove('saving', 'error');
            input.classList.add('success');
            input.value = data.profit_percent;
            input.setAttribute('data-original', data.profit_percent);

            sellingPriceCell.setAttribute('data-raw', data.selling_price_raw);
            sellingPriceCell.innerHTML = data.selling_price_raw > 0
                ? data.selling_price_formatted
                : '<span class="empty-value">No selling price yet</span>';

            showToast('Profit percent updated successfully. Selling price recalculated.', 'success');

            setTimeout(() => {
                input.classList.remove('success');
            }, 1200);

        } catch (error) {
            input.classList.remove('saving', 'success');
            input.classList.add('error');

            const originalProfit = parseFloat(input.getAttribute('data-original') || '0') || 0;
            const rollbackPrice = calculateSellingPrice(avgImportPrice, originalProfit);

            input.value = originalProfit;
            sellingPriceCell.innerHTML = rollbackPrice > 0
                ? formatMoney(rollbackPrice)
                : '<span class="empty-value">No selling price yet</span>';

            showToast(error.message || 'Update failed.', 'error');
        } finally {
            savingText.style.display = 'none';
        }
    }

    document.querySelectorAll('.profit-input').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            const avgImportPriceCell = row.querySelector('.average-import-price-cell');
            const sellingPriceCell = row.querySelector('.selling-price-cell');

            const avgImportPrice = parseFloat(avgImportPriceCell.getAttribute('data-raw') || '0') || 0;
            const profitPercent = parseFloat(this.value || '0');

            if (!isNaN(profitPercent) && profitPercent >= 0) {
                const previewSellingPrice = calculateSellingPrice(avgImportPrice, profitPercent);
                sellingPriceCell.innerHTML = previewSellingPrice > 0
                    ? formatMoney(previewSellingPrice)
                    : '<span class="empty-value">No selling price yet</span>';
            }
        });

        input.addEventListener('blur', function() {
            const row = this.closest('tr');
            const original = parseFloat(this.getAttribute('data-original') || '0') || 0;
            const current = parseFloat(this.value || '0');

            if (current !== original) {
                saveProfitPercent(row, this);
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
</script>

<?php include 'footer.php'; ?>