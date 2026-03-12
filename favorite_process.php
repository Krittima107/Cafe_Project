<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['menu_id'])) {
    $user_id = $_SESSION['user_id'];
    $menu_id = $_POST['menu_id'];

    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND menu_id = :menu_id");
    $check->execute([':user_id' => $user_id, ':menu_id' => $menu_id]);

    if ($check->fetch()) {
        // ถ้ามีอยู่แล้วให้ลบออก (Toggle Off)
        $delete = $conn->prepare("DELETE FROM favorites WHERE user_id=:user_id AND menu_id=:menu_id");
        $delete->execute([':user_id' => $user_id, ':menu_id' => $menu_id]);
    } else {
        // ถ้ายังไม่มีให้เพิ่มเข้าไป (Toggle On)
        $insert = $conn->prepare("INSERT INTO favorites(user_id, menu_id) VALUES(:user_id, :menu_id)");
        $insert->execute([':user_id' => $user_id, ':menu_id' => $menu_id]);
    }

    // เด้งกลับไปที่หน้ารายละเอียดเมนูเดิม
    header("Location: menu_detail.php?id=" . $menu_id);
    exit;
}

header("Location: index1.php");
exit;
?>