<?php
session_start();
require_once 'config/db_connect.php';

// --- โค้ดเพิ่มใหม่: เช็คสถานะการล็อกอินเพื่อให้ Navbar แสดงรูปโปรไฟล์ ---
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
// --------------------------------------------------------

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
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'>ไม่พบเมนูนี้ หรือเมนูนี้หมดชั่วคราว<br><br><a href='index1.php'>กลับหน้าหลัก</a></div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($menu['name']); ?> | Cafe Menu</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background-color: var(--color-cream);
            font-family: 'Prompt', sans-serif;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 40px 20px;
        }

        .detail-card {
            background: white;
            max-width: 900px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
        }

        .detail-img {
            width: 100%;
            max-width: 450px;
            min-height: 400px;
            object-fit: cover;
            background-color: #f9f9f9;
        }

        .detail-info {
            padding: 40px;
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .btn-back {
            display: inline-block;
            padding: 8px 15px;
            background: var(--color-brown-light);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            align-self: flex-start;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: var(--color-brown-dark);
        }

        .title {
            color: var(--color-brown-dark);
            font-size: 32px;
            margin: 0 0 10px 0;
        }

        .category {
            color: #888;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .serve-type {
            background: var(--color-cream);
            color: var(--color-brown-dark);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            border: 1px solid var(--color-brown-light);
            margin-bottom: 20px;
        }

        .price {
            font-size: 36px;
            color: var(--color-green);
            font-weight: bold;
            margin-bottom: 30px;
        }

        .views-counter {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px dashed var(--color-brown-light);
            color: var(--color-brown-dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
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
                <div class="detail-img" style="display:flex; align-items:center; justify-content:center; color:#999;">
                    ไม่มีรูปภาพ</div>
            <?php endif; ?>

            <div class="detail-info">
                <a href="index1.php" class="btn-back">← กลับไปเลือกเมนูต่อ</a>

                <h1 class="title"><?php echo $menu['name']; ?></h1>
                <div class="category">หมวดหมู่: <?php echo $menu['category_name']; ?></div>

                <div>
                    <span class="serve-type">รูปแบบ: <?php echo $menu['description']; ?></span>
                </div>

                <div class="price"><?php echo number_format($menu['price'], 2); ?> ฿</div>

                <div class="views-counter">
                    <span>👁️</span> เมนูนี้มีคนสนใจเข้าดูแล้ว <strong><?php echo $menu['views']; ?></strong> ครั้ง
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>