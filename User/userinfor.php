<?php
include 'header.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$currentUsername = $_SESSION['username'];

// Load latest full_name and address from database
$query = "SELECT address, full_name FROM users_acc WHERE username = '$currentUsername'";
$result = mysqli_query($connect, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['address'] = $row['address'];
}

// Add edit form handling
if (isset($_POST['edit_profile'])) {
    include 'connect.php';

    $oldUsername = $_SESSION['username'];

    $username = mysqli_real_escape_string($connect, $_POST['username']);
    $email = mysqli_real_escape_string($connect, $_POST['email']);
    $phone_num = mysqli_real_escape_string($connect, $_POST['phone_num']);
    $full_name = mysqli_real_escape_string($connect, $_POST['full_name']);
    $address = mysqli_real_escape_string($connect, $_POST['address']);

    $sql = "UPDATE users_acc SET 
        username = '$username',
        email = '$email',
        phone_num = '$phone_num',
        full_name = '$full_name',
        address = '$address'";

    // Keep same password logic as login.php
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($connect, $_POST['password']);
        $sql .= ", password = '$password'";
    }

    $sql .= " WHERE username = '$oldUsername'";

    if (mysqli_query($connect, $sql)) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['phone_num'] = $phone_num;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['address'] = $address;

        if (!empty($_POST['password'])) {
            $_SESSION['password'] = $_POST['password'];
        }

        echo "<script>showNotification('Cập nhật thông tin thành công!', 'success');</script>";
        echo "<script>window.location.href='userinfor.php';</script>";
    } else {
        echo "<script>showNotification('Lỗi cập nhật thông tin: " . mysqli_error($connect) . "', 'error');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="dp56vcf7.png" type="image/png">
    <title>Thông tin người dùng</title>
    <style>
        .user-container {

            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }

        .user-info {
            margin-bottom: 2rem;
        }

        .info-row {
            display: flex;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
            color: #666;
        }

        .info-value {
            flex: 1;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: bold;
        }

        .status-activated {
            background-color: #28a745;
            color: white;
        }

        .status-banned {
            background-color: #dc3545;
            color: white;
        }

        .status-disabled {
            background-color: #6c757d;
            color: white;
        }

        @media (max-width: 768px) {
            .user-container {
                margin: 1rem;
                padding: 1rem;
            }

            .info-row {
                flex-direction: column;
                gap: 0.5rem;
            }

            .info-label {
                width: 100%;
            }

            .nav-links {
                flex-direction: column;
            }

            .nav-link {
                width: 100%;
                justify-content: center;
            }
        }

        main {
            background-color: #efefef;
        }

        body {
            background-color: #efefef;

        }

        header {
            background-color: #ffffff;
        }
    </style>

    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #dc3545;
        }
    </style>
    <style>
        .edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 1rem 0;
        }

        .edit-btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .edit-btn:active {
            transform: translateY(0);
        }
    </style>
    <style>
        .edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .edit-btn:hover {
            background-color: #0056b3;
        }

        .edit-form {
            display: none;
            margin-top: 1rem;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .edit-form.show {
            display: block;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #218838;
        }

        #edit {
            background-color: #fff;
            border: none;
            height: 37px;
        }

        #edit:hover {
            background-color: rgb(241, 241, 241);
        }
    </style>
    <style>
        .modal-content {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            padding: 2.5rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            position: relative;
        }

        .modal-content h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #00d2ff);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input {
            width: 90%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            color: #495057;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        .form-group input:hover {
            border-color: #adb5bd;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(90deg, #0056b3, #004094);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
            transform: rotate(90deg);
        }

        .form-group input::placeholder {
            color: #adb5bd;
            opacity: 0.7;
        }

        .form-group {
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 12px;
            top: 38px;
            color: #adb5bd;
            transition: all 0.3s ease;
        }

        .form-group input {
            padding-left: 35px;
        }

        .form-group input:focus+i {
            color: #007bff;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-60px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal.show .modal-content {
            animation: modalSlideIn 0.3s ease forwards;
        }
    </style>
    <style>
        .form-group {
            margin-bottom: 20px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-group label {
            min-width: 140px;
            text-align: left;
            font-weight: 500;
            color: #495057;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input {
            flex: 1;
            padding: 12px 16px 12px 35px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            color: #495057;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group i {
            position: absolute;
            left: 170px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            transition: all 0.3s ease;
        }

        .form-group input:focus+i {
            color: #007bff;
        }

        .modal-content {
            width: 90%;
            max-width: 600px;
            padding: 2.5rem;
        }
    </style>
    <style>
        .page-title-container {
            position: relative;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .page-title-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #00d2ff, #007bff);
            background-size: 200% 100%;
            animation: gradientSlide 3s linear infinite;
        }

        h2.page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            position: relative;
            transition: all 0.3s ease;
        }

        h2.page-title i {
            font-size: 2.2rem;
            background: linear-gradient(45deg, #007bff, #00d2ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: iconFloat 3s ease infinite;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #00d2ff);
            transition: width 0.3s ease;
        }

        .page-title-container:hover .page-title::after {
            width: 100px;
        }

        @keyframes gradientSlide {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .back-btn {
            position: relative;
            overflow: hidden;
            background: linear-gradient(45deg, #6c757d, #495057);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 30px;
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            margin-bottom: 2rem;
        }

        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        .back-btn:hover::before {
            left: 100%;
        }

        .back-btn i {
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-5px);
        }
    </style>
    <style>
        .info-row {
            display: flex;
            padding: 1.2rem;
            border-bottom: 1px solid #eee;
            position: relative;
            transition: all 0.3s ease;
            background: linear-gradient(to right, transparent 50%, rgba(0, 123, 255, 0.05) 50%);
            background-size: 200% 100%;
            background-position: left bottom;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .info-row:hover {
            background-position: right bottom;
            transform: translateX(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .info-label {
            font-weight: 600;
            width: 155px;
            color: #495057;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label::after {
            content: ':';
            position: absolute;
            right: 10px;
            color: #adb5bd;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .info-row:hover .info-label::after {
            opacity: 1;
            transform: translateX(5px);
        }

        .info-value {
            flex: 1;
            color: #2c3e50;
            position: relative;
            padding-left: 20px;
            transition: all 0.3s ease;
        }

        .info-value::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 3px;
            height: 0;
            background: linear-gradient(to bottom, #007bff, #00d2ff);
            transition: all 0.3s ease;
            transform: translateY(-50%);
        }

        .info-row:hover .info-value::before {
            height: 80%;
        }

        .status-badge, .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .status-badge::before, .role-badge::before {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
        }

        .status-activated {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .status-activated::before {
            content: '\f058';
        }

        .status-banned {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }

        .status-banned::before {
            content: '\f056';
        }

        .status-disabled {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .status-disabled::before {
            content: '\f05e';
        }

        .role-admin {
            background: linear-gradient(135deg, #007bff, #6610f2);
            color: white;
        }

        .role-admin::before {
            content: '\f509';
        }

        .role-customer {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }

        .role-customer::before {
            content: '\f007';
        }

        .status-badge:hover, .role-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .password-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toggle-password {
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 30px;
            height: 30px;
        }

        .toggle-password:hover {
            background-color: rgba(108, 117, 125, 0.1);
            color: #007bff;
        }

        .toggle-password:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .password-dots {
            letter-spacing: 2px;
            font-weight: bold;
        }

        .toggle-password.showing .fa-eye {
            display: none;
        }

        .toggle-password:not(.showing) .fa-eye-slash {
            display: none;
        }

        body.dark-theme .user-container {
            background-color: #2c3e50;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme .page-title-container {
            background: linear-gradient(145deg, #34495e, #2c3e50);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        body.dark-theme .page-title {
            color: #ecf0f1;
        }

        body.dark-theme .page-title i {
            background: linear-gradient(45deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            background-clip: text;
        }

        body.dark-theme .back-btn {
            background: linear-gradient(45deg, #34495e, #2c3e50);
            color: #ecf0f1;
        }

        body.dark-theme .back-btn:hover {
            background: linear-gradient(45deg, #2c3e50, #243240);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        body.dark-theme .info-row {
            border-bottom: 1px solid #445566;
        }

        body.dark-theme .info-row:hover {
            background-position: right bottom;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme .info-label {
            color: #bdc3c7;
        }

        body.dark-theme .info-value {
            color: #ecf0f1;
        }

        body.dark-theme .info-label i {
            color: #3498db;
        }

        body.dark-theme .modal-content {
            background: linear-gradient(145deg, #34495e, #2c3e50);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        body.dark-theme .modal-content h2 {
            color: #ecf0f1;
        }

        body.dark-theme .form-group label {
            color: #bdc3c7;
        }

        body.dark-theme .form-group input {
            background-color: #34495e;
            border-color: #445566;
            color: #ecf0f1;
        }

        body.dark-theme .form-group input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        body.dark-theme .form-group input::placeholder {
            color: #95a5a6;
        }

        body.dark-theme .form-group i {
            color: #7f8c8d;
        }

        body.dark-theme .submit-btn {
            background: linear-gradient(90deg, #3498db, #2980b9);
        }

        body.dark-theme .submit-btn:hover {
            background: linear-gradient(90deg, #2980b9, #2472a4);
        }

        body.dark-theme .close-btn {
            color: #bdc3c7;
        }

        body.dark-theme .close-btn:hover {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }

        body.dark-theme .status-badge {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme .role-badge {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme .toggle-password {
            color: #bdc3c7;
            background-color: rgba(189, 195, 199, 0.1);
        }

        body.dark-theme .toggle-password:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        body.dark-theme .password-dots {
            color: #bdc3c7;
        }

        body.dark-theme .nav-links .nav-link {
            background-color: #34495e;
            color: #ecf0f1;
        }

        body.dark-theme .nav-links .nav-link:hover {
            background-color: #2c3e50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        body.dark-theme #edit {
            background-color: #34495e;
            color: #ecf0f1;
        }

        body.dark-theme #edit:hover {
            background-color: #2c3e50;
        }

        @keyframes darkFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        body.dark-theme .user-container,
        body.dark-theme .modal-content,
        body.dark-theme .info-row {
            animation: darkFadeIn 0.3s ease-out;
        }
    </style>
</head>

<body>
    <main>

<div class="user-container">
    <a href="javascript:history.back()" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        <span>Trở về</span>
    </a>

    <div class="page-title-container">
        <h2 class="page-title">
            <i class="fas fa-user-circle"></i>
            <span>Thông tin người dùng</span>
        </h2>
    </div>

            <div class="user-info">
                <div class="info-row">
                    <div class="info-label">Tên người dùng:</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-key"></i> Mật khẩu:
                    </div>
                    <div class="info-value password-field">
                        <span class="password-dots">•••••••••</span>
                        <span class="password-text" style="display: none;">
                            <?php echo isset($_SESSION['password']) ? htmlspecialchars($_SESSION['password']) : 'Không có'; ?>
                        </span>
                        <button type="button" class="toggle-password">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['email']) && !empty($_SESSION['email'])
                            ? htmlspecialchars($_SESSION['email'])
                            : 'Không có'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Số điện thoại:</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['phone_num']) && !empty($_SESSION['phone_num'])
                            ? htmlspecialchars($_SESSION['phone_num'])
                            : 'Không có'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Ngày đăng ký:</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['register_date']) && !empty($_SESSION['register_date'])
                            ? htmlspecialchars($_SESSION['register_date'])
                            : 'Không có'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Họ tên:</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])
                            ? htmlspecialchars($_SESSION['full_name'])
                            : 'Không có'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Địa chỉ:</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['address']) && !empty($_SESSION['address'])
                            ? htmlspecialchars($_SESSION['address'])
                            : 'Không có'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Vai trò:</div>
                    <div class="info-value">
                        <?php
                        if (isset($_SESSION['role'])) {
                            $roleClass = 'role-' . strtolower($_SESSION['role']);
                            $roleText = '';
                            switch ($_SESSION['role']) {
                                case 'admin':
                                    $roleText = 'Quản trị viên';
                                    break;
                                default:
                                    $roleText = 'Khách hàng';
                            }
                            echo "<span class='role-badge {$roleClass}'>{$roleText}</span>";
                        } else {
                            echo 'Không có';
                        }
                        ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Trạng thái:</div>
                    <div class="info-value">
                        <?php
                        if (isset($_SESSION['status']) && !empty($_SESSION['status'])) {
                            $statusClass = 'status-' . strtolower($_SESSION['status']);
                            $statusText = '';
                            switch ($_SESSION['status']) {
                                case 'activated':
                                    $statusText = 'Hoạt động';
                                    break;
                                case 'banned':
                                    $statusText = 'Đã bị cấm';
                                    break;
                                case 'disabled':
                                    $statusText = 'Vô hiệu hóa';
                                    break;
                                default:
                                    $statusText = $_SESSION['status'];
                            }
                            echo "<span class='status-badge {$statusClass}'>{$statusText}</span>";
                        } else {
                            echo 'Không có';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div id="editModal" class="modal">
                <div class="modal-content">
                    <button type="button" class="close-btn" onclick="closeEditForm()">&times;</button>
                    <h2>Chỉnh sửa thông tin</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="editForm">
                        <div class="form-group">
                            <label>Tên người dùng:</label>
                            <input type="text" name="username"
                                value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới:</label>
                            <input type="password" name="password" placeholder="Để trống nếu không muốn thay đổi">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email"
                                value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại:</label>
                            <input type="tel" name="phone_num"
                                value="<?php echo isset($_SESSION['phone_num']) ? htmlspecialchars($_SESSION['phone_num']) : ''; ?>">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="form-group">
                            <label>Họ tên:</label>
                            <input type="text" name="full_name"
                                value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>">
                            <i class="fa-solid fa-user-circle"></i>
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ:</label>
                            <input type="text" name="address"
                                value="<?php echo isset($_SESSION['address']) ? htmlspecialchars($_SESSION['address']) : ''; ?>">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <button type="submit" name="edit_profile" class="submit-btn">
                            <i class="fa-solid fa-save"></i> Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>

            <div class="nav-links">
                <a href="cart.php" class="nav-link">
                    <i class="fa-solid fa-shopping-cart"></i>
                    Giỏ hàng của tôi
                </a>
                <a href="billhistory.php" class="nav-link">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Lịch sử mua hàng
                </a>
                <button class="nav-link" id="edit" onclick="toggleEditForm()">
                    <i class="fa-solid fa-user-edit"></i>
                    Chỉnh sửa thông tin
                </button>
            </div>
    </main>
    <script>
        function toggleEditForm() {
            const modal = document.getElementById('editModal');
            modal.classList.add('show');
        }

        function closeEditForm() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('show');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditForm();
            }
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeEditForm();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const titleContainer = document.querySelector('.page-title-container');
            const title = document.querySelector('.page-title');

            titleContainer.addEventListener('mouseenter', () => {
                title.querySelector('i').style.transform = 'scale(1.1) rotate(5deg)';
            });

            titleContainer.addEventListener('mouseleave', () => {
                title.querySelector('i').style.transform = 'scale(1) rotate(0)';
            });

            titleContainer.style.opacity = '0';
            titleContainer.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                titleContainer.style.transition = 'all 0.5s ease';
                titleContainer.style.opacity = '1';
                titleContainer.style.transform = 'translateY(0)';
            }, 300);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const infoRows = document.querySelectorAll('.info-row');

            infoRows.forEach(row => {
                const label = row.querySelector('.info-label');
                const labelText = label.textContent.toLowerCase();

                if (labelText.includes('tên')) {
                    label.innerHTML = `<i class="fas fa-user"></i> ${label.textContent}`;
                } else if (labelText.includes('mật khẩu')) {
                    label.innerHTML = `<i class="fas fa-key"></i> ${label.textContent}`;
                } else if (labelText.includes('email')) {
                    label.innerHTML = `<i class="fas fa-envelope"></i> ${label.textContent}`;
                } else if (labelText.includes('điện thoại')) {
                    label.innerHTML = `<i class="fas fa-phone"></i> ${label.textContent}`;
                } else if (labelText.includes('ngày')) {
                    label.innerHTML = `<i class="fas fa-calendar"></i> ${label.textContent}`;
                } else if (labelText.includes('địa chỉ')) {
                    label.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${label.textContent}`;
                } else if (labelText.includes('vai trò')) {
                    label.innerHTML = `<i class="fas fa-user-tag"></i> ${label.textContent}`;
                } else if (labelText.includes('trạng thái')) {
                    label.innerHTML = `<i class="fas fa-toggle-on"></i> ${label.textContent}`;
                }

                row.addEventListener('mouseenter', () => {
                    const icon = row.querySelector('.info-label i');
                    if (icon) {
                        icon.style.transform = 'scale(1.2) rotate(5deg)';
                    }
                });

                row.addEventListener('mouseleave', () => {
                    const icon = row.querySelector('.info-label i');
                    if (icon) {
                        icon.style.transform = 'scale(1) rotate(0)';
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.toggle-password');
            const passwordDots = document.querySelector('.password-dots');
            const passwordText = document.querySelector('.password-text');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const icon = toggleBtn.querySelector('i');
                    if (icon.classList.contains('fa-eye')) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        passwordDots.style.display = 'none';
                        passwordText.style.display = 'inline';
                        toggleBtn.setAttribute('title', 'Ẩn mật khẩu');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        passwordDots.style.display = 'inline';
                        passwordText.style.display = 'none';
                        toggleBtn.setAttribute('title', 'Hiện mật khẩu');
                    }

                    toggleBtn.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        toggleBtn.style.transform = 'scale(1)';
                    }, 100);
                });
            }
        });
    </script>
</body>

</html>

<?php
include "footer.php";
?>