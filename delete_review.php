<?php
session_start();
require_once 'config/db_connect.php';

/* ต้องล็อกอินก่อน */
if (!isset($_SESSION['user_id'])) {
    header("Location: index1.php");
    exit;
}

/* ตรวจสอบว่ามีค่า id และ menu_id ส่งมาหรือไม่ */
if (!isset($_GET['id']) || !isset($_GET['menu_id'])) {
    header("Location: index1.php");
    exit;
}

$review_id = $_GET['id'];
$menu_id = $_GET['menu_id'];

/* ลบรีวิวเฉพาะของตัวเอง */
$stmt = $conn->prepare("
DELETE FROM reviews
WHERE id = :id
AND user_id = :user_id
");

$stmt->execute([
    ':id' => $review_id,
    ':user_id' => $_SESSION['user_id']
]);

/* กลับไปหน้าเมนู */
header("Location: menu_detail.php?id=" . $menu_id);
exit;
?>