<?php
include 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
    ASSUMPTIONS / RULES
    - Current stock = products.remain_quantity
    - Low stock threshold = products.low_stock_threshold
    - Imported quantity in selected period:
        purchase_orders.status = 'completed'
        + purchase_order_items.quantity
        filtered by purchase_orders.purchase_date
    - Exported quantity in selected period:
        orders.order_status = 'completed'
        + order_details.quantity
        filtered by orders.order_date
*/

/* =========================
   POST ACTIONS
========================= */

/* Keep old manual stock update logic (if still needed elsewhere) */
if (isset($_POST['update_stock']) && isset($_POST['product_id'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $new_stock = (int)($_POST['new_stock'] ?? 0);

    if ($product_id > 0 && $new_stock >= 0) {
        $stmt = mysqli_prepare($connect, "UPDATE products SET remain_quantity = ? WHERE product_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $new_stock, $product_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['notification'] = ['message' => 'Current stock updated successfully.', 'type' => 'success'];
            } else {
                $_SESSION['notification'] = ['message' => 'Failed to update current stock.', 'type' => 'error'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['notification'] = ['message' => 'Failed to prepare stock update query.', 'type' => 'error'];
        }
    } else {
        $_SESSION['notification'] = ['message' => 'Invalid stock update data.', 'type' => 'error'];
    }

    echo "<script>window.location.href='manage-inventory.php';</script>";
    exit();
}

/* Update low stock threshold */
if (isset($_POST['update_threshold']) && isset($_POST['product_id'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $new_threshold = (int)($_POST['low_stock_threshold'] ?? 0);

    if ($product_id > 0 && $new_threshold >= 0) {
        $stmt = mysqli_prepare($connect, "
            UPDATE products
            SET low_stock_threshold = ?
            WHERE product_id = ?
        ");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $new_threshold, $product_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['notification'] = ['message' => 'Low stock threshold updated successfully.', 'type' => 'success'];
            } else {
                $_SESSION['notification'] = ['message' => 'Failed to update low stock threshold.', 'type' => 'error'];
            }

            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['notification'] = ['message' => 'Failed to prepare threshold update query.', 'type' => 'error'];
        }
    } else {
        $_SESSION['notification'] = ['message' => 'Invalid threshold value.', 'type' => 'error'];
    }

    echo "<script>window.location.href='manage-inventory.php';</script>";
    exit();
}

/* =========================
   FILTERS
========================= */
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_filter = trim($_GET['stock_filter'] ?? '');
$sort = trim($_GET['sort'] ?? 'product_id_asc');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

/* Simple date validation */
function isValidDateYmd($date): bool {
    if ($date === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

$hasStartDate = isValidDateYmd($start_date);
$hasEndDate = isValidDateYmd($end_date);

/* =========================
   BUILD IMPORT SUBQUERY
========================= */
$importWhere = ["po.status = 'completed'"];
$importParams = [];
$importTypes = '';

if ($hasStartDate) {
    $importWhere[] = "DATE(po.purchase_date) >= ?";
    $importParams[] = $start_date;
    $importTypes .= 's';
}

if ($hasEndDate) {
    $importWhere[] = "DATE(po.purchase_date) <= ?";
    $importParams[] = $end_date;
    $importTypes .= 's';
}

$importSubquery = "
    SELECT
        poi.product_id,
        SUM(poi.quantity) AS imported_qty
    FROM purchase_order_items poi
    INNER JOIN purchase_orders po
        ON po.purchase_id = poi.purchase_id
    WHERE " . implode(' AND ', $importWhere) . "
    GROUP BY poi.product_id
";

/* =========================
   BUILD EXPORT SUBQUERY
========================= */
$exportWhere = ["o.order_status = 'completed'"];
$exportParams = [];
$exportTypes = '';

if ($hasStartDate) {
    $exportWhere[] = "DATE(o.order_date) >= ?";
    $exportParams[] = $start_date;
    $exportTypes .= 's';
}

if ($hasEndDate) {
    $exportWhere[] = "DATE(o.order_date) <= ?";
    $exportParams[] = $end_date;
    $exportTypes .= 's';
}

$exportSubquery = "
    SELECT
        od.product_id,
        SUM(od.quantity) AS exported_qty
    FROM order_details od
    INNER JOIN orders o
        ON o.order_id = od.order_id
    WHERE " . implode(' AND ', $exportWhere) . "
    GROUP BY od.product_id
";

/* =========================
   MAIN QUERY
========================= */
$where = [];
$params = [];
$types = '';

$query = "
    SELECT
        p.product_id,
        p.car_name,
        p.brand_id,
        COALESCE(ct.type_name, 'N/A') AS brand_name,
        COALESCE(p.price, 0) AS price,
        COALESCE(p.remain_quantity, 0) AS current_stock,
        COALESCE(p.low_stock_threshold, 5) AS low_stock_threshold,
        COALESCE(imp.imported_qty, 0) AS imported_qty,
        COALESCE(exp.exported_qty, 0) AS exported_qty,
        COALESCE(imp.imported_qty, 0) - COALESCE(exp.exported_qty, 0) AS net_movement
    FROM products p
    LEFT JOIN car_types ct
        ON ct.type_id = p.brand_id
    LEFT JOIN (
        {$importSubquery}
    ) imp ON imp.product_id = p.product_id
    LEFT JOIN (
        {$exportSubquery}
    ) exp ON exp.product_id = p.product_id
";

$params = array_merge($params, $importParams, $exportParams);
$types .= $importTypes . $exportTypes;

/* Product filters */
if ($category > 0) {
    $where[] = "p.brand_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($stock_filter === 'low') {
    $where[] = "COALESCE(p.remain_quantity, 0) > 0 AND COALESCE(p.remain_quantity, 0) <= COALESCE(p.low_stock_threshold, 5)";
} elseif ($stock_filter === 'out') {
    $where[] = "COALESCE(p.remain_quantity, 0) = 0";
} elseif ($stock_filter === 'in') {
    $where[] = "COALESCE(p.remain_quantity, 0) > COALESCE(p.low_stock_threshold, 5)";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

/* Sorting */
switch ($sort) {
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY current_stock DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY current_stock ASC";
        break;
    case 'imported_desc':
        $query .= " ORDER BY imported_qty DESC";
        break;
    case 'exported_desc':
        $query .= " ORDER BY exported_qty DESC";
        break;
    case 'net_desc':
        $query .= " ORDER BY net_movement DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.car_name ASC";
        break;
    default:
        $query .= " ORDER BY p.product_id ASC";
        break;
}

/* Execute */
$rows = [];
$stmt = mysqli_prepare($connect, $query);

if (!$stmt) {
    die("Query prepare error: " . mysqli_error($connect));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}

mysqli_stmt_close($stmt);

/* =========================
   SUMMARY CARDS
========================= */
$totalProducts = count($rows);
$totalUnitsInStock = 0;
$lowStockProducts = 0;
$outOfStockProducts = 0;

foreach ($rows as $row) {
    $stock = (int)$row['current_stock'];
    $threshold = (int)$row['low_stock_threshold'];

    $totalUnitsInStock += $stock;

    if ($stock === 0) {
        $outOfStockProducts++;
    } elseif ($stock <= $threshold) {
        $lowStockProducts++;
    }
}

/* =========================
   NOTIFICATION
========================= */
$pendingNotification = null;
if (isset($_SESSION['notification'])) {
    $pendingNotification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f4f6f8;
            margin:0;
            padding:0;
        }

        .page-wrap{
            margin:20px;
        }

        .admin-section{
            margin-bottom:20px;
            padding:20px;
            background:#fff;
            border-radius:12px;
            box-shadow:0 2px 10px rgba(0,0,0,0.08);
        }

        h2{
            color:#2c3e50;
            margin-top:0;
            margin-bottom:8px;
        }

        .section-desc{
            color:#64748b;
            margin-top:0;
            margin-bottom:18px;
            line-height:1.6;
        }

        .filter-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:16px;
        }

        .filter-group{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .filter-group label{
            font-weight:700;
            color:#34495e;
        }

        .filter-group input,
        .filter-group select{
            width:100%;
            padding:10px 12px;
            border-radius:8px;
            border:1px solid #d1d5db;
            box-sizing:border-box;
            outline:none;
            background:#fff;
        }

        .filter-group input:focus,
        .filter-group select:focus{
            border-color:#1abc9c;
            box-shadow:0 0 0 3px rgba(26,188,156,0.12);
        }

        .filter-buttons{
            display:flex;
            gap:10px;
            justify-content:flex-end;
            margin-top:18px;
            flex-wrap:wrap;
        }

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            padding:10px 16px;
            border:none;
            border-radius:8px;
            font-weight:700;
            text-decoration:none;
            cursor:pointer;
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

        .btn-warning{
            background:#f59e0b;
            color:#fff;
            padding:8px 12px;
        }

        .btn-warning:hover{
            background:#d97706;
        }

        .summary-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:16px;
        }

        .summary-card{
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:18px;
            box-shadow:0 2px 8px rgba(0,0,0,0.04);
        }

        .summary-label{
            color:#64748b;
            font-size:14px;
            margin-bottom:10px;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .summary-value{
            color:#1f2937;
            font-size:28px;
            font-weight:800;
        }

        .table-wrap{
            overflow-x:auto;
        }

        table{
            width:100%;
            min-width:1200px;
            border-collapse:collapse;
            margin-top:10px;
        }

        th, td{
            padding:12px;
            border-bottom:1px solid #eee;
            text-align:left;
            vertical-align:middle;
        }

        th{
            background:#f8f9fa;
            color:#2c3e50;
            font-weight:700;
            white-space:nowrap;
        }

        .status-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:6px 12px;
            border-radius:999px;
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }

        .status-good{
            background:#dcfce7;
            color:#166534;
        }

        .status-low{
            background:#fef3c7;
            color:#92400e;
        }

        .status-out{
            background:#fee2e2;
            color:#991b1b;
        }

        .stock-value{
            font-weight:700;
        }

        .low-stock-threshold{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:42px;
            height:32px;
            padding:0 10px;
            border-radius:999px;
            background:#ecfeff;
            color:#0f766e;
            font-weight:700;
        }

        .movement-positive{
            color:#166534;
            font-weight:700;
        }

        .movement-negative{
            color:#991b1b;
            font-weight:700;
        }

        .movement-neutral{
            color:#475569;
            font-weight:700;
        }

        .date-range-note{
            margin-top:12px;
            color:#64748b;
            font-size:13px;
            line-height:1.6;
        }

        .modal{
            display:none;
            position:fixed;
            inset:0;
            z-index:1000;
            align-items:center;
            justify-content:center;
        }

        .modal.show{
            display:flex;
        }

        .modal-overlay{
            position:absolute;
            inset:0;
            background:rgba(0,0,0,0.45);
        }

        .modal-dialog{
            position:relative;
            z-index:1001;
            width:100%;
            max-width:500px;
            margin:20px;
            background:#fff;
            border-radius:14px;
            box-shadow:0 18px 40px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .modal-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:18px 20px;
            border-bottom:1px solid #e5e7eb;
        }

        .modal-title{
            margin:0;
            color:#1f2937;
            font-size:20px;
        }

        .modal-close{
            border:none;
            background:none;
            cursor:pointer;
            font-size:20px;
            color:#64748b;
        }

        .modal-body{
            padding:20px;
        }

        .modal-actions{
            display:flex;
            justify-content:flex-end;
            gap:10px;
            flex-wrap:wrap;
            margin-top:18px;
        }

        .field-note{
            margin-top:8px;
            color:#64748b;
            font-size:13px;
            line-height:1.5;
        }

        @media (max-width: 768px){
            .page-wrap{
                margin:14px;
            }

            .filter-buttons{
                justify-content:stretch;
            }

            .filter-buttons .btn{
                width:100%;
            }

            .modal-dialog{
                margin:20px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <!-- FILTERS -->
        <div class="admin-section">
            <h2><i class="fas fa-boxes-stacked"></i> Inventory Filters & Report Range</h2>
            <p class="section-desc">
                Filter products, select a reporting period, and review inventory movement. Inventory status is based on each product’s low stock threshold.
            </p>

            <form method="GET" action="manage-inventory.php">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="category">Brand</label>
                        <select name="category" id="category">
                            <option value="">All Brands</option>
                            <?php
                            $catQuery = mysqli_query($connect, "SELECT type_id, type_name FROM car_types ORDER BY type_name ASC");
                            while ($cat = mysqli_fetch_assoc($catQuery)) {
                                $val = (int)$cat['type_id'];
                                $label = htmlspecialchars($cat['type_name']);
                                $selected = ($category === $val) ? 'selected' : '';
                                echo "<option value=\"{$val}\" {$selected}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="stock_filter">Inventory Status</label>
                        <select name="stock_filter" id="stock_filter">
                            <option value="">All</option>
                            <option value="in" <?php echo ($stock_filter === 'in') ? 'selected' : ''; ?>>In Stock</option>
                            <option value="low" <?php echo ($stock_filter === 'low') ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out" <?php echo ($stock_filter === 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select name="sort" id="sort">
                            <option value="product_id_asc" <?php echo ($sort === 'product_id_asc') ? 'selected' : ''; ?>>ID Ascending</option>
                            <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Product Name A → Z</option>
                            <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price High → Low</option>
                            <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price Low → High</option>
                            <option value="stock_desc" <?php echo ($sort === 'stock_desc') ? 'selected' : ''; ?>>Current Stock High → Low</option>
                            <option value="stock_asc" <?php echo ($sort === 'stock_asc') ? 'selected' : ''; ?>>Current Stock Low → High</option>
                            <option value="imported_desc" <?php echo ($sort === 'imported_desc') ? 'selected' : ''; ?>>Imported Qty High → Low</option>
                            <option value="exported_desc" <?php echo ($sort === 'exported_desc') ? 'selected' : ''; ?>>Exported Qty High → Low</option>
                            <option value="net_desc" <?php echo ($sort === 'net_desc') ? 'selected' : ''; ?>>Net Movement High → Low</option>
                        </select>
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <a href="manage-inventory.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                        Reset
                    </a>
                </div>

                <div class="date-range-note">
                    <strong>Report rule:</strong>
                    Imported Qty is calculated from completed purchase orders.
                    Exported Qty is calculated from completed customer orders.
                    <?php if ($hasStartDate || $hasEndDate): ?>
                        The current report is filtered by the selected date range.
                    <?php else: ?>
                        No date range selected, so Imported Qty / Exported Qty are shown for all recorded completed transactions.
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- SUMMARY -->
        <div class="admin-section">
            <h2><i class="fas fa-chart-column"></i> Inventory Summary</h2>

            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">
                        <i class="fas fa-cubes"></i>
                        Total Products
                    </div>
                    <div class="summary-value"><?php echo number_format($totalProducts); ?></div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">
                        <i class="fas fa-warehouse"></i>
                        Total Units In Stock
                    </div>
                    <div class="summary-value"><?php echo number_format($totalUnitsInStock); ?></div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">
                        <i class="fas fa-triangle-exclamation"></i>
                        Low Stock Products
                    </div>
                    <div class="summary-value"><?php echo number_format($lowStockProducts); ?></div>
                </div>

                <div class="summary-card">
                    <div class="summary-label">
                        <i class="fas fa-circle-xmark"></i>
                        Out of Stock Products
                    </div>
                    <div class="summary-value"><?php echo number_format($outOfStockProducts); ?></div>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="admin-section">
            <h2><i class="fas fa-warehouse"></i> Manage Inventory</h2>
            <p class="section-desc">
                Review current stock, low stock threshold, and stock movement for each product in the selected period.
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Selling Price (VND)</th>
                            <th>Current Stock</th>
                            <th>Low Stock Threshold</th>
                            <th>Inventory Status</th>
                            <th>Imported Qty</th>
                            <th>Exported Qty</th>
                            <th>Net Movement</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $p): ?>
                                <?php
                                    $product_id = (int)$p['product_id'];
                                    $car_name = htmlspecialchars($p['car_name']);
                                    $brand_name = htmlspecialchars($p['brand_name']);
                                    $price = number_format((float)$p['price'], 0, ',', '.');
                                    $stock = (int)$p['current_stock'];
                                    $threshold = (int)$p['low_stock_threshold'];
                                    $importedQty = (int)$p['imported_qty'];
                                    $exportedQty = (int)$p['exported_qty'];
                                    $netMovement = (int)$p['net_movement'];

                                    if ($stock === 0) {
                                        $statusClass = 'status-out';
                                        $statusText = 'Out of Stock';
                                    } elseif ($stock <= $threshold) {
                                        $statusClass = 'status-low';
                                        $statusText = 'Low Stock';
                                    } else {
                                        $statusClass = 'status-good';
                                        $statusText = 'In Stock';
                                    }

                                    if ($netMovement > 0) {
                                        $netClass = 'movement-positive';
                                    } elseif ($netMovement < 0) {
                                        $netClass = 'movement-negative';
                                    } else {
                                        $netClass = 'movement-neutral';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $product_id; ?></td>
                                    <td><?php echo $car_name; ?></td>
                                    <td><?php echo $brand_name; ?></td>
                                    <td><?php echo $price; ?></td>
                                    <td class="stock-value"><?php echo number_format($stock); ?></td>
                                    <td>
                                        <span class="low-stock-threshold"><?php echo number_format($threshold); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($importedQty); ?></td>
                                    <td><?php echo number_format($exportedQty); ?></td>
                                    <td class="<?php echo $netClass; ?>">
                                        <?php echo number_format($netMovement); ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-warning"
                                            onclick='openThresholdModal(<?php echo $product_id; ?>, <?php echo json_encode($p["car_name"]); ?>, <?php echo $threshold; ?>)'
                                        >
                                            <i class="fas fa-pen"></i>
                                            Set Threshold
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center;color:#64748b;font-style:italic;">
                                    No products found for the selected filters.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- THRESHOLD MODAL -->
    <div id="thresholdModal" class="modal">
        <div class="modal-overlay" onclick="closeThresholdModal()"></div>

        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Update Low Stock Threshold</h3>
                <button type="button" class="modal-close" onclick="closeThresholdModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" action="manage-inventory.php">
                    <input type="hidden" name="product_id" id="threshold_product_id">

                    <div class="filter-group">
                        <label>Product</label>
                        <input type="text" id="threshold_product_name" readonly>
                    </div>

                    <div class="filter-group">
                        <label for="low_stock_threshold">Low Stock Threshold</label>
                        <input
                            type="number"
                            min="0"
                            name="low_stock_threshold"
                            id="low_stock_threshold"
                            required
                        >
                        <div class="field-note">
                            When current stock is less than or equal to this value, the product will be marked as Low Stock.
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeThresholdModal()">
                            Cancel
                        </button>
                        <button type="submit" name="update_threshold" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Threshold
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openThresholdModal(productId, productName, threshold) {
            document.getElementById('threshold_product_id').value = productId;
            document.getElementById('threshold_product_name').value = productName || '';
            document.getElementById('low_stock_threshold').value = threshold || 0;
            document.getElementById('thresholdModal').classList.add('show');
        }

        function closeThresholdModal() {
            document.getElementById('thresholdModal').classList.remove('show');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeThresholdModal();
            }
        });

        <?php if ($pendingNotification): ?>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof showNotification === 'function') {
                showNotification(
                    <?php echo json_encode($pendingNotification['message']); ?>,
                    <?php echo json_encode($pendingNotification['type']); ?>
                );
            } else {
                alert(<?php echo json_encode($pendingNotification['message']); ?>);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

<?php include 'footer.php'; ?>