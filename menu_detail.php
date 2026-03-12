<?php
session_start();
require_once 'config/db_connect.php';

// --- เช็คสถานะการล็อกอิน ---
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

// อัปเดตยอดวิว
$update_views = $conn->prepare("UPDATE menus SET views = views + 1 WHERE id = :id");
$update_views->execute([':id' => $id]);

// ดึงข้อมูลเมนู
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

// --- FAVORITE SYSTEM ---
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $fav_stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND menu_id = :menu_id");
    $fav_stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':menu_id' => $menu['id']
    ]);
    if ($fav_stmt->fetch()) {
        $is_favorite = true;
    }
}

// --- REVIEW SYSTEM ---
/* คะแนนเฉลี่ย */
$avg_stmt = $conn->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM reviews
    WHERE menu_id = :menu_id
");
$avg_stmt->execute([':menu_id' => $id]);
$avg = $avg_stmt->fetch(PDO::FETCH_ASSOC);

$avg_rating = $avg['avg_rating'] ? round($avg['avg_rating'], 1) : 0;
$total_reviews = $avg['total_reviews'];

/* ดึงรีวิวทั้งหมด พร้อมรูปโปรไฟล์ */
$review_stmt = $conn->prepare("
    SELECT r.*, u.username, u.profile_image
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

        /* Container หลัก ปรับให้เรียงจากบนลงล่าง (สินค้าอยู่บน - รีวิวอยู่ล่าง) */
        .main-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            gap: 40px;
            width: 100%;
            box-sizing: border-box;
        }

        /* ================= ส่วนข้อมูลเมนูด้านบน ================= */
        .detail-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            /* แบ่งครึ่งซ้ายขวา และปัดลงเมื่อเป็นจอมือถือ */
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
            /* จัดให้อยู่กึ่งกลางแนวตั้งพอดีกับรูป */
        }

        /* รองรับจอมือถือ */
        @media (max-width: 768px) {
            .detail-img {
                max-width: 100%;
                height: 350px;
                min-height: auto;
            }

            .detail-info {
                padding: 25px;
            }
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
            font-size: 36px;
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
            font-size: 38px;
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

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }

        .favorite-btn {
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            color: white;
            transition: 0.3s;
        }

        .favorite-add {
            background: #ff5c5c;
        }

        .favorite-add:hover {
            background: #e04a4a;
        }

        .favorite-remove {
            background: #777;
        }

        .favorite-remove:hover {
            background: #555;
        }

        /* ================= ส่วนรีวิวด้านล่าง ================= */
        .review-section {
            width: 100%;
        }

        .review-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .review-card h3 {
            color: var(--color-brown-dark);
            margin-top: 0;
            font-size: 24px;
            border-bottom: 2px solid var(--color-cream);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .review-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
            background: #fffdf9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #f5e6c8;
        }

        .avg-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avg {
            font-size: 48px;
            color: #ffc107;
            font-weight: bold;
            line-height: 1;
        }

        .review-btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--color-brown-dark);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
        }

        .review-btn:hover {
            opacity: 0.8;
        }

        .review-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
            position: relative;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-actions {
            position: absolute;
            right: 0;
            top: 20px;
        }

        .action-btn {
            font-size: 13px;
            text-decoration: none;
            margin-left: 10px;
            padding: 6px 12px;
            border-radius: 4px;
            transition: 0.3s;
        }

        .edit-btn {
            background: #f0f8ff;
            color: #007bff;
            border: 1px solid #cce5ff;
        }

        .edit-btn:hover {
            background: #cce5ff;
        }

        .delete-btn {
            background: #ffebee;
            color: #dc3545;
            border: 1px solid #ffcdd2;
        }

        .delete-btn:hover {
            background: #ffcdd2;
        }

        .hidden-review {
            display: none;
        }

        .show-more-btn {
            margin-top: 20px;
            padding: 12px 20px;
            background: var(--color-brown-light);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-family: 'Prompt';
            font-size: 16px;
            transition: 0.3s;
        }

        .show-more-btn:hover {
            background: var(--color-brown-dark);
        }

        .star-color {
            color: #ffc107;
            font-size: 18px;
        }

        .reviewer-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .reviewer-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eee;
        }

        .reviewer-default {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="main-wrapper">

        <div class="detail-card">
            <?php if ($menu['image_name']): ?>
                <img src="uploads/menus/<?php echo $menu['image_name']; ?>" class="detail-img">
            <?php else: ?>
                <div class="detail-img" style="display:flex; align-items:center; justify-content:center; color:#999;">
                    ไม่มีรูปภาพ</div>
            <?php endif; ?>

            <div class="detail-info">
                <a href="index1.php" class="btn-back">← กลับหน้าร้านค้า</a>
                <h1 class="title"><?php echo $menu['name']; ?></h1>
                <div class="category">หมวดหมู่: <?php echo $menu['category_name']; ?></div>
                <div><span class="serve-type">รูปแบบ: <?php echo $menu['description']; ?></span></div>
                <div class="price"><?php echo number_format($menu['price'], 2); ?> ฿</div>

                <?php if (!$is_admin): ?>
                    <div class="action-group">
                        <form action="add_to_cart.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" name="quantity" value="1" min="1" max="20"
                                    style="width: 60px; padding: 10px; border-radius: 4px; border: 1px solid var(--color-brown-light); text-align: center; font-family: 'Prompt';">
                                <button type="submit"
                                    style="padding: 10px 20px; background-color: var(--color-green); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-family: 'Prompt';">
                                    🛒 เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </form>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form action="favorite_process.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                                <?php if ($is_favorite): ?>
                                    <button type="submit" class="favorite-btn favorite-remove">💔 ลบออกจากเมนูโปรด</button>
                                <?php else: ?>
                                    <button type="submit" class="favorite-btn favorite-add">❤️ เพิ่มเมนูโปรด</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div style="color: #ff5c5c; font-size: 13px; margin-top: -10px; margin-bottom: 20px;">
                            * กรุณาเข้าสู่ระบบเพื่อสั่งซื้อ หรือใช้งานฟังก์ชันเมนูโปรด
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div
                        style="background-color: #fff3e0; padding: 15px; border-radius: 8px; color: #e65100; margin-bottom: 20px; text-align: center; border: 1px dashed #ffb74d;">
                        🛑 <strong>มุมมองผู้ดูแลระบบ:</strong> แอดมินไม่สามารถสั่งซื้อหรือกดเมนูโปรดได้
                    </div>
                <?php endif; ?>

                <div class="views-counter">
                    <span>👁️</span> เมนูนี้มีคนสนใจเข้าดูแล้ว <strong
                        style="margin-left: 5px; margin-right: 5px; font-size: 16px;"><?php echo $menu['views']; ?></strong>
                    ครั้ง
                </div>
            </div>
        </div>

        <div class="review-section">
            <div class="review-card">
                <h3>⭐ รีวิวจากลูกค้า</h3>

                <div class="review-header-flex">
                    <div class="avg-container">
                        <div class="avg"><?php echo $avg_rating; ?></div>
                        <div style="color: #888; font-size: 15px;">
                            <span class="star-color">★</span> เต็ม 5<br>
                            (จากทั้งหมด <?php echo $total_reviews; ?> รีวิว)
                        </div>
                    </div>

                    <?php if (!$is_admin): ?>
                        <a class="review-btn" href="write_review.php?menu_id=<?php echo $menu['id']; ?>">✍️
                            เขียนรีวิวของคุณ</a>
                    <?php else: ?>
                        <div style="color: #888; font-size: 14px;">
                            🛑 แอดมินไม่สามารถเขียนรีวิวได้
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($reviews) == 0): ?>
                    <div style="text-align: center; color: #999; padding: 30px 0; font-size: 18px;">
                        ยังไม่มีรีวิวสำหรับเมนูนี้ 📝<br>มารีวิวเป็นคนแรกสิ!
                    </div>
                <?php endif; ?>

                <?php
                $show_limit = 5; // ปรับให้แสดง 5 คอมเมนต์แรก (เพราะพื้นที่กว้างขึ้น)
                $index = 0;
                foreach ($reviews as $review):
                    $hidden = $index >= $show_limit ? "hidden-review" : "";
                    ?>
                    <div class="review-item <?php echo $hidden; ?>">

                        <div class="reviewer-header">
                            <?php if (!empty($review['profile_image'])): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($review['profile_image']); ?>"
                                    class="reviewer-img">
                            <?php else: ?>
                                <div class="reviewer-default">👤</div>
                            <?php endif; ?>
                            <strong
                                style="color: var(--color-brown-dark); font-size: 16px;"><?php echo htmlspecialchars($review['username']); ?></strong>
                        </div>

                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                            <div class="review-actions">
                                <a class="action-btn edit-btn"
                                    href="edit_review.php?id=<?php echo $review['id']; ?>&menu_id=<?php echo $menu['id']; ?>">✏️
                                    แก้ไข</a>
                                <a class="action-btn delete-btn"
                                    href="delete_review.php?id=<?php echo $review['id']; ?>&menu_id=<?php echo $menu['id']; ?>"
                                    onclick="return confirm('คุณต้องการลบรีวิวนี้ใช่หรือไม่?');">🗑️ ลบ</a>
                            </div>
                        <?php endif; ?>

                        <div style="margin: 5px 0 10px 0;">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                echo ($i <= $review['rating']) ? '<span class="star-color">★</span>' : '<span style="color:#ddd;">★</span>';
                            }
                            ?>
                        </div>

                        <div
                            style="color: #444; font-size: 15px; line-height: 1.6; background: #fafafa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--color-brown-light);">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        <div style="font-size: 12px; color: #999; margin-top: 10px; text-align: right;">
                            🕒 โพสต์เมื่อ: <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <?php
                    $index++;
                endforeach;
                ?>

                <?php if (count($reviews) > $show_limit): ?>
                    <button id="showMoreBtn" class="show-more-btn">อ่านรีวิวเพิ่มเติม 🔽</button>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>

    <script>
        const btn = document.getElementById("showMoreBtn");
        if (btn) {
            let expanded = false;
            btn.addEventListener("click", function () {
                const hiddenReviews = document.querySelectorAll(".hidden-review");
                if (!expanded) {
                    hiddenReviews.forEach(function (el) { el.style.display = "block"; });
                    btn.innerText = "ย่อรีวิว 🔼";
                    expanded = true;
                } else {
                    hiddenReviews.forEach(function (el) { el.style.display = "none"; });
                    btn.innerText = "อ่านรีวิวเพิ่มเติม 🔽";
                    expanded = false;
                }
            });
        }
    </script>

</body>

</html>