<?php
session_start();
require_once 'config/db_connect.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลสำหรับ Navbar
$is_admin = ($_SESSION['role'] === 'admin') ? true : false;
$user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
$user_stmt->execute([':id' => $user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

/* ลบเมนูโปรดจากหน้านี้ */
if(isset($_GET['remove'])){
    $menu_id = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id=:user_id AND menu_id=:menu_id");
    $stmt->execute([':user_id' => $user_id, ':menu_id' => $menu_id]);
    header("Location: favorite_menu.php");
    exit;
}

/* ดึงเมนูโปรดทั้งหมดของยูสเซอร์คนนี้ */
$stmt = $conn->prepare("
    SELECT m.* FROM favorites f
    JOIN menus m ON f.menu_id = m.id
    WHERE f.user_id = :user_id
");
$stmt->execute([':user_id' => $user_id]);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เมนูโปรดของฉัน | Moom Marm Cafe</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: var(--color-cream); font-family: 'Prompt', sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .header-title { color: var(--color-brown-dark); border-bottom: 2px solid var(--color-cream); padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-back { padding: 8px 15px; background: var(--color-brown-light); color: white; text-decoration: none; border-radius: 4px; font-size: 14px; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .menu-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #eee; text-decoration: none; color: inherit; display: block; position: relative; transition: 0.3s; }
        .menu-card:hover { transform: translateY(-5px); }
        .menu-img { width: 100%; height: 200px; object-fit: cover; background-color: var(--color-cream); }
        .menu-info { padding: 15px; text-align: center; }
        .menu-title { margin: 0 0 10px 0; font-size: 18px; color: var(--color-brown-dark); }
        .menu-price { font-weight: bold; color: var(--color-green); font-size: 18px; }
        
        .remove-btn { display: block; background: #ff5c5c; color: white; text-decoration: none; padding: 10px; text-align: center; font-weight: bold; transition: 0.3s; border-top: 1px solid #eee; font-size: 14px; }
        .remove-btn:hover { background: #e04a4a; }
        
        .empty-state { text-align: center; padding: 50px 20px; color: #888; }
        .empty-state h3 { color: var(--color-brown-light); margin-bottom: 10px; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="header-title">
                <h2 style="margin: 0;">❤️ เมนูโปรดของฉัน</h2>
                <a href="index1.php" class="btn-back">← กลับหน้าแรก</a>
            </div>

            <?php if(empty($menus)): ?>
                <div class="empty-state">
                    <h3 style="font-size: 24px;">ยังไม่มีเมนูโปรด 😥</h3>
                    <p>ลองไปเลือกดูเมนูอร่อยๆ แล้วกดหัวใจไว้สิครับ!</p>
                    <a href="index1.php" class="btn-back" style="display: inline-block; margin-top: 10px; background: var(--color-green);">☕ ไปดูเมนูเลย</a>
                </div>
            <?php else: ?>
                <div class="menu-grid">
                    <?php foreach($menus as $menu): ?>
                        <div class="menu-card">
                            <a href="menu_detail.php?id=<?php echo $menu['id']; ?>" style="text-decoration: none; color: inherit;">
                                <?php if ($menu['image_name']): ?>
                                    <img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="menu-img">
                                <?php else: ?>
                                    <div class="menu-img" style="display:flex; align-items:center; justify-content:center; color:#999;">ไม่มีรูปภาพ</div>
                                <?php endif; ?>
                                
                                <div class="menu-info">
                                    <h3 class="menu-title"><?php echo $menu['name']; ?></h3>
                                    <div class="menu-price"><?php echo number_format($menu['price'], 2); ?> ฿</div>
                                </div>
                            </a>
                            
                            <a href="?remove=<?php echo $menu['id']; ?>" class="remove-btn" onclick="return confirm('ต้องการลบเมนูนี้ออกจากรายการโปรดหรือไม่?');">
                                ❌ ลบเมนูโปรด
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>