<?php
session_start();
require_once 'config/db_connect.php';

/* ถ้ายังไม่ login ให้ไปหน้า login และกลับมาที่ checkout */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];

/* ถ้า cart ว่าง */
if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$total = 0;

/* คำนวณราคารวม */
foreach ($cart as $menu_id => $qty) {

    $stmt = $conn->prepare("SELECT price FROM menus WHERE id=:id");
    $stmt->execute([':id' => $menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        continue;
    }

    $total += $menu['price'] * $qty;
}

/* บันทึก order */
$insert = $conn->prepare("
INSERT INTO orders(user_id,total_price,status)
VALUES(:user_id,:total,'pending')
");

$insert->execute([
    ':user_id' => $_SESSION['user_id'],
    ':total' => $total
]);

$order_id = $conn->lastInsertId();

/* บันทึก order_items */
foreach ($cart as $menu_id => $qty) {

    $stmt = $conn->prepare("SELECT price FROM menus WHERE id=:id");
    $stmt->execute([':id' => $menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        continue;
    }

    $insertItem = $conn->prepare("
    INSERT INTO order_items(order_id,menu_id,quantity,price)
    VALUES(:order_id,:menu_id,:qty,:price)
    ");

    $insertItem->execute([
        ':order_id' => $order_id,
        ':menu_id' => $menu_id,
        ':qty' => $qty,
        ':price' => $menu['price']
    ]);
}

/* ลบ cart */
unset($_SESSION['cart']);

/* ไปหน้า success */
header("Location: order_success.php?id=" . $order_id);
exit;
?>