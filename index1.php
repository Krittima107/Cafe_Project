<?php
session_start();
require_once 'config/db_connect.php';

// --- เช็คสถานะการล็อกอินและดึงข้อมูลผู้ใช้ ---
$is_admin = false;
$current_user = null;

if (isset($_SESSION['user_id'])) {
    // ดึงข้อมูลผู้ใช้จากฐานข้อมูลเพื่อเอามาโชว์ที่หน้าแรก
    $user_stmt = $conn->prepare("SELECT username, profile_image, role FROM userscafe WHERE id = :id");
    $user_stmt->execute([':id' => $_SESSION['user_id']]);
    $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($current_user && $current_user['role'] === 'admin') {
        $is_admin = true;
    }
}

// --- รับค่าการค้นหาและหมวดหมู่ ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_cat = isset($_GET['category']) ? $_GET['category'] : '';

// --- สร้าง Query สำหรับดึงเมนู ---
$sql = "SELECT m.*, c.name as category_name 
        FROM menus m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.is_available = 1"; // ดึงเฉพาะเมนูที่พร้อมขาย
$params = [];

if ($search !== '') {
    $sql .= " AND m.name LIKE :search";
    $params[':search'] = "%$search%";
}
if ($filter_cat !== '') {
    $sql .= " AND m.category_id = :category";
    $params[':category'] = $filter_cat;
}
$sql .= " ORDER BY m.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ดึงหมวดหมู่มาทำปุ่มตัวกรอง ---
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- ดึงเมนูแนะนำ 3 อันดับแรก (ยอดวิวสูงสุด) ---
$rec_stmt = $conn->query("SELECT * FROM menus WHERE is_available = 1 ORDER BY views DESC LIMIT 3");
$recommended_menus = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Cafe Menu | หน้าแรก</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: var(--color-brown-light);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar h2 {
            margin: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background-color: var(--color-brown-dark);
            border-radius: 4px;
            font-size: 14px;
        }

        .navbar a:hover {
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* ส่วนค้นหา */
        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 10px;
            width: 250px;
            border: 1px solid var(--color-brown-light);
            border-radius: 4px;
        }

        .btn-search {
            padding: 10px 20px;
            background-color: var(--color-green);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        /* ปุ่มหมวดหมู่ */
        .category-tags {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .cat-tag {
            padding: 8px 15px;
            background-color: white;
            border: 1px solid var(--color-brown-light);
            color: var(--color-brown-dark);
            text-decoration: none;
            border-radius: 20px;
            transition: 0.3s;
        }

        .cat-tag:hover,
        .cat-tag.active {
            background-color: var(--color-brown-light);
            color: white;
        }

        /* Grid แสดงเมนู */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .menu-card:hover {
            transform: translateY(-5px);
        }

        .menu-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: var(--color-cream);
        }

        .menu-info {
            padding: 15px;
        }

        .menu-title {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: var(--color-brown-dark);
        }

        .menu-price {
            font-weight: bold;
            color: var(--color-green);
            font-size: 18px;
        }

        .menu-serve {
            font-size: 12px;
            background: var(--color-cream);
            padding: 3px 8px;
            border-radius: 10px;
            color: var(--color-brown-dark);
        }

        .section-title {
            border-left: 4px solid var(--color-brown-light);
            padding-left: 10px;
            margin-top: 30px;
        }
    </style>
</head>

<body>

<div class="navbar">
        <h2>☕ Cafe Menu</h2>
        
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if ($current_user): ?>
                <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.15); padding: 5px 15px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.3);">
                    <?php if(!empty($current_user['profile_image'])): ?>
                        <img src="uploads/profiles/<?php echo $current_user['profile_image']; ?>" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 35px; height: 35px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px;">👤</div>
                    <?php endif; ?>
                    
                    <div style="line-height: 1.2;">
                        <div style="font-weight: bold; color: white;"><?php echo htmlspecialchars($current_user['username']); ?></div>
                        <div style="font-size: 12px; color: #a5d6a7; display: flex; align-items: center; gap: 4px;">
                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #4CAF50; border-radius: 50%; box-shadow: 0 0 4px #4CAF50;"></span> ออนไลน์
                        </div>
                    </div>
                </div>

                <a href="user_edit_profile.php" style="background-color: var(--color-brown-light);">✏️ แก้ไขโปรไฟล์</a>

                <?php if ($is_admin): ?>
                    <a href="admin/dashboard.php">⚙️ Dashboard</a>
                <?php endif; ?>
                
                <a href="logout.php" style="background-color: #d9534f;">ออกจากระบบ</a>
                
            <?php else: ?>
                <a href="login_user.php" style="background-color: var(--color-green);">👤 เข้าสู่ระบบ (User)</a>
                <a href="login_admin.php" style="background-color: var(--color-brown-dark);">🔒 เข้าสู่ระบบ (Admin)</a>
            <?php endif; ?>
        </div>
</div>
        </div>
</div>

    <div class="container">

        <form action="index1.php" method="GET" class="search-bar">
            <strong style="color: var(--color-brown-dark);">ค้นหาเมนู:</strong>
            <input type="text" name="search" class="search-input" placeholder="พิมพ์ชื่อเมนู..."
                value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">ค้นหา</button>
            <?php if ($search != '' || $filter_cat != ''): ?>
                <a href="index1.php" style="color: red; text-decoration: none; margin-left: 10px;">ล้างการค้นหา</a>
            <?php endif; ?>
        </form>

        <div class="category-tags">
            <a href="index1.php" class="cat-tag <?php echo ($filter_cat == '') ? 'active' : ''; ?>">ทั้งหมด</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index1.php?category=<?php echo $cat['id']; ?>"
                    class="cat-tag <?php echo ($filter_cat == $cat['id']) ? 'active' : ''; ?>">
                    <?php echo $cat['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($search == '' && $filter_cat == '' && count($recommended_menus) > 0): ?>
            <h2 class="section-title">⭐ เมนูแนะนำยอดฮิต</h2>
            <div class="menu-grid" style="margin-bottom: 40px;">
                <?php foreach ($recommended_menus as $menu): ?>
                    <a href="menu_detail.php?id=<?php echo $menu['id']; ?>" class="menu-card" style="border: 2px solid gold;">
                        <?php if ($menu['image_name']): ?>
                            <img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="menu-img">
                        <?php else: ?>
                            <div class="menu-img" style="display:flex; align-items:center; justify-content:center; color:#999;">
                                ไม่มีรูปภาพ</div>
                        <?php endif; ?>
                        <div class="menu-info">
                            <h3 class="menu-title"><?php echo $menu['name']; ?></h3>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="menu-price"><?php echo number_format($menu['price'], 2); ?> ฿</span>
                                <span class="menu-serve">👁️ <?php echo $menu['views']; ?> วิว</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title">เมนูเครื่องดื่มและเบเกอรี่</h2>
        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
                <a href="menu_detail.php?id=<?php echo $menu['id']; ?>" class="menu-card">
                    <?php if ($menu['image_name']): ?>
                        <img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="menu-img">
                    <?php else: ?>
                        <div class="menu-img" style="display:flex; align-items:center; justify-content:center; color:#999;">
                            ไม่มีรูปภาพ</div>
                    <?php endif; ?>
                    <div class="menu-info">
                        <h3 class="menu-title"><?php echo $menu['name']; ?></h3>
                        <div style="margin-bottom: 10px;">
                            <span class="menu-serve"><?php echo $menu['description']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="menu-price"><?php echo number_format($menu['price'], 2); ?> ฿</span>
                            <span style="font-size: 12px; color: #888;"><?php echo $menu['category_name']; ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (count($menus) == 0): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 8px;">
                    ไม่พบเมนูที่คุณค้นหา 😥
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>