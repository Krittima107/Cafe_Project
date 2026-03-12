<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index1.php");
    exit;
}

/* ดึงข้อมูล user สำหรับ navbar */
$user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
$user_stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = true;
$in_admin = true;

/* รับ order id */
$order_id = $_GET['id'] ?? 0;

/* ดึงรายการสินค้า */
$stmt = $conn->prepare("
SELECT order_items.*, menus.name
FROM order_items
JOIN menus ON order_items.menu_id = menus.id
WHERE order_items.order_id = :id
");
$stmt->execute([':id'=>$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* คำนวณยอดรวม */
$total = 0;
foreach($items as $item){
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
<meta charset="UTF-8">
<title>Order Detail</title>
<link rel="stylesheet" href="../assets/style.css">

<style>

body{
margin:0;
display:flex;
flex-direction:column;
min-height:100vh;
background-color:var(--color-cream);
font-family:'Prompt',sans-serif;
}

.main-content{
flex:1;
padding-bottom:40px;
}

.container{
max-width:1100px;
margin:40px auto;
padding:30px;
background:white;
border-radius:8px;
box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
padding:12px;
text-align:center;
border:1px solid #ddd;
}

th{
background:var(--color-cream);
color:var(--color-brown-dark);
}

.total-row{
font-weight:bold;
background:#f9f9f9;
}

.btn-complete{
padding:10px 20px;
background:#4caf50;
color:white;
border:none;
border-radius:6px;
font-size:16px;
cursor:pointer;
}

.btn-center{
text-align:center;
margin-top:25px;
}

</style>

</head>

<body>

<?php include '../navbar.php'; ?>

<div class="main-content">

<div class="container">

<h2 style="margin-bottom:20px;color:var(--color-brown-dark);">
📋 รายละเอียด Order #<?php echo $order_id; ?>
</h2>

<table>

<tr>
<th>เมนู</th>
<th>จำนวน</th>
<th>ราคา</th>
<th>รวม</th>
</tr>

<?php foreach($items as $item): ?>

<tr>
<td><?php echo $item['name']; ?></td>
<td><?php echo $item['quantity']; ?></td>
<td><?php echo number_format($item['price'],2); ?></td>
<td><?php echo number_format($item['price'] * $item['quantity'],2); ?> ฿</td>
</tr>

<?php endforeach; ?>

<tr class="total-row">
<td colspan="3" style="text-align:center;">รวมทั้งหมด</td>

<td><?php echo number_format($total,2); ?> ฿</td>
</tr>

</table>

<div class="btn-center">

<a href="update_status.php?id=<?php echo $order_id; ?>&status=completed">
<button class="btn-complete">
✅ เสร็จสิ้น
</button>
</a>

</div>

</div>

</div>

<?php include '../footer.php'; ?>

</body>
</html>