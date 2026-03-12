<?php
session_start();
require_once 'config/db_connect.php';

// 1. เช็คการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';

// ==========================================
// 2. ระบบบันทึกการแก้ไขโปรไฟล์
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);

    // จัดการอัปโหลดรูปภาพ
    $update_img_sql = "";
    $params = [':username' => $new_username, ':id' => $user_id];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        $file_type = mime_content_type($tmp_name);

        if (in_array($file_type, $allowed)) {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_img_name = "user_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = "uploads/profiles/";

            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($tmp_name, $upload_dir . $new_img_name)) {
                $update_img_sql = ", profile_image = :profile_image";
                $params[':profile_image'] = $new_img_name;
            }
        } else {
            $msg = "<div class='alert-error'>ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น</div>";
        }
    }

    // จัดการรหัสผ่าน
    $update_pass_sql = "";
    if (!empty($new_password)) {
        $update_pass_sql = ", password = :password";
        $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // อัปเดตลงฐานข้อมูล
    if (empty($msg)) {
        $sql = "UPDATE userscafe SET username = :username {$update_img_sql} {$update_pass_sql} WHERE id = :id";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['username'] = $new_username; // อัปเดต session
            $msg = "<div class='alert-success'>✅ บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว</div>";
        }
    }
}

// ==========================================
// 3. ดึงข้อมูลผู้ใช้งานปัจจุบัน
// ==========================================
$stmt = $conn->prepare("SELECT * FROM userscafe WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

// ==========================================
// 4. ดึงประวัติการสั่งซื้อ (Order History)
// ==========================================
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = :id ORDER BY created_at DESC");
$order_stmt->execute([':id' => $user_id]);
$orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 5. ดึงประวัติการรีวิว (Review History)
// ==========================================
$review_stmt = $conn->prepare("
    SELECT r.*, m.name as menu_name, m.image_name as menu_image 
    FROM reviews r 
    JOIN menus m ON r.menu_id = m.id 
    WHERE r.user_id = :id 
    ORDER BY r.created_at DESC
");
$review_stmt->execute([':id' => $user_id]);
$reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ของฉัน | Moom Marm Cafe</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: var(--color-cream); font-family: 'Prompt', sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        
        .dashboard-container { 
            max-width: 1200px; 
            margin: 40px auto; 
            padding: 0 20px; 
            display: flex; 
            gap: 30px; 
            align-items: flex-start;
            flex-wrap: wrap;
        }

        /* ฝั่งซ้าย: แก้ไขโปรไฟล์ */
        .profile-side { 
            flex: 0 0 350px; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
            text-align: center; 
        }
        
        /* ฝั่งขวา: ประวัติ */
        .history-side { 
            flex: 1; 
            min-width: 300px; 
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }
        
        .card-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .card-title { color: var(--color-brown-dark); margin-top: 0; border-bottom: 2px solid var(--color-cream); padding-bottom: 15px; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px; }

        /* สไตล์ฟอร์มโปรไฟล์ */
        .profile-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--color-cream); margin-bottom: 15px; }
        .profile-default { width: 120px; height: 120px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 15px; color: white; }
        .form-group { text-align: left; margin-bottom: 15px; }
        .form-group label { display: block; font-size: 14px; color: var(--color-brown-dark); margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-family: 'Prompt'; }
        
        .btn-save { background: var(--color-green); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; transition: 0.3s; font-family: 'Prompt'; font-weight: bold;}
        .btn-save:hover { background: #3d8b40; }
        .btn-back { background: var(--color-brown-light); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: block; text-align: center; margin-top: 10px; transition: 0.3s; }
        .btn-back:hover { background: var(--color-brown-dark); }

        /* สไตล์ตารางสั่งซื้อ */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: var(--color-brown-dark); font-weight: bold; background: #fafafa; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }

        /* สไตล์ลิสต์รีวิว */
        .review-list { display: flex; flex-direction: column; gap: 15px; }
        .review-item { display: flex; gap: 15px; padding: 15px; border: 1px solid #eee; border-radius: 8px; align-items: flex-start; }
        .review-menu-img { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .review-content { flex: 1; }
        .review-menu-name { font-weight: bold; color: var(--color-brown-dark); text-decoration: none; font-size: 16px; }
        .review-menu-name:hover { text-decoration: underline; }
        .star-color { color: #ffc107; font-size: 14px; }
        .review-text { font-size: 14px; color: #555; margin-top: 5px; background: #fafafa; padding: 8px; border-radius: 6px; }
        
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center;}
        .alert-error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center;}
        .empty-text { text-align: center; color: #999; padding: 20px; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="dashboard-container">
        
        <div class="profile-side">
            <h2 style="color: var(--color-brown-dark); margin-top: 0;">⚙️ ตั้งค่าบัญชีผู้ใช้</h2>
            <div style="color: #4caf50; font-size: 14px; margin-bottom: 20px;">● กำลังออนไลน์</div>

            <?php echo $msg; ?>

            <form action="user_edit_profile.php" method="POST" enctype="multipart/form-data">
                
                <?php if (!empty($current_user['profile_image'])): ?>
                        <img src="uploads/profiles/<?php echo $current_user['profile_image']; ?>" class="profile-preview">
                <?php else: ?>
                        <div class="profile-default">👤</div>
                <?php endif; ?>

                <div class="form-group">
                    <label>เปลี่ยนรูปโปรไฟล์ (JPG, PNG):</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/png, image/jpeg">
                </div>

                <div class="form-group">
                    <label>ชื่อผู้ใช้งาน (Username):</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>รหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน):</label>
                    <input type="password" name="password" class="form-control" placeholder="กรอกรหัสผ่านใหม่...">
                </div>

                <button type="submit" name="update_profile" class="btn-save">💾 บันทึกการเปลี่ยนแปลง</button>
                <a href="index1.php" class="btn-back">← กลับหน้าแรก</a>
            </form>
        </div>

        <div class="history-side">
            
            <div class="card-box">
                <h3 class="card-title">📦 ประวัติการสั่งซื้อของฉัน</h3>
                <?php if (count($orders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>รหัสสั่งซื้อ</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ยอดรวม</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td style="font-weight: bold; color: var(--color-brown-dark);">#<?php echo $order['id']; ?></td>
                                            <td style="font-size: 14px; color: #666;"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td style="color: var(--color-green); font-weight: bold;"><?php echo number_format($order['total_price'], 2); ?> ฿</td>
                                            <td>
                                                <?php if ($order['status'] === 'completed'): ?>
                                                        <span class="status-badge status-completed">✅ เสร็จสิ้น</span>
                                                <?php else: ?>
                                                        <span class="status-badge status-pending">⏳ กำลังเตรียม</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                <?php else: ?>
                        <div class="empty-text">คุณยังไม่มีประวัติการสั่งซื้อ ☕</div>
                <?php endif; ?>
            </div>

            <div class="card-box">
                <h3 class="card-title">⭐ ประวัติการรีวิวของฉัน</h3>
                <?php if (count($reviews) > 0): ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $rev): ?>
                                    <div class="review-item">
                                        <?php if ($rev['menu_image']): ?>
                                                <img src="uploads/menus/<?php echo htmlspecialchars($rev['menu_image']); ?>" class="review-menu-img">
                                        <?php else: ?>
                                                <div class="review-menu-img" style="background: #eee; display:flex; align-items:center; justify-content:center; font-size:10px; color:#999;">No IMG</div>
                                        <?php endif; ?>
                                
                                        <div class="review-content">
                                            <a href="menu_detail.php?id=<?php echo $rev['menu_id']; ?>" class="review-menu-name">
                                                <?php echo htmlspecialchars($rev['menu_name']); ?>
                                            </a>
                                            <div style="margin: 3px 0;">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo ($i <= $rev['rating']) ? '<span class="star-color">★</span>' : '<span style="color:#ddd;">★</span>';
                                                }
                                                ?>
                                                <span style="font-size: 11px; color: #999; margin-left: 5px;">(<?php echo date('d/m/Y', strtotime($rev['created_at'])); ?>)</span>
                                            </div>
                                            <div class="review-text">
                                                "<?php echo nl2br(htmlspecialchars($rev['comment'])); ?>"
                                            </div>
                                        </div>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                <?php else: ?>
                        <div class="empty-text">คุณยังไม่เคยเขียนรีวิวให้เมนูไหนเลย 📝</div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <?php include 'footer.php'; ?>

</body>
</html>