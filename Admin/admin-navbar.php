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
        z-index: 2000;
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

    .notification.warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .notification.info {
        background-color: #cce5ff;
        color: #004085;
        border-left: 4px solid #007bff;
    }

    .admin-header {
        background: linear-gradient(270deg, #5a6877, #2f5161, #2b7070);
        background-size: 400% 400%;
        padding: 18px 20px 22px;
        text-align: center;
        animation: gradientAnimation 20s ease infinite;
    }

    @keyframes gradientAnimation {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    #logoheader {
        width: 155px;
        max-width: 100%;
        display: inline-block;
        background: #fff;
        padding: 8px;
    }

    .navbar {
        background: #33465a;
        display: flex;
        align-items: center;
        gap: 0;
        flex-wrap: wrap;
        min-height: 58px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        position: relative;
        z-index: 1000;
    }

    .navbar a {
        color: #f3f4f6;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 17px 22px;
        font-weight: 700;
        font-size: 15px;
        position: relative;
        transition: 0.2s ease;
        white-space: nowrap;
    }

    .navbar a:hover {
        background: #3d5166;
        color: #ffffff;
    }

    .navbar a.active {
        background: #25c7ae;
        color: #ffffff;
    }

    .nav-count {
        position: absolute;
        top: 4px;
        right: 8px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.18);
        line-height: 1;
    }

    a[href*="manage-users"] .nav-count {
        background: #4f7cff;
        color: #fff;
    }

    a[href*="manage-orders"] .nav-count {
        background: #31c04c;
        color: #fff;
    }

    a[href*="manage-products"] .nav-count {
        background: #ff4f64;
        color: #fff;
    }

    a[href*="best-seller"] .nav-count {
        background: #d8eb2f;
        color: #333;
    }

    a[href*="manage-inventory"] .nav-count {
        background: #f29b1f;
        color: #fff;
    }

    .nav-user {
        margin-left: auto;
        margin-right: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 10px;
        padding: 8px 12px;
    }

    .user-greeting {
        color: #fff;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
    }

    .logout-form {
        margin: 0;
    }

    .logout-btn {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.18);
        color: #fff;
        padding: 8px 14px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: 0.2s ease;
    }

    .logout-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    @media (max-width: 1100px) {
        .navbar {
            justify-content: center;
        }

        .nav-user {
            margin: 10px auto 12px;
        }
    }

    @media (max-width: 768px) {
        .notification {
            width: 90%;
            top: 10px;
            right: 5%;
        }

        .navbar {
            flex-direction: column;
            align-items: stretch;
        }

        .navbar a {
            justify-content: center;
        }

        .nav-user {
            flex-direction: column;
            margin: 12px;
        }
    }
</style>

<div id="notification" class="notification"></div>

<header class="admin-header">
    <a href="index.php">
        <img id="logoheader" src="../User/dp56vcf7.png" alt="Logo">
    </a>
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
        <i class="fa-solid fa-star"></i>
        <span>Inventory Statistics</span>
        <span class="nav-count"><?php echo $counts['products']; ?></span>
    </a>

    <a href="manage-purchase-orders.php" class="<?php echo $currentPage === 'manage-purchase-orders.php' ? 'active' : ''; ?>">
        <i class="fa-solid fa-truck-ramp-box"></i>
        <span>Quản lý nhập hàng</span>
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
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.innerHTML = '';
        }, 300);
    }, 5000);
}
</script>