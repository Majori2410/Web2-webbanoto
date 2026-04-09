<?php
include 'header.php';
include 'connect.php';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// Add this after your database connection
function checkCartItemsStatus($connect) {
    if (!isset($_SESSION['cart_notifications'])) {
        $_SESSION['cart_notifications'] = [];
    }

    $notifications = [];
    $cartItems = [];

    if (isset($_SESSION['user_id'])) {
        // For logged in users
        $query = "SELECT p.product_id, p.car_name, p.status 
                  FROM cart_items ci 
                  JOIN cart c ON ci.cart_id = c.cart_id 
                  JOIN products p ON ci.product_id = p.product_id 
                  WHERE c.user_id = ? AND c.cart_status = 'activated'";
        
        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $cartItems[] = $row;
        }
    } else {
        // For guest users
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $query = "SELECT product_id, car_name, status FROM products WHERE product_id = ?";
                $stmt = mysqli_prepare($connect, $query);
                mysqli_stmt_bind_param($stmt, "i", $item['product_id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($result) {
                    $cartItems[] = $result;
                }
            }
        }
    }

    foreach ($cartItems as $item) {
        if ($item['status'] === 'hidden' || $item['status'] === 'soldout') {
            $notification = [
                'product_id' => $item['product_id'],
                'message' => $item['status'] === 'hidden' ? 
                    "Sản phẩm '{$item['car_name']}' đã tạm thời bị ẩn." : 
                    "Sản phẩm '{$item['car_name']}' đã hết hàng.",
                'type' => $item['status']
            ];
            
            // Only add if notification doesn't already exist
            if (!in_array($notification, $_SESSION['cart_notifications'])) {
                $_SESSION['cart_notifications'][] = $notification;
            }
        }
    }
}
// Get cart items
$cartItems = [];

$totalAmount = 0;
$totalItems = 0;
$unavailableItems = 0;

foreach ($cartItems as $item) {
    if ($item['status'] !== 'hidden' && $item['status'] !== 'soldout') {
        $totalItems += $item['quantity'];
        $totalAmount += $item['price'] * $item['quantity'];
    } else {
        $unavailableItems++;
    }
}
// At the beginning of the file, update the guest user cart items fetching
if (!isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    $cartItems = [];
    foreach ($_SESSION['cart'] as $item) {
        // Get updated product information including status
        $query = "SELECT p.*, ct.type_name 
                 FROM products p 
                 LEFT JOIN car_types ct ON p.brand_id = ct.type_id 
                 WHERE p.product_id = ?";

        $stmt = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param($stmt, "i", $item['product_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($product = mysqli_fetch_assoc($result)) {
            $cartItem = array_merge($item, [
                'type_name' => $product['type_name'] ?? 'N/A',
                'status' => $product['status'] // Include status in cart item
            ]);

            // Update session cart item status
            foreach ($_SESSION['cart'] as &$sessionItem) {
                if ($sessionItem['product_id'] == $item['product_id']) {
                    $sessionItem['status'] = $product['status'];
                    break;
                }
            }

            $cartItems[] = $cartItem;
            if ($product['status'] !== 'hidden' && $product['status'] !== 'soldout') {
                $totalItems += $item['quantity'];
                $totalAmount += $item['price'] * $item['quantity'];
            }
        }
    }
}

else if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // First, check if user has an active cart
    $check_cart = "SELECT cart_id FROM cart WHERE user_id = ? AND cart_status = 'activated'";
    $stmt = mysqli_prepare($connect, $check_cart);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $cart_result = mysqli_stmt_get_result($stmt);

    if (!mysqli_fetch_assoc($cart_result)) {
        // Create new cart if none exists
        $create_cart = "INSERT INTO cart (user_id, cart_status) VALUES (?, 'activated')";
        $stmt = mysqli_prepare($connect, $create_cart);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
    }

    // Now get cart items
    $query = "SELECT ci.*, p.*, ct.type_name 
              FROM cart_items ci
              JOIN cart c ON ci.cart_id = c.cart_id
              JOIN products p ON ci.product_id = p.product_id
              LEFT JOIN car_types ct ON p.brand_id = ct.type_id
              WHERE c.user_id = ? AND c.cart_status = 'activated'";

    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $cartItems[] = $row;
        $totalItems += $row['quantity'];
        $totalAmount += $row['price'] * $row['quantity'];
    }
} else {
    // Get items from session for guest user
    $cartItems = [];
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            // Get brand information for each product
            $query = "SELECT p.*, ct.type_name 
                     FROM products p 
                     LEFT JOIN car_types ct ON p.brand_id = ct.type_id 
                     WHERE p.product_id = ?";

            $stmt = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param($stmt, "i", $item['product_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($product = mysqli_fetch_assoc($result)) {
                $cartItem = array_merge($item, [
                    'type_name' => $product['type_name'] ?? 'N/A'
                ]);
                $cartItems[] = $cartItem;
                $totalItems += $item['quantity'];
                $totalAmount += $item['price'] * $item['quantity'];
            }
        }
    }
}
checkCartItemsStatus($connect);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng</title>
    <script src="cart.js"></script>
    <link rel="icon" href="dp56vcf7.png" type="image/png">
    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>

</head>
<style>
    /* Cart Page Container */
    .cart {
        --cart-primary: #007bff;
        --cart-secondary: #f8f9fa;
        --cart-accent: #0056b3;
        padding: 40px 0;
        background-color: #efefef;
    }

    /* Progress Bar Section */
    .cart-top-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .cart-top {
        height: 2px;
        width: 70%;
        background-color: #909091;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 30px auto;
        max-width: 840px;
        position: relative;
    }

    .cart-top-item {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #fff;
        position: relative;
        transition: all 0.3s ease;
        cursor: pointer;
        z-index: 2;
    }

    .cart-top-item i {
        color: #666;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .cart-top-item.active {
        border-color: var(--cart-primary);
        background-color: var(--cart-primary);
    }

    .cart-top-item.active i {
        color: #fff;
    }

    /* Cart Content Layout */
    .container {
        margin: 0 auto;
        padding: 0 20px;
    }

    .cart-content-row {
        display: flex;
        gap: 30px;
        margin-top: 40px;
    }

    /* Cart Items Table */
    .cart-content-left {
        flex: 2;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .cart-content-left table {
        width: 100%;
        border-collapse: collapse;
    }

    .cart-content-left th {
        padding: 15px;
        text-transform: uppercase;
        font-size: 0.9rem;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        text-align: left;
    }

    .cart-content-left td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }

    .cart-content-left img {
        width: 100px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        transition: transform 0.3s ease;
    }

    .cart-content-left img:hover {
        transform: scale(1.05);
    }

    /* Quantity Controls */
    .cart-content-left input[type="number"] {
        width: 70px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
    }

    /* Cart Summary */
    .cart-content-right {
        flex: 1;
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        height: fit-content;
    }

    .cart-content-right table {
        width: 100%;
        margin-bottom: 20px;
    }

    .cart-content-right th {
        padding: 15px;
        text-align: center;
        background-color: #f8f9fa;
        color: #495057;
        font-size: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .cart-content-right td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }

    /* Action Buttons */
    .cart-content-right-button {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .cart-content-right-button button {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    #continue-shopping {
        background-color: #6c757d;
        color: white;
    }

    #checkout-button {
        background-color: var(--cart-primary);
        color: white;
    }

    #continue-shopping:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }

    #checkout-button:hover {
        background-color: var(--cart-accent);
        transform: translateY(-2px);
    }

    /* Remove Item Button */
    .remove-item {
        color: #dc3545;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .remove-item:hover {
        color: #c82333;
        transform: scale(1.1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .cart-content-row {
            flex-direction: column;
        }

        .cart-content-left {
            overflow-x: auto;
        }

        .cart-content-left table {
            min-width: 600px;
        }

        .cart-top {
            width: 90%;
        }
    }

    /* Preserve Header Styles */
    .header-container {
        --header-bg: #f8f9fa;
        --header-text: #495057;
    }

    /* Preserve Cart Original Styles */
    .cart-top-cart {
        border: 1px solid rgb(7, 7, 7);
    }

    .cart-top-cart i {
        color: black;
    }
</style>
<style>
    h1 {
        position: relative;
        padding: 0;
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bold;
        color: #666;
        font-size: 40px;
        -webkit-transition: all 0.4s ease 0s;
        -o-transition: all 0.4s ease 0s;
        transition: all 0.4s ease 0s;
        height: 50px;
    }

    h1 em {
        font-style: normal;
        font-weight: 600;
    }
</style>
<style>
    .cart-content-left a {
        text-decoration: none;
        color: gray;
        font-weight: bold;
        text-transform: uppercase;
    }
</style>
<style>
    /* Update the cart image styling */
    .cart-content-left img {
        width: 150px;
        height: 100px;
        object-fit: contain;
        border-radius: 8px;
        transition: transform 0.3s ease;
        background-color: #f8f9fa;
        padding: 5px;
    }

    .cart-content-left img:hover {
        transform: scale(1.1);
    }

    .cart-content-left td:first-child {
        padding: 10px;
        width: 160px;
    }

    .cart-icon {
        margin-right: 8px;
        color: #666;
    }

    .price-icon {
        color: #28a745;
    }

    .quantity-icon {
        color: #007bff;
    }

    .total-icon {
        color: #17a2b8;
    }

    .empty-cart-message {
        text-align: center;
        padding: 40px 0;
    }

    .empty-cart-message i:first-child {
        font-size: 30px;
        color: #6c757d;
        margin-bottom: 15px;
    }

    #cart-icon {
        padding-top: 20px;
    }
</style>
<style>
    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .quantity-btn:hover {
        background: #f8f9fa;
        border-color: #007bff;
        color: #007bff;
    }

    .quantity-input {
        width: 60px !important;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px;
        margin: 0 5px;
    }

    .remove-item {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        margin: 0 auto;
        border: 1px solid #dc3545;
        border-radius: 50%;
        color: #dc3545;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .remove-item:hover {
        background: #dc3545;
        color: white;
    }

    .delete-all-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-left: auto;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .delete-all-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .delete-all-btn i {
        font-size: 14px;
    }
</style>
<style>
    .product-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #007bff;
    }

    .delete-selected-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
        transition: all 0.3s ease;
    }

    .delete-selected-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .delete-buttons-container {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
    }

    .confirm-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .confirm-modal-content {
        position: relative;
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        width: 90%;
        max-width: 400px;
        border-radius: 8px;
        text-align: center;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-100px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .confirm-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
</style>
<style>
    .delete-buttons-container {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin: 15px;
        padding-right: 15px;
        position: relative;
        z-index: 1;
    }

    .cart-content-left table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .cart-content-left th:first-child,
    .cart-content-left td:first-child {
        width: 50px;
        text-align: center;
        padding: 15px 10px;
        vertical-align: middle;
    }

    .product-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #007bff;
        margin: 0 auto;
        display: block;
    }

    .cart-content-left th {
        background-color: #f8f9fa;
        padding: 15px;
        text-transform: uppercase;
        font-size: 0.9rem;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        text-align: center;
    }

    .cart-content-left td {
        text-align: center;
        vertical-align: middle;
        padding: 15px;
    }

    .delete-selected-btn,
    .delete-all-btn {
        min-width: 120px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .delete-selected-btn {
        background: #dc3545;
        color: white;
    }

    .delete-all-btn {
        background: #dc3545;
        color: white;
    }

    .delete-selected-btn:hover,
    .delete-all-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
    }
</style>
<style>
    .empty-cart-message i {
        font-size: 48px;
        color: #6c757d;
    }

    .empty-cart-message p {
        font-size: 18px;
        color: #495057;
        margin: 10px 0;
    }

    .empty-cart-message #continue-shopping {
        min-width: 200px;
        height: 45px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 15px;
        padding: 0 20px;
    }

    .empty-cart-message #continue-shopping:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-cart-message #continue-shopping i {
        font-size: 16px;
        color: white;
        margin: 0;
    }
</style>
<style>
    .empty-cart-message i {
        font-size: 48px;
        color: #6c757d;
    }

    .empty-cart-message p {
        font-size: 18px;
        color: #495057;
        margin: 10px 0;
    }

    .empty-cart-message #continue-shopping {
        min-width: 200px;
        height: 45px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 20px auto 0;
        padding: 0 20px;
    }

    .empty-cart-message #continue-shopping:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-cart-message #continue-shopping i {
        font-size: 16px;
        color: white;
        margin: 0;
    }

    .cart-content-left td[colspan="5"] {
        padding: 40px 20px;
    }
</style>
<style>
    .delete-selected-btn,
    .delete-all-btn {
        min-width: 120px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .delete-selected-btn {
        background: #dc3545;
        color: white;
    }

    .delete-all-btn {
        background: #dc3545;
        color: white;
        margin: 0;
    }

    .delete-selected-btn:hover,
    .delete-all-btn:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .cart-content-left {
        flex: 3;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .cart-content-left th {
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    .cart-content-left tr {
        border-radius: 12px;
    }

    .delete-buttons-container {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 60px;
        padding: 0;
        border-top: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 0 0 12px 12px;
    }

    .container {
        margin-left: 300px;
        margin-right: 300px;
    }
</style>
<style>
    .cart-content-left td:nth-child(4) {
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.9em;
    }

    .cart-content-left th:nth-child(1) {
        width: 50px;
    }

    .cart-content-left th:nth-child(2) {
        width: 160px;
    }

    .cart-content-left th:nth-child(3) {
        width: auto;
    }

    .cart-content-left th:nth-child(4) {
        width: 120px;
    }

    .cart-content-left th:nth-child(5) {
        width: 120px;
    }

    .cart-content-left th:nth-child(6) {
        width: 120px;
    }

    .cart-content-left th:nth-child(7) {
        width: 80px;
    }

    #price {
        color: #008000;
    }
</style>
<style>
    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 120px;
        height: 36px;
        border: 1px solid #ddd;
        border-radius: 20px;
        background: white;
        margin: 0 auto;
    }

    .quantity-btn {
        width: 32px;
        height: 32px;
        border: none;
        background: transparent;
        color: #007bff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .quantity-btn:hover {
        color: #0056b3;
        transform: scale(1.1);
    }

    .quantity-input {
        width: 40px !important;
        border: none !important;
        text-align: center;
        font-weight: 500;
        color: #495057;
        padding: 0 !important;
        margin: 0 4px !important;
        -moz-appearance: textfield;
    }

    .quantity-input::-webkit-outer-spin-button,
    .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .delete-buttons-container {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding: 15px 20px;
        border-top: 1px solid #e9ecef;
        background: linear-gradient(to bottom, #f8f9fa, #fff);
        border-radius: 0 0 12px 12px;
        box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.02);
    }

    .delete-selected-btn,
    .delete-all-btn {
        min-width: 130px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        background: #fff;
        color: #dc3545;
        border: 1px solid #dc3545;
    }

    .delete-selected-btn:hover,
    .delete-all-btn:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
    }

    .delete-buttons-container i {
        font-size: 14px;
    }

    .cart {
        padding-bottom: 10vh;
    }
</style>

<style>
    .cart-top-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 40px;
    }

    .cart-top {
        height: 2px;
        width: 70%;
        background-color: #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 30px auto;
        max-width: 840px;
        position: relative;
    }

    .cart-top::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        height: 2px;
        width: 0%;
        background-color: #4CAF50;
        transition: width 0.3s ease;
        z-index: 1;
    }

    .cart-top-item {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #fff;
        position: relative;
        transition: all 0.3s ease;
        z-index: 2;
    }

    .cart-top-item i {
        color: #666;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .cart-top-item.active {
        border-color: #4CAF50;
        background-color: #4CAF50;
    }

    .cart-top-item.active i {
        color: #fff;
    }

    .cart-top-item:hover {
        transform: scale(1.1);
    }

    .cart-top-item a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .cart-top-cart.active~.cart-top-address .cart-top-item,
    .cart-top-cart.active~.cart-top-payment .cart-top-item {
        border-color: #ddd;
        background-color: #fff;
    }

    .cart-top-cart.active~.cart-top-address .cart-top-item i,
    .cart-top-cart.active~.cart-top-payment .cart-top-item i {
        color: #666;
    }

    .cart-content-right {
        font-size: 14px;
    }

    .thr {
        width: 50%;
    }
</style>
<style>
    .cart-content-right table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
    }

    .cart-content-right td.thr {
        width: 50%;
        padding-right: 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
        color: #495057;
    }

    .cart-content-right td:last-child {
        width: 50%;
        text-align: right;
        padding-left: 15px;
        white-space: nowrap;
    }

    .cart-content-right th {
        padding: 15px;
        text-align: center;
        background-color: #f8f9fa;
        color: #495057;
        font-size: 1rem;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
    }

    .cart-content-right td.thr i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
</style>

<style>
.disabled-item {
    opacity: 0.7;
    background-color: #f8f9fa;
    position: relative;
}

.disabled-item::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.5);
    pointer-events: none;
}

.disabled-link {
    color: #6c757d;
    text-decoration: none;
    cursor: not-allowed;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    margin-top: 5px;
    font-weight: 500;
}

.status-hidden {
    background-color: #6c757d;
    color: white;
}

.status-soldout {
    background-color: #dc3545;
    color: white;
}

.grayscale {
    filter: grayscale(100%);
}

.cart-warning {
    position: relative;
    display: inline-block;
}

.cart-warning .tooltip {
    visibility: hidden;
    background-color: #dc3545;
    color: white;
    text-align: center;
    padding: 8px 12px;
    border-radius: 4px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.3s;
}

.cart-warning:hover .tooltip {
    visibility: visible;
    opacity: 1;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
</style>
<style>
.cart-notifications {
    margin: 20px 0;
    padding: 0 20px;
}

.cart-alert {
    display: flex;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    animation: slideIn 0.3s ease;
}

.cart-alert.hidden {
    background-color: #e2e3e5;
    border-left-color: #6c757d;
}

.cart-alert.soldout {
    background-color: #f8d7da;
    border-left-color: #dc3545;
}

.cart-alert i:first-child {
    margin-right: 10px;
    font-size: 1.2em;
}

.cart-alert .dismiss-btn {
    margin-left: auto;
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    transition: all 0.3s ease;
}

.cart-alert .dismiss-btn:hover {
    color: #333;
    transform: scale(1.1);
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    to {
        transform: translateY(-20px);
        opacity: 0;
    }
}
.eight {
    height: auto;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin: 20px auto;
    padding: 20px 40px;
    width: fit-content;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.eight h1 {
    font-family: Arial, Helvetica, sans-serif;
    font-weight: 600;
    font-size: 26px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0;
    padding: 0;
    background-size: 200% auto;
    color: #2c3e50;
}

.eight h1 span {
    display: inline-block;
    opacity: 0;
    transform: translateY(20px);
    animation: revealLetters 0.5s ease forwards;
}

@keyframes gradientText {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}

@keyframes revealLetters {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.eight::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(90, 90, 90, 0.8),
        transparent
    );
    animation: highlightSweep 3s ease-in-out infinite;
}

@keyframes highlightSweep {
    100% { left: 200%; }
}

body.dark-theme .cart {
    background-color: #33475C;
}

body.dark-theme .cart-top {
    background-color: #445566;
}

body.dark-theme .cart-top-item {
    background-color: #2c3e50;
    border-color: #445566;
}

body.dark-theme .cart-top-item i {
    color: #bdc3c7;
}

body.dark-theme .cart-top-item.active {
    background-color: #3498db;
    border-color: #2980b9;
}

body.dark-theme .cart-top-item.active i {
    color: #fff;
}

body.dark-theme .eight {
    background-color: #2c3e50;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-theme .eight h1 {
    color: #ecf0f1;
}

body.dark-theme .cart-content-left {
    background-color: #2c3e50;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-theme .cart-content-left th {
    background-color: #34495e;
    color: #ecf0f1;
    border-bottom-color: #445566;
}

body.dark-theme .cart-content-left td {
    border-bottom-color: #445566;
    color: #ecf0f1;
}

body.dark-theme .cart-content-left img {
    background-color: #34495e;
    border: 1px solid #445566;
}

body.dark-theme .cart-content-left a {
    color: #3498db;
}

body.dark-theme .cart-content-left a:hover {
    color: #2980b9;
}

body.dark-theme .quantity-control {
    background-color: #34495e;
    border-color: #445566;
}

body.dark-theme .quantity-btn {
    color: #3498db;
    background-color: transparent;
}

body.dark-theme .quantity-input {
    background-color: #34495e;
    color: #ecf0f1;
    border-color: #445566;
}

body.dark-theme #price {
    color: #2ecc71;
}

body.dark-theme .delete-buttons-container {
    background: #34495e;
    border-top-color: #445566;
}

body.dark-theme .delete-selected-btn,
body.dark-theme .delete-all-btn {
    background-color: #34495e;
    color: #e74c3c;
    border: 1px solid #e74c3c;
}

body.dark-theme .delete-selected-btn:hover,
body.dark-theme .delete-all-btn:hover {
    background-color: #e74c3c;
    color: #ecf0f1;
}

body.dark-theme .cart-content-right {
    background-color: #2c3e50;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-theme .cart-content-right th {
    background-color: #34495e;
    color: #ecf0f1;
    border-bottom-color: #445566;
}

body.dark-theme .cart-content-right td {
    color: #ecf0f1;
    border-bottom-color: #445566;
}

body.dark-theme .cart-content-right .thr {
    color: #bdc3c7;
}

body.dark-theme .cart-content-right i {
    color: #3498db;
}

body.dark-theme #continue-shopping {
    background-color: #34495e;
    color: #ecf0f1;
}

body.dark-theme #checkout-button {
    background-color: #3498db;
    color: #ecf0f1;
}

body.dark-theme #continue-shopping:hover {
    background-color: #2c3e50;
}

body.dark-theme #checkout-button:hover {
    background-color: #2980b9;
}

body.dark-theme .empty-cart-message {
    color: #bdc3c7;
}

body.dark-theme .empty-cart-message i {
    color: #34495e;
}

body.dark-theme .status-badge.status-hidden {
    background-color: #7f8c8d;
}

body.dark-theme .status-badge.status-soldout {
    background-color: #e74c3c;
}

body.dark-theme .disabled-item {
    background-color: #34495e;
}

body.dark-theme .disabled-item::after {
    background: rgba(26, 26, 26, 0.5);
}

body.dark-theme .cart-alert {
    background-color: #34495e;
    border-left-color: #3498db;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

body.dark-theme .cart-alert.hidden {
    background-color: #2c3e50;
    border-left-color: #7f8c8d;
    color:#e0e0e0;
}

body.dark-theme .cart-alert.soldout {
    background-color: #2c3e50;
    border-left-color: #e74c3c;
    color:#e74c3c;
}

body.dark-theme .cart-alert .dismiss-btn {
    color: #bdc3c7;
}

body.dark-theme .cart-alert .dismiss-btn:hover {
    color: #ecf0f1;
}

body.dark-theme .confirm-modal-content {
    background-color: #2c3e50;
    color: #ecf0f1;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

body.dark-theme .confirm-modal-buttons button {
    background-color: #34495e;
    color: #ecf0f1;
    border: 1px solid #445566;
}

body.dark-theme .confirm-modal-buttons button:hover {
    background-color: #3498db;
}
</style>
<body>

    <section class="cart">
        <div class="container">
            <div class="cart-top-wrap">
                <div class="cart-top">
                    <div class="cart-top-cart cart-top-item active">
                        <a href="cart.php">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </a>
                    </div>

                    <div class="cart-top-address cart-top-item">
                        <a href="delivery.php">
                            <i class="fa-solid fa-location-dot"></i>
                        </a>
                    </div>

                    <div class="cart-top-payment cart-top-item">
                        <a href="payment.php">
                            <i class="fa-solid fa-money-check"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="eight">
                <h1>
                    <i class="fa-solid fa-cart-shopping"></i>
                Giỏ Hàng 
                </h1>
            </div>

            <?php if (!empty($_SESSION['cart_notifications'])): ?>
                <div class="cart-notifications">
                    <?php foreach ($_SESSION['cart_notifications'] as $notification): ?>
                        <div class="cart-alert <?php echo $notification['type']; ?>">
                            <i class="fas <?php echo $notification['type'] === 'hidden' ? 'fa-eye-slash' : 'fa-times-circle'; ?>"></i>
                            <span><?php echo $notification['message']; ?></span>
                            <button onclick="dismissNotification(<?php echo $notification['product_id']; ?>)" class="dismiss-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="cart-content-row">
                <div class="cart-content-left">
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" class="product-checkbox"
                                        onclick="toggleAllCheckboxes()"></th>
                                <th><i class="fa-solid fa-image cart-icon"></i>Hình ảnh xe</th>
                                <th><i class="fa-solid fa-tag cart-icon"></i>Tên </th>
                                <th><i class="fa-solid fa-car cart-icon"></i>Hãng</th>
                                <th><i class="fa-solid fa-sort-amount-up cart-icon"></i>Số lượng</th>
                                <th><i class="fa-solid fa-money-bill cart-icon"></i>Đơn giá</th>
                                <th><i class="fa-solid fa-trash"></i>&nbsp;Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cartItems)): ?>
                                <?php foreach ($cartItems as $item): ?>
    <tr class="<?php echo ($item['status'] === 'hidden' || $item['status'] === 'soldout') ? 'disabled-item' : ''; ?>">
        <td>
            <input type="checkbox" class="product-checkbox" 
                   value="<?php echo $item['product_id']; ?>"
                   <?php echo ($item['status'] === 'hidden' || $item['status'] === 'soldout') ? 'disabled' : ''; ?>
                   onchange="updateDeleteSelectedButton()">
        </td>
        <td>
            <img src="<?php echo htmlspecialchars($item['image_link']); ?>" 
                 alt="<?php echo htmlspecialchars($item['car_name']); ?>"
                 class="<?php echo ($item['status'] === 'hidden' || $item['status'] === 'soldout') ? 'grayscale' : ''; ?>">
        </td>
        <td>
            <a href="car-details.php?name=<?php echo urlencode($item['car_name']); ?>"
               class="<?php echo ($item['status'] === 'hidden' || $item['status'] === 'soldout') ? 'disabled-link' : ''; ?>">
                <?php echo htmlspecialchars($item['car_name']); ?>
            </a>
            <?php if ($item['status'] === 'hidden'): ?>
                <div class="status-badge status-hidden">
                    <i class="fas fa-eye-slash"></i> Sản phẩm tạm thời bị ẩn
                </div>
            <?php elseif ($item['status'] === 'soldout'): ?>
                <div class="status-badge status-soldout">
                    <i class="fas fa-times-circle"></i> Sản phẩm tạm thời hết hàng
                </div>
            <?php endif; ?>
        </td>

        <td>
            <?php echo htmlspecialchars($item['type_name'] ?? 'N/A'); ?>
        </td>

        <td>
            <div class="quantity-control">
                <button class="quantity-btn minus"
                    onclick="updateQuantity(<?php echo $item['product_id']; ?>, 'decrease')">
                    <i class="fa-solid fa-minus"></i>
                </button>
                <input type="number" class="quantity-input"
                    value="<?php echo $item['quantity']; ?>"
                    min="1"
                    max="<?php echo max(1, (int)$item['remain_quantity']); ?>"
                    data-id="<?php echo $item['product_id']; ?>"
                    data-stock="<?php echo (int)$item['remain_quantity']; ?>"
                    oninput="handleQuantityChange(this)">
                <button class="quantity-btn plus"
                    onclick="updateQuantity(<?php echo $item['product_id']; ?>, 'increase')">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <div style="margin-top: 6px; font-size: 12px; color: #666;">
                Tồn kho: <?php echo (int)$item['remain_quantity']; ?>
            </div>
        </td>

        <td id="price">
            <p>
                <?php echo number_format($item['price'], 0, ',', '.'); ?> ₫
            </p>
        </td>
        <td>
            <span class="remove-item"
                onclick="removeCartItem(<?php echo $item['product_id']; ?>)">X</span>
        </td>
    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-cart-message">
                                        <i class="fa-solid fa-shopping-cart" id="cart-icon"></i>
                                        <p>Giỏ hàng của bạn đang trống</p>
                                        <button id="continue-shopping" onclick="window.location.href='index.php'">
                                            <i class="fa-solid fa-home"></i> Tiếp tục mua sắm
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="delete-buttons-container">
                        <button class="delete-selected-btn" onclick="deleteSelectedItems()" id="delete-selected-btn"
                            style="display: none;">
                            <i class="fa-solid fa-trash-alt"></i> Xóa đã chọn
                        </button>
                        <button class="delete-all-btn" onclick="confirmDeleteAll()">
                            <i class="fa-solid fa-trash-alt"></i> Xóa tất cả
                        </button>
                    </div>
                </div>
                <div class="cart-content-right">
                    <table style="table-layout: fixed; border-collapse: collapse;">
                        <tr>
                            <th colspan="2">
                                <i class="fa-solid fa-shopping-cart cart-icon"></i>
                                TỔNG TIỀN GIỎ HÀNG
                            </th>
                        </tr>
                        <tr>
                            <td class="thr">
                                <i class="fa-solid fa-cubes quantity-icon"></i>
                                TỔNG SẢN PHẨM
                            </td>
                            <td id="total-items">
                                <?php echo $totalItems; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="thr">
                                <i class="fa-solid fa-calculator price-icon"></i>
                                TỔNG TIỀN
                            </td>
                            <td>
                                <p id="total-amount">
                                    <?php echo number_format($totalAmount, 0, ',', '.'); ?> ₫
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td class="thr">
                                <i class="fa-solid fa-receipt total-icon"></i>
                                TẠM TÍNH
                            </td>
                            <td>
                                <p style=" font-weight: bold;">
                                    <?php echo number_format($totalAmount, 0, ',', '.'); ?> ₫
                                </p>
                            </td>
                        </tr>
                    </table>
                    <div class="cart-content-right-button">
                        <button id="continue-shopping" onclick="history.back()">
                            <i class="fas fa-arrow-left"></i>
                            Trở về
                        </button>
                        <button id="checkout-button" onclick="proceedToCheckout()">
                            Tiếp tục
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="deleteConfirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <h3>Xác nhận xóa</h3>
            <p id="deleteConfirmMessage"></p>
            <div class="confirm-modal-buttons">
                <button class="confirm-btn" onclick="executeDelete()">Xóa</button>
                <button class="cancel-btn" onclick="closeConfirmModal()">Hủy</button>
            </div>
        </div>
    </div>
    <script>
    let deleteType = '';
    let selectedItems = [];

    // Quantity Management
    function handleQuantityChange(input) {
        const productId = input.dataset.id;
        let quantity = parseInt(input.value);
        const stock = parseInt(input.dataset.stock) || 0;

        if (isNaN(quantity) || quantity < 1) {
            showNotification('Số lượng không thể nhỏ hơn 1', 'error');
            input.value = 1;
            return;
        }

        if (stock <= 0) {
            showNotification('Sản phẩm này hiện không còn hàng', 'error');
            input.value = 1;
            return;
        }

        if (quantity > stock) {
            showNotification(`Số lượng không thể vượt quá tồn kho hiện có (${stock})`, 'warning');
            input.value = stock;
            quantity = stock;
        }

        if (window.quantityUpdateTimeout) {
            clearTimeout(window.quantityUpdateTimeout);
        }

        window.quantityUpdateTimeout = setTimeout(() => {
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Đã cập nhật số lượng', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra', 'error');
            });
        }, 1000);
    }

    function updateQuantity(productId, action) {
        const input = document.querySelector(`input[data-id="${productId}"]`);
        let value = parseInt(input.value) || 1;
        const stock = parseInt(input.dataset.stock) || 0;

        if (action === 'increase') {
            if (value >= stock) {
                showNotification(`Bạn chỉ có thể mua tối đa ${stock} sản phẩm theo số lượng tồn hiện có`, 'warning');
                input.value = stock;
                return;
            }
            value++;
        } else if (action === 'decrease' && value > 1) {
            value--;
        } else if (value <= 1 && action === 'decrease') {
            showNotification('Số lượng không thể nhỏ hơn 1', 'error');
            return;
        }

        input.value = value;
        handleQuantityChange(input);
    }

    // Delete Management
    function removeCartItem(productId) {
        showConfirmModal('Bạn có chắc muốn xóa sản phẩm này?', 'single');
        selectedItems = [productId];
    }

    function confirmDeleteAll() {
        showConfirmModal('Bạn có chắc muốn xóa tất cả sản phẩm?', 'all');
    }

    function deleteSelectedItems() {
        if (selectedItems.length > 0) {
            showConfirmModal('Bạn có chắc muốn xóa các sản phẩm đã chọn?', 'selected');
        }
    }

    // Modal Management
    function showConfirmModal(message, type) {
        deleteType = type;
        document.getElementById('deleteConfirmMessage').textContent = message;
        document.getElementById('deleteConfirmModal').style.display = 'block';
    }

    function closeConfirmModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
    }

    function executeDelete() {
        closeConfirmModal();
        let url, data;

        switch (deleteType) {
            case 'all':
                url = 'remove_all_cart_items.php';
                data = null;
                break;
            case 'selected':
            case 'single':
                url = deleteType === 'single' ? 'remove_cart_item.php' : 'remove_selected_items.php';
                data = deleteType === 'single' ? 
                    `product_id=${selectedItems[0]}` :
                    JSON.stringify({ items: selectedItems });
                break;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': deleteType === 'single' ? 
                    'application/x-www-form-urlencoded' : 
                    'application/json',
            },
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Đã xóa sản phẩm thành công', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Có lỗi xảy ra', 'error');
        });
    }

    // Checkbox Management
    function toggleAllCheckboxes() {
        const mainCheckbox = document.getElementById('select-all');
        const checkboxes = document.getElementsByClassName('product-checkbox');
        Array.from(checkboxes).forEach(checkbox => {
            if (checkbox !== mainCheckbox) {
                checkbox.checked = mainCheckbox.checked;
            }
        });
        updateDeleteSelectedButton();
    }

    function updateDeleteSelectedButton() {
        const checkboxes = document.getElementsByClassName('product-checkbox');
        const deleteSelectedBtn = document.getElementById('delete-selected-btn');
        selectedItems = [];

        Array.from(checkboxes).forEach(checkbox => {
            if (checkbox.checked && checkbox.value) {
                selectedItems.push(checkbox.value);
            }
        });

        if (deleteSelectedBtn) {
            deleteSelectedBtn.style.display = selectedItems.length > 0 ? 'flex' : 'none';
        }
    }

    function proceedToCheckout() {
        const unavailableItems = document.querySelectorAll('.disabled-item').length;

        if (unavailableItems > 0) {
            showNotification(
                'Vui lòng xóa các sản phẩm không có sẵn khỏi giỏ hàng trước khi tiếp tục', 
                'warning'
            );
            highlightUnavailableItems();
            return;
        }

        const totalItems = <?php echo $totalItems; ?>;
        if (totalItems > 0) {
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = 'delivery.php';
            <?php else: ?>
                showNotification('Vui lòng đăng nhập để tiếp tục', 'warning');
                setTimeout(() => window.location.href = 'login.php', 1500);
            <?php endif; ?>
        } else {
            showNotification('Giỏ hàng của bạn đang trống!', 'info');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDeleteSelectedButton();

        window.onclick = function(event) {
            if (event.target === document.getElementById('deleteConfirmModal')) {
                closeConfirmModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeConfirmModal();
            }
        });
    });

    function highlightUnavailableItems() {
        const unavailableItems = document.querySelectorAll('.disabled-item');
        unavailableItems.forEach(item => {
            item.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                item.style.animation = '';
            }, 500);
        });
    }
    </script>

    <script>
    function dismissNotification(productId) {
        const notification = event.target.closest('.cart-alert');
        notification.style.animation = 'slideOut 0.3s ease forwards';

        setTimeout(() => {
            fetch('dismiss_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notification.remove();
                }
            });
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const title = document.querySelector('.eight h1');
        const text = title.textContent.trim();
        title.innerHTML = '';

        text.split('').forEach((letter, index) => {
            const span = document.createElement('span');
            span.textContent = letter === ' ' ? '\u00A0' : letter;
            span.style.display = 'inline-block';
            span.style.opacity = '0';
            span.style.transform = 'translateY(20px)';
            span.style.transition = `all 0.5s ease ${index * 0.1}s`;
            title.appendChild(span);

            setTimeout(() => {
                span.style.opacity = '1';
                span.style.transform = 'translateY(0)';
            }, 100);
        });
    });

    let $color;
    const titleContainer = document.querySelector('.eight');
    titleContainer.addEventListener('mouseenter', () => {
        const letters = titleContainer.querySelectorAll('h1 span');
        letters.forEach((letter, index) => {
            letter.style.transform = 'translateY(-5px)';
            $color = letter.style.color;
            letter.style.color = '#007bff';
            letter.style.transition = `all 0.3s ease ${index * 0.05}s`;
        });
    });

    titleContainer.addEventListener('mouseleave', () => {
        const letters = titleContainer.querySelectorAll('h1 span');
        letters.forEach((letter, index) => {
            letter.style.transform = 'translateY(0)';
            letter.style.color = $color;
            letter.style.transition = `all 0.3s ease ${index * 0.05}s`;
        });
    });
    </script>
</body>

</html>
<?php
include 'footer.php';
?>