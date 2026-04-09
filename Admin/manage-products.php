<?php
include 'header.php';
include '../User/connect.php';

function hasCompletedImportHistory($connect, $product_id)
{
    $query = "SELECT COUNT(*) AS cnt
              FROM purchase_order_items poi
              INNER JOIN purchase_orders po ON po.purchase_id = poi.purchase_id
              WHERE poi.product_id = ?
                AND po.status = 'completed'";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return isset($row['cnt']) && (int)$row['cnt'] > 0;
}

function hasOrderHistory($connect, $product_id)
{
    $query = "SELECT COUNT(*) AS cnt
              FROM order_details
              WHERE product_id = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return isset($row['cnt']) && (int)$row['cnt'] > 0;
}

function deleteProductFilesAndRows($connect, $product_id)
{
    $query = "SELECT image_link FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if (!$product) {
        return false;
    }

    if (!empty($product['image_link'])) {
        $image_path = "../User/" . $product['image_link'];
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }

    $images_query = "SELECT image_url FROM product_images WHERE product_id = ?";
    $stmt = mysqli_prepare($connect, $images_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $images_result = mysqli_stmt_get_result($stmt);

    while ($img = mysqli_fetch_assoc($images_result)) {
        if (!empty($img['image_url'])) {
            $img_path = "../User/" . $img['image_url'];
            if (file_exists($img_path)) {
                @unlink($img_path);
            }
        }
    }

    $delete_additional_images_query = "DELETE FROM product_images WHERE product_id = ?";
    $stmt = mysqli_prepare($connect, $delete_additional_images_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $delete_query = "DELETE FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($connect, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);

    return mysqli_stmt_execute($stmt);
}

function getStatusLabel($status)
{
    switch ($status) {
        case 'selling':
            return '<span style="color: green; font-weight: 600;">selling</span>';
        case 'hidden':
            return '<span style="color: gray; font-weight: 600;">hidden</span>';
        case 'discounting':
            return '<span style="color: blue; font-weight: 600;">discounting</span>';
        case 'soldout':
            return '<span style="color: red; font-weight: 600;">sold out</span>';
        default:
            return htmlspecialchars($status);
    }
}

// Xử lý delete / hide sản phẩm
if (isset($_POST['confirm_action']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];

    $has_import_history = hasCompletedImportHistory($connect, $product_id);
    $has_order_history = hasOrderHistory($connect, $product_id);

    if ($has_import_history || $has_order_history) {
        $update_query = "UPDATE products SET status = 'hidden' WHERE product_id = ?";
        $stmt = mysqli_prepare($connect, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);

        if (mysqli_stmt_execute($stmt)) {
            if ($has_import_history) {
                echo "<script>showNotification('Sản phẩm đã từng nhập hàng hoàn tất nên sẽ được ẩn thay vì xóa.', 'info');</script>";
            } else {
                echo "<script>showNotification('Sản phẩm đã tồn tại trong đơn hàng nên sẽ được ẩn thay vì xóa.', 'info');</script>";
            }
        } else {
            echo "<script>showNotification('Lỗi khi ẩn sản phẩm!', 'error');</script>";
        }
    } else {
        if (deleteProductFilesAndRows($connect, $product_id)) {
            echo "<script>showNotification('Xóa sản phẩm thành công!', 'success');</script>";
        } else {
            echo "<script>showNotification('Lỗi khi xóa sản phẩm!', 'error');</script>";
        }
    }

    echo "<script>setTimeout(function() { window.location.href = 'manage-products.php'; }, 1200);</script>";
    exit;
}

// Xử lý thêm sản phẩm
if (isset($_POST['add_product'])) {
    $car_name = mysqli_real_escape_string($connect, $_POST['car_name']);

    $check_query = "SELECT product_id FROM products WHERE car_name = '$car_name'";
    $check_result = mysqli_query($connect, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>
            showNotification('A car with this name already exists!', 'warning');
        </script>";
    } else {
        $brand_id = mysqli_real_escape_string($connect, $_POST['brand_id']);
        $year = mysqli_real_escape_string($connect, $_POST['year']);
        $price = mysqli_real_escape_string($connect, $_POST['price']);
        $max_speed = mysqli_real_escape_string($connect, $_POST['max_speed']);
        $engine_name = mysqli_real_escape_string($connect, $_POST['engine_name']);
        $fuel_name = mysqli_real_escape_string($connect, $_POST['fuel_name']);
        $color = mysqli_real_escape_string($connect, $_POST['color']);
        $seat_number = mysqli_real_escape_string($connect, $_POST['seat_number']);
        $engine_power = mysqli_real_escape_string($connect, $_POST['engine_power']);
        $status = mysqli_real_escape_string($connect, $_POST['status']);
        $fuel_capacity = mysqli_real_escape_string($connect, $_POST['fuel_capacity']);
        $car_description = mysqli_real_escape_string($connect, $_POST['car_description']);

        $image_link = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../User/uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_link = 'uploads/' . $image_name;
            } else {
                echo "<script>showNotification('Couldn\\'t upload the image!', 'error');</script>";
            }
        }

        $query = "INSERT INTO products (
                    car_name, brand_id, year_manufacture, price, max_speed, engine_name,
                    fuel_name, color, seat_number, engine_power, image_link, status, fuel_capacity, car_description
                  ) VALUES (
                    '$car_name', '$brand_id', '$year', '$price', '$max_speed', '$engine_name',
                    '$fuel_name', '$color', '$seat_number', '$engine_power', '$image_link', '$status', '$fuel_capacity', '$car_description'
                  )";

        if (mysqli_query($connect, $query)) {
            $product_id = mysqli_insert_id($connect);

            if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
                $upload_dir = '../User/uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $additional_images_count = count($_FILES['additional_images']['name']);
                for ($i = 0; $i < $additional_images_count; $i++) {
                    if ($_FILES['additional_images']['error'][$i] == 0) {
                        $tmp_name = $_FILES['additional_images']['tmp_name'][$i];
                        $original_name = $_FILES['additional_images']['name'][$i];

                        $image_name = time() . '_' . $product_id . '_' . $i . '_' . $original_name;
                        $target_file = $upload_dir . $image_name;

                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $relative_path = 'uploads/' . $image_name;
                            $insert_image_query = "INSERT INTO product_images (product_id, image_url, sort_order)
                                                   VALUES ($product_id, '$relative_path', $i)";
                            mysqli_query($connect, $insert_image_query);
                        }
                    }
                }
            }

            echo "<script>showNotification('Add product successfully!', 'success');</script>";
        } else {
            echo "<script>showNotification('Error: " . mysqli_error($connect) . "', 'error');</script>";
        }
    }
}

// Xử lý xóa ảnh phụ bằng JSON
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['image_id'])) {
    $image_id = (int)$data['image_id'];

    $query = "SELECT image_url FROM product_images WHERE image_id = $image_id";
    $result = mysqli_query($connect, $query);
    $image = mysqli_fetch_assoc($result);

    if ($image) {
        $file_path = '../User/' . $image['image_url'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        $delete_query = "DELETE FROM product_images WHERE image_id = $image_id";
        mysqli_query($connect, $delete_query);
    }
}

// Xử lý cập nhật sản phẩm
if (isset($_POST['update_product'])) {
    $product_id = mysqli_real_escape_string($connect, $_POST['product_id']);
    $car_name = mysqli_real_escape_string($connect, $_POST['car_name']);
    $brand_id = mysqli_real_escape_string($connect, $_POST['brand_id']);
    $year = mysqli_real_escape_string($connect, $_POST['year']);
    $price = mysqli_real_escape_string($connect, $_POST['price']);
    $max_speed = mysqli_real_escape_string($connect, $_POST['max_speed']);
    $engine_name = mysqli_real_escape_string($connect, $_POST['engine_name']);
    $fuel_name = mysqli_real_escape_string($connect, $_POST['fuel_name']);
    $color = mysqli_real_escape_string($connect, $_POST['color']);
    $seat_number = mysqli_real_escape_string($connect, $_POST['seat_number']);
    $engine_power = mysqli_real_escape_string($connect, $_POST['engine_power']);
    $status = mysqli_real_escape_string($connect, $_POST['status']);
    $fuel_capacity = mysqli_real_escape_string($connect, $_POST['fuel_capacity']);
    $car_description = mysqli_real_escape_string($connect, $_POST['car_description']);

    $image_update = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../User/uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_update = ", image_link = 'uploads/" . $image_name . "'";
        } else {
            echo "<script>showNotification('Couldn\\'t upload the image!', 'error');</script>";
        }
    }

    $query = "UPDATE products SET
                car_name = '$car_name',
                brand_id = '$brand_id',
                year_manufacture = '$year',
                price = '$price',
                max_speed = '$max_speed',
                engine_name = '$engine_name',
                fuel_name = '$fuel_name',
                color = '$color',
                seat_number = '$seat_number',
                engine_power = '$engine_power',
                fuel_capacity = '$fuel_capacity',
                car_description = '$car_description',
                status = '$status'
                $image_update
              WHERE product_id = $product_id";

    if (mysqli_query($connect, $query)) {
        if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
            $upload_dir = '../User/uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $additional_images_count = count($_FILES['additional_images']['name']);
            for ($i = 0; $i < $additional_images_count; $i++) {
                if ($_FILES['additional_images']['error'][$i] == 0) {
                    $tmp_name = $_FILES['additional_images']['tmp_name'][$i];
                    $original_name = $_FILES['additional_images']['name'][$i];

                    $image_name = time() . '_' . $product_id . '_' . $i . '_' . $original_name;
                    $target_file = $upload_dir . $image_name;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $relative_path = 'uploads/' . $image_name;
                        $insert_image_query = "INSERT INTO product_images (product_id, image_url, sort_order)
                                               VALUES ($product_id, '$relative_path', $i)";
                        mysqli_query($connect, $insert_image_query);
                    }
                }
            }
        }

        echo "<script>showNotification('Update the product successfully!', 'success');</script>";
    } else {
        echo "<script>showNotification('Error: " . mysqli_error($connect) . "', 'error');</script>";
    }
}

// Lấy danh sách hãng xe
$brands_query = "SELECT * FROM car_types ORDER BY type_name";
$brands_result = mysqli_query($connect, $brands_query);
$brands = [];
while ($brand = mysqli_fetch_assoc($brands_result)) {
    $brands[] = $brand;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>
    <style>
        .admin-section {
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .admin-table th {
            background-color: #f3f3f3;
            font-weight: bold;
        }

        .admin-table button {
            padding: 6px 10px;
            margin-right: 5px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #007bff;
        }

        .edit-btn:hover {
            background-color: #0056b3;
        }

        .delete-btn {
            background-color: #dc3545 !important;
        }

        .delete-btn:hover {
            background-color: #c82333 !important;
        }

        #add-user-btn{
            margin-bottom:0;
        }

        #add-user-btn:hover {
            background: #16a085;
            transform: translateY(-2px);
        }

        #addProductModal,
        #editProductModal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            padding: 30px;
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        #addProductModal h3,
        #editProductModal h3 {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin: 0 0 20px 0;
            text-align: center;
            border-bottom: 2px solid #1abc9c;
            padding-bottom: 10px;
        }

        .form-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        #addProductForm div,
        #editProductForm div {
            margin-bottom: 16px;
        }

        #addProductForm label,
        #editProductForm label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #444;
        }

        #addProductForm input,
        #addProductForm select,
        #addProductForm textarea,
        #editProductForm input,
        #editProductForm select,
        #editProductForm textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #f9f9f9;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }

        #addProductForm input:focus,
        #addProductForm select:focus,
        #addProductForm textarea:focus,
        #editProductForm input:focus,
        #editProductForm select:focus,
        #editProductForm textarea:focus {
            border-color: #1abc9c;
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.2);
            outline: none;
            background-color: #fff;
        }

        #addProductForm input[type="file"],
        #editProductForm input[type="file"] {
            padding: 8px;
            background-color: #fff;
            border: 1px dashed #ccc;
        }

        .form-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            gap: 10px;
        }

        .form-buttons button {
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            flex: 1;
            padding: 12px;
            color: white;
        }

        .form-buttons button[type="submit"] {
            background-color: #1abc9c;
        }

        .form-buttons button[type="submit"]:hover {
            background-color: #16a085;
            transform: translateY(-2px);
        }

        .form-buttons button[type="button"] {
            background-color: #e74c3c;
        }

        .form-buttons button[type="button"]:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .required:after {
            content: "*";
            color: #e74c3c;
            margin-left: 4px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 999;
            display: none;
            backdrop-filter: blur(3px);
        }

        #currentProductImage,
        #addImagePreview {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        #currentImagePreview,
        #addImagePreview img {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .images-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px dashed #dee2e6;
        }

        .image-preview-item {
            position: relative;
            aspect-ratio: 16/9;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-image,
        .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-image:hover,
        .remove-preview:hover {
            background: #dc3545;
            transform: scale(1.1);
        }

        .empty-preview-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        .preview-count {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }

        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popup .popup-content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            width: 400px;
            max-width: 90%;
        }

        .popup-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .confirm-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .confirm-btn:hover {
            background-color: #c82333;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
        }

        .admin-table th:nth-child(1) { width: 50px; }
        .admin-table th:nth-child(2) { width: 80px; }
        .admin-table th:nth-child(5) { width: 60px; }
        .admin-table th:nth-child(6) { width: 150px; }
        .admin-table th:nth-child(7) { width: 60px; }
        .admin-table th:nth-child(8) { width: 130px; }
        .admin-table th:nth-child(9) { width: 125px; }
        .admin-table th:nth-child(13) { width: 115px; }
        .admin-table th:nth-child(12) { width: 70px; }

        @media (max-width: 768px) {
            #addProductModal,
            #editProductModal {
                width: 95%;
                padding: 20px;
            }

            .form-buttons {
                flex-direction: column;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }

        .product-top-actions{
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }

        .top-action-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            height:44px;
            padding:0 18px;
            border:none;
            border-radius:10px;
            font-size:16px;
            font-weight:700;
            text-decoration:none;
            cursor:pointer;
            transition:all 0.2s ease;
            box-sizing:border-box;
            white-space:nowrap;
        }

        .top-action-btn i{
            font-size:15px;
        }

        .top-action-primary{
            background:#1abc9c;
            color:#fff;
            box-shadow:0 4px 12px rgba(26,188,156,0.22);
        }

        .top-action-primary:hover{
            background:#16a085;
            transform:translateY(-1px);
            box-shadow:0 6px 16px rgba(22,160,133,0.28);
        }

        .top-action-secondary{
            background:#ffffff;
            color:#1abc9c;
            border:1.5px solid #1abc9c;
        }

        .top-action-secondary:hover{
            background:#ecfffb;
            color:#16a085;
            border-color:#16a085;
            transform:translateY(-1px);
        }
    </style>
</head>

<body>
    <main>
        <section class="admin-section">
            <h2><i class="fa-solid fa-pen-to-square"></i>&nbsp;&nbsp;Product Management</h2>

            <div class="product-top-actions">
            <button type="button" onclick="showAddProductForm()" id="add-user-btn" class="top-action-btn top-action-primary">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Product</span>
            </button>

            <a href="manage-brands.php" class="top-action-btn top-action-secondary">
                <i class="fa-solid fa-tags"></i>
                <span>Manage Brands</span>
            </a>
        </div>

            <form method="GET" class="filter-form" style="display:flex; gap:10px; align-items:center; margin-top:8px; margin-bottom:12px; flex-wrap:wrap;">
                <input type="text" name="keyword" placeholder="Tìm tên sản phẩm..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>" style="padding:6px 8px; min-width:200px;">

                <select name="car_type" style="padding:6px 8px;">
                    <option value="">Tất cả loại xe</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['type_id'] ?>" <?= (isset($_GET['car_type']) && $_GET['car_type'] == $b['type_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['type_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="price_filter" style="padding:6px 8px;">
                    <option value="">Tất cả giá</option>
                    <option value="1" <?= (isset($_GET['price_filter']) && $_GET['price_filter'] == "1") ? 'selected' : '' ?>>Dưới 500 triệu</option>
                    <option value="2" <?= (isset($_GET['price_filter']) && $_GET['price_filter'] == "2") ? 'selected' : '' ?>>500 - 1 tỷ</option>
                    <option value="3" <?= (isset($_GET['price_filter']) && $_GET['price_filter'] == "3") ? 'selected' : '' ?>>Trên 1 tỷ</option>
                </select>

                <select name="status" style="padding:6px 8px;">
                    <option value="">Tình trạng</option>
                    <option value="selling" <?= (isset($_GET['status']) && $_GET['status'] == 'selling') ? 'selected' : '' ?>>Còn hàng</option>
                    <option value="hidden" <?= (isset($_GET['status']) && $_GET['status'] == 'hidden') ? 'selected' : '' ?>>Ẩn</option>
                    <option value="discounting" <?= (isset($_GET['status']) && $_GET['status'] == 'discounting') ? 'selected' : '' ?>>Đang giảm giá</option>
                    <option value="soldout" <?= (isset($_GET['status']) && $_GET['status'] == 'soldout') ? 'selected' : '' ?>>Hết hàng</option>
                </select>

                <button type="submit" style="padding:6px 10px;">Lọc</button>
                <a href="manage-products.php" style="padding:6px 10px; background:#f8f9fa; border:1px solid #ddd; text-decoration:none; color:#333;">Reset</a>
            </form>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-hashtag"></i> ID</th>
                        <th><i class="fa-solid fa-image"></i> Image</th>
                        <th><i class="fa-solid fa-car"></i> Car Name</th>
                        <th><i class="fa-solid fa-building"></i> Brand</th>
                        <th><i class="fa-solid fa-calendar"></i> Year</th>
                        <th><i class="fa-solid fa-tag"></i> Price</th>
                        <th><i class="fa-solid fa-gas-pump"></i> Fuel</th>
                        <th><i class="fa-solid fa-oil-can"></i> Fuel Capacity</th>
                        <th><i class="fa-solid fa-gear"></i> Engine Power</th>
                        <th><i class="fa-solid fa-gears"></i> Engine</th>
                        <th><i class="fa-solid fa-palette"></i> Color</th>
                        <th><i class="fa-solid fa-users"></i> Seats</th>
                        <th><i class="fa-solid fa-gauge"></i> Max Speed</th>
                        <th><i class="fa-solid fa-circle-info"></i> Status</th>
                        <th><i class="fa-solid fa-wrench"></i> Actions</th>
                    </tr>
                </thead>
                <tbody id="product-list">
                    <?php
                    $wheres = [];
                    if (!empty($_GET['keyword'])) {
                        $kw = mysqli_real_escape_string($connect, $_GET['keyword']);
                        $wheres[] = "p.car_name LIKE '%" . $kw . "%'";
                    }
                    if (!empty($_GET['car_type'])) {
                        $type = (int)$_GET['car_type'];
                        $wheres[] = "p.brand_id = " . $type;
                    }
                    if (!empty($_GET['price_filter'])) {
                        $pf = $_GET['price_filter'];
                        if ($pf == '1') $wheres[] = "p.price < 500000000";
                        elseif ($pf == '2') $wheres[] = "p.price BETWEEN 500000000 AND 1000000000";
                        elseif ($pf == '3') $wheres[] = "p.price > 1000000000";
                    }
                    if (!empty($_GET['status'])) {
                        $st = mysqli_real_escape_string($connect, $_GET['status']);
                        $wheres[] = "p.status = '" . $st . "'";
                    }

                    $sql = "SELECT p.*, c.type_name
                            FROM products p
                            LEFT JOIN car_types c ON p.brand_id = c.type_id";
                    if ($wheres) {
                        $sql .= ' WHERE ' . implode(' AND ', $wheres);
                    }
                    $sql .= ' ORDER BY p.product_id ASC';

                    $result = mysqli_query($connect, $sql);
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td>' . $row['product_id'] . '</td>';
                            echo '<td><img src="../User/' . htmlspecialchars($row['image_link']) . '" alt="' . htmlspecialchars($row['car_name']) . '" style="width: 80px; height: 60px; object-fit: cover;"></td>';
                            echo '<td>' . htmlspecialchars($row['car_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['year_manufacture']) . '</td>';
                            echo '<td>' . number_format($row['price'], 0, ',', '.') . ' VND</td>';
                            echo '<td>' . htmlspecialchars($row['fuel_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['fuel_capacity']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['engine_power']) . ' hp</td>';
                            echo '<td>' . htmlspecialchars($row['engine_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['color']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['seat_number']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['max_speed']) . '</td>';
                            echo '<td>' . getStatusLabel($row['status']) . '</td>';
                            echo '<td>
                                <button onclick="showEditProductForm(' . $row['product_id'] . ')" class="edit-btn">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </button>
                                <button onclick="confirmDeleteProduct(' . $row['product_id'] . ')" class="delete-btn">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="15">No products available</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>

    <div id="addProductModal">
        <h3>Add New Product</h3>
        <form id="addProductForm" method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <div>
                    <label for="car_name" class="required"><i class="fa-solid fa-car"></i> Car Name:</label>
                    <input type="text" id="car_name" name="car_name" required>
                </div>

                <div>
                    <label for="brand_id" class="required"><i class="fa-solid fa-building"></i> Brand:</label>
                    <select id="brand_id" name="brand_id" required>
                        <option value="">Select brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['type_id'] ?>"><?= htmlspecialchars($brand['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="year" class="required"><i class="fa-solid fa-calendar"></i> Year of Manufacture:</label>
                    <input type="number" id="year" name="year" min="1900" max="<?= date('Y') + 1 ?>" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="price" class="required"><i class="fa-solid fa-tag"></i> Price (VND):</label>
                    <input type="number" id="price" name="price" min="0" required>
                </div>

                <div>
                    <label for="max_speed" class="required"><i class="fa-solid fa-gauge-high"></i> Maximum Speed:</label>
                    <input type="text" id="max_speed" name="max_speed" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="engine_name" class="required"><i class="fa-solid fa-gears"></i> Engine:</label>
                    <input type="text" id="engine_name" name="engine_name" required>
                </div>

                <div>
                    <label for="fuel_name" class="required"><i class="fa-solid fa-gas-pump"></i> Fuel Type:</label>
                    <input type="text" id="fuel_name" name="fuel_name" required>
                </div>

                <div>
                    <label for="color" class="required"><i class="fa-solid fa-palette"></i> Color:</label>
                    <input type="text" id="color" name="color" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="seat_number" class="required"><i class="fa-solid fa-users"></i> Number of Seats:</label>
                    <input type="number" id="seat_number" name="seat_number" min="1" max="20" required>
                </div>

                <div>
                    <label for="engine_power" class="required"><i class="fa-solid fa-gear"></i> Engine Power:</label>
                    <input type="number" id="engine_power" name="engine_power" min="0" max="2000" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="fuel_capacity" class="required"><i class="fa-solid fa-oil-can"></i> Fuel Capacity:</label>
                    <input type="text" id="fuel_capacity" name="fuel_capacity" placeholder="e.g., 65L, 100kWh, 5kg" required>
                </div>

                <div>
                    <label for="car_description" class="required"><i class="fa-solid fa-align-left"></i> Description:</label>
                    <textarea id="car_description" name="car_description" rows="4" required></textarea>
                </div>
            </div>

            <div>
                <label for="image" class="required"><i class="fa-solid fa-image"></i> Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>

            <div id="addImagePreview" style="display:none;"></div>

            <div>
                <label for="additional_images"><i class="fa-solid fa-images"></i> Additional Images:</label>
                <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                <small style="display: block; margin-top: 5px; color: #666;">You can select multiple images at once</small>
            </div>

            <div id="addImagesPreview" class="images-preview-container"></div>

            <div>
                <label for="status" class="required"><i class="fa-solid fa-circle-info"></i> Status:</label>
                <select id="status" name="status" required>
                    <option value="selling">Available</option>
                    <option value="hidden">Hidden</option>
                    <option value="discounting">On Sale</option>
                    <option value="soldout">Sold Out</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" name="add_product">Add Product</button>
                <button type="button" onclick="closeAddProductForm()">Cancel</button>
            </div>
        </form>
    </div>

    <div id="editProductModal">
        <h3>Edit Product Information</h3>
        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="edit_product_id" name="product_id">

            <div id="currentProductImage">
                <img id="currentImagePreview" src="" alt="Product Image">
            </div>

            <div class="form-section">
                <div>
                    <label for="edit_car_name" class="required">Car Name:</label>
                    <input type="text" id="edit_car_name" name="car_name" required>
                </div>

                <div>
                    <label for="edit_brand_id" class="required">Brand:</label>
                    <select id="edit_brand_id" name="brand_id" required>
                        <option value="">Select brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['type_id'] ?>"><?= htmlspecialchars($brand['type_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="edit_year" class="required">Year of Manufacture:</label>
                    <input type="number" id="edit_year" name="year" min="1900" max="<?= date('Y') + 1 ?>" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="edit_price" class="required">Price (VND):</label>
                    <input type="number" id="edit_price" name="price" min="0" required>
                </div>

                <div>
                    <label for="edit_max_speed" class="required">Maximum Speed:</label>
                    <input type="text" id="edit_max_speed" name="max_speed" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="edit_engine_name" class="required">Engine:</label>
                    <input type="text" id="edit_engine_name" name="engine_name" required>
                </div>

                <div>
                    <label for="edit_fuel_name" class="required">Fuel Type:</label>
                    <input type="text" id="edit_fuel_name" name="fuel_name" required>
                </div>

                <div>
                    <label for="edit_color" class="required">Color:</label>
                    <input type="text" id="edit_color" name="color" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="edit_seat_number" class="required">Number of Seats:</label>
                    <input type="number" id="edit_seat_number" name="seat_number" min="1" max="20" required>
                </div>

                <div>
                    <label for="edit_engine_power" class="required">Engine Power:</label>
                    <input type="number" id="edit_engine_power" name="engine_power" min="0" max="2000" required>
                </div>
            </div>

            <div class="form-section">
                <div>
                    <label for="edit_fuel_capacity" class="required">Fuel Capacity:</label>
                    <input type="text" id="edit_fuel_capacity" name="fuel_capacity" placeholder="e.g., 65L, 100kWh, 5kg" required>
                </div>

                <div>
                    <label for="edit_car_description" class="required">Description:</label>
                    <textarea id="edit_car_description" name="car_description" rows="4" required></textarea>
                </div>
            </div>

            <div>
                <label for="edit_image">New Image (leave empty if unchanged):</label>
                <input type="file" id="edit_image" name="image" accept="image/*">
            </div>

            <div>
                <label for="edit_additional_images">Additional Images:</label>
                <input type="file" id="edit_additional_images" name="additional_images[]" accept="image/*" multiple>
                <small style="display: block; margin-top: 5px; color: #666;">You can select multiple images at once</small>

                <label>Product Additional Image(s):</label>
                <div id="currentAdditionalImages" class="images-preview-container"></div>

                <label>New Selected Additional Image(s):</label>
                <div id="editImagesPreview" class="images-preview-container"></div>
            </div>

            <div>
                <label for="edit_status" class="required">Status:</label>
                <select id="edit_status" name="status" required>
                    <option value="selling">Available</option>
                    <option value="hidden">Hidden</option>
                    <option value="discounting">On Sale</option>
                    <option value="soldout">Sold Out</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" name="update_product">Save Changes</button>
                <button type="button" onclick="closeEditProductForm()">Cancel</button>
            </div>
        </form>
    </div>

    <div id="deleteConfirmModal" class="popup">
        <div class="popup-content">
            <h3><i class="fa-solid fa-trash"></i> Delete Product</h3>
            <p id="deleteMessage">
                Nếu sản phẩm đã từng nhập hàng hoàn tất hoặc đã từng xuất hiện trong đơn hàng,
                hệ thống sẽ chỉ ẩn sản phẩm. Nếu chưa từng phát sinh dữ liệu nghiệp vụ,
                sản phẩm sẽ bị xóa hẳn khỏi CSDL.
            </p>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="product_id" id="delete_product_id">
                <div class="popup-buttons">
                    <button type="submit" name="confirm_action" class="confirm-btn">
                        <i class="fa-solid fa-trash"></i> Confirm
                    </button>
                    <button type="button" class="cancel-btn" onclick="closeDeleteConfirm()">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay"></div>

    <script>
        function showAddProductForm() {
            document.getElementById('addProductForm').reset();
            document.getElementById('addImagePreview').style.display = 'none';
            document.getElementById('addImagePreview').innerHTML = '';
            document.getElementById('addImagesPreview').innerHTML = '';
            document.getElementById('addProductModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeAddProductForm() {
            document.getElementById('addProductModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        function showEditProductForm(productId) {
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.product;

                        document.getElementById('edit_product_id').value = product.product_id;
                        document.getElementById('edit_car_name').value = product.car_name;
                        document.getElementById('edit_brand_id').value = product.brand_id;
                        document.getElementById('edit_year').value = product.year_manufacture;
                        document.getElementById('edit_price').value = product.price;
                        document.getElementById('edit_max_speed').value = product.max_speed;
                        document.getElementById('edit_engine_name').value = product.engine_name;
                        document.getElementById('edit_fuel_name').value = product.fuel_name;
                        document.getElementById('edit_color').value = product.color;
                        document.getElementById('edit_seat_number').value = product.seat_number;
                        document.getElementById('edit_engine_power').value = product.engine_power;
                        document.getElementById('edit_status').value = product.status;
                        document.getElementById('edit_fuel_capacity').value = product.fuel_capacity;
                        document.getElementById('edit_car_description').value = product.car_description;
                        document.getElementById('currentImagePreview').src = '../User/' + product.image_link;

                        document.getElementById('editImagesPreview').innerHTML = '';
                        loadExistingImages(productId);

                        document.getElementById('editProductModal').style.display = 'block';
                        document.getElementById('modalOverlay').style.display = 'block';
                    } else {
                        showNotification('Error loading product information', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error loading product information', 'error');
                });
        }

        function closeEditProductForm() {
            document.getElementById('editProductModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        function loadExistingImages(productId) {
            fetch(`get_product_images.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('currentAdditionalImages');
                    container.innerHTML = '';

                    if (data.images && data.images.length > 0) {
                        data.images.forEach(image => {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'image-preview-item';
                            previewItem.innerHTML = `
                                <img src="../User/${image.image_url}" alt="Product Image">
                                <button type="button" class="remove-image" onclick="deleteProductImage(${image.image_id}, this)">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            `;
                            container.appendChild(previewItem);
                        });
                    } else {
                        container.innerHTML = '<div class="empty-preview-message">No additional images</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading additional images:', error);
                });
        }

        function confirmDeleteProduct(productId) {
            document.getElementById('delete_product_id').value = productId;
            document.getElementById('deleteConfirmModal').style.display = 'flex';
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeDeleteConfirm() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }

        function deleteProductImage(imageId, button) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch('delete_product_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ image_id: imageId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.closest('.image-preview-item').remove();
                        showNotification('Image deleted successfully', 'success');

                        const container = document.getElementById('currentAdditionalImages');
                        if (container.children.length === 0) {
                            container.innerHTML = '<div class="empty-preview-message">No additional images</div>';
                        }
                    } else {
                        showNotification(data.message || 'Error deleting image', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }

        function previewAdditionalImages(input, containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            if (input.files && input.files.length > 0) {
                const fragment = document.createDocumentFragment();

                Array.from(input.files).forEach((file, index) => {
                    const reader = new FileReader();
                    const previewItem = document.createElement('div');
                    previewItem.className = 'image-preview-item';

                    reader.onload = function (e) {
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}">
                            <button type="button" class="remove-preview" onclick="removePreview(this, '${containerId}', ${index})">
                                <i class="fa-solid fa-times"></i>
                            </button>
                            <span class="preview-count">${index + 1}/${input.files.length}</span>
                        `;
                    };

                    reader.readAsDataURL(file);
                    fragment.appendChild(previewItem);
                });

                container.appendChild(fragment);
            } else {
                container.innerHTML = '<div class="empty-preview-message">No additional images selected</div>';
            }
        }

        function removePreview(button, containerId, index) {
            const container = document.getElementById(containerId);
            const fileInput = containerId === 'addImagesPreview'
                ? document.getElementById('additional_images')
                : document.getElementById('edit_additional_images');

            button.closest('.image-preview-item').remove();

            const dt = new DataTransfer();
            Array.from(fileInput.files).forEach((file, i) => {
                if (i !== index) dt.items.add(file);
            });
            fileInput.files = dt.files;

            if (dt.files.length === 0) {
                container.innerHTML = '<div class="empty-preview-message">No additional images selected</div>';
            } else {
                const previewItems = container.querySelectorAll('.image-preview-item');
                previewItems.forEach((item, i) => {
                    const countSpan = item.querySelector('.preview-count');
                    if (countSpan) {
                        countSpan.textContent = `${i + 1}/${dt.files.length}`;
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('modalOverlay');
            if (overlay) {
                overlay.addEventListener('click', function () {
                    ['editProductModal', 'addProductModal', 'deleteConfirmModal'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.style.display = 'none';
                    });
                    overlay.style.display = 'none';
                });
            }

            const addImageInput = document.getElementById('image');
            if (addImageInput) {
                addImageInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    const previewContainer = document.getElementById('addImagePreview');

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (ev) {
                            previewContainer.style.display = 'block';
                            previewContainer.innerHTML = `
                                <img src="${ev.target.result}" 
                                     style="max-width: 200px; max-height: 150px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            `;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            const editImageInput = document.getElementById('edit_image');
            if (editImageInput) {
                editImageInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (ev) {
                            document.getElementById('currentImagePreview').src = ev.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            const additionalImagesInput = document.getElementById('additional_images');
            if (additionalImagesInput) {
                additionalImagesInput.addEventListener('change', function () {
                    previewAdditionalImages(this, 'addImagesPreview');
                });
            }

            const editAdditionalImagesInput = document.getElementById('edit_additional_images');
            if (editAdditionalImagesInput) {
                editAdditionalImagesInput.addEventListener('change', function () {
                    previewAdditionalImages(this, 'editImagesPreview');
                });
            }
        });
    </script>
</body>
</html>
<?php
include 'footer.php';
?>