<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once '../User/connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST["logout"])) {
    session_destroy();
    session_start();
    $_SESSION['logout_message'] = "Bạn đã đăng xuất thành công.";
    header("Location: login.php");
    exit();
}

$counts = array(
    'users' => 0,
    'products' => 0,
    'orders' => 0
);

$result = mysqli_query($connect, "SELECT COUNT(*) as count FROM users_acc");
if ($result) {
    $counts['users'] = mysqli_fetch_assoc($result)['count'];
}

$result = mysqli_query($connect, "SELECT COUNT(*) as count FROM products");
if ($result) {
    $counts['products'] = mysqli_fetch_assoc($result)['count'];
}

$result = mysqli_query($connect, "SELECT COUNT(*) as count FROM orders");
if ($result) {
    $counts['orders'] = mysqli_fetch_assoc($result)['count'];
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="icon" href="../User/dp56vcf7.png" type="image/png">
<script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>

<style>
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 4px;
        background-color: #f8f9fa;
        color: #333;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transform: translateX(150%);
        transition: transform 0.3s ease-in-out;
        z-index: 1000;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .notification.show {
        transform: translateX(0);
    }

    .notification.success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .notification.error {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .notification.info {
        background-color: #cce5ff;
        color: #004085;
        border-left: 4px solid #007bff;
    }

    .notification.warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .admin-header {
        background: linear-gradient(270deg,#40C1A8, #2C3E50, rgb(255, 255, 255));
        background-size: 400% 400%;
        padding: 20px;
        text-align: center;
        animation: gradientAnimation 30s ease-in-out infinite;
    }

    @keyframes gradientAnimation {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    #logoheader {
        max-width: 120px;
    }

    .navbar {
        background: rgba(44, 62, 80, 0.97);
        overflow: hidden;
        font-weight: bold;
        padding: 10px 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
flex-wrap: wrap;
        gap: 4px;
    }

    .navbar a {
        color: #ecf0f1;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-align: center;
        padding: 14px 20px;
        text-decoration: none;
        position: relative;
        transition: background-color 0.3s, color 0.3s;
    }

    .navbar a:hover {
        background-color: #34495e;
        color: #1abc9c;
    }

    .navbar a.active {
        background-color: #1abc9c;
        color: #ffffff;
    }

    .navbar a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: #1abc9c;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .navbar a:hover::after {
        width: 80%;
    }

    .nav-count {
        position: absolute;
        top: -8px;
        right: -8px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    a[href*="manage-users"] .nav-count { background: #007bff; color: white; }
    a[href*="manage-orders"] .nav-count { background: #28a745; color: white; }
    a[href*="manage-products"] .nav-count { background: #dc3545; color: white; }
    a[href*="manage-inventory"] .nav-count { background: #f88e04; color: white; }
    a[href*="best-seller"] .nav-count { background: #e5e903; color: black; }

    .nav-user {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-right: 20px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        max-width: 260px;
    }

    .user-greeting {
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .logout-form {
        margin: 0;
    }

    .logout-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .notification {
            width: 90%;
            top: 10px;
            right: 50%;
            transform: translateX(50%) translateY(-100%);
        }

        .notification.show {
            transform: translateX(50%) translateY(0);
        }

        .nav-user {
padding: 10px;
            flex-direction: column;
            gap: 10px;
            margin: 10px;
        }

        .navbar {
            flex-direction: column;
            align-items: stretch;
        }

        .navbar a {
            justify-content: center;
        }
    }
</style>

<div id="notification" class="notification"></div>

<header class="admin-header">
    <div class="logo">
        <a href="index.php">
            <img id="logoheader" src="../User/dp56vcf7.png" alt="Logo">
        </a>
    </div>
</header>

<div class="navbar">
    <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-house-chimney"></i>
        <span>Home</span>
    </a>

    <a href="statics.php" class="<?php echo $currentPage === 'statics.php' ? 'active' : ''; ?>">
        <i class="fa-regular fa-clipboard"></i>
        <span>Statics</span>
    </a>

    <a href="manage-users.php" class="<?php echo $currentPage === 'manage-users.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-users-rectangle"></i>
        <span>Manage Users</span>
        <span class="nav-count"><?php echo $counts['users']; ?></span>
    </a>

    <a href="manage-orders.php" class="<?php echo $currentPage === 'manage-orders.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-clipboard-list"></i>
        <span>Manage Orders</span>
        <span class="nav-count"><?php echo $counts['orders']; ?></span>
    </a>

    <a href="manage-products.php" class="<?php echo $currentPage === 'manage-products.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-pen-to-square"></i>
        <span>Manage Products</span>
        <span class="nav-count"><?php echo $counts['products']; ?></span>
    </a>

    <a href="best-seller.php" class="<?php echo $currentPage === 'best-seller.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-star"></i>
        <span>Best Sellers</span>
        <span class="nav-count"><?php echo $counts['products']; ?></span>
    </a>

    <a href="manage-inventory.php" class="<?php echo $currentPage === 'manage-inventory.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-column"></i>
        <span>Inventory Statistics</span>
        <span class="nav-count"><?php echo $counts['products']; ?></span>
    </a>

    <a href="manage-purchase-orders.php" class="<?php echo $currentPage === 'manage-purchase-orders.php' ? 'active' : ''; ?>">
    <i class="fa-solid fa-truck-ramp-box"></i>
    <span>Quản lý nhập hàng</span>
</a>

<a href="add-purchase-order.php" class="<?php echo $currentPage === 'add-purchase-order.php' ? 'active' : ''; ?>">
    <i class="fa-solid fa-file-circle-plus"></i>
    <span>Tạo phiếu nhập</span>
</a>



    <a href="manage-prices.php" class="<?php echo $currentPage === 'manage-prices.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-money-bill-trend-up"></i>
        <span>Quản lý giá bán</span>
    </a>

    <div class="nav-user">
        <span class="user-greeting">
            <i class="fa-regular fa-user"></i>
            <span>Hi,</span>
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
</span>
        <form method="POST" class="logout-form">
            <button type="submit" name="logout" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                Log out
            </button>
        </form>
    </div>
</div>

<script>
function showNotification(message, type) {
    const notification = document.getElementById('notification');
    let icon = '';

    switch(type) {
        case 'success':
            icon = '<i class="fa-solid fa-circle-check"></i>';
            break;
        case 'error':
            icon = '<i class="fa-solid fa-circle-xmark"></i>';
            break;
        case 'warning':
            icon = '<i class="fa-solid fa-triangle-exclamation"></i>';
            break;
        default:
            icon = '<i class="fa-solid fa-circle-info"></i>';
            break;
    }

    notification.innerHTML = icon + ' ' + message;
    notification.className = 'notification ' + type;

    setTimeout(() => {
        notification.classList.add('show');
    }, 100)
}