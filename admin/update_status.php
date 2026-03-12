<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index1.php");
    exit;
}

$id = $_GET['id'];
$status = $_GET['status'];

$stmt = $conn->prepare("
UPDATE orders
SET status = :status
WHERE id = :id
");

$stmt->execute([
':status'=>$status,
':id'=>$id
]);

header("Location: orders.php");
exit;