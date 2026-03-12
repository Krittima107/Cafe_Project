<?php
session_start();

/* ❌ กัน admin ไม่ให้สั่งซื้อ */
if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'){
    header("Location: admin/dashboard.php");
    exit;
}

/* ตรวจสอบว่ามี menu_id ส่งมาหรือไม่ */
if(!isset($_POST['menu_id'])){
    header("Location: index1.php");
    exit;
}

$menu_id = intval($_POST['menu_id']);
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

/* ถ้าจำนวนต่ำกว่า 1 ให้เป็น 1 */
if($qty < 1){
    $qty = 1;
}

/* สร้าง cart ถ้ายังไม่มี */
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

/* ถ้ามีเมนูนี้อยู่แล้วให้เพิ่มจำนวน */
if(isset($_SESSION['cart'][$menu_id])){
    $_SESSION['cart'][$menu_id] += $qty;
}else{
    $_SESSION['cart'][$menu_id] = $qty;
}

/* กลับไปหน้าเดิมที่กดเพิ่มสินค้า */
if(isset($_SERVER['HTTP_REFERER'])){
    header("Location: " . $_SERVER['HTTP_REFERER']);
}else{
    header("Location: index1.php");
}

exit;
?>