<?php
session_start();
// Check for logout message
if (isset($_SESSION['logout_message'])) {
    echo "<script>
            window.onload = function() {
                showNotification('{$_SESSION['logout_message']}', 'info');
            }
        </script>";
    unset($_SESSION['logout_message']); // Remove the message after showing it
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="dp56vcf7.png" type="image/png">
    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>
    <title>Đăng Nhập/ Đăng Ký</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: rgb(16, 194, 243);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 90%;
            max-width: 400px;
        }

        .form-box {
            padding: 2rem;
        }

        .form-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
        }

        .toggle-form {
            text-align: center;
            margin-bottom: 1rem;
        }

        .toggle-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            padding: 0.5rem 1rem;
            font-weight: bold;
        }

        .toggle-btn.active {
            border-bottom: 2px solid #007bff;
        }

        .input-group {
            margin-bottom: 1rem;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: #007bff;
        }

        .submit-btn {
            width: 100%;
            padding: 0.8rem;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 480px) {
            .container {
                width: 95%;
            }

            .form-box {
                padding: 1.5rem;
            }
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: #f8f9fa;
            transform: translateX(-2px);
        }

        @media (max-width: 480px) {
            .back-btn {
                top: 10px;
                left: 10px;
            }
        }
    </style>
    <style>
        body {
            overflow: hidden;
            background-color: #000030;
            background-image: url("https://images.unsplash.com/photo-1536152470836-b943b246224c?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1476&q=80");
            background-size: 100%;
            background-position: center;
        }

        @media only screen and (max-width: 600px) {
            body {
                background-size: auto;
                background-position: right;
            }
        }

        .starfall {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            left: 0;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            -webkit-perspective: 1000px;
            perspective: 1000px;
            z-index: 0;
        }

        .starfall .falling-star {
            width: 8px;
            height: 8px;
            background: #00d1b2;
            position: absolute;
            border-radius: 50%;
            opacity: 0.5;
        }

        .falling-star:nth-child(1) {
            -webkit-transform: translateX(68vw) translateY(-8px);
            transform: translateX(68vw) translateY(-8px);
            -webkit-animation: anim1 4s infinite;
            animation: anim1 4s infinite;
            -webkit-animation-delay: 0.3s;
            animation-delay: 0.3s;
        }

        @-webkit-keyframes anim1 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(88vw) translateY(100vh);
                transform: translateX(88vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim1 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(88vw) translateY(100vh);
                transform: translateX(88vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(2) {
            -webkit-transform: translateX(57vw) translateY(-8px);
            transform: translateX(57vw) translateY(-8px);
            -webkit-animation: anim2 4s infinite;
            animation: anim2 4s infinite;
            -webkit-animation-delay: 0.6s;
            animation-delay: 0.6s;
        }

        @-webkit-keyframes anim2 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(77vw) translateY(100vh);
                transform: translateX(77vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim2 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(77vw) translateY(100vh);
                transform: translateX(77vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(3) {
            -webkit-transform: translateX(70vw) translateY(-8px);
            transform: translateX(70vw) translateY(-8px);
            -webkit-animation: anim3 4s infinite;
            animation: anim3 4s infinite;
            -webkit-animation-delay: 0.9s;
            animation-delay: 0.9s;
        }

        @-webkit-keyframes anim3 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(90vw) translateY(100vh);
                transform: translateX(90vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim3 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(90vw) translateY(100vh);
                transform: translateX(90vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(4) {
            -webkit-transform: translateX(54vw) translateY(-8px);
            transform: translateX(54vw) translateY(-8px);
            -webkit-animation: anim4 4s infinite;
            animation: anim4 4s infinite;
            -webkit-animation-delay: 1.2s;
            animation-delay: 1.2s;
        }

        @-webkit-keyframes anim4 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim4 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(5) {
            -webkit-transform: translateX(85vw) translateY(-8px);
            transform: translateX(85vw) translateY(-8px);
            -webkit-animation: anim5 4s infinite;
            animation: anim5 4s infinite;
            -webkit-animation-delay: 1.5s;
            animation-delay: 1.5s;
        }

        @-webkit-keyframes anim5 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(105vw) translateY(100vh);
                transform: translateX(105vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim5 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(105vw) translateY(100vh);
                transform: translateX(105vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(6) {
            -webkit-transform: translateX(59vw) translateY(-8px);
            transform: translateX(59vw) translateY(-8px);
            -webkit-animation: anim6 4s infinite;
            animation: anim6 4s infinite;
            -webkit-animation-delay: 1.8s;
            animation-delay: 1.8s;
        }

        @-webkit-keyframes anim6 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(79vw) translateY(100vh);
                transform: translateX(79vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim6 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(79vw) translateY(100vh);
                transform: translateX(79vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(7) {
            -webkit-transform: translateX(33vw) translateY(-8px);
            transform: translateX(33vw) translateY(-8px);
            -webkit-animation: anim7 4s infinite;
            animation: anim7 4s infinite;
            -webkit-animation-delay: 2.1s;
            animation-delay: 2.1s;
        }

        @-webkit-keyframes anim7 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(53vw) translateY(100vh);
                transform: translateX(53vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim7 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(53vw) translateY(100vh);
                transform: translateX(53vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(8) {
            -webkit-transform: translateX(82vw) translateY(-8px);
            transform: translateX(82vw) translateY(-8px);
            -webkit-animation: anim8 4s infinite;
            animation: anim8 4s infinite;
            -webkit-animation-delay: 2.4s;
            animation-delay: 2.4s;
        }

        @-webkit-keyframes anim8 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(102vw) translateY(100vh);
                transform: translateX(102vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim8 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(102vw) translateY(100vh);
                transform: translateX(102vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(9) {
            -webkit-transform: translateX(24vw) translateY(-8px);
            transform: translateX(24vw) translateY(-8px);
            -webkit-animation: anim9 4s infinite;
            animation: anim9 4s infinite;
            -webkit-animation-delay: 2.7s;
            animation-delay: 2.7s;
        }

        @-webkit-keyframes anim9 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(44vw) translateY(100vh);
                transform: translateX(44vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim9 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(44vw) translateY(100vh);
                transform: translateX(44vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(10) {
            -webkit-transform: translateX(54vw) translateY(-8px);
            transform: translateX(54vw) translateY(-8px);
            -webkit-animation: anim10 4s infinite;
            animation: anim10 4s infinite;
            -webkit-animation-delay: 3s;
            animation-delay: 3s;
        }

        @-webkit-keyframes anim10 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim10 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(11) {
            -webkit-transform: translateX(11vw) translateY(-8px);
            transform: translateX(11vw) translateY(-8px);
            -webkit-animation: anim11 4s infinite;
            animation: anim11 4s infinite;
            -webkit-animation-delay: 3.3s;
            animation-delay: 3.3s;
        }

        @-webkit-keyframes anim11 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(31vw) translateY(100vh);
                transform: translateX(31vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim11 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(31vw) translateY(100vh);
                transform: translateX(31vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(12) {
            -webkit-transform: translateX(14vw) translateY(-8px);
            transform: translateX(14vw) translateY(-8px);
            -webkit-animation: anim12 4s infinite;
            animation: anim12 4s infinite;
            -webkit-animation-delay: 3.6s;
            animation-delay: 3.6s;
        }

        @-webkit-keyframes anim12 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(34vw) translateY(100vh);
                transform: translateX(34vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim12 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(34vw) translateY(100vh);
                transform: translateX(34vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(13) {
            -webkit-transform: translateX(66vw) translateY(-8px);
            transform: translateX(66vw) translateY(-8px);
            -webkit-animation: anim13 4s infinite;
            animation: anim13 4s infinite;
            -webkit-animation-delay: 3.9s;
            animation-delay: 3.9s;
        }

        @-webkit-keyframes anim13 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(86vw) translateY(100vh);
                transform: translateX(86vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim13 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(86vw) translateY(100vh);
                transform: translateX(86vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(14) {
            -webkit-transform: translateX(64vw) translateY(-8px);
            transform: translateX(64vw) translateY(-8px);
            -webkit-animation: anim14 4s infinite;
            animation: anim14 4s infinite;
            -webkit-animation-delay: 4.2s;
            animation-delay: 4.2s;
        }

        @-webkit-keyframes anim14 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(84vw) translateY(100vh);
                transform: translateX(84vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim14 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(84vw) translateY(100vh);
                transform: translateX(84vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(15) {
            -webkit-transform: translateX(3vw) translateY(-8px);
            transform: translateX(3vw) translateY(-8px);
            -webkit-animation: anim15 4s infinite;
            animation: anim15 4s infinite;
            -webkit-animation-delay: 4.5s;
            animation-delay: 4.5s;
        }

        @-webkit-keyframes anim15 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(23vw) translateY(100vh);
                transform: translateX(23vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim15 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(23vw) translateY(100vh);
                transform: translateX(23vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(16) {
            -webkit-transform: translateX(78vw) translateY(-8px);
            transform: translateX(78vw) translateY(-8px);
            -webkit-animation: anim16 4s infinite;
            animation: anim16 4s infinite;
            -webkit-animation-delay: 4.8s;
            animation-delay: 4.8s;
        }

        @-webkit-keyframes anim16 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(98vw) translateY(100vh);
                transform: translateX(98vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim16 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(98vw) translateY(100vh);
                transform: translateX(98vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(17) {
            -webkit-transform: translateX(98vw) translateY(-8px);
            transform: translateX(98vw) translateY(-8px);
            -webkit-animation: anim17 4s infinite;
            animation: anim17 4s infinite;
            -webkit-animation-delay: 5.1s;
            animation-delay: 5.1s;
        }

        @-webkit-keyframes anim17 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(118vw) translateY(100vh);
                transform: translateX(118vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim17 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(118vw) translateY(100vh);
                transform: translateX(118vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(18) {
            -webkit-transform: translateX(34vw) translateY(-8px);
            transform: translateX(34vw) translateY(-8px);
            -webkit-animation: anim18 4s infinite;
            animation: anim18 4s infinite;
            -webkit-animation-delay: 5.4s;
            animation-delay: 5.4s;
        }

        @-webkit-keyframes anim18 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(54vw) translateY(100vh);
                transform: translateX(54vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim18 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(54vw) translateY(100vh);
                transform: translateX(54vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(19) {
            -webkit-transform: translateX(54vw) translateY(-8px);
            transform: translateX(54vw) translateY(-8px);
            -webkit-animation: anim19 4s infinite;
            animation: anim19 4s infinite;
            -webkit-animation-delay: 5.7s;
            animation-delay: 5.7s;
        }

        @-webkit-keyframes anim19 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim19 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(74vw) translateY(100vh);
                transform: translateX(74vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(20) {
            -webkit-transform: translateX(71vw) translateY(-8px);
            transform: translateX(71vw) translateY(-8px);
            -webkit-animation: anim20 4s infinite;
            animation: anim20 4s infinite;
            -webkit-animation-delay: 6s;
            animation-delay: 6s;
        }

        @-webkit-keyframes anim20 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(91vw) translateY(100vh);
                transform: translateX(91vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim20 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(91vw) translateY(100vh);
                transform: translateX(91vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(21) {
            -webkit-transform: translateX(100vw) translateY(-8px);
            transform: translateX(100vw) translateY(-8px);
            -webkit-animation: anim21 4s infinite;
            animation: anim21 4s infinite;
            -webkit-animation-delay: 6.3s;
            animation-delay: 6.3s;
        }

        @-webkit-keyframes anim21 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(120vw) translateY(100vh);
                transform: translateX(120vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim21 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(120vw) translateY(100vh);
                transform: translateX(120vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(22) {
            -webkit-transform: translateX(26vw) translateY(-8px);
            transform: translateX(26vw) translateY(-8px);
            -webkit-animation: anim22 4s infinite;
            animation: anim22 4s infinite;
            -webkit-animation-delay: 6.6s;
            animation-delay: 6.6s;
        }

        @-webkit-keyframes anim22 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(46vw) translateY(100vh);
                transform: translateX(46vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim22 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(46vw) translateY(100vh);
                transform: translateX(46vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(23) {
            -webkit-transform: translateX(89vw) translateY(-8px);
            transform: translateX(89vw) translateY(-8px);
            -webkit-animation: anim23 4s infinite;
            animation: anim23 4s infinite;
            -webkit-animation-delay: 6.9s;
            animation-delay: 6.9s;
        }

        @-webkit-keyframes anim23 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(109vw) translateY(100vh);
                transform: translateX(109vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim23 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(109vw) translateY(100vh);
                transform: translateX(109vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(24) {
            -webkit-transform: translateX(42vw) translateY(-8px);
            transform: translateX(42vw) translateY(-8px);
            -webkit-animation: anim24 4s infinite;
            animation: anim24 4s infinite;
            -webkit-animation-delay: 7.2s;
            animation-delay: 7.2s;
        }

        @-webkit-keyframes anim24 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(62vw) translateY(100vh);
                transform: translateX(62vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim24 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(62vw) translateY(100vh);
                transform: translateX(62vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(25) {
            -webkit-transform: translateX(3vw) translateY(-8px);
            transform: translateX(3vw) translateY(-8px);
            -webkit-animation: anim25 4s infinite;
            animation: anim25 4s infinite;
            -webkit-animation-delay: 7.5s;
            animation-delay: 7.5s;
        }

        @-webkit-keyframes anim25 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(23vw) translateY(100vh);
                transform: translateX(23vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim25 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(23vw) translateY(100vh);
                transform: translateX(23vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(26) {
            -webkit-transform: translateX(24vw) translateY(-8px);
            transform: translateX(24vw) translateY(-8px);
            -webkit-animation: anim26 4s infinite;
            animation: anim26 4s infinite;
            -webkit-animation-delay: 7.8s;
            animation-delay: 7.8s;
        }

        @-webkit-keyframes anim26 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(44vw) translateY(100vh);
                transform: translateX(44vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim26 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(44vw) translateY(100vh);
                transform: translateX(44vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(27) {
            -webkit-transform: translateX(19vw) translateY(-8px);
            transform: translateX(19vw) translateY(-8px);
            -webkit-animation: anim27 4s infinite;
            animation: anim27 4s infinite;
            -webkit-animation-delay: 8.1s;
            animation-delay: 8.1s;
        }

        @-webkit-keyframes anim27 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(39vw) translateY(100vh);
                transform: translateX(39vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim27 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(39vw) translateY(100vh);
                transform: translateX(39vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(28) {
            -webkit-transform: translateX(81vw) translateY(-8px);
            transform: translateX(81vw) translateY(-8px);
            -webkit-animation: anim28 4s infinite;
            animation: anim28 4s infinite;
            -webkit-animation-delay: 8.4s;
            animation-delay: 8.4s;
        }

        @-webkit-keyframes anim28 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(101vw) translateY(100vh);
                transform: translateX(101vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim28 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(101vw) translateY(100vh);
                transform: translateX(101vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(29) {
            -webkit-transform: translateX(40vw) translateY(-8px);
            transform: translateX(40vw) translateY(-8px);
            -webkit-animation: anim29 4s infinite;
            animation: anim29 4s infinite;
            -webkit-animation-delay: 8.7s;
            animation-delay: 8.7s;
        }

        @-webkit-keyframes anim29 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(60vw) translateY(100vh);
                transform: translateX(60vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim29 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(60vw) translateY(100vh);
                transform: translateX(60vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(30) {
            -webkit-transform: translateX(75vw) translateY(-8px);
            transform: translateX(75vw) translateY(-8px);
            -webkit-animation: anim30 4s infinite;
            animation: anim30 4s infinite;
            -webkit-animation-delay: 9s;
            animation-delay: 9s;
        }

        @-webkit-keyframes anim30 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(95vw) translateY(100vh);
                transform: translateX(95vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim30 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(95vw) translateY(100vh);
                transform: translateX(95vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(31) {
            -webkit-transform: translateX(73vw) translateY(-8px);
            transform: translateX(73vw) translateY(-8px);
            -webkit-animation: anim31 4s infinite;
            animation: anim31 4s infinite;
            -webkit-animation-delay: 9.3s;
            animation-delay: 9.3s;
        }

        @-webkit-keyframes anim31 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(93vw) translateY(100vh);
                transform: translateX(93vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim31 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(93vw) translateY(100vh);
                transform: translateX(93vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(32) {
            -webkit-transform: translateX(4vw) translateY(-8px);
            transform: translateX(4vw) translateY(-8px);
            -webkit-animation: anim32 4s infinite;
            animation: anim32 4s infinite;
            -webkit-animation-delay: 9.6s;
            animation-delay: 9.6s;
        }

        @-webkit-keyframes anim32 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(24vw) translateY(100vh);
                transform: translateX(24vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim32 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(24vw) translateY(100vh);
                transform: translateX(24vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(33) {
            -webkit-transform: translateX(97vw) translateY(-8px);
            transform: translateX(97vw) translateY(-8px);
            -webkit-animation: anim33 4s infinite;
            animation: anim33 4s infinite;
            -webkit-animation-delay: 9.9s;
            animation-delay: 9.9s;
        }

        @-webkit-keyframes anim33 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(117vw) translateY(100vh);
                transform: translateX(117vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim33 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(117vw) translateY(100vh);
                transform: translateX(117vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(34) {
            -webkit-transform: translateX(48vw) translateY(-8px);
            transform: translateX(48vw) translateY(-8px);
            -webkit-animation: anim34 4s infinite;
            animation: anim34 4s infinite;
            -webkit-animation-delay: 10.2s;
            animation-delay: 10.2s;
        }

        @-webkit-keyframes anim34 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(68vw) translateY(100vh);
                transform: translateX(68vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim34 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(68vw) translateY(100vh);
                transform: translateX(68vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(35) {
            -webkit-transform: translateX(44vw) translateY(-8px);
            transform: translateX(44vw) translateY(-8px);
            -webkit-animation: anim35 4s infinite;
            animation: anim35 4s infinite;
            -webkit-animation-delay: 10.5s;
            animation-delay: 10.5s;
        }

        @-webkit-keyframes anim35 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(64vw) translateY(100vh);
                transform: translateX(64vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim35 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(64vw) translateY(100vh);
                transform: translateX(64vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(36) {
            -webkit-transform: translateX(45vw) translateY(-8px);
            transform: translateX(45vw) translateY(-8px);
            -webkit-animation: anim36 4s infinite;
            animation: anim36 4s infinite;
            -webkit-animation-delay: 10.8s;
            animation-delay: 10.8s;
        }

        @-webkit-keyframes anim36 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(65vw) translateY(100vh);
                transform: translateX(65vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim36 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(65vw) translateY(100vh);
                transform: translateX(65vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(37) {
            -webkit-transform: translateX(69vw) translateY(-8px);
            transform: translateX(69vw) translateY(-8px);
            -webkit-animation: anim37 4s infinite;
            animation: anim37 4s infinite;
            -webkit-animation-delay: 11.1s;
            animation-delay: 11.1s;
        }

        @-webkit-keyframes anim37 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(89vw) translateY(100vh);
                transform: translateX(89vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim37 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(89vw) translateY(100vh);
                transform: translateX(89vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(38) {
            -webkit-transform: translateX(19vw) translateY(-8px);
            transform: translateX(19vw) translateY(-8px);
            -webkit-animation: anim38 4s infinite;
            animation: anim38 4s infinite;
            -webkit-animation-delay: 11.4s;
            animation-delay: 11.4s;
        }

        @-webkit-keyframes anim38 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(39vw) translateY(100vh);
                transform: translateX(39vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim38 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(39vw) translateY(100vh);
                transform: translateX(39vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(39) {
            -webkit-transform: translateX(71vw) translateY(-8px);
            transform: translateX(71vw) translateY(-8px);
            -webkit-animation: anim39 4s infinite;
            animation: anim39 4s infinite;
            -webkit-animation-delay: 11.7s;
            animation-delay: 11.7s;
        }

        @-webkit-keyframes anim39 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(91vw) translateY(100vh);
                transform: translateX(91vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim39 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(91vw) translateY(100vh);
                transform: translateX(91vw) translateY(100vh);
                opacity: 0;
            }
        }

        .falling-star:nth-child(40) {
            -webkit-transform: translateX(31vw) translateY(-8px);
            transform: translateX(31vw) translateY(-8px);
            -webkit-animation: anim40 4s infinite;
            animation: anim40 4s infinite;
            -webkit-animation-delay: 12s;
            animation-delay: 12s;
        }

        @-webkit-keyframes anim40 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(51vw) translateY(100vh);
                transform: translateX(51vw) translateY(100vh);
                opacity: 0;
            }
        }

        @keyframes anim40 {
            10% {
                opacity: 0.5;
            }

            12% {
                opacity: 1;
                -webkit-box-shadow: 0 0 3px 0 #fff;
                box-shadow: 0 0 3px 0 #fff;
            }

            15% {
                opacity: 0.5;
            }

            50% {
                opacity: 0;
            }

            100% {
                -webkit-transform: translateX(51vw) translateY(100vh);
                transform: translateX(51vw) translateY(100vh);
                opacity: 0;
            }
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 200;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            overflow: hidden;
        }

        @-webkit-keyframes move {
            0% {
                -webkit-transform: translateY(0);
                transform: translateY(0);
                opacity: 0;
            }

            10%, 90% {
                opacity: 1;
            }

            100% {
                -webkit-transform: translateY(45vw);
                transform: translateY(45vw);
                opacity: 0;
            }
        }

        @keyframes move {
            0% {
                -webkit-transform: translateY(0);
                transform: translateY(0);
                opacity: 0;
            }

            10%, 90% {
                opacity: 1;
            }

            100% {
                -webkit-transform: translateY(45vw);
                transform: translateY(45vw);
                opacity: 0;
            }
        }
    </style>
    <style>
        .starfall {
            position: fixed;
            height: 100%;
            width: 100%;
            top: 0;
            left: 0;
            -webkit-transform-style: preserve-3d;
            transform-style: preserve-3d;
            -webkit-perspective: 1000px;
            perspective: 1000px;
            z-index: -1;
        }

        .container {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 90%;
            max-width: 400px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .container::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: -1;
        }

        .form-box {
            position: relative;
            z-index: 2;
            padding: 2rem;
            background: transparent;
        }

        .form-title,
        .input-group,
        .submit-btn,
        .toggle-btn {
            position: relative;
            z-index: 3;
        }

        .input-group input {
            background-color: rgba(255, 255, 255, 0.95);
        }

        .submit-btn {
            background: linear-gradient(45deg, rgba(0, 123, 255, 0.9), rgba(0, 209, 178, 0.9));
        }

        .toggle-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .toggle-btn.active {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .back-btn {
            position: fixed;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        }
    </style>
    <style>
        .container {
            animation: slideInFromTop 0.8s ease-out;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px) scale(1.01);
        }

        .form-box {
            animation: fadeInScale 0.6s ease-out;
            transition: all 0.4s ease;
        }

        .form-box:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .form {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: top center;
        }

        #login-form.hide,
        #signup-form.hide {
            opacity: 0;
            transform: rotateX(-90deg);
        }

        #login-form.show,
        #signup-form.show {
            opacity: 1;
            transform: rotateX(0);
        }

        .input-group {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .input-group::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #007bff, #00d1b2);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .input-group:focus-within::after {
            transform: translateX(0);
        }

        .input-group:focus-within {
            transform: translateX(5px);
        }

        .input-group input {
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.15);
        }

        .submit-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            background: linear-gradient(45deg, #007bff, #00d1b2);
            background-size: 200% auto;
        }

        .submit-btn:hover {
            background-position: right center;
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 123, 255, 0.3);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .toggle-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .toggle-btn::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: 0;
            left: -100%;
            background: linear-gradient(90deg, #007bff, #00d1b2);
            transition: all 0.4s ease;
        }

        .toggle-btn.active::before {
            left: 0;
        }

        .back-btn {
            animation: slideInFromLeft 0.6s ease-out;
            transition: all 0.4s ease;
        }

        .back-btn:hover {
            transform: translateX(-8px);
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        @keyframes slideInFromTop {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInFromLeft {
            0% {
                opacity: 0;
                transform: translateX(-30px);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                animation: slideInFromBottom 0.8s ease-out;
            }

            @keyframes slideInFromBottom {
                0% {
                    opacity: 0;
                    transform: translateY(50px) scale(0.95);
                }

                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
        }
    </style>
    <style>
        .input-group {
            position: relative;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 2;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            transition: all 0.3s ease;
            background-color: white;
            color: #333;
            position: relative;
        }

        .input-group input:focus {
            border-color: #007bff;
            transform: scale(1.02);
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.15);
            background-color: white;
        }

        .input-group input::placeholder {
            color: #666;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .input-group input:focus::placeholder {
            opacity: 0.7;
        }
    </style>
    <style>
        .password-container {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 1rem;
            width: 100%;
        }

        .input-group.password {
            margin-bottom: 0;
            flex: 1;
            width: 100%;
        }

        .input-group.password input {
            width: 100%;
            padding-right: 45px;
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .separator {
            width: 1px;
            height: 25px;
            background-color: #ddd;
            margin: 0;
            position: relative;
            right: -1px;
        }

        .password-toggle {
            position: relative;
            padding: 8px 12px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #ddd;
            border-left: none;
            height: 43px;
            display: flex;
            align-items: center;
            background-color: white;
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        }

        .password-toggle:hover {
            color: #007bff;
        }

        .input-group.password input:focus {
            border-right: none;
        }

        .input-group.password input:focus + .separator + .password-toggle {
            border-color: #007bff;
        }
    </style>
    <style>
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 1000;
            font-size: 1.2rem;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.2);
        }

        .theme-toggle i {
            transition: transform 0.5s ease;
        }

        body.dark-theme {
            background-image: linear-gradient(45deg, #1a1a1a, #000033);
        }

        body.dark-theme .container {
            background: rgba(0, 0, 0, 0.4);
        }

        body.dark-theme .form-box input {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        body.dark-theme .form-box input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        body.dark-theme .form-title,
        body.dark-theme .toggle-form {
            color: rgba(255, 255, 255, 0.9);
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="starfall">
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
        <div class="falling-star"></div>
    </div>

    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-sun"></i>
    </button>

    <div id="notification" class="notification"></div>

    <a href="index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i>
        Trờ về
    </a>

    <div class="container">
        <div class="form-box">
            <div class="toggle-form">
                <button type="button" class="toggle-btn active" onclick="toggleForm('login')">Đăng Nhập</button>
                <button type="button" class="toggle-btn" onclick="toggleForm('signup')">Đăng Ký</button>
            </div>

            <!-- Login Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="login-form" class="form">
                <h2 class="form-title">Đăng Nhập</h2>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" placeholder="Tên đăng nhập" name="usn" required>
                </div>
                <div class="password-container">
                    <div class="input-group password">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="loginPassword" placeholder="Mật Khẩu" required name="pass">
                    </div>
                    <div class="separator"></div>
                    <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('loginPassword', this)"></i>
                </div>
                <input type="submit" class="submit-btn" value="Đăng Nhập" name="login">
            </form>

            <!-- Sign Up Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="signup-form" class="form" style="display: none;">
                <h2 class="form-title">Đăng Ký</h2>

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" placeholder="Tên đăng nhập" required name="username">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" placeholder="Email" required name="email">
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" placeholder="Số Điện Thoại" required name="phone_num" pattern="[0-9]*"
                        maxlength="20" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                </div>

                <!-- DA SUA: them dia chi -->
                <div class="input-group">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" placeholder="Địa chỉ" required name="address">
                </div>

                <div class="password-container">
                    <div class="input-group password">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="signupPassword" placeholder="Mật Khẩu" required name="password">
                    </div>
                    <div class="separator"></div>
                    <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('signupPassword', this)"></i>
                </div>

                <div class="password-container">
                    <div class="input-group password">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirmPassword" placeholder="Nhập Lại Mật Khẩu" required name="c-password">
                    </div>
                    <div class="separator"></div>
                    <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('confirmPassword', this)"></i>
                </div>

                <input type="submit" class="submit-btn" value="Đăng Ký" name="register">
            </form>
        </div>
    </div>

    <script>
        function toggleForm(formType) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const loginBtn = document.querySelector('.toggle-btn:nth-child(1)');
            const signupBtn = document.querySelector('.toggle-btn:nth-child(2)');

            if (formType === 'signup') {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
                loginBtn.classList.remove('active');
                signupBtn.classList.add('active');
                window.history.pushState(null, '', '#signup');
            } else {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
                loginBtn.classList.add('active');
                signupBtn.classList.remove('active');
                window.history.pushState(null, '', '#login');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.substring(1);
            if (hash === 'signup') {
                toggleForm('signup');
            }

            const signupLink = document.querySelector('a[href="login.php#signup"]');
            if (signupLink) {
                signupLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.location.href = 'login.php#signup';
                    toggleForm('signup');
                });
            }
        });
    </script>

    <script>
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            let icon = '';

            switch (type) {
                case 'success':
                    icon = '<i class="fa-solid fa-circle-check"></i>';
                    break;
                case 'error':
                    icon = '<i class="fa-solid fa-circle-xmark"></i>';
                    break;
                case 'warning':
                    icon = '<i class="fa-solid fa-triangle-exclamation"></i>';
                    break;
                case 'info':
                    icon = '<i class="fa-solid fa-circle-info"></i>';
                    break;
            }

            notification.innerHTML = `${icon} ${message}`;
            notification.className = `notification ${type}`;

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.textContent = '';
                }, 300);
            }, 5000);
        }
    </script>

    <script>
        const phoneInput = document.querySelector('input[name="phone_num"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');

                if (this.value.length > 20) {
                    this.value = this.value.slice(0, 20);
                }
            });
        }
    </script>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }

            icon.style.transform = 'scale(1.2)';
            setTimeout(() => {
                icon.style.transform = '';
            }, 200);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggle = document.getElementById('themeToggle');
            const icon = themeToggle.querySelector('i');

            const darkTheme = localStorage.getItem('darkTheme') === 'true';
            if (darkTheme) {
                document.body.classList.add('dark-theme');
                icon.classList.replace('fa-sun', 'fa-moon');
            }

            themeToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme');

                icon.style.animation = 'rotate 0.5s ease';
                setTimeout(() => icon.style.animation = '', 500);

                if (document.body.classList.contains('dark-theme')) {
                    icon.classList.replace('fa-sun', 'fa-moon');
                } else {
                    icon.classList.replace('fa-moon', 'fa-sun');
                }

                localStorage.setItem('darkTheme', document.body.classList.contains('dark-theme'));
            });
        });
    </script>

</body>

</html>

<?php
$sever = "localhost";
$accountname = "root";
$userpassword = "";
$database_name = "webbanoto";

$connect = "";

try {
    $connect = mysqli_connect($sever, $accountname, $userpassword, $database_name);
    if ($connect && !isset($_SESSION['logout_message'])) {
        // echo "<script>showNotification('Connected to database successfully', 'success');</script>";
    }
} catch (mysqli_sql_exception) {
    echo "<script>showNotification('Connection failed please reload the page', 'error');</script>";
}

if (isset($_POST['register'])) {
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);
    $c_password = filter_input(INPUT_POST, "c-password", FILTER_SANITIZE_SPECIAL_CHARS);
    $phone_num = filter_input(INPUT_POST, "phone_num", FILTER_SANITIZE_SPECIAL_CHARS);
    $address = filter_input(INPUT_POST, "address", FILTER_SANITIZE_SPECIAL_CHARS); // DA SUA

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>showNotification('Email không hợp lệ!', 'warning');</script>";
        exit();
    } else if ($password != $c_password) {
        echo "<script>showNotification('Mật khẩu đăng ký không trùng khớp', 'warning');</script>";
        exit();
    } else if (empty($username) || empty($email) || empty($password) || empty($c_password) || empty($phone_num) || empty($address)) {
        echo "<script>showNotification('Bạn cần phải nhập tất cả thông tin để đăng ký', 'warning');</script>";
        exit();
    } else {
        $status = "activated";

        $check_username = "SELECT * FROM users_acc WHERE username='$username'";
        $result = mysqli_query($connect, $check_username);

        if (mysqli_num_rows($result) > 0) {
            echo "<script>showNotification('Tên đăng nhập đã được sử dụng vui lòng nhập tên khác', 'error');</script>";
            exit();
        }

        // DA SUA: them address vao insert
        $sql = "INSERT INTO users_acc(username, email, password, status, phone_num, address) 
                VALUES ('$username', '$email', '$password', '$status', '$phone_num', '$address')";

        try {
            if (mysqli_query($connect, $sql)) {
                // echo "<script>showNotification('Đăng ký thành công ', 'success');</script>";
            }

            $query = "SELECT * FROM users_acc WHERE username='$username'";
            $fetch = mysqli_query($connect, $query);
            $row = mysqli_fetch_assoc($fetch);

            $_SESSION["username"] = $username;
            $_SESSION["password"] = $password;
            $_SESSION["email"] = $email;
            $_SESSION["status"] = $status;
            $_SESSION["phone_num"] = $phone_num;
            $_SESSION["register_date"] = $row["register_date"];
            $_SESSION['first_login'] = true;
            $_SESSION['full_name'] = null;
            $_SESSION['address'] = $address; // DA SUA
            $_SESSION['role'] = 'user';

            echo "<script>showNotification('Đăng ký thành công . Vui lòng chờ 1 lát...', 'success');</script>";
            echo "<script>
                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 1000);
                    </script>";
        } catch (mysqli_sql_exception) {
            echo "<script>showNotification('Đăng ký không thành công!', 'error');</script>";
        }
    }
} else if (isset($_POST['login'])) {
    $username = filter_input(INPUT_POST, "usn", FILTER_SANITIZE_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, "pass", FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($username) || empty($password)) {
        echo "<script>showNotification('Bạn cần phải nhập tất cả thông tin để đăng nhập', 'warning');</script>";
        exit();
    } else {
        $sql = "SELECT * FROM users_acc WHERE username='$username'";
        $result = mysqli_query($connect, $sql);

        if (mysqli_num_rows($result) === 0) {
            echo "<script>showNotification('Bạn chưa có tài khoản ! Vui lòng đăng kí trở thành viên mới của chúng tôi !', 'warning');</script>";
            exit();
        }

        $row = mysqli_fetch_assoc($result);

        if ($row["password"] != $password) {
            echo "<script>showNotification('Tên đăng nhập hoặc mật khẩu không đúng!!', 'warning');</script>";
            exit();
        }

        if ($row['status'] == 'banned' || $row['status'] == 'disabled') {
            $viestatus = ($row['status'] == 'banned') ? 'cấm' : 'vô hiệu hóa';
            echo "<script>showNotification('Tài khoản của bạn hiện bị {$viestatus}. Vui lòng liên hệ với quản trị viên để biết thêm thông tin', 'error');</script>";
            exit();
        }

        $_SESSION['user_id'] = $row['id'];
        $_SESSION["username"] = $username;
        $_SESSION["password"] = $password;
        $_SESSION["email"] = $row["email"];
        $_SESSION["status"] = $row["status"];
        $_SESSION["phone_num"] = $row["phone_num"];
        $_SESSION["register_date"] = $row["register_date"];
        $_SESSION["role"] = $row["role"];
        $_SESSION['first_login'] = true;
        $_SESSION['full_name'] = $row['full_name']; // DA SUA
        $_SESSION['address'] = $row['address'];     // DA SUA

        echo "<script>showNotification('Đăng nhập thành công. Vui lòng chờ một lát...', 'success');</script>";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 1000);
            </script>";
    }
}
// else if (isset($_POST['forgotpass'])) {

//     $username = filter_input(INPUT_POST, "usn",FILTER_SANITIZE_SPECIAL_CHARS);
//     if(empty($username)){
//         echo "<script>showNotification('Please input your username first', 'error');</script>";
//         exit();
//     }else{
//         $sql = "SELECT * FROM admin_acc WHERE username='$username'";
//         $result = mysqli_query($connect, $sql);

//         if(mysqli_num_rows($result) > 0) {
//             $row = mysqli_fetch_assoc($result);
//             $hash = $row["password"];
//             echo "<script>showNotification('Your password is {$hash}', 'success');</script>";
//         } else {
//             echo "<script>showNotification('Username not found', 'error');</script>";
//             exit();
//         }
//     }
// }

?>