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

include '../User/connect.php';

$message = '';
$messageType = '';

/* =========================
   HELPERS
========================= */
function ensureBrandUploadDir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function uploadBrandFile(array $file, string $prefix): ?string {
    if (!isset($file['error']) || $file['error'] !== 0) {
        return null;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    $originalName = $file['name'] ?? '';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return null;
    }

    $uploadDir = '../User/uploads/brands/';
    ensureBrandUploadDir($uploadDir);

    $newName = time() . '_' . $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetFile = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return 'uploads/brands/' . $newName;
    }

    return null;
}

function getAssetSrc(?string $path): string {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    // URL đầy đủ
    if (
        stripos($path, 'http://') === 0 ||
        stripos($path, 'https://') === 0 ||
        stripos($path, '//') === 0 ||
        stripos($path, 'data:') === 0
    ) {
        return $path;
    }

    $normalized = ltrim($path, '/');

    // file upload mới
    if (stripos($normalized, 'uploads/') === 0) {
        return '../User/' . $normalized;
    }

    // dữ liệu cũ có thể chỉ là tên file logo trong thư mục hiện tại Admin
    // ví dụ: audi.png, bmw.png
    return $normalized;
}

/* =========================
   ADD BRAND
========================= */
if (isset($_POST['add_brand'])) {
    $name = trim($_POST['type_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = "Brand name is required.";
        $messageType = "error";
    } else {
        $checkStmt = $connect->prepare("
            SELECT type_id
            FROM car_types
            WHERE LOWER(type_name) = LOWER(?)
        ");
        $checkStmt->bind_param("s", $name);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "Brand already exists.";
            $messageType = "error";
        } else {
            $uploadedLogo = uploadBrandFile($_FILES['logo_file'] ?? [], 'logo');
            $uploadedBanner = uploadBrandFile($_FILES['banner_file'] ?? [], 'banner');

            $stmt = $connect->prepare("
                INSERT INTO car_types (type_name, logo_url, banner_url, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $name, $uploadedLogo, $uploadedBanner, $desc);

            if ($stmt->execute()) {
                $message = "Brand added successfully.";
                $messageType = "success";
            } else {
                $message = "Error adding brand: " . $connect->error;
                $messageType = "error";
            }
        }
    }
}

/* =========================
   UPDATE BRAND
========================= */
if (isset($_POST['update_brand'])) {
    $id = (int)($_POST['type_id'] ?? 0);
    $name = trim($_POST['type_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $currentLogo = trim($_POST['current_logo_url'] ?? '');
    $currentBanner = trim($_POST['current_banner_url'] ?? '');

    if ($id <= 0 || $name === '') {
        $message = "Invalid brand data.";
        $messageType = "error";
    } else {
        $checkStmt = $connect->prepare("
            SELECT type_id
            FROM car_types
            WHERE LOWER(type_name) = LOWER(?)
              AND type_id <> ?
        ");
        $checkStmt->bind_param("si", $name, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "Another brand with this name already exists.";
            $messageType = "error";
        } else {
            $uploadedLogo = uploadBrandFile($_FILES['edit_logo_file'] ?? [], 'logo');
            $uploadedBanner = uploadBrandFile($_FILES['edit_banner_file'] ?? [], 'banner');

            $finalLogo = $uploadedLogo ?: $currentLogo;
            $finalBanner = $uploadedBanner ?: $currentBanner;

            $stmt = $connect->prepare("
                UPDATE car_types
                SET type_name = ?, logo_url = ?, banner_url = ?, description = ?
                WHERE type_id = ?
            ");
            $stmt->bind_param("ssssi", $name, $finalLogo, $finalBanner, $desc, $id);

            if ($stmt->execute()) {
                $message = "Brand updated successfully.";
                $messageType = "success";
            } else {
                $message = "Error updating brand: " . $connect->error;
                $messageType = "error";
            }
        }
    }
}

/* =========================
   DELETE BRAND (SAFE)
========================= */
if (isset($_POST['delete_brand'])) {
    $id = (int)($_POST['type_id'] ?? 0);

    $checkStmt = $connect->prepare("
        SELECT COUNT(*) AS total
        FROM products
        WHERE brand_id = ?
    ");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $countRow = $checkStmt->get_result()->fetch_assoc();
    $count = (int)($countRow['total'] ?? 0);

    if ($count > 0) {
        $message = "Cannot delete brand. It is already used by existing products.";
        $messageType = "error";
    } else {
        $stmt = $connect->prepare("DELETE FROM car_types WHERE type_id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Brand deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting brand.";
            $messageType = "error";
        }
    }
}

/* =========================
   LOAD BRANDS
========================= */
$brandQuery = "
    SELECT
        ct.type_id,
        ct.type_name,
        ct.logo_url,
        ct.banner_url,
        ct.description,
        COUNT(p.product_id) AS product_count
    FROM car_types ct
    LEFT JOIN products p ON p.brand_id = ct.type_id
    GROUP BY
        ct.type_id,
        ct.type_name,
        ct.logo_url,
        ct.banner_url,
        ct.description
    ORDER BY ct.type_name ASC
";

$brandResult = mysqli_query($connect, $brandQuery);

if (!$brandResult) {
    die("Query error: " . mysqli_error($connect));
}

$brands = [];
while ($row = mysqli_fetch_assoc($brandResult)) {
    $brands[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brands</title>
    <link rel="icon" href="../User/dp56vcf7.png" type="image/png">
    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>

    <style>
        body{
            margin:0;
            font-family: Arial, sans-serif;
            background:#f4f6f9;
        }

        .page{
            max-width:1250px;
            margin:30px auto;
            background:#fff;
            padding:24px;
            border-radius:14px;
            box-shadow:0 8px 24px rgba(0,0,0,0.08);
        }

        .top-link{
            display:inline-flex;
            align-items:center;
            gap:8px;
            margin-bottom:18px;
            color:#1abc9c;
            text-decoration:none;
            font-weight:bold;
        }

        h1{
            margin:0 0 8px 0;
            color:#2c3e50;
        }

        .section-desc{
            margin:0 0 20px 0;
            color:#64748b;
            line-height:1.6;
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

        .content-grid{
            display:grid;
            grid-template-columns: 390px minmax(0, 1fr);
            gap:22px;
            align-items:start;
        }

        .card{
            background:#fff;
            border:1px solid #e5e7eb;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,0.04);
        }

        .content-grid > .card{
            min-width:0;
        }

        .card-header{
            padding:18px 18px 10px 18px;
            border-bottom:1px solid #eef2f7;
        }

        .card-title{
            margin:0;
            color:#2c3e50;
            font-size:20px;
            font-weight:700;
        }

        .card-subtitle{
            margin:8px 0 0 0;
            color:#64748b;
            font-size:14px;
            line-height:1.5;
        }

        .card-body{
            padding:18px;
        }

        .form-group{
            display:flex;
            flex-direction:column;
            gap:8px;
            margin-bottom:14px;
        }

        .form-group label{
            font-size:14px;
            font-weight:700;
            color:#334155;
        }

        .form-input{
            width:100%;
            padding:11px 12px;
            border:1px solid #d1d5db;
            border-radius:8px;
            font-size:14px;
            box-sizing:border-box;
            background:#fff;
            outline:none;
            transition:0.2s ease;
        }

        .form-input:focus{
            border-color:#1abc9c;
            box-shadow:0 0 0 3px rgba(26,188,156,0.12);
        }

        textarea.form-input{
            min-height:110px;
            resize:vertical;
            line-height:1.5;
        }

        .preview-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
            margin-top:6px;
        }

        .preview-box{
            border:1px dashed #cbd5e1;
            border-radius:10px;
            background:#f8fafc;
            min-height:110px;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            padding:8px;
            color:#94a3b8;
            font-size:13px;
            text-align:center;
        }

        .preview-box img{
            max-width:100%;
            max-height:160px;
            object-fit:contain;
            border-radius:8px;
        }

        .btn{
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:700;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            transition:0.2s ease;
        }

        .btn-primary{
            background:#1abc9c;
            color:#fff;
            padding:11px 16px;
        }

        .btn-primary:hover{
            background:#16a085;
        }

        .btn-warning{
            background:#f59e0b;
            color:#fff;
            padding:8px 12px;
        }

        .btn-warning:hover{
            background:#d97706;
        }

        .btn-danger{
            background:#ef4444;
            color:#fff;
            padding:8px 12px;
        }

        .btn-danger:hover{
            background:#dc2626;
        }

        .btn-secondary{
            background:#e5e7eb;
            color:#334155;
            padding:11px 16px;
        }

        .btn-secondary:hover{
            background:#d1d5db;
        }

        .table-wrap{
            width:100%;
            max-width:100%;
            overflow-x:auto;
        }

        table{
            width:100%;
            min-width:760px;
            border-collapse:collapse;
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
            font-size:14px;
            white-space:nowrap;
        }

        .brand-cell{
            display:flex;
            align-items:center;
            gap:12px;
            min-width:0;
        }

        .logo-box{
            width:56px;
            height:56px;
            border-radius:12px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            flex-shrink:0;
        }

        .logo-box img{
            max-width:100%;
            max-height:100%;
            object-fit:contain;
        }

        .logo-placeholder{
            color:#94a3b8;
            font-size:12px;
            text-align:center;
            line-height:1.2;
            padding:6px;
        }

        .banner-box{
            width:150px;
            height:70px;
            border-radius:10px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
        }

        .banner-box img{
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .brand-name{
            font-weight:700;
            color:#1f2937;
            text-transform:uppercase;
        }

        .desc-text{
            color:#475569;
            line-height:1.5;
            max-width:260px;
            white-space:normal;
            word-break:break-word;
        }

        .product-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:36px;
            height:30px;
            padding:0 10px;
            border-radius:999px;
            background:#ecfeff;
            color:#0f766e;
            font-size:13px;
            font-weight:700;
        }

        .action-group{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }

        .hint-box{
            margin-top:14px;
            padding:12px 14px;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:10px;
            color:#64748b;
            font-size:13px;
            line-height:1.6;
        }

        .modal{
            display:none;
            position:fixed;
            inset:0;
            z-index:1000;
        }

        .modal-overlay{
            position:absolute;
            inset:0;
            background:rgba(0,0,0,0.45);
        }

        .modal-dialog{
            position:relative;
            max-width:620px;
            margin:50px auto;
            background:#fff;
            border-radius:14px;
            box-shadow:0 16px 36px rgba(0,0,0,0.18);
            overflow:hidden;
        }

        .modal-header{
            padding:18px 20px;
            border-bottom:1px solid #e5e7eb;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .modal-title{
            margin:0;
            font-size:20px;
            color:#1f2937;
        }

        .modal-close{
            background:none;
            border:none;
            font-size:20px;
            cursor:pointer;
            color:#64748b;
        }

        .modal-body{
            padding:20px;
        }

        .modal-actions{
            display:flex;
            gap:10px;
            justify-content:flex-end;
            flex-wrap:wrap;
            margin-top:8px;
        }

        @media (max-width: 1024px){
            .content-grid{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 640px){
            .page{
                padding:18px;
                margin:18px;
            }

            .preview-grid{
                grid-template-columns:1fr;
            }

            .modal-dialog{
                margin:20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="page">
        <a class="top-link" href="manage-products.php">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Product Management
        </a>

        <h1>Manage Brands</h1>
        <p class="section-desc">
            Manage vehicle brands used by products. New brands added here will automatically appear
            in the Brand dropdown of the product form and on the homepage brand section.
        </p>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Add New Brand</h2>
                    <p class="card-subtitle">
                        Add a new brand for product classification. Upload logo and banner directly from your device.
                    </p>
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="type_name">Brand Name</label>
                            <input
                                type="text"
                                id="type_name"
                                name="type_name"
                                class="form-input"
                                placeholder="Example: BMW"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="logo_file">Upload Logo File</label>
                            <input
                                type="file"
                                id="logo_file"
                                name="logo_file"
                                class="form-input"
                                accept="image/*"
                                onchange="previewLocalFile(this, 'logoPreview')"
                            >
                        </div>

                        <div class="form-group">
                            <label for="banner_file">Upload Banner File</label>
                            <input
                                type="file"
                                id="banner_file"
                                name="banner_file"
                                class="form-input"
                                accept="image/*"
                                onchange="previewLocalFile(this, 'bannerPreview')"
                            >
                        </div>

                        <div class="preview-grid">
                            <div class="preview-box" id="logoPreview">Logo preview</div>
                            <div class="preview-box" id="bannerPreview">Banner preview</div>
                        </div>

                        <div class="form-group" style="margin-top:14px;">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-input"
                                placeholder="Write a short brand description..."
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" name="add_brand">
                            <i class="fa-solid fa-plus"></i>
                            Add Brand
                        </button>
                    </form>

                    <div class="hint-box">
                        <strong>Note:</strong> A brand that is already used by products should not be deleted.
                        The system will block deletion automatically if products are linked to that brand.
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Brand List</h2>
                    <p class="card-subtitle">
                        Review existing brands, preview their logo and banner, check product usage, and edit or delete them safely.
                    </p>
                </div>

                <div class="card-body">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Brand</th>
                                    <th>Banner</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (count($brands) > 0): ?>
                                    <?php foreach ($brands as $row): ?>
                                        <?php
                                            $logoSrc = getAssetSrc($row['logo_url'] ?? '');
                                            $bannerSrc = getAssetSrc($row['banner_url'] ?? '');
                                            $desc = trim((string)($row['description'] ?? ''));
                                        ?>
                                        <tr>
                                            <td><?php echo (int)$row['type_id']; ?></td>

                                            <td>
                                                <div class="brand-cell">
                                                    <div class="logo-box">
                                                        <?php if ($logoSrc !== ''): ?>
                                                            <img
                                                                src="<?php echo htmlspecialchars($logoSrc); ?>"
                                                                alt="<?php echo htmlspecialchars($row['type_name']); ?>"
                                                            >
                                                        <?php else: ?>
                                                            <div class="logo-placeholder">No logo</div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div>
                                                        <div class="brand-name">
                                                            <?php echo htmlspecialchars($row['type_name']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="banner-box">
                                                    <?php if ($bannerSrc !== ''): ?>
                                                        <img
                                                            src="<?php echo htmlspecialchars($bannerSrc); ?>"
                                                            alt="<?php echo htmlspecialchars($row['type_name']); ?> banner"
                                                        >
                                                    <?php else: ?>
                                                        <div class="logo-placeholder">No banner</div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <td class="desc-text">
                                                <?php if ($desc !== ''): ?>
                                                    <?php echo htmlspecialchars($desc); ?>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8;font-style:italic;">No description</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <span class="product-badge">
                                                    <?php echo (int)$row['product_count']; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <div class="action-group">
                                                    <button
                                                        type="button"
                                                        class="btn btn-warning"
                                                        onclick='openEditModal(<?php echo json_encode([
                                                            "type_id" => (int)$row["type_id"],
                                                            "type_name" => (string)$row["type_name"],
                                                            "logo_url" => (string)($row["logo_url"] ?? ""),
                                                            "banner_url" => (string)($row["banner_url"] ?? ""),
                                                            "description" => (string)($row["description"] ?? "")
                                                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                    >
                                                        <i class="fa-solid fa-pen"></i>
                                                        Edit
                                                    </button>

                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="type_id" value="<?php echo (int)$row['type_id']; ?>">
                                                        <button
                                                            type="submit"
                                                            class="btn btn-danger"
                                                            name="delete_brand"
                                                            onclick="return confirm('Delete this brand? This action is only allowed if no products are linked to it.');"
                                                        >
                                                            <i class="fa-solid fa-trash"></i>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:#64748b;font-style:italic;">
                                            No brands found in the database.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="editBrandModal" class="modal">
        <div class="modal-overlay" onclick="closeEditModal()"></div>

        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Edit Brand</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="type_id" id="edit_type_id">
                    <input type="hidden" name="current_logo_url" id="edit_current_logo_url">
                    <input type="hidden" name="current_banner_url" id="edit_current_banner_url">

                    <div class="form-group">
                        <label for="edit_type_name">Brand Name</label>
                        <input
                            type="text"
                            name="type_name"
                            id="edit_type_name"
                            class="form-input"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="edit_logo_file">Upload New Logo File</label>
                        <input
                            type="file"
                            name="edit_logo_file"
                            id="edit_logo_file"
                            class="form-input"
                            accept="image/*"
                            onchange="previewLocalFile(this, 'editLogoPreview')"
                        >
                    </div>

                    <div class="form-group">
                        <label for="edit_banner_file">Upload New Banner File</label>
                        <input
                            type="file"
                            name="edit_banner_file"
                            id="edit_banner_file"
                            class="form-input"
                            accept="image/*"
                            onchange="previewLocalFile(this, 'editBannerPreview')"
                        >
                    </div>

                    <div class="preview-grid">
                        <div class="preview-box" id="editLogoPreview">Logo preview</div>
                        <div class="preview-box" id="editBannerPreview">Banner preview</div>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label for="edit_description">Description</label>
                        <textarea
                            name="description"
                            id="edit_description"
                            class="form-input"
                        ></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" name="update_brand">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setPreview(targetId, src, emptyText) {
            const box = document.getElementById(targetId);
            if (!box) return;

            if (src && src.trim() !== '') {
                box.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" alt="preview">';
            } else {
                box.textContent = emptyText;
            }
        }

        function previewLocalFile(input, targetId) {
            const file = input.files && input.files[0] ? input.files[0] : null;
            const emptyText = targetId.toLowerCase().includes('banner') ? 'Banner preview' : 'Logo preview';

            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                setPreview(targetId, e.target.result, emptyText);
            };
            reader.readAsDataURL(file);
        }

        function resolveAssetPath(path) {
            if (!path || path.trim() === '') return '';

            const normalized = path.trim();

            const lower = normalized.toLowerCase();
            if (
                lower.startsWith('http://') ||
                lower.startsWith('https://') ||
                lower.startsWith('//') ||
                lower.startsWith('data:')
            ) {
                return normalized;
            }

            if (lower.startsWith('uploads/')) {
                return '../User/' + normalized.replace(/^\/+/, '');
            }

            return normalized;
        }

        function openEditModal(brand) {
            document.getElementById('edit_type_id').value = brand.type_id || '';
            document.getElementById('edit_type_name').value = brand.type_name || '';
            document.getElementById('edit_description').value = brand.description || '';
            document.getElementById('edit_current_logo_url').value = brand.logo_url || '';
            document.getElementById('edit_current_banner_url').value = brand.banner_url || '';

            setPreview('editLogoPreview', resolveAssetPath(brand.logo_url || ''), 'Logo preview');
            setPreview('editBannerPreview', resolveAssetPath(brand.banner_url || ''), 'Banner preview');

            document.getElementById('edit_logo_file').value = '';
            document.getElementById('edit_banner_file').value = '';

            document.getElementById('editBrandModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editBrandModal').style.display = 'none';
        }
    </script>
</body>
</html>