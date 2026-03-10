<?php
session_start();
require_once '../config/db_connect.php';

// ป้องกันคนไม่ได้ล็อกอิน หรือไม่ใช่ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index1.php");
    exit;
}
// ดึงข้อมูลรูปโปรไฟล์ของผู้ใช้ที่ล็อกอินอยู่
// โค้ดใหม่: เพิ่ม username เข้าไปในคำสั่ง SELECT
$user_stmt = $conn->prepare("SELECT username, profile_image FROM userscafe WHERE id = :id");
$user_stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
// ==========================================
// ส่วนดึงข้อมูลตัวชี้วัด (Metrics) จากฐานข้อมูล
// ==========================================

// 1. จำนวนเมนูทั้งหมด
$stmt1 = $conn->query("SELECT COUNT(*) FROM menus");
$total_menus = $stmt1->fetchColumn();

// 2. จำนวนหมวดหมู่
$stmt2 = $conn->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt2->fetchColumn();

// 3. จำนวนผู้ใช้งานในระบบ (ดึงจากตาราง userscafe)
$stmt3 = $conn->query("SELECT COUNT(*) FROM userscafe");
$total_users = $stmt3->fetchColumn();

// 4. (แถม) เมนูที่สถานะ "หมด" (is_available = 0)
$stmt4 = $conn->query("SELECT COUNT(*) FROM menus WHERE is_available = 0");
$out_of_stock = $stmt4->fetchColumn();

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--color-cream);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            display: inline-block;
        }

        .btn-green {
            background-color: var(--color-green);
        }

        .btn-brown {
            background-color: var(--color-brown-light);
        }

        .btn-red {
            background-color: #d9534f;
        }

        /* สไตล์สำหรับกล่อง Dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--color-cream);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--color-brown-light);
        }

        .stat-card h3 {
            margin: 0;
            color: var(--color-brown-dark);
            font-size: 18px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: var(--color-brown-dark);
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="container">
<div class="header">
            <h2>⚙️ ระบบจัดการร้าน (Dashboard)</h2>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; background: var(--color-cream); padding: 5px 15px; border-radius: 30px; border: 1px solid var(--color-brown-light);">
                    <?php if(!empty($current_user['profile_image'])): ?>
                        <img src="../uploads/profiles/<?php echo $current_user['profile_image']; ?>" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 35px; height: 35px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px;">👤</div>
                    <?php endif; ?>
                    
                    <div style="line-height: 1.2;">
                        <div style="font-weight: bold; color: var(--color-brown-dark);"><?php echo htmlspecialchars($current_user['username']); ?></div>
                        <div style="font-size: 12px; color: #4CAF50; display: flex; align-items: center; gap: 4px;">
                            <span style="display: inline-block; width: 8px; height: 8px; background-color: #4CAF50; border-radius: 50%; box-shadow: 0 0 4px #4CAF50;"></span> กำลังใช้งาน
                        </div>
                    </div>
                </div>

                <a href="edit_profile.php" class="btn btn-brown" style="padding: 6px 12px; font-size: 14px;">✏️ แก้ไขโปรไฟล์</a>
                <a href="../logout.php" class="btn btn-red" style="padding: 6px 12px; font-size: 14px;">ออกจากระบบ</a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>รายการเมนูทั้งหมด</h3>
                <div class="number"><?php echo $total_menus; ?></div>
                <span style="font-size: 14px;">รายการ</span>
            </div>
            <div class="stat-card" style="background-color: #e8f5e9; border-color: var(--color-green);">
                <h3>หมวดหมู่สินค้า</h3>
                <div class="number"><?php echo $total_categories; ?></div>
                <span style="font-size: 14px;">หมวดหมู่</span>
            </div>
            <div class="stat-card" style="background-color: #fff3e0; border-color: orange;">
                <h3>เมนูที่สินค้าหมด</h3>
                <div class="number" style="color: orange;"><?php echo $out_of_stock; ?></div>
                <span style="font-size: 14px;">รายการ</span>
            </div>
            <div class="stat-card" style="background-color: #f3e5f5; border-color: purple;">
                <h3>ผู้ใช้งานในระบบ</h3>
                <div class="number" style="color: purple;"><?php echo $total_users; ?></div>
                <span style="font-size: 14px;">บัญชี</span>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 2px solid var(--color-cream); padding-top: 20px;">
            <h3>เครื่องมือจัดการ</h3>
            <a href="manage_menu.php" class="btn btn-green">☕ จัดการเมนู (เพิ่ม/ลบ/แก้ไข)</a>
            <a href="../index1.php" class="btn btn-brown">🏠 ดูหน้าร้าน (Frontend)</a>
        </div>
    </div>
</body>

</html>