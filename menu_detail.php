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

/* เพิ่มวิว */
$update_views = $conn->prepare("UPDATE menus SET views = views + 1 WHERE id = :id");
$update_views->execute([':id' => $id]);

/* ดึงข้อมูลเมนู */
$stmt = $conn->prepare("
SELECT m.*, c.name as category_name 
FROM menus m 
LEFT JOIN categories c ON m.category_id = c.id 
WHERE m.id = :id AND m.is_available = 1
");
$stmt->execute([':id' => $id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    echo "<div style='text-align:center;padding:50px'>ไม่พบเมนู</div>";
    exit;
}

/* คะแนนเฉลี่ย */
$avg_stmt = $conn->prepare("
SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
FROM reviews
WHERE menu_id = :menu_id
");
$avg_stmt->execute([':menu_id' => $id]);
$avg = $avg_stmt->fetch(PDO::FETCH_ASSOC);

$avg_rating = $avg['avg_rating'] ? round($avg['avg_rating'],1) : 0;
$total_reviews = $avg['total_reviews'];

/* รีวิวทั้งหมด */
$review_stmt = $conn->prepare("
SELECT r.*, u.username
FROM reviews r
JOIN userscafe u ON r.user_id = u.id
WHERE r.menu_id = :menu_id
ORDER BY r.created_at DESC
");
$review_stmt->execute([':menu_id' => $id]);
$reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($menu['name']); ?></title>
<link rel="stylesheet" href="assets/style.css">

<style>

body{
background:var(--color-cream);
font-family:'Prompt',sans-serif;
margin:0;
}

.main-wrapper{
display:flex;
gap:40px;
padding:40px 8%;
}

.main-content{
flex:1;
}

.detail-card{
background:white;
border-radius:12px;
overflow:hidden;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
display:flex;
flex-wrap:wrap;
}

.detail-img{
width:100%;
max-width:350px;
object-fit:cover;
}

.detail-info{
padding:30px;
flex:1;
}

.title{
font-size:32px;
color:var(--color-brown-dark);
}

.price{
font-size:34px;
color:var(--color-green);
font-weight:bold;
margin:20px 0;
}

.btn-back{
background:var(--color-brown-light);
color:white;
padding:8px 15px;
border-radius:5px;
text-decoration:none;
}

.views-counter{
margin-top:20px;
padding-top:15px;
border-top:1px dashed #ccc;
}

.review-section{
flex:1;
}

.review-card{
background:white;
border-radius:12px;
padding:30px;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
}

.review-item{
border-bottom:1px solid #eee;
padding:12px 0;
position:relative;
}

.avg{
font-size:32px;
color:#e7b416;
font-weight:bold;
}

.review-btn{
display:inline-block;
padding:10px 20px;
background:#8b6b5a;
color:white;
border-radius:6px;
text-decoration:none;
margin-bottom:20px;
}

.review-btn:hover{
background:#6f5243;
}

.action-btn{
font-size:13px;
text-decoration:none;
margin-left:10px;
}

.edit-btn{
color:#007bff;
}

.delete-btn{
color:red;
}

.review-actions{
position:absolute;
right:0;
top:10px;
}

.hidden-review{
display:none;
}

.show-more-btn{
margin-top:15px;
padding:8px 20px;
background:#8b6b5a;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

</style>

</head>

<body>

<?php include 'navbar.php'; ?>

<div class="main-wrapper">

<div class="main-content">

<div class="detail-card">

<?php if ($menu['image_name']): ?>
<img src="uploads/menus/<?php echo htmlspecialchars($menu['image_name']); ?>" class="detail-img">
<?php endif; ?>

<div class="detail-info">

<a href="index1.php" class="btn-back">← กลับ</a>

<h1 class="title"><?php echo htmlspecialchars($menu['name']); ?></h1>

<div>
หมวดหมู่ : <?php echo htmlspecialchars($menu['category_name']); ?>
</div>

<div class="price">
<?php echo number_format($menu['price'],2); ?> ฿
</div>

<div class="views-counter">
👁️ ดูแล้ว <?php echo $menu['views']; ?> ครั้ง
</div>

</div>
</div>
</div>


<div class="review-section">

<div class="review-card">

<h3>⭐ คะแนนเฉลี่ย</h3>

<div class="avg">
<?php echo $avg_rating; ?> / 5
</div>

<div style="margin-bottom:20px">
(<?php echo $total_reviews; ?> รีวิว)
</div>

<a class="review-btn" href="write_review.php?menu_id=<?php echo $menu['id']; ?>">
✍️ เขียนรีวิว
</a>

<hr style="margin:25px 0">

<h3>รีวิวจากลูกค้า</h3>

<?php if(count($reviews)==0): ?>
<p>ยังไม่มีรีวิว</p>
<?php endif; ?>

<?php 
$show_limit = 3;
$index = 0;
foreach($reviews as $review): 
$hidden = $index >= $show_limit ? "hidden-review" : "";
?>

<div class="review-item <?php echo $hidden; ?>">

<strong><?php echo htmlspecialchars($review['username']); ?></strong>

<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
<div class="review-actions">

<a class="action-btn edit-btn" href="edit_review.php?id=<?php echo $review['id']; ?>&menu_id=<?php echo $menu['id']; ?>">
แก้ไข
</a>

<a class="action-btn delete-btn" href="delete_review.php?id=<?php echo $review['id']; ?>&menu_id=<?php echo $menu['id']; ?>" onclick="return confirm('ต้องการลบรีวิวหรือไม่');">
ลบ
</a>

</div>
<?php endif; ?>

<br>

<?php echo str_repeat("⭐",$review['rating']); ?>

<br>

<?php echo htmlspecialchars($review['comment']); ?>

</div>

<?php 
$index++;
endforeach; 
?>

<?php if(count($reviews) > 3): ?>

<button id="showMoreBtn" class="show-more-btn">
ดูรีวิวเพิ่มเติม
</button>

<?php endif; ?>

</div>

</div>

</div>

<?php include 'footer.php'; ?>

<script>

const btn = document.getElementById("showMoreBtn");

if(btn){

let expanded = false;

btn.addEventListener("click",function(){

const hiddenReviews = document.querySelectorAll(".hidden-review");

if(!expanded){

hiddenReviews.forEach(function(el){
el.style.display="block";
});

btn.innerText = "ย่อรีวิว";
expanded = true;

}else{

hiddenReviews.forEach(function(el){
el.style.display="none";
});

btn.innerText = "ดูรีวิวเพิ่มเติม";
expanded = false;

}

});

}

</script>

</body>
</html>