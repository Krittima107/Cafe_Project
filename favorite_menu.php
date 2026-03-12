<?php
session_start();
require_once 'config/db_connect.php';

if(!isset($_SESSION['user_id'])){
header("Location: login_user.php");
exit;
}

$user_id=$_SESSION['user_id'];

/* ลบเมนูโปรด */
if(isset($_GET['remove'])){
$menu_id=$_GET['remove'];

$stmt=$conn->prepare("DELETE FROM favorites WHERE user_id=:user_id AND menu_id=:menu_id");
$stmt->execute([
':user_id'=>$user_id,
':menu_id'=>$menu_id
]);
}

/* ดึงเมนูโปรด */
$stmt=$conn->prepare("
SELECT m.*
FROM favorites f
JOIN menus m ON f.menu_id=m.id
WHERE f.user_id=:user_id
");

$stmt->execute([':user_id'=>$user_id]);
$menus=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>

.menu-container{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
gap:20px;
margin-top:20px;
}

.menu-card{
border:1px solid #ddd;
border-radius:10px;
padding:15px;
text-align:center;
background:white;
box-shadow:0 2px 6px rgba(0,0,0,0.1);
transition:0.3s;
}

.menu-card:hover{
transform:translateY(-5px);
}

.menu-card img{
width:100%;
height:150px;
object-fit:cover;
border-radius:8px;
}

.price{
color:#ff6b6b;
font-weight:bold;
margin-top:5px;
}

.remove-btn{
display:inline-block;
margin-top:10px;
background:#d9534f;
color:white;
padding:6px 12px;
border-radius:5px;
text-decoration:none;
}

</style>

<h2>❤️ เมนูโปรดของฉัน</h2>

<?php if(empty($menus)): ?>
<p>ยังไม่มีเมนูโปรด</p>
<?php else: ?>

<div class="menu-container">

<?php foreach($menus as $menu): ?>

<div class="menu-card">

<img src="uploads/menus/<?php echo $menu['image_name']; ?>">

<h3><?php echo $menu['name']; ?></h3>

<p class="price"><?php echo number_format($menu['price'],2); ?> ฿</p>

<a href="?remove=<?php echo $menu['id']; ?>" class="remove-btn">
❌ ลบเมนูโปรด
</a>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>