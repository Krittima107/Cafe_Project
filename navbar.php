<?php
// โค้ดอัจฉริยะ 1: เช็คว่าไฟล์นี้ถูกเรียกใช้จากโฟลเดอร์ admin หรือไม่ ถ้าใช่ให้เติม ../ หน้าลิงก์
$path = (isset($in_admin) && $in_admin) ? '../' : '';

// โค้ดอัจฉริยะ 2: เช็คชื่อไฟล์ปัจจุบันว่าคือหน้าอะไร
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.navbar{
    background-color: var(--color-brown-light);
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    color:white;
    position:sticky;
    top:0;
    z-index:100;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
    font-family:'Prompt',sans-serif;
}

.navbar h2{
    margin:0;
    display:flex;
    align-items:center;
    gap:10px;
}

.navbar a{
    color:white !important;
    text-decoration:none !important;
    padding:8px 15px;
    background-color:var(--color-brown-dark);
    border-radius:4px;
    font-size:14px;
    transition:0.3s;
}

.navbar a:hover{
    opacity:0.8;
}

.nav-logo-img{
    width:45px;
    height:45px;
    object-fit:cover;
    background-color:white;
    border-radius:50%;
    padding:2px;
}
</style>

<div class="navbar">

<a href="<?php echo $path; ?>index1.php" style="background:none;padding:0;display:flex;align-items:center;">
<h2 style="transition:opacity 0.3s;color:white;"
onmouseover="this.style.opacity='0.8'"
onmouseout="this.style.opacity='1'">

<img src="<?php echo $path; ?>assets/logo.png" class="nav-logo-img">
Moom Marm Cafe
</h2>
</a>

<div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">

<?php if(isset($current_user) && $current_user): ?>

<div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.15);padding:5px 15px;border-radius:30px;border:1px solid rgba(255,255,255,0.3);white-space:nowrap;">

<?php if(!empty($current_user['profile_image'])): ?>

<img src="<?php echo $path; ?>uploads/profiles/<?php echo $current_user['profile_image']; ?>"
style="width:35px;height:35px;border-radius:50%;object-fit:cover;">

<?php else: ?>

<div style="width:35px;height:35px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;font-size:20px;">
👤
</div>

<?php endif; ?>

<div style="line-height:1.2;">
<div style="font-weight:bold;color:white;">
<?php echo htmlspecialchars($current_user['username']); ?>
</div>

<div style="font-size:12px;color:#a5d6a7;display:flex;align-items:center;gap:4px;">
<span style="display:inline-block;width:8px;height:8px;background:#4CAF50;border-radius:50%;box-shadow:0 0 4px #4CAF50;"></span>
ออนไลน์
</div>
</div>

</div>

<?php if(isset($is_admin) && $is_admin): ?>

<a href="<?php echo $path; ?>admin/edit_profile.php"
style="background-color:var(--color-brown-light);white-space:nowrap;">
✏️ แก้ไขโปรไฟล์
</a>

<?php if($current_page !== 'dashboard.php'): ?>

<a href="<?php echo $path; ?>admin/dashboard.php"
style="background-color:var(--color-brown-dark);white-space:nowrap;">
⚙️ Dashboard
</a>

<?php endif; ?>

<?php else: ?>

<a href="<?php echo $path; ?>user_edit_profile.php"
style="background-color:var(--color-brown-light);white-space:nowrap;">
✏️ แก้ไขโปรไฟล์
</a>

<a href="<?php echo $path; ?>favorite_menu.php"
style="background-color:#ff5c5c;white-space:nowrap;">
❤️ เมนูโปรด
</a>

<?php endif; ?>

<a href="<?php echo $path; ?>logout.php"
style="background-color:#d9534f;white-space:nowrap;">
ออกจากระบบ
</a>

<?php else: ?>

<a href="<?php echo $path; ?>login_user.php"
style="background-color:var(--color-green);white-space:nowrap;">
👤 เข้าสู่ระบบ (User)
</a>

<a href="<?php echo $path; ?>login_admin.php"
style="background-color:var(--color-brown-dark);white-space:nowrap;">
🔒 เข้าสู่ระบบ (Admin)
</a>

<?php endif; ?>

</div>
</div>