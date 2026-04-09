<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'header.php';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT 
        po.purchase_id,
        po.purchase_code,
        po.purchase_date,
        po.status,
        s.supplier_name,
        COALESCE(SUM(poi.quantity * poi.import_price), 0) AS calculated_total
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
    LEFT JOIN purchase_order_items poi ON po.purchase_id = poi.purchase_id
";

if ($keyword !== '') {
    $keyword_safe = mysqli_real_escape_string($connect, $keyword);
    $sql .= " WHERE po.purchase_code LIKE '%$keyword_safe%' 
              OR s.supplier_name LIKE '%$keyword_safe%'";
}

$sql .= "
    GROUP BY 
        po.purchase_id, 
        po.purchase_code, 
        po.purchase_date, 
        po.status, 
        s.supplier_name
    ORDER BY po.purchase_id DESC
";

$purchaseResult = mysqli_query($connect, $sql);

if (!$purchaseResult) {
    die("Query error: " . mysqli_error($connect));
}

$resultCount = mysqli_num_rows($purchaseResult);
?>

<!-- ✅ CSS PHẢI ĐỂ Ở ĐÂY (SAU HEADER) -->
<style>
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
.search-form{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
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
    text-decoration:none;
    cursor:pointer;
    font-weight:bold;
    display:inline-block;
}
.btn-primary{
    background:#1abc9c;
    color:white;
}
.btn-secondary{
    background:#ecf0f1;
    color:#2c3e50;
}
.btn-warning{
    background:#f39c12;
    color:white;
}
.result-info{
    margin-bottom:16px;
    color:#555;
    font-size:15px;
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
.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
@media (max-width: 768px){
    table{
        display:block;
        overflow-x:auto;
        white-space:nowrap;
    }
}
</style>

<div class="page">
    <h1>Purchase Orders</h1>
    <p>Track purchase orders, search records, and create a new purchase order.</p>

    <div class="toolbar">
        <form method="GET" class="search-form">
            <input 
                type="text" 
                name="keyword" 
                class="search-input"
                placeholder="Search by order code or supplier name..."
                value="<?php echo htmlspecialchars($keyword); ?>"
            >
            <button type="submit" class="btn btn-secondary">Search</button>
            <a href="manage-purchase-orders.php" class="btn btn-secondary">Reset</a>
        </form>

        <a href="add-purchase-order.php" class="btn btn-primary">+ Create Purchase Order</a>
    </div>

    <div class="result-info">
        <?php if ($keyword !== ''): ?>
            Found <?php echo $resultCount; ?> result(s) for "<strong><?php echo htmlspecialchars($keyword); ?></strong>".
        <?php else: ?>
            Total purchase orders: <strong><?php echo $resultCount; ?></strong>.
        <?php endif; ?>
    </div>

    <?php if ($purchaseResult && mysqli_num_rows($purchaseResult) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Order Code</th>
                <th>Supplier</th>
                <th>Purchase Date</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($purchaseResult)): ?>
                <tr>
                    <td><?php echo $row['purchase_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['purchase_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php 
                            echo !empty($row['purchase_date']) 
                                ? date('d/m/Y', strtotime($row['purchase_date'])) 
                                : 'N/A'; 
                        ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'draft'): ?>
                            <span class="badge-draft">Draft</span>
                        <?php else: ?>
                            <span class="badge-completed">Completed</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format((float)$row['calculated_total'], 0, ',', '.'); ?> VND</td>
                    <td>
                        <div class="actions">
                            <?php if ($row['status'] === 'draft'): ?>
                                <a href="edit-purchase-order.php?id=<?php echo $row['purchase_id']; ?>" class="btn btn-warning">
                                    Edit
                                </a>
                            <?php endif; ?>

                            <a href="view-purchase-order.php?id=<?php echo $row['purchase_id']; ?>" class="btn btn-secondary">
                                View Details
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No purchase orders found.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>