<?php
session_start();
require_once 'config/db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$menu_id = $_POST['menu_id'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

$stmt = $conn->prepare("
INSERT INTO reviews (user_id,menu_id,rating,comment)
VALUES (:user_id,:menu_id,:rating,:comment)
");

$stmt->execute([
'user_id'=>$user_id,
'menu_id'=>$menu_id,
'rating'=>$rating,
'comment'=>$comment
]);

header("Location: menu_detail.php?id=".$menu_id);
exit;
?>