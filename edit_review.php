<?php
session_start();
require_once 'config/db_connect.php';

if(!isset($_SESSION['user_id'])){
header("Location:index1.php");
exit;
}

if(!isset($_GET['id']) || !isset($_GET['menu_id'])){
header("Location:index1.php");
exit;
}

$review_id = $_GET['id'];
$menu_id = $_GET['menu_id'];

/* ดึงข้อมูลรีวิว */
$stmt = $conn->prepare("
SELECT * FROM reviews
WHERE id = :id AND user_id = :user_id
");

$stmt->execute([
':id'=>$review_id,
':user_id'=>$_SESSION['user_id']
]);

$review = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$review){
echo "ไม่พบรีวิว";
exit;
}

/* บันทึกการแก้ไข */
if(isset($_POST['update_review'])){

$rating = $_POST['rating'];
$comment = $_POST['comment'];

$update = $conn->prepare("
UPDATE reviews
SET rating = :rating, comment = :comment
WHERE id = :id AND user_id = :user_id
");

$update->execute([
':rating'=>$rating,
':comment'=>$comment,
':id'=>$review_id,
':user_id'=>$_SESSION['user_id']
]);

header("Location:menu_detail.php?id=".$menu_id);
exit;

}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขรีวิว</title>

<style>

body{
font-family:Prompt;
background:#f5e6c8;
padding:50px;
}

.review-box{
max-width:500px;
margin:auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 10px 20px rgba(0,0,0,0.1);
}

textarea{
width:100%;
height:120px;
padding:10px;
}

.star{
font-size:28px;
cursor:pointer;
color:#ccc;
}

.star.active{
color:#f5b301;
}

button{
margin-top:15px;
padding:10px 20px;
background:#8b6b5a;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

.cancel-btn{
background:#999;
margin-left:10px;
text-decoration:none;
padding:10px 20px;
color:white;
border-radius:6px;
}

</style>

</head>

<body>

<div class="review-box">

<h2>แก้ไขรีวิว</h2>

<form method="POST">

<input type="hidden" name="rating" id="rating" value="<?php echo $review['rating']; ?>">

<div id="stars">

<span class="star" data-value="1">★</span>
<span class="star" data-value="2">★</span>
<span class="star" data-value="3">★</span>
<span class="star" data-value="4">★</span>
<span class="star" data-value="5">★</span>

</div>

<br>

<textarea name="comment"><?php echo htmlspecialchars($review['comment']); ?></textarea>

<br>

<button type="submit" name="update_review">บันทึกการแก้ไข</button>

<a class="cancel-btn" href="menu_detail.php?id=<?php echo $menu_id; ?>">ยกเลิก</a>

</form>

</div>

<script>

const stars = document.querySelectorAll(".star");
const ratingInput = document.getElementById("rating");

function setStars(rating){

stars.forEach(star=>{
star.classList.remove("active");

if(star.dataset.value <= rating){
star.classList.add("active");
}

});

}

setStars(ratingInput.value);

stars.forEach(star=>{

star.addEventListener("click",function(){

const value = this.dataset.value;

ratingInput.value = value;

setStars(value);

});

});

</script>

</body>
</html>