<?php
session_start();
require_once 'config/db_connect.php';

$is_admin = false;
$current_user = null;

if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
    $user_stmt->execute([':id' => $_SESSION['user_id']]);
    $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($current_user && $current_user['role'] === 'admin') {
        $is_admin = true;
    }
}

if (!isset($_GET['id'])) {
    header("Location: index1.php");
    exit;
}

$id = $_GET['id'];

$update_views = $conn->prepare("UPDATE menus SET views = views + 1 WHERE id = :id");
$update_views->execute([':id' => $id]);

$stmt = $conn->prepare("
SELECT m.*, c.name as category_name 
FROM menus m 
LEFT JOIN categories c ON m.category_id = c.id 
WHERE m.id = :id AND m.is_available = 1
");

$stmt->execute([':id' => $id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    echo "<div style='text-align:center; padding: 50px;'>ไม่พบเมนูนี้</div>";
    exit;
}

/* ------------------ FAVORITE SYSTEM ------------------ */

$is_favorite = false;

if(isset($_SESSION['user_id'])){
    $fav_stmt = $conn->prepare("
    SELECT id FROM favorites
    WHERE user_id = :user_id AND menu_id = :menu_id
    ");

    $fav_stmt->execute([
        ':user_id'=>$_SESSION['user_id'],
        ':menu_id'=>$menu['id']
    ]);

    if($fav_stmt->fetch()){
        $is_favorite = true;
    }
}

/* ---------------------------------------------------- */

?>

<!DOCTYPE html>
<html lang="th">

<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($menu['name']); ?> | Cafe Menu</title>
<link rel="stylesheet" href="assets/style.css">

<style>

body{
background-color:var(--color-cream);
font-family:'Prompt',sans-serif;
margin:0;
display:flex;
flex-direction:column;
min-height:100vh;
}

.main-content{
display:flex;
justify-content:center;
align-items:center;
flex:1;
padding:40px 20px;
}

.detail-card{
background:white;
max-width:900px;
width:100%;
border-radius:12px;
overflow:hidden;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
display:flex;
flex-wrap:wrap;
}

.detail-img{
width:100%;
max-width:450px;
min-height:400px;
object-fit:cover;
background:#f9f9f9;
}

.detail-info{
padding:40px;
flex:1;
min-width:300px;
display:flex;
flex-direction:column;
}

.btn-back{
display:inline-block;
padding:8px 15px;
background:var(--color-brown-light);
color:white;
text-decoration:none;
border-radius:4px;
margin-bottom:20px;
}

.title{
color:var(--color-brown-dark);
font-size:32px;
margin-bottom:10px;
}

.category{
color:#888;
margin-bottom:20px;
}

.serve-type{
background:var(--color-cream);
padding:5px 15px;
border-radius:20px;
border:1px solid var(--color-brown-light);
margin-bottom:20px;
display:inline-block;
}

.price{
font-size:36px;
color:var(--color-green);
font-weight:bold;
margin-bottom:20px;
}

.favorite-btn{
border:none;
padding:10px 20px;
border-radius:25px;
cursor:pointer;
font-size:14px;
color:white;
}

.favorite-add{
background:#ff5c5c;
}

.favorite-remove{
background:#777;
}

.views-counter{
margin-top:auto;
padding-top:20px;
border-top:1px dashed var(--color-brown-light);
font-size:14px;
}

</style>

</head>

<body>

<?php include 'navbar.php'; ?>

<div class="main-content">

<div class="detail-card">

<?php if ($menu['image_name']): ?>

<img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="detail-img">

<?php else: ?>

<div class="detail-img" style="display:flex;align-items:center;justify-content:center;color:#999;">
ไม่มีรูปภาพ
</div>

<?php endif; ?>


<div class="detail-info">

<a href="index1.php" class="btn-back">← กลับไปเลือกเมนูต่อ</a>

<h1 class="title"><?php echo $menu['name']; ?></h1>

<div class="category">
หมวดหมู่: <?php echo $menu['category_name']; ?>
</div>

<div>
<span class="serve-type">
รูปแบบ: <?php echo $menu['description']; ?>
</span>
</div>

<div class="price">
<?php echo number_format($menu['price'],2); ?> ฿
</div>


<?php if(isset($_SESSION['user_id'])): ?>

<form action="favorite_process.php" method="POST">

<input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">

<?php if($is_favorite): ?>

<button class="favorite-btn favorite-remove">
💔 ลบออกจากเมนูโปรด
</button>

<?php else: ?>

<button class="favorite-btn favorite-add">
❤️ เพิ่มเมนูโปรด
</button>

<?php endif; ?>

</form>

<?php else: ?>

<p style="color:#999;">
เข้าสู่ระบบก่อนเพื่อเพิ่มเมนูโปรด
</p>

<?php endif; ?>


<div class="views-counter">
👁️ เมนูนี้มีคนเข้าดูแล้ว
<strong><?php echo $menu['views']; ?></strong>
ครั้ง
</div>

</div>
</div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>