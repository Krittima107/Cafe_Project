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

$cart = $_SESSION['cart'] ?? [];
?>

<!DOCTYPE html>
<html lang="th">
<head>

<meta charset="UTF-8">
<title>ตะกร้าสินค้า</title>

<link rel="stylesheet" href="assets/style.css">

<style>

.main-container{
max-width:1000px;
margin:auto;
padding:40px 20px;
}

.cart-card{
background:white;
padding:30px;
border-radius:12px;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
}

.cart-title{
font-size:28px;
margin-bottom:20px;
color:var(--color-brown-dark);
}

.cart-table{
width:100%;
border-collapse:collapse;
}

.cart-table th{
background:var(--color-brown-light);
color:white;
padding:12px;
}

.cart-table td{
padding:12px;
border-bottom:1px solid #eee;
text-align:center;
}

.cart-img{
width:60px;
height:60px;
object-fit:cover;
border-radius:6px;
}

.type-badge{
background:#eee;
padding:4px 10px;
border-radius:20px;
font-size:13px;
}

.qty-box{
display:flex;
justify-content:center;
align-items:center;
gap:8px;
}

.qty-btn{
width:28px;
height:28px;
border:none;
background:#ddd;
border-radius:4px;
cursor:pointer;
font-weight:bold;
}

.qty-btn:hover{
background:#ccc;
}

.qty-number{
min-width:20px;
}

.remove-btn{
font-size:20px;
text-decoration:none;
}

.remove-btn:hover{
opacity:0.7;
}

.total-box{
margin-top:20px;
text-align:right;
font-size:22px;
font-weight:bold;
color:var(--color-green);
}

.checkout-btn{
margin-top:20px;
display:inline-block;
padding:12px 25px;
background:var(--color-brown-dark);
color:white;
text-decoration:none;
border-radius:6px;
font-size:16px;
transition:0.3s;
border:none;
cursor:pointer;
}

.checkout-btn:hover{
opacity:0.8;
}

.empty-cart{
text-align:center;
padding:40px;
font-size:18px;
}

</style>

</head>

<body>

<?php include 'navbar.php'; ?>

<div class="main-container">

<div class="cart-card">

<div class="cart-title">🛒 ตะกร้าสินค้า</div>

<?php if(empty($cart)): ?>

<div class="empty-cart">
ยังไม่มีสินค้าในตะกร้า ☕
<br><br>
<a href="index1.php" class="checkout-btn">เลือกเมนู</a>
</div>

<?php else: ?>

<form action="checkout.php" method="post" id="cartForm">

<table class="cart-table">

<tr>
<th>เลือก</th>
<th>รูป</th>
<th>เมนู</th>
<th>ประเภท</th>
<th>รูปแบบ</th>
<th>ราคา</th>
<th>จำนวน</th>
<th>รวม</th>
<th>ลบ</th>
</tr>

<tbody>

<?php

foreach($cart as $menu_id => $qty){

$stmt = $conn->prepare("
SELECT menus.*, categories.name AS category_name
FROM menus
LEFT JOIN categories ON menus.category_id = categories.id
WHERE menus.id=:id
");

$stmt->execute([':id'=>$menu_id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$menu){ continue; }

$subtotal = $menu['price'] * $qty;

?>

<tr data-price="<?php echo $menu['price']; ?>">

<td>
<input type="checkbox" class="item-check" checked>
</td>

<td>

<?php if(!empty($menu['image_name'])): ?>

<img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="cart-img">

<?php else: ?>

<div style="width:60px;height:60px;background:#eee;border-radius:6px;display:flex;align-items:center;justify-content:center;">
☕
</div>

<?php endif; ?>

</td>

<td><?php echo htmlspecialchars($menu['name']); ?></td>

<td><?php echo htmlspecialchars($menu['category_name']); ?></td>

<td>
<span class="type-badge">
<?php echo htmlspecialchars($menu['description']); ?>
</span>
</td>

<td class="price">
<?php echo number_format($menu['price'],2); ?>
</td>

<td>

<div class="qty-box">

<button type="button" class="qty-btn minus">-</button>

<span class="qty-number"><?php echo $qty; ?></span>

<button type="button" class="qty-btn plus">+</button>

</div>

</td>

<td class="subtotal">
<?php echo number_format($subtotal,2); ?>
</td>

<td>
<a href="remove_cart.php?id=<?php echo $menu_id; ?>" class="remove-btn">
🗑️
</a>
</td>

</tr>

<?php } ?>

</tbody>

</table>

<div class="total-box">
รวมทั้งหมด : <span id="totalPrice">0.00</span> ฿
</div>

<br>

<button type="submit" class="checkout-btn">
✅ สั่งซื้อสินค้า
</button>

</form>

<?php endif; ?>

</div>
</div>

<script>

function calculateTotal(){

let total = 0;

document.querySelectorAll("tbody tr").forEach(row=>{

let checkbox = row.querySelector(".item-check");

if(checkbox && checkbox.checked){

let subtotal = parseFloat(row.querySelector(".subtotal").innerText);

if(!isNaN(subtotal)){
total += subtotal;
}

}

});

document.getElementById("totalPrice").innerText = total.toFixed(2);

}

function updateSubtotal(row){

let price = parseFloat(row.dataset.price);
let qty = parseInt(row.querySelector(".qty-number").innerText);

let subtotal = price * qty;

row.querySelector(".subtotal").innerText = subtotal.toFixed(2);

}

document.querySelectorAll(".plus").forEach(btn=>{

btn.addEventListener("click",function(){

let row = this.closest("tr");
let qtyEl = row.querySelector(".qty-number");

qtyEl.innerText = parseInt(qtyEl.innerText) + 1;

updateSubtotal(row);
calculateTotal();

});

});

document.querySelectorAll(".minus").forEach(btn=>{

btn.addEventListener("click",function(){

let row = this.closest("tr");
let qtyEl = row.querySelector(".qty-number");

let qty = parseInt(qtyEl.innerText);

if(qty > 1){

qtyEl.innerText = qty - 1;

updateSubtotal(row);
calculateTotal();

}

});

});

document.querySelectorAll(".item-check").forEach(cb=>{
cb.addEventListener("change",calculateTotal);
});

calculateTotal();

</script>

</body>
</html>