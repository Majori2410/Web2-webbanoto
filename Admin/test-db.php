<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// final commit
include '../User/connect.php';

echo "<h2>Test ket noi database</h2>";

if ($connect) {
    echo "<p style='color:green;'>Da ket noi database thanh cong</p>";
} else {
    echo "<p style='color:red;'>Khong ket noi duoc database</p>";
}

$sql = "SELECT COUNT(*) AS total FROM products";
$result = mysqli_query($connect, $sql);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>So san pham trong bang products: " . $row['total'] . "</p>";
} else {
    echo "<p style='color:red;'>Loi query: " . mysqli_error($connect) . "</p>";
}
?>