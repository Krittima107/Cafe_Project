<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $menu_id = $_POST['menu_id'];
    $qty = (int) $_POST['quantity'];

    if ($qty > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$menu_id])) {
            $_SESSION['cart'][$menu_id] += $qty;
        } else {
            $_SESSION['cart'][$menu_id] = $qty;
        }
    }
    // เด้งกลับไปหน้าร้านค้า
    header("Location: index1.php");
    exit;
}
?>