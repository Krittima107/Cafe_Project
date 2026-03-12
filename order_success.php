<?php
session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>

<meta charset="UTF-8">
<title>สั่งซื้อสำเร็จ</title>

<link rel="stylesheet" href="assets/style.css">

<style>

.success-box{
max-width:600px;
margin:80px auto;
background:white;
padding:40px;
text-align:center;
border-radius:12px;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
}

.success-icon{
font-size:60px;
margin-bottom:20px;
}

.btn-home{
display:inline-block;
margin-top:20px;
padding:12px 25px;
background:var(--color-brown-dark);
color:white;
text-decoration:none;
border-radius:6px;
}

</style>

</head>

<body>

<?php include 'navbar.php'; ?>

<div class="success-box">

<div class="success-icon">✅</div>

<h2>สั่งซื้อสำเร็จ</h2>

<p>ขอบคุณที่ใช้บริการ Cafe Menu</p>

<a href="index1.php" class="btn-home">
กลับหน้าหลัก
</a>

</div>

<?php include 'footer.php'; ?>

</body>
</html>