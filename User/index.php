<?php
include 'header.php';
// include 'connect.php';
?>
<?php

// Get products with selling or discounting status
$query = "SELECT * FROM products 
          WHERE status IN ('selling', 'discounting') 
          ORDER BY RAND()"; // Using RAND() for random ordering
$result = mysqli_query($connect, $query);

$cars = array();
while ($row = mysqli_fetch_assoc($result)) {
    $cars[] = array(
        'name' => $row['car_name'],
        'price' => $row['price'],
        'year' => $row['year_manufacture'],
        'speed' => $row['max_speed'],
        'location' => 'TP.HCM', // You might want to add this field to your database
        'image' => $row['image_link'],
        'enginepower' => $row['engine_power'],
        'engine_name' => $row['engine_name'],
        'seating_capacity' => $row['seat_number'],
        'status' => $row['status']
    );
}

// Get current page number
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$carsPerPage = 3;

// Calculate total pages
$totalCars = count($cars);
$totalPages = ceil($totalCars / $carsPerPage);

// Ensure page is within valid range
$page = max(1, min($page, $totalPages));

// Get cars for current page
$startIndex = ($page - 1) * $carsPerPage;
$currentCars = array_slice($cars, $startIndex, $carsPerPage);
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web kinh doanh xe hàng hiệu</title>
    <!-- <link rel="stylesheet" href="style.css"> -->
    <script src="index.js"></script>
    <link rel="icon" href="dp56vcf7.png" type="image/png">

    <script src="https://kit.fontawesome.com/8341c679e5.js" crossorigin="anonymous"></script>
</head>
<style>
    .ctn-img {
        margin-top: -5.1em;
        position: relative;
        background-color: #EAEDF0 ;

    }

    .ctn-img .wp {
        width: 60vw;
        height: 75vh;
        box-shadow: 10px 10px 20px rgba(0, 0, 0, 0.6);
        margin: 5rem auto;
        overflow: hidden;
        background-color: white;

    }

    .ctn-img .wp-hld {
        display: grid;
        grid-template-columns: repeat(6, 100%);
        height: 100%;
        width: 100%;
        animation: slider 30s ease-in-out infinite alternate;
        padding-bottom: 50px;
    }

    .ctn-img #img1 {
        background-image: url('lambo1.jpg');
        background-size: cover;
        background-position: center;
        max-height: 650px;



    }

    .ctn-img #img2 {
        background-image: url('lambo2.png');
        background-size: cover;
        background-position: center;
        max-height: 650px;
    }

    .ctn-img #img3 {
        background-image: url('lambo3.jpg');
        background-size: cover;
        background-position: center;
        max-height: 650px;
    }

    .ctn-img #img4 {
        background-image: url('lambo4.jpg');
        background-size: cover;
        background-position: center;
        max-height: 650px;
    }

    .ctn-img #img5 {
        background-image: url('lambo5.jpg');
        background-size: cover;
        background-position: center;
        max-height: 650px;
    }

    .ctn-img #img6 {
        background-image: url('lambo6.jpg');
        background-size: cover;
        background-position: center;
        max-height: 650px;
    }

    .ctn-img .btn-hld .btn {
        background-color: rgb(131, 117, 117);
        width: 15px;
        height: 15px;
        border-radius: 15px;
        display: inline-block;
        margin: .3rem;
    }

    .ctn-img .btn-hld {
        position: absolute;
        left: 45%;
        bottom: 0%;
    }

    .btn:hover {
        box-shadow: 0px 0px 7px 4px rgba(0, 255, 255, 0.6);
    }

    @keyframes slider {
        0% {
            transform: translateX(0%);
        }

        10% {
            transform: translateX(-100%);
        }

        20% {
            transform: translateX(-100%);
        }

        30% {
            transform: translateX(-200%);
        }

        40% {
            transform: translateX(-200%);
        }

        50% {
            transform: translateX(-300%);
        }

        60% {
            transform: translateX(-300%);
        }

        70% {
            transform: translateX(-400%);
        }

        80% {
            transform: translateX(-400%);
        }

        90% {
            transform: translateX(-500%);
        }

        100% {
            transform: translateX(-500%);
        }
    }



    #searchbar {
        width: 700px;
        /* Half of the image slider's width */
        margin: 2px auto;
        /* Center the search bar */
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #ffffff;
        padding: 20px;

        border-radius: 15px;
    }

    #searchbar input[type="text"] {
        max-width: 500px;
        /* Adjust as needed */
        padding: 10px;
        min-width: 400px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #F5F5F5
    }

    #searchbar .search:hover {
        border: 1px solid #333;
    }

    #searchbar button {
        padding: 10px;
        border: none;
        background-color: #333;
        color: white;
        cursor: pointer;
        border-radius: 4px;
        margin-left: 5px;
    }

    #searchbar button:hover {
        background-color: #000;
    }
</style>
<style>
    .ds-md-sb {
        background-color: #ffffff;
        padding: 30px;
        margin: 20px;
        margin-left: 320px;
        margin-right: 320px;
        border: 2px solid rgb(227, 219, 219);
        border-radius: 15px;
    }

    #ds-md {
        text-align: center;
    }

    #ctn21 {
        display: flex;
        justify-content: center;
        gap: 20px;
        /* Khoảng cách giữa các phần tử */
    }

    .lgb {
        border: 2px solid gray;
        padding: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 15px;
    }

    .lgb:hover {
        border: 2px solid black;
        padding: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 15px;
    }

    .lg-b {
        max-width: 100px;
        /* Điều chỉnh kích thước hình ảnh nếu cần */
    }

    .lg {
        text-align: center;
        text-decoration: none;
        color: gray;

        font-weight: bold;
    }

    .lg:hover {
        color: rgba(59, 130, 246, 0.5);
    }

    #ds-md-title {
        color: gray;
    }

    #newcar {
        background-color: #ffffff;
        padding: 30px;
        margin: 20px;
        margin-left: 320px;
        margin-right: 320px;
        border: 2px solid rgb(227, 219, 219);
        border-radius: 15px;
    }

    #why {
        background-color: #ffffff;
        padding: 10px;
        margin: 20px;
        margin-left: 320px;
        margin-right: 320px;
        border: 2px solid rgb(227, 219, 219);
        border-radius: 15px;
    }

    .nc-title {
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        background-color: #ffffff;
        padding: -20px;
        /* Thêm padding 10px */
        margin: 10px;

    }

    .why-title {

        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        background-color: #ffffff;
        padding: 10px;
        /* Thêm padding 10px */
        margin: 10px;
        margin-bottom: 50px;
    }

    .hct {
        border: 1px solid #ccc;
        border-radius: 15px;
        background-color: #00B3FC;
        padding: 10px;
        padding-left: 100px;
        padding-right: 100px;
    }

    .wco {
        border: 1px solid #ccc;
        border-radius: 15px;
        background-color: #00B3FC;
        padding: 10px;
        padding-left: 100px;
        padding-right: 100px;
        text-transform: uppercase;
    }

    .morecar {
        border: 1px solid #ccc;
        border-radius: 15px;
        background-color: #00B3FC;
        padding: 10px;
        padding-left: 70px;
        padding-right: 70px;
    }

    .morecar:hover {
        background-color: #007BFF;
    }

    /* .carpic{

    max-height: 60vh;

} */
    .linkcar {
        text-decoration: none;

    }

    .cith2 {
        color: black;
        padding-left: 40px;

    }

    .cit {
        color: gray;
        text-transform: uppercase;
        font-weight: bold;
        padding-left: 40px;

    }

    .nc-item {
        border: 1px solid black;
        margin-bottom: 50px;
        border-radius: 15px;
        overflow: hidden;
        /* Đảm bảo phần vượt quá sẽ bị ẩn */
    }

    .nc-item:hover {
        box-shadow: 0px 0px 10px 2px rgba(0, 0, 0, 0.5);
    }

    .carinfo {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-left: 40px;
        padding-right: 40px;

        color: black;
    }

    .info {
        padding-left: 5px;
        font-family: 'Times New Roman', Times, serif;
    }

    .more {
        text-transform: uppercase;
        text-decoration: none;
        font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        color: #ffffff;
        font-weight: lighter;
        font-size: 25px;
    }
</style>
<style>
    /* Car List Container */
    #newcar {
        padding: 40px;
        background-color: rgb(255, 255, 255);
    }

    /* Grid Layout */
    .ctn21 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 40px;
        margin-top: 20px;
    }

    /* Car Card Styling */
    .nc-item {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .nc-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    /* Car Image */
    .carpic {
        width: 100%;
        height: 200px;
        max-height: 400px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .nc-item:hover .carpic {
        transform: scale(1.05);
    }

    /* Car Details */
    .cith2 {
        color: #007bff;
        padding: 15px;
        margin: 0;
        font-size: 1.2rem;
    }

    .cit {
        color: #666;
        padding: 0 15px 15px;
        margin: 0;
        font-weight: 500;
        text-transform: uppercase;
    }

    /* Car Info Icons */
    .carinfo {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        padding: 15px;
        gap: 10px;
        border-top: 1px solid #eee;
        background: #f8f9fa;
    }

    .carinfo i {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 0.9rem;
    }

    .info {
        font-family: 'Times New Roman', Times, serif;
        padding-left: 5px;
        font-weight: bolder;
        color: #666;
    }

    /* Section Titles */
    /* .nc-title {
    text-align: center;
    margin: 40px 0;
    background-color:#EFEFEF;
} */

    .hct,
    .morecar {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        transition: background-color 0.3s ease;
    }

    .morecar:hover {
        background-color: #0056b3;
    }

    /* Links */
    .linkcar {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .ctn21 {
            padding: 20px;
            gap: 20px;
        }

        .carinfo {
            grid-template-columns: repeat(2, 1fr);
        }

        #newcar {
            padding: 20px;
        }
    }

    /* More Button */
    .more {
        color: white;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 500;
        font-family: Arial, sans-serif;
    }

    body {
        margin: 0;
    }

    .fctn {
        background-color: rgb(220, 220, 220);
    }
</style>
<style>
    /* Add to your existing CSS */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 30px 0;
    }

    .page-link {
        padding: 8px 16px;
        border: 1px solid #007bff;
        border-radius: 4px;
        color: #007bff;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .page-link:hover {
        background-color: #007bff;
        color: white;
    }

    .page-link.active {
        background-color: #007bff;
        color: white;
        pointer-events: none;
    }

    /* Add to your existing pagination styles */
    .page-nav {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 8px 16px;
        background-color: #fff;
        border: 1px solid #007bff;
        color: #007bff;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .page-nav:hover {
        background-color: #007bff;
        color: white;
    }

    .page-nav i {
        font-size: 0.8rem;
    }

    /* Update existing pagination styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin: 30px 0;
    }
</style>
<style>
    /* Add to your existing styles */
    .garage-btn-container {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }

    .garage-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 30px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
    }

    .garage-btn:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .garage-btn i {
        font-size: 1.1rem;
    }

    /* Update Car Info Icons Grid */
    .carinfo {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-gap: 15px;
        padding: 15px;
        border-top: 1px solid #eee;
        background: #f8f9fa;
    }

    .carinfo i {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 0.9rem;
    }

    .info {
        font-family: 'Times New Roman', Times, serif;
        padding-left: 5px;
        font-weight: bold;
        color: #666;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .carinfo {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
<style>
    /* Add to your existing styles */
    .nc-item {
        position: relative;
        max-width: 357px;
    }

    .discount-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #ff4757;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }
</style>
<style>
    /* Enhanced Brand Section */
    .ds-md-sb {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.95);
        transform: translateY(20px);
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .lgb {
        transform: translateY(30px);
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
    }



    /* Enhanced Car Cards */
    .nc-item {
        transform: translateY(30px);
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        white-space:nowrap;
    }

    .nc-item:hover {
        transform: translateY(-10px) scale(1.02);
    }

    .carpic {
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        filter: brightness(0.95);
    }

    .nc-item:hover .carpic {
        transform: scale(1.1);
        filter: brightness(1.1);
    }

    /* Enhanced Car Info */
    .carinfo i {
        position: relative;
        overflow: hidden;
    }


    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    /* Loading Skeleton */
    .skeleton-loader {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        to {
            background-position: -200% 0;
        }
    }
</style>

<style>
    /* Enhanced Search Bar */


    /* Enhanced Brand Section */
    .ds-md-sb {
        background: rgba(255, 255, 255, 0.95);
        padding: 40px;
        margin: 20px 320px;
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        transition: all 0.3s ease;
    }

    #ds-md-title {
        color: #333;
        font-size: 2rem;
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
    }

    #ds-md-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 3px;
        background: linear-gradient(45deg, #007bff, #00b3ff);
        border-radius: 3px;
    }

    #ctn21 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 25px;
        padding: 20px;
    }

    .lgb {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .lgb::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent);
        transition: 0.5s;
    }

    .lgb:hover::before {
        left: 100%;
    }

    .lgb:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }


    .lg-b {
        max-width: 100px;

        transition: transform 0.3s ease;
    }

    .lgb:hover .lg-b {
        transform: scale(1.1);
    }

    .lg {
        color: #666;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        transition: color 0.3s ease;
    }

    .lg:hover {
        color: #007bff;
    }

    @media (max-width: 1200px) {
        .ds-md-sb {
            margin: 20px;
        }

        #searchbar {
            width: 90%;
        }
    }

    @media (max-width: 768px) {
        #ctn21 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        #ctn21 {
            grid-template-columns: 1fr;
        }
    }
</style>
<style>
    /* Update the brand section styles */
    #ctn21 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
        padding: 20px;
        width: 60%;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Enhanced hot car title */
    .nc-title {
        margin-bottom: 40px;
    }

    .hot-car {
        margin: 0;
        padding: 0;
    }

    .hct {
        display: inline-block;
        background: linear-gradient(45deg, #007bff, #00b3ff);
        color: white;
        padding: 15px 60px;
        border-radius: 30px;
        font-size: 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .hct::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.2) 50%,
                transparent 100%);
        animation: shimmer 2s infinite linear;
    }

    @keyframes shimmer {
        100% {
            left: 100%;
        }
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        #ctn21 {
            grid-template-columns: repeat(4, 1fr);
            padding: 15px;
            gap: 15px;
        }
    }

    @media (max-width: 992px) {
        #ctn21 {
            grid-template-columns: repeat(3, 1fr);
        }

        .hct {
            padding: 12px 40px;
            font-size: 1.3rem;
        }
    }

    @media (max-width: 768px) {
        #ctn21 {
            grid-template-columns: repeat(2, 1fr);
        }

        .hct {
            padding: 10px 30px;
            font-size: 1.2rem;
        }
    }

    @media (max-width: 480px) {
        #ctn21 {
            grid-template-columns: repeat(1, 1fr);
        }
    }
    .br{
        background-color:#EAEDF0;
    }
     body.dark-theme .ctn-img{
                background-color: #33475C;
            }
            /* Brand section dropdown */
            body.dark-theme .ds-md-sb {
                background: rgba(44, 62, 80, 0.95);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            }
            
            /* New car section */
            body.dark-theme .newcar {
                background-color: #2c3e50;
                border: 1px solid #445566;

            }
            
            body.dark-theme .newcar .title {
                color: #3498db;
                border-bottom: 2px solid #445566;
            }
            
            body.dark-theme .newcar .description,
            body.dark-theme #ds-md-title {
                color: #bdc3c7;
            }
            body.dark-theme .ctn-img{
                background-color: #33475C;
            }
            body.dark-theme .wp{
                background-color:rgb(62, 86, 112);
            }
            /* Dark theme styles for main */
body.dark-theme main,
body.dark-theme .lgb
 {
    background-color:rgb(43, 59, 77);  /* Dark background for dark theme */
}
body.dark-theme #newcar,
body.dark-theme .nc-title,
body.dark-theme .pagination{
     background-color:rgb(62, 86, 112);
      color:#e0e0e0;
}
body.dark-theme .garage-btn,
body.dark-theme .info{
    color:#e0e0e0;
}


body.dark-theme #searchbar{
    background:#2C3E50!important;
}
body.dark-theme .lg{
    color: #e0e0e0;
}
</style>

<body>

    <div class="br" style="height:5vh;"></div>
    <main>
        <div class="fctn">

            <div class="ctn-img">
                <div class="wp">
                    <div class="wp-hld">
                        <div id="img1"></div>
                        <div id="img2"></div>
                        <div id="img3"></div>
                        <div id="img4"></div>
                        <div id="img5"></div>
                        <div id="img6"></div>
                    </div>
                </div>
                <div class="btn-hld">
                    <a href="#img1" class="btn" id="btn1"></a>
                    <a href="#img2" class="btn" id="btn2"></a>
                    <a href="#img3" class="btn" id="btn3"></a>
                    <a href="#img4" class="btn" id="btn4"></a>
                    <a href="#img5" class="btn" id="btn5"></a>
                    <a href="#img6" class="btn" id="btn6"></a>
                </div>
            </div>
        </div>
        <!-- Replace the static brand section with this dynamic one -->
        <div class="ds-md-sb">
            <div id="searchbar">
                <form action="search-results.php" method="GET" style="display: flex; align-items: center;">
                    <input type="text" class="search" id="search" name="query"
                        placeholder="Nhập hãng xe vd: Lamborghini,...."
                        style="flex: 1; padding: 10px; font-size: 16px;">
                    <button type="submit"
                        style="padding: 10px 20px; font-size: 16px; margin-left: 5px; cursor: pointer;">
                        <i class="fa fa-search"></i>
                    </button>
                </form>
            </div>

            <div id="ds-md">
                <h1 id="ds-md-title">Kiểu Dáng/ Hãng Xe Phổ Biến</h1>
                <div id="ctn21">
                    <?php
                    // Query to get all car types/brands
                    $brand_query = "SELECT * FROM car_types ORDER BY type_name";
                    $brand_result = mysqli_query($connect, $brand_query);

                    while ($brand = mysqli_fetch_assoc($brand_result)) {
                        echo '<div class="lgb">';
                        echo '<a style="text-transform:uppercase;" class="lg" href="brand.php?type=' . urlencode($brand['type_name']) . '">';
                        echo '<img class="lg-b" src="' . htmlspecialchars($brand['logo_url']) . '" alt="' . htmlspecialchars($brand['type_name']) . '">';
                        echo '<br>';
                        echo htmlspecialchars($brand['type_name']);
                        echo '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div id="view"></div>

        <div id="newcar">
            <div class="nc-title">
                <h2 class="hot-car">
                    <span class="hct">

                        Xe Bán Chạy
                    </span>
                </h2>

            </div>
            <div class="ctn21">
                <?php foreach ($currentCars as $car): ?>
                    <div class="nc-item" data-aos="fade-up">
                        <a href="car-details.php?name=<?= urlencode($car['name']) ?>" class="linkcar">
                            <img class="carpic" data-src="<?= $car['image'] ?>"
                                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                alt="<?= $car['name'] ?>">
                            <h2 class="cith2"><?= number_format($car['price'], 0, ',', '.') ?> VND</h2>
                            <p class="cit"><?= $car['name'] ?></p>
                            <div class="carinfo">
                                <i class="fas fa-car">
                                    <span class="info"><?= $car['seating_capacity'] ?> chỗ</span>
                                </i>
                                <i class="fa-solid fa-gear"> <!-- Changed from fas fa-engine -->
                                    <span class="info"><?= $car['engine_name'] ?></span>
                                </i>
                                <i class="fa-solid fa-wrench"> <!-- Changed to engine icon -->
                                    <span class="info"><?= $car['enginepower'] ?>&nbsp;Mã lực</span>
                                </i>
                                <i class="fas fa-tachometer-alt">
                                    <span class="info"><?= $car['speed'] ?>&nbsp;km/h</span>
                                </i>
                                <i class="fas fa-calendar-alt">
                                    <span class="info"><?= $car['year'] ?></span>
                                </i>
                                <i class="fa-solid fa-location-dot">
                                    <span class="info"><?= $car['location'] ?></span>
                                </i>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <!-- Replace the existing pagination div -->
            <!-- Replace the existing pagination div -->
            <div class="pagination">
                <?php if ($totalPages > 1): ?>
                    <!-- Previous button -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>#view" class="page-link">
                            <i class="fas fa-chevron-left"></i> Trang trước
                        </a>
                    <?php endif; ?>

                    <?php
                    // Calculate range of pages to show
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="?page=1#view" class="page-link">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="page-dots">...</span>';
                        }
                    }

                    // Show pages around current page
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<a href="?page=' . $i . '#view" class="page-link ' . ($i === $page ? 'active' : '') . '">' . $i . '</a>';
                    }

                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="page-dots">...</span>';
                        }
                        echo '<a href="?page=' . $totalPages . '#view" class="page-link">' . $totalPages . '</a>';
                    }

                    // Next button
                    if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>#view" class="page-link">
                            Trang sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- Add this after the pagination div -->
            <div class="garage-btn-container">
                <a href="more.php" class="garage-btn">
                    <i class="fas fa-warehouse"></i>
                    Garage Xe
                </a>
            </div>

        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lazy loading for images
            const images = document.querySelectorAll('.carpic');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('skeleton-loader');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => {
                img.classList.add('skeleton-loader');
                imageObserver.observe(img);
            });

            // Smooth scroll for navigation
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Enhanced hover effects for car cards
            const cards = document.querySelectorAll('.nc-item');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                    this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.1)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                });
            });

            // Animate numbers in price
            document.querySelectorAll('.cith2').forEach(price => {
                const originalText = price.textContent;
                const number = parseInt(originalText.replace(/[^\d]/g, ''));

                const animate = () => {
                    let start = 0;
                    const duration = 1000;
                    const step = timestamp => {
                        if (!start) start = timestamp;
                        const progress = Math.min((timestamp - start) / duration, 1);
                        const current = Math.floor(progress * number);
                        price.textContent = current.toLocaleString('vi-VN') + ' VND';
                        if (progress < 1) {
                            window.requestAnimationFrame(step);
                        }
                    };
                    window.requestAnimationFrame(step);
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animate();
                            observer.unobserve(entry.target);
                        }
                    });
                });

                observer.observe(price);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Stagger animation for brand logos
            const brandItems = document.querySelectorAll('.lgb');
            brandItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
            });

            // Enhanced search input interaction
            const searchInput = document.querySelector('#searchbar input[type="text"]');
            const searchForm = document.querySelector('#searchbar form');

            searchInput.addEventListener('focus', () => {
                searchForm.style.transform = 'scale(1.02)';
            });

            searchInput.addEventListener('blur', () => {
                searchForm.style.transform = 'scale(1)';
            });

            // Typing animation for search placeholder
            const placeholders = [
                "Tìm kiếm xe yêu thích...",
                "Nhập tên thương hiệu...",
                "Tìm theo giá: <1000000000",
                "Tìm theo năm: >2020",
                "Tìm theo động cơ: V8",
                "Tìm theo màu: đen"
            ];

            let currentPlaceholder = 0;
            let currentChar = 0;
            let isDeleting = false;
            let isPaused = false;

            function typeEffect() {
                const current = placeholders[currentPlaceholder];

                if (!isDeleting && currentChar <= current.length) {
                    searchInput.setAttribute('placeholder', current.substring(0, currentChar));
                    currentChar++;
                    setTimeout(typeEffect, 100);
                } else if (isDeleting && currentChar >= 0) {
                    searchInput.setAttribute('placeholder', current.substring(0, currentChar));
                    currentChar--;
                    setTimeout(typeEffect, 50);
                } else if (currentChar <= 0) {
                    isDeleting = false;
                    currentPlaceholder = (currentPlaceholder + 1) % placeholders.length;
                    setTimeout(typeEffect, 500);
                } else {
                    isPaused = true;
                    isDeleting = true;
                    setTimeout(typeEffect, 2000);
                }
            }

            typeEffect();
        });
    </script>
</body>

</html>

<?php
include 'footer.php';
?>